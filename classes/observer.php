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
 * @author    Luke Carrier <luke@tdm.co>, Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2014 The Development Manager Ltd, 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../definitions.php');
require_once(dirname(__FILE__) . '/update_handler.php');

/**
 * Event observer.
 *
 * Responds to course module events emitted by the Moodle event manager.
 *
 * @copyright (c) 2014 The Development Manager Ltd, 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadnotification_observer {

    /**
     * Course module created.
     *
     * @param \core\event\course_module_created $event The event that triggered our execution.
     */
    public static function course_module_created($event) {
        self::handle_update($event);
    }

    /**
     * Course module updated.
     *
     * @param \core\event\course_module_updated $event The event that triggered our execution.
     */
    public static function course_module_updated($event) {
        self::handle_update($event);
    }

    /**
     * Event handler.
     * Called by observers to handle notifications and changelog.
     * @param \core\event\base $event The received event.
     */
    private static function handle_update($event) {
        $handler = new local_uploadnotification_update_handler($event);

        if ($handler->is_changelog_requested()) {
            $handler->generate_changelog();
        }

        if ($handler->is_notification_enabled()) {
            $handler->schedule_notification();
        }
    }
}
