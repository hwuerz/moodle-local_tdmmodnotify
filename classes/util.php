<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
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
    course_module.id AS moodleid,
    notification.action as action,
    course.id AS courseid, course.fullname AS coursefullname,
    module.name AS modulename,
    section.id AS coursesectionid, section.name as coursesectionname,
    user.id AS userid, user.firstname AS userfirstname, user.lastname AS userlastname
FROM {local_tdmmodnotify} notification
LEFT JOIN {course_sections} section
    ON section.id = notification.sectionid
LEFT JOIN {user} user
    ON user.id = notification.userid
LEFT JOIN {course} course
    ON course.id = notification.courseid
LEFT JOIN {course_modules} course_module
    ON course_module.id = notification.coursemoduleid
LEFT JOIN {modules} module
    ON module.id = course_module.module
WHERE user.id = ?
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
