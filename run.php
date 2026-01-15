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

require_once(__DIR__ . '/../../config.php');

use core\output\notification;
use local_courseprogressnotify\task\check_progress_25;
use local_courseprogressnotify\task\check_progress_50;
use local_courseprogressnotify\task\check_course_end_soon;
use local_courseprogressnotify\task\check_course_last_day;

require_login();
$context = context_system::instance();
require_capability('local/courseprogressnotify:run', $context);

$url = new moodle_url('/local/courseprogressnotify/run.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('runpage:title', 'local_courseprogressnotify'));
$PAGE->set_heading(get_string('runpage:title', 'local_courseprogressnotify'));

$type = optional_param('type', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($confirm && !empty($type) && confirm_sesskey()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('runpage:heading', 'local_courseprogressnotify'));
    
    core_php_time_limit::raise(600);
    ignore_user_abort(true);

    // Execute the selected tasks based on type.
    // Capture both mtrace and direct output.
    $output = [];
    $errors = [];
    
    $output[] = '=== Starting Manual Check ===';
    $output[] = 'Type: ' . $type;
    $output[] = 'Time: ' . userdate(time());
    $output[] = 'Category ID: ' . get_config('local_courseprogressnotify', 'categoryid');
    $output[] = '';
    
    ob_start();
    
    if ($type === 'progress') {
        try {
            $output[] = '--- Executing 25% Progress Check ---';
            $t25 = new check_progress_25();
            $t25->execute();
            $output[] = ob_get_contents();
        } catch (Throwable $e) {
            $errors[] = '25% task: ' . $e->getMessage();
            $output[] = 'ERROR in 25% task: ' . $e->getMessage();
        }
        ob_clean();
        
        try {
            $output[] = '\n--- Executing 50% Progress Check ---';
            $t50 = new check_progress_50();
            $t50->execute();
            $output[] = ob_get_contents();
        } catch (Throwable $e) {
            $errors[] = '50% task: ' . $e->getMessage();
            $output[] = 'ERROR in 50% task: ' . $e->getMessage();
        }
    } else if ($type === 'courseend') {
        try {
            $output[] = '--- Executing Course End Soon Check (7 days before) ---';
            $tendSoon = new check_course_end_soon();
            $tendSoon->execute();
            $output[] = ob_get_contents();
        } catch (Throwable $e) {
            $errors[] = 'End soon task: ' . $e->getMessage();
            $output[] = 'ERROR in end soon task: ' . $e->getMessage();
        }
        ob_clean();
        
        try {
            $output[] = '\n--- Executing Course Last Day Check ---';
            $tlastDay = new check_course_last_day();
            $tlastDay->execute();
            $output[] = ob_get_contents();
        } catch (Throwable $e) {
            $errors[] = 'Last day task: ' . $e->getMessage();
            $output[] = 'ERROR in last day task: ' . $e->getMessage();
        }
    }
    
    ob_end_clean();
    
    $output[] = '';
    $output[] = '=== Manual Check Completed ===';
    
    $taskoutput = implode("\n", $output);

    if (empty($errors)) {
        echo $OUTPUT->notification(get_string('run_now_done', 'local_courseprogressnotify'), notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->notification(get_string('run_now_error', 'local_courseprogressnotify') . ' ' . s(implode(' | ', $errors)), notification::NOTIFY_ERROR);
    }

    // Always show output for debugging
    echo html_writer::tag('h4', 'Debug Output:');
    echo html_writer::tag('pre', s($taskoutput), ['class' => 'local-courseprogressnotify-output', 'style' => 'background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 600px; overflow-y: auto;']);

    $backlink = html_writer::link(new moodle_url('/local/courseprogressnotify/run.php'),
        get_string('backtosettings', 'local_courseprogressnotify'));
    echo html_writer::div($backlink, 'mt-3');
    echo $OUTPUT->footer();
    exit;
}

// Show the page with the action buttons for different types.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('runpage:heading', 'local_courseprogressnotify'));

$categoryid = (int)get_config('local_courseprogressnotify', 'categoryid');
if (!$categoryid) {
    echo $OUTPUT->notification(get_string('runpage:nocategory', 'local_courseprogressnotify'), notification::NOTIFY_WARNING);
} else {
    echo html_writer::tag('p', get_string('runpage:desc', 'local_courseprogressnotify'));
    
    // Progress checks (25% and 50%)
    echo $OUTPUT->box_start('generalbox mb-3');
    echo html_writer::tag('h4', get_string('runpage:type_progress', 'local_courseprogressnotify'));
    echo html_writer::tag('p', get_string('runpage:confirm_progress', 'local_courseprogressnotify'));
    
    $formurl = new moodle_url('/local/courseprogressnotify/run.php', ['type' => 'progress', 'confirm' => 1, 'sesskey' => sesskey()]);
    echo html_writer::link($formurl, get_string('run_progress_button', 'local_courseprogressnotify'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->box_end();
    
    // Course end checks (7 days before and last day)
    echo $OUTPUT->box_start('generalbox mb-3');
    echo html_writer::tag('h4', get_string('runpage:type_courseend', 'local_courseprogressnotify'));
    echo html_writer::tag('p', get_string('runpage:confirm_courseend', 'local_courseprogressnotify'));
    
    $formurl = new moodle_url('/local/courseprogressnotify/run.php', ['type' => 'courseend', 'confirm' => 1, 'sesskey' => sesskey()]);
    echo html_writer::link($formurl, get_string('run_courseend_button', 'local_courseprogressnotify'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->box_end();
    echo $OUTPUT->box_end();
}

$backlink = html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'local_courseprogressnotify']),
    get_string('backtosettings', 'local_courseprogressnotify'));
echo html_writer::div($backlink, 'mt-3');

echo $OUTPUT->footer();