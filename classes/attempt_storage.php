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

namespace report_embedquestion;

use filter_embedquestion\embed_id;
use filter_embedquestion\embed_location;

/**
 * Deals with finding or creating the usages for filter_embedquestion so that
 * the attempts are kept long-term.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_storage extends \filter_embedquestion\attempt_storage {

    /**
     * Find an existing attempt for the given embed id and location.
     *
     * @param embed_id $embedid The embed id.
     * @param embed_location $embedlocation The embed location.
     * @param \stdClass $user The user object.
     * @return array An array containing the question usage and the last slot number, or null if not found.
     */
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

    /**
     * Update the time modified for the given question usage id.
     *
     * @param int $qubaid The question usage id.
     */
    public function update_timemodified(int $qubaid): void {
        global $DB;

        $DB->set_field('report_embedquestion_attempt', 'timemodified', time(),
                ['questionusageid' => $qubaid]);
    }

    /**
     * Create a new question usage for the given embed id and location.
     *
     * @param embed_id $embedid The embed id.
     * @param embed_location $embedlocation The embed location.
     * @param \stdClass $user The user object.
     * @return \question_usage_by_activity The new question usage.
     */
    public function make_new_usage(embed_id $embedid, embed_location $embedlocation,
            \stdClass $user): \question_usage_by_activity {
        $quba = \question_engine::make_questions_usage_by_activity(
                'report_embedquestion', $embedlocation->context);
        return $quba;
    }

    /**
     * Save a new usage for the given embed id and location.
     *
     * @param \question_usage_by_activity $quba The question usage.
     * @param embed_id $embedid The embed id.
     * @param embed_location $embedlocation The embed location.
     * @param \stdClass $user The user object.
     */
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
        // Cache invalidation.
        attempt_tracker::user_attempts_changed($embedlocation->context);
    }

    /**
     * Verify that the usage belongs to the current user and context.
     *
     * @param \question_usage_by_activity $quba The question usage.
     * @param \context $context The context of the usage.
     * @throws \moodle_exception If the usage does not belong to the current user or context.
     */
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

    /**
     * Delete the attempt associated with the given question usage.
     *
     * @param \question_usage_by_activity $quba The question usage to delete.
     */
    public function delete_attempt(\question_usage_by_activity $quba) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        \question_engine::delete_questions_usage_by_activity($quba->get_id());
        $contextid = $DB->get_field('report_embedquestion_attempt', 'contextid',
                ['questionusageid' => $quba->get_id()]);
        $DB->delete_records('report_embedquestion_attempt', ['questionusageid' => $quba->get_id()]);
        if ($contextid) {
            attempt_tracker::user_attempts_changed(\context::instance_by_id($contextid));
        }
        $transaction->allow_commit();
    }
}
