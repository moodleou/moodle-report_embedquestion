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

namespace report_embedquestion;

use cm_info;
use moodle_url;
use stdClass;

/**
 * This file defines the options for the embed question progress report.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_display_options {

    /** @var int Number of attempts to show per page. */
    const DEFAULT_REPORT_PAGE_SIZE = 10;

    /** @var cm_info The course module objects. */
    public $cm;
    /** @var int Course id. */
    private $courseid;
    /** @var object Course. */
    private $course;

    /** @var array of context ids, if the user has chosen to filter by particular locations. */
    public $locationids = [];

    /** @var int Filter number of days to look back. */
    public $lookback = 0;
    /** @var int Filter date from. */
    public $datefrom = 0;
    /** @var int Filter date to. */
    public $dateto = 0;
    /** @var int Number of attempts to show per page. */
    public $pagesize = self::DEFAULT_REPORT_PAGE_SIZE;
    /** @var int Group id. */
    public $group = 0;
    /** @var int User id. */
    public $userid = 0;
    /** @var string Whether the data should be downloaded in some format, or '' to display it. */
    public $download = '';
    /** @var int Usage Id. */
    public $usageid;

    /**
     * report_display_options constructor.
     *
     * @param int $courseid
     * @param cm_info|null $cm
     */
    public function __construct(int $courseid, cm_info $cm = null) {
        $this->cm = $cm;
        $this->courseid = $courseid;
        $this->course = get_course($this->courseid);
    }

    /**
     * Get the general parameters to show the report with these options. It will be used in form post url.
     * @return array URL parameter name => value.
     */
    public function get_general_params(): array {
        $params = [];
        if ($this->cm !== null) {
            $params['cmid'] = $this->cm->id;
        } else {
            $params['courseid'] = $this->courseid;
        }
        if ($this->userid) {
            $params['userid'] = $this->userid;
        }
        if (groups_get_course_group($this->course, true)) {
            $params['group'] = $this->group;
        }

        return $params;
    }

    /**
     * Get all parameters required to show the report with these options.
     * @return array URL parameter name => value.
     */
    public function get_all_params(): array {
        $params = $this->get_general_params();
        if ($this->locationids) {
            $params['locationids'] = implode('-', $this->locationids);
        }
        if ($this->lookback) {
            $params['lookback'] = $this->lookback;
        }
        if ($this->datefrom) {
            $params['datefrom'] = $this->datefrom;
        }
        if ($this->dateto) {
            $params['dateto'] = $this->dateto;
        }
        if ($this->dateto) {
            $params['dateto'] = $this->dateto;
        }

        return $params;
    }

    /**
     * Get the URL to show the report with these options.
     * @return moodle_url the URL.
     */
    public function get_url(): moodle_url {
        if ($this->cm !== null) {
            $url = utils::get_url($this->get_all_params(), 'activity');
        } else {
            $url = utils::get_url($this->get_all_params());
        }

        return $url;
    }

    /**
     * Process the data we get when the settings form is submitted. This includes
     * updating the fields of this class, and updating the user preferences
     * where appropriate.
     * @param object $fromform The data from $mform->get_data() from the settings form.
     */
    public function process_settings_from_form($fromform) {
        $this->setup_filter_from_form_data($fromform);
        $this->update_user_preferences();
        $this->redirect_to_clean_url();
    }

    /**
     * Set up this preferences object using optional_param (using user_preferences
     * to set anything not specified by the params.
     */
    public function process_settings_from_params() {
        $this->setup_from_user_preferences();
        $this->setup_filter_from_params();
    }

    /**
     * Get the current value of the settings to pass to the settings form.
     */
    public function get_initial_form_data() {
        $toform = new stdClass();
        $toform->locationids = $this->locationids;
        $toform->lookback = $this->lookback;
        $toform->datefrom = $this->datefrom;
        $toform->dateto = $this->dateto;
        $toform->pagesize = $this->pagesize;

        return $toform;
    }

    /**
     * Set the filter fields of this object from the form data.
     * @param object $fromform The data from $mform->get_data() from the settings form.
     */
    public function setup_filter_from_form_data($fromform) {
        // Only set the location filter for filter from from Course level only.
        if (isset($fromform->locationids)) {
            $this->locationids = $fromform->locationids;
        }
        $this->lookback = $fromform->lookback;
        $this->datefrom = $fromform->datefrom;
        $this->dateto = $fromform->dateto;
        $this->pagesize = $fromform->pagesize;
    }

    /**
     * Set the filter fields of this object from the URL parameters.
     */
    public function setup_filter_from_params() {
        $locationsstring = optional_param('locationids', '', PARAM_ALPHANUMEXT);
        if (!empty($locationsstring)) {
            $this->locationids = explode('-', $locationsstring);
        }
        $this->lookback = optional_param('lookback', 0, PARAM_INT);
        $this->datefrom = optional_param('datefrom', 0, PARAM_INT);
        $this->dateto = optional_param('dateto', 0, PARAM_INT);
    }

    /**
     * Set the general fields of this object from the URL parameters.
     */
    public function setup_general_from_params() {
        $this->userid = optional_param('userid', 0, PARAM_INT);
        $this->group = groups_get_course_group($this->course, true);
        $this->download = optional_param('download', $this->download, PARAM_ALPHA);
        $this->usageid = optional_param('usageid', 0, PARAM_INT);
    }

    /**
     * Set the fields of this object from the user's preferences.
     * (For those settings that are backed by user-preferences).
     */
    public function setup_from_user_preferences() {
        $this->pagesize = get_user_preferences('report_embedquestion_pagesize', $this->pagesize);
    }

    /**
     * Update the user preferences so they match the settings in this object.
     * (For those settings that are backed by user-preferences).
     */
    public function update_user_preferences() {
        set_user_preference('report_embedquestion_pagesize', $this->pagesize);
    }

    public function redirect_to_clean_url() {
        redirect($this->get_url());
    }
}
