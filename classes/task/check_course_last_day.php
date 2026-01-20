<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;

/**
 * Task to notify users on the last day of the course.
 */
class check_course_last_day extends scheduled_task {

    public function get_name() {
        return get_string('task_check_course_last_day', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB;
        mtrace('=== Running task: course last day (day before) ===');

        $now = time();
        $today = usergetmidnight($now);
        $tomorrow = $today + DAYSECS;
        $dayAfterTomorrow = $tomorrow + DAYSECS;

        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('\u2717 No category configured; skipping.');
            return;
        }
        mtrace("\u2713 Category ID configured: {$categoryid}");
        mtrace("Looking for courses ending tomorrow: " . userdate($tomorrow) . " to " . userdate($dayAfterTomorrow));

        // Find courses ending TOMORROW (so today is the day before the last day)
        $courses = $DB->get_records_select('course', 'visible = 1 AND enddate > 0 AND enddate >= :tomorrow AND enddate < :dayafter AND category = :cat', ['tomorrow' => $tomorrow, 'dayafter' => $dayAfterTomorrow, 'cat' => $categoryid]);
        
        $coursecount = count($courses);
        mtrace("Found {$coursecount} course(s) ending tomorrow in category");
        
        $processedcount = 0;
        $sentcount = 0;
        
        foreach ($courses as $course) {
            mtrace("\nCourse {$course->id} ({$course->fullname}): ends tomorrow (" . userdate($course->enddate) . ")");
            $students = $this->get_course_students($course->id);
            $studentcount = count($students);
            mtrace("  Found {$studentcount} enrolled student(s)");
            
            foreach ($students as $user) {
                $processedcount++;
                if (notification_log::has_sent($user->id, $course->id, 'course_last_day')) {
                    mtrace("  User {$user->id} ({$user->email}): already notified");
                    continue;
                }
                $imgurl = new \moodle_url('/local/courseprogressnotify/pix/email_quality_survey_location.png');
                $placeholders = [
                    'courseenddate' => $this->format_date_for_user($user, $course->enddate),
                    'image_quality_survey' => $imgurl->out(false),
                ];
                $result = email_builder::send($user, $course, 'last_day', $placeholders, 'course_last_day');
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
