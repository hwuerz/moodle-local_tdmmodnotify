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

// Include function library.
require_once(dirname(__FILE__).'/models/course_settings_model.php');
require_once(dirname(__FILE__).'/models/user_settings_model.php');
require_once(dirname(__FILE__).'/mail_wrapper.php');


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
     * User Object.
     *
     * @var stdClass
     */
    protected $user;

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
        $this->user          = core_user::get_user($this->userid);

        $this->notifications = $notifications;
    }

    /**
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * @return string
     */
    public function get_userfirstname() {
        return $this->userfirstname;
    }

    /**
     * @return string
     */
    public function get_userlastname() {
        return $this->userlastname;
    }

    /**
     * @return local_uploadnotification_notification[]
     */
    public function get_notifications() {
        return $this->notifications;
    }

    /**
     * Build the notification content.
     *
     * @param stdClass $substitutions The string substitions to be passed to the location API when generating the
     *                                content. If this recipient object will have contain notifications, this object
     *                                must include a moodle_url object in its baseurl property else a fatal error will
     *                                be raised when building their content.
     *
     * @param $attachment_optimizer local_uploadnotification_attachment_optimizer The manager for all mail attachments
     * @return local_uploadnotification_mail_wrapper[] An array of all mails which should be send to this user.
     *                        The array can be empty if no content is available for this user. This can happen if the
     *                        visibility of a stored file was changed to hidden.
     */
    public function build_content($substitutions, $attachment_optimizer) {
        global $DB;

        // Whether the user has requested mails.
        $user_settings = new local_uploadnotification_user_settings_model($this->userid);
        if ($user_settings->is_mail_enabled() == 0) {
            return array();
        }

        // User has not forbidden to send mails (-> no preferences or requested).

        /** @var local_uploadnotification_mail_wrapper[] $attachment_mails*/
        $attachment_mails = array();
        $text_mail = false;

        // Loop each notification (= file were changes are detected)
        foreach ($this->notifications as $notification) {

            // Should a mail be send?
            // General rule: A mail will be send if
            // docent or student has requested it
            // AS WELL AS
            // nobody (docent or student) has forbidden it
            $course_settings = new local_uploadnotification_course_settings_model($notification->get_courseid());

            // Docent has disabled mail delivery for his course
            if ($course_settings->is_mail_enabled() == 0) {
                continue;
            }

            // No one has requested mails
            if (!($user_settings->is_mail_enabled() == 1 || $course_settings->is_mail_enabled() == 1)) {
                continue;
            }

            // Check visibility for current user
            // Handles restricted access like visibility for groups and timestamps
            // See https://docs.moodle.org/dev/Availability_API
            $course = $DB->get_record('course', array('id' => $notification->get_courseid()));
            $modinfo = get_fast_modinfo($course, $this->userid);
            $cm = $modinfo->get_cm($notification->get_moodleid());
            if (!$cm->uservisible) { // User can not access the activity.
                continue;
            }

            // Generate the text which informs the user about the file
            $content = $notification->build_content($substitutions);

            // Check whether this notification will lead to an attachment
            $attachment = $this->add_file_attachment($cm, $user_settings, $course_settings, $attachment_optimizer);
            if ($attachment === false) { // No attachment --> this notification can be written in the text mail
                if (empty($text_mail)) {
                    $text_mail = new local_uploadnotification_mail_wrapper($this->user);
                }
                $text_mail->add_content($content->text, $content->html);

            } else { // Each attachment will lead in a single mail
                $mail = new local_uploadnotification_mail_wrapper($this->user);
                $mail->add_content($content->text, $content->html);
                $mail->set_attachment($attachment->file_name, $attachment->file_path);
                $attachment_mails[] = $mail;
            }
        }

        // Add the plain text mail to the array
        if (!empty($text_mail)) {
            $attachment_mails[] = $text_mail;
        }
        return $attachment_mails;
    }

    /**
     * @param $cm cm_info The course module record
     * @param $user_settings local_uploadnotification_user_settings_model
     * @param $course_settings local_uploadnotification_course_settings_model
     * @param $attachment_optimizer local_uploadnotification_attachment_optimizer
     * @return attachment_optimizer_file|bool
     */
    private function add_file_attachment($cm, $user_settings, $course_settings, $attachment_optimizer) {

        // If the admin has attachments disabled --> do not send them
        $max_filesize = get_config('uploadnotification', 'max_filesize');
        if ($max_filesize !== false && $max_filesize == 0) {
            return false;
        }

        // If the user has not requested attachments --> do not send them
        if ($user_settings->get_max_filesize() == 0) {
            return false;
        }

        // If the course admin has forbidden attachments --> do not send them
        if ($course_settings->is_attachment_enabled() == 0) {
            return false;
        }

        // File might be interesting --> fetch it
        $file = $attachment_optimizer->require_file($cm);

        // The file could not be fetched for any reason
        if ($file === false) {
            return false;
        }

        // Check filesize
        if ($file->filesize > get_config('uploadnotification', 'max_filesize')
            || $file->filesize > $user_settings->get_max_filesize()) {
            return false;
        }

        // Check number of receiving users
        if ($file->requesting_users > get_config('uploadnotification', 'max_mails_for_resource')) {
            return false;
        }

        return $file;
    }

    /**
     * Delete the recipient's record.
     *
     * @return void
     */
    public function delete() {
        global $DB;

        foreach ($this->notifications as $notification) {
            $DB->delete_records('local_uploadnotification', array(
                'id' => $notification->get_notificationid(),
            ));
        }
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
     * @param stdClass[] $notificationdigest A notfication digest object from the DML API.
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
