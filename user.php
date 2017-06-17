<?php
// This file is part of uploadnotification for Moodle - http://moodle.org/
//
// uploadnotification is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// uploadnotification is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with uploadnotification.  If not, see <http://www.gnu.org/licenses/>.

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
require_once(dirname(__FILE__).'/classes/forms/uploadnotification_user_form.php');
require_once(dirname(__FILE__).'/classes/models/user_settings_model.php');

// Globals.
global $DB, $CFG, $OUTPUT, $USER, $SITE, $PAGE;

//require_login($course, true);
$PAGE->set_url("/mod/$pluginname/user.php");//, array('id' => $course_id));
$PAGE->set_title('My modules page title');
$PAGE->set_heading('My modules page heading');

$homeurl = new moodle_url('/');
require_login();

// Only add settings item on non-site course pages.
if (!$USER->id) {
    redirect($homeurl, "This feature is only available for valid users.", 5);
}

echo $OUTPUT->header();

$settings = new user_settings_model($USER->id);

// Display global config
$user_form = new uploadnotification_user_form(null, array(
    'id' => $USER->id,
    'enable' => $settings->is_mail_enabled(),
    'attachment' => $settings->is_attachment_enabled()));

// Evaluate form data
$data = $user_form->get_data();
if ($data) {
    $settings->set_mail_enabled($data->enable);
    $settings->set_attachment_enabled($data->attachment);
    $settings->save();
}

$user_form->display();


// Footing  =========================================================.

echo $OUTPUT->footer();
