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
 * Upgrade hook.
 *
 * @package     tool_blocksmanager
 * @category    upgrade
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_tool_blocksmanager_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019122300) {

        // Define table tool_blocksmanager_region to be created.
        $table = new xmldb_table('tool_blocksmanager_region');

        // Adding fields to table tool_blocksmanager_region.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('region', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('categories', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('config', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('delete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('hide', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('add', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('move', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_blocksmanager_region.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table tool_blocksmanager_region.
        $table->add_index('reg', XMLDB_INDEX_NOTUNIQUE, ['region']);

        // Conditionally launch create table for tool_blocksmanager_region.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Blocksmanager savepoint reached.
        upgrade_plugin_savepoint(true, 2019122300, 'tool', 'blocksmanager');
    }

    if ($oldversion < 2019122301) {

        // Define table tool_blocksmanager_block to be created.
        $table = new xmldb_table('tool_blocksmanager_block');

        // Adding fields to table tool_blocksmanager_block.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('region', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null);
        $table->add_field('block', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, null);
        $table->add_field('categories', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('config', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('delete', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('hide', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('move', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_blocksmanager_block.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table tool_blocksmanager_block.
        $table->add_index('block', XMLDB_INDEX_NOTUNIQUE, ['block']);

        // Conditionally launch create table for tool_blocksmanager_block.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Blocksmanager savepoint reached.
        upgrade_plugin_savepoint(true, 2019122301, 'tool', 'blocksmanager');
    }

    if ($oldversion < 2019122701) {

        $table = new xmldb_table('tool_blocksmanager_region');
        $field = new xmldb_field('delete',  XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'remove');
        }

        $table = new xmldb_table('tool_blocksmanager_block');
        $field = new xmldb_field('delete',  XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'remove');
        }

        // Blocksmanager savepoint reached.
        upgrade_plugin_savepoint(true, 2019122701, 'tool', 'blocksmanager');
    }

    return true;
}
