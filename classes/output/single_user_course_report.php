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
use report_embedquestion\form\filter;
use report_embedquestion\latest_attempt_table;
use report_embedquestion\attempt_summary_table;
use html_writer;
use report_embedquestion\report_display_options;
use report_embedquestion\utils;

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
     *
     * @param string|null $download if set, download in this format, else display in the HTML page.
     * @param int $usageid if set, show only data relating to this usage.
     */
    public function display_download_content(?string $download = null, int $usageid = 0): void {
        global $COURSE;
        if ($usageid) {
            $table = new attempt_summary_table($this->context, $this->courseid, 0, null, $this->userid, $usageid);
        } else {
            // TODO: This part of code will be moved to only one place in #385941.
            $filterform = new filter(utils::get_url(['courseid' => $this->courseid])->out(false), ['context' => $this->context]);
            $displayoptions = new report_display_options($this->courseid, null);
            if ($fromform = $filterform->get_data()) {
                $displayoptions->process_settings_from_form($fromform);
            } else {
                $displayoptions->process_settings_from_params();
            }
            $filterform->set_data($displayoptions->get_initial_form_data());
            if (!$download) {
                $table = new latest_attempt_table($this->context, $this->courseid, null, $displayoptions, null, $this->userid);
                // Display the filter form.
                echo $filterform->render();
                utils::allow_downloadability_for_attempt_table($table, $this->get_title(), $this->context);
            } else {
                $table = new latest_attempt_table($this->context, $this->courseid, null, $displayoptions, $download, $this->userid);

                $filename = $COURSE->shortname . '_' . str_replace(' ', '_', $this->get_title());
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
            echo \report_embedquestion\utils::get_embed_location_summary($this->courseid, $attempt);
            // Display the table.
            $table->out(report_display_options::DEFAULT_REPORT_PAGE_SIZE, false);
        } else {
            // Display the table.
            $table->out($displayoptions->pagesize, false);
        }
    }
}
