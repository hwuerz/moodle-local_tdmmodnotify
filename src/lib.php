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
 * TDM: Module modification notification.
 *
 * @package   local_tdmmodnotify
 * @author    Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Action: created.
 *
 * @var integer
 */
define('LOCAL_TDMMODNOTIFY_ACTION_CREATED', 1);

/**
 * Action: updated.
 *
 * @var integer
 */
define('LOCAL_TDMMODNOTIFY_ACTION_UPDATED', 2);

/**
 * Send scheduled notification emails.
 *
 * @return void
 */
function local_tdmmodnotify_cron() {
    $recipients  = new local_tdmmodnotify_recipient_iterator();
    $supportuser = core_user::get_support_user();
    $mailer      = new local_tdmmodnotify_mailer($recipients, $supportuser);

    $mailer->execute();
}
