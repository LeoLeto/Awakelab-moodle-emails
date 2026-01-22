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
        
        mtrace('Running task: diploma available (30 days after course end)');
        
        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        $customfieldshortname = get_config('local_courseprogressnotify', 'customfield_shortname');
        
        if (!$categoryid && empty($customfieldshortname)) {
            mtrace('No category or custom field configured; skipping.');
            return;
        }
        
        if (!empty($customfieldshortname)) {
            mtrace("Using custom field: {$customfieldshortname}");
        } else {
            mtrace("Category ID: {$categoryid}");
        }

        $now = time();
        $targetdaystart = usergetmidnight($now - (30 * DAYSECS));
        $targetdayend = $targetdaystart + DAYSECS;

        mtrace("Checking courses that ended exactly 30 days ago: " . userdate($targetdaystart, '%Y-%m-%d'));

        // Courses that ended exactly 30 days ago
        $courses = $DB->get_records_select('course', 
            'visible = 1 AND enddate > 0 AND enddate >= :from AND enddate < :to', 
            [
                'from' => $targetdaystart,
                'to' => $targetdayend,
            ]
        );
        
        // Filter courses based on custom field or category
        $courses = array_filter($courses, function($course) use ($categoryid, $customfieldshortname) {
            return $this->is_course_enabled($course->id, $course->category, $categoryid, $customfieldshortname);
        });
        
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
            
            $notified = 0;
            
            foreach ($students as $user) {
                if (notification_log::has_sent($user->id, $course->id, 'diploma_available')) {
                    mtrace("    ✓ Already sent to {$user->firstname} {$user->lastname} ({$user->email}) - skipping");
                    continue;
                }
                
                $placeholders = [
                    'campus_url' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                ];
                email_builder::send($user, $course, 'diploma', $placeholders, 'diploma_available');
                $notified++;
                $totalnotifs++;
            }
            
            mtrace("    Notified: {$notified}");
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

    private function is_course_enabled($courseid, $coursecategory, $configcategoryid, $customfieldshortname) {
        global $DB;
        if (!empty($customfieldshortname)) {
            $field = $DB->get_record('customfield_field', ['shortname' => $customfieldshortname]);
            if ($field) {
                $data = $DB->get_record('customfield_data', ['fieldid' => $field->id, 'instanceid' => $courseid]);
                return $data && $data->value == 1;
            }
            return false;
        }
        return $configcategoryid > 0 && $coursecategory == $configcategoryid;
    }
}
