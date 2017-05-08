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
     * Initialiser.
     *
     * @param \local_uploadnotification_recipient[]|\local_uploadnotification_recipient_iterator $recipients  Array/iterator of
     *                                                                                            recipients.
     * @param stdClass                                                               $supportuser Support user record.
     */
    public function __construct($recipients, $supportuser) {
        $this->recipients  = $recipients;
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
        foreach ($this->recipients as $recipient) {
            mtrace("user#{$recipient->userid}");

            $this->mail($recipient);
//            $this->delete_scheduled_notifications($recipient);
        }
    }

    /**
     * Mail a single recipient.
     *
     * @param \local_uploadnotification_recipient $recipient The recipient record.
     *
     * @return void
     */
    protected function mail($recipient) {
        $recipientuser = core_user::get_user($recipient->userid);

        $substitutions = (object) array(
            'firstname' => $recipient->userfirstname,
            'signoff'   => generate_email_signoff(),
            'baseurl_course'   => new moodle_url('/course/view.php'),
            'baseurl_file'   => new moodle_url('/mod/resource/view.php'),
        );
        $notifications = $recipient->build_content($substitutions);

        $substitutions->notifications = $notifications->text;
        $message = get_string('templatemessage', 'local_uploadnotification', $substitutions);

        $substitutions->notifications = $notifications->html;
        $message_html = get_string('templatemessage_html', 'local_uploadnotification', $substitutions);

        mtrace('send message to ' . $recipientuser->username . '<br>');

        email_to_user($recipientuser,
            core_user::get_noreply_user(),
            get_string('templatesubject', 'local_uploadnotification'),
            $message,
            $message_html,
            '', '', true);
    }
}
