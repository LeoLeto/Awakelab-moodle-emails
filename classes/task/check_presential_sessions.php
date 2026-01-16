<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;
use local_courseprogressnotify\presential_provider;

/**
 * Task to notify users before presential sessions (exam/tutoring).
 * 
 * This task scans calendar events to detect presential sessions based on:
 * - Location field being populated
 * - "Presencial" keyword in title/description
 * Then classifies them as exam or tutoring based on keywords.
 */
class check_presential_sessions extends scheduled_task {

    public function get_name() {
        return get_string('task_check_presential_sessions', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB;
        mtrace('Running task: presential sessions (calendar-based detection)');

        $days = (int)get_config('local_courseprogressnotify', 'presentialdaysbefore');
        if ($days <= 0) { $days = 2; }

        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('No category configured; skipping.');
            return;
        }

        // Time window: N days from now (midnight to midnight)
        $now = time();
        $from = usergetmidnight($now + ($days * DAYSECS));
        $to   = $from + DAYSECS;

        mtrace("Checking presential events {$days} days ahead: " . userdate($from, '%Y-%m-%d') . " to " . userdate($to, '%Y-%m-%d'));

        // Get all courses in the configured category
        $courses = $DB->get_records('course', ['category' => $categoryid, 'visible' => 1]);
        
        $totalevents = 0;
        $totalnotifs = 0;

        foreach ($courses as $course) {
            // Get presential events for this course using smart detection
            $presentialevents = presential_provider::get_presential_events($course->id, $from, $to);
            
            if (empty($presentialevents)) {
                continue;
            }

            mtrace("  Course: {$course->fullname} - Found " . count($presentialevents) . " presential event(s)");
            $totalevents += count($presentialevents);

            // Get enrolled students
            $students = $this->get_course_students($course->id);
            if (empty($students)) {
                mtrace("    No students enrolled, skipping.");
                continue;
            }

            foreach ($presentialevents as $event) {
                $typekey = $event['type']; // 'exam' or 'tutoring'
                $notiftype = 'presential_' . $typekey;
                
                mtrace("    Event: {$event['name']} (Type: {$typekey}, Location: {$event['location']})");

                foreach ($students as $user) {
                    // Check if already notified (use event ID as entity)
                    if (notification_log::has_sent($user->id, $course->id, $notiftype, $event['id'])) {
                        continue;
                    }

                    // Prepare placeholders for email
                    $datefmt = get_string('strftimedate', 'langconfig');
                    $timefmt = get_string('strftimetime', 'langconfig');
                    $usertz = \core_date::get_user_timezone($user);

                    $placeholders = [
                        $typekey . '_location' => s($event['location']),
                        $typekey . '_date' => userdate($event['timestart'], $datefmt, $usertz),
                        $typekey . '_start' => userdate($event['timestart'], $timefmt, $usertz),
                        $typekey . '_end' => userdate($event['timeend'], $timefmt, $usertz),
                    ];

                    email_builder::send($user, $course, $typekey, $placeholders, $notiftype, $event['id']);
                    $totalnotifs++;
                }
            }
        }

        mtrace("Presential sessions check complete. Events found: {$totalevents}, Notifications sent: {$totalnotifs}");
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
