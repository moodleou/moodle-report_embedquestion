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
 * Deals with finding or creating the usages for filter_embedquestion so that
 * the attempts are kept long-term.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion;
use filter_embedquestion\embed_id;
use filter_embedquestion\embed_location;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents the attempt at one embedded question.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_storage extends \filter_embedquestion\attempt_storage {

    public function find_existing_attempt(embed_id $embedid, embed_location $embedlocation,
            \stdClass $user): array {
        global $DB;

        $attemptinfo = $DB->get_record('report_embedquestion_attempt',
                ['userid' => $user->id, 'contextid' => $embedlocation->context->id,
                        'embedid' => (string) $embedid]);

        if (!$attemptinfo) {
            return [null, 0];
        }

        $quba = \question_engine::load_questions_usage_by_activity($attemptinfo->questionusageid);
        $allslots = $quba->get_slots();
        return [$quba, end($allslots)];
    }

    public function update_timemodified(int $qubaid): void {
        global $DB;

        $DB->set_field('report_embedquestion_attempt', 'timemodified', time(),
                ['questionusageid' => $qubaid]);
    }

    public function make_new_usage(embed_id $embedid, embed_location $embedlocation,
            \stdClass $user): \question_usage_by_activity {
        $quba = \question_engine::make_questions_usage_by_activity(
                'report_embedquestion', $embedlocation->context);
        return $quba;
    }

    public function new_usage_saved(\question_usage_by_activity $quba,
            embed_id $embedid, embed_location $embedlocation, \stdClass $user): void {
        global $DB;

        $now = time();
        $attemptinfo = [
                'userid' => $user->id,
                'contextid' => $embedlocation->context->id,
                'embedid' => (string) $embedid,
                'questionusageid' => $quba->get_id(),
                'pagename' => $embedlocation->pagetitle,
                'pageurl' => $embedlocation->pageurl->out_as_local_url(false),
                'timecreated' => $now,
                'timemodified' => $now,
        ];
        $DB->insert_record('report_embedquestion_attempt', (object) $attemptinfo);
    }

    public function verify_usage(\question_usage_by_activity $quba, \context $context): void {
        global $DB, $USER;

        if ($quba->get_owning_component() != 'report_embedquestion') {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }

        $attemptinfo = $DB->get_record('report_embedquestion_attempt',
                ['questionusageid' => $quba->get_id()], '*', MUST_EXIST);

        if ($attemptinfo->contextid != $quba->get_owning_context()->id) {
            throw new \moodle_exception('notyourattempt', 'filter_embedquestion');
        }
        if ($USER->id !== $attemptinfo->userid) {
            require_capability('report/embedquestion:viewallprogress', $context);
        }
    }

    public function delete_attempt(\question_usage_by_activity $quba) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        \question_engine::delete_questions_usage_by_activity($quba->get_id());
        $DB->delete_records('report_embedquestion_attempt', ['questionusageid' => $quba->get_id()]);
        $transaction->allow_commit();
    }
}
