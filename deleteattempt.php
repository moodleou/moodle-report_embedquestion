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
 * Delete an embedded question attempt for the embedquestion report.
 *
 * @package   report_embedquestion
 * @copyright 2025 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_embedquestion\utils;

require(__DIR__ . '/../../config.php');
global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/engine/datalib.php');

global $USER, $DB;

$cmid = optional_param('cmid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$attemptid = optional_param('attemptid', null, PARAM_INT);
$userid = optional_param('userid', null, PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_URL);
$qubaid = optional_param('qubaid', null, PARAM_INT);

// Validate required parameters.
if (is_null($cmid) && is_null($courseid)) {
    throw new moodle_exception('missingparam', '', '', 'courseid or cmid');
}

if ($cmid) {
    [$course, $cm] = get_course_and_cm_from_cmid($cmid);
    $context = context_module::instance($cm->id);
    $url = new moodle_url(
        '/report/embedquestion/deleteattempt.php',
        ['cmid' => $cm->id, 'attemptid' => $attemptid]
    );
} else {
    $course = get_course($courseid);
    $context = context_course::instance($courseid);
    $url = new moodle_url(
        '/report/embedquestion/deleteattempt.php',
        ['courseid' => $courseid, 'attemptid' => $attemptid]
    );
}
require_login($courseid, false);
// Capability checks and attempt deletion.
if (
    has_capability('report/embedquestion:deleteanyattempt', $context) ||
    (has_capability('report/embedquestion:deletemyattempt', $context) && $USER->id == $userid)
) {
    $placeholderquestionid = get_config('report_embedquestion', 'deletedquestionplaceholderid');
    utils::delete_specific_attempt($attemptid, $qubaid, $placeholderquestionid);
    redirect($returnurl);
} else {
    throw new required_capability_exception(
        $context,
        'report/embedquestion:deletemyattempt',
        'nopermissions',
        ''
    );
}
