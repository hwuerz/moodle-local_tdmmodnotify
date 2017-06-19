<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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
 * Manages mail attachments
 */
class local_uploadnotification_attachment_optimizer {

    /**
     * @var attachment_optimizer_file[] All known files
     * Key: course_module id
     * Value: attachment_optimizer_file object
     */
    private $files = array();

    /**
     * @param $cm cm_info The course module record. Must contain the id and the modname.
     * @return attachment_optimizer_file|bool
     */
    public function require_file($cm) {

        // Check whether this file is already known
        if(in_array($cm->id, array_keys($this->files))) {
            return $this->files[$cm->id];
        }

        // This file is not known until now --> add it to the cache

        // The course module has to be a resource
        if ($cm->modname != 'resource') {
            return false;
        }

        // get the file
        $fs = get_file_storage();
        $context = context_module::instance($cm->id);
        $area_files = $fs->get_area_files(
            $context->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false);
        $resource_file = array_shift($area_files); //get only the first file

        // Fill cache object
        $file = new attachment_optimizer_file();
        $file->file_name = $resource_file->get_filename();
        $file->file_path = $resource_file->copy_content_to_temp();
        $file->filesize = $resource_file->get_filesize();
        $file->requesting_users = $this->calculate_requesting_users($cm->id);

        // Store cache object
        $this->files[$cm->id] = $file;
        return $file;
    }

    /**
     * Calculates how many users have requested this file as an attachment.
     * The value might be not exactly but is an upper limit
     * @param $cm_id int The course module ID of the resource
     * @return int The amount of requesting users
     */
    private function calculate_requesting_users($cm_id) {
        global $DB;
        // TODO optimize: which users request attachments
        $sql = <<<SQL
SELECT COUNT(n.id)
FROM {local_uploadnotification} n
INNER JOIN {local_uploadnotification_usr} u
ON n.userid = u.userid
WHERE u.activated = 1 AND n.coursemoduleid = ?
SQL;
        $count = $DB->count_records_sql($sql, array($cm_id));
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

class attachment_optimizer_file {

    public $file_name;

    public $file_path;

    public $filesize;

    public $requesting_users;
}