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
require_once(dirname(__FILE__) . '/models/course_settings_model.php');


/**
 * Manages mail attachments
 */
class local_uploadnotification_attachment_optimizer {

    /**
     * @var local_uploadnotification_attachment_optimizer_file[] All known files
     * Key: course_module id
     * Value: attachment_optimizer_file object
     */
    private $files = array();

    /**
     * @param $cm cm_info The course module record. Must contain the id and the modname.
     * @param $course_settings local_uploadnotification_course_settings_model The course settings of the corresponding course.
     * @return bool|local_uploadnotification_attachment_optimizer_file
     */
    public function require_file($cm, $course_settings) {

        // Check whether this file is already known
        if (in_array($cm->id, array_keys($this->files))) {
            return $this->files[$cm->id];
        }

        // This file is not known until now --> add it to the cache

        // The course module has to be a resource
        if ($cm->modname != 'resource') {
            return false;
        }

        // Get the file
        $fs = get_file_storage();
        $context = context_module::instance($cm->id);
        $area_files = $fs->get_area_files(
            $context->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false);
        $resource_file = array_shift($area_files); // Get only the first file

        // Fill cache object
        $file = new local_uploadnotification_attachment_optimizer_file();
        $file->file_name = $resource_file->get_filename();
        $file->file_path = $resource_file->copy_content_to_temp();
        $file->filesize = $resource_file->get_filesize();
        $file->requesting_users = $this->calculate_requesting_users($cm->course, $course_settings);

        // Store cache object
        $this->files[$cm->id] = $file;
        return $file;
    }

    /**
     * Calculates how many users have requested this file as an attachment.
     * The value might be not exactly but is an upper limit
     * @param $course_id int The course ID of the resource
     * @param $course_settings local_uploadnotification_course_settings_model The settings of the course.
     * @return int The amount of requesting users
     */
    private function calculate_requesting_users($course_id, $course_settings) {
        global $DB;

        // Taken and modified from https://moodle.org/mod/forum/discuss.php?d=118532#p968575
        $sql = "
SELECT COUNT(u.id)
FROM {course} c
JOIN {context} ct ON c.id = ct.instanceid
JOIN {role_assignments} ra ON ra.contextid = ct.id
JOIN {user} u ON u.id = ra.userid
JOIN {role} r ON r.id = ra.roleid
LEFT JOIN {local_uploadnotification_usr} usr ON u.id = usr.userid
WHERE c.id = ?";

        // The course requests mails --> All students which have not forbidden mail deliver request the file
        if ($course_settings->is_mail_enabled() == '1') {
            $sql .= " AND (usr.userid IS NULL OR usr.enable_mail != 0)";

        } else if ($course_settings->is_mail_enabled() == '-1') { // No preference from the course --> Only active requests
            $sql .= " AND usr.enable_mail = 1";

        } else { // Course has forbidden mail (this function should not be called in this case)
            return 0;
        }

        $count = $DB->count_records_sql($sql, array($course_id));
        return $count;
    }

    /**
     * deletes all created tmp files
     */
    public function delete_all_tmp_copies() {
        foreach ($this->files as $file) {
            if (!empty($file->file_path) && file_exists($file->file_path)) {
                unlink($file->file_path);
            }
        }
    }
}

class local_uploadnotification_attachment_optimizer_file {

    public $file_name;

    public $file_path;

    public $filesize;

    public $requesting_users;
}