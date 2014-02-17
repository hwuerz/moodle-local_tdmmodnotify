<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

$plugin->component = 'local_tdmmodnotify';

// Emails are sent daily unless we're in debug mode
$plugin->cron = $CFG->debugdeveloper ? 1 : 86400;

// Version format:  YYYYMMDDXX
$plugin->version  = 2014021400;
$plugin->requires = 2013111800;
