<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
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
