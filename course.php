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
$course_id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

require_login($course, true);
$PAGE->set_url("/mod/$pluginname/course.php", array('id' => $course_id));
$PAGE->set_title('My modules page title');
$PAGE->set_heading('My modules page heading');



// Globals.
global $CFG;

//require_once($CFG->libdir.'/adminlib.php');

// Globals.
global $OUTPUT, $USER, $SITE, $PAGE;

// Include our function library.
//$pluginname = 'uploadnotification';
require_once($CFG->dirroot.'/local/'.$pluginname.'/classes/uploadnotification_course_form.php');

//$course_id = required_param('id', PARAM_INT);
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

// Display global config
$course_form = new uploadnotification_course_form(null, array('id'=>$course_id, 'fullname'=>$course->fullname));
$data = $course_form->get_data();
if ($data) {

    // Delete old settings
    $course_selector = array('courseid'  => $course_id);
    $DB->delete_records('local_uploadnotification_cou', $course_selector);

    // Insert new settings
    $record = array(
        'courseid'  => $course_id,
        'activated' => $data->enable ? 1 : 0
    );
    $sql = "INSERT INTO {local_uploadnotification_cou} (courseid, activated) VALUES (?, ?)";
    $DB->execute($sql, $record);
}
$course_form->display();


// Footing  =========================================================.

echo $OUTPUT->footer();
