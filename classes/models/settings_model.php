<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
}

/**
 * Created by PhpStorm.
 * User: Hendrik
 * Date: 17.06.2017
 * Time: 16:53
 */
abstract class settings_model {

    /**
     * All settings
     * @var stdClass
     */
    protected $settings;

    /**
     * All attributes of the settings
     * These names must me identically with the attributes in the database table
     * @return array of strings
     */
    abstract protected function get_attributes();

    /**
     * Get the name of the primary attribute of the table
     * @return string
     */
    protected function get_id_attribute() {
        return $this->get_attributes()[0];
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
            implode(', ', $this->get_attributes()),
            IGNORE_MISSING);

        // There are no settings stored --> build default settings
        if($settings === false) {
            $settings = new stdClass();
            foreach ($this->get_attributes() as $attribute) {
                $settings->{$attribute} = -1;
            }
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
        if(!in_array($attribute, $this->get_attributes())) {
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
    protected function set($attribute, $preference) {
        $this->require_valid_attribute($attribute);
        local_uploadnotification_util::require_valid_preference($preference);
        $this->settings->{$attribute} = $preference;
    }

    /**
     * Stores all settings in the database.
     */
    public function save() {
        global $DB;

        $settings = (array) $this->settings;

        $sql = "REPLACE INTO {".$this->get_table_name()."} ("
            .implode(', ', array_keys($settings)). // all attributes
        ") VALUES ("
            .implode(', ', // Add a '?' for each settings attribute
                array_map(function($a) {return '?';}, $settings)).
            ")";
        $DB->execute($sql, $settings);
    }
}