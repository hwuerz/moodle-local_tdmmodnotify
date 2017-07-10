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

    /**
     * The course_module of the original resource whose previous version should be found.
     * @var stdClass $original_cm
     */
    private $original_cm;

    /**
     * The file instance for the original course_module.
     * @var stored_file $original_file
     */
    private $original_file;

    /**
     * local_uploadnotification_update_detector constructor.
     * @param stdClass $original_cm The course_module of the original resource whose previous version should be found.
     */
    public function __construct($original_cm) {
        $this->original_cm = $original_cm;

        // Get the uploaded file instance
        $this->original_file = self::get_file($original_cm->id);
    }

    /**
     * Check whether this is course module is an update.
     * @return bool|stored_file False if this is not an update of an earlier file. The previous version of this file if found.
     */
    public function is_update() {
        global $DB;

        $candidate = $this->get_best_candidate();

        // No candidate was found
        if ($candidate == null) {
            return false;
        }

        // Threshold: If the candidate similarity is lower this value is is not a predecessor?
        if ($candidate->similarity < 0.5) {
            return false;
        }

//        $DB->insert_record('local_uploadnotification_cl', (object)array(
//            'course_module' => $this->original_cm->id,
//            'changelog' => "Best fitting candidate: " .
//                $candidate->file->get_filename()
//        ));
        return $candidate->file;

    }


    /**
     * Get the best candidate for an update.
     * This will check pending deletions as well as stored, but officially deleted files.
     * @return null|stdClass
     * Null is returned if no candidate fits.
     * The stdClass contains the key of the best candidate, the calculated similarity and the @see stored_file of the best candidate
     */
    private function get_best_candidate() {

        $best_pending = $this->get_best_pending_candidate();
        $best_stored = $this->get_best_stored_candidate();

        // Avoid access to similarity attribute on null type
        if ($best_pending == null) {
            return $best_stored;
        }

        // Avoid access to similarity attribute on null type
        if ($best_stored == null) {
            return $best_pending;
        }

        // Both components have found a candidate --> compare the similarity
        return $best_pending->similarity > $best_stored->similarity ? $best_pending : $best_stored;
    }

    /**
     * @return null|stdClass
     * Null is returned if no candidate fits.
     * The stdClass contains the key of the best candidate, the calculated similarity and the @see stored_file of the best candidate
     */
    private function get_best_pending_candidate() {
        global $DB;

        // Get candidates_pending
        // These are all files which are marked for deletion, but are still in the normal storage
        $candidates_pending = $DB->get_records('course_modules', array(
            'deletioninprogress' => 1, // The old file should be deleted
            'course' => $this->original_cm->course,
            'section' => $this->original_cm->section
        ));

        // Get the file instances for pending candidates
        $candidate_pending_files = array_map(function ($candidate) {
            return self::get_file($candidate->id);
        }, $candidates_pending);

        return $this->check_candidates($candidate_pending_files);
    }

    /**
     * @return null|stdClass
     * Null is returned if no candidate fits.
     * The stdClass contains the key of the best candidate, the calculated similarity and the @see stored_file of the best candidate
     */
    private function get_best_stored_candidate() {
        global $DB;

        // Get candidates_stored
        $candidates_stored = $DB->get_records('local_uploadnotification_del', array(
            'course' => $this->original_cm->course,
            'section' => $this->original_cm->section
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

        return $this->check_candidates($candidate_stored_files);
    }

    /**
     * @param stored_file[] $candidate_files
     * @return null|stdClass
     * Null is returned if no candidate fits.
     * The stdClass contains the key of the best candidate, the calculated similarity and the @see stored_file of the best candidate
     */
    private function check_candidates($candidate_files) {

        // Store the data of the best candidate
        $best_candidate = -1;
        $best_similarity = 0;

        // Check each candidate whether it is the best
        foreach ($candidate_files as $key => $candidate_file) {

            // The types of the files must match
            if ($this->original_file->get_mimetype() != $candidate_file->get_mimetype()) {
                continue;
            }

            $similarity = self::calculate_meta_similarity($this->original_file, $candidate_file);

            if ($similarity > $best_similarity) { // This candidate is the best until now
                $best_candidate = $key;
                $best_similarity = $similarity;
            }
        }

        // No candidate fits
        if ($best_candidate < 0) {
            return null;
        }

        // Build a response object based on the calculated similarity
        $response = new stdClass();
        $response->key = $best_candidate;
        $response->similarity = $best_similarity;
        $response->file = $candidate_files[$best_candidate];
        return $response;
    }

    /**
     * Calculates the similarity of the passed files based on meta information.
     * This means, the content will not become analysed.
     * @param stored_file $original The original file which is now uploaded
     * @param stored_file $candidate An candidate which might be a predecessor of the file.
     * @return float The similarity of the two files based on meta information. Value in range [0,1]
     */
    private static function calculate_meta_similarity(stored_file $original, stored_file $candidate) {

        $key_weight = 0;
        $key_similarity = 1;
        $factors = array();

        // How similar are the file names
        $filename = self::levenshtein_realtive($original->get_filename(), $candidate->get_filename());
        $factors[] = array($key_weight => 1, $key_similarity => $filename);

        // How similar is the file size
        $filesize = self::number_similarity_realtive($original->get_filesize(), $candidate->get_filesize());
        $factors[] = array($key_weight => 1, $key_similarity => $filesize);

        // How many minutes ago the candidate was deleted
        // Until one minute (= 60 sec) the similarity will not decrease
        $deletion_time = 60 / max(60, (time() - $candidate->get_timemodified()));
        $factors[] = array($key_weight => 0.5, $key_similarity => $deletion_time);

        // Sum up all factors with their weights
        $weight_sum = 0;
        $similarity_sum = 0;
        foreach ($factors as $factor) {
            $weight_sum += $factor[$key_weight];
            $similarity_sum += $factor[$key_similarity];
        }
        return $similarity_sum / $weight_sum;
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
