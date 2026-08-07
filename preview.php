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
use local_courseprogressnotify\email_builder;

require_login();
$context = context_system::instance();
require_capability('local/courseprogressnotify:run', $context);

$previewlang = optional_param('lang', 'es', PARAM_ALPHA);
if (!in_array($previewlang, ['es', 'ca', 'en'], true)) {
    $previewlang = 'es';
}

$url = new moodle_url('/local/courseprogressnotify/preview.php', ['lang' => $previewlang]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('previewpage:title', 'local_courseprogressnotify'));
$PAGE->set_heading(get_string('previewpage:title', 'local_courseprogressnotify'));

// Load the raw template strings for the selected language directly from the plugin lang file,
// exactly as email_builder does in combined mode.
$strings = email_builder::load_lang_strings($previewlang);

// Sample progress table using the same markup as progress_calculator::build_progress_table_html().
$headeractivity = $strings['progress:header:activity'] ?? get_string('progress:header:activity', 'local_courseprogressnotify');
$headerstatus = $strings['progress:header:status'] ?? get_string('progress:header:status', 'local_courseprogressnotify');
$statuscomplete = $strings['progress:status:complete'] ?? get_string('progress:status:complete', 'local_courseprogressnotify');
$statusincomplete = $strings['progress:status:incomplete'] ?? get_string('progress:status:incomplete', 'local_courseprogressnotify');

$sampletable = html_writer::start_tag('table', ['class' => 'generaltable local-courseprogressnotify-progress']);
$sampletable .= html_writer::start_tag('thead');
$sampletable .= html_writer::tag('tr', html_writer::tag('th', s($headeractivity)) . html_writer::tag('th', s($headerstatus)));
$sampletable .= html_writer::end_tag('thead');
$sampletable .= html_writer::start_tag('tbody');
$samplerows = [
    ['Tema 1', $statuscomplete],
    ['Tema 2', $statuscomplete],
    ['Tema 3', $statusincomplete],
];
foreach ($samplerows as [$activity, $status]) {
    $sampletable .= html_writer::tag('tr', html_writer::tag('td', s($activity)) . html_writer::tag('td', s($status)));
}
$sampletable .= html_writer::end_tag('tbody');
$sampletable .= html_writer::end_tag('table');

$dateformat = get_string('strftimedatetime', 'langconfig');
$pix = fn(string $file): string => (new moodle_url('/local/courseprogressnotify/pix/' . $file))->out(false);

// Sample values for every placeholder any task can provide. Image URLs match the ones the tasks send.
$sampleplaceholders = [
    'firstname' => 'María',
    'lastname' => 'García',
    'coursename' => 'Curso de ejemplo',
    'campus_url' => (new moodle_url('/'))->out(false),
    'courseenddate' => userdate(time() + 30 * DAYSECS, $dateformat),
    'progress_percentage' => '25',
    'progress_table' => $sampletable,
    'image_progress_25' => $pix('email_progress_report_25.png'),
    'image_progress_50' => $pix('email_progress_report_50.png'),
    'image_documentation' => $pix('email_documentation_location.png'),
    'image_quality_survey' => $pix('email_quality_survey_location.png'),
    'image_tutorial' => $pix('email_tutorial_video.png'),
    'image_zoom_link_es' => $pix('updated_es_email_zoom_link_location.png'),
    'image_zoom_link_ca' => $pix('updated_ca_email_zoom_link_location.png'),
    'zoom_date' => userdate(time() + 2 * DAYSECS, $dateformat),
    'zoom_start' => '10:00',
    'zoom_end' => '12:00',
    'exam_date' => userdate(time() + 7 * DAYSECS, $dateformat),
    'exam_start' => '09:00',
    'exam_end' => '11:00',
    'exam_location' => 'Aula 1',
    'tutoring_date' => userdate(time() + 5 * DAYSECS, $dateformat),
    'tutoring_start' => '16:00',
    'tutoring_end' => '17:00',
    'tutoring_location' => 'Aula 2',
];

// Collect every email template key present in the lang file, preserving file order.
$templatekeys = [];
foreach (array_keys($strings) as $stringkey) {
    if (preg_match('/^email_([a-z0-9_]+)_subject$/', $stringkey, $matches)) {
        $templatekeys[] = $matches[1];
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('previewpage:heading', 'local_courseprogressnotify'));
echo html_writer::tag('p', get_string('previewpage:desc', 'local_courseprogressnotify'));

// Combined-email mode notice, so the reviewer knows what students actually receive.
$sendcombined = get_config('local_courseprogressnotify', 'send_combined_email');
if ($sendcombined) {
    echo $OUTPUT->notification(get_string('previewpage:combined_on', 'local_courseprogressnotify'), notification::NOTIFY_INFO);
} else {
    echo $OUTPUT->notification(get_string('previewpage:combined_off', 'local_courseprogressnotify'), notification::NOTIFY_INFO);
}

// Language switcher.
echo html_writer::start_div('mb-4');
echo html_writer::tag('strong', get_string('previewpage:language', 'local_courseprogressnotify') . ' ');
foreach (['es' => 'Español', 'ca' => 'Català', 'en' => 'English'] as $langcode => $langname) {
    $class = 'btn btn-sm mr-1 ' . ($langcode === $previewlang ? 'btn-primary' : 'btn-outline-primary');
    $langurl = new moodle_url('/local/courseprogressnotify/preview.php', ['lang' => $langcode]);
    echo html_writer::link($langurl, $langname, ['class' => $class]);
}
echo html_writer::end_div();

foreach ($templatekeys as $key) {
    $subjecttpl = $strings['email_' . $key . '_subject'] ?? '';
    $bodytpl = $strings['email_' . $key . '_body'] ?? '';

    $subject = email_builder::replace_placeholders($subjecttpl, $sampleplaceholders);
    $body = email_builder::replace_placeholders($bodytpl, $sampleplaceholders);

    // Highlight any placeholder the plugin never fills, so broken templates are easy to spot.
    $missing = [];
    if (preg_match_all('/\{\{([a-z0-9_]+)\}\}/', $subject . ' ' . $body, $matches)) {
        $missing = array_unique($matches[1]);
    }
    $highlight = fn(string $html): string => preg_replace(
        '/\{\{([a-z0-9_]+)\}\}/',
        '<span style="background:#f8d7da;color:#842029;padding:0 4px;border-radius:3px;font-family:monospace;">{{$1}}</span>',
        $html
    );

    echo $OUTPUT->box_start('generalbox mb-4');
    echo html_writer::tag('h4', 'email_' . $key, ['class' => 'mb-3']);

    if (!empty($missing)) {
        echo $OUTPUT->notification(
            get_string('previewpage:missing', 'local_courseprogressnotify') . ' ' . s(implode(', ', $missing)),
            notification::NOTIFY_WARNING
        );
    }

    echo html_writer::div(
        html_writer::tag('strong', get_string('previewpage:subject', 'local_courseprogressnotify') . ': ') . $highlight(s($subject)),
        'mb-2'
    );
    echo html_writer::div($highlight($body), 'border rounded p-3 bg-white');
    echo $OUTPUT->box_end();
}

$backlink = html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'local_courseprogressnotify']),
    get_string('backtosettings', 'local_courseprogressnotify'));
echo html_writer::div($backlink, 'mt-3');

echo $OUTPUT->footer();
