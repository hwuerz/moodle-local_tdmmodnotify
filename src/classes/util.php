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
 * TDM: Module modification notification.
 *
 * @package   local_tdmmodnotify
 * @author    Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Utility methods not covered by the data model.
 */
class local_tdmmodnotify_util {
    /**
     * Given a course module ID, retrieve the ID of its parent section.
     *
     * @param integer $coursemoduleid The ID of the course module (CMID), as per the course_modules table.
     *
     * @return integer The ID of its parent section within the course.
     */
    public static function get_coursemodule_section($coursemoduleid) {
        global $DB;

        $sql = <<<SQL
SELECT cs.section
FROM {course_modules} cm
LEFT JOIN {course_sections} cs
    ON cs.id = cm.section
WHERE cm.id = ?
SQL;

        return $DB->get_field_sql($sql, array($coursemoduleid), MUST_EXIST);
    }

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
    cm.id AS moodleid,
    n.action AS action,
    c.id AS courseid, c.fullname AS coursefullname,
    m.name AS modulename,
    s.id AS coursesectionid, s.name AS coursesectionname,
    u.id AS userid, u.firstname AS userfirstname, u.lastname AS userlastname
FROM {local_tdmmodnotify} n
LEFT JOIN {course_sections} s
    ON s.id = n.sectionid
LEFT JOIN {user} u
    ON u.id = n.userid
LEFT JOIN {course} c
    ON c.id = n.courseid
LEFT JOIN {course_modules} cm
    ON cm.id = n.coursemoduleid
LEFT JOIN {modules} m
    ON m.id = cm.module
WHERE u.id = ?
SQL;

        return $DB->get_records_sql($sql, array($userid));
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
FROM {local_tdmmodnotify}
ORDER BY userid ASC
SQL;

        return $DB->get_fieldset_sql($sql);
    }
}
