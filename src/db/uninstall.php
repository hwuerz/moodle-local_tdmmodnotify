<?php

/**
 * TDM: Module modification notification.
 *
 * @author Luke Carrier <luke@tdm.co>
 * @copyright (c) 2014 The Development Manager Ltd
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Uninstall the plugin.
 *
 * @return boolean Always true (indicating success).
 */
function xmldb_local_tdmmodnotify_uninstall() {
    global $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('local_tdmmodnotify');
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }

    return true;
}
