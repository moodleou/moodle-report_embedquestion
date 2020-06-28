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
use moodle_url;
use stdClass;
use table_sql;
use user_picture;
use html_writer;

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
    protected $sqldata;

    /**
     * @var int $pagesize the default number of rows on a page.
     */
    public $perpage = 10;

    /**
     * @var object $attempt
     */
    protected $attempts = null;

    /**
     * @var string, the name used an 'id' field for the user which is used by parent class in col_fullname() method.
     */
    public $useridfield = 'userid';

    /**
     * @var array required user fields (e.g. all the name fields and extra profile fields).
     */
    public $userfields = null;

    protected $courseid = 0;
    protected $cm = null;
    protected $groupid = 0;
    protected $userid = 0;
    protected $context = null;

    /**
     * latest_attempt_table constructor.
     * @param \context $context, the context object
     * @param int $courseid the id of the current course
     * @param int $groupid, the id of the group in a course
     * @param \cm_info|null $cm, the course-module object
     * @param object|null $filter the filter object('look-back, datefrom, dateto).
     * @param string|null $download the string used for file extension
     * @param int $userid, the userid as an optional param
     */
    public function __construct(\context $context, $courseid, $groupid = 0, \cm_info $cm = null,
                                $filter = null, $download = null, $userid = 0) {
        global $CFG;
        parent::__construct('report_embedquestion_latest_attempt');
        $this->context = $context;
        $this->courseid = $courseid;
        $this->groupid = $groupid;
        $this->cm = $cm;
        $this->userid = $userid;
        $this->userfields = utils::get_user_fields($context);

        // Set base url.
        if ($cm !== null) {
            $url = utils::get_url(['cmid' => $cm->id], 'activity');
        } else {
            $url = utils::get_url(['courseid' => $this->courseid]);
        }

        $this->generate_query($this->context->id, $this->userfields, $filter);
        $this->define_headers($this->get_headers());
        $this->define_columns($this->get_columns());
        $this->collapsible(false);

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
        $headers[] = get_string('location');
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
        if ($this->is_downloading()) {
            return '';
        }
        return $OUTPUT->user_picture($user);
    }

    /**
     * Generate the display of the user's full name column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($attempt) {
        $html = parent::col_fullname($attempt);
        if ($this->is_downloading() || empty($attempt)) {
            return strip_tags($html);
        }
        return $html;
    }

    protected function col_questiontype($attempt) {
        if ($this->is_downloading()) {
            return $attempt->questiontype;
        }
        return utils::get_question_icon($attempt->questiontype);
    }

    protected function col_questionname($attempt) {
        if ($this->is_downloading()) {
            return $attempt->questionname;
        }
        return utils::get_question_link($this->courseid, $attempt->questionid, null, $attempt->questionname);
    }

    /**
     * Generate the display of the attempt state column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    protected function col_questionstate($attempt) {
        if ($this->is_downloading()) {
            return strip_tags(utils::get_question_state($attempt->questionstate));
        }
        return utils::get_question_state($attempt->questionstate);
    }

    protected function col_embedid($attempt) {
        if ($this->is_downloading()) {
            return $attempt->embedid;
        }
        return $attempt->embedid;
    }

    protected function col_pagename($attempt) {
        if ($this->is_downloading()) {
            return $attempt->pagename;
        }
        return utils::get_activity_link($attempt);
    }

    protected function col_questionattemptstepid($attempt) {
        if ($this->is_downloading()) {
            return userdate($attempt->questionattemptsteptime);
        }
        return utils::get_attempt_summary_link($attempt);
    }

    protected function set_sql_data_fields($userfields) {
        // Define the default fields.
        $this->sqldata = new stdClass();
        $this->sqldata->fields[] = 'qas.id              AS questionattemptstepid';
        $this->sqldata->fields[] = 'qas.timecreated     AS questionattemptsteptime';
        if ($userfields) {
            foreach ($userfields as $field) {
                $this->sqldata->fields[] = "u.$field        AS $field";
            }
        } else {
            $this->sqldata->fields[] = 'u.username      AS username';
            $this->sqldata->fields[] = 'u.firstname     AS firstname';
            $this->sqldata->fields[] = 'u.lastname      AS lastname';
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
        $this->sqldata->from[] = "JOIN {question_usages} qu ON " .
                "(qu.id = r.questionusageid AND qu.component = 'report_embedquestion')";

        // Select latest question attempt steps.
        $this->sqldata->from[] = 'JOIN {question_attempts} qa ON (qa.questionusageid = r.questionusageid ' .
                'AND qa.slot = (SELECT MAX(slot) FROM {question_attempts} WHERE questionusageid = qu.id))';
        $this->sqldata->from[] = 'JOIN {question} q ON (q.id = qa.questionid)';
        $this->sqldata->from[] = 'JOIN {question_attempt_steps} qas ON (qa.id = qas.questionattemptid AND qas.sequencenumber <> 0)';
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required.
     *
     * @param int $contextid
     * @param array $userfields required user fields.
     * @param object|null $filter the filter object('look-back, datefrom, dateto).
     */
    protected function generate_query($contextid, $userfields, $filter = null) {
        // Set the sql data.
        $this->set_sql_data_fields($userfields);
        $this->set_sql_data_from();

        $this->sqldata->where[]  = ' (r.contextid = :contextid';
        $this->sqldata->params['contextid'] = $contextid;

        // Report is called from course->report.
        if ($this->cm === null) {
            $coursecontextid = $this->context->id;
            $this->sqldata->from[] = 'JOIN {context} cxt ON cxt.id = r.contextid';
            $this->sqldata->where[] = " OR cxt.path LIKE '%/$coursecontextid/%')";
        } else {
            $this->sqldata->where[] = ')';
        }
        // Single user report.
        if ($this->userid > 0) {
            $this->sqldata->where[]  = ' AND r.userid = :userid';
            $this->sqldata->params['userid'] = $this->userid;
        }

        // Filter data.
        if ($filter && $filter->lookback > 0) { // Look back.
            $this->sqldata->where[]  = ' AND qas.timecreated > :lookback';
            $this->sqldata->params['lookback'] = time() - $filter->lookback;

        } else if ($filter && $filter->datefrom > 0 && $filter->dateto > 0) { // From - To.
            $this->sqldata->where[]  = ' AND (qas.timecreated > :datefrom AND qas.timecreated < :dateto)';
            $this->sqldata->params['datefrom'] = $filter->datefrom;
            $this->sqldata->params['dateto'] = $filter->dateto + DAYSECS;

        } else if ($filter && $filter->datefrom > 0) { // From.
            $this->sqldata->where[]  = ' AND qas.timecreated > :datefrom';
            $this->sqldata->params['datefrom'] = $filter->datefrom;

        } else if ($filter && $filter->dateto > 0) { // To.
            $this->sqldata->where[]  = ' AND qas.timecreated < :dateto';
            $this->sqldata->params['dateto'] = $filter->dateto + DAYSECS;
        }

        $this->sql = new stdClass();
        $this->sql->fields = implode(",\n    ", $this->sqldata->fields);
        $this->sql->from = implode("\n",  $this->sqldata->from);
        $this->sql->where = implode("\n    ", $this->sqldata->where);
        $this->sql->params = $this->sqldata->params;
    }
}
