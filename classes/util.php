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
     * @return boolean -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     */
    public static function enabled_mail_in_course($courseid) {
        global $DB;

        $course_settings = $DB->get_record('local_uploadnotification_cou', array('courseid' => $courseid), 'activated', IGNORE_MISSING);

        // If no record was found --> $course_settings is false
        if(!$course_settings) {
            return -1;
        }
        // Course has defined settings --> return them
        return $course_settings->activated;
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
