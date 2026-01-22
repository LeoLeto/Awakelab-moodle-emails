<?php
namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\notification_log;

/**
 * Task to notify users on the first day of course about initial tasks.
 */
class check_first_day_tasks extends scheduled_task {

    public function get_name() {
        return get_string('task_check_first_day_tasks', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB, $CFG;
        
        mtrace('Running task: first day tasks notification');
        
        $customfieldshortname = get_config('local_courseprogressnotify', 'customfield_shortname');
        
        if (empty($customfieldshortname)) {
            mtrace('No custom field configured; skipping.');
            return;
        }
        
        mtrace("Using custom field: {$customfieldshortname}");

        $now = time();
        $todaystart = usergetmidnight($now);
        $todayend = $todaystart + DAYSECS;

        mtrace("Checking courses starting today: " . userdate($todaystart, '%Y-%m-%d'));

        // Courses starting today
        $courses = $DB->get_records_select('course', 
            'visible = 1 AND startdate >= :from AND startdate < :to', 
            [
                'from' => $todaystart,
                'to' => $todayend,
            ]
        );
        
        // Filter courses based on custom field
        $courses = array_filter($courses, function($course) use ($customfieldshortname) {
            return $this->is_course_enabled($course->id, $customfieldshortname);
        });
        
        if (empty($courses)) {
            mtrace('  No courses starting today.');
            return;
        }

        $totalnotifs = 0;
        
        foreach ($courses as $course) {
            mtrace("  Course: {$course->fullname} (starts: " . userdate($course->startdate, '%Y-%m-%d') . ")");
            
            $students = $this->get_course_students($course->id);
            if (empty($students)) {
                mtrace("    No students found.");
                continue;
            }
            
            $notified = 0;
            
            foreach ($students as $user) {
                if (notification_log::has_sent($user->id, $course->id, 'first_day_tasks')) {
                    mtrace("    ✓ Already sent to {$user->firstname} {$user->lastname} ({$user->email}) - skipping");
                    continue;
                }
                
                // Generate image URLs (all screenshots are in Catalan)
                $imgdocurl = new \moodle_url('/local/courseprogressnotify/pix/email_documentation_location.png');
                $imgtuturl = new \moodle_url('/local/courseprogressnotify/pix/email_tutorial_video.png');
                
                $placeholders = [
                    'image_documentation' => $imgdocurl->out(false),
                    'image_tutorial' => $imgtuturl->out(false),
                ];
                
                email_builder::send($user, $course, 'first_day', $placeholders, 'first_day_tasks');
                $notified++;
                $totalnotifs++;
            }
            
            mtrace("    Notified: {$notified}");
        }
        
        mtrace("First day tasks check complete. Total notifications sent: {$totalnotifs}");
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

    /**
     * Check if notifications are enabled for a course via custom field.
     * @param int $courseid Course ID
     * @param string $customfieldshortname Custom field shortname from settings
     * @return bool True if notifications should be sent for this course
     */
    private function is_course_enabled($courseid, $customfieldshortname) {
        global $DB;
        
        $field = $DB->get_record('customfield_field', ['shortname' => $customfieldshortname]);
        if (!$field) {
            return false;
        }
        
        $data = $DB->get_record('customfield_data', [
            'fieldid' => $field->id,
            'instanceid' => $courseid
        ]);
        
        return $data && $data->value == 1;
    }
}
