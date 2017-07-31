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
 * Event observer.
 *
 * Responds to course module events emitted by the Moodle event manager.
 */
class local_uploadnotification_backup_lib {

    /**
     * The name of the database table where all deleted, but backuped files are managed.
     */
    const DELETED_FILE_TABLE = 'local_uploadnotification_del';

    /**
     * Hock to backup a file right before it will be deleted.
     * This is needed to have a reference for new uploads.
     * @param stdClass $cm Course module
     * @throws moodle_exception
     */
    public static function backup($cm) {

        // Only store this file if the plugin is enabled
        $enabled = get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'changelog_enabled');
        if (!$enabled) {
            return;
        }

        global $DB;

        // Get more information.
        $modinfo = get_fast_modinfo($cm->course);

        // Can't continue without the module information.
        // Needed for resource module verification
        if (!isset($modinfo->cms[$cm->id])) {
            return;
        }

        // Only resources are supported. Plugin does not work with more complex types
        $cminfo = $modinfo->cms[$cm->id];
        if ($cminfo->modname != 'resource') {
            return;
        }

        $fs = get_file_storage();

        // Get the file which will be deleted right now
        $area_files = $fs->get_area_files(
            context_module::instance($cm->id)->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false);

        // There should be minimum one file for this course module.
        // If there are less an error occurred.
        if (count($area_files) < 1) {
            return;
        }
        $deleted_file = array_shift($area_files); // Get only the first file

        // Get the context of the course and store the copy under this ID
        // This is needed, because the course module context is not longer available after original file is deleted.
        $context = context_course::instance($cm->course)->id;

        // Store a reference for this file in the plugin table
        $id = $DB->insert_record(self::DELETED_FILE_TABLE, (object)array(
            'course' => $cm->course,
            'context' => $context,
            'section' => $cm->section,
            'name' => $cminfo->name,
            'course_module' => $cm->id,
            'timestamp' => time()
        ), true);

        // Create a copy of the file and store is under the given ID
        $file_info = array(
            'contextid' => $context,
            'component' => LOCAL_UPLOADNOTIFICATION_FULL_NAME,
            'filearea' => LOCAL_UPLOADNOTIFICATION_RECENT_DELETIONS_FILEAREA,
            'itemid' => $id);
        try {
            $fs->create_file_from_storedfile($file_info, $deleted_file);
        } catch (Exception $exception) { // Unknown error --> rollback
            $DB->delete_records(self::DELETED_FILE_TABLE, array('id' => $id));
        }
    }

    /**
     * Deletes all backup files which are older than one hour.
     */
    public static function clean_up_old_files() {

        global $DB;

        // The DB query to select the files which should be deleted
        $select = 'timestamp < ' . time() - 60 * 60;

        // Get the references to the files
        $candidates_stored = $DB->get_records_select(self::DELETED_FILE_TABLE, $select);

        // Get the file instances for the records
        $fs = get_file_storage();
        foreach ($candidates_stored as $candidate) {
            // Get the file for the candidate
            $files = $fs->get_area_files(
                $candidate->context,
                LOCAL_UPLOADNOTIFICATION_FULL_NAME,
                LOCAL_UPLOADNOTIFICATION_RECENT_DELETIONS_FILEAREA,
                $candidate->id
            );
            // Delete the file (The loop should only be iterated once)
            foreach ($files as $file) {
                try {
                    $file->delete();
                } catch (Exception $exception) { // This file is not reachable for any reason
                    continue;
                }
            }
        }

        // Delete the reference in the database
        $DB->delete_records_select(self::DELETED_FILE_TABLE, $select);
    }

    /**
     * Get a previously saved file in the backup database.
     * @param int $backup_context The course context of the backup. Stored in column context in the deletion-table.
     * @param int $backup_id The ID of the backup. Stored in column id in the deletion-table.
     * @return stored_file The file instance of the backup-file
     */
    public static function get_backup_file($backup_context, $backup_id) {
        $fs = get_file_storage();
        $area_files = $fs->get_area_files(
            $backup_context,
            LOCAL_UPLOADNOTIFICATION_FULL_NAME,
            LOCAL_UPLOADNOTIFICATION_RECENT_DELETIONS_FILEAREA,
            $backup_id,
            'sortorder DESC, id ASC',
            false);
        return array_shift($area_files); // Get only the first file
    }
}