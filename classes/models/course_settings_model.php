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
 * Course settings model.
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/settings_model.php');

/**
 * Course settings model.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_course_settings_model extends local_uploadnotification_settings_model {

    /**
     * All attributes of the course settings.
     * These names must me identically with the attributes in the database table.
     * The first name must be the primary key.
     * @var array An array of all attributes with their default values. Defaults might be overwritten in constructor.
     */
    private $attributes = array(
        'courseid' => -1,
        'enable_mail' => -1,
        'allow_attachment' => 1,
        'enable_changelog' => 0,
        'enable_diff' => 0
    );

    /**
     * course_settings_model constructor.
     * Get all settings for the course with the passed ID.
     * @param int $courseid The ID of the course whose settings should be fetched.
     */
    // @codingStandardsIgnoreStart CodeSniffer detects constructor as useless but it is required to make class accessible.
    public function __construct($courseid) {
        // Overwrite default data by the admin configuration.
        $this->attributes['enable_changelog'] = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'enable_changelog_by_default');
        $this->attributes['enable_diff'] = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'enable_diff_by_default');
        parent::__construct($courseid);
    }
    // @codingStandardsIgnoreEnd

    /**
     * All attributes of the course settings.
     * These names must me identically with the attributes in the database table.
     * @return array An array of all attributes with their default values.
     */
    protected function get_attributes() {
        return $this->attributes;
    }

    /**
     * Get the table name where the settings are stored.
     * @return string The name of the database table where the settings are stored.
     */
    protected function get_table_name() {
        return 'local_uploadnotification_cou';
    }

    /**
     * Checks whether mails should be delivered for this course.
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'.
     */
    public function is_mail_enabled() {
        return $this->get('enable_mail');
    }

    /**
     * Stores the new preference.
     * Does not update the database until save is called.
     * If preference is not set, nothing will be done.
     * @param int $preference The new preference. Must be -1, 0 or 1.
     * @throws InvalidArgumentException If the preference is invalid.
     */
    public function set_mail_enabled(&$preference) {
        if (isset($preference)) {
            $this->set_preference('enable_mail', $preference);
        }
    }

    /**
     * Checks whether attachments could be send in this course.
     * @return int 0 for 'disabled', 1 for 'activated'.
     */
    public function is_attachment_allowed() {
        return $this->get('allow_attachment');
    }

    /**
     * Stores the new value.
     * Does not update the database until save is called.
     * @param int $value The new value. Must be 0 or 1. Undefined will be mapped tp 0.
     * @throws InvalidArgumentException If the value is invalid.
     */
    public function set_attachment_allowed(&$value) {
        $this->set_binary('allow_attachment', $value);
    }

    /**
     * Checks whether the changelog is enabled in this course.
     * @return int 0 for 'disabled', 1 for 'activated'.
     */
    public function is_changelog_enabled() {
        return $this->get('enable_changelog');
    }

    /**
     * Stores the new value.
     * Does not update the database until save is called.
     * @param int $value The new value. Must be 0 or 1. Undefined will be mapped tp 0.
     * @throws InvalidArgumentException If the value is invalid.
     */
    public function set_changelog_enabled(&$value) {
        $this->set_binary('enable_changelog', $value);
    }

    /**
     * Checks whether the difference detection is enabled in this course.
     * @return int 0 for 'disabled', 1 for 'activated'.
     */
    public function is_diff_enabled() {
        return $this->get('enable_diff');
    }

    /**
     * Stores the new value.
     * Does not update the database until save is called.
     * @param int $value The new value. Must be 0 or 1. Undefined will be mapped tp 0.
     * @throws InvalidArgumentException If the value is invalid.
     */
    public function set_diff_enabled(&$value) {
        $this->set_binary('enable_diff', $value);
    }
}