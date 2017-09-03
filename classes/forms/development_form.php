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
 * Form to send pending upload notifications to students
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->libdir.'/formslib.php');

/**
 * Form manually execute the cron function for uploadnotification.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_development_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Header.

        $mform->addElement('html', '<h3>Send Mails</h3>');
        $mform->addElement('html', '<p>Use this button for development, or if cron is not running.</p>');
        $mform->addElement('html', '<p>Simply calls the cron method for uploadnotification.</p>');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'send', 'SENDEN');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Validate submitted form data
     *
     * @param      array $data The data fields submitted from the form.
     * @param      array $files Files submitted from the form (not used)
     *
     * @return     array  List of errors to be displayed on the form if validation fails.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
