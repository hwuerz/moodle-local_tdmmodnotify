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

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../definitions.php');

/**
 * Class local_uploadnotification_test_helper.
 * Provides helper functions which are required by multiple tests.
 */
class local_uploadnotification_test_helper {

    const NOTIFICATION_TABLE = 'local_uploadnotification';

    /**
     * Creates a new stored file, based on the documents in the tests/res/ subdirectory.
     * @param int $contextid The ID of the context of the new file.
     * @param string $filename The name of the file. Must ne equal to the filename in tests/res.
     * @param string $component The component under which the file should be created.
     * @param string $filearea The file area under which the file should be created.
     * @param int $itemid The item ID under which the file should be created.
     * @param string $filepath The filepath of the file.
     * @return stored_file The file instance of moodle.
     */
    public static function create_file($contextid, $filename = 'file.pdf', $component = 'mod_resource', $filearea = 'content',
                                 $itemid = 0, $filepath = '/') {

        $fs = get_file_storage();
        $file_info = array(
            'contextid' => $contextid,
            'filename' => $filename,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'timecreated' => time(),
            'timemodified' => time());
        // Delete auto generated file.
        $fs->delete_area_files($file_info['contextid'], $file_info['component'], $file_info['filearea']);
        // Create own files.
        $file = $fs->create_file_from_pathname($file_info, dirname(__FILE__) . '/res/' . $filename);

        return $file;
    }


    /**
     * Makes all planed notifications old enough to be send.
     */
    public static function make_all_notifications_older() {
        global $DB;
        $notifications = $DB->get_records(self::NOTIFICATION_TABLE);
        foreach ($notifications as $notification) {
            self::make_notification_older($notification->id);
        }
    }

    /**
     * Manipulates the timestamp of the notification with the passed ID.
     * After manipulation it is old enough to be send.
     * @param int $notification_id The notification which should be manipulated.
     */
    public static function make_notification_older($notification_id) {
        global $DB;
        $DB->update_record('local_uploadnotification', (object)array(
            'id' => $notification_id,
            'timestamp' => time() - 5 * 60 - 1 // Manipulate timestamp to make notification older.
        ));
    }

    /**
     * Enables / disables mail delivery in the test course.
     * @param int $course_id The ID of the course.
     * @param boolean $enabled Whether mail delivery should be enabled in the test course.
     */
    public static function set_mail_enabled_in_course($course_id, $enabled) {
        $settings = new local_uploadnotification_course_settings_model($course_id);
        $settings->set_mail_enabled($enabled);
        $settings->save();
    }

    /**
     * Allows / forbids attachment delivery in the test course.
     * @param int $course_id The ID of the course.
     * @param boolean $enabled Whether attachment delivery should be allowed in the test course.
     */
    public static function set_attachment_allowed_in_course($course_id, $enabled) {
        $settings = new local_uploadnotification_course_settings_model($course_id);
        $settings->set_attachment_allowed($enabled);
        $settings->save();
    }

    /**
     * Set the max attachment filesize for the student.
     * @param int $filesize The max attachment filesize.
     * @param int $user_id The ID of the user.
     */
    public static function set_max_filesize_for_student($filesize, $user_id) {
        $settings = new local_uploadnotification_user_settings_model($user_id);
        $settings->set_max_filesize($filesize);
        $settings->save();
    }
}
