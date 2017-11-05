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

require_once(dirname(__FILE__) . '/definitions.php');
require_once(dirname(__FILE__) . '/../changeloglib/classes/pdftotext.php');

if ($hassiteconfig) {

    // Create the new settings page.
    $settings_name = get_string('pluginname', LOCAL_UPLOADNOTIFICATION_FULL_NAME);
    $settings = new admin_settingpage( LOCAL_UPLOADNOTIFICATION_FULL_NAME, $settings_name);

    // Create.
    $ADMIN->add( 'localplugins', $settings );

    $settings->add(new admin_setting_configcheckbox(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/allow_mail',
        new lang_string('settings_allow_mail', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_allow_mail_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        1));

    $settings->add(new admin_setting_configtime(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/digest_hour',
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/digest_minute',
        new lang_string('settings_digest', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_digest_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        array('h' => 18, 'm' => 0)
    ));

    $settings->add(new admin_setting_configtext(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/max_mail_filesize',
        new lang_string('settings_max_mail_filesize', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_max_mail_filesize_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        100000,  '/^[0-9]+$/'));

    $settings->add(new admin_setting_configtext(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/max_mails_for_resource',
        new lang_string('settings_max_mails_for_resource', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_max_mails_for_resource_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        800, '/^[0-9]+$/'));

    $settings->add(new admin_setting_configcheckbox(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/allow_changelog',
        new lang_string('settings_allow_changelog', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_allow_changelog_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        1));

    $settings->add(new admin_setting_configtext(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/max_diff_filesize',
        new lang_string('settings_max_diff_filesize', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_max_diff_filesize_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        100, '/^[0-9]+$/'));

    if (!local_changeloglib_pdftotext::is_installed()) {
        $settings->add(new admin_setting_heading(
            LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/diff_not_available',
            "Warning",
            new lang_string('settings_diff_not_available', LOCAL_UPLOADNOTIFICATION_FULL_NAME)));
    }

    $settings->add(new admin_setting_configcheckbox(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/enable_changelog_by_default',
        new lang_string('settings_enable_changelog_by_default', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_enable_changelog_by_default_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        0));

    $settings->add(new admin_setting_configcheckbox(
        LOCAL_UPLOADNOTIFICATION_FULL_NAME . '/enable_diff_by_default',
        new lang_string('settings_enable_diff_by_default', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        new lang_string('settings_enable_diff_by_default_help', LOCAL_UPLOADNOTIFICATION_FULL_NAME),
        0));
}
