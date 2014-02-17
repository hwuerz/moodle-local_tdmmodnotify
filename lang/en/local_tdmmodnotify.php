<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 */

defined('MOODLE_INTERNAL') || die;

// Module metadata
$string['pluginname'] = 'TDM: module modification notification';

// Created notification
$string['templatesubject'] = 'Resource updates in your courses';
$string['templatemessage'] = 'Hi {$a->firstname},

The following activities resources have changed in courses you\'re enrolled in.

{$a->notifications}

{$a->signoff}';
$string['templateresource'] = '* "{$a->modulename}" in "{$a->coursefullname}" was {$a->action}: {$a->url}';
$string['actioncreated']    = 'created';
$string['actionupdated']    = 'updated';
