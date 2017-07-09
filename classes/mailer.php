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
require_once(dirname(__FILE__) . '/attachment_optimizer.php');

/**
 * Digest mailer.
 */
class local_uploadnotification_mailer {
    /**
     * Array/iterator of recipients.
     *
     * @var \local_uploadnotification_recipient[]|\local_uploadnotification_recipient_iterator
     */
    protected $recipients;

    /**
     * Support user record.
     *
     * @var stdClass
     */
    protected $supportuser;

    /**
     * The manager for the file attachments to avoid duplicated files for every user
     * @var local_uploadnotification_attachment_optimizer
     */
    private $attachment_optimizer;

    /**
     * Initialiser.
     *
     * @param \local_uploadnotification_recipient[]|\local_uploadnotification_recipient_iterator $recipients Array/iterator of
     *                                                                                            recipients.
     * @param stdClass $supportuser Support user record.
     */
    public function __construct($recipients, $supportuser) {
        $this->recipients = $recipients;
        $this->supportuser = $supportuser;
    }

    /**
     * Delete scheduled notifications for a recipient.
     *
     * @param \local_uploadnotification_recipient $recipient The recipient record.
     *
     * @return void
     */
    public function delete_scheduled_notifications($recipient) {
        $recipient->delete();
    }

    /**
     * Execute the mailer.
     *
     * Send all of the scheduled notifications in digest form.
     *
     * @return void
     */
    public function execute() {
        $this->attachment_optimizer = new local_uploadnotification_attachment_optimizer();

        foreach ($this->recipients as $recipient) {
            mtrace("user#{$recipient->get_userid()}");
            $this->mail($recipient);
        }

        // Records must be deleted AFTER all mails are send
        // Script counts scheduled mails to calculate load for mail attachments
        foreach ($this->recipients as $recipient) {
            $this->delete_scheduled_notifications($recipient);
        }

        $this->attachment_optimizer->delete_all_tmp_copies();
    }

    /**
     * Mail a single recipient.
     *
     * @param \local_uploadnotification_recipient $recipient The recipient record.
     *
     * @return void
     */
    protected function mail($recipient) {

        $substitutions = (object)array(
            'firstname' => $recipient->get_userfirstname(),
            'signoff' => generate_email_signoff(),
            'baseurl_course' => new moodle_url('/course/view.php'),
            'baseurl_file' => new moodle_url('/mod/resource/view.php'),
            'user_settings' => (new moodle_url('/local/uploadnotification/user.php'))->out(),
        );
        $mail_wrappers = $recipient->build_content($substitutions, $this->attachment_optimizer);

        // Iterate over all mails for the user
        // It is possible that there are no notifications available for the user.
        // This can happen if the visibility of a stored file was changed to hidden.
        // In this case no mail has to be delivered.
        foreach ($mail_wrappers as $mail_wrapper) {
            $mail_wrapper->send($substitutions);
        }
    }
}
