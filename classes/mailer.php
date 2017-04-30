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
 * Digest mailer.
 */
class local_tdmmodnotify_mailer {
    /**
     * Array/iterator of recipients.
     *
     * @var \local_tdmmodnotify_recipient[]|\local_tdmmodnotify_recipient_iterator
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
     * @param \local_tdmmodnotify_recipient[]|\local_tdmmodnotify_recipient_iterator $recipients  Array/iterator of
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
     * @param \local_tdmmodnotify_recipient $recipient The recipient record.
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
            $this->delete_scheduled_notifications($recipient);
        }
    }

    /**
     * Mail a single recipient.
     *
     * @param \local_tdmmodnotify_recipient $recipient The recipient record.
     *
     * @return void
     */
    protected function mail($recipient) {
        $recipientuser = core_user::get_user($recipient->userid);

        $substitutions = (object) array(
            'firstname' => $recipient->userfirstname,
            'signoff'   => generate_email_signoff(),
            'baseurl'   => new moodle_url('/course/view.php'),
        );
        $substitutions->notifications = $recipient->build_content($substitutions);

        $message     = get_string('templatemessage', 'local_tdmmodnotify', $substitutions);
        $messagehtml = text_to_html($message, false, false, true);

        $message = (object) array(
            'component' => 'local_tdmmodnotify',
            'name'      => 'digest',

            'userfrom' => core_user::get_noreply_user(),
            'userto'   => $recipientuser,

            'subject'           => get_string('templatesubject', 'local_tdmmodnotify'),
            'smallmessage'      => $message,
            'fullmessage'       => $message,
            'fullmessageformat' => FORMAT_PLAIN,
            'fullmessagehtml'   => $messagehtml,
        );
        message_send($message);
    }
}
