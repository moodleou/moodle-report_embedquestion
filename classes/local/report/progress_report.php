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

namespace report_embedquestion\local\report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');

use cm_info;
use context;
use moodle_url;
use report_embedquestion\attempt_summary_table;
use report_embedquestion\form\filter;
use report_embedquestion\latest_attempt_table;
use report_embedquestion\report_display_options;
use report_embedquestion\utils;
use stdClass;

/**
 * Abstract class to represent report in the embed question.
 *
 * The report can either relate to all embedded questions in a course (including all
 * the activities the course contains), which is handled by the course_progress_report
 * subclass or, just the embedded questions in one activity: activity_progress_report.
 *
 * The report can contain various amounts of information:
 *
 * - There can be a summary report, showing just the latest attempt at each question,
 *   either for all users, or just one user (depending on the viewer's permissions, and choices.)
 *   This is handled by latest_attempt_table.
 * - or, it can be a detailed analysis of all attempts at one question, handled by attempt_summary_table.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class progress_report {

    /** @var stdClass Course object. */
    protected $course;
    /** @var context Context. */
    protected $context;
    /** @var cm_info Course module object. */
    protected $cm;
    /** @var latest_attempt_table Latest attempt table. */
    protected $reporttable;
    /** @var filter Filter form. */
    protected $filterform;
    /** @var report_display_options Report display options. */
    protected $displayoptions;
    /** @var bool Is the attempt summary report or not. */
    protected $isonequestiondetails;
    /** @var int Single report for specific user. */
    protected $singlereportforuser = 0;

    /**
     * Factory method create a new spell-checker object for a given language.
     *
     * @param stdClass $course
     * @param context $context
     * @param cm_info|null $cm
     * @param bool $isonequestiondetails
     * @return progress_report the requested object.
     */
    public static function make(stdClass $course, context $context, cm_info $cm = null,
            bool $isonequestiondetails = false): progress_report {
        if ($cm) {
            $report = new activity_progress_report($course, $context, $cm);
        } else {
            $report = new course_progress_report($course, $context);
        }
        $report->isonequestiondetails = $isonequestiondetails;

        return $report;
    }

    /**
     * Get the report tile.
     *
     * @return string title.
     */
    abstract public function get_title(): string;

    /**
     * Get the report url.
     *
     * @return moodle_url report url.
     */
    abstract public function get_url_report(): moodle_url;

    /**
     * Get the report url with full params.
     *
     * @return moodle_url report url.
     */
    public function get_full_url_report(): moodle_url {
        $url = $this->get_url_report();
        $url->params($this->displayoptions->get_all_params());

        return $url;
    }

    /**
     * Init the report.
     */
    public function init(): void {
        $this->displayoptions = new report_display_options($this->course->id, $this->cm);
        $this->displayoptions->setup_general_from_params();

        if ($this->isonequestiondetails) {
            $this->displayoptions->process_settings_from_params();
            $this->reporttable = new attempt_summary_table($this->displayoptions->usageid,
                    $this->context, $this->course->id, $this->cm,
                    $this->displayoptions->userid);

        } else {
            $formurl = $this->get_url_report();
            $formurl->params($this->displayoptions->get_general_params());
            $this->filterform = new filter($formurl->out(false), ['context' => $this->context]);
            if ($fromform = $this->filterform->get_data()) {
                $this->displayoptions->process_settings_from_form($fromform);
            } else if (!$this->filterform->is_submitted()) {
                $this->displayoptions->process_settings_from_params();
            }
            if ($this->singlereportforuser) {
                $this->displayoptions->userid = $this->singlereportforuser;
            }
            $this->filterform->set_data($this->displayoptions->get_initial_form_data());
            $this->reporttable = new latest_attempt_table($this->context, $this->course->id, $this->cm,
                    $this->displayoptions, $this->displayoptions->download);
        }
    }

    /**
     * Get download file name.
     *
     * @return string
     */
    public function get_download_filename(): string {
        return $this->course->shortname . '_' . str_replace(' ', '_', $this->get_title());
    }

    /**
     * Set the report to single report for given user.
     * @param int $userid
     */
    public function single_report(int $userid): void {
        $this->singlereportforuser = $userid;
    }

    /**
     * Return the suitable page size for the report.
     *
     * @return int
     */
    public function get_report_page_size(): int {
        return $this->isonequestiondetails ? report_display_options::DEFAULT_REPORT_PAGE_SIZE : $this->displayoptions->pagesize;
    }

    /**
     * Allow the report to use initial bar or not.
     *
     * @return bool
     */
    protected function get_report_use_initialsbar(): bool {
        return empty($this->displayoptions->userid);
    }

    /**
     * Display the report table.
     */
    public function display() {
        if ($this->isonequestiondetails) {
            $this->display_attempt_summary();
        } else {
            if (!$this->displayoptions->download) {
                if (!$this->displayoptions->userid) {
                    $groupurl = $this->get_full_url_report();
                    $groupurl->remove_params('group');
                    groups_print_course_menu($this->course, $groupurl);
                }
                $this->filterform->display();
                utils::allow_downloadability_for_attempt_table($this->reporttable, $this->get_title(), $this->context);
            } else {
                $this->reporttable->is_downloading($this->displayoptions->download, $this->get_download_filename());
                raise_memory_limit(MEMORY_EXTRA);
            }
            $this->reporttable->out($this->get_report_page_size(), $this->get_report_use_initialsbar());
        }
    }

    /**
     * Display the attempt summary table.
     */
    public function display_attempt_summary() {
        // We need information from rendering the table to display the information above it.
        // So, render the table and catch the output.
        ob_start();
        $this->reporttable->out($this->get_report_page_size(), $this->get_report_use_initialsbar());
        $tablehtml = ob_get_contents();
        ob_end_clean();

        // Display heading content.
        $attempt = end($this->reporttable->rawdata);
        echo utils::get_embed_location_summary($this->course->id, $attempt);

        // Then display the table.
        echo $tablehtml;
    }
}
