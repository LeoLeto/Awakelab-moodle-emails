<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;
use local_courseprogressnotify\zoom_provider;

/**
 * Task to notify users X days before Zoom sessions.
 */
class check_zoom_sessions extends scheduled_task {

    public function get_name() {
        return get_string('task_check_zoom_sessions', 'local_courseprogressnotify');
    }

    public function execute() {
        global $CFG;
        mtrace('Running task: zoom sessions');

        $days = (int)get_config('local_courseprogressnotify', 'zoomdaysbefore');
        if ($days <= 0) { $days = 2; }

        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        if (!$categoryid) {
            mtrace('No category configured; skipping.');
            return;
        }

        $now = time();
        // Window covers exactly the day that is $days ahead.
        $from = usergetmidnight($now + ($days * DAYSECS));
        $to   = $from + DAYSECS;

        $sessions = zoom_provider::get_upcoming_between($from, $to);
        if (empty($sessions)) {
            return;
        }

        foreach ($sessions as $session) {
            $course = get_course($session->course);
            if ((int)$course->category !== $categoryid) { continue; }
            $students = $this->get_course_students($course->id);
            if (empty($students)) { continue; }

            foreach ($students as $user) {
                if (notification_log::has_sent($user->id, $course->id, 'zoom_reminder', $session->id)) {
                    continue;
                }
                $datefmt = get_string('strftimedate', 'langconfig');
                $timefmt = get_string('strftimetime', 'langconfig');
                $start = $session->start_time;
                $end = $session->start_time + ((int)$session->duration * 60);
                $placeholders = [
                    'zoom_name' => format_string($session->name, true, ['context' => \context_module::instance($session->cmid)]),
                    'zoom_date' => userdate($start, $datefmt, \core_date::get_user_timezone($user)),
                    'zoom_start' => userdate($start, $timefmt, \core_date::get_user_timezone($user)),
                    'zoom_end' => userdate($end, $timefmt, \core_date::get_user_timezone($user)),
                    'zoom_time' => userdate($start, $timefmt, \core_date::get_user_timezone($user)) . ' - ' . userdate($end, $timefmt, \core_date::get_user_timezone($user)),
                    'zoom_link' => (new \moodle_url('/mod/zoom/view.php', ['id' => $session->cmid]))->out(false),
                ];
                email_builder::send($user, $course, 'zoom', $placeholders, 'zoom_reminder', $session->id);
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
