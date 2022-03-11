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

namespace report_embedquestion\output;

use coding_exception;
use html_writer;
use moodle_url;
use plugin_renderer_base;
use question_display_options;
use report_embedquestion\utils;

/**
 * Defines the renderer for the report_embedquestion module.
 *
 * @package   report_embedquestion
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
     * Render the download heading.
     *
     * @param string $title Download title
     * @return string HTML string
     */
    public function download_heading(string $title): string {
        return $this->heading($title);
    }

    /**
     * Render the questions content of a particular attempt.
     *
     * @param \question_attempt $qa the question attempt to display.
     * @param \context $context the context the attempt belongs to.
     * @return string HTML string.
     */
    public function render_question_attempt_content(\question_attempt $qa, \context $context): string {

        $displayoptions = new question_display_options();
        $displayoptions->context = $context;
        $displayoptions->readonly = true;
        $displayoptions->manualcommentlink = question_display_options::HIDDEN;
        $displayoptions->history = question_display_options::VISIBLE;
        $displayoptions->flags = question_display_options::HIDDEN;

        // Adjust the display options before a question is rendered.
        $qa->get_behaviour()->adjust_display_options($displayoptions);

        // The question number is always 'Question 1' for embedded questions. So that, we have param '1'.
        return $qa->render($displayoptions, 1);
    }

    /**
     * Render attempt detail page.
     *
     * @param array $sumdata information about which attempt this is.
     * @param \question_attempt $qa the question attempt to display.
     * @param \context $context the context the attempt belongs to.
     * @return string HTML string.
     */
    public function render_attempt_detail(array $sumdata, \question_attempt $qa, \context $context): string {
        $output = '';
        $quizrenderer = $this->page->get_renderer('mod_quiz');
        $output .= $quizrenderer->review_summary_table($sumdata, 0);
        $output .= $this->render_question_attempt_content($qa, $context);
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


    /**
     *
     * Render show only link for given user and course.
     *
     * @param moodle_url $url
     * @return string
     * @throws \coding_exception
     */
    public function render_show_only_link(\moodle_url $url): string {

        $showonlystring = get_string('viewallattemptsforthisuser', 'report_embedquestion');
        return html_writer::span(\html_writer::link($url, get_string('showonly', 'report_embedquestion'),
                ['title' => $showonlystring, 'aria-label' => $showonlystring]), 'view-all');
    }

    /**
     * Render the delete attempt button.
     * @return string HTML string
     */
    public function render_delete_attempts_buttons() {
        $deletebuttonparams = [
                'type' => 'submit',
                'class' => 'btn btn-secondary mr-1',
                'id' => 'deleteattemptsbutton',
                'name' => 'delete',
                'value' => get_string('deleteselected', 'quiz_overview'),
                'data-action' => 'toggle',
                'data-togglegroup' => 'embed-attempts',
                'data-toggle' => 'action',
                'disabled' => true
        ];

        $this->page->requires->event_handler('#deleteattemptsbutton', 'click', 'M.util.show_confirm_dialog',
                ['message' => get_string('deleteattemptcheck', 'quiz')]);

        return html_writer::empty_tag('input', $deletebuttonparams);
    }

    /**
     * Render download response buttons.
     *
     * @return string HTML string
     */
    public function render_download_response_files(): string {
        $output = '';

        $output .= html_writer::start_div('download-response');

        $output .= html_writer::start_div('download-response-infomation');
        $output .= get_string('downloadresponseinfo', 'report_embedquestion');
        $output .= $this->output->help_icon('downloadresponseinfo', 'report_embedquestion', true);
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('download-response-buttons');

        $downloadselectbuttonparams = [
                'type' => 'submit',
                'class' => 'btn btn-secondary mr-1',
                'id' => 'downloadselectedattemptsbutton',
                'name' => 'downloadselect',
                'value' => get_string('downloadresponse_buttonselected', 'report_embedquestion'),
                'disabled' => true
        ];

        $downloadallbuttonparams = [
                'type' => 'submit',
                'class' => 'btn btn-secondary mr-1',
                'id' => 'downloadallattemptsbutton',
                'name' => 'downloadall',
                'value' => get_string('downloadresponse_buttonall', 'report_embedquestion'),
                'disabled' => true
        ];

        $output .= html_writer::empty_tag('input', $downloadallbuttonparams);
        $output .= html_writer::empty_tag('input', $downloadselectbuttonparams);
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();

        // We need custom logic for whether to enable or disable the button, based on which question types are selected.
        $this->page->requires->js_call_amd('report_embedquestion/download_responses', 'init');

        return $output;
    }

    /**
     * Render responses download link.
     *
     * @param array $params URL params
     * @return string HTML string
     */
    public function render_responses_download_links(array $params): string {
        $output = '';

        $output .= html_writer::start_div('cloudexport');
        $output .= html_writer::start_div('cloudexport-info-text');
        $output .= html_writer::span(get_string('downloadresponse_exportserviceinfo', 'report_embedquestion'));
        $output .= html_writer::end_div();

        $output .= $this->render_cloud_export_links($params);
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Render cloud export links.
     *
     * @param array $params URL params
     * @return string HTML string
     */
    public function render_cloud_export_links(array $params) {
        // Placeholder to add more cloud service link in the future like: Dropbox, Google Drive, One Drive.
        return $this->render_cloud_export_element($params, 'local');
    }

    /**
     * Render cloud export.
     *
     * @param array $params URL params
     * @param string $cloudtype Cloud type
     * @return string HTML string
     */
    public function render_cloud_export_element(array $params, string $cloudtype = 'local'): string {
        $output = '';

        if ($cloudtype != 'local') {
            // TODO: Remove this if we turn on the cloud service in the future.
            throw new coding_exception("Download type {$cloudtype} does not support");
        }

        $filesizes = number_format($params['size'] / 1048576, 1) . ' MB';
        $iconsrc = $this->output->image_url('t/download');
        $title = get_string('downloadresponse_downloadto_device', 'report_embedquestion');
        $link = new moodle_url('/report/embedquestion/responsedownload.php', array_merge($params, ['download' => 1]));

        $output .= html_writer::start_div('downloadexport');
        $output .= html_writer::empty_tag('img', ['src' => $iconsrc, 'alt' => $title]);
        $output .= html_writer::link($link, $title);
        $output .= html_writer::span($filesizes, 'downloadexport sizer');
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Render back to report link.
     *
     * @param moodle_url $reporturl Report url
     * @return string HTML string
     */
    public function render_back_to_report_link(moodle_url $reporturl): string {
        return html_writer::div(link_arrow_left(get_string('downloadresponse_backtoreport', 'report_embedquestion'), $reporturl),
                'back-link');
    }

    /**
     * Render show only heading and show everybody link.
     *
     * @param string $userinfo User info.
     * @param moodle_url $showeverybodyurl Show everybody url.
     * @return string HTML string.
     * @throws coding_exception
     */
    public function render_show_only_heading(string $userinfo, moodle_url $showeverybodyurl): string {
        $showeverybodyurl->remove_params('userid');
        $showeverybodystring = get_string('showeverybody', 'report_embedquestion');

        $output = html_writer::start_div('show-only-heading');
        $output .= html_writer::span(get_string('showonly_heading', 'report_embedquestion', $userinfo), 'student-info');
        $output .= html_writer::span(html_writer::link($showeverybodyurl, $showeverybodystring,
                ['title' => $showeverybodystring, 'aria-label' => $showeverybodystring]), 'show-all-link');
        $output .= html_writer::end_div();

        return $output;
    }

}
