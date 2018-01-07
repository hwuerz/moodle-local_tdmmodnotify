<?php
// This file is part of UploadNotification plugin for Moodle - http://moodle.org/
//
// UploadNotification is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// UploadNotification is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with UploadNotification.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upload notification.
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

// Include function library.
require_once(dirname(__FILE__) . '/definitions.php');
require_once(dirname(__FILE__) . '/classes/forms/course_form.php');
require_once(dirname(__FILE__) . '/classes/models/course_settings_model.php');

// Globals.
global $DB, $CFG, $OUTPUT, $USER, $SITE, $PAGE;

require_login();

$homeurl = new moodle_url('/');
$course_id = required_param('id', PARAM_INT);
try {
    $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
} catch (dml_exception $e) {
    redirect($homeurl,
        get_string('settings_course_require_valid_course_id', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        5);
}
require_login($course, true);

$PAGE->set_url("/mod/" . LOCAL_UPLOADNOTIFICATION_NAME . "/course.php", array('id' => $course_id));
$PAGE->set_title('Uploadnotification Settings');
$PAGE->set_heading('Uploadnotification Settings');


// Only add settings item on non-site course pages.
if ($course_id == 1) {
    redirect($homeurl,
        get_string('settings_course_require_valid_course_id', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        5);
}

// Only let users with the appropriate capability see this settings item.
if (!has_capability('moodle/backup:backupcourse', context_course::instance($course_id))) {
    redirect($homeurl,
        get_string('settings_course_require_valid_course_admin', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        5);
}

echo $OUTPUT->header();

$settings = new local_uploadnotification_course_settings_model($course_id);

// Display config.
$course_form = new local_uploadnotification_course_form(null, array(
    'id' => $course_id,
    'fullname' => $course->fullname,
    'enable_mail' => $settings->is_mail_enabled(),
    'allow_attachment' => $settings->is_attachment_allowed(),
    'enable_changelog' => $settings->is_changelog_enabled(),
    'enable_diff' => $settings->is_diff_enabled()));

// Evaluate form data.
$data = $course_form->get_data();
if ($data) {
    $settings->set_mail_enabled($data->enable_mail);
    $settings->set_attachment_allowed($data->allow_attachment);
    $settings->set_changelog_enabled($data->enable_changelog);
    $settings->set_diff_enabled($data->enable_diff);
    $settings->save();
    \core\notification::success(get_string('settings_saved_successfully', LOCAL_UPLOADNOTIFICATION_FULL_NAME));
}

$course_form->display();


// Footing  =========================================================.

echo $OUTPUT->footer();
