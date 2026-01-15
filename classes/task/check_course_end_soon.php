<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;

/**
 * Task to notify users 7 days before course end date.
 */
class check_course_end_soon extends scheduled_task {

    public function get_name() {
        return get_string('task_check_course_end_soon', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB;
        mtrace('=== Running task: course end soon (7 days) ===');
        $now = time();

        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('\u2717 No category configured; skipping.');
            return;
        }
        mtrace("\u2713 Category ID configured: {$categoryid}");

        $courses = $DB->get_records_select('course', 'visible = 1 AND enddate > 0 AND category = :cat', ['cat' => $categoryid]);
        $coursecount = count($courses);
        mtrace("Found {$coursecount} course(s) with end dates in category");
        
        $processedcount = 0;
        $sentcount = 0;
        
        foreach ($courses as $course) {
            $days = (int)floor(($course->enddate - $now) / DAYSECS);
            mtrace("\nCourse {$course->id} ({$course->fullname}): ends in {$days} days (" . userdate($course->enddate) . ")");
            
            if ($days !== 7) { // Send only exactly 7 days before.
                mtrace("  \u2192 Not exactly 7 days away, skipping");
                continue;
            }
            
            mtrace("  \u2713 Exactly 7 days before end date!");
            $students = $this->get_course_students($course->id);
            $studentcount = count($students);
            mtrace("  Found {$studentcount} enrolled student(s)");
            
            foreach ($students as $user) {
                $processedcount++;
                if (notification_log::has_sent($user->id, $course->id, 'course_end_soon')) {
                    mtrace("  User {$user->id} ({$user->email}): already notified");
                    continue;
                }
                $placeholders = [
                    'courseenddate' => $this->format_date_for_user($user, $course->enddate),
                ];
                $result = email_builder::send($user, $course, 'end_soon', $placeholders, 'course_end_soon');
                if ($result) {
                    $sentcount++;
                }
            }
        }
        
        mtrace("\n=== Summary: Processed {$processedcount} student(s), sent {$sentcount} email(s) ===");
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

    protected function format_date_for_user(\stdClass $user, int $timestamp): string {
        if (empty($timestamp)) { return ''; }
        $defaulttz = \core_date::get_user_timezone($user);
        return userdate($timestamp, get_string('strftimedatetime', 'langconfig'), $defaulttz);
    }
}
