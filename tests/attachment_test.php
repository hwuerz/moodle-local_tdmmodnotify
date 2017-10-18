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
require_once(dirname(__FILE__) . '/helper.php');

require_once(dirname(__FILE__) . '/../classes/models/course_settings_model.php');
require_once(dirname(__FILE__) . '/../classes/models/user_settings_model.php');
require_once(dirname(__FILE__) . '/../classes/observer.php');
require_once(dirname(__FILE__) . '/../classes/update_handler.php');
require_once(dirname(__FILE__) . '/../classes/util.php');

/**
 * Class local_uploadnotification_attachment_test.
 *
 * vendor/bin/phpunit local_uploadnotification_attachment_test local/uploadnotification/tests/attachment_test.php
 *
 * Tests mail delivery.
 *
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group local_uploadnotification
 */
class local_uploadnotification_attachment_test extends advanced_testcase {

    /**
     * @var stdClass The course used for tests.
     */
    private $course;

    /**
     * @var stdClass The student used for tests.
     */
    private $student;

    /**
     * @var stdClass The teacher used for tests.
     */
    private $teacher;

    /**
     * Checks that a notification mail can be send if everything is enabled.
     */
    public function test_admin_disables_mail() {
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('max_mail_filesize', 0, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('max_mails_for_resource', 999, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_mail_enabled_in_course(1); // Enable mail delivery in course.

        $this->set_attachment_allowed_in_course(1); // Allow attachment delivery in course.
        $this->set_max_filesize_for_student(100);
        $messages = $this->create_resource_and_send_mails('file.txt'); // Get all send mails.

        $this->assertEquals(1, count($messages)); // The mail was send.
        $this->assertFalse($this->contains_attachment($messages[0], 'file.txt')); // The attachment was not included.
    }

    /**
     * Do not add attachment if it would be included in to many mails.
     */
    public function test_admin_max_mails_for_resource() {
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('max_mail_filesize', 100, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_mail_enabled_in_course(1); // Enable mail delivery in course.
        $this->set_attachment_allowed_in_course(1); // Allow attachment delivery in course.
        $this->set_max_filesize_for_student(100);
        // Add some more students to the course.
        // Finally there are 11 students. One from preparation and ten more which are added here.
        $students = array($this->student);
        for ($i = 0; $i < 10; $i++) {
            $student = $this->getDataGenerator()->create_user(array(
                'email' => 'student' . $i . '@example.com',
                'username' => 'student' . $i
            ));
            $this->getDataGenerator()->enrol_user($student->id, $this->course->id);
            $students[] = $student;
        }

        // All students require the attachment and the admin allows delivery for all of them.
        set_config('max_mails_for_resource', count($students), LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        foreach ($students as $student) {
            $this->set_max_filesize_for_student(100, $student->id);
        }
        $messages = $this->create_resource_and_send_mails('file.txt'); // Get all send mails.
        $this->assertEquals(count($students), count($messages)); // All mails were send.
        for ($i = 0; $i < count($students); $i++) {
            $this->assertTrue($this->contains_attachment($messages[$i], 'file.txt')); // The attachment was included.
        }

        // The admin only allows attachments to all but one student --> no one receives the attachment.
        set_config('max_mails_for_resource', count($students) - 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        $messages = $this->create_resource_and_send_mails('file.txt'); // Get all send mails.
        $this->assertEquals(count($students), count($messages)); // All mails were send.
        for ($i = 0; $i < count($students); $i++) {
            $this->assertFalse($this->contains_attachment($messages[$i], 'file.txt')); // The attachment was NOT included.
        }

        // All but one students require the attachment and the admin allows delivery for all of them but the one.
        $this->set_max_filesize_for_student(0, $students[0]->id);
        $messages = $this->create_resource_and_send_mails('file.txt'); // Get all send mails.
        $this->assertEquals(count($students), count($messages)); // All mails were send.
        for ($i = 0; $i < count($students); $i++) {
            $iteration_name = 'Student ' . $i;
            $attachment_included = $this->contains_attachment($messages[$i], 'file.txt');
            if ($messages[$i]->to == $students[0]->email) { // This student has not requested an attachment.
                $this->assertFalse($attachment_included, $iteration_name);
            } else { // All other students have requested an attachment.
                $this->assertTrue($attachment_included, $iteration_name);
            }
        }
    }

    /**
     * The max filesize in the settings is checked for attachments.
     */
    public function test_max_filesize() {
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('max_mails_for_resource', 999, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_mail_enabled_in_course(1); // Enable mail delivery in course.
        $this->set_attachment_allowed_in_course(1); // Allow attachment delivery in course.

        $filename = 'file.pdf'; // Filesize ~30KB.

        // Index 0: admin limit
        // Index 1: student limit
        // Index 2: attachment expected.
        $settings = array(
            array(10, 10, false),
            array(10, 100, false),
            array(100, 10, false),
            array(100, 100, true)
        );

        foreach ($settings as $setting) {
            set_config('max_mail_filesize', $setting[0], LOCAL_UPLOADNOTIFICATION_FULL_NAME);
            $this->set_max_filesize_for_student($setting[1]);
            $messages = $this->create_resource_and_send_mails($filename); // Get all send mails.

            $iteration_description = 'Admin=' . $setting[0] . ' User=' . $setting[1];
            $this->assertEquals(1, count($messages)); // The mail was send.
            $attachment_included = $this->contains_attachment($messages[0], $filename);
            $this->assertEquals($setting[2], $attachment_included, $iteration_description); // Attachment included if allowed.
        }
    }

    /**
     * Check all combinations of course (allow / forbid) and user (max filesize) settings.
     */
    public function test_settings() {
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings to schedule a notification.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('max_mail_filesize', 100000, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('max_mails_for_resource', 999, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_mail_enabled_in_course(1); // Enable mail delivery in course.

        // Index 0: course settings
        // Index 1: student settings
        // Index 2: attachment expected.
        $settings = array(
            array(0, 0, false),
            array(0, 100, false),
            array(1, 0, false),
            array(1, 100, true)
        );
        $filename = 'file.txt';
        foreach ($settings as $setting) {
            $this->set_attachment_allowed_in_course($setting[0]); // Allow attachment delivery in course.
            $this->set_max_filesize_for_student($setting[1]);
            $messages = $this->create_resource_and_send_mails($filename); // Get all send mails.

            $iteration_description = 'Course=' . $setting[0] . ' User=' . $setting[1];
            $this->assertEquals(1, count($messages), $iteration_description); // A mail should have been send in all cases.
            $attachment_included = $this->contains_attachment($messages[0], $filename);
            $this->assertEquals($setting[2], $attachment_included, $iteration_description); // Attachment included if requested.
        }
    }

    /**
     * Creates a course, student and teacher.
     */
    private function prepare_course() {

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create a teacher.
        $this->teacher = $this->getDataGenerator()->create_user(array('email' => 'teacher@example.com', 'username' => 'teacher'));
        $this->setUser($this->teacher);

        // Enroll a student.
        $this->student = $this->getDataGenerator()->create_user(array('email' => 'student@example.com', 'username' => 'student'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id);
    }

    /**
     * Enables / disables mail delivery in the test course.
     * @param boolean $enabled Whether mail delivery should be enabled in the test course.
     */
    private function set_mail_enabled_in_course($enabled) {
        local_uploadnotification_test_helper::set_mail_enabled_in_course($this->course->id, $enabled);
    }

    /**
     * Allows / forbids attachment delivery in the test course.
     * @param boolean $enabled Whether attachment delivery should be allowed in the test course.
     */
    private function set_attachment_allowed_in_course($enabled) {
        local_uploadnotification_test_helper::set_attachment_allowed_in_course($this->course->id, $enabled);
    }

    /**
     * Set the max attachment filesize for the student.
     * @param int $filesize The max attachment filesize.
     * @param int $user_id The user ID. If no one is passed, the ID of the default student will be used.
     */
    private function set_max_filesize_for_student($filesize, $user_id = null) {
        if ($user_id == null) {
            $user_id = $this->student->id;
        }
        local_uploadnotification_test_helper::set_max_filesize_for_student($filesize, $user_id);
    }

    /**
     * Creates a new resource in the course.
     * @return stdClass The created resource.
     */
    private function create_resource() {
        return $this->getDataGenerator()->create_module('resource', array('course' => $this->course->id));
    }

    /**
     * Creates a new resource in the course and send all scheduled notifications.
     * @param string $filename The filename of the created resource in the /res folder.
     * @return array All send messages.
     */
    private function create_resource_and_send_mails($filename) {
        $resource = $this->create_resource(); // Create a resource in the course. This should be captured by the plugin.
        local_uploadnotification_test_helper::create_file(context_module::instance($resource->cmid)->id, $filename);
        local_uploadnotification_test_helper::make_all_notifications_older();
        unset_config('noemailever'); // Catch mails.
        $sink = $this->redirectEmails();
        local_uploadnotification_update_handler::send_notifications(); // Send the notification.
        return $sink->get_messages();
    }

    /**
     * Checks whether the passed message contains the passed file as an attachment.
     * @param stdClass $message The send message.
     * @param string $filename The filename of the expected attachment
     * @return bool Whether this file was likely included in the mail or not.
     */
    private function contains_attachment($message, $filename) {
        $file_content = file_get_contents(dirname(__FILE__) . '/res/' . $filename); // The attachment file.
        $expected_content = substr(base64_encode($file_content), 0, 50); // First chars of the base64 encoding.
        $attachment_found = (strpos($message->body, $expected_content) !== false);
        return $attachment_found;
    }
}
