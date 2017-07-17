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
 * Upload notification.
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../../definitions.php');

/**
 * Checks whether a course module is an update of an old, deleted module.
 */
class local_uploadnotification_pdftotext {

    public static function is_installed() {
        $output = shell_exec('dpkg -s poppler-utils 2>&1');
        if (strpos($output, 'pdf') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public static function convert_to_txt(stored_file $file) {

        // The linux tool poppler-utils must be installed
        if (!self::is_installed()) {
            return false;
        }

        // The file must be a PDF file
        if ($file->get_mimetype() != 'application/pdf') {
            return false;
        }

        $file_tmp = $file->copy_content_to_temp();
        $file_tmp_txt = $file_tmp . '_txt';

        shell_exec("pdftotext " . $file_tmp . " " . $file_tmp_txt . " 2>&1");

        unlink($file_tmp); // Remove PDF file copy
        return $file_tmp_txt;
    }

}
