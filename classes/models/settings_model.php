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
 * Settings model.
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(__FILE__) . '/../../definitions.php');

/**
 * Settings model.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class local_uploadnotification_settings_model {

    /**
     * @var stdClass All settings
     */
    protected $settings;

    /**
     * All attributes with their default settings.
     * These keys must me identically with the attributes in the database table.
     * If get_id_attribute() is not overwritten, the first key has to be the primary key in the database.
     * @return array Key is attribute name, Value is default Value.
     */
    abstract protected function get_attributes();

    /**
     * Get the name of the primary attribute of the table.
     * @return string The name of the attribute which is the ID of the settings record.
     */
    protected function get_id_attribute() {
        return array_keys($this->get_attributes())[0];
    }

    /**
     * Get the table name where the settings are stored.
     * @return string The name of the database table where the settings are stored.
     */
    abstract protected function get_table_name();

    /**
     * settings_model constructor.
     * Get all settings for the entry with the passed ID.
     * @param int $id The ID of the settings record in the correct database table.
     */
    protected function __construct($id) {
        global $DB;

        // Fetch settings from DB.
        $settings = $DB->get_record(
            $this->get_table_name(),
            array($this->get_id_attribute() => $id),
            implode(', ', array_keys($this->get_attributes())),
            IGNORE_MISSING);

        // There are no settings stored --> build default settings.
        if ($settings === false) {
            $settings = new stdClass();
            foreach (array_keys($this->get_attributes()) as $attribute) {
                $settings->{$attribute} = $this->get_attributes()[$attribute];
            }
            $settings->{$this->get_id_attribute()} = $id;
        }

        // Store settings in this object.
        $this->settings = $settings;
    }

    /**
     * Requires that the passed attribute is a part of these settings.
     * @param string $attribute The attribute to be checked.
     * @throws InvalidArgumentException If the attribute is not defined in these settings.
     */
    private function require_valid_attribute($attribute) {
        if (!in_array($attribute, array_keys($this->get_attributes()))) {
            throw new InvalidArgumentException('Attribute is not available in these settings.');
        }
    }

    /**
     * Checks the value of the passed attribute.
     * @param string $attribute The attribute which is requested.
     * @return int -1 for no preferences, 0 for 'disabled', 1 for 'activated'.
     * @throws InvalidArgumentException If the attribute is not defined.
     */
    protected function get($attribute) {
        $this->require_valid_attribute($attribute);
        return $this->settings->{$attribute};
    }

    /**
     * Stores the new preference.
     * Does not update the database until save id called.
     * @param string $attribute The attribute which should be set.
     * @param int $preference The new preference. Must be -1, 0 or 1.
     * @throws InvalidArgumentException If the preference or attribute is invalid.
     */
    protected function set_preference($attribute, $preference) {
        self::require_valid_preference($preference);
        $this->set($attribute, $preference);
    }

    /**
     * Checks whether the passed preference is valid and can be stored in the database.
     * If it is not, an exception will be thrown.
     * @param int $preference The preference to be checked.
     * @throws InvalidArgumentException If the preference is invalid.
     */
    private static function require_valid_preference($preference) {
        if (!self::is_valid_preference($preference)) {
            throw new InvalidArgumentException(
                "Only valid preferences are accepted. Use -1 for no preference, 0 for deactivated and 1 for activated.");
        }
    }

    /**
     * Checks whether the passed preference is valid and can be stored in the database.
     * @param int $preference The preference to be checked.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_preference($preference) {
        return $preference == -1 || $preference == 0 || $preference == 1;
    }

    /**
     * Stores the new value.
     * Does not update the database until save id called.
     * @param string $attribute The attribute which should be set.
     * @param int $value The new value. Must be 0 or 1. If variable is unset it will be handled as 0.
     * @throws InvalidArgumentException If the value or attribute is invalid.
     */
    protected function set_binary($attribute, &$value) {
        $data = self::require_valid_binary($value);
        $this->set($attribute, $data);
    }

    /**
     * Checks whether the passed value is valid and can be stored in the database.
     * If it is not, an exception will be thrown.
     * @param int $value The value to be checked.
     * @return int The corrected binary value if possible.
     * @throws InvalidArgumentException If the value is invalid.
     */
    private static function require_valid_binary(&$value) {
        // In checkboxes nothing is passed if they are unselected --> Map this case to 0.
        if (!isset($value)) {
            return 0;
        }

        // Value is not valid --> throw the exception.
        if (!self::is_valid_binary($value)) {
            throw new InvalidArgumentException(
                "Only valid binary values are accepted. Use 0 to forbid, 1 to allow.");
        }

        return $value; // Value is valid.
    }

    /**
     * Checks whether the passed value is valid and can be stored in the database.
     * @param int $value The value to be checked.
     * @return bool True if valid, false otherwise.
     */
    private static function is_valid_binary($value) {
        return $value == 0 || $value == 1;
    }

    /**
     * Stores the new value under the passed attribute.
     * Does not update the database until save id called.
     * @param string $attribute The attribute which should be set.
     * @param mixed $value The new value of the attribute.
     * @throws InvalidArgumentException If the attribute is invalid.
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

        $id = $this->settings->{$this->get_id_attribute()};

        // Check whether this setting has already a row in the database.
        $db_entry_exist = $DB->record_exists($this->get_table_name(),
            array($this->get_id_attribute() => $id));

        if ($db_entry_exist) { // Update the existing entry if it exists.
            $settings_array = (array) $this->settings;
            $sql = "UPDATE {".$this->get_table_name()."} SET "
                . implode(', ', $this->get_set_string_foreach_setting($settings_array))
                . " WHERE "
                . $this->get_id_attribute() . "=?";
            // Add the primary key (id) to the end of the settings array for where clause.
            $settings_array[] = $id;
            // Hint: $DB->update_record() can not be used because the primary key can be named other than 'id'.
            $DB->execute($sql, $settings_array);

        } else { // Crate a new settings entry.
            $DB->insert_record($this->get_table_name(), (object)$this->settings, false);
        }
    }

    /**
     * Generates an array of strings to update the passed settings array via SQL.
     * The values are escaped with '?' and should be passed as a parameter in the execute.
     * @return array with of all settings attribute names followed by '=?'.
     *         Example: ['userid=?', 'enable_mail=?', 'enable_digest=?', 'max_mail_filesize=?'].
     */
    private function get_set_string_foreach_setting($settings_array) {
        return array_map(function($a) {
            return $a . '=?';
        }, array_keys($settings_array));
    }
}