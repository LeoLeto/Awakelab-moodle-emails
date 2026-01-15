<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;

/**
 * Task to notify users 30 days after course end if they are approved.
 */
class check_diploma_available extends scheduled_task {

    public function get_name() {
        return get_string('task_check_diploma_available', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB;
        mtrace('Running task: diploma available (30 days)');
        $now = time();
        $targetdaystart = usergetmidnight($now - (30 * DAYSECS));
        $targetdayend = $targetdaystart + DAYSECS;

        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('No category configured; skipping.');
            return;
        }

        // Courses that ended exactly 30 days ago in selected category.
        $courses = $DB->get_records_select('course', 'visible = 1 AND enddate > 0 AND enddate >= :from AND enddate < :to AND category = :cat', [
            'from' => $targetdaystart,
            'to' => $targetdayend,
            'cat' => $categoryid,
        ]);
        foreach ($courses as $course) {
            $students = $this->get_course_students($course->id);
            foreach ($students as $user) {
                if (notification_log::has_sent($user->id, $course->id, 'diploma_available')) {
                    continue;
                }
                if ($this->user_is_approved_in_course($user->id, $course->id)) {
                    $placeholders = [
                        'diploma_link' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                    ];
                    email_builder::send($user, $course, 'diploma', $placeholders, 'diploma_available');
                }
            }
        }
    }

    protected function get_course_students(int $courseid): array {
        global $DB;
        $sql = "SELECT DISTINCT u.*
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id AND ue.status = 0
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid AND e.status = 0
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :ctxlevel AND ctx.instanceid = :courseid2
                 WHERE u.deleted = 0 AND u.suspended = 0";
        $params = ['courseid' => $courseid, 'courseid2' => $courseid, 'ctxlevel' => CONTEXT_COURSE];
        return $DB->get_records_sql($sql, $params);
    }

    protected function user_is_approved_in_course(int $userid, int $courseid): bool {
        // Uses gradebook: compare final grade against gradepass if set.
        require_once(__DIR__ . '/../../../lib/gradelib.php');
        $coursegrade = grade_get_course_grade($userid, $courseid);
        if (!$coursegrade || $coursegrade->locked || $coursegrade->grade === null) {
            return false;
        }
        $gradeitem = \grade_item::fetch(['courseid' => $courseid, 'itemtype' => 'course']);
        if (!$gradeitem || is_null($gradeitem->gradepass) || $gradeitem->gradepass === '') {
            // If no pass grade set, consider not approved (strict interpretation).
            return false;
        }
        $final = (float)$coursegrade->grade;
        $pass = (float)$gradeitem->gradepass;
        return $final >= $pass;
    }
}
