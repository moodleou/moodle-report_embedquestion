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
 * Display the report for latest attempt table.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_embedquestion;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

use html_writer;
use moodle_url;
use stdClass;
use table_sql;
use user_picture;

/**
 * Display the report for latest attempt table.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class latest_attempt_table extends table_sql {
    /**
     * @var stdClass The sql query we build up before parsing it and filling the parent's $sql variables.
     */
    protected $sqldata = null;

    /**
     * @var int $pagesize the default number of rows on a page.
     */
    protected $perpage = 10;

    /**
     * @var object $attempt
     */
    protected $attempts = null;

    /**
     * @var int $tablemaxrows maximum number of rows.
     */
    protected $tablemaxrows = 10000;

    /**
     * @var string, the name used an 'id' field for the user which is used by parent class in col_fullname() method.
     */
    public $useridfield = 'userid';


    protected $courseid = 0;
    protected $cm = null;
    protected $groupid = 0;
    protected $userid = 0;
    protected $context = null;
    protected $usageid = 0;

    /**
     * progress_table constructor.
     * @param \context $context, the context object
     * @param int $courseid the id of the current course
     * @param int $groupid, the id of the group in a course
     * @param \cm_info|null $cm, the course-module object
     * @param int $userid, the userid as an optional param
     * @param int $usageid, the questionusage id as an optional param
     */
    public function __construct(\context $context, $courseid, $groupid = 0, \cm_info $cm = null, $userid = 0) {
        global $CFG, $DB;
        parent::__construct('report_embedquestion_latest_attempt');
        $this->context = $context;
        $this->courseid = $courseid;
        $this->groupid = $groupid;
        $this->cm = $cm;
        $this->userid = $userid;
        $this->userfields = \report_embedquestion\utils::get_user_fields($context);
        $this->generate_query($this->context->id, $this->userfields);

        $this->define_headers($this->get_headers($userid));
        $this->define_columns($this->get_columns($userid));
        $this->collapsible(false);

        // Set base url.
        if ($cm) {
            $url = new moodle_url($CFG->wwwroot . '/report/embedquestion/activity.php', ['cmid' => $cm->id]);
        } else {
            $url = new moodle_url($CFG->wwwroot . '/report/embedquestion/index.php', ['courseid' => $courseid]);
        }
        $this->define_baseurl($url);
    }

    /**
     * Return an array of the headers
     * @return array
     */
    private function get_headers() {
        $headers = [];
        $headers[] = '';// User's picture or place holder.
        $headers[] = get_string('fullname');
        $headers[] = get_string('username');
        $headers[] = get_string('type', 'report_embedquestion');
        $headers[] = get_string('question');
        $headers[] = get_string('status');
        $headers[] = get_string('context', 'report_embedquestion');
        $headers[] = get_string('attemptfinal', 'report_embedquestion');
        return $headers;
    }

    /**
     * Return an array of columns.
     * @return array
     */
    private function get_columns() {
        $columns = [];
        $columns[] = 'picture';
        $columns[] = 'fullname';
        $columns[] = 'username';
        $columns[] = 'questiontype';
        $columns[] = 'questionname';
        $columns[] = 'questionstate';
        $columns[] = 'pagename';
        $columns[] = 'questionattemptstepid';
        return $columns;
    }

    /**
     * Generate the display of the user's picture column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_picture($attempt) {
        global $OUTPUT;
        $user = new stdClass();
        $additionalfields = explode(',', user_picture::fields());
        $user = username_load_fields_from_object($user, $attempt, null, $additionalfields);
        $user->id = $attempt->userid;
        return $OUTPUT->user_picture($user);
    }

    /**
     * Generate the display of the user's full name column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($attempt) {
        $html = parent::col_fullname($attempt);
        if ($this->is_downloading() || empty($attempt->attempt)) {
            return $html;
        }
        return $html;
    }

    protected function col_questiontype($row) {
        global $CFG;
        if ($this->is_downloading()) {
            return get_string('pluginname', 'qtype_' . $row->questiontype);
        }
        return \report_embedquestion\utils::get_question_icon($row->questiontype);
    }

    protected function col_questionname($row) {
        global $CFG;
        if ($this->is_downloading()) {
            return $row->questionname;
        }
        return \report_embedquestion\utils::get_question_link($this->courseid, $row->questionid, null, $row->questionname);
    }

    /**
     * Generate the display of the attempt state column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    protected function col_questionstate($attempt) {
        return \report_embedquestion\utils::get_icon_for_question_state($attempt->questionstate);
    }

    protected function col_embedid($row) {
        global $CFG;
        if ($this->is_downloading()) {
            return $row->embedid;
        }
        return $row->embedid;
    }

    protected function col_pagename($row) {
        global $CFG;
        if ($this->is_downloading()) {
            return $row->pagename;
        }
        return \report_embedquestion\utils::get_activity_link($row, $this->courseid, $this->cm);
    }

    protected function col_questionattemptstepid($row) {
        global $CFG;
        if ($this->is_downloading()) {
            return $row->questionattemptstepid;
        }
        return \report_embedquestion\utils::get_attempt_summary_link($row, $this->usageid);
    }

    protected function set_sql_data_fields($userfields) {
        // Define the default fields.
        $this->sqldata->fields[] = 'qas.id              AS questionattemptstepid';
        $this->sqldata->fields[] = 'qas.timecreated     AS questionattemptsteptime';
        foreach ($userfields as $field) {
            $this->sqldata->fields[] = "u.$field        AS $field";
        }
        $this->sqldata->fields[] = 'u.id                AS userid';
        $this->sqldata->fields[] = 'q.qtype             AS questiontype';
        $this->sqldata->fields[] = 'q.name              AS questionname';
        $this->sqldata->fields[] = 'q.id                AS questionid';
        $this->sqldata->fields[] = 'r.contextid         AS contextid';
        $this->sqldata->fields[] = 'r.questionusageid   AS questionusageid';
        $this->sqldata->fields[] = 'r.embedid           AS embedid';
        $this->sqldata->fields[] = 'r.pagename          AS pagename';
        $this->sqldata->fields[] = 'r.pageurl           AS pageurl';
        $this->sqldata->fields[] = 'qa.id               AS questionattemptid';
        $this->sqldata->fields[] = 'qa.slot             AS slot';
        $this->sqldata->fields[] = 'qa.maxmark          AS maxmark';
        $this->sqldata->fields[] = 'qas.state           AS questionstate';
        $this->sqldata->fields[] = 'qas.fraction        AS fraction';
    }

    protected function set_sql_data_from() {
        $this->sqldata->from[] = '{report_embedquestion_attempt} r';
        $this->sqldata->from[] = 'JOIN {user} u ON u.id = r.userid';
        $this->sqldata->from[] = 'JOIN {question_usages} qu ON (qu.id = r.questionusageid AND qu.component = \'report_embedquestion\')';

        // Select latest question attempt steps.
        $this->sqldata->from[] = 'JOIN {question_attempts} qa ON (qa.questionusageid = r.questionusageid AND qa.slot = 
                (SELECT MAX(slot) FROM {question_attempts} WHERE questionusageid = qu.id))';
        $this->sqldata->from[] = 'JOIN {question} q ON (q.id = qa.questionid)';
        $this->sqldata->from[] = 'JOIN {question_attempt_steps} qas ON (qa.id = qas.questionattemptid AND qas.sequencenumber <> 0)';
    }

        /**
     * Generate the intermediate SQL data structure to retrieve the information required.
     *
     * @param $contextid
     */
    protected function generate_query($contextid, $userfields, $usageid = 0) {
        global $CFG, $DB;
        // Set the sql data.
        $this->set_sql_data_fields($userfields);
        $this->set_sql_data_from();

        $this->sqldata->where[]  = ' r.contextid = :contextid';
        $this->sqldata->params['contextid'] = $contextid;

        // Report is called from course->report.
        if ($this->cm === null) {
            $coursecontextid = $this->context->id;
            $this->sqldata->from[] = 'JOIN {context} cxt ON cxt.id = r.contextid';
            $this->sqldata->where[] = " OR cxt.path LIKE '%/$coursecontextid/%'";
        }
        // Single user report
        if ($this->userid > 0) {
            $this->sqldata->where[]  = ' AND r.userid = :userid';
            $this->sqldata->params['userid'] = $this->userid;
        }

        $this->sql = new stdClass();
        $this->sql->fields = implode(",\n    ", $this->sqldata->fields);
        $this->sql->from = implode("\n",  $this->sqldata->from);
        $this->sql->where = implode("\n    ", $this->sqldata->where);
        $this->sql->params = $this->sqldata->params;
    }
}
