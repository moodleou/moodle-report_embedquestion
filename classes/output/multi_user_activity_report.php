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
 * Renderable for the Embedded questions progress report for for staff
 * who can see data for several users at activity level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\output;
use report_embedquestion\latest_attempt_table;
use report_embedquestion\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable for the Embedded questions progress report for for staff
 * who can see data for several users at activity level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 */
class multi_user_activity_report implements \renderable {

    /** @var \stdClass the course containing the activity we are showing the report for. */
    protected $course;

    /** @var \cm_info the activity we are showing the report for. */
    protected $cm;

    /** @var int the id of the group we are showing the report for. 0 for all users. */
    protected $groupid;

    /** @var \context the activity context. */
    protected $context;

    /** @var int number of rows in the progress report table per page. */
    protected $pagesize = 10;

    /**
     * Constructor.
     *
     * @param \stdClass $course the course containing the activity we are showing the report for.
     * @param \cm_info $cm the activity we are showing the report for.
     * @param int $groupid the id of the group we are showing the report for. 0 for all users.
     * @param \context $context the activity context.
     */
    public function __construct(\stdClass $course, \cm_info $cm, int $groupid, \context $context) {
        $this->course = $course;
        $this->cm = $cm;
        $this->groupid = $groupid;
        $this->context = $context;
    }

    /**
     * Get a suitable page title.
     *
     * @return string the title.
     */
    public function get_title(): string {
        return get_string('activityreporttitle', 'report_embedquestion',
                $this->context->get_context_name(false, false));

    }

    /**
     * Display the report.
     */
    public function display_content() {
        $table = new latest_attempt_table($this->context, $this->course->id, $this->groupid, $this->cm);
        $table->setup();
        $table->out($this->pagesize, true, null);
    }
}
