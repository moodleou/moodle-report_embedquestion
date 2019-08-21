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
 * Renderable for the Embedded questions progress report for staff
 * who can see data for several users at course level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\output;
defined('MOODLE_INTERNAL') || die();


/**
 * Renderable for the Embedded questions progress report for staff
 * who can see data for several users at course level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 */
class multi_user_course_report implements \renderable, \templatable {

    /** @var int the id of the course we are showing the report for. */
    protected $courseid;

    /** @var int the id of the group we are showing the report for. 0 for all users. */
    protected $groupid;

    /** @var \context the course context. */
    protected $context;

    /**
     * Constructor.
     *
     * @param int $courseid the id of the course we are showing the report for.
     * @param int $groupid the id of the group we are showing the report for. 0 for all users.
     */
    public function __construct(int $courseid, int $groupid, \context $context) {
        $this->courseid = $courseid;
        $this->groupid = $groupid;
        $this->context = $context;
    }

    /**
     * Get a suitable page title.
     *
     * @return string the title.
     */
    public function get_title(): string {
        return get_string('coursereporttitle', 'report_embedquestion',
                $this->context->get_context_name(false, false));

    }

    public function export_for_template(\renderer_base $output): array {
        return [
        ];
    }
}
