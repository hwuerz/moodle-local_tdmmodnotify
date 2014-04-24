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
 * Base model class.
 */
abstract class local_tdmmodnotify_model {
    /**
     * Retrieve an array of accessors.
     *
     * "Accessors" are fields which are publicly readable, but protected within the scope of the class.
     *
     * @return string[] The accessors.
     */
    abstract protected function model_accessors();

    /**
     * Get a given property via its accessor (if permitted).
     *
     * @param string $property The property to retrieve the value for.
     *
     * @return mixed The value.
     *
     * @throws \coding_exception When a property is specified which does not exist within the permitted accessors.
     * @access private
     */
    public function __get($property) {
        if (!in_array($property, $this->model_accessors())) {
            throw new coding_exception("Property '{$property}' does not have an accessor");
        }

        return $this->{$property};
    }
}
