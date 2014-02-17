<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Recipient iterator.
 */
class local_tdmmodnotify_recipient_iterator implements Iterator {
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
        $this->records = local_tdmmodnotify_util::get_scheduled_recipients();
    }

    /**
     * Return a recipient object for the current recipient.
     *
     * @return local_tdmmodnotify_recipient The recipient object.
     */
    public function current() {
        $userid        = current($this->records);
        $notifications = local_tdmmodnotify_util::get_notification_digest($userid);

        return local_tdmmodnotify_recipient::from_digest($notifications);
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
