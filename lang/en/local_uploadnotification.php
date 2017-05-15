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
 * Upload notification.
 *
 * @package   local_uploadnotification
 * @author    Luke Carrier <luke@tdm.co>, Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2014 The Development Manager Ltd, 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Module metadata
$string['pluginname'] = 'Upload notification';

// Settings
$string['heading'] = 'Upload notification settings';

// Created notification
$string['templatesubject'] = 'Resource updates in your courses';
$string['templatemessage'] = 'Hi {$a->firstname},

The following activities resources have changed in courses you\'re enrolled in.

{$a->notifications}

{$a->signoff}';
$string['templatemessage_html'] = 'Hi {$a->firstname},<br><br>

The following activities resources have changed in courses you\'re enrolled in.<br><br>

{$a->notifications}<br><br>

{$a->signoff}';
//$string['templateresource'] = '* "{$a->modulename}" in "{$a->coursefullname}" was {$a->action}: {$a->url}';
$string['templateresource']      = '* "{$a->filename}" in "{$a->coursefullname}" ({$a->url_course}) was {$a->action}: {$a->url_file} ';
$string['templateresource_html'] = '<li><a href="{$a->url_file}">{$a->filename}</a> in <a href="{$a->url_course}">{$a->coursefullname}</a> was {$a->action}</li>';
$string['actioncreated']    = 'created';
$string['actionupdated']    = 'updated';

// Capabilities
$string['uploadnotification:receivedigest'] = 'Receive course modification digest notification';

// Message providers
$string['messageprovider:digest'] = 'Course modification digest notification';


// Settings
$string['setting_enable_plugin'] = 'Enable Mail delivery by this plugin';
