<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 */

/**
 * Event observer.
 *
 * Responds to course module events emitted by the Moodle event manager.
 */
class local_tdmmodnotify_observer {
    /**
     * Course module created.
     *
     * @param \core\event\course_module_created $event The event that triggered our execution.
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        static::notify($event);
    }

    /**
     * Course module updated.
     *
     * @param \core\event\course_module_updated $event The event that triggered our execution.
     */
    public static function course_module_updated(\core\event\course_module_updated $event) {
        static::notify($event);
    }

    /**
     * Given a course module ID, retrieve the ID of its parent section.
     *
     * @param integer $coursemoduleid The ID of the course module (CMID), as per the course_modules table.
     *
     * @return integer The ID of its parent section within the course.
     */
    protected static function get_coursemodule_section($coursemoduleid) {
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
     * Event handler.
     *
     * Called by observers to handle notification sending.
     *
     * @param \core\event\base $event The event object.
     */
    protected static function notify(\core\event\base $event) {
        global $DB;

        $validactions = array('created', 'updated');
        if (!in_array($event->action, $validactions)) {
            throw new coding_exception("Invalid event action '{$event->action}' (valid options: 'created', 'updated')");
        }

        $coursecontext    = context_course::instance($event->courseid);
        $coursefullname   = $DB->get_field('course', 'fullname', array('id' => $event->courseid), MUST_EXIST);
        $coursesection    = static::get_coursemodule_section($event->objectid);
        $coursesectionurl = new moodle_url("/course/view.php?id={$event->courseid}#section-{$coursesection}");

        $supportuser   = core_user::get_support_user();
        $enrolledusers = get_enrolled_users($coursecontext);

        $substitutions = (object) array(
            'coursefullname' => $coursefullname,
            'siteadmin'      => generate_email_signoff(),
            'sectionurl'     => $coursesectionurl->out(),
        );

        $subject = get_string("template{$event->action}subj", 'local_tdmmodnotify', $substitutions);

        foreach ($enrolledusers as $enrolleduser) {
            $enrolleduser->mailformat = 1;

            $substitutions->userfirstname = $enrolleduser->firstname;

            $message     = get_string("template{$event->action}body", 'local_tdmmodnotify', $substitutions);
            $messagehtml = text_to_html($message, false, false, true);

            email_to_user($enrolleduser, $supportuser, $subject, $message, $messagehtml);
        }
    }
}
