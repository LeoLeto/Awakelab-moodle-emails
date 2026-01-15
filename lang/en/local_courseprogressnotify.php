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

$string['pluginname'] = 'Course progress notifications';

// Scheduled task names.
$string['task_check_progress_25'] = 'Progress notification 25%';
$string['task_check_progress_50'] = 'Progress notification 50%';
$string['task_check_course_end_soon'] = 'Reminder: 7 days before course end';
$string['task_check_course_last_day'] = 'Reminder: last day of course';
$string['task_check_zoom_sessions'] = 'Zoom session reminders';
$string['task_check_presential_sessions'] = 'On-site session reminders';
$string['task_check_diploma_available'] = 'Reminder: diploma available (30 days)';

// Manual run page.
$string['runpage:title'] = 'Manual progress verification';
$string['runpage:heading'] = 'Run progress check now';
$string['runpage:desc'] = 'You can manually run different notification checks. Select the type you want to test:';
$string['runpage:type_progress'] = 'Progress Notifications (25% and 50%)';
$string['runpage:confirm_progress'] = 'Check all students in the configured category and send emails to those who have reached 25% or 50% progress (if they haven\'t been notified yet).';
$string['runpage:type_courseend'] = 'Course End Notifications (7 days before and last day)';
$string['runpage:confirm_courseend'] = 'Check courses ending soon (exactly 7 days away) or today, and send reminder emails to enrolled students (if they haven\'t been notified yet).';
$string['runpage:confirm'] = 'Click the button below to start the verification. This will check all students in the configured category and send emails to those who have reached 25% or 50% progress (if they haven\'t been notified yet).';
$string['run_progress_button'] = 'Test Progress Emails';
$string['run_courseend_button'] = 'Test Course End Emails';
$string['backtosettings'] = 'Back to settings';
$string['run_now_button'] = 'Run Now';
$string['run_now_done'] = 'Verification completed successfully.';
$string['run_now_error'] = 'An error occurred while running:';
$string['runpage:nocategory'] = 'No category selected in settings. Select a category to enable the plugin operations.';

// Settings.
$string['settings:category'] = 'Target category';
$string['settings:category_desc'] = 'Select the category where this plugin will apply. If none is selected ("None"), the plugin will not perform any operation.';
$string['settings:category:none'] = 'None (disabled)';
$string['settings:zoomdaysbefore'] = 'Days before Zoom invitation';
$string['settings:zoomdaysbefore_desc'] = 'Number of days before the Zoom session date to send the automatic invitation.';
$string['settings:presentialdaysbefore'] = 'Days before on-site sessions';
$string['settings:presentialdaysbefore_desc'] = 'Number of days before the on-site session (exam/tutoring) to send the reminder.';

// Run block in settings page.
$string['settings:run:desc'] = 'Manually run the progress verification from this page. It will apply only to the selected category.';

// Progress table.
$string['progress:header:activity'] = 'Activity';
$string['progress:header:status'] = 'Status';
$string['progress:status:complete'] = 'Complete';
$string['progress:status:incomplete'] = 'Incomplete';

// Privacy.
$string['privacy:metadata'] = 'The plugin local_courseprogressnotify stores logs of notifications sent to users.';
$string['privacy:metadata:local_courseprogressnotify_log'] = 'Log of notifications sent';
$string['privacy:metadata:local_courseprogressnotify_log:userid'] = 'Recipient user id';
$string['privacy:metadata:local_courseprogressnotify_log:courseid'] = 'Course associated with the notification';
$string['privacy:metadata:local_courseprogressnotify_log:notification_type'] = 'Type of notification sent';
$string['privacy:metadata:local_courseprogressnotify_log:entityid'] = 'Associated entity id (e.g. Zoom or session)';
$string['privacy:metadata:local_courseprogressnotify_log:time_sent'] = 'Timestamp of the send';

// Email templates (English fallback).
$string['email_zoom_subject'] = 'Zoom session for course {{coursename}}';
$string['email_zoom_body'] = '<p>Hello {{firstname}}!</p>

<p>We are writing regarding the course <strong>{{coursename}}</strong> you are taking.</p>

<p>Please remember that on <strong>{{zoom_date}}</strong>, from <strong>{{zoom_start}}</strong> to <strong>{{zoom_end}}</strong>, there will be a live Zoom session with the tutor.</p>

<p>On the day of the session, when you access the platform, you will see the link to join the video call directly.</p>

<p>This session aims to:</p>
<ul>
  <li>Resolve course questions</li>
  <li>Deepen specific content</li>
  <li>Offer a more dynamic and practical session</li>
