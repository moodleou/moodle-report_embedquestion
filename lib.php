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
 * Embedded questions progress report callback functions to integrate with Moodle.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Standard callback to add the log report to the course-level navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 */
function report_embedquestion_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if (has_any_capability(['report/embedquestion:viewmyprogress', 'report/embedquestion:viewallprogress'], $context)) {
        // TODO, also check if there are any embedded questions here.
        $navigation->add(get_string('pluginname', 'report_embedquestion'),
                new moodle_url('/report/embedquestion/index.php', ['courseid' => $course->id]),
                navigation_node::TYPE_SETTING, null, 'embedquestionreport', new pix_icon('i/report', ''));
    }
}

/**
 * Standard callback to add the log report to the activity-level navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param cm_info $cm
 */
function report_embedquestion_extend_navigation_module(navigation_node $navigation, cm_info $cm) {
    if (has_any_capability(['report/embedquestion:viewmyprogress', 'report/embedquestion:viewallprogress'],
            context_module::instance($cm->id))) {
        // TODO, also check if there are any embedded questions here.
        $navigation->add(get_string('pluginname', 'report_embedquestion'),
                new moodle_url('/report/embedquestion/activity.php', ['cmid' => $cm->id]),
                navigation_node::TYPE_SETTING, null, 'embedquestionreport');
    }
}

/**
 * Standard callback to return a list of page types, used by the blocks UI.
 *
 * @param string $pagetype current page type
 * @param context $parentcontext Block's parent context
 * @param context $currentcontext Current context of block
 * @return array a list of page types
 */
function report_embedquestion_page_type_list(string $pagetype, context $parentcontext, context $currentcontext): array {
    return [
        '*'                             => get_string('page-x', 'pagetype'),
        'report-*'                      => get_string('page-report-x', 'pagetype'),
        'report-embedquestion-*'        => get_string('page-report-embedquestion-x', 'report_embedquestion'),
        'report-embedquestion-activity' => get_string('page-report-embedquestion-activity', 'report_embedquestion'),
        'report-embedquestion-index'    => get_string('page-report-embedquestion-index', 'report_embedquestion'),
    ];
}
