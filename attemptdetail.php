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
 * This page is a attempt detail of a particular embedquestion attempt.
 *
 * @package   report_embedquestion
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \report_embedquestion\utils;

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/engine/datalib.php');

$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('attempt', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid);
$attemptobj = (new question_engine_data_mapper)->load_question_attempt($attemptid);
$userattemptid = $attemptobj->get_last_step()->get_user_id();

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$pagetitle = get_string('attempt-detail-page', 'report_embedquestion');

$url = new moodle_url('/report/embedquestion/attemptdetail.php',
        ['cmid' => $cm->id,
                'attempt' => $attemptid]);

$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');
$PAGE->navbar->add(get_string('attempt-detail-page', 'report_embedquestion'), $url);

if (!has_capability('report/embedquestion:viewallprogress', $context)) {
    require_capability('report/embedquestion:viewmyprogress', $context);
    if ($userattemptid != $USER->id) {
        // At this point, we know this next check will fail, but it generates the error message we want.
        require_capability('report/viewallprogress:viewmyprogress', $context);
    }
}

$renderer = $PAGE->get_renderer('report_embedquestion');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$sumdata = utils::prepare_summary_attempt_information($course->id, $attemptobj);
$title = utils::get_title($context);

echo $OUTPUT->header();
echo $renderer->render_attempt_navigation();
echo $OUTPUT->heading($title);
echo $renderer->render_attempt_detail($sumdata, $attemptobj);
echo $OUTPUT->footer();
