<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upload notification.
 *
 * @package   local_uploadnotification
 * @author    Luke Carrier <luke@tdm.co>, Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2014 The Development Manager Ltd, 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Utility methods not covered by the data model.
 */
class local_uploadnotification_util {

    /**
     * Get a notification digest for a user.
     *
     * @param integer $userid The ID of the user to retrieve a digest for.
     *
     * @return stdClass[] DML record objects for each notification.
     */
    public static function get_notification_digest($userid) {
        global $DB;

        $sql = <<<SQL
SELECT
    n.id AS notificationid,
    cm.id AS moodleid,
    n.action AS action,
    c.id AS courseid, c.fullname AS coursefullname,
    m.name AS modulename,
    u.id AS userid, u.firstname AS userfirstname, u.lastname AS userlastname,
    r.name AS filename,
    cm.visible AS visible
FROM {local_uploadnotification} n
LEFT JOIN {user} u
    ON u.id = n.userid
LEFT JOIN {course} c
    ON c.id = n.courseid
LEFT JOIN {course_modules} cm
    ON cm.id = n.coursemoduleid
LEFT JOIN {modules} m
    ON m.id = cm.module
LEFT JOIN {resource} r
    ON cm.instance = r.id
WHERE u.id = ? AND n.timestamp < ?
SQL;

       return $DB->get_records_sql($sql, array($userid, self::get_max_timestamp()));
    }

    /**
     * Retrieve a list of IDs of users with pending notifications.
     *
     * @return integer[]
     */
    public static function get_scheduled_recipients() {
        global $DB;

        $sql = <<<SQL
SELECT DISTINCT userid
FROM {local_uploadnotification}
WHERE timestamp < ?
ORDER BY userid ASC
SQL;

        return $DB->get_fieldset_sql($sql, array(self::get_max_timestamp()));
    }

    /**
     * Checks whether the mail delivery is enabled for the passed course.
     *
     * @param $courseid integer The ID of the course where material was uploaded
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     */
    public static function is_course_mail_enabled($courseid) {
        return self::is_mail_enabled('local_uploadnotification_cou', 'courseid', $courseid);
    }

    /**
     * Stores the new preference for a course in the database.
     *
     * @param $courseid integer The ID of the course whose preference should be stored
     * @param $preference integer The preference of the course
     * @return bool Whether the update was successful or not
     */
    public static function set_course_mail_enabled($courseid, $preference) {
        return self::set_mail_enabled('local_uploadnotification_cou', 'courseid', $courseid, $preference);
    }

    /**
     * Checks whether the current user wants to receive email notifications.
     *
     * @param $userid integer The ID of the user whose preferences should be fetched
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     */
    public static function is_user_mail_enabled($userid) {
        return self::is_mail_enabled('local_uploadnotification_usr', 'userid', $userid);
    }

    /**
     * Stores the new preference for a user in the database.
     *
     * @param $userid integer The ID of the user whose preference should be stored
     * @param $preference integer The preference of the user
     * @return bool Whether the update was successful or not
     */
    public static function set_user_mail_enabled($userid, $preference) {
        global $DB;

        // Check for valid parameter
        if(!self::is_valid_preference($preference)) return false;

        $settings = $DB->get_record(
            'local_uploadnotification_usr',
            array('userid' => $userid),
            'userid, activated, attachment',
            IGNORE_MISSING);

        $record = array(
            'userid'  => $userid,
            'activated' => null,
            'attachment' => -1
        );

        // If record was found
        if($settings !== false) {
            $record = array(
                'userid'  => $settings->userid,
                'activated' => $settings->activated,
                'attachment' => $settings->attachment
            );
        }

        $record['activated'] = $preference;

        // Delete old settings
        $DB->delete_records('local_uploadnotification_usr', array('userid'  => $userid));

        $sql = "INSERT INTO {local_uploadnotification_usr} (userid, activated, attachment) VALUES (?, ?, ?)";
        $DB->execute($sql, $record);
        return true;
    }

