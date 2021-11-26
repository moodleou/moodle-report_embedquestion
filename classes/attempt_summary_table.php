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

use moodle_url;
use stdClass;
use table_sql;
use core_user\fields;

/**
 * This table shows all the attempts at one particular question.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_summary_table extends table_sql {

    /**
     * @var int $pagesize the default number of rows on a page.
     */
    public $perpage = 10;

    /** @var int the report will just show details of all at attempts in this usage. */
    public $usageid = 0;

    /** @var \context $context the context this report is for (coure or activity context). */
    protected $context;

    /** @var int $courseid the id of the current course. */
    protected $courseid = 0;

    /** @var \cm_info|null $cm if set, this is a report for one activity, else a whole course. */
    protected $cm;

    /** @var int if set, only show data for this one user, else all relevant. */
    protected $userid;

    /**
     * @var fields info about required user name and info fields.
     */
    public $userfields;

    /**
     * Constructor.
     *
     * @param int $usageid the report will just show details of all at attempts in this usage.
     * @param \context $context the context this report is for (coure or activity context).
     * @param int $courseid the id of the current course.
     * @param \cm_info|null $cm if set, this is a report for one activity, else a whole course.
     * @param int $userid if set, only show data for this one user, else all relevant.
     */
    public function __construct(int $usageid, \context $context, int $courseid, \cm_info $cm = null,
            int $userid = 0) {

        parent::__construct('report_embedquestion_attempt_summary');
        $this->usageid = $usageid;
        $this->context = $context;
        $this->courseid = $courseid;
        $this->cm = $cm;
        $this->userid = $userid;
        $this->userfields = fields::for_identity($context)->with_name();

        // The name used an 'id' field for the user which is used by parent class in col_fullname() method.
        $this->useridfield = 'userid';

        $this->define_headers($this->get_headers());
        $this->define_columns($this->get_columns());

        $this->collapsible(false);

        $this->generate_query();

        if ($cm) {
            $url = new moodle_url('/report/embedquestion/activity.php',
                    ['cmid' => $cm->id, 'userid' => $userid, 'usageid' => $usageid]);
        } else {
            $url = new moodle_url('/report/embedquestion/index.php',
                    ['courseid' => $courseid, 'userid' => $userid, 'usageid' => $usageid]);
        }
        $this->define_baseurl($url);
    }

    /**
     * Return an array of the headers.
     *
     * @return array
     */
    private function get_headers(): array {
        $headers = [];
        $headers[] = get_string('status');
        $headers[] = get_string('gradenoun');
        $headers[] = get_string('attemptedon', 'report_embedquestion');
        return $headers;
    }

    /**
     * Return an array of columns.
     *
     * @return array
     */
    private function get_columns(): array {
        $columns = [];
        $columns[] = 'questionstate';
        $columns[] = 'fraction';
        $columns[] = 'questionattemptsteptime';
        return $columns;
    }

    /**
     * Generate the display of the attempt state column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_questionstate(stdClass $attempt): string {
        return utils::get_question_state($attempt->questionstate);
    }

    /**
     * Generate the display of the fraction (mark) column.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    public function col_fraction(stdClass $attempt): string {
        global $PAGE;
        if ($attempt->questionstate === 'todo') {
            return '';
        }
        /** @var \report_embedquestion\output\renderer $renderer */
        $renderer = $PAGE->get_renderer('report_embedquestion');
        return $renderer->render_grade_link($attempt, $this->cm->id, $this->courseid);
    }

    /**
     * Generate the link to the attempt summary.
     *
     * @param stdClass $attempt the table row being output.
     * @return string HTML content to go inside the td.
     */
    protected function col_questionattemptsteptime(stdClass $attempt): string {
        if ($this->is_downloading()) {
            return $attempt->questionattemptstepid;
        }
        return utils::get_attempt_summary_link($attempt, $this->usageid);
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required.
     */
    protected function generate_query(): void {

        $this->sql = new stdClass();
        $userfieldssql = $this->userfields->get_sql('u', true);

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
                JOIN {context} cxt ON cxt.id = r.contextid

                JOIN {user} u ON u.id = r.userid
                $userfieldssql->joins

                JOIN {question_usages} qu ON qu.id = r.questionusageid
                JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                JOIN {question} q ON q.id = qa.questionid
                JOIN {question_attempt_steps} qas ON qa.id = qas.questionattemptid";

        $this->sql->params = $userfieldssql->params;

        $this->sql->where = "r.questionusageid = :usageid";
        $this->sql->params['usageid'] = $this->usageid;

        if ($this->cm === null) {
            $this->sql->where .= "
                    AND (ctx.id = :contextid OR ctx.path LIKE :contextpathpattern)";
            $this->sql->params['contextid'] = $this->context->id;
            $this->sql->params['contextpathpattern'] = $this->context->path . '/%';
        } else {
            $this->sql->where .= "
                    AND r.contextid = :contextid";
            $this->sql->params['contextid'] = $this->context->id;
        }

        // Single user report.
        if ($this->userid) {
            $this->sql->where .= "
                AND r.userid = :userid";
            $this->sql->params['userid'] = $this->userid;
        }
    }
}
