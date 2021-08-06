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
 * Display the zip download link.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/filelib.php');

$cmid = required_param('cmid', PARAM_INT);
$file = required_param('file', PARAM_FILE);
$size = required_param('size', PARAM_INT);
$download = optional_param('download', 0, PARAM_BOOL);

[$course, $cm] = get_course_and_cm_from_cmid($cmid);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$pageurl = new moodle_url('/report/embedquestion/responsedownload.php',
        ['cmid' => $cmid, 'file' => $file, 'size' => $size]);
$reporturl = new moodle_url('/report/embedquestion/activity.php', ['cmid' => $cmid]);
$pagetitle = get_string('downloadresponse_title', 'report_embedquestion');
$renderer = $PAGE->get_renderer('report_embedquestion');

if ($download) {
    $filepath = $CFG->dataroot . '/cache/report_embedquestion/download/' . $file . '.zip';
    $filename = $file . '.zip';
    if (file_exists($filepath)) {
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: ');
        header('Pragma: ');
        readfile_accel($filepath, 'application/zip', true);
        redirect($pageurl);
    } else {
        throw new moodle_exception('downloadresponse_error', 'report_embedquestion');
    }
}

$PAGE->navbar->add(get_string('activityreporttitle', 'report_embedquestion',
        $context->get_context_name(false)), $reporturl);
$PAGE->navbar->add(get_string('downloadresponse_title', 'report_embedquestion'));

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);

echo $renderer->header();
echo $renderer->download_heading($pagetitle);
echo $renderer->render_responses_download_links(['cmid' => $cmid, 'file' => $file, 'size' => $size]);
echo $renderer->render_back_to_report_link($reporturl);
echo $renderer->footer();
