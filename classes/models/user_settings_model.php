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
class local_uploadnotification_user_settings_model extends local_uploadnotification_settings_model {

    /**
     * All attributes of the user settings
     * These names must me identically with the attributes in the database table
     * The first name must be the primary key
     * @var array string
     */
    private $attributes = array('userid' => -1, 'activated' => -1, 'max_filesize' => 0);

    /**
     * user_settings_model constructor.
     * Get all settings for the user with the passed ID
     * @param integer $userid
     */
    // @codingStandardsIgnoreStart CodeSniffer detects constructor as useless but it is required to make class accessible
    public function __construct($userid) {
        parent::__construct($userid);
    }
    // @codingStandardsIgnoreEnd

    /**
     * All attributes of the user settings
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
        return 'local_uploadnotification_usr';
    }

    /**
     * Checks whether the user wants to receive email notifications.
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
     * Checks whether the user wants to receive email attachments and which is the maximum filesize for them
     * @return integer max filesize in byte
     */
    public function get_max_filesize() {
        return $this->get('max_filesize');
    }

    /**
     * Stores the new filesize.
     * Does not update the database until save id called
     * @param $filesize integer The new filesize in byte. Must be greater or equals zero
     * @throws InvalidArgumentException If the preference is invalid
     */
    public function set_max_filesize($filesize) {
        if (!is_int($filesize) || $filesize < 0) {
            throw new InvalidArgumentException('The filesize must be greater or equals zero');
        }
        $this->set('max_filesize', $filesize);
    }
}