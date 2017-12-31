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
 * Settings form for course admins
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/../../definitions.php');

/**
 * Settings form for moodle admins to customize uploadnotification.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_course_form extends moodleform {

    /**
     * The prefix for all used form elements and strings.
     */
    const STRING_PREFIX = 'settings_course_';

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Inject the course ID to parse the submitted data easily.
        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        // Headline.
        $mform->addElement('html', '<h3>'.$this->_customdata['fullname'].'</h3>');
        $mform->addElement('html',
            '<p>' . get_string(self::STRING_PREFIX . 'headline', LOCAL_UPLOADNOTIFICATION_FULL_NAME) . '</p>');

        // Get admin settings to show only relevant form elements.
        $admin_allow_mail = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_mail');
        $admin_allow_attachment = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_mail_filesize') > 0;
        $admin_allow_changelog = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_changelog');
        $admin_allow_diff = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_diff_filesize') > 0;

        // Check whether this course is too big for notification delivery.
        $course_context = context_course::instance($this->_customdata['id']);
        $amount_of_users = count_enrolled_users($course_context);
        if ($amount_of_users > get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_mail_amount')) {
            $admin_allow_mail = false;
        }

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
            $this->add_setting('checkbox', 'allow_attachment');
        }

        // Whether a changelog should be generated.
        if ($admin_allow_changelog) {
            $this->add_setting('checkbox', 'enable_changelog');
        }

        // Whether differences should be detected.
        if ($admin_allow_changelog && $admin_allow_diff) {
            $this->add_setting('checkbox', 'enable_diff');
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
     * @param array $data The data fields submitted from the form.
     * @param array $files Files submitted from the form.    *
     * @return array List of errors to be displayed on the form if validation fails.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Diff but no changelog.
        if (!isset($data['enable_changelog']) && isset($data['enable_diff'])) {
            $error = get_string(self::STRING_PREFIX . 'error_diff_no_changelog', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
            $errors['enable_changelog'] = $error;
            $errors['enable_diff'] = $error;
        }

        return $errors;
    }
}
