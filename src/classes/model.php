<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
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
