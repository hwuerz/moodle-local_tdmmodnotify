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
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


/**
 * A wrapper for notification mails.
 */
class mail_wrapper {

    /**
     * @var stdClass The user object, who should receive the mail
     */
    private $recipient;

    /**
     * @var string The message of the mail in plain text
     */
    private $message_text = '';

    /**
     * @var string The message of the mail in HTML format
     */
    private $message_html = '';

    /**
     * @var string The filename of the attachment. If no attachment is send an empty string will be stored
     */
    private $attachment_name = '';

    /**
     * @var string The file path of the attachment. If no attachment is send an empty string will be stored
     */
    private $attachment_path = '';

    /**
     * @var stdClass The user record, who should receive the mail
     */
    public function __construct($recipient) {
        $this->recipient = $recipient;
    }

    /**
     * Define an attachment for this mail.
     * This method can only be called ones, because multiple attachments are not supported.
     * @param $name string The filename
     * @param $path string The path, where the attachment is stored
     */
    public function set_attachment($name, $path) {
        if($this->attachment_name !== '') {
            throw new RuntimeException('An attachment was already set. Moodle does not support multiple attachments.');
        }

        $this->attachment_name = $name;
        $this->attachment_path = $path;
    }

    /**
     * @param $text string The content of the message in plain text which should be added.
     * @param $html string The content of the message in html which should be added.
     */
    public function add_content($text, $html) {
        $this->message_text .= $text;
        $this->message_html .= $html;
    }

    /**
     * Sends this mail to the user
     * @param $substitutions object The substitutions for the mail template.
     */
    public function send($substitutions) {
        $substitutions->notifications = $this->message_text;
        $message_text = get_string('templatemessage', 'local_uploadnotification', $substitutions);

        $substitutions->notifications = $this->message_html;
        $message_html = get_string('templatemessage_html', 'local_uploadnotification', $substitutions);

        email_to_user($this->recipient,
            core_user::get_noreply_user(),
            get_string('templatesubject', 'local_uploadnotification'),
            $message_text,
            $message_html,
            $this->attachment_path, $this->attachment_name,
            true);
    }
}
