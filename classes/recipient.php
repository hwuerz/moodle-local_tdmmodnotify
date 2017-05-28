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
 * Recipient.
 */
class local_uploadnotification_recipient extends local_uploadnotification_model {
    /**
     * User ID.
     *
     * @var integer
     */
    protected $userid;

    /**
     * User forename.
     *
     * @var string
     */
    protected $userfirstname;

    /**
     * User surname.
     *
     * @var string
     */
    protected $userlastname;

    /**
     * Notifications.
     *
     * @var local_uploadnotification_notification[]
     */
    protected $notifications;

    /**
     * Initialiser.
     *
     * @param integer                           $userid        User ID.
     * @param string                            $userfirstname User forename.
     * @param string                            $userlastname  User surname.
     * @param local_uploadnotification_notification[] $notifications Notifications.
     */
    public function __construct($userid, $userfirstname, $userlastname, $notifications) {
        $this->userid        = $userid;
        $this->userfirstname = $userfirstname;
        $this->userlastname  = $userlastname;

        $this->notifications = $notifications;
    }

    /**
     * Build the notification content.
     *
     * @param stdClass $substitutions The string substitions to be passed to the location API when generating the
     *                                content. If this recipient object will have contain notifications, this object
     *                                must include a moodle_url object in its baseurl property else a fatal error will
     *                                be raised when building their content.
     *
     * @return object {string text, string html} | null
     *      The notification content.
     *      null if no content is available for this user. This can happen if the visibility of a stored file was
     *      changed to hidden.
     */
    public function build_content($substitutions) {

        global $DB;

        $format = (object) array(
            'text' => '',
            'html' => ''
        );

        foreach ($this->notifications as $notification) {

            // If this file is not visible for the user, do not include it in the report
            // TODO: Redundant with uservisible check below. Remove variable from all scripts
            if($notification->visible == 0) {
                continue;
            }

            // Check visibility for current user
            // Handles restricted access like visibility for groups and timestamps
            $course = $DB->get_record('course', array('id' => $notification->courseid));
            $modinfo = get_fast_modinfo($course, $this->userid);
            $cm = $modinfo->get_cm($notification->moodleid);
            if (!$cm->uservisible) { // User can not access the activity.
                continue;
            }

            $context = $notification->build_content($substitutions);
            $format->text .= $context->text;
            $format->html .= $context->html;
        }

        // There are no notifications at all --> do not send an email
        if($format->text == '') {
            return null;
        }

        $format->text = substr($format->text, 0, -1);
        $format->html = substr($format->html, 0, -1);

        return $format;
    }

    /**
     * Delete the recipient's record.
     *
     * @return void
     */
    public function delete() {
        global $DB;

        $DB->delete_records('local_uploadnotification', array(
            'userid' => $this->userid,
        ));
    }

    /**
     * @override \local_uploadnotification_model
     */
    public function model_accessors() {
        return array(
            'userid',
            'userfirstname',
            'userlastname',
            'notifications',
        );
    }

    /**
     * Build a recipient object and child notification objects from a digest.
     *
     * @param stdClass $notificationdigest A notfication digest object from the DML API.
     *
     * @return \local_uploadnotification_recipient A recipient object.
     */
    public static function from_digest($notificationdigest) {
        $notification  = current($notificationdigest);
        $userid        = $notification->userid;
        $userfirstname = $notification->userfirstname;
        $userlastname  = $notification->userlastname;

        $notifications = array();
        foreach ($notificationdigest as $notification) {
            $notifications[] = local_uploadnotification_notification::from_digest($notification);
        }

        return new static($userid, $userfirstname, $userlastname, $notifications);
    }
}
