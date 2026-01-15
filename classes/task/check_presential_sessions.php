<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;

/**
 * Task to notify users before presential sessions (exam/tutoring).
 */
class check_presential_sessions extends scheduled_task {

    public function get_name() {
        return get_string('task_check_presential_sessions', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB;
        mtrace('Running task: presential sessions');

        $days = (int)get_config('local_courseprogressnotify', 'presentialdaysbefore');
        if ($days <= 0) { $days = 2; }

        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('No category configured; skipping.');
            return;
        }

        $now = time();
        $from = usergetmidnight($now + ($days * DAYSECS));
        $to   = $from + DAYSECS;

        $sql = "SELECT s.* , c.fullname
                  FROM {local_courseprogressnotify_sess} s
                  JOIN {course} c ON c.id = s.courseid
                 WHERE s.visible = 1 AND s.starttime >= :from AND s.starttime < :to AND c.visible = 1 AND c.category = :cat";
        $sessions = $DB->get_records_sql($sql, ['from' => $from, 'to' => $to, 'cat' => $categoryid]);
        foreach ($sessions as $sess) {
            $course = get_course($sess->courseid);
            $students = $this->get_course_students($course->id);
            if (empty($students)) { continue; }

            foreach ($students as $user) {
                $typekey = ($sess->type === 'exam') ? 'exam' : 'tutoring';
                $notiftype = 'presential_' . $typekey;
                if (notification_log::has_sent($user->id, $course->id, $notiftype, $sess->id)) {
                    continue;
                }
                $datefmt = get_string('strftimedate', 'langconfig');
                $timefmt = get_string('strftimetime', 'langconfig');
                $placeholders = [
                    $typekey . '_location' => s($sess->location),
                    $typekey . '_date' => userdate($sess->starttime, $datefmt, \core_date::get_user_timezone($user)),
                    $typekey . '_start' => userdate($sess->starttime, $timefmt, \core_date::get_user_timezone($user)),
                    $typekey . '_end' => userdate($sess->endtime, $timefmt, \core_date::get_user_timezone($user)),
                ];
                email_builder::send($user, $course, $typekey, $placeholders, $notiftype, $sess->id);
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
}
