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
     * Checks whether the passed preference is valid and can be stored in the database
     * @param $preference int The preference to be checked
     * @return bool True if valid, false otherwise
     */
    private static function is_valid_preference($preference) {
        return $preference == -1 || $preference == 0 || $preference == 1;
    }

    /**
     * Checks whether the passed preference is valid and can be stored in the database
     * If it is not, an exception will be thrown
     * @param $preference int The preference to be checked
     * @throws InvalidArgumentException If the preference is invalid
     */
    public static function require_valid_preference($preference) {
        if (!self::is_valid_preference($preference)) {
            throw new InvalidArgumentException(
                "Only valid preferences are accepted. Use -1 for no preference, 0 for deactivated and 1 for activated");
        }
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
