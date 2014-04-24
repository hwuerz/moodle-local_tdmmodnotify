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

global $CFG;

$plugin->component = 'local_tdmmodnotify';

$plugin->release  = '0.1.0';
$plugin->maturity = MATURITY_ALPHA;

// Emails are sent daily unless we're in debug mode
$plugin->cron = $CFG->debugdeveloper ? 1 : 86400;

// Version format:  YYYYMMDDXX
$plugin->version  = 2014021400;
$plugin->requires = 2013111800;
