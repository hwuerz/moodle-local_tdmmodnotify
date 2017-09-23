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
 * @author    Luke Carrier <luke@tdm.co>, Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2014 The Development Manager Ltd, 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/definitions.php');
require_once(dirname(__FILE__).'/classes/changelog.php');
require_once(dirname(__FILE__).'/classes/update_handler.php');


/**
 * Send scheduled notification emails.
 *
 * @return void
 */
function local_uploadnotification_cron() {
    local_uploadnotification_update_handler::send_notifications();
}

/**
 * Hook called before we delete a course module.
 *
 * @param \stdClass $cm The course module record.
 */
function local_uploadnotification_pre_course_module_delete($cm) {
    local_uploadnotification_changelog::backup_coursemodule($cm);
}

/**
 * Hook called on course module edit form validation.
 *
 * @param object $data The form data which should be validated. The course module must be available with §data->get_cm().
 * @return array Empty array to indicate no validation errors.
 */
function local_uploadnotification_coursemodule_validation($data) {
    $modulename = $data->get_current()->modulename;
    if ($modulename == 'resource' && $data->get_coursemodule() != null) {
        local_uploadnotification_changelog::backup_coursemodule($data->get_coursemodule());
    }
    return array(); // Empty array to indicate no errors.
}

/**
 * Inject a link in course settings menu.
 * Provides options for docents to disable mail delivery in particular courses.
 * @param settings_navigation $settingsnav The settings navigation node where the new element will be added.
 * @param navigation_node $context The current context.
 */
function local_uploadnotification_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;

    // Disable menu if admin has forbidden mail delivery.
    if (!get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_mail')
        && !get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_changelog')) {
        return;
    }

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('moodle/backup:backupcourse', context_course::instance($PAGE->course->id))) {
        return;
    }

    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $displayed_text = get_string('settings_course_link', 'local_uploadnotification');
        $url = new moodle_url('/local/uploadnotification/course.php', array('id' => $PAGE->course->id));
        $foonode = navigation_node::create(
            $displayed_text,
            $url,
            navigation_node::NODETYPE_LEAF,
            $displayed_text,
            'uploadnotification_course',
            new pix_icon('t/right', $displayed_text)
        );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $foonode->make_active();
        }
        $settingnode->add_node($foonode);
    }
}


/**
 * Inject link in user settings menu.
 * Provides options for students to disable mail delivery for themselves.
 * @param navigation_node $parentnode The parent where the menu will be added.
 * @param stdClass $user The current user.
 * @param context_user $context The current context.
 * @param stdClass $course The displayed course.
 * @param context_course $coursecontext The context of the displayed course.
 */
function local_uploadnotification_extend_navigation_user_settings
(navigation_node $parentnode, stdClass $user, context_user $context, stdClass $course, context_course $coursecontext) {
    global $PAGE;

    // Disable menu if admin has forbidden mail delivery (user could not set any preferences even if link would be enabled).
    if (!get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_mail')) {
        return;
    }

    // Only add this settings item on non-site course pages.
    if (!$user->id) {
        return;
    }

    $displayed_text = get_string('settings_user_link', 'local_uploadnotification');
    $url = new moodle_url('/local/uploadnotification/user.php');
    $foonode = navigation_node::create(
        $displayed_text,
        $url,
        navigation_node::NODETYPE_LEAF,
        $displayed_text,
        'uploadnotification_course',
        new pix_icon('t/right', $displayed_text)
    );
    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $foonode->make_active();
    }
    $parentnode->add_node($foonode);
}
