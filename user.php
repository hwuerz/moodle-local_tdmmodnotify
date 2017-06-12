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

require_once('../../config.php');
$pluginname = 'uploadnotification';
//$course_id = required_param('id', PARAM_INT);
//$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

//require_login($course, true);
$PAGE->set_url("/mod/$pluginname/user.php");//, array('id' => $course_id));
$PAGE->set_title('My modules page title');
$PAGE->set_heading('My modules page heading');



// Globals.
global $DB, $CFG, $OUTPUT, $USER, $SITE, $PAGE;

// Include our function library.
//$pluginname = 'uploadnotification';
require_once($CFG->dirroot.'/local/'.$pluginname.'/classes/uploadnotification_user_form.php');

//$course_id = required_param('id', PARAM_INT);
$homeurl = new moodle_url('/');
require_login();

// Only add settings item on non-site course pages.
if (!$USER->id) {
    redirect($homeurl, "This feature is only available for valid users.", 5);
}

echo $OUTPUT->header();

// Display global config
$user_form = new uploadnotification_user_form(null, array(
    'id' => $USER->id,
    'enable' => local_uploadnotification_util::is_user_mail_enabled($USER->id),
    'attachment' => local_uploadnotification_util::is_user_attachment_enabled($USER->id)));

// Evaluate form data
$data = $user_form->get_data();
if ($data) {
    local_uploadnotification_util::set_user_mail_enabled($USER->id, $data->enable);
    local_uploadnotification_util::set_user_attachment_enabled($USER->id, $data->attachment);
}

$user_form->display();


// Footing  =========================================================.

echo $OUTPUT->footer();
