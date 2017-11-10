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

require_once(dirname(__FILE__) . '/util.php');

require_once(dirname(__FILE__) . '/../../changeloglib/classes/backup_lib.php');
require_once(dirname(__FILE__) . '/../../changeloglib/classes/diff_detector.php');
require_once(dirname(__FILE__) . '/../../changeloglib/classes/pdftotext.php');
require_once(dirname(__FILE__) . '/../../changeloglib/classes/update_detector.php');

/**
 * Wrapper to access changelog functions.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_changelog {

    /**
     * Provides an update detector for the passed coursemodule.
     * Wrapper around changeloglib plugin to be used for course modules.
     * @param stdClass $coursemodule The course module which should be checked as an update.
     * @return local_changeloglib_update_detector The update detector.
     */
    public static function get_update_detector($coursemodule) {

        $new_file = self::get_file($coursemodule->id);
        $new_data = self::get_data($coursemodule);
        $new_files = array(new local_changeloglib_new_file_wrapper($new_file, $new_data));
        $context = self::get_context($coursemodule);
        $scope = self::get_scope($coursemodule);
        $further_candidates = self::get_pending_files($coursemodule);

        return new local_changeloglib_update_detector($new_files, $context, $scope, $further_candidates);
    }

    /**
     * Creates a backup of the passed course module.
     * Wrapper around changeloglib plugin to be used for course modules.
     * @param stdClass $coursemodule The course module of which a backup should created.
     */
    public static function backup_coursemodule($coursemodule) {

        // If the changelog is not enabled in the course --> do not backup the file.
        if (!local_uploadnotification_util::is_changelog_enabled($coursemodule->course)) {
            return;
        }

        // Get information to access the course module and create a copy of it.
        $data = self::get_data($coursemodule);
        $context_id_from = context_module::instance($coursemodule->id)->id;
        $component_from = 'mod_resource';
        $filearea_from = 'content';
        $itemid_from = 0;
        $context_id_to = self::get_context($coursemodule);
        $scope_id = self::get_scope($coursemodule);

        // Backup this course module using the changeloglib plugin.
        local_changeloglib_backup_lib::backup($data,
            $context_id_from, $component_from, $filearea_from, $itemid_from,
            $context_id_to, $scope_id);
    }

    /**
     * Check whether diff detection should be performed.
     * @return bool Whether the diff detection is allowed technically and by the admin.
     */
    public static function is_diff_allowed() {
        return get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'allow_changelog')
            && get_config(LOCAL_UPLOADNOTIFICATION_FULL_NAME, 'max_diff_filesize') > 0
            && local_changeloglib_pdftotext::is_installed();
    }

    /**
     * Get the array which should be stored as `data` for the passed course module.
     * @param stdClass $coursemodule The course module record
     * @return array The data which should be stored with the passed course module to identify a definite predecessor.
     */
    private static function get_data($coursemodule) {
        return array('coursemoduleid' => $coursemodule->id);
    }

    /**
     * Get the context under which a backup is stored.
     * @param stdClass $coursemodule The coursemodule record.
     * @return int The context ID under which backups are stored.
     */
    private static function get_context($coursemodule) {
        return context_course::instance($coursemodule->course)->id;
    }

    /**
     * Get the scope under which a backup is stored. For resources this is the section.
     * @param stdClass $coursemodule The coursemodule record.
     * @return int The scope under which backups are stored.
     */
    private static function get_scope($coursemodule) {
        return $coursemodule->section;
    }

    /**
     * Get all files in the same course and section like the passed course module for which a deletion is marked.
     * These files are relevant for the update_detector too.
     * @param stdClass $coursemodule The coursemodule record.
     * @return stored_file[] All relevant pending files.
     */
    private static function get_pending_files($coursemodule) {
        global $DB;

        // Get candidates_pending.
        // These are all files which are marked for deletion, but are still in the normal storage.
        $candidates_pending = $DB->get_records('course_modules', array(
            'deletioninprogress' => 1, // The old file should be deleted.
            'course' => $coursemodule->course,
            'section' => $coursemodule->section
        ));

        // Get the file instances for pending candidates.
        $pending_files = array_map(function ($candidate) {
            return self::get_file($candidate->id);
        }, $candidates_pending);

        // The pending files can contain null values if no file was found. Filter these entries.
        return array_filter($pending_files, function ($file) {
            return $file instanceof stored_file;
        });
    }

    /**
     * Get the file of the passed course module.
     * The course module must be a resource instance and the file be available in the mod_resource component.
     * @param int $coursemodule_id The ID of the course module from where the file is fetched.
     * @return stored_file The resource file for the passed course module.
     */
    private static function get_file($coursemodule_id) {
        $fs = get_file_storage();
        $context = context_module::instance($coursemodule_id);
        $area_files = $fs->get_area_files(
            $context->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false);
        return array_shift($area_files); // Get only the first file.
    }
}
