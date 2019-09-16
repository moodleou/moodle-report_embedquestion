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
 * Privacy Subsystem implementation for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use filter_embedquestion\question_options;

defined('MOODLE_INTERNAL') || die();


/**
 * Privacy Subsystem for report_embedquestion.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $items): collection {

        // The table 'report_embedquestion_attempt' stores a record of each set of attempts
        // by a user at a particular embedded question.
        $items->add_database_table('report_embedquestion_attempt', [
                'userid'       => 'privacy:metadata:report_embedquestion_attempt:userid',
                'contextid'    => 'privacy:metadata:report_embedquestion_attempt:contextid',
                'embedid'      => 'privacy:metadata:report_embedquestion_attempt:embedid',
                'pagename'     => 'privacy:metadata:report_embedquestion_attempt:pagename',
                'pageurl'      => 'privacy:metadata:report_embedquestion_attempt:pageurl',
                'timecreated'  => 'privacy:metadata:report_embedquestion_attempt:timecreated',
                'timemodified' => 'privacy:metadata:report_embedquestion_attempt:timemodified',
        ], 'privacy:metadata:report_embedquestion_attempt');

        // This report links to the 'core_question' subsystem for storing the details of
        // what happened during the attempts.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');

        return $items;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $contextlist->add_from_sql("
                SELECT DISTINCT contextid
                  FROM {report_embedquestion_attempt}
                 WHERE userid = :userid
                ", ['userid' => $userid]);

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $userlist->add_from_sql('userid', "
                SELECT DISTINCT userid
                  FROM {report_embedquestion_attempt}
                 WHERE contextid = :contextid
                ", ['contextid' => $userlist->get_context()->id]);
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $contextids = $contextlist->get_contextids();
        if (empty($contextids)) {
            return;
        }
        list($contextsql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        $params['userid'] = $contextlist->get_user()->id;

        $attempts = $DB->get_recordset_sql("SELECT *
                FROM {report_embedquestion_attempt}
               WHERE contextid $contextsql
                 AND userid = :userid
                ", $params);
        foreach ($attempts as $attempt) {
            // Prepare the context and sub-context.
            $context = \context::instance_by_id($attempt->contextid);
            $subcontext = [
                get_string('attempts', 'report_embedquestion'),
                get_string('attemptsatquestion', 'report_embedquestion', $attempt->embedid),
            ];

            // Store the overall data about the attempt.
            $data = (object) [
                'pagename'     => $attempt->pagename,
                'pageurl'      => (new \moodle_url($attempt->pageurl))->out(false),
                'timecreated'  => transform::datetime($attempt->timecreated),
                'timemodified' => transform::datetime($attempt->timemodified),
            ];
            writer::with_context($context)->export_data($subcontext, $data);

            // Export the related question attempt data.
            \core_question\privacy\provider::export_question_usage($attempt->userid,
                    $context, $subcontext, $attempt->questionusageid, new question_options(), true);
        }
        $attempts->close();
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $attempts = $DB->get_records_menu('report_embedquestion_attempt',
                ['contextid' => $context->id], '', 'id, questionusageid');

        \question_engine::delete_questions_usage_by_activities(new \qubaid_list($attempts));
        $DB->delete_records_list('report_embedquestion_attempt', 'id', array_keys($attempts));
        $transaction->allow_commit();
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $contextids = $contextlist->get_contextids();
        if (empty($contextids)) {
            return;
        }
        [$contextsql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);

        $user = $contextlist->get_user();
        $params['userid'] = $user->id;

        $transaction = $DB->start_delegated_transaction();
        $attempts = $DB->get_records_sql_menu("
                SELECT id, questionusageid
                  FROM {report_embedquestion_attempt}
                 WHERE userid = :userid
                   AND contextid $contextsql",
                $params);

        \question_engine::delete_questions_usage_by_activities(new \qubaid_list($attempts));
        $DB->delete_records_list('report_embedquestion_attempt', 'id', array_keys($attempts));
        $transaction->allow_commit();
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        [$useridsql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $context = $userlist->get_context();
        $params['contextid'] = $context->id;

        $transaction = $DB->start_delegated_transaction();
        $attempts = $DB->get_records_sql_menu("
                SELECT id, questionusageid
                  FROM {report_embedquestion_attempt}
                 WHERE userid $useridsql
                   AND contextid = :contextid",
                $params);

        \question_engine::delete_questions_usage_by_activities(new \qubaid_list($attempts));
        $DB->delete_records_list('report_embedquestion_attempt', 'id', array_keys($attempts));
        $transaction->allow_commit();
    }
}
