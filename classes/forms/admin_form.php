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
require_once($CFG->libdir . '/formslib.php');
// @codingStandardsIgnoreEnd

require_once(dirname(__FILE__) . '/../../definitions.php');
require_once(dirname(__FILE__) . '/../changelog/pdftotext.php');

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
            '0' => get_string('settings_disable', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
            '1' => get_string('settings_enable', LOCAL_UPLOADNOTIFICATION_FULL_NAME)
        );
        $mform->addElement('select', 'enable', get_string('setting_enable_plugin',
            LOCAL_UPLOADNOTIFICATION_FULL_NAME), $preferences);
        $mform->setDefault('enable', get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'enabled'));

        $mform->addElement('text', 'max_filesize', get_string('setting_max_filesize', LOCAL_UPLOADNOTIFICATION_FULL_NAME));
        $mform->setType('max_filesize', PARAM_INT);
        $mform->setDefault('max_filesize', get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_filesize') / 1024);

        $mform->addElement('text', 'max_mails_for_resource',
            get_string('setting_max_mails_for_resource', LOCAL_UPLOADNOTIFICATION_FULL_NAME));
        $mform->setType('max_mails_for_resource', PARAM_INT);
        $mform->setDefault('max_mails_for_resource', get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_mails_for_resource'));

        $mform->addElement('select', 'changelog_enabled',
            get_string('setting_enable_changelog', LOCAL_UPLOADNOTIFICATION_FULL_NAME), $preferences);
        $mform->setDefault('changelog_enabled', get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'changelog_enabled'));

        // Only activate diff generation if pdftotext is available
        $diff_preferences = array('0' => get_string('settings_disable', LOCAL_UPLOADNOTIFICATION_FULL_NAME));
        $pdftotext_installed = local_uploadnotification_pdftotext::is_installed();
        if ($pdftotext_installed) {
            $diff_preferences['1'] = get_string('settings_enable', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        }
        $mform->addElement('select', 'diff_enabled',
            get_string('setting_enable_diff', LOCAL_UPLOADNOTIFICATION_FULL_NAME), $diff_preferences);
        $mform->setDefault('diff_enabled', get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'diff_enabled'));
        if ($pdftotext_installed) {
            $mform->addElement('html', get_string('setting_enable_diff_available', LOCAL_UPLOADNOTIFICATION_FULL_NAME));
        } else {
            $mform->addElement('html', get_string('setting_enable_diff_not_available', LOCAL_UPLOADNOTIFICATION_FULL_NAME));
        }

        $this->add_action_buttons();
    }

    /**
     * Validate submitted form data
     *
     * @param array $data The data fields submitted from the form. (not used)
     * @param array $files Files submitted from the form (not used)
     *
     * @return array List of errors to be displayed on the form if validation fails.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['max_filesize'] < 0) {
            $errors['max_filesize'] = get_string('setting_not_negative', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        }
        if ($data['max_mails_for_resource'] < 0) {
            $errors['max_mails_for_resource'] = get_string('setting_not_negative', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        }
        if ($data['diff_enabled'] && !$data['changelog_enabled']) {
            $errors['diff_enabled'] = get_string('setting_require_changelog_for_diff',
                LOCAL_UPLOADNOTIFICATION_FULL_NAME);
            $errors['changelog_enabled'] = get_string('setting_require_changelog_for_diff',
                LOCAL_UPLOADNOTIFICATION_FULL_NAME);
        }
        return $errors;
    }
}
