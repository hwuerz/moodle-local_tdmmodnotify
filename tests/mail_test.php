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
 * Class local_uploadnotification_mail_test.
 *
 * vendor/bin/phpunit local_uploadnotification_mail_test local/uploadnotification/tests/mail_test.php
 *
 * Tests mail delivery.
 *
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group local_uploadnotification
 */
class local_uploadnotification_mail_test extends advanced_testcase {

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
    public function test_mail_delivery() {
        global $DB;
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_mail_enabled_in_course(1); // Enable mail delivery in course.
        $resource = $this->create_resource(); // Create a resource in the course. This should be captured by the plugin.

        // Check that a notification was planed for the enrolled student.
        $this->assertEquals(1, $DB->count_records(local_uploadnotification_test_helper::NOTIFICATION_TABLE));

        // Check the content of the planed notification.
        $planed_notification = $DB->get_record(local_uploadnotification_test_helper::NOTIFICATION_TABLE, array());
        $this->assertEquals($this->course->id, $planed_notification->courseid);
        $this->assertEquals($resource->cmid, $planed_notification->coursemoduleid);
        $this->assertEquals($this->student->id, $planed_notification->userid);

        // Catch mails.
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        // Mails are NOT send directly.
        local_uploadnotification_update_handler::send_notifications();
        $messages = $sink->get_messages(); // Get all send mails.
        $this->assertEquals(0, count($messages)); // There are no send mails.

        // Mails are send after five minutes.
        local_uploadnotification_test_helper::make_notification_older($planed_notification->id);
        local_uploadnotification_update_handler::send_notifications(); // Send the notification.
        $messages = $sink->get_messages(); // Get all send mails.
        $this->assertEquals(1, count($messages)); // The mail was send.

        // The planed notification was deleted after sending.
        $amount = $DB->count_records('local_uploadnotification');
        $this->assertEquals(0, $amount);
    }

    /**
     * Admin disables mail delivery --> no notifications are scheduled.
     */
    public function test_admin_disables_notification_planing() {
        global $DB;
        $this->resetAfterTest(true);

        // Disable the mail delivery in admin settings.
        set_config('allow_mail', 0, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_mail_enabled_in_course(1); // Enable mail delivery in course.
        $this->create_resource(); // Create a resource in the course. This should be captured by the plugin.

        // Check that no notification was planed.
        $this->assertEquals(0, $DB->count_records(local_uploadnotification_test_helper::NOTIFICATION_TABLE));
    }

    /**
     * Admin disables mail delivery --> scheduled notifications are not send.
     */
    public function test_admin_disables_notification_delivery() {
        global $DB;
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings to schedule a notification.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_mail_enabled_in_course(1); // Enable mail delivery in course.
        $this->create_resource(); // Create a resource in the course. This should be captured by the plugin.

        // Check that a notification was planed.
        $this->assertEquals(1, $DB->count_records(local_uploadnotification_test_helper::NOTIFICATION_TABLE));

        set_config('allow_mail', 0, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        local_uploadnotification_test_helper::make_all_notifications_older();
        unset_config('noemailever'); // Catch mails.
        $sink = $this->redirectEmails();
        local_uploadnotification_update_handler::send_notifications(); // Send the notification.
        $this->assertEquals(0, count($sink->get_messages())); // No mail was send.
    }

    /**
     * Test the correct merging of user and course settings.
     * Only send notifications if somebody has requested them and nobody has forbidden them.
     */
    public function test_settings() {
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings to schedule a notification.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.

        // Index 0: course settings
        // Index 1: student settings
        // Index 2: Expected amount of mails.
        $settings = array(
            array(-1, -1, 0),
            array(-1, 0, 0),
            array(-1, 1, 1),
            array(0, -1, 0),
            array(0, 0, 0),
            array(0, 1, 0),
            array(1, -1, 1),
            array(1, 0, 0),
            array(1, 1, 1)
        );
        foreach ($settings as $setting) {
            $this->set_mail_enabled_in_course($setting[0]);
            $this->set_mail_enabled_for_student($setting[1]);
            $this->assertEquals($setting[2], count($this->create_resource_and_send_mails()));
        }
    }

    /**
     * Test the checks for availability settings.
     */
    public function test_availability_checks() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        // Enable the mail delivery in admin settings to schedule a notification.
        set_config('allow_mail', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.

        $this->set_mail_enabled_in_course(true);
        $this->set_mail_enabled_for_student(true);

        $CFG->enableavailability = true;
        $time = time() + 10000;
        $this->getDataGenerator()->create_module('resource', array(
            'course' => $this->course->id,
            'availability' => json_encode( // The resource will be available at $time.
                \core_availability\tree::get_root_json(array(
                    \availability_date\condition::get_json(\availability_date\condition::DIRECTION_FROM, $time)),
                    \core_availability\tree::OP_AND,
                    false)))
        );

        // The notification was planed.
        $amount = $DB->count_records('local_uploadnotification');
        $this->assertEquals(1, $amount);

        // The timestamp maps to the availability date.
        $record = $DB->get_record('local_uploadnotification', array());
        $this->assertEquals($time, $record->timestamp);
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
     * Enables / disables mail delivery for the student.
     * @param boolean $enabled Whether mail delivery should be enabled in the test course.
     */
    private function set_mail_enabled_for_student($enabled) {
        $settings = new local_uploadnotification_user_settings_model($this->student->id);
        $settings->set_mail_enabled($enabled);
        $settings->save();
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
     * @return array All send messages.
     */
    private function create_resource_and_send_mails() {
        $this->create_resource(); // Create a resource in the course. This should be captured by the plugin.
        local_uploadnotification_test_helper::make_all_notifications_older();
        unset_config('noemailever'); // Catch mails.
        $sink = $this->redirectEmails();
        local_uploadnotification_update_handler::send_notifications(); // Send the notification.
        return $sink->get_messages();
    }
}
