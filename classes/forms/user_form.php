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
 */
class local_uploadnotification_user_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Header.

        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $mform->addElement('html', '<h3>Uploadnotification</h3>');
        $mform->addElement('html', '<p>Do you want to receive notifications when new material was uploaded to a course?</p>');

        $preferences = array(
            '-1' => get_string('settings_no_preferences', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
            '0' => get_string('settings_disable', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
            '1' => get_string('settings_enable', LOCAL_UPLOADNOTIFICATION_FULL_NAME)
        );
        $mform->addElement('select', 'enable', get_string('setting_enable_plugin',
            LOCAL_UPLOADNOTIFICATION_FULL_NAME), $preferences);
        $mform->setDefault('enable', $this->_customdata['enable']);

        $mform->addElement('text', 'max_filesize', get_string('setting_max_filesize',
            LOCAL_UPLOADNOTIFICATION_FULL_NAME));
        $mform->setType('max_filesize', PARAM_INT);
        $mform->setDefault('max_filesize', $this->_customdata['max_filesize'] / 1024);

        $this->add_action_buttons();
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
        if ($data['max_filesize'] < 0) {
            $errors['max_filesize'] = get_string('setting_not_negative', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        }
        $max_admin_filesize = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_filesize') / 1024;
        if ($data['max_filesize'] > $max_admin_filesize) {
            $errors['max_filesize'] = get_string('setting_max_filesize_not_more_than_admin',
                LOCAL_UPLOADNOTIFICATION_FULL_NAME, $max_admin_filesize);
        }
        return $errors;
    }
}
