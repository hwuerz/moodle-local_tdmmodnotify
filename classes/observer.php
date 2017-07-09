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

require_once(dirname(__FILE__).'/../lib.php');

/**
 * Event observer.
 *
 * Responds to course module events emitted by the Moodle event manager.
 */
class local_uploadnotification_observer {
    /**
     * Course module created.
     *
     * @param \core\event\course_module_created $event The event that triggered our execution.
     *
     * @return void
     */
    public static function course_module_created($event) {
        static::schedule_notification($event);
    }

    /**
     * Course module updated.
     *
     * @param \core\event\course_module_updated $event The event that triggered our execution.
     *
     * @return void
     */
    public static function course_module_updated($event) {
        static::schedule_notification($event);
    }

    /**
     * Event handler.
     *
     * Called by observers to handle notification sending.
     *
     * @param \core\event\base $event The event object.
     *
     * @return void
     *
     * @throws \coding_exception When given an invalid action.
     */
    protected static function schedule_notification(\core\event\base $event) {

        // Do not record updates if the plugin is deactivated
        $enabled = get_config('uploadnotification', 'enabled');
        if (!$enabled) {
            return;
        }

        global $DB;

        // Only send mails for updated resources
        if ($event->other['modulename'] != 'resource') {
            return;
        }

        switch ($event->action) {
            case 'created':
                $action = LOCAL_UPLOADNOTIFICATION_ACTION_CREATED;
                break;

            case 'updated':
                $action = LOCAL_UPLOADNOTIFICATION_ACTION_UPDATED;
                break;

            default:
                throw new coding_exception("Invalid event action '{$event->action}' (valid options: 'created', 'updated')");
        }

        // Check for "visible from" condition
        // A docent can define a date when the material becomes available for students.
        // Do not check visibility for students before this date.
        // If the dates becomes modified, an update event will be send and the record will be changed.
        $timestamp = time();
        $cm = $DB->get_record('course_modules', array('id' => $event->objectid), 'availability');
        $availability = json_decode($cm->availability);
        if (!is_null($availability)
            && !is_null($availability->c)) { // This resource has visibility conditions
            $conditions = $availability->c;
            foreach ($conditions as $condition) {
                // Check for a date condition with "visible after" definition
                if ($condition->type == 'date' && $condition->d == '>=' && $condition->t > $timestamp) {
                    $timestamp = $condition->t;
                }
            }
        }

        $coursecontext = context_course::instance($event->courseid);
        $enrolledusers = get_enrolled_users($coursecontext);

        foreach ($enrolledusers as $enrolleduser) {
            // Delete entries for this user and file which are already stored in the database.
            // This is needed to avoid duplicated entries on file updates.
            $DB->delete_records('local_uploadnotification', array(
                'coursemoduleid' => $event->objectid,
                'userid' => $enrolleduser->id,
            ));
            $DB->insert_record('local_uploadnotification', (object) array(
                'action'         => $action,
                'courseid'       => $event->courseid,
                'coursemoduleid' => $event->objectid,
                'userid'         => $enrolleduser->id,
                'timestamp'      => $timestamp
            ));
        }
    }
}
