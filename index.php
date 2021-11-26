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
 * Display the Embedded questions progress report for a course.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use report_embedquestion\event\course_report_viewed;
use report_embedquestion\utils;
use report_embedquestion\local\report\progress_report;

$courseid = required_param('courseid', PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$usageid = optional_param('usageid', 0, PARAM_INT);
$download = optional_param('download', null, PARAM_RAW);

require_login($courseid);
$context = context_course::instance($courseid);

$params = ['courseid' => $courseid];
if ($groupid !== 0) {
    $params['groupid'] = $groupid;
}
if ($userid !== 0) {
    $params['userid'] = $userid;
}
$PAGE->set_url('/report/embedquestion/index.php', $params);
$PAGE->set_pagelayout('report');

$userid = utils::require_report_permissions($context, $userid);
utils::validate_usageid($usageid, $userid);

// Log the view.
course_report_viewed::create(['context' => $context,
        'relateduserid' => $userid, 'other' => ['groupid' => $groupid]])->trigger();

// Create the right sort of report.
$showonly = '';
$report = progress_report::make(get_course($courseid), $context, null, (bool) $usageid);
if ($userid) {
    [$user, $info] = utils::get_user_details($userid, $context);
    $showonly = utils::get_user_display($user, $info);
    $report->single_report($userid);
}
$report->init();

// Set navbar in the report.
utils::set_report_navbar($report->get_title(), $context, $showonly);

// Display the report.
if (!$download) {
    $title = $report->get_title();
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    /** @var report_embedquestion\output\renderer $renderer */
    $renderer = $PAGE->get_renderer('report_embedquestion');

    echo $OUTPUT->header();
    echo $renderer->report_heading($title);
    if ($userid && has_capability('report/embedquestion:viewallprogress', $context)) {
        echo $renderer->render_show_only_heading($showonly, $report->get_full_url_report());
    }
    $report->display();
    echo $OUTPUT->footer();

} else {
    $report->display();
}
