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
 * The name of the plugin.
 * Can be used if a name must be unique globally.
 *
 * @var string
 */
define('LOCAL_UPLOADNOTIFICATION_NAME', 'uploadnotification');
define('LOCAL_UPLOADNOTIFICATION_FULL_NAME', 'local_uploadnotification');

/**
 * Action: created.
 *
 * @var integer
 */
define('LOCAL_UPLOADNOTIFICATION_ACTION_CREATED', 1);

/**
 * Action: updated.
 *
 * @var integer
 */
define('LOCAL_UPLOADNOTIFICATION_ACTION_UPDATED', 2);

/**
 * An identifier which can be used if a key must be unique globally.
 *
 * @var string
 */
define('LOCAL_UPLOADNOTIFICATION_RECENT_DELETIONS_FILEAREA', 'recent_deletions');
