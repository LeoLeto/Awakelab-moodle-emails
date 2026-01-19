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
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        
        mtrace('Running task: diploma available (30 days after course end)');
        
        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('No category configured; skipping.');
            return;
        }

        $now = time();
        $targetdaystart = usergetmidnight($now - (30 * DAYSECS));
        $targetdayend = $targetdaystart + DAYSECS;

        mtrace("Checking courses that ended exactly 30 days ago: " . userdate($targetdaystart, '%Y-%m-%d'));

        // Courses that ended exactly 30 days ago in selected category.
        $courses = $DB->get_records_select('course', 
            'visible = 1 AND enddate > 0 AND enddate >= :from AND enddate < :to AND category = :cat', 
            [
                'from' => $targetdaystart,
                'to' => $targetdayend,
                'cat' => $categoryid,
            ]
        );
        
        if (empty($courses)) {
            mtrace('  No courses ended exactly 30 days ago.');
            return;
        }

        $totalnotifs = 0;
        
        foreach ($courses as $course) {
            mtrace("  Course: {$course->fullname} (ended: " . userdate($course->enddate, '%Y-%m-%d') . ")");
            
            $students = $this->get_course_students($course->id);
            if (empty($students)) {
                mtrace("    No students found.");
                continue;
            }
            
            $passed = 0;
            $notified = 0;
            
            foreach ($students as $user) {
                if (notification_log::has_sent($user->id, $course->id, 'diploma_available')) {
                    continue;
                }
                
                if ($this->user_is_approved_in_course($user->id, $course->id)) {
                    $passed++;
                    $placeholders = [
                        'campus_url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                    ];
                    email_builder::send($user, $course, 'diploma', $placeholders, 'diploma_available');
                    $notified++;
                    $totalnotifs++;
                }
            }
            
            mtrace("    Students passed: {$passed}, Notified: {$notified}");
        }
        
        mtrace("Diploma check complete. Total notifications sent: {$totalnotifs}");
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
        global $DB;
        
        // Get the course grade item
        $gradeitem = \grade_item::fetch(['courseid' => $courseid, 'itemtype' => 'course']);
        
        if (!$gradeitem) {
            return false;
        }
        
        // Get student's final grade
        $grade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $userid]);
        
        if (!$grade || $grade->finalgrade === null) {
            return false; // No grade yet
        }
        
        // Check gradepass - if not set or 0, we could either require explicit pass grade or accept any grade
        // Here we're strict: must have gradepass > 0 and student must meet it
        $gradepass = $gradeitem->gradepass ?? 0;
        
        if ($gradepass <= 0) {
            // No passing grade configured - strict interpretation: don't send diploma
            return false;
        }
        
        $finalgrade = (float)$grade->finalgrade;
        return $finalgrade >= $gradepass;
    }
}
