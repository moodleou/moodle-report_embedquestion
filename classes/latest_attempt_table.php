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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

use cm_info;
use context;
use core_user\fields;
use moodle_url;
use question_state;
use report_embedquestion\local\export\response_export;
use stdClass;
use table_sql;
use html_writer;

/**
 * The report table shows the latest attempt at each different embedded question which matches the conditions.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class latest_attempt_table extends table_sql {

    /** @var int $perpage the default number of rows on a page. */
    public $perpage = 10;

    /** @var context $context the context this report is for (coure or activity context). */
    protected $context;

    /** @var int $courseid the id of the current course. */
    protected $courseid = 0;

    /** @var cm_info|null $cm if set, this is a report for one activity, else a whole course. */
    protected $cm;

    /** @var int if set, only show data for users in this group, else all users. */
    protected $groupid;

    /** @var int if set, only show data for this one user, else all relevant. */
    protected $userid;

    /** @var string|null the string used for file extension. */
    protected $isdownloading = null;

    /** @var bool set to true if the report contains any qtype that has response files. */
    protected $hasresponsesqtype = false;

    /** @var fields information about which user fields to show. */
    protected $userfields;

    /** @var array Questionusage's ids which has at least one slot has a response file. */
    protected $questionusagehasattachmentids = [];

    /**
     * latest_attempt_table constructor.
     *
     * @param context $context the context object.
     * @param int $courseid the id of the current course.
     * @param cm_info|null $cm if set, this is a report for one activity, else a whole course.
     * @param report_display_options $displayoption The report display option ('look-back, datefrom, dateto).
     * @param string|null $download the string used for file extension.
     */
    public function __construct(context $context, int $courseid, ?cm_info $cm,
            report_display_options $displayoption, string $download = null) {
        parent::__construct('report_embedquestion_latest_attempt');

        $this->context = $context;
        $this->courseid = $courseid;
        $this->groupid = $displayoption->group;
        $this->cm = $cm;
        $this->userid = $displayoption->userid;
        $this->userfields = fields::for_identity($context)->with_name();
        $this->isdownloading = $download;

        // The name used an 'id' field for the user which is used by parent class in col_fullname() method.
        $this->useridfield = 'userid';

        $this->generate_query($displayoption);
        $this->define_headers($this->get_headers());
        $this->define_columns($this->get_columns());
        $this->collapsible(false);

        $url = $displayoption->get_url();
        $this->define_baseurl($url);
        $this->process_actions($url);
    }

    /**
     * Return an array of the headers
     * @return array
     */
    private function get_headers(): array {
        $headers = [];
        if (!$this->isdownloading) {
            // We cannot use is_downloading() function here because the table constructor was called before the is_download().
            $headers[] = $this->checkbox_col_header('checkbox');
        }
        $headers[] = get_string('fullnameuser');
        foreach ($this->userfields->get_required_fields([fields::PURPOSE_IDENTITY]) as $field) {
            $headers[] = fields::get_display_name($field);
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
    private function get_columns(): array {
        $columns = [];
        if (!$this->isdownloading) {
            // We cannot use is_downloading() function here because the table constructor was called before the is_download().
            $columns[] = 'checkbox';
        }
        $columns[] = 'fullname';
        foreach ($this->userfields->get_required_fields([fields::PURPOSE_IDENTITY]) as $field) {
            $columns[] = strtolower($field);
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
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_checkbox(stdClass $attempt): string {
        global $OUTPUT, $USER;
        $allowdownload = 0;
        if (response_export::is_qtype_has_response_contain_file($attempt->questiontype)) {
            // We allow the user to download their responses in cases:
            // 1. Attempt that has only one slot and their response has file response.
            // 2. Attempt that has more than one slot with at least one slot has a file response.
            if (array_key_exists($attempt->questionusageid, $this->questionusagehasattachmentids)) {
                $allowdownload = 1;
            }
        }

        if (has_capability('report/embedquestion:deleteanyattempt', $this->context) ||
                ($USER->id == $attempt->userid && has_capability('report/embedquestion:deletemyattempt', $this->context))) {
            $checkbox = new \core\output\checkbox_toggleall('embed-attempts', false, [
                    'id' => 'questionusageid_' . $attempt->questionusageid,
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
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fullname($attempt): string {
        global $PAGE;
        $html = parent::col_fullname($attempt);
        if ($this->is_downloading() || empty($attempt)) {
            return strip_tags($html);
        }
        if (has_capability('report/embedquestion:viewallprogress', $this->context) && !$this->userid) {
            $url = new moodle_url($this->baseurl);
            $url->params(['userid' => $attempt->userid]);
            /** @var \report_embedquestion\output\renderer $renderer */
            $renderer = $PAGE->get_renderer('report_embedquestion');
            $html .= $renderer->render_show_only_link($url);
        }
        return $html;
    }

    /**
     * Render the contents of the question type icon column.
     *
     * @param stdClass $attempt data for the row of the table being shown.
     * @return string HTML of the cell contents.
     */
    protected function col_questiontype(stdClass $attempt): string {
        if ($this->is_downloading()) {
            return $attempt->questiontype;
        }
        if (response_export::is_qtype_has_response_contain_file($attempt->questiontype)) {
            $this->hasresponsesqtype = true;
        }
        return utils::get_question_icon($attempt->questiontype);
    }

    /**
     * Render the contents of the question name column.
     *
     * @param stdClass $attempt data for the row of the table being shown.
     * @return string HTML of the cell contents.
     */
    protected function col_questionname(stdClass $attempt): string {
        if ($this->is_downloading()) {
            return $attempt->questionname;
        }
        return utils::get_question_link($this->courseid, $attempt->questionid, null, $attempt->questionname);
    }

    /**
     * Generate the display of the attempt state column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    protected function col_questionstate(stdClass $attempt): string {
        if ($this->is_downloading()) {
            return strip_tags(utils::get_question_state($attempt->questionstate));
        }
        return utils::get_question_state($attempt->questionstate);
    }

    /**
     * Generate the display of the attempt state column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    protected function col_embedid(stdClass $attempt): string {
        return $attempt->embedid;
    }

    /**
     * Generate the display of the page name column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    protected function col_pagename(stdClass $attempt): string {
        if ($this->is_downloading()) {
            return $attempt->pagename;
        }
        return utils::get_activity_link($attempt);
    }

    /**
     * Generate the display of the action time column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    protected function col_questionattemptstepid(stdClass $attempt): string {
        if ($this->is_downloading()) {
            return userdate($attempt->questionattemptsteptime);
        }
        return utils::get_attempt_summary_link($attempt);
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required.
     *
     * @param report_display_options $displayoption The report display option ('look-back, datefrom, dateto).
     */
    protected function generate_query(report_display_options $displayoption): void {
        global $DB;

        $this->sql = new stdClass();

        $userfieldssql = $this->userfields->get_sql('u', true);
        $enroljoin = get_enrolled_with_capabilities_join($this->context, '',
                'report/embedquestion:viewmyprogress', $this->groupid);

        $this->sql->fields = "
                qas.id              AS questionattemptstepid,
                qas.timecreated     AS questionattemptsteptime,
                qas.state           AS questionstate,
                qas.fraction,
                qa.id               AS questionattemptid,
                qa.slot,
                qa.maxmark          AS maxmark,
                q.qtype             AS questiontype,
                q.name              AS questionname,
                q.id                AS questionid,
                r.contextid,
                r.userid,
                r.questionusageid,
                r.embedid,
                r.pagename,
                r.pageurl" .
                $userfieldssql->selects;

        $this->sql->from =
                "{report_embedquestion_attempt} r

                JOIN {context} ctx ON ctx.id = r.contextid

                JOIN {user} u ON u.id = r.userid
                $userfieldssql->joins

                $enroljoin->joins

                JOIN {question_usages} qu ON qu.id = r.questionusageid
                -- Latest attempt in each usage.
                JOIN {question_attempts} qa ON qa.questionusageid = r.questionusageid AND
                        qa.slot = (SELECT MAX(slot) FROM {question_attempts} WHERE questionusageid = qu.id)
                JOIN {question} q ON q.id = qa.questionid
                -- Latest step in each question_attempt.
                JOIN {question_attempt_steps} qas ON qa.id = qas.questionattemptid
                        AND qas.sequencenumber = (
                                SELECT MAX(sequencenumber)
                                FROM {question_attempt_steps}
                                WHERE questionattemptid = qas.questionattemptid
                        )
        ";

        $this->sql->params = array_merge($userfieldssql->params, $enroljoin->params);

        if ($this->cm === null) {
            $contextwhere = "(ctx.id = :contextid OR ctx.path LIKE :contextpathpattern)";
            $this->sql->params['contextid'] = $this->context->id;
            $this->sql->params['contextpathpattern'] = $this->context->path . '/%';
        } else {
            $contextwhere = "r.contextid = :contextid";
            $this->sql->params['contextid'] = $this->context->id;
        }
        $this->sql->where = "
                $contextwhere
                AND $enroljoin->wheres";
        // Single user report.
        if ($this->userid) {
            $this->sql->where .= "
                AND r.userid = :userid";
            $this->sql->params['userid'] = $this->userid;
        }

        // Filter data.
        if ($displayoption->lookback > 0) { // Look back.
            $this->sql->where .= "
                AND qas.timecreated > :lookback";
            $this->sql->params['lookback'] = time() - $displayoption->lookback;

        } else if ($displayoption->datefrom > 0 && $displayoption->dateto > 0) { // From - To.
            $this->sql->where .= "
                AND qas.timecreated > :datefrom
                AND qas.timecreated < :dateto";
            $this->sql->params['datefrom'] = $displayoption->datefrom;
            $this->sql->params['dateto'] = $displayoption->dateto + DAYSECS;

        } else if ($displayoption->datefrom > 0) { // From.
            $this->sql->where .= "
                AND qas.timecreated > :datefrom";
            $this->sql->params['datefrom'] = $displayoption->datefrom;

        } else if ($displayoption->dateto > 0) { // To.
            $this->sql->where .= "
                AND qas.timecreated < :dateto";
            $this->sql->params['dateto'] = $displayoption->dateto + DAYSECS;
        }

        // Location.
        if (!empty($displayoption->locationids)) {
            list($locationidssql, $params) = $DB->get_in_or_equal(
                    $displayoption->locationids, SQL_PARAMS_NAMED, 'location');
            $this->sql->where .= "
                AND r.contextid $locationidssql";
            $this->sql->params = array_merge($this->sql->params, $params);
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
        /** @var \report_embedquestion\output\renderer $renderer */
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

    /**
     * Load any extra data after main query.
     * At this point we need to check the attempt has at least one slot has a file response.
     *
     * @return  void
     */
    protected function load_extra_data() : void {
        global $DB;

        $questionusageids = [];
        foreach ($this->rawdata as $attempt) {
            if (!response_export::is_qtype_has_response_contain_file($attempt->questiontype)) {
                continue;
            }

            if ($attempt->questionusageid > 0) {
                $questionusageids[] = $attempt->questionusageid;
            }
        }

        if (empty($questionusageids)) {
            return;
        }

        list($areasql, $areaparam) = $DB->get_in_or_equal(utils::get_qtype_fileareas());
        list($questionusageidsql, $questionusageidparam) = $DB->get_in_or_equal($questionusageids);
        $sql = "SELECT DISTINCT qa.questionusageid
                  FROM {question_attempts} as qa
                  JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
                            AND qas.sequencenumber = (
                                    SELECT MAX(sequencenumber)
                                      FROM {question_attempt_steps} qas1
                                 LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas1.id
                                           AND qasd.name $areasql
                                     WHERE questionattemptid = qas.questionattemptid and qasd.id IS NOT NULL
                            )
             LEFT JOIN {files} f ON f.component = 'question'
                            AND f.itemid = qas.id
                            AND f.filename <> '.'
                 WHERE qa.questionusageid $questionusageidsql AND f.filename IS NOT NULL";
        $this->questionusagehasattachmentids = $DB->get_records_sql($sql, array_merge($areaparam, $questionusageidparam));
    }

    public function query_db($pagesize, $useinitialsbar = true) {
        parent::query_db($pagesize, $useinitialsbar);

        $this->load_extra_data();
    }
}
