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
 * Renderable for the Embedded questions progress report for one user at course level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\output;
use report_embedquestion\latest_attempt_table;
use report_embedquestion\attempt_summary_table;
use html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable for the Embedded questions progress report for one user at course level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 */
class single_user_course_report {

    /** @var int the id of the course we are showing the report for. */
    protected $courseid;

    /** @var int the id of the user we are showing the report for. */
    protected $userid;

    /** @var \context the course context. */
    protected $context;

    /** @var int number of rows in the progress report table per page. */
    protected $pagesize = 10;

    /**
     * Constructor.
     *
     * @param int $courseid the id of the course we are showing the report for.
     * @param int $userid the id of the user we are showing the report for.
     */
    public function __construct(int $courseid, int $userid, \context $context) {
        $this->courseid = $courseid;
        $this->userid = $userid;
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

    /**
     * Display the report.
     */
    public function display_content() {
        $usageid = optional_param('usageid', 0, PARAM_INT);
        $useinitialsbar = false;
        if ($usageid > 0) {
            $table = new attempt_summary_table($this->context, $this->courseid, 0, null, $this->userid, $usageid);
        } else {
            $table = new latest_attempt_table($this->context, $this->courseid, 0, null, null, null, $this->userid);
        }
        $table->setup();

        // Display the attempt summary for a question attempted by a user.
        if ($usageid > 0) {
            $attempt = end($table->rawdata);
            // Diaplay heading content.
            echo \report_embedquestion\utils::get_embed_location_summary($this->courseid, $attempt);
        }
        // Display the table.
        $table->out($this->pagesize, $useinitialsbar, '');
    }
}
