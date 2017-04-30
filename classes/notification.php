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
 * Notification.
 */
class local_tdmmodnotify_notification extends local_tdmmodnotify_model {
    /**
     * Action.
     *
     * One of the LOCAL_TDMMODNOTIFY_ACTION_* constants.
     *
     * @var integer
     */
    protected $action;
    
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
     * Parent section number (within the scope of the course).
     *
     * @var integer
     */
    protected $coursesectionid;

    /**
     * Course section name.
     *
     * @var string
     */
    protected $coursesectionname;

    /**
     * Module name.
     *
     * @var string
     */
    protected $modulename;

    /**
     * Initialiser.
     *
     * @param integer $action            Action.
     * @param integer $courseid          Course ID.
     * @param string  $coursefullname    Course full name.
     * @param integer $coursesectionid   Parent section number (within the scope of the course).
     * @param string  $coursesectionname Course section name.
     * @param string  $modulename        Module name.
     */
    public function __construct($action, $courseid, $coursefullname, $coursesectionid, $coursesectionname, $modulename) {
        $this->action            = $action;
        $this->courseid          = $courseid;
        $this->coursefullname    = $coursefullname;
        $this->coursesectionid   = $coursesectionid;
        $this->coursesectionname = $coursesectionname;
        $this->modulename        = $modulename;
    }

    /**
     * Build the notification content.
     *
     * @param stdClass $substitutions The string substitions to be passed to the location API when generating the
     *                                content. This object must include a moodle_url object in its baseurl property,
     *                                else a fatal error will be raised.
     *
     * @return string The notification content.
     */
    public function build_content($substitutions) {
        switch ($this->action) {
            case LOCAL_TDMMODNOTIFY_ACTION_CREATED:
                $action = 'created';
                break;

            case LOCAL_TDMMODNOTIFY_ACTION_UPDATED:
                $action = 'updated';
                break;

            default:
                throw new coding_exception("Invalid action '{$this->action}'");
        }

        $substitutions->baseurl->param('id', $this->courseid);
        $substitutions->baseurl->set_anchor("section-{$this->coursesectionid}");
        $substitutions->url = $substitutions->baseurl->out();

        foreach ($this->model_accessors() as $attribute) {
            $substitutions->{$attribute} = $this->{$attribute};
        }
        $substitutions->action = get_string("action{$action}", 'local_tdmmodnotify');

        return get_string('templateresource', 'local_tdmmodnotify', $substitutions) . "\n";
    }

    /**
     * @override \local_tdmmodnotify_model
     */
    public function model_accessors() {
        return array(
            'action',
            'courseid',
            'coursefullname',
            'coursesectionid',
            'coursesectionname',
            'modulename',
        );
    }

    /**
     * Build a notification object from a digest.
     *
     * @param stdClass $notificationdigest A notfication digest object from the DML API.
     *
     * @return \local_tdmmodnotify_notification A notification object.
     */
    public static function from_digest($notificationdigest) {
        return new static($notificationdigest->action,
                          $notificationdigest->courseid, $notificationdigest->coursefullname,
                          $notificationdigest->coursesectionid, $notificationdigest->coursesectionname,
                          $notificationdigest->modulename);
    }
}
