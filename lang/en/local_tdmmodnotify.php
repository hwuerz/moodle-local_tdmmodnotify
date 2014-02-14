<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 */

// Module metadata
$string['pluginname'] = 'TDM: module modification notification';

// Created notification
$string['templatecreatedsubj'] = 'New resource';
$string['templatecreatedbody'] = 'Hi {$a->userfirstname},

A new resource has been uploaded the "{$a->coursefullname}" course.

You can view it here:
    {$a->sectionurl}

Regards,
{$a->siteadmin}';

// Updated notification
$string['templateupdatedsubj'] = 'Updated resource';
$string['templateupdatedbody'] = 'Hi {$a->userfirstname},

A resource has been modified in the "{$a->coursefullname}" course.

You can view it here:
    {$a->sectionurl}

Regards,
{$a->siteadmin}';
