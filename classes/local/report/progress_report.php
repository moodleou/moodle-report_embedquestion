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
 * Abstract class to represent report in the embed question.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\local\report;

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
    protected $isattemptreport;
    /** @var int Single report for specific user. */
    protected $singlereportforuser = 0;

    /**
     * Factory method create a new spell-checker object for a given language.
     *
     * @param stdClass $course
     * @param context $context
     * @param cm_info|null $cm
     * @param bool $isattemptreport
     * @return progress_report the requested object.
     */
    public static function make(stdClass $course, context $context, cm_info $cm = null, bool $isattemptreport = false): progress_report {
        if ($cm) {
            $report = new activity_progress_report($course, $context, $cm);
        } else {
            $report = new course_progress_report($course, $context);
        }
        $report->isattemptreport = $isattemptreport;

        return $report;
    }

    /**
     * Get the report tile.
     *
     * @return string title.
     */
    public abstract function get_title(): string;

    /**
     * Get the report url.
     *
     * @return moodle_url report url.
     */
    public abstract function get_url_report(): moodle_url;

    /**
     * Init the report.
     */
    public function init(): void {
        $this->displayoptions = new report_display_options($this->course->id, $this->cm);
        if ($this->isattemptreport) {
            $this->displayoptions->process_settings_from_params();
            $this->reporttable =
                    new attempt_summary_table($this->context, $this->course->id, 0, $this->cm, $this->displayoptions->userid,
                            $this->displayoptions->usageid);
        } else {
            $this->filterform = new filter($this->get_url_report()->out(false), ['context' => $this->context]);
            if ($fromform = $this->filterform->get_data()) {
                $this->displayoptions->process_settings_from_form($fromform);
            } else {
                $this->displayoptions->process_settings_from_params();
            }
            if ($this->singlereportforuser) {
                $this->displayoptions->userid = $this->singlereportforuser;
            }
            $this->filterform->set_data($this->displayoptions->get_initial_form_data());
            $this->reporttable = new latest_attempt_table($this->context, $this->course->id, $this->cm, $this->displayoptions,
                    $this->displayoptions->download);
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
        return $this->isattemptreport ? report_display_options::DEFAULT_REPORT_PAGE_SIZE : $this->displayoptions->pagesize;
    }

    /**
     * Allow the report to use initial bar or not.
     *
     * @return bool
     */
    protected function get_report_use_initialsbar(): bool {
        return $this->displayoptions->userid > 0;
    }

    /**
     * Display the report table.
     */
    public function display() {
        if ($this->isattemptreport) {
            $this->display_attempt_summary();
        } else {
            if (!$this->displayoptions->download) {
                if (!$this->displayoptions->userid) {
                    groups_print_course_menu($this->course, $this->get_url_report());
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
        $attempt = end($this->reporttable->rawdata);
        // Display heading content.
        echo utils::get_embed_location_summary($this->course->id, $attempt);
        $this->reporttable->out($this->get_report_page_size(), $this->get_report_use_initialsbar());
    }
}
