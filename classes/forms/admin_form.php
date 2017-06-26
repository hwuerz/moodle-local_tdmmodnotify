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
 * Settings form for admins
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
require_once($CFG->libdir.'/formslib.php');
// @codingStandardsIgnoreEnd

/**
 * Settings form for moodle admins to customize uploadnotification
 */
class local_uploadnotification_admin_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Header.

        $mform->addElement('html', '<h3>Settings</h3>');
        $mform->addElement('html', '<p>Global Settings for uploadnotification</p>');

        $preferences = array(
            '0' => get_string('settings_disable', 'local_uploadnotification'),
            '1' => get_string('settings_enable', 'local_uploadnotification')
        );
        $mform->addElement('select', 'enable', get_string('setting_enable_plugin', 'local_uploadnotification'), $preferences);
        $mform->setDefault('enable', get_config('uploadnotification', 'enabled'));

        $mform->addElement('text', 'max_filesize', get_string('setting_max_filesize', 'local_uploadnotification'));
        $mform->setType('max_filesize', PARAM_INT);
        $mform->setDefault('max_filesize', get_config('uploadnotification', 'max_filesize'));

        $mform->addElement('text', 'max_mails_for_resource',
            get_string('setting_max_mails_for_resource', 'local_uploadnotification'));
        $mform->setType('max_mails_for_resource', PARAM_INT);
        $mform->setDefault('max_mails_for_resource', get_config('uploadnotification', 'max_mails_for_resource'));

        $this->add_action_buttons();
    }

    /**
     * Validate submitted form data
     *
     * @param      array  $data   The data fields submitted from the form. (not used)
     * @param      array  $files  Files submitted from the form (not used)
     *
     * @return     array  List of errors to be displayed on the form if validation fails.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
