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

use \report_embedquestion\event\course_report_viewed,
    \report_embedquestion\output\multi_user_course_report,
    \report_embedquestion\output\single_user_course_report;

$courseid = required_param('courseid', PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
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

if (!has_capability('report/embedquestion:viewallprogress', $context)) {
    require_capability('report/embedquestion:viewmyprogress', $context);
    if ($userid === 0) {
        $userid = $USER->id;
    }
    if ($userid != $USER->id) {
        // At this point, we know this next check will fail, but it generates the error message we want.
        require_capability('report/viewallprogress:viewmyprogress', $context);
    }
}

// Log the view.
course_report_viewed::create(['context' => $context,
        'relateduserid' => $userid, 'other' => ['groupid' => $groupid]])->trigger();

// Create the right sort of report.
if ($userid) {
    $report = new single_user_course_report($courseid, $userid, $context);
} else {
    $report = new multi_user_course_report($courseid, $groupid, $context);
}
// Display the report.
if (!$download) {
    $title = $report->get_title();
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
    $report->display_download_content();
    echo $OUTPUT->footer();
} else {
    $report->display_download_content($download);
}
