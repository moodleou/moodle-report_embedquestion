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
 * Display the report for attempt summary table.
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


/**
 * Display the report for attempt summary table.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_summary_table extends table_sql {

    /**
     * @var stdClass The sql query we build up before parsing it and filling the parent's $sql variables.
     */
    public $sqldata = null;

    /**
     * @var int $pagesize the default number of rows on a page.
     */
    public $perpage = 10;

    /**
     * @var object $attempt
     */
    public $attempts = null;

    /**
     * @var string the name used an 'id' field for the user which is used by parent class in col_fullname() method.
     */
    public $useridfield = 'userid';

    /**
     * @var array required user fields (e.g. all the name fields and extra profile fields).
     */
    public $userfields = 'userid';

    /**
     * @var int $tablemaxrows maximum number of rows.
     */
    protected $tablemaxrows = 10000;

    public $courseid = 0;
    public $cm = null;
    public $groupid = 0;
    public $userid = 0;
    public $context = null;
    public $usageid = 0;

    /**
     * progress_table constructor.
     * @param \context $context, the context object
     * @param int $courseid the id of the current course
     * @param int $groupid, the id of the group in a course
     * @param \cm_info|null $cm, the course-module object
     * @param int $userid, the userid as an optional param
     * @param int $usageid, the questionusage id as an optional param
     */
    public function __construct(\context $context, $courseid, $groupid = 0, \cm_info $cm = null, $userid = 0, $usageid = 0) {
        global $CFG;
        parent::__construct('report_embedquestion_attempt_summary');
        $this->context = $context;
        $this->courseid = $courseid;
        $this->groupid = $groupid;
        $this->cm = $cm;
        $this->userid = $userid;
        $this->usageid = $usageid;
        $this->userfields = utils::get_user_fields($context);

        $this->define_headers($this->get_headers());
        $this->define_columns($this->get_columns());

        $this->collapsible(false);

        $this->generate_query($this->context->id, $this->userfields, $usageid);

        if ($cm) {
            $url = new moodle_url('/report/embedquestion/activity.php',
                    ['cmid' => $cm->id, 'usageid' => $usageid]);
        } else {
            $url = new moodle_url('/report/embedquestion/index.php',
                    ['courseid' => $courseid, 'usageid' => $usageid]);
        }
        $this->define_baseurl($url);
        $this->setup();
        $this->query_db($this->tablemaxrows, false);
    }

    /**
     * Return an array of the headers
     * @return array
     */
    private function get_headers() {
        $headers = [];
        $headers[] = get_string('status');
        $headers[] = get_string('grade');
        $headers[] = get_string('attemptedon', 'report_embedquestion');
        return $headers;
    }

    /**
     * Return an array of columns.
     * @return array
     */
    private function get_columns() {
        $columns = [];
        $columns[] = 'questionstate';
        $columns[] = 'fraction';
        $columns[] = 'questionattemptsteptime';
        return $columns;
    }

    /**
     * Generate the display of the attempt state column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_questionstate($attempt) {
        return utils::get_question_state($attempt->questionstate);
    }

    public function col_fraction($attempt) {
        if ($attempt->questionstate === 'todo') {
            return null;
        }
        return utils::get_grade($this->courseid, $attempt->fraction, $attempt->maxmark);
    }

    protected function col_questionattemptsteptime($row) {
        if ($this->is_downloading()) {
            return $row->questionattemptstepid;
        }
        return utils::get_attempt_summary_link($row, $this->usageid);
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

    public function set_sql_data_from() {
        $this->sqldata->from[] = '{report_embedquestion_attempt} r';
        $this->sqldata->from[] = 'JOIN {user} u ON u.id = r.userid';
        $this->sqldata->from[] = 'JOIN {question_usages} qu ON (qu.id = r.questionusageid ' .
                'AND qu.component = \'report_embedquestion\')';
        $this->sqldata->from[] = "JOIN {question_attempts} qa ON (qa.questionusageid = :usageid)";
        $this->sqldata->from[] = 'JOIN {question} q ON (q.id = qa.questionid)';
        $this->sqldata->from[] = 'JOIN {question_attempt_steps} qas ON (qa.id = qas.questionattemptid)';
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required.
     *
     * @param $contextid
     */
    protected function generate_query($contextid, $userfields, $usageid = 0) {

        // Set sql data.
        $this->set_sql_data_fields($userfields);
        $this->set_sql_data_from();

        $this->sqldata->where[]  = "r.questionusageid = $usageid AND ";
        $this->sqldata->params['usageid'] = $usageid;

        $this->sqldata->where[]  = ' r.contextid = :contextid';
        $this->sqldata->params['contextid'] = $contextid;

        // Report is called from course->report.
        if ($this->cm === null) {
            $coursecontextid = $this->context->id;
            $this->sqldata->from[] = 'JOIN {context} cxt ON cxt.id = r.contextid';
            if ($usageid === 0) {
                $this->sqldata->where[] = " OR cxt.path LIKE '%/$coursecontextid/%'";
            }
        }
        // Single user report.
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
