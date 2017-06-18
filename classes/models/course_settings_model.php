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
class course_settings_model extends settings_model {

    /**
     * All attributes of the course settings
     * These names must me identically with the attributes in the database table
     * The first name must be the primary key
     * @var array string
     */
    private $attributes = array('courseid', 'activated', 'attachment');

    /**
     * course_settings_model constructor.
     * Get all settings for the course with the passed ID
     * @param integer $courseid
     */
    public function __construct($courseid) {
        parent::__construct($courseid);
    }

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
        $this->set('activated', $preference);
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
        if(!in_array($preference, array(-1, 0))) {
            throw new InvalidArgumentException('A course admin can only allow attachments (-1) or not (0)');
        }
        $this->set('attachment', $preference);
    }
}