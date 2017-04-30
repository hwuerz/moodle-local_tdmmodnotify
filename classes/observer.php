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

require_once "{$CFG->dirroot}/local/tdmmodnotify/lib.php";

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
     *
     * @return void
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        static::schedule_notification($event);
    }

    /**
     * Course module updated.
     *
     * @param \core\event\course_module_updated $event The event that triggered our execution.
     *
     * @return void
     */
    public static function course_module_updated(\core\event\course_module_updated $event) {
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
        global $DB;

        switch ($event->action) {
            case 'created':
                $action = LOCAL_TDMMODNOTIFY_ACTION_CREATED;
                break;

            case 'updated':
                $action = LOCAL_TDMMODNOTIFY_ACTION_UPDATED;
                break;

            default:
                throw new coding_exception("Invalid event action '{$event->action}' (valid options: 'created', 'updated')");
        }

        $coursesection = local_tdmmodnotify_util::get_coursemodule_section($event->objectid);

        $coursecontext = context_course::instance($event->courseid);
        $enrolledusers = get_enrolled_users($coursecontext);

        foreach ($enrolledusers as $enrolleduser) {
            $DB->insert_record('local_tdmmodnotify', (object) array(
                'action'         => $action,
                'courseid'       => $event->courseid,
                'coursemoduleid' => $event->objectid,
                'sectionid'      => $coursesection,
                'userid'         => $enrolleduser->id,
            ));
        }
    }
}
