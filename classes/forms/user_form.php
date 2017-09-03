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
 * Settings form for users
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;

// @codingStandardsIgnoreStart PhpStorm only supports /** */ annotation
/** @noinspection PhpIncludeInspection */
require_once($CFG->libdir . '/formslib.php');
// @codingStandardsIgnoreEnd

require_once(dirname(__FILE__) . '/../../definitions.php');

/**
 * Settings form for moodle admins to customize uploadnotification
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_user_form extends moodleform {

    const STRING_PREFIX = 'settings_user_';

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Header.

        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $mform->addElement('html',
            '<h3>' . get_string(self::STRING_PREFIX . 'link', LOCAL_UPLOADNOTIFICATION_FULL_NAME) . '</h3>');
        $mform->addElement('html',
            '<p>' . get_string(self::STRING_PREFIX . 'headline', LOCAL_UPLOADNOTIFICATION_FULL_NAME) . '</p>');

        // Get admin settings to show only relevant form elements.
        $admin_allow_mail = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_mail');
        $admin_allow_attachment = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_mail_filesize') > 0;

        // Whether mails should be delivered.
        if ($admin_allow_mail) {
            $this->add_setting('select', 'enable_mail', array(
                '-1' => get_string(self::STRING_PREFIX . 'no_preferences', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
                '0' => get_string(self::STRING_PREFIX . 'disable', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
                '1' => get_string(self::STRING_PREFIX . 'enable', LOCAL_UPLOADNOTIFICATION_FULL_NAME)
            ));
        }

        // Whether attachments should be send.
        if ($admin_allow_mail && $admin_allow_attachment) {
            $this->add_setting('text', 'max_mail_filesize');
            $mform->setType('max_mail_filesize', PARAM_INT);
        }

        $this->add_action_buttons();
    }

    /**
     * Adds a new setting to the displayed form.
     * The string identifier in the language file must match to the passed element_name.
     * @param string $type The type of this setting (select, checkbox, ...)
     * @param string $element_name The name of the element. Must match the default settings, the language string
     *                             definition and will be used as varaible name for the response data.
     * @param null|array $options If selected type is checkbox, pass the options here.
     */
    private function add_setting($type, $element_name, $options = null) {
        $mform = $this->_form;

        $mform->addElement($type, $element_name,
            get_string(self::STRING_PREFIX . $element_name, LOCAL_UPLOADNOTIFICATION_FULL_NAME),
            $options);
        $mform->setDefault($element_name, $this->_customdata[$element_name]);
        $mform->addHelpButton($element_name, self::STRING_PREFIX . $element_name, LOCAL_UPLOADNOTIFICATION_FULL_NAME);
    }

    /**
     * Validate submitted form data
     *
     * @param      array $data The data fields submitted from the form. (not used)
     * @param      array $files Files submitted from the form (not used)
     *
     * @return     array  List of errors to be displayed on the form if validation fails.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['max_mail_filesize'] < 0) {
            $errors['max_mail_filesize'] = get_string('settings_user_not_negative', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        }
        $admin_max_mail_filesize = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_mail_filesize');
        if ($data['max_mail_filesize'] > $admin_max_mail_filesize) {
            $errors['max_mail_filesize'] = get_string('settings_user_max_mail_filesize_not_more_than_admin',
                LOCAL_UPLOADNOTIFICATION_FULL_NAME, $admin_max_mail_filesize);
        }
        return $errors;
    }
}
