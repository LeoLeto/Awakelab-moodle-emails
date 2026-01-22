<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_courseprogressnotify\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_courseprogressnotify\email_builder;
use local_courseprogressnotify\progress_calculator;
use local_courseprogressnotify\notification_log;

/**
 * Task to notify users when they reach 25% progress.
 */
class check_progress_25 extends scheduled_task {

    public function get_name() {
        return get_string('task_check_progress_25', 'local_courseprogressnotify');
    }

    public function execute() {
        global $DB;
        mtrace('=== Running task: progress 25% ===');

        $categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
        $customfieldshortname = get_config('local_courseprogressnotify', 'customfield_shortname');
        
        if (!$categoryid && empty($customfieldshortname)) {
            mtrace('✗ No category or custom field configured; skipping.');
            return;
        }
        
        if (!empty($customfieldshortname)) {
            mtrace("✓ Using custom field: {$customfieldshortname}");
        } else {
            mtrace("✓ Using category ID: {$categoryid}");
        }

        // Fetch all courses with completion enabled and visible (we'll filter by custom field/category below).
        $courses = $DB->get_records('course', ['visible' => 1, 'enablecompletion' => 1], '', 'id, fullname, enddate, startdate, category');
        $now = time();
        
        // Filter courses based on custom field or category.
        $courses = array_filter($courses, function($course) use ($categoryid, $customfieldshortname) {
            return $this->is_course_enabled($course->id, $course->category, $categoryid, $customfieldshortname);
        });
        
        $coursecount = count($courses);
        mtrace("Found {$coursecount} eligible course(s) with completion enabled");

        if (empty($courses)) {
            mtrace('✗ No eligible courses found in the configured category.');
            return;
        }

        $processedcount = 0;
        $sentcount = 0;

        foreach ($courses as $course) {
            // Skip courses that ended long ago ( > 120 days ) to limit processing.
            if (!empty($course->enddate) && $course->enddate > 0 && $course->enddate < ($now - 120 * DAYSECS)) {
                mtrace("  Skipping course {$course->id} ({$course->fullname}): ended > 120 days ago");
                continue;
            }

            mtrace("\nProcessing course {$course->id}: {$course->fullname}");
            $students = $this->get_course_students($course->id);
            $studentcount = count($students);
            mtrace("  Found {$studentcount} enrolled student(s)");
            
            if (empty($students)) {
                continue;
            }

            foreach ($students as $user) {
                $processedcount++;
                if (notification_log::has_sent($user->id, $course->id, 'progress_25')) {
                    mtrace("  User {$user->id} ({$user->email}): already notified");
                    continue; // Already notified.
                }
                $percent = progress_calculator::get_progress_percentage($course, $user);
                mtrace("  User {$user->id} ({$user->email}): progress = {$percent}%");
                if ($percent >= 25.0) {
                    $imgurl = new \moodle_url('/local/courseprogressnotify/pix/email_progress_report_25.png');
                    $placeholders = [
                        'progress_percentage' => (string)$percent,
                        'courseenddate' => $this->format_date_for_user($user, $course->enddate),
                        'progress_table' => progress_calculator::build_progress_table_html($course, $user),
                        'image_progress_25' => $imgurl->out(false),
                    ];
                    $result = email_builder::send($user, $course, '25', $placeholders, 'progress_25');
                    if ($result) {
                        $sentcount++;
                    }
                } else {
                    mtrace("  → Below 25% threshold, skipping");
                }
            }
        }
        
        mtrace("\n=== Summary: Processed {$processedcount} student(s), sent {$sentcount} email(s) ===");
    }

    /**
     * Get enrolled students in course (role archetype = student) with active enrolments.
     *
     * @param int $courseid
     * @return array of user records
     */
    protected function get_course_students(int $courseid): array {
        global $DB;
        $context = \context_course::instance($courseid);
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
        if (empty($timestamp)) {
            return '';
        }
        // Format in user's timezone and language.
        $defaulttz = \core_date::get_user_timezone($user);
        return userdate($timestamp, get_string('strftimedatetime', 'langconfig'), $defaulttz);
    }

    /**
     * Check if notifications are enabled for a course.
     * @param int $courseid Course ID
     * @param int $coursecategory Course category ID
     * @param int $configcategoryid Configured category ID from settings
     * @param string $customfieldshortname Custom field shortname from settings
     * @return bool True if notifications should be sent for this course
     */
    private function is_course_enabled($courseid, $coursecategory, $configcategoryid, $customfieldshortname) {
        global $DB;
        
        // If custom field is configured, check it first.
        if (!empty($customfieldshortname)) {
            $field = $DB->get_record('customfield_field', ['shortname' => $customfieldshortname]);
            if ($field) {
                $data = $DB->get_record('customfield_data', [
                    'fieldid' => $field->id,
                    'instanceid' => $courseid
                ]);
                // Return true only if the custom field is explicitly set to 1 (checked).
                return $data && $data->value == 1;
            }
            // If custom field is configured but doesn't exist, don't fall back to category.
            return false;
        }
        
        // Fallback to category-based check.
        return $configcategoryid > 0 && $coursecategory == $configcategoryid;
    }
}
