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
 * Class to represent activity level report in the embed question.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\local\report;

use cm_info;
use context;
use moodle_url;
use report_embedquestion\utils;
use stdClass;

/**
 * Class to represent activity level report in the embed question.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_progress_report extends progress_report {

    /**
     * Constructor.
     *
     * @param stdClass $course
     * @param context $context
     * @param cm_info $cm
     */
    public function __construct(stdClass $course, context $context, cm_info $cm) {
        $this->course = $course;
        $this->context = $context;
        $this->cm = $cm;
    }

    public function get_title(): string {
        return get_string('activityreporttitle', 'report_embedquestion', $this->cm->get_formatted_name());
    }

    public function get_url_report(): moodle_url {
        return utils::get_url(['cmid' => $this->cm->id], 'activity');
    }
}
