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
use report_embedquestion\latest_attempt_table;
use report_embedquestion\attempt_summary_table;
use report_embedquestion\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable for the Embedded questions progress report for one user at activity level.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 */
class single_user_activity_report {

    /** @var \stdClass the course containing the activity we are showing the report for. */
    protected $course;

    /** @var \cm_info the activity we are showing the report for. */
    protected $cm;

    /** @var int the id of the user we are showing the report for. */
    protected $userid;

    /** @var \context the activity context. */
    protected $context;

    /** @var int number of rows in the progress report table per page. */
    protected $pagesize = 10;

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

    /**
     * Display the report.
     * @throws \coding_exception
     */
    public function display_download_content($download = null) {
        $usageid = optional_param('usageid', 0, PARAM_INT);
        if ($usageid > 0) {
            $table = new attempt_summary_table($this->context, $this->course->id, 0, $this->cm, $this->userid, $usageid);
        } else {
            list ($filterform, $filter) = utils::get_filter_data(utils::get_url(['cmid' => $this->cm->id], 'activity'));
            if (!$download) {
                $table = new latest_attempt_table($this->context, $this->course->id, 0, $this->cm, $filter, null, $this->userid);
                // Display the filter form.
                echo $filterform;
                utils::allow_downloadability_for_attempt_table($table, $this->get_title(), $this->context);
            } else {
                $table = new latest_attempt_table($this->context, $this->course->id, 0, $this->cm, $filter, $download, $this->userid);
                $filename = $this->course->shortname . '_' . str_replace(' ', '_', $this->get_title());
                $table->is_downloading($download, $filename);
                if ($table->is_downloading()) {
                    raise_memory_limit(MEMORY_EXTRA);
                }
            }
        }
        $table->setup();

        // Display the attempt summary for a question attempted by a user.
        if ($usageid > 0) {
            $attempt = end($table->rawdata);
            // Diaplay heading content.
            echo \report_embedquestion\utils::get_embed_location_summary($this->course->id, $attempt);
        }
        // Display the table.
        $table->out($this->pagesize, false);
    }
}
