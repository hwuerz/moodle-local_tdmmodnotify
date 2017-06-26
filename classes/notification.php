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
 * Notification.
 */
class local_uploadnotification_notification extends local_uploadnotification_model {

    /**
     * Notification ID.
     *
     * The ID of this notification
     *
     * @var integer
     */
    protected $notificationid;

    /**
     * Action.
     *
     * One of the LOCAL_UPLOADNOTIFICATION_ACTION_* constants.
     *
     * @var integer
     */
    protected $action;

    /**
     * Whether this file is visible for the user (1) or not (0).
     *
     * @var integer
     */
    protected $visible;

    /**
     * Course ID.
     *
     * @var integer
     */
    protected $courseid;

    /**
     * Course full name.
     *
     * @var string
     */
    protected $coursefullname;

    /**
     * Module name.
     *
     * @var string
     */
    protected $modulename;

    /**
     * Filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * File ID in the coursemodules table.
     *
     * @var int
     */
    protected $moodleid;

    /**
     * Initialiser.
     *
     * @param integer $notificationid The ID of this notification.
     * @param integer $action Action.
     * @param integer $visible Whether this file is visible for the user or not.
     * @param integer $courseid Course ID.
     * @param string $coursefullname Course full name.
     * @param string $modulename Module name.
     * @param string $filename The name of the file
     * @param string $moodleid The ID of the file in coursemodules
     */
    public function __construct($notificationid, $action, $visible, $courseid, $coursefullname, $modulename,
                                $filename, $moodleid) {
        $this->notificationid    = $notificationid;
        $this->action            = $action;
        $this->visible           = $visible;
        $this->courseid          = $courseid;
        $this->coursefullname    = $coursefullname;
        $this->modulename        = $modulename;
        $this->filename          = $filename;
        $this->moodleid          = $moodleid;
    }

    /**
     * @return int
     */
    public function get_notificationid() {
        return $this->notificationid;
    }

    /**
     * @return int
     */
    public function get_action() {
        return $this->action;
    }

    /**
     * @return int
     */
    public function get_visible() {
        return $this->visible;
    }

    /**
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * @return string
     */
    public function get_coursefullname() {
        return $this->coursefullname;
    }

    /**
     * @return string
     */
    public function get_modulename() {
        return $this->modulename;
    }

    /**
     * @return string
     */
    public function get_filename() {
        return $this->filename;
    }

    /**
     * @return int
     */
    public function get_moodleid() {
        return $this->moodleid;
    }

    /**
     * Build the notification content.
     *
     * @param stdClass $substitutions The string substitions to be passed to the location API when generating the
     *                                content. This object must include a moodle_url object in its baseurl property,
     *                                else a fatal error will be raised.
     * @return object {string text, string html}
     * @throws coding_exception
     */
    public function build_content($substitutions) {
        switch ($this->action) {
            case LOCAL_UPLOADNOTIFICATION_ACTION_CREATED:
                $action = 'created';
                break;

            case LOCAL_UPLOADNOTIFICATION_ACTION_UPDATED:
                $action = 'updated';
                break;

            default:
                throw new coding_exception("Invalid action '{$this->action}'");
        }

        $substitutions->baseurl_file->param('id', $this->moodleid);
        $substitutions->url_file = $substitutions->baseurl_file->out();

        $substitutions->baseurl_course->param('id', $this->courseid);
        $substitutions->url_course = $substitutions->baseurl_course->out();

        foreach ($this->model_accessors() as $attribute) {
            $substitutions->{$attribute} = $this->{$attribute};
        }
        $substitutions->action = get_string("action{$action}", 'local_uploadnotification');

        $context = (object) array(
            'text' => get_string('templateresource',      'local_uploadnotification', $substitutions) . "\n",
            'html' => get_string('templateresource_html', 'local_uploadnotification', $substitutions) . "\n"
        );

        return $context;
    }

    /**
     * @override \local_uploadnotification_model
     */
    public function model_accessors() {
        return array(
            'notificationid',
            'action',
            'visible',
            'courseid',
            'coursefullname',
            'modulename',
            'filename',
            'moodleid',
        );
    }

    /**
     * Build a notification object from a digest.
     *
     * @param stdClass $notificationdigest A notfication digest object from the DML API.
     *
     * @return \local_uploadnotification_notification A notification object.
     */
    public static function from_digest($notificationdigest) {
        return new static($notificationdigest->notificationid, $notificationdigest->action, $notificationdigest->visible,
                          $notificationdigest->courseid, $notificationdigest->coursefullname,
                          $notificationdigest->modulename,
                          $notificationdigest->filename, $notificationdigest->moodleid);
    }
}
