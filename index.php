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

// Include config.php.
require_once(dirname(__FILE__).'/../../config.php');

// Globals.
global $CFG, $OUTPUT, $USER, $SITE, $PAGE;
$pluginname = 'uploadnotification';

// @codingStandardsIgnoreStart PhpStorm only supports /** */ annotation
/** @noinspection PhpIncludeInspection */
require_once($CFG->libdir.'/adminlib.php');
// @codingStandardsIgnoreEnd

// Include function library.
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/classes/forms/admin_form.php');
require_once(dirname(__FILE__).'/classes/forms/development_form.php');



// Ensure only administrators have access.
$homeurl = new moodle_url('/');
if (!is_siteadmin()) {
    redirect($homeurl, "This feature is only available for site administrators.", 5);
}

// URL Parameters.
// There are none.

// Heading ==========================================================.

$title = get_string('pluginname', 'local_'.$pluginname);
$heading = get_string('heading', 'local_'.$pluginname);
$context = context_system::instance();
$url = new moodle_url('/local/'.$pluginname.'/');

$PAGE->set_pagelayout('admin');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($heading);
admin_externalpage_setup('local_'.$pluginname); // Sets the navbar & expands navmenu.

echo $OUTPUT->header();

// Manually send mail
$development_form = new local_uploadnotification_development_form();
$data = $development_form->get_data();
if ($data) {
    local_uploadnotification_cron();
}
$development_form->display();

// Display global config
$admin_form = new local_uploadnotification_admin_form();
$data = $admin_form->get_data();
if ($data) {
    set_config('enabled', $data->enable, 'uploadnotification');
    set_config('max_filesize', $data->max_filesize, 'uploadnotification');
    set_config('max_mails_for_resource', $data->max_mails_for_resource, 'uploadnotification');
}
$admin_form->display();


// Footing  =========================================================.

echo $OUTPUT->footer();
