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
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../definitions.php');


/**
 * A wrapper for notification mails.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_mail_wrapper {

    /**
     * @var stdClass The user object, who should receive the mail.
     */
    private $recipient;

    /**
     * The names of all courses in which a notification exists.
     * This will be used for the title of the mail.
     * @var array Array of strings with all course names.
     */
    private $affected_courses = array();

    /**
     * @var string The message of the mail in plain text.
     */
    private $message_text = '';

    /**
     * @var string The message of the mail in HTML format.
     */
    private $message_html = '';

    /**
     * @var string The filename of the attachment. If no attachment is send an empty string will be stored.
     */
    private $attachment_name = '';

    /**
     * @var string The file path of the attachment. If no attachment is send an empty string will be stored.
     */
    private $attachment_path = '';

    /**
     * Creates a new mail wrapper for the passed recipient.
     * @param stdClass $recipient The user record, who should receive the mail.
     */
    public function __construct($recipient) {
        $this->recipient = $recipient;
    }

    /**
     * Define an attachment for this mail.
     * This method can only be called ones, because multiple attachments are not supported.
     * @param string $name The filename.
     * @param string $path The path, where the attachment is stored.
     */
    public function set_attachment($name, $path) {
        if ($this->attachment_name !== '') {
            throw new RuntimeException('An attachment was already set. Moodle does not support multiple attachments.');
        }

        $this->attachment_name = $name;
        $this->attachment_path = $path;
    }

    /**
     * Adds the passed strings to the content of the mail.
     * @param string $text The content of the message in plain text which should be added.
     * @param string $html The content of the message in html which should be added.
     */
    public function add_content($text, $html) {
        $this->message_text .= $text;
        $this->message_html .= $html;
    }

    /**
     * Adds the passed course name to the title of the generated mail.
     * @param string $course_name The name of the course in which a notification exists.
     */
    public function add_course($course_name) {
        if (!in_array($course_name, $this->affected_courses)) {
            $this->affected_courses[] = $course_name;
        }
    }

    /**
     * Sends this mail to the user.
     * @param object $substitutions The substitutions for the mail template.
     */
    public function send($substitutions) {
        $substitutions->notifications = $this->message_text;
        $message_text = get_string('templatemessage', LOCAL_UPLOADNOTIFICATION_FULL_NAME, $substitutions);

        $substitutions->notifications = $this->message_html;
        $message_html = get_string('templatemessage_html', LOCAL_UPLOADNOTIFICATION_FULL_NAME, $substitutions);

        // Build subject based on the names of all courses from which a notification is in this mail.
        $courses = implode(', ', $this->affected_courses);
        $subject_template = (count($this->affected_courses) > 1) ? 'templatesubject_plural' : 'templatesubject_singular';
        $subject = get_string($subject_template, LOCAL_UPLOADNOTIFICATION_FULL_NAME, $courses);

        email_to_user($this->recipient,
            core_user::get_noreply_user(),
            $subject,
            $message_text,
            $message_html,
            $this->attachment_path, $this->attachment_name,
            true);
    }
}
