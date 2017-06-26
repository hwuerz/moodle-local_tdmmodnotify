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

/**
 * Recipient iterator.
 */
class local_uploadnotification_recipient_iterator implements Iterator {
    /**
     * Record set.
     *
     * @var stdClass[]
     */
    protected $records;

    /**
     * Initialiser.
     */
    public function __construct() {
        $this->records = local_uploadnotification_util::get_scheduled_recipients();
    }

    /**
     * Return a recipient object for the current recipient.
     *
     * @return local_uploadnotification_recipient The recipient object.
     */
    public function current() {
        $userid        = current($this->records);
        $notifications = local_uploadnotification_util::get_notification_digest($userid);

        return local_uploadnotification_recipient::from_digest($notifications);
    }

    /**
     * Get the current recipient key.
     *
     * @return integer The ID of the recipient's associated user.
     */
    public function key() {
        return key($this->records);
    }

    /**
     * Skip to the next record.
     *
     * @return void
     */
    public function next() {
        next($this->records);
    }

    /**
     * Rewind to the beginning of the record set.
     *
     * @return void
     */
    public function rewind() {
        reset($this->records);
    }

    /**
     * Does the iterator still have records remaining?
     *
     * @return boolean True if records remain, else false.
     */
    public function valid() {
        return current($this->records) !== false;
    }
}
