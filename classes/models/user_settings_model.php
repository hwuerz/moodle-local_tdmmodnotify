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
 * User settings model.
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/settings_model.php');

/**
 * User settings model.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_user_settings_model extends local_uploadnotification_settings_model {

    /**
     * All attributes of the user settings.
     * These names must me identically with the attributes in the database table.
     * The first name must be the primary key.
     * @var array An array of all attributes with their default values.
     */
    private $attributes = array(
        'userid' => -1,
        'enable_mail' => -1,
        'max_mail_filesize' => 0
    );

    /**
     * user_settings_model constructor.
     * Get all settings for the user with the passed ID.
     * @param int $userid The ID of the user whose settings should be fetched.
     */
    // @codingStandardsIgnoreStart CodeSniffer detects constructor as useless but it is required to make class accessible
    public function __construct($userid) {
        parent::__construct($userid);
    }
    // @codingStandardsIgnoreEnd

    /**
     * All attributes of the user settings.
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
        return 'local_uploadnotification_usr';
    }

    /**
     * Checks whether the user wants to receive email notifications.
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'.
     */
    public function is_mail_enabled() {
        return $this->get('enable_mail');
    }

    /**
     * Stores the new preference.
     * Does not update the database until save id called
     * @param int $preference The new preference. Must be -1, 0 or 1.
     * @throws InvalidArgumentException If the preference is invalid.
     */
    public function set_mail_enabled($preference) {
        $this->set_preference('enable_mail', $preference);
    }

    /**
     * Checks whether the user wants to receive email attachments and which is the maximum filesize for them.
     * @return int Max filesize in byte.
     */
    public function get_max_filesize() {
        return $this->get('max_mail_filesize');
    }

    /**
     * Stores the new filesize.
     * Does not update the database until save id called.
     * @param int $filesize The new filesize in byte. Must be greater or equals zero.
     * @throws InvalidArgumentException If the preference is invalid.
     */
    public function set_max_filesize($filesize) {
        if (!is_int($filesize) || $filesize < 0) {
            throw new InvalidArgumentException('The filesize must be greater or equals zero');
        }
        $this->set('max_mail_filesize', $filesize);
    }
}