    /**
     * Checks whether the current user wants to receive attachments in email notifications.
     *
     * @param $userid integer The ID of the user whose preferences should be fetched
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     */
    public static function is_user_attachment_enabled($userid) {
        global $DB;
        $settings = $DB->get_record(
            'local_uploadnotification_usr',
            array('userid' => $userid),
            'attachment',
            IGNORE_MISSING);

        // If no record was found --> $settings is false
        if($settings === false) {
            return -1;
        }
        // User has defined settings --> return them
        return $settings->attachment;
    }

    /**
     * Stores the new preference for a user in the database.
     *
     * @param $userid integer The ID of the user whose preference should be stored
     * @param $preference integer The preference of the user
     * @return bool Whether the update was successful or not
     */
    public static function set_user_attachment_enabled($userid, $preference) {
        global $DB;

        // Check for valid parameter
        if(!self::is_valid_preference($preference)) return false;

        $settings = $DB->get_record(
            'local_uploadnotification_usr',
            array('userid' => $userid),
            'userid, activated, attachment',
            IGNORE_MISSING);

        $record = array(
            'userid'  => $userid,
            'activated' => -1,
            'attachment' => null
        );

        // If record was found
        if($settings !== false) {
            $record = array(
                'userid'  => $settings->userid,
                'activated' => $settings->activated,
                'attachment' => $settings->attachment
            );
        }

        $record['attachment'] = $preference;

        // Delete old settings
        $DB->delete_records('local_uploadnotification_usr', array('userid'  => $userid));

        $sql = "INSERT INTO {local_uploadnotification_usr} (userid, activated, attachment) VALUES (?, ?, ?)";
        $DB->execute($sql, $record);
        return true;
    }

    /**
     * Checks whether the passed entry (course or user) wants to receive email notifications.
     *
     * @param $table string The DB table name where the preference should be fetched from
     * @param $id_column_name string The name of the column where the id is stored
     * @param $id integer The record id
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     */
    public static function is_mail_enabled($table, $id_column_name, $id) {
        global $DB;
        $settings = $DB->get_record(
            $table,
            array($id_column_name => $id),
            'activated',
            IGNORE_MISSING);

        // If no record was found --> $settings is false
        if($settings === false) {
            return -1;
        }
        // User has defined settings --> return them
        return $settings->activated;
    }

    /**
     * Stores the new preference in the passed table.
     * @param $table string The DB table name where the preference should be stored
     * @param $id_column_name string The name of the column where the id is stored
     * @param $id integer The record id
     * @param $preference integer The new preference
     * @return bool Whether the update was successful or not
     */
    private static function set_mail_enabled($table, $id_column_name, $id, $preference) {
        global $DB;

        // Check for valid parameter
        if(!self::is_valid_preference($preference)) return false;

        // Delete old settings
        $DB->delete_records($table, array($id_column_name  => $id));

        // There is no preference --> do not store anything in database
        if($preference < 0) return true;

        // There is a preference --> store
        $record = array(
            $id_column_name  => $id,
            'activated' => $preference
        );
        $sql = "INSERT INTO {".$table."} ($id_column_name, activated) VALUES (?, ?)";
        $DB->execute($sql, $record);
        return true;
    }

    /**
     * Checks whether the passed preference is valid and can be stored in the database
     * @param $preference int The preference to be checked
     * @return bool True if valid, false otherwise
     */
    private static function is_valid_preference($preference) {
        return $preference == -1 || $preference == 0 || $preference == 1;
    }

    /**
     * Get the maximum timestamp of records to be returned.
     * Only get entries which are older than 5 minutes
     * After a docent uploaded some material, he maybe wants to change some properties
     * --> do not send mail with records newer than this timestamp
     *
     * @return int The max timestamp allowed
     */
    private static function get_max_timestamp() {
        return time() - 5 * 60;
    }
}
