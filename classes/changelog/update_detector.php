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
 * Checks whether a course module is an update of an old, deleted module.
 */
class local_uploadnotification_update_detector {

    // Keys in the response array after a similarity check
    const BEST_CANDIDATE_KEY = 'best_candidate_key';
    const BEST_CANDIDATE_SIMILARITY = 'best_candidate_similarity';

    /**
     * Check whether this is course module is an update.
     * @param $original_cm
     * @return string
     */
    public static function is_update($original_cm) {

        global $DB;

        // Get the uploaded file instance
        $original_file = self::get_file($original_cm->id);

        // Get candidates_pending
        // These are all files which are marked for deletion, but are still in the normal storage
        $candidates_pending = $DB->get_records('course_modules', array(
            'deletioninprogress' => 1, // The old file should be deleted
            'course' => $original_cm->course,
            'section' => $original_cm->section
        ));
        // Get the file instances for pending candidates
        $candidate_pending_files = array_map(function ($candidate) {
            return self::get_file($candidate->id);
        }, $candidates_pending);
        $candidate_pending_result = self::check_candidates($candidate_pending_files, $original_file);

        // Get candidates_stored
        $candidates_stored = $DB->get_records('local_uploadnotification_del', array(
            'course' => $original_cm->course,
            'section' => $original_cm->section
        ));
        // Get the file instances for pending candidates
        /** @var stored_file[] $candidate_stored_files */
        $candidate_stored_files = array_map(function ($candidate) {
            $fs = get_file_storage();
            $area_files = $fs->get_area_files(
                $candidate->context,
                LOCAL_UPLOADNOTIFICATION_UNIQUE_PREFIX,
                LOCAL_UPLOADNOTIFICATION_RECENT_DELETIONS_FILEAREA,
                $candidate->id,
                'sortorder DESC, id ASC',
                false);
            return array_shift($area_files); // Get only the first file
        }, $candidates_stored);
        $candidate_stored_result = self::check_candidates(
            $candidate_stored_files,
            $original_file,
            $candidate_pending_result[self::BEST_CANDIDATE_SIMILARITY]);


        if ($candidate_stored_result[self::BEST_CANDIDATE_KEY] > 0.5) {
            $DB->insert_record('local_uploadnotification_cl', (object)array(
                'course_module' => $original_cm->id,
                'changelog' => "Best fitting candidate: Stored: " .
                    $candidate_stored_files[$candidate_stored_result[self::BEST_CANDIDATE_KEY]]->get_filename()
            ));
            echo "Best fitting candidate: Stored: " .
                $candidate_stored_files[$candidate_stored_result[self::BEST_CANDIDATE_KEY]]->get_filename() . "\n";
            return null;
        }

        if ($candidate_pending_result[self::BEST_CANDIDATE_KEY] > 0.5) {
            $DB->insert_record('local_uploadnotification_cl', (object)array(
                'course_module' => $original_cm->id,
                'changelog' => "Best fitting candidate: Pending: " .
                    $candidate_pending_files[$candidate_pending_result[self::BEST_CANDIDATE_KEY]]->get_filename()
            ));
            echo "Best fitting candidate: Pending: " .
                $candidate_pending_files[$candidate_pending_result[self::BEST_CANDIDATE_KEY]]->get_filename() . "\n";
            return null;
        }

        $DB->insert_record('local_uploadnotification_cl', (object)array(
            'course_module' => $original_cm->id,
            'changelog' => "No fitting candidate"
        ));
        echo "No fitting candidate\n";
        return null;
    }

    /**
     * @param stored_file[] $candidate_files
     * @param stored_file $original_file
     * @param int $best_similarity
     * @return array
     */
    private static function check_candidates($candidate_files, $original_file, $best_similarity = 0) {

        // Store the data of the best candidate
        $best_candidate = -1;

        // Check each candidate whether it is the best
        foreach ($candidate_files as $key => $candidate_file) {

            echo "Check for similarity with ".$candidate_file->get_filename()
                ."\n  Mimetype ".$candidate_file->get_mimetype()." vs ".$original_file->get_mimetype()
                ."\n  Filename ".$candidate_file->get_filename()." vs ".$original_file->get_filename()
                ."\n  size ".$candidate_file->get_filesize()." vs ".$original_file->get_filesize()
                ."\n  deletion time ".(time() - $candidate_file->get_timemodified())
                ."\n";

            // The types of the files must match
            if ($original_file->get_mimetype() != $candidate_file->get_mimetype()) {
                continue;
            }

            $similarity = self::calculate_meta_similarity($original_file, $candidate_file);
            echo "  Similarity = ".$similarity."\n";
            if ($similarity > $best_similarity) { // This candidate is the best until now
                $best_candidate = $key;
                $best_similarity = $similarity;
            }
        }

        return array(
            self::BEST_CANDIDATE_KEY => $best_candidate,
            self::BEST_CANDIDATE_SIMILARITY => $best_similarity,
        );
    }

    /**
     * Calculates the similarity of the passed files based on meta information.
     * This means, the content will not become analysed.
     * @param stored_file $original The original file which is now uploaded
     * @param stored_file $candidate An candidate which might be a predecessor of the file.
     * @return float The similarity of the two files based on meta information
     */
    private static function calculate_meta_similarity(stored_file $original, stored_file $candidate) {
        // How similar are the file names
        $filename = self::levenshtein_realtive($original->get_filename(), $candidate->get_filename());

        // How similar is the file size
        $filesize = self::number_similarity_realtive($original->get_filesize(), $candidate->get_filesize());

        // How many minutes ago the candidate was deleted
        $deletion_time = 60 / max(60, (time() - $candidate->get_timemodified()));

        return ($filename + $filesize + $deletion_time) / 3;
    }

    /**
     * Wrapper around @see levenshtein Which calculates the operations relative to the string length
     * @param string $str1 The first string
     * @param string $str2 The second string
     * @return float The operations relative to the string length.
     */
    private static function levenshtein_realtive($str1, $str2) {
        return 1 - levenshtein($str1, $str2) / max(strlen($str1), strlen($str2));
    }

    /**
     * Calculates the similarits between the two numbers based on the relative difference between them
     * @param int $val1 The first number
     * @param int $val2 The second number
     * @return float The similarity of the passed numbers
     */
    private static function  number_similarity_realtive($val1, $val2) {
        return 1 - abs($val1 - $val2) / max($val1, $val2);
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
        return array_shift($area_files); // Get only the first file
    }
}
