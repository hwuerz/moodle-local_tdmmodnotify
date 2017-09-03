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


/**
 * Manages mail attachments.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_attachment_optimizer {

    /**
     * @var local_uploadnotification_attachment_optimizer_file[] All known files.
     * Key: course_module id.
     * Value: attachment_optimizer_file object.
     */
    private $files = array();

    /**
     * @param cm_info $cm The course module record. Must contain the id and the modname.
     * @return local_uploadnotification_attachment_optimizer_file|bool The requested file or false if unavailable.
     */
    public function require_file($cm) {

        // Check whether this file is already known.
        if (in_array($cm->id, array_keys($this->files))) {
            return $this->files[$cm->id];
        }

        // This file is not known until now --> add it to the cache.

        // The course module has to be a resource.
        if ($cm->modname != 'resource') {
            return false;
        }

        // Get the file.
        $fs = get_file_storage();
        $context = context_module::instance($cm->id);
        $area_files = $fs->get_area_files(
            $context->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false);
        $resource_file = array_shift($area_files); // Get only the first file.

        // Fill cache object.
        $file = new local_uploadnotification_attachment_optimizer_file();
        $file->file_name = $resource_file->get_filename();
        $file->file_path = $resource_file->copy_content_to_temp();
        $file->filesize = $resource_file->get_filesize();
        $file->requesting_users = $this->calculate_requesting_users($cm->id);

        // Store cache object.
        $this->files[$cm->id] = $file;
        return $file;
    }

    /**
     * Calculates how many users have requested this file as an attachment.
     * The value might be not exactly but is an upper limit.
     * @param int $cm_id The course module ID of the resource.
     * @return int The amount of requesting users.
     */
    private function calculate_requesting_users($cm_id) {
        global $DB;

        $sql = <<<SQL
SELECT COUNT(notification.id)
FROM {local_uploadnotification} notification
LEFT JOIN {local_uploadnotification_usr} usr ON notification.userid = usr.userid
WHERE notification.coursemoduleid = ? AND usr.max_mail_filesize > 0
SQL;

        $count = $DB->count_records_sql($sql, array($cm_id));
        return $count;
    }

    /**
     * Deletes all created tmp files.
     */
    public function delete_all_tmp_copies() {
        foreach ($this->files as $file) {
            if (!empty($file->file_path) && file_exists($file->file_path)) {
                unlink($file->file_path);
            }
        }
    }
}

/**
 * Class local_uploadnotification_attachment_optimizer_file.
 * Simple wrapper around a requested file.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_attachment_optimizer_file {

    /**
     * @var string The name of the file.
     */
    public $file_name;

    /**
     * @var string The path where the file is stored.
     */
    public $file_path;

    /**
     * @var int The size of the file in bytes.
     */
    public $filesize;

    /**
     * @var int The amount of requesting users for this file.
     */
    public $requesting_users;
}