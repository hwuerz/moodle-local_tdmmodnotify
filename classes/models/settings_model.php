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

require_once(dirname(__FILE__) . '/../../definitions.php');

/**
 * Created by PhpStorm.
 * User: Hendrik
 * Date: 17.06.2017
 * Time: 16:53
 */
abstract class local_uploadnotification_settings_model {

    /**
     * All settings
     * @var stdClass
     */
    protected $settings;

    /**
     * All attributes with their default settings.
     * These keys must me identically with the attributes in the database table.
     * If get_id_attribute() is not overwritten, the first key has to be the primary key in the database.
     * @return array Key is attribute name, Value is default Value
     */
    abstract protected function get_attributes();

    /**
     * Get the name of the primary attribute of the table
     * @return string
     */
    protected function get_id_attribute() {
        return array_keys($this->get_attributes())[0];
    }

    /**
     * Get the table name where the settings are stored
     * @return string
     */
    abstract protected function get_table_name();

    /**
     * settings_model constructor.
     * Get all settings for the entry with the passed ID
     * @param integer $id
     */
    protected function __construct($id) {
        global $DB;

        // Fetch settings from DB
        $settings = $DB->get_record(
            $this->get_table_name(),
            array($this->get_id_attribute() => $id),
            implode(', ', array_keys($this->get_attributes())),
            IGNORE_MISSING);

        // There are no settings stored --> build default settings
        if ($settings === false) {
            $settings = new stdClass();
            foreach (array_keys($this->get_attributes()) as $attribute) {
                $settings->{$attribute} = $this->get_attributes()[$attribute];
            }
            $settings->{$this->get_id_attribute()} = $id;
        }

        // Store settings in this object
        $this->settings = $settings;
    }

    /**
     * Requires that the passed attribute is a part of these settings
     * @param $attribute string The attribute to be checked
     * @throws InvalidArgumentException If the attribute is not defined in these settings
     */
    private function require_valid_attribute($attribute) {
        if (!in_array($attribute, array_keys($this->get_attributes()))) {
            throw new InvalidArgumentException('Attribute is not available in these settings');
        }
    }

    /**
     * Checks the value of the passed attribute
     * @param $attribute string The attribute which is requested
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'
     * @throws InvalidArgumentException If the attribute is not defined
     */
    protected function get($attribute) {
        $this->require_valid_attribute($attribute);
        return $this->settings->{$attribute};
    }

    /**
     * Stores the new preference.
     * Does not update the database until save id called
     * @param $attribute string The attribute which should be set
     * @param $preference integer The new preference. Must be -1, 0 or 1
     * @throws InvalidArgumentException If the preference or attribute is invalid
     */
    protected function set_preference($attribute, $preference) {
        self::require_valid_preference($preference);
        $this->set($attribute, $preference);
    }

    /**
     * Checks whether the passed preference is valid and can be stored in the database
     * If it is not, an exception will be thrown
     * @param $preference int The preference to be checked
     * @throws InvalidArgumentException If the preference is invalid
     */
    private static function require_valid_preference($preference) {
        if (!self::is_valid_preference($preference)) {
            throw new InvalidArgumentException(
                "Only valid preferences are accepted. Use -1 for no preference, 0 for deactivated and 1 for activated");
        }
    }

    /**
     * Checks whether the passed preference is valid and can be stored in the database
     * @param $preference int The preference to be checked
     * @return bool True if valid, false otherwise
     */
    private static function is_valid_preference($preference) {
        return $preference == -1 || $preference == 0 || $preference == 1;
    }

    /**
     * Stores the new value.
     * Does not update the database until save id called
     * @param $attribute string The attribute which should be set
     * @param $value integer The new value. Must be 0 or 1. If variable is unset it will be handled as 0.
     * @throws InvalidArgumentException If the value or attribute is invalid
     */
    protected function set_binary($attribute, &$value) {
        $data = self::require_valid_binary($value);
        $this->set($attribute, $data);
    }

    /**
     * Checks whether the passed value is valid and can be stored in the database
     * If it is not, an exception will be thrown.
     * @param int $value The value to be checked
     * @return int The corrected binary value if possible.
     * @throws InvalidArgumentException If the value is invalid
     */
    private static function require_valid_binary(&$value) {
        // In checkboxes nothing is passed if they are unselected --> Map this case to 0
        if (!isset($value)) {
            return 0;
        }

        // Value is not valid --> throw the exception
        if (!self::is_valid_binary($value)) {
            throw new InvalidArgumentException(
                "Only valid binary values are accepted. Use 0 to forbid, 1 to allow");
        }

        return $value; // Value is valid
    }

    /**
     * Checks whether the passed value is valid and can be stored in the database
     * @param int $value The value to be checked
     * @return bool True if valid, false otherwise
     */
    private static function is_valid_binary($value) {
        return $value == 0 || $value == 1;
    }

    /**
     * Stores the new value under the passed attribute.
     * Does not update the database until save id called
     * @param $attribute string The attribute which should be set
     * @param $value mixed The new value of the attribute
     * @throws InvalidArgumentException If the attribute is invalid
     */
    protected function set($attribute, $value) {
        $this->require_valid_attribute($attribute);
        $this->settings->{$attribute} = $value;
    }

    /**
     * Stores all settings in the database.
     */
    public function save() {
        global $DB;

        $settings = (array) $this->settings;

        $sql = "REPLACE INTO {".$this->get_table_name()."} ("
            .implode(', ', array_keys($settings)). // All attributes
        ") VALUES ("
            .implode(', ', // Add a '?' for each settings attribute
                array_map(function($a) {
                    return '?';
                }, $settings)).
            ")";
        $DB->execute($sql, $settings);
    }
}