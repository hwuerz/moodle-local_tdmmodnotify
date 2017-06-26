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
require_once(dirname(__FILE__).'/settings_model.php');

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
    private $attributes = array('courseid' => -1, 'activated' => -1, 'attachment' => -1);

    /**
     * course_settings_model constructor.
     * Get all settings for the course with the passed ID
     * @param integer $courseid
     */
    // @codingStandardsIgnoreStart CodeSniffer detects constructor as useless but it is required to make class accessible
    public function __construct($courseid) {
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
        return $this->get('activated');
    }

    /**
     * Stores the new preference.
     * Does not update the database until save id called
     * @param $preference integer The new preference. Must be -1, 0 or 1
     * @throws InvalidArgumentException If the preference is invalid
     */
    public function set_mail_enabled($preference) {
        $this->set_preference('activated', $preference);
    }

    /**
     * Checks whether attachments could be send in this course.
     * @return integer -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     */
    public function is_attachment_enabled() {
        return $this->get('attachment');
    }

    /**
     * Stores the new preference.
     * Does not update the database until save id called
     * @param $preference integer The new preference. Must be -1 or 0
     * @throws InvalidArgumentException If the preference is invalid
     */
    public function set_attachment_enabled($preference) {
        if (!in_array($preference, array(-1, 0))) {
            throw new InvalidArgumentException('A course admin can only allow attachments (-1) or not (0)');
        }
        $this->set_preference('attachment', $preference);
    }
}