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
 * Embedded questions progress report database upgrade script.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Embedded questions progress report database upgrade function.
 *
 * @param string $oldversion the version we are upgrading from.
 * @return bool true for success.
 */
function xmldb_report_embedquestion_upgrade(int $oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019082101) {

        // Define table report_embedquestion_attempt to be created.
        $table = new xmldb_table('report_embedquestion_attempt');

        // Adding fields to table report_embedquestion_attempt.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('embedcode', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('questionusageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pagename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pageurl', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_embedquestion_attempt.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, ['contextid'], 'context', ['id']);
        $table->add_key('questionusageid', XMLDB_KEY_FOREIGN,
                ['questionusageid'], 'question_usages', ['id']);

        // Adding indexes to table report_embedquestion_attempt.
        $table->add_index('userid-contextid-embedcode', XMLDB_INDEX_UNIQUE,
                ['userid', 'contextid', 'embedcode']);

        // Conditionally launch create table for report_embedquestion_attempt.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Embedquestion savepoint reached.
        upgrade_plugin_savepoint(true, 2019082101, 'report', 'embedquestion');
    }

    if ($oldversion < 2019090900) {

        // Define index userid-contextid-embedcode (unique) to be dropped form report_embedquestion_attempt.
        $table = new xmldb_table('report_embedquestion_attempt');
        $index = new xmldb_index('userid-contextid-embedcode', XMLDB_INDEX_UNIQUE, ['userid', 'contextid', 'embedcode']);

        // Conditionally launch drop index userid-contextid-embedcode.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Embedquestion savepoint reached.
        upgrade_plugin_savepoint(true, 2019090900, 'report', 'embedquestion');
    }

    if ($oldversion < 2019090901) {

        // Rename field embedid on table report_embedquestion_attempt to NEWNAMEGOESHERE.
        $table = new xmldb_table('report_embedquestion_attempt');
        $field = new xmldb_field('embedcode', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'contextid');

        // Launch rename field embedid.
        $dbman->rename_field($table, $field, 'embedid');

        // Embedquestion savepoint reached.
        upgrade_plugin_savepoint(true, 2019090901, 'report', 'embedquestion');
    }

    if ($oldversion < 2019090902) {

        // Define index userid-contextid-embedid (unique) to be added to report_embedquestion_attempt.
        $table = new xmldb_table('report_embedquestion_attempt');
        $index = new xmldb_index('userid-contextid-embedid', XMLDB_INDEX_UNIQUE, ['userid', 'contextid', 'embedid']);

        // Conditionally launch add index userid-contextid-embedid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Embedquestion savepoint reached.
        upgrade_plugin_savepoint(true, 2019090902, 'report', 'embedquestion');
    }

    if ($oldversion < 2019091600) {

        // Define key questionusageid (foreign) to be dropped form report_embedquestion_attempt.
        $table = new xmldb_table('report_embedquestion_attempt');
        $key = new xmldb_key('questionusageid', XMLDB_KEY_FOREIGN, ['questionusageid'], 'question_usages', ['id']);

        // Launch drop key questionusageid.
        $dbman->drop_key($table, $key);

        // Embedquestion savepoint reached.
        upgrade_plugin_savepoint(true, 2019091600, 'report', 'embedquestion');
    }

    if ($oldversion < 2019091601) {

        // Define key questionusageid (foreign-unique) to be added to report_embedquestion_attempt.
        $table = new xmldb_table('report_embedquestion_attempt');
        $key = new xmldb_key('questionusageid', XMLDB_KEY_FOREIGN_UNIQUE, ['questionusageid'], 'question_usages', ['id']);

        // Launch add key questionusageid.
        $dbman->add_key($table, $key);

        // Embedquestion savepoint reached.
        upgrade_plugin_savepoint(true, 2019091601, 'report', 'embedquestion');
    }

    return true;
}

