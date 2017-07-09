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
 * This task will clean up saved files which are already deleted by the user.
 * The plugin will create copies of deleted files to identify updates of the same file.
 * These files will be stored for some minutes and become deleted by this task afterwards.
 *
 * @package   local_uploadnotification
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uploadnotification\task;

defined('MOODLE_INTERNAL') || die;

class clean_deletion_backup extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('deletion_backup_clean_task', 'local_uploadnotification');
    }

    public function execute() {

        // Delete old backup files for changelog generation
        require_once(dirname(__FILE__).'/../changelog/deletion_hock.php');
        \local_uploadnotification_deletion_hock::clean_up_old_files();

    }
}