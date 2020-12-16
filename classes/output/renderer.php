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
 * Defines the renderer for the report_embedquestion module.
 *
 * @package   report_embedquestion
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use plugin_renderer_base;
use question_display_options;
use report_embedquestion\utils;

/**
 * The renderer for the report_embedquestion module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the report heading.
     *
     * @param string $title Report title
     * @return string HTML string
     */
    public function report_heading(string $title): string {
        return $this->heading($title);
    }

    /**
     * Render the questions content of a particular attempt.
     *
     * @param $attemptobj
     * @return string HTML string.
     */
    public function render_question_attempt_content($attemptobj): string {

        $displayoptions = new question_display_options();
        $displayoptions->readonly = true;
        $displayoptions->manualcommentlink = question_display_options::HIDDEN;
        $displayoptions->history = question_display_options::VISIBLE;
        $displayoptions->flags = question_display_options::HIDDEN;

        // Adjust the display options before a question is rendered.
        $attemptobj->get_behaviour()->adjust_display_options($displayoptions);

        // The question number is always 'Question 1' for embedded questions. So that, we have param '1'.
        return $attemptobj->render($displayoptions, 1);
    }

    /**
     * Render attempt detail page.
     *
     * @param array $sumdata
     * @param $attemptobj
     * @return string HTML string.
     */
    public function render_attempt_detail(array $sumdata, $attemptobj): string {
        $output = '';
        $quizrenderer = $this->page->get_renderer('mod_quiz');
        $output .= $quizrenderer->review_summary_table($sumdata, 0);
        $output .= $this->render_question_attempt_content($attemptobj);
        $output .= $this->output->close_window_button(get_string('closeattemptview', 'report_embedquestion'));
        return $output;
    }

    /**
     * Render page navigation.
     *
     * @return string HTML string.
     */
    public function render_attempt_navigation(): string {
        return html_writer::div($this->output->navbar(), 'breadcrumb-nav');
    }

    /**
     * Render grade link to go to detail page.
     *
     * @param $attempt
     * @param $cmid
     * @param $courseid
     * @return string HTML string.
     */
    public function render_grade_link($attempt, $cmid, $courseid): string {
        $url = new moodle_url('/report/embedquestion/attemptdetail.php',
                ['cmid' => $cmid,
                        'attempt' => $attempt->questionattemptid]);

        $attempturl = html_writer::link(
                $url, utils::get_grade($courseid, $attempt->fraction, $attempt->maxmark),
                ['target' => '_blank',
                        'title' => get_string('attempt-detail-page', 'report_embedquestion')]);

        return $attempturl;
    }

}
