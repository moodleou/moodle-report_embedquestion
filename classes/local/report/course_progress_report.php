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
 * Class to represent course level report in the embed question.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\local\report;

use context;
use moodle_url;
use report_embedquestion\utils;
use stdClass;

/**
 * Class to represent course level report in the embed question.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_progress_report extends progress_report {

    /**
     * course_progress_report constructor.
     *
     * @param stdClass $course
     * @param context $context
     */
    public function __construct(stdClass $course, context $context) {
        $this->course = $course;
        $this->context = $context;
    }

    public function get_title(): string {
        return get_string('coursereporttitle', 'report_embedquestion', $this->context->get_context_name(false, false));
    }

    public function get_url_report(): moodle_url {
        return utils::get_url(['courseid' => $this->course->id]);
    }
}
