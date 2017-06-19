<?php
// This file is part of uploadnotification for Moodle - http://moodle.org/
//
// MailTest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MailTest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with MailTest.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upload notification.
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$pluginname = 'uploadnotification';
require_once(dirname(__FILE__).'/../../config.php');

// Include function library.
require_once(dirname(__FILE__).'/classes/forms/course_form.php');
require_once(dirname(__FILE__).'/classes/models/course_settings_model.php');

// Globals.
global $DB, $CFG, $OUTPUT, $USER, $SITE, $PAGE;


$course_id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
require_login($course, true);

$PAGE->set_url("/mod/$pluginname/course.php", array('id' => $course_id));
$PAGE->set_title('Uploadnotification Settings');
$PAGE->set_heading('Uploadnotification Settings');

$homeurl = new moodle_url('/');
require_login();

// Only add settings item on non-site course pages.
if ($course_id == 1) {
    redirect($homeurl, "This feature is only available for valid course ids.", 5);
}

// Only let users with the appropriate capability see this settings item.
if (!has_capability('moodle/backup:backupcourse', context_course::instance($course_id))) {
    redirect($homeurl, "This feature is only available for valid course ids.", 5);
}

echo $OUTPUT->header();

$settings = new local_uploadnotification_course_settings_model($course_id);

// Display global config
$course_form = new local_uploadnotification_course_form(null, array(
    'id' => $course_id,
    'fullname' => $course->fullname,
    'enable' => $settings->is_mail_enabled(),
    'attachment' => $settings->is_attachment_enabled()));

// Evaluate form data
$data = $course_form->get_data();
if ($data) {
    $settings->set_mail_enabled($data->enable);
    $settings->set_attachment_enabled($data->attachment);
    $settings->save();
}

$course_form->display();


// Footing  =========================================================.

echo $OUTPUT->footer();
