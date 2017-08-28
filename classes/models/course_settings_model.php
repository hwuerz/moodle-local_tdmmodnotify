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

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/settings_model.php');

/**
 * Created by PhpStorm.
 * User: Hendrik
 * Date: 17.06.2017
 * Time: 16:53
 */
class local_uploadnotification_course_settings_model extends local_uploadnotification_settings_model {

    /**
     * All attributes of the course settings
     * These names must me identically with the attributes in the database table
     * The first name must be the primary key
     * @var array string
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
     * Get all settings for the course with the passed ID
     * @param integer $courseid
     */
    // @codingStandardsIgnoreStart CodeSniffer detects constructor as useless but it is required to make class accessible
    public function __construct($courseid) {
        // Overwrite default data by the admin configuration
        $this->attributes['enable_changelog'] = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'enable_changelog_by_default');
        $this->attributes['enable_diff'] = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'enable_diff_by_default');
        parent::__construct($courseid);
    }
    // @codingStandardsIgnoreEnd

    /**
     * All attributes of the course settings
     * These names must me identically with the attributes in the database table
     * @return array of strings
     */
    protected function get_attributes() {
        return $this->attributes;
    }

    /**
     * Get the table name where the settings are stored
     * @return string
     */
    protected function get_table_name() {
        return 'local_uploadnotification_cou';
    }

    /**
     * Checks whether mails should be delivered for this course.
     * @return integer -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     */
    public function is_mail_enabled() {
        return $this->get('enable_mail');
    }

    /**
     * Stores the new preference.
     * Does not update the database until save is called.
     * If preference is not set, nothing will be done.
     * @param $preference integer The new preference. Must be -1, 0 or 1.
     * @throws InvalidArgumentException If the preference is invalid
     */
    public function set_mail_enabled(&$preference) {
        if (isset($preference)) {
            $this->set_preference('enable_mail', $preference);
        }
    }

    /**
     * Checks whether attachments could be send in this course.
     * @return integer 0 for 'disabled', 1 for 'activated'
     */
    public function is_attachment_allowed() {
        return $this->get('allow_attachment');
    }

    /**
     * Stores the new value.
     * Does not update the database until save is called
     * @param $value integer The new value. Must be 0 or 1. Undefined will be mapped tp 0.
     * @throws InvalidArgumentException If the value is invalid
     */
    public function set_attachment_allowed(&$value) {
        $this->set_binary('allow_attachment', $value);
    }

    /**
     * Checks whether the changelog is enabled in this course.
     * @return integer 0 for 'disabled', 1 for 'activated'
     */
    public function is_changelog_enabled() {
        return $this->get('enable_changelog');
    }

    /**
     * Stores the new value.
     * Does not update the database until save is called
     * @param $value integer The new value. Must be 0 or 1. Undefined will be mapped tp 0.
     * @throws InvalidArgumentException If the value is invalid
     */
    public function set_changelog_enabled(&$value) {
        $this->set_binary('enable_changelog', $value);
    }

    /**
     * Checks whether the difference detection is enabled in this course.
     * @return integer 0 for 'disabled', 1 for 'activated'
     */
    public function is_diff_enabled() {
        return $this->get('enable_diff');
    }

    /**
     * Stores the new value.
     * Does not update the database until save is called
     * @param $value integer The new value. Must be 0 or 1. Undefined will be mapped tp 0.
     * @throws InvalidArgumentException If the value is invalid
     */
    public function set_diff_enabled(&$value) {
        $this->set_binary('enable_diff', $value);
    }
}