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
 * Renderable for the Embedded questions progress report for one user at activity level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\output;
defined('MOODLE_INTERNAL') || die();


/**
 * Renderable for the Embedded questions progress report for one user at activity level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 */
class single_user_activity_report implements \renderable, \templatable {

    /** @var \stdClass the course containing the activity we are showing the report for. */
    protected $course;

    /** @var \cm_info the activity we are showing the report for. */
    protected $cm;

    /** @var int the id of the user we are showing the report for. */
    protected $userid;

    /** @var \context the activity context. */
    protected $context;

    /**
     * Constructor.
     *
     * @param \stdClass $course the course containing the activity we are showing the report for.
     * @param \cm_info $cm the activity we are showing the report for.
     * @param int $userid the id of the user we are showing the report for.
     * @param \context $context the activity context.
     */
    public function __construct(\stdClass $course, \cm_info $cm, int $userid, \context $context) {
        $this->course = $course;
        $this->cm = $cm;
        $this->userid = $userid;
        $this->context = $context;
    }

    /**
     * Get a suitable page title.
     *
     * @return string the title.
     */
    public function get_title(): string {
        return get_string('activityreporttitle', 'report_embedquestion',
                $this->cm->get_formatted_name());

    }

    public function export_for_template(\renderer_base $output): array {
        return [
        ];
    }
}
