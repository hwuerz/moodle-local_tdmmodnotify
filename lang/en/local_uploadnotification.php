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

// Module metadata
$string['pluginname'] = 'Upload notification';

// Settings
$string['heading'] = 'Upload notification settings';

// Created notification
$string['templatesubject'] = 'Resource updates in your courses';
$string['templatemessage'] = 'Hi {$a->firstname},

The following activities resources have changed in courses you\'re enrolled in.

{$a->notifications}

{$a->signoff}

You are receiving this mail because you or a course admin has requested this information. You can edit your preferences under {$a->user_settings}';
$string['templatemessage_html'] = 'Hi {$a->firstname},<br><br>

The following activities resources have changed in courses you\'re enrolled in.

<ul>
{$a->notifications}
</ul>

{$a->signoff}<br><br>

You are receiving this mail because you or a course admin has requested this information. You can edit your preferences under your <a href="{$a->user_settings}">preferences</a>.';
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
$string['setting_max_filesize'] = 'Maximum filesize of mail attachments (in bytes)';
$string['setting_max_mails_for_resource'] = 'Maximum amount of mails with the same attachment';
$string['setting_enable_changelog'] = 'Enable Changelog generation by this plugin';
$string['setting_receive_attachments'] = 'Send email attachments';
$string['settings_no_preferences'] = 'No preferences';
$string['settings_allow'] = 'Allow';
$string['settings_enable'] = 'Enable';
$string['settings_disable'] = 'Disable';
$string['course_settings_link'] = 'Uploadnotification';

$string['deletion_backup_clean_task'] = 'Clean old deletion backups';