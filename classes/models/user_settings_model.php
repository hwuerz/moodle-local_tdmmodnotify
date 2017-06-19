<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
}
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
    public function __construct($userid) {
        parent::__construct($userid);
    }

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
        $this->setPreference('activated', $preference);
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
        if(!is_int($filesize) || $filesize < 0) {
            throw new InvalidArgumentException('The filesize must be greater or equals zero');
        }
        $this->set('max_filesize', $filesize);
    }
}