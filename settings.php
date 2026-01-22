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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_courseprogressnotify', get_string('pluginname', 'local_courseprogressnotify'));

    // Custom field shortname for per-course notification control.
    $settings->add(new admin_setting_configtext(
        'local_courseprogressnotify/customfield_shortname',
        get_string('customfield_shortname', 'local_courseprogressnotify'),
        get_string('customfield_shortname_desc', 'local_courseprogressnotify'),
        'courseemailnotifications_enabled',
        PARAM_TEXT
    ));

    // Plugin version info
    $plugin = new stdClass();
    require(__DIR__ . '/version.php');
    $versioninfo = html_writer::tag('div', 
        html_writer::tag('strong', 'Version: ') . $plugin->release . ' (Build: ' . $plugin->version . ')',
        ['style' => 'padding: 10px; background: #e8f4f8; border-left: 4px solid #0066cc; margin-bottom: 20px;']
    );
    $settings->add(new admin_setting_heading(
        'local_courseprogressnotify_version',
        '',
        $versioninfo
    ));

    // Days before Zoom session to send invitation reminder.
    $settings->add(new admin_setting_configtext(
        'local_courseprogressnotify/zoomdaysbefore',
        get_string('settings:zoomdaysbefore', 'local_courseprogressnotify'),
        get_string('settings:zoomdaysbefore_desc', 'local_courseprogressnotify'),
        2,
        PARAM_INT
    ));

    // Days before presential session (exam/tutoring) to send reminder.
    $settings->add(new admin_setting_configtext(
        'local_courseprogressnotify/presentialdaysbefore',
        get_string('settings:presentialdaysbefore', 'local_courseprogressnotify'),
        get_string('settings:presentialdaysbefore_desc', 'local_courseprogressnotify'),
        2,
        PARAM_INT
    ));

    // Add a manual run block inside the settings page.
    $runurl = new moodle_url('/local/courseprogressnotify/run.php');
    $customfield = get_config('local_courseprogressnotify', 'customfield_shortname');
    $disabled = empty($customfield);
    $desc = html_writer::tag('p', get_string('settings:run:desc', 'local_courseprogressnotify'));
    if ($disabled) {
        $desc .= $OUTPUT->notification(get_string('runpage:nocategory', 'local_courseprogressnotify'), \core\output\notification::NOTIFY_WARNING);
    } else {
        $desc .= html_writer::link(
            $runurl,
            get_string('run_now_button', 'local_courseprogressnotify'),
            ['class' => 'btn btn-primary']
        );
    }

    $settings->add(new admin_setting_heading('local_courseprogressnotify_runblock', get_string('runpage:heading', 'local_courseprogressnotify'), $desc));

    $ADMIN->add('localplugins', $settings);
}
