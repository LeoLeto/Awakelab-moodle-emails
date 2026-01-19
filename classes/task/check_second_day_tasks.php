<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;

/**
 * Task to notify users on the second day of course about browser compatibility.
 */
class check_second_day_tasks extends scheduled_task {

    public function get_name() {
        return get_string('task_check_second_day_tasks', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB;
        
        mtrace('Running task: second day tasks notification (browser compatibility)');
        
        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('No category configured; skipping.');
            return;
        }

        $now = time();
        $yesterdaystart = usergetmidnight($now - DAYSECS);
        $yesterdayend = $yesterdaystart + DAYSECS;

        mtrace("Checking courses that started yesterday: " . userdate($yesterdaystart, '%Y-%m-%d'));

        // Courses that started yesterday (second day is today)
        $courses = $DB->get_records_select('course', 
            'visible = 1 AND startdate >= :from AND startdate < :to AND category = :cat', 
            [
                'from' => $yesterdaystart,
                'to' => $yesterdayend,
                'cat' => $categoryid,
            ]
        );
        
        if (empty($courses)) {
            mtrace('  No courses on their second day.');
            return;
        }

        $totalnotifs = 0;
        
        foreach ($courses as $course) {
            mtrace("  Course: {$course->fullname} (started: " . userdate($course->startdate, '%Y-%m-%d') . ")");
            
            $students = $this->get_course_students($course->id);
            if (empty($students)) {
                mtrace("    No students found.");
                continue;
            }
            
            $notified = 0;
            
            foreach ($students as $user) {
                if (notification_log::has_sent($user->id, $course->id, 'second_day_tasks')) {
                    continue;
                }
                
                $placeholders = [];
                
                email_builder::send($user, $course, 'second_day', $placeholders, 'second_day_tasks');
                $notified++;
                $totalnotifs++;
            }
            
            mtrace("    Notified: {$notified}");
        }
        
        mtrace("Second day tasks check complete. Total notifications sent: {$totalnotifs}");
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