</ul>

<p>We look forward to seeing you!</p>';

$string['email_25_subject'] = '25% progress for course {{coursename}}';
$string['email_25_body'] = '<p>Welcome {{firstname}},</p>

<p>You have reached <strong>25%</strong> of the course <strong>{{coursename}}</strong>. Below is your progress to date:</p>

{{progress_table}}

<p>There is still time to finish the course, which ends on <strong>{{courseenddate}}</strong>. To complete it, you must:</p>

<ul>
  <li>Reach a minimum connection of 75% of total hours</li>
  <li>View 100% of the contents</li>
  <li>Complete the assessment activities</li>
</ul>

<p>Please also complete the <strong>Student evaluation questionnaire</strong>, available in the quality evaluation section.</p>

<p>If you have any questions, contact us.</p>

<p>Regards,</p>';

$string['email_50_subject'] = 'Halfway progress for course {{coursename}}';
$string['email_50_body'] = '<p>Welcome {{firstname}}!</p>

<p>We have reached the middle of the course <strong>{{coursename}}</strong>. The course ends on <strong>{{courseenddate}}</strong>, and to complete it you need to view all content and complete the activities and assessments.</p>

<p>Here is your progress to date:</p>

{{progress_table}}

<p>Keep going, and contact us if you have any questions.</p>

<p>Regards,</p>';

$string['email_end_soon_subject'] = 'Final stretch of the course {{coursename}}';
$string['email_end_soon_body'] = '<p>Hello {{firstname}}!</p>

<p>This is a reminder that we are in the last week of the course <strong>{{coursename}}</strong>, which ends on <strong>{{courseenddate}}</strong>.</p>

<p>Remember the completion requirements:</p>

<ul>
  <li>Complete the course assessments</li>
  <li>View 100% of the contents</li>
  <li>Reach a minimum connection of 75% of the hours</li>
</ul>

<p>Make the most of these last days to finish the course.</p>

<p>All the best!</p>';

$string['email_last_day_subject'] = 'Course {{coursename}} final day instructions';
$string['email_last_day_body'] = '<p>Hello {{firstname}},</p>

<p>This is a reminder that tomorrow is the last training day of the course <strong>{{coursename}}</strong>.</p>

<p>It has been a pleasure to have you on the course and we hope it has been useful and enriching.</p>

<p>If you have not done so yet, please complete the assessments and ensure you have viewed 100% of the contents to obtain your diploma.</p>

<p>You can also complete the final satisfaction survey.</p>

<p>After the course ends, we will contact you to inform you about the availability of the diploma download.</p>

<p>Thank you for your participation and commitment.</p>

<p>Kind regards,</p>';

$string['email_exam_subject'] = 'Mandatory on-site exam for course {{coursename}}';
$string['email_exam_body'] = '<p>Hello {{firstname}},</p>

<p>Please remember that on <strong>{{exam_date}}</strong> the mandatory on-site exam for the course <strong>{{coursename}}</strong> is scheduled.</p>

<p><strong>Location:</strong> {{exam_location}}<br>
<strong>Time:</strong> from {{exam_start}} to {{exam_end}}</p>

<p>To access the exam, you must have completed all the course activities and assessments.</p>

<p>Please arrive early and bring your ID for verification.</p>

<p>Good luck!</p>';

$string['email_tutoring_subject'] = 'Mandatory on-site tutoring for course {{coursename}}';
$string['email_tutoring_body'] = '<p>Hello {{firstname}},</p>

<p>Please remember that on <strong>{{tutoring_date}}</strong> the mandatory on-site tutoring for the course <strong>{{coursename}}</strong> will take place.</p>

<p><strong>Location:</strong> {{tutoring_location}}<br>
<strong>Time:</strong> from {{tutoring_start}} to {{tutoring_end}}</p>

<p>During the tutoring there will be practical activities, content presentation, and Q&A.</p>

<p>We recommend preparing your questions in advance.</p>

<p>See you soon!</p>';

$string['email_diploma_subject'] = 'Diploma for course {{coursename}}';
$string['email_diploma_body'] = '<p>Dear {{firstname}},</p>

<p>Starting today, the diploma for the course <strong>{{coursename}}</strong> is available in the virtual campus.</p>

<p>You can access the campus using the following link:</p>

<p><a href="{{campus_url}}">{{campus_url}}</a></p>

<p>To download the diploma you must have completed all course requirements.</p>

<p>If you have not done so yet, please complete the final satisfaction survey.</p>

<p>Thank you for your participation. We encourage you to keep learning with us.</p>

<p>Regards,</p>';
