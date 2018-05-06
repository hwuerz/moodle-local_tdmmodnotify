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

require_once(dirname(__FILE__) . '/../definitions.php');
require_once(dirname(__FILE__) . '/../lib.php');
require_once(dirname(__FILE__) . '/models/course_settings_model.php');
require_once(dirname(__FILE__) . '/models/user_settings_model.php');
require_once(dirname(__FILE__) . '/changelog.php');
require_once(dirname(__FILE__) . '/mailer.php');
require_once(dirname(__FILE__) . '/recipient_iterator.php');
require_once(dirname(__FILE__) . '/util.php');

/**
 * Upload notification update handler.
 *
 * @package   local_uploadnotification
 * @author    Luke Carrier <luke@tdm.co>, Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2014 The Development Manager Ltd, 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_update_handler {

    /**
     * @var \core\event\base The event which was created and should be handled right now.
     */
    private $event;

    /**
     * @var null|stdClass The course module which invoked the event.
     * Do not access directly because it will be loaded only if required. Use getter function.
     */
    private $course_module = null;

    /**
     * @var null|local_uploadnotification_course_settings_model The settings of the course where the event was created.
     * Do not access directly because it will be loaded only if required. Use getter function.
     */
    private $course_settings = null;

    /**
     * local_uploadnotification_update_handler constructor.
     * @param \core\event\base $event The event object.
     */
    public function __construct($event) {
        $this->event = $event;
    }

    /**
     * Checks whether the event is valid and can be handled.
     * @return bool Whether this event can be handled by this plugin or not.
     */
    private function is_event_valid() {

        // Check course module type (Only send mails for updated resources).
        if ($this->get_module_name() != 'resource') {
            return false;
        }

        // Check action.
        if ($this->get_action() === false) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether this update has changed the used file in the course module.
     * @return bool True if the file was changed, false if this update only changed some meta data.
     */
    private function is_file_changed() {

        // Get the current file of the course module.
        $coursemodule_id = $this->get_course_module()->id;
        $context = context_module::instance($coursemodule_id);
        $fs = get_file_storage();
        $area_files = $fs->get_area_files(
            $context->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false);
        $file = array_shift($area_files); // Get only the first file.

        // The file was changed if this event was created maximum five seconds after the last modification of the current file.
        // Add the delta of five seconds to handle slow servers.
        return $this->event->timecreated - 5 < $file->get_timemodified();
    }

    /**
     * Checks whether the changelog is enabled.
     * @return bool Whether changelog generation is enabled in this course or not.
     */
    public function is_changelog_requested() {

        // Check event metadata.
        if (!$this->is_event_valid()) {
            return false;
        }

        return local_uploadnotification_util::is_changelog_enabled($this->get_course_id(), $this->course_settings);
    }

    /**
     * Generated the changelog string for this event.
     * The changelog contains the old file and the differences if this functionality is enabled.
     * @return bool|string The changelog or false if no predecessor was found.
     */
    public function generate_changelog() {
        global $DB;

        $detector = local_uploadnotification_changelog::get_update_detector($this->get_course_module());
        $edit_dialog_used = $this->get_action() == LOCAL_UPLOADNOTIFICATION_ACTION_UPDATED; // Updates require the edit dialog.
        if ($edit_dialog_used) { // This update was performed via the edit dialog --> only definit predecessors will be used.
            // Because only definit predecessors are used, the requirements can be as low as possible.
            // This will return the best fitting definit predecessor.
            $detector->set_ensure_mime_type(false);
            $detector->set_min_similarity(0);
        }

        // Perform the mapping.
        $distribution = $detector->map_backups();
        if (empty($distribution->mappings)) { // No mapping was performed.
            return false;
        }
        $mapping = array_shift($distribution->mappings);
        if ($mapping->predecessor == null) { // No predecessor was found.
            return false;
        }
        if (!$mapping->has_changed()) { // The file has not changed while updating (predecessor == successor).
            $mapping->delete_found_predecessor();
            return false;
        }
        $predecessor = $mapping->predecessor->get_backup()->get_file();

        // Prepare the changelog entry. It will be extended in the following.
        $changelog_entry = get_string('printed_changelog_prefix', LOCAL_UPLOADNOTIFICATION_FULL_NAME, (object)array(
            'filename' => $predecessor->get_filename(),
            'date' => date("Y-m-d H:i")
        ));

        // Perform diff detection if required and possible.
        $file = $mapping->file_wrapper->get_file();
        $max_filesize_for_diff = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_diff_filesize');
        if ($this->is_diff_enabled()
            && $predecessor->get_filesize() <= $max_filesize_for_diff * 1024 * 1024
            && $file->get_filesize() <= $max_filesize_for_diff * 1024 * 1024) {

            $diff = $this->generate_diff($predecessor, $file);
            if ($diff !== false) { // After diff generation the predecessor was not rejected.
                $changelog_entry .= $diff;
            } else if ($edit_dialog_used) { // There are too many changes for a diff, but this is a definit predecessor.
                $changelog_entry .= '<br>'
                    . get_string('long_diff_many', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
            } else { // There are too many changes and it is not a definit predecessor --> abort.
                return '';
            }
        }

        // Get the resource of this course module
        // The check on top of this function ensures that the course module is a resource.
        $resource = $DB->get_record('resource', array('id' => $this->get_course_module()->instance));

        // Build new intro based on calculation and current data.
        $intro = $resource->intro;
        if (strlen($intro) > 0) { // Add new line if an intro already exists.
            $intro .= "<br>";
        }
        $intro .= $changelog_entry;

        // Store the new intro with the changelog.
        $DB->update_record('resource', (object)array(
            'id' => $resource->id,
            'intro' => $intro
        ));

        // Show the intro on the course page.
        $DB->update_record('course_modules', (object)array(
            'id' => $this->get_course_module()->id,
            'showdescription' => 1
        ));

        // Delete the found predecessor to avoid reuse.
        $mapping->delete_found_predecessor();

        // Cache must be rebuild to render intro with changelog.
        rebuild_course_cache($this->get_course_module()->course, true);

        // Only the generated changelog (not the complete intro).
        return $changelog_entry;
    }

    /**
     * Checks whether diff detection is enabled in this course.
     * @return bool Whether difference detection is enabled for this course or not
     */
    private function is_diff_enabled() {
        return local_uploadnotification_changelog::is_diff_allowed()
            && $this->get_course_settings()->is_diff_enabled();
    }

    /**
     * Generates the difference between the two passed files.
     * @param stored_file $predecessor The first file which was removed.
     * @param stored_file $file The second file which was uploaded right now.
     * @return bool|string The detected changes or false if too many.
     */
    private function generate_diff($predecessor, $file) {

        $diff_output = '';
        $predecessor_txt_file = local_changeloglib_pdftotext::convert_to_txt($predecessor);
        $original_txt_file = local_changeloglib_pdftotext::convert_to_txt($file);

        // Only continue of valid text files could be generated.
        if ($predecessor_txt_file !== false && $original_txt_file !== false) {
            $diff_detector = new local_changeloglib_diff_detector($predecessor_txt_file, $original_txt_file);

            if ($diff_detector->has_acceptable_amount_of_changes()) {
                $diff = $diff_detector->get_info();
                if (strlen($diff) > 50) {
                    $changed_pages = count(explode(', ', $diff));
                    $diff_output .= '<br>' . get_string('long_diff', LOCAL_UPLOADNOTIFICATION_FULL_NAME, $changed_pages);
                } else {
                    $diff_output .= '<br>'
                        . get_string('printed_diff_prefix', LOCAL_UPLOADNOTIFICATION_FULL_NAME)
                        . ' '
                        . $diff;
                }
            } else {
                $diff_output = false;
            }
        }

        // Delete auto generated text files.
        if ($predecessor_txt_file) {
            unlink($predecessor_txt_file);
        }
        if ($original_txt_file) {
            unlink($original_txt_file);
        }

        return $diff_output;
    }


    /**
     * Checks whether notifications are allowed to be send for this event.
     * @return bool Whether notifications are enabled or not
     */
    public function is_notification_enabled() {

        // Check event metadata.
        if (!$this->is_event_valid()) {
            return false;
        }

        // Check admin settings.
        $allowed = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_mail');
        if (!$allowed) {
            return false;
        }

        // Check course settings. -1 and 1 are handled as true, zero as false.
        if (!$this->get_course_settings()->is_mail_enabled()) {
            return false;
        }

        // Only send notification for changed files (not for updates in meta data).
        if (!$this->is_file_changed()) {
            return false;
        }

        // Everything is ok --> Notifications can be scheduled.
        return true;
    }

    /**
     * Schedules notifications for all course participants.
     * The schedules will be inserted in the database and a cron job will deliver them as soon as allowed.
     */
    public function schedule_notification() {
        global $DB;

        $course_context = context_course::instance($this->get_course_id());
        $enrolled_users = get_enrolled_users($course_context);

        // Check whether this course is bigger than allowed.
        // The admin can specify a maximum amount of mails which might be send based on one action.
        $amount_of_users = count($enrolled_users);
        if ($amount_of_users > get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_mail_amount')) {
            return;
        }

        foreach ($enrolled_users as $enrolled_user) {
            // Delete entries for this user and file which are already stored in the database.
            // This is needed to avoid duplicated entries on file updates.
            $DB->delete_records('local_uploadnotification', array(
                'coursemoduleid' => $this->get_course_module_id(),
                'userid' => $enrolled_user->id,
            ));
            $DB->insert_record('local_uploadnotification', (object)array(
                'action' => $this->get_action(),
                'courseid' => $this->get_course_id(),
                'coursemoduleid' => $this->get_course_module_id(),
                'userid' => $enrolled_user->id,
                'timestamp' => $this->get_notification_timestamp($enrolled_user->id)
            ));
        }
    }

    /**
     * Get the timestamp when this notification should be delivered.
     * This is now instead a "visible from" condition is set.
     * "visible from" condition:
     *      A docent can define a date when the material becomes available for students.
     *      Do not evaluate (= send) the notification before this date
     *      If the dates becomes modified, an update event will be send and the record will be changed.
     * @param int $user_id The user for whom the notification should be scheduled.
     * @return int The timestamp when the notification should be delivered earliest.
     */
    private function get_notification_timestamp($user_id) {
        $timestamp = time();

        // Check availability API.
        $availability = json_decode($this->get_course_module()->availability);
        if (!is_null($availability) && !is_null($availability->c)) { // This resource has visibility conditions.
            $conditions = $availability->c;
            foreach ($conditions as $condition) {
                // Check for a date condition with "visible after" definition.
                if ($condition->type == 'date' && $condition->d == '>=' && $condition->t > $timestamp) {
                    $timestamp = $condition->t;
                }
            }
        }

        // Check digest preferences.
        $user_settings = new local_uploadnotification_user_settings_model($user_id);
        if ($user_settings->is_digest_enabled()) {
            $begin_of_day = strtotime("midnight", $timestamp);

            // Fetch admin preferences for digest delivery.
            $digest_hour = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'digest_hour');
            $digest_minute = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'digest_minute');
            if ($digest_hour === false || $digest_minute === false) { // There are no preferences (should never happen).
                $digest_hour = 18;
                $digest_minute = 0;
            }

            // Calculate delivery day.
            $digest_time = $begin_of_day + $digest_hour * 60 * 60 + $digest_minute * 60;
            if ($digest_time < $timestamp) { // It is already after the sending time --> mail will be send tomorrow.
                $digest_time = $digest_time + 24 * 60 * 60; // Next day.
            }
            $timestamp = $digest_time;
        }

        return $timestamp;
    }

    /**
     * Get the action which was performed (create or update) as a global constant.
     * @return bool|int The performed action of false if action is invalid.
     */
    private function get_action() {
        switch ($this->event->action) {
            case 'created':
                return LOCAL_UPLOADNOTIFICATION_ACTION_CREATED;

            case 'updated':
                return LOCAL_UPLOADNOTIFICATION_ACTION_UPDATED;

            default:
                return false;
        }
    }

    /**
     * Get the course module name from the event.
     * @return string The type of the updated / created course module. Normally this is 'resource'.
     */
    private function get_module_name() {
        return $this->event->other['modulename'];
    }

    /**
     * Get the ID of the course module which was updated / created.
     * @return int The course module ID.
     */
    private function get_course_module_id() {
        return $this->event->objectid;
    }

    /**
     * The the course module which was updated.
     * Performs lazy loading: Only queries the database if the object is not already known.
     * @return stdClass The course module instance which was updated or created.
     */
    private function get_course_module() {
        global $DB;
        if ($this->course_module == null) {
            $this->course_module = $DB->get_record('course_modules', array('id' => $this->get_course_module_id()));
        }
        return $this->course_module;
    }

    /**
     * Get the ID of the course from where the event was submitted.
     * @return int The course ID.
     */
    private function get_course_id() {
        return $this->event->courseid;
    }

    /**
     * Get the settings for th course from which the event was generated.
     * Performs lazy loading: Only queries the database if the object is not already known.
     * @return local_uploadnotification_course_settings_model The course settings.
     */
    private function get_course_settings() {

        // Check whether settings are not already loaded --> Load now.
        if ($this->course_settings == null) {
            $this->course_settings = new local_uploadnotification_course_settings_model($this->get_course_id());
        }

        return $this->course_settings;
    }

    /**
     * Send scheduled notification emails.
     * This function will be called by the cron job.
     */
    public static function send_notifications() {
        // Only send mails if a moodle admin has allowed this function.
        $allowed = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_mail');
        if (!$allowed) {
            return;
        }

        // Send mails.
        $recipients  = new local_uploadnotification_recipient_iterator();
        $supportuser = core_user::get_support_user();
        $mailer      = new local_uploadnotification_mailer($recipients, $supportuser);

        $mailer->execute();
    }
}
