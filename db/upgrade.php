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
 * @copyright 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Performs an upgrade of the used database tables if required.
 * @param int $oldversion The currently installed version.
 * @return bool Whether the upgrade was successful or not.
 */
function xmldb_local_uploadnotification_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2017081400) {

        // Uploadnotification savepoint reached.
        upgrade_plugin_savepoint(true, 2017081400, 'local', 'uploadnotification');
    }

    if ($oldversion < 2017102200) {

        // Define field enable_digest to be added to local_uploadnotification_usr.
        $table = new xmldb_table('local_uploadnotification_usr');
        $field = new xmldb_field('enable_digest', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'enable_mail');

        // Conditionally launch add field enable_digest.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Uploadnotification savepoint reached.
        upgrade_plugin_savepoint(true, 2017102200, 'local', 'uploadnotification');
    }

    return true;
}