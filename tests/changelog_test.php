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

/**
 * Class local_uploadnotification_changelog_test.
 *
 * vendor/bin/phpunit local_uploadnotification_changelog_test local/uploadnotification/tests/changelog_test.php
 *
 * Tests changelog generation.
 *
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group local_uploadnotification
 */
class local_uploadnotification_changelog_test extends advanced_testcase {

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
     * Checks that a changelog will be generated for updates.
     */
    public function test_changelog_generation() {
        $this->resetAfterTest(true);

        // Define admin settings.
        set_config('allow_changelog', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_changelog_enabled_in_course(1);

        $file_v2 = $this->create_file_and_update('file.pdf', 'file_v2.pdf');
        $this->assertTrue($this->is_changelog_generated($file_v2->cmid, 'file.pdf'));
    }

    /**
     * Checks that NO changelog will be generated if the admin has disabled the feature.
     */
    public function test_admin_disables_changelog() {
        $this->resetAfterTest(true);

        // Define admin settings.
        set_config('allow_changelog', 0, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_changelog_enabled_in_course(1);

        $file_v2 = $this->create_file_and_update('file.pdf', 'file_v2.pdf');
        $this->assertFalse($this->is_changelog_generated($file_v2->cmid, 'file.pdf'));
    }

    /**
     * Checks that NO changelog will be generated if the course has disabled the feature.
     */
    public function test_course_disables_changelog() {
        $this->resetAfterTest(true);

        // Define admin settings.
        set_config('allow_changelog', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_changelog_enabled_in_course(0); // DISABLES changelog generation in course.

        $file_v2 = $this->create_file_and_update('file.pdf', 'file_v2.pdf');
        $this->assertFalse($this->is_changelog_generated($file_v2->cmid, 'file.pdf'));
    }

    /**
     * Checks that NO changelog will be generated if the files are too different.
     */
    public function test_no_similarity() {
        $this->resetAfterTest(true);

        // Define admin settings.
        set_config('allow_changelog', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.
        $this->set_changelog_enabled_in_course(0); // DISABLES changelog generation in course.

        $file_v2 = $this->create_file_and_update('file.pdf', 'other.pdf');
        $this->assertFalse($this->is_changelog_generated($file_v2->cmid, 'file.pdf'));
    }

    /**
     * Checks that a changelog will be generated for updates.
     */
    public function test_default_values_enabled() {
        $this->resetAfterTest(true);

        // Define admin settings.
        set_config('allow_changelog', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('enable_changelog_by_default', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.

        $file_v2 = $this->create_file_and_update('file.pdf', 'file_v2.pdf');
        $this->assertTrue($this->is_changelog_generated($file_v2->cmid, 'file.pdf'));
    }

    /**
     * Checks that a changelog will be generated for updates.
     */
    public function test_default_values_disabled() {
        $this->resetAfterTest(true);

        // Define admin settings.
        set_config('allow_changelog', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('enable_changelog_by_default', 0, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.

        $file_v2 = $this->create_file_and_update('file.pdf', 'file_v2.pdf');
        $this->assertFalse($this->is_changelog_generated($file_v2->cmid, 'file.pdf'));
    }

    /**
     * Checks that a changelog will be generated for finally deleted updates.
     */
    public function test_real_deletion() {
        global $DB;
        $this->resetAfterTest(true);

        // Define admin settings.
        set_config('allow_changelog', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        set_config('enable_changelog_by_default', 1, LOCAL_UPLOADNOTIFICATION_FULL_NAME);

        $this->prepare_course(); // Create course, student and teacher.

        $file = $this->create_resource('file.pdf');
        course_delete_module($file->cmid, true); // Mark file for deletion.
        phpunit_util::run_all_adhoc_tasks(); // Hard delete file.
        $amount = $DB->count_records('course_modules', array('id' => $file->cmid));
        $this->assertEquals(0, $amount); // Ensure that the file was hard deleted.
        $file_v2 = $this->create_resource('file_v2.pdf');

        $this->assertTrue($this->is_changelog_generated($file_v2->cmid, 'file.pdf'));
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
     * Creates a file --> mark it for deletion --> Updates the file.
     * @param string $file1 The filename of the first file.
     * @param string $file2 The filename of the second file.
     * @return stdClass The resource of the final file.
     */
    private function create_file_and_update($file1, $file2) {
        $file = $this->create_resource($file1);
        course_delete_module($file->cmid, true);
        $file_v2 = $this->create_resource($file2);

        return $file_v2;
    }

    /**
     * Enables / disables changelog generation in the test course.
     * @param boolean $enabled Whether changelog generation should be enabled in the test course.
     */
    private function set_changelog_enabled_in_course($enabled) {
        $settings = new local_uploadnotification_course_settings_model($this->course->id);
        $settings->set_changelog_enabled($enabled);
        $settings->save();
    }

    /**
     * Creates a new resource in the course.
     * @param string $filename The filename of the created resource in the /res folder,
     * @return stdClass The created resource.
     */
    private function create_resource($filename = null) {

        $file = local_uploadnotification_test_helper::create_file(
            context_user::instance($this->teacher->id)->id,
            $filename,
            'user', 'draft', file_get_unused_draft_itemid()
        );

        $resource = $this->getDataGenerator()->create_module('resource', array(
            'course' => $this->course->id,
            'files' => $file->get_itemid()
        ));

        return $resource;
    }

    /**
     * Checks whether a string contains a substring.
     * @param string $string The complete string.
     * @param string $needle The substring which should be searched in the complete string.
     * @return bool Whether needle was found in string.
     */
    private function str_contains($string, $needle) {
        return strpos($string, $needle) !== false;
    }

    /**
     * Checks whether a changelog was generated.
     * @param int $cmid The course module id of the new file.
     * @param string $predecessor_name The name of the predecessor which should be included in the changelog.
     * @return bool Whether a changelog was generated or not.
     */
    private function is_changelog_generated($cmid, $predecessor_name) {
        global $DB;

        $cm = $DB->get_record('course_modules', array('id' => $cmid));
        $record = $DB->get_record('resource', array('id' => $cm->instance));

        $generated = $cm->showdescription == 1 && $this->str_contains($record->intro, $predecessor_name);
        return $generated;
    }
}
