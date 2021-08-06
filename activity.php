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
 * Display the Embedded questions progress report for an activity.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use \report_embedquestion\event\activity_report_viewed,
    \report_embedquestion\utils;
use report_embedquestion\local\report\progress_report;

$cmid = required_param('cmid', PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$usageid = optional_param('usageid', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$params = ['cmid' => $cm->id];
if ($groupid !== 0) {
    $params['groupid'] = $groupid;
}
if ($userid !== 0) {
    $params['userid'] = $userid;
}
$PAGE->set_url('/report/embedquestion/activity.php', $params);
$PAGE->set_pagelayout('report');

$userid = utils::require_report_permissions($context, $userid);
utils::validate_usageid($usageid, $userid);

// Log the view.
activity_report_viewed::create(['context' => $context,
        'relateduserid' => $userid, 'other' => ['groupid' => $groupid]])->trigger();

$report = progress_report::make($course, $context, $cm, $usageid ? true : false);
// Create the right sort of report.
if ($userid) {
    [$user, $info] = utils::get_user_details($userid, $context);
    $showonly = utils::get_user_display($user, $info);
    utils::set_report_navbar($report->get_title(), $context, $showonly);
    $report->single_report($userid);
}
$report->init();
$renderer = $PAGE->get_renderer('report_embedquestion');
// Display the report.
$download = optional_param('download', null, PARAM_RAW);
if (!$download) {
    $title = $report->get_title();
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    $output = $OUTPUT->header();
    $output .= $renderer->report_heading($title);
    if ($userid && has_capability('report/embedquestion:viewallprogress', $context)) {
        $output .= $renderer->render_show_only_heading($showonly, $report->get_full_url_report());
    }
    ob_start();
    $report->display();
    $output .= ob_get_contents();
    ob_end_clean();
    $output .= $OUTPUT->footer();

    echo $output;
} else {
    $report->display();
}
