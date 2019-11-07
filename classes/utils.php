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
 * Helper functions for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion;
use html_writer;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../question/engine/datalib.php');

/**
 * Helper functions for report_embedquestion.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils
{
    /**
     * Used at the top of the drill-down for a single question. Give more info about the location.
     *
     * @param int $courseid, the course id
     * @param object $attempt the question attempt object
     * @return string
     */
    public static function get_embed_location_summary($courseid, $attempt) {
        global $USER;
        $context = html_writer::tag('span', get_string('attemptsummarycontext', 'report_embedquestion',
            \report_embedquestion\utils::get_activity_link($attempt)), ['class' => 'heading-context']);
        $questionlink = \report_embedquestion\utils::get_question_link($courseid, $attempt->questionid, $attempt->questiontype, $attempt->questionname);
        $questionlink = html_writer::tag('span', $questionlink, ['class' => 'heading-question']);

        // Print users link when the viewer is not the current user.
        $userlink = '';
        if ($USER->id !== $attempt->userid) {
            $userlink = \report_embedquestion\utils::get_user_link($courseid, $attempt->userid, $attempt->firstname . ' ' . $attempt->lastname);
            $userlink = html_writer::tag('span', get_string(
                'attemptsummaryby', 'report_embedquestion', $userlink), ['class' => 'heading-user']);
        }
        $heading = get_string('attemptsummaryfor', 'report_embedquestion');
        echo html_writer::tag('p', $heading . ' ' . $questionlink . ' | ' . $userlink . ' ' . $context);
    }

    /**
     * Return array of userfields
     * @param $context
     * @return array
     */
    public static function get_user_fields($context) {
        return array_unique(array_merge(['username'], get_all_user_name_fields(), get_extra_user_fields($context)));
    }

    /**
     * Return the question icon.
     *
     * @param string $qtype the question type string.
     * @return string HTML fragment.
     */
    public static function get_question_icon($qtype) {
        global $OUTPUT;
        $alt =  get_string('pluginname', 'qtype_' . $qtype);
        return $OUTPUT->pix_icon('icon', $alt, 'qtype_' . $qtype);
    }

    /**
     * @param $courseid
     * @param $userid
     * @param $username
     * @return string
     * @throws \moodle_exception
     */
    public static function get_user_link($courseid, $userid, $username) {
        $profileurl = new \moodle_url('/user/view.php', ['id' => $userid, 'course' => $courseid]);
        return html_writer::link($profileurl, $username);
    }

    /**
     * @param $courseid
     * @param $questionid
     * @param $qtype
     * @param $questionname
     * @return string
     */
    public static function get_question_link($courseid, $questionid, $qtype, $questionname) {
        global $CFG;
        $url = new moodle_url($CFG->wwwroot . '/question/preview.php', ['id' => $questionid, 'courseid' => $courseid]);
        $previewlink = '';
        if (question_has_capability_on($questionid, 'view')) {
            $previewlink = ' (' . html_writer::link($url, get_string('preview') . ')');
        }
        $icon = null;
        if ($qtype) {
            $icon =  self::get_question_icon($qtype);
        }
        return html_writer::tag('span', $icon . $questionname . $previewlink, []);
    }

    /**
     * Return an appropriate icon (green tick, red cross, etc.) for a grade.
     * @param float $fraction grade on a scale 0..1.
     * @return string html fragment.
     */
    public static function get_icon_for_question_state(string $state) {
        global $OUTPUT;

        $icon = 'i/grade_';
        if ($state === 'gradedright') {
            $correctness = 'correct';
        } else if ($state === 'gradedpartial') {
            $correctness = 'partiallycorrect';
        } else if ($state === 'gradedwrong') {
            $correctness = 'incorrect';
        } else {
            $correctness = null;
        }

        if ($correctness === null) {
            return \question_state::get($state)->default_string(true);
        }
        return $OUTPUT->pix_icon('i/grade_' . $correctness,
            get_string($correctness, 'question'), 'moodle', array('class' => 'icon'));
    }

    /**
     * Return the activity link.
     * @param $attempt
     * @return string
     */
    public static function get_activity_link($attempt) {
        global $CFG;
        $url = \html_writer::link($CFG->wwwroot . $attempt->pageurl . '#' . $attempt->embedid, $attempt->pagename);
        return $url;
    }

    /**
     * Return attempt summary link.
     * @param object $attempt, the attempt object
     * @param int $usageid, the question usageid
     * @return string
     */
    public static function get_attempt_summary_link($attempt, $usageid) {
        global $CFG;
        $id = explode('=', $attempt->pageurl)[1];
        if (explode(':', $attempt->pagename)[0] === 'Course') {
            $url = $CFG->wwwroot . '/report/embedquestion/index.php?courseid=' . $id;
        } else {
            $paramstring = '?cmid=' . $id;
            $url = $CFG->wwwroot . '/report/embedquestion/activity.php?cmid=' . $id;
        }
        $options = [
            'userid' => $attempt->userid,
            'usageid' => $attempt->questionusageid,
        ];
        $paramstring = '';
        foreach ($options as $key => $option) {
            $paramstring .= '&' . $key . '=' . $option;
        }

        $formatteddate = userdate($attempt->questionattemptsteptime);

        $url = \html_writer::link($url . $paramstring,  $formatteddate);
        if ($usageid > 0) {
            return  $formatteddate;
        }
        return $url;
    }

    /**
     * Return formatted grade base on greade_decimalpoints setting.
     * @param int $courseid
     * @param float $fraction
     * @param float $amaxmark
     * @return string
     */
    public static function get_grade($courseid, $fraction, $amaxmark) {
        global $CFG;
        $decimalpoints = grade_get_setting($courseid, 'decimalpoints', $CFG->grade_decimalpoints);
        $grade = format_float($fraction, $decimalpoints);
        $maxgrade = format_float($amaxmark, $decimalpoints);
        return $grade . '/' . $maxgrade;
    }
}
