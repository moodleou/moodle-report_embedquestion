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
use report_embedquestion\local\export\response_export;
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

    /** @var array $extrauserfields extra user fields from user identity config. */
    public $extrauserfields = [];

    protected $courseid = 0;
    protected $cm = null;
    protected $groupid = 0;
    protected $userid = 0;
    protected $context = null;
    protected $allowedjoins = null;

    /** @var string|null the string used for file extension. */
    protected $isdownloading = null;

    /** @var bool Report table has the qtype that have response files or not. */
    protected $hasresponsesqtype = false;

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
        $this->extrauserfields = get_extra_user_fields($this->context);
        $this->isdownloading = $download;

        // Set base url.
        $baseparams = [];
        if ($this->userid) {
            $baseparams['userid'] = $this->userid;
        }
        if ($this->groupid) {
            $baseparams['groupid'] = $this->groupid;
        }
        if ($cm !== null) {
            $baseparams['cmid'] = $cm->id;
            $url = utils::get_url($baseparams, 'activity');
        } else {
            $baseparams['courseid'] = $this->courseid;
            $url = utils::get_url($baseparams);
        }

        $this->allowedjoins = $this->get_students_joins($this->groupid);

        $this->generate_query($this->context->id, $this->userfields, $filter);
        $this->define_headers($this->get_headers());
        $this->define_columns($this->get_columns());
        $this->collapsible(false);

        $this->define_baseurl($url);
        $this->process_actions($url);
    }

    /**
     * Return an array of the headers
     * @return array
     */
    private function get_headers() {
        $headers = [];
        if (!$this->isdownloading) {
            // We cannot use is_downloading() function here because the table constructor was called before the is_download().
            $headers[] = $this->checkbox_col_header('checkbox');
        }
        $headers[] = get_string('fullnameuser');
        foreach ($this->extrauserfields as $field) {
            $headers[] = get_user_field_name($field);;
        }
        $headers[] = get_string('type', 'report_embedquestion');
        $headers[] = get_string('question');
        $headers[] = get_string('location');
        $headers[] = get_string('latestattemptstatus', 'report_embedquestion');
        $headers[] = get_string('attemptfinal', 'report_embedquestion');
        return $headers;
    }

    /**
     * Render checkbox column header
     *
     * @param string $columnname name of the column
     * @return bool|string
     */
    public function checkbox_col_header(string $columnname) {
        global $OUTPUT;

        // Make sure to disable sorting on this column.
        $this->no_sorting($columnname);

        // Build the select/deselect all control.
        $selectallid = 'embed-question-report-selectall-attempts';
        $selectalltext = get_string('selectall', 'quiz');
        $deselectalltext = get_string('selectnone', 'quiz');
        $mastercheckbox = new \core\output\checkbox_toggleall('embed-attempts', true, [
                'id' => $selectallid,
                'name' => $selectallid,
                'value' => 1,
                'label' => $selectalltext,
                'labelclasses' => 'accesshide',
                'selectall' => $selectalltext,
                'deselectall' => $deselectalltext,
        ]);

        return $OUTPUT->render($mastercheckbox);
    }


    /**
     * Return an array of columns.
     * @return array
     */
    private function get_columns() {
        $columns = [];
        if (!$this->isdownloading) {
            // We cannot use is_downloading() function here because the table constructor was called before the is_download().
            $columns[] = 'checkbox';
        }
        $columns[] = 'fullname';
        foreach ($this->extrauserfields as $field) {
            $columns[] = $field;
        }
        $columns[] = 'questiontype';
        $columns[] = 'questionname';
        $columns[] = 'pagename';
        $columns[] = 'questionstate';
        $columns[] = 'questionattemptstepid';
        return $columns;
    }

    /**
     * Generate the display of the checkbox column.
     *
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_checkbox(object $attempt): string {
        global $OUTPUT, $USER;
        $allowdownload = 0;
        if (response_export::is_qtype_has_response_contain_file($attempt->questiontype) && $attempt->questionstate != 'todo') {
            $allowdownload = 1;
        }

        if (has_capability('report/embedquestion:deleteanyattempt', $this->context) ||
                ($USER->id == $attempt->userid && has_capability('report/embedquestion:deletemyattempt', $this->context))) {
            $checkbox = new \core\output\checkbox_toggleall('embed-attempts', false, [
                    'id' => "questionusageid_{$attempt->questionusageid}",
                    'name' => 'questionusageid[]',
                    'value' => $attempt->questionusageid . '-' . $attempt->slot . '-' . $allowdownload,
                    'label' => get_string('selectattempt', 'quiz'),
                    'labelclasses' => 'accesshide',
            ]);

            return $OUTPUT->render($checkbox);
        } else {
            return '';
        }
    }

    /**
     * Generate the display of the user's full name column.
     * @param object $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($attempt) {
        global $PAGE;
        $html = parent::col_fullname($attempt);
        if ($this->is_downloading() || empty($attempt)) {
            return strip_tags($html);
        }
        if (has_capability('report/embedquestion:viewallprogress', $this->context)) {
            if ($this->cm) {
                $url = new moodle_url('/report/embedquestion/activity.php',
                        ['cmid' => $this->cm->id, 'userid' => $attempt->userid]);
            } else {
                $url = new moodle_url('/report/embedquestion/index.php',
                        ['courseid' => $this->courseid, 'userid' => $attempt->userid]);
            }
            $renderer = $PAGE->get_renderer('report_embedquestion');
            $html .= $renderer->render_show_only_link($url);
        }
        return $html;
    }

    protected function col_questiontype($attempt) {
        if ($this->is_downloading()) {
            return $attempt->questiontype;
        }
        if (response_export::is_qtype_has_response_contain_file($attempt->questiontype)) {
            $this->hasresponsesqtype = true;
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
        $this->sqldata->fields = [];
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
        $this->sqldata->from = [];
        $this->sqldata->from[] = '{report_embedquestion_attempt} r';
        $this->sqldata->from[] = 'JOIN {user} u ON u.id = r.userid';
        $this->sqldata->from[] = "JOIN {question_usages} qu ON " .
                "(qu.id = r.questionusageid AND qu.component = 'report_embedquestion')";

        // Select latest question attempt steps.
        $this->sqldata->from[] = 'JOIN {question_attempts} qa ON (qa.questionusageid = r.questionusageid ' .
                'AND qa.slot = (SELECT MAX(slot) FROM {question_attempts} WHERE questionusageid = qu.id))';
        $this->sqldata->from[] = 'JOIN {question} q ON (q.id = qa.questionid)';
        $this->sqldata->from[] = 'JOIN {question_attempt_steps} qas ON (qa.id = qas.questionattemptid ' .
                'AND qas.sequencenumber = (SELECT MAX(sequencenumber) FROM {question_attempt_steps} ' .
                'WHERE questionattemptid = qas.questionattemptid))';
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required.
     *
     * @param int $contextid
     * @param array $userfields required user fields.
     * @param object|null $filter the filter object('look-back, datefrom, dateto).
     */
    protected function generate_query($contextid, $userfields, $filter = null) {
        global $DB;

        // Set the sql data.
        $this->sqldata = new stdClass();
        $this->sqldata->where = [];
        $this->sqldata->params = [];
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

        // Location.
        if ($filter && !empty($filter->locationids)) {
            list($locationidssql, $params) = $DB->get_in_or_equal($filter->locationids, SQL_PARAMS_NAMED, 'location');
            $this->sqldata->where[]  = ' AND r.contextid ' . $locationidssql;
            $this->sqldata->params = array_merge($this->sqldata->params, $params);
        }

        $this->setup_sql_queries();

        $this->sql = new stdClass();
        $this->sql->fields = implode(",\n    ", $this->sqldata->fields);
        $this->sql->from = implode("\n",  $this->sqldata->from);
        $this->sql->where = implode("\n    ", $this->sqldata->where);
        $this->sql->params = $this->sqldata->params;
    }

    /**
     * Setup query with groupid, groupmode.
     */
    public function setup_sql_queries() {
        $this->sqldata->from[] = $this->allowedjoins->joins;
        $this->sqldata->where[] = ' AND ' . $this->allowedjoins->wheres;
        $this->sqldata->params = array_merge($this->sqldata->params, $this->allowedjoins->params);
    }

    /**
     * Get sql fragments (joins) which can be used to build queries that
     * will select an appropriate set of students to show in the reports.
     *
     * @param int $groupid The group id.
     * @return object with elements:
     *         \core\dml\sql_join Contains joins, wheres, params for all the students to show in the report.
     *
     */
    protected function get_students_joins($groupid) {
        // We have a currently selected group.
        if ($groupid) {
            return get_enrolled_with_capabilities_join($this->context, '',
                    'report/embedquestion:viewmyprogress', $groupid);
        } else {
            return get_enrolled_with_capabilities_join($this->context, '', 'report/embedquestion:viewmyprogress');
        }
    }

    /**
     * Hook that can be overridden in child classes to wrap a table in a form
     * for example. Called only when there is data to display and not
     * downloading.
     */
    public function wrap_html_start() {
        $output = html_writer::start_tag('form',
                ['id' => 'attemptsform', 'method' => 'post', 'action' => $this->baseurl]);
        $url = $this->baseurl;
        $url->param('sesskey', sesskey());
        $output .= html_writer::input_hidden_params($url);

        echo $output;
    }

    /**
     * Hook that can be overridden in child classes to wrap a table in a form
     * for example. Called only when there is data to display and not
     * downloading.
     */
    public function wrap_html_finish() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('report_embedquestion');

        $output = html_writer::start_div('commands');
        $output .= $renderer->render_delete_attempts_buttons();
        if ($this->hasresponsesqtype && $this->context->contextlevel == CONTEXT_MODULE) {
            $output .= $renderer->render_download_response_files();
        }
        $output .= html_writer::end_div();

        // Close the form.
        $output .= html_writer::end_tag('form');

        echo $output;
    }

    /**
     * Process any submitted actions.
     *
     * @param moodle_url $redirect Redirect url
     */
    protected function process_actions(moodle_url $redirect) {
        global $USER, $DB;
        if (optional_param('delete', 0, PARAM_BOOL) && confirm_sesskey()) {
            if ($questionusagemetas = optional_param_array('questionusageid', [], PARAM_ALPHANUMEXT)) {
                foreach ($questionusagemetas as $questionusagemeta) {
                    $qubameta = explode('-', $questionusagemeta);
                    $qubaid = $qubameta[0];
                    $slot = $qubameta[1];

                    $quba = \question_engine::load_questions_usage_by_activity($qubaid);
                    $userid = $quba->get_question_attempt($slot)->get_last_step()->get_user_id();

                    if (has_capability('report/embedquestion:deleteanyattempt', $this->context)) {
                        attempt_storage::instance()->delete_attempt($quba);
                    } else if (has_capability('report/embedquestion:deletemyattempt', $this->context) && $USER->id == $userid) {
                        attempt_storage::instance()->delete_attempt($quba);
                    } else {
                        throw new \required_capability_exception($this->context, 'report/embedquestion:deletemyattempt',
                                'nopermissions', '');
                    }
                }
                redirect($redirect);
            }
        }

        if (optional_param('downloadselect', 0, PARAM_BOOL) && confirm_sesskey()) {
            if ($questionusagemetas = optional_param_array('questionusageid', [], PARAM_ALPHANUMEXT)) {
                $qubaids = [];
                foreach ($questionusagemetas as $questionusagemeta) {
                    $qubameta = explode('-', $questionusagemeta);
                    $qubaids[] = $qubameta[0];
                }
                $params = response_export::get_response_zip_file_info($qubaids, $this->context, $this->userid);
                $params['cmid'] = $this->cm->id;
                $downloadurl = new moodle_url('/report/embedquestion/responsedownload.php', $params);
                redirect($downloadurl);
            }
        }

        if (optional_param('downloadall', 0, PARAM_BOOL) && confirm_sesskey()) {
            $sql = "SELECT DISTINCT r.questionusageid
                      FROM {$this->sql->from}
                     WHERE {$this->sql->where}";

            $datas = $DB->get_records_sql_menu($sql, $this->sql->params);
            $qubaids = [];
            foreach ($datas as $qubaid => $unused) {
                $qubaids[] = $qubaid;
            }
            $params = response_export::get_response_zip_file_info($qubaids, $this->context, $this->userid);
            $params['cmid'] = $this->cm->id;
            $downloadurl = new moodle_url('/report/embedquestion/responsedownload.php', $params);
            redirect($downloadurl);
        }
    }

}
