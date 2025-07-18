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

use action_link;
use context;
use filter_embedquestion\embed_id;
use html_writer;
use moodle_url;
use user_picture;
use stdClass;

/**
 * Helper functions for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Used at the top of the drill-down for a single question. Give more info about the location.
     *
     * @param int $courseid the course id
     * @param stdClass $attempt the question attempt object
     */
    public static function get_embed_location_summary(int $courseid, stdClass $attempt) {
        global $USER;
        $context = html_writer::tag('span', get_string('attemptsummarycontext', 'report_embedquestion',
                self::get_activity_link($attempt)), ['class' => 'heading-context']);
        $questionlink = self::get_question_link($courseid, $attempt->questionid,
                $attempt->questiontype, $attempt->questionname);
        $questionlink = html_writer::tag('span', $questionlink, ['class' => 'heading-question']);

        // Print users link when the viewer is not the current user.
        $userlink = '';
        if ($USER->id !== $attempt->userid) {
            $userlink = self::get_user_link($courseid, $attempt->userid, fullname($attempt));
            $userlink = html_writer::tag('span', get_string(
                    'attemptsummaryby', 'report_embedquestion', $userlink), ['class' => 'heading-user']);
        }
        $heading = get_string('attemptsummaryfor', 'report_embedquestion');
        echo html_writer::tag('p', $heading . ' ' . $questionlink . ' | ' . $userlink . ' ' . $context);
    }

    /**
     * Check that the user can see the report, and throw an exception if not.
     *
     * @param context $context the report context.
     * @param int $userid if not zero, the report being shown only has data for this user.
     * @return int if this report should only show data for one user, that userid, otherwise 0
     * @throws \required_capability_exception if access is not allowed.
     */
    public static function require_report_permissions(context $context, int $userid): int {
        global $USER;
        if (!has_capability('report/embedquestion:viewallprogress', $context)) {
            require_capability('report/embedquestion:viewmyprogress', $context);
            if ($userid === 0) {
                $userid = $USER->id;
            }
            if ($userid != $USER->id) {
                // At this point, we know this next check will fail, but it generates the error message we want.
                require_capability('report/viewallprogress:viewmyprogress', $context);
            }
        }

        return $userid;
    }

    /**
     * Check that the given usage id exists, and belongs to the given user.
     *
     * @param int $usageid
     * @param int $userid
     * @throws \coding_exception if the usage does not exist.
     */
    public static function validate_usageid(int $usageid, int $userid): void {
        global $DB;

        if (!$usageid) {
            return; // Usage id not specified is always fine.
        }

        if (!$userid) {
            throw new \coding_exception('If usage id is set, user id must also be set.');
        }

        if (!$DB->record_exists_sql("
                SELECT 1
                  FROM {question_usages} qu
                  JOIN {report_embedquestion_attempt} r ON r.questionusageid = qu.id
                 WHERE qu.id = ?
                   AND r.userid = ?
                ", [$usageid, $userid])) {
            throw new \coding_exception('Unknown usage id.');
        }
    }

    /**
     * Return the question icon.
     *
     * @param string $qtype the question type string.
     * @return string HTML fragment.
     */
    public static function get_question_icon($qtype) {
        global $OUTPUT;
        return $OUTPUT->pix_icon('icon', get_string('pluginname', 'qtype_' . $qtype), 'qtype_' . $qtype);
    }

    /**
     * Get a link to a user's profile.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $username
     * @return string
     */
    public static function get_user_link(int $courseid, int $userid, string $username): string {
        $profileurl = new \moodle_url('/user/view.php', ['id' => $userid, 'course' => $courseid]);
        return html_writer::link($profileurl, $username);
    }

    /**
     * Return a link to a question, with an icon and preview link if appropriate.
     *
     * @param int $courseid
     * @param int $questionid
     * @param string|null $qtype
     * @param string $questionname
     * @return string
     */
    public static function get_question_link(int $courseid, int $questionid, ?string $qtype, string $questionname): string {

        $previewlink = '';
        if (question_has_capability_on($questionid, 'view')) {
            if (class_exists('\qbank_previewquestion\helper')) {
                $url = \qbank_previewquestion\helper::question_preview_url($questionid);
            } else {
                $url = question_preview_url($questionid);
            }
            $previewlink = ' (' . html_writer::link($url, get_string('preview'), ['target' => '_blank']) . ')';
        }

        $icon = '';
        if ($qtype) {
            $icon = self::get_question_icon($qtype);
        }

        return html_writer::span($icon . $questionname . $previewlink);
    }

    /**
     * Return an appropriate icon (green tick, red cross, etc.) for a grade.
     *
     * @param string $state the string such as 'gradedright', 'gradedwrong', 'gradedpartial'.
     * @return string html fragment.
     */
    public static function get_question_state(string $state) {
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
            global $CFG;
            require_once($CFG->dirroot . '/question/engine/states.php');
            return \question_state::get($state)->default_string(true);
        }

        $output = '';

        $output .= html_writer::start_span('que correctness ' . $correctness);
        $output .= get_string($correctness, 'question');
        $output .= $OUTPUT->pix_icon($icon . $correctness,
                get_string($correctness, 'question'), 'moodle', ['class' => 'state-icon']);
        $output .= html_writer::end_span();

        return $output;
    }

    /**
     * Return the activity link.
     * @param stdClass $attempt row of data from the queries used to build the report tables.
     * @return string HTML activity link.
     */
    public static function get_activity_link(stdClass $attempt): string {
        $url = new moodle_url($attempt->pageurl);
        $url->set_anchor(embed_id::create_from_string($attempt->embedid)->to_html_id());
        return \html_writer::link($url->out(), $attempt->pagename);;
    }

    /**
     * Return attempt summary link.
     *
     * @param stdClass $attempt row of data from the queries used to build the report tables.
     * @return string Question attempt time or attempt summary link.
     */
    public static function get_attempt_summary_link(stdClass $attempt): string {
        if ($attempt->contextlevel == CONTEXT_COURSE) {
            $url = new moodle_url('/report/embedquestion/index.php', ['courseid' => $attempt->instanceid]);
        } else {
            $url = new moodle_url('/report/embedquestion/activity.php', ['cmid' => $attempt->instanceid]);
        }
        $url->param('userid', $attempt->userid);
        $url->param('usageid', $attempt->questionusageid);

        return \html_writer::link($url, userdate($attempt->questionattemptsteptime),
            ['title' => get_string('attemptsummary', 'report_embedquestion')]);
    }

    /**
     * Return formatted grade base on greade_decimalpoints setting.
     * @param int $courseid
     * @param float $fraction
     * @param float $maxmark
     * @return string
     */
    public static function get_grade($courseid, $fraction, $maxmark) {
        global $CFG;
        $decimalpoints = grade_get_setting($courseid, 'decimalpoints', $CFG->grade_decimalpoints);
        $grade = format_float($fraction * $maxmark, $decimalpoints);
        $maxgrade = format_float($maxmark, $decimalpoints);
        return $grade . '/' . $maxgrade;
    }

    /**
     * Display dataformt selector's form for latest_attempt_table to be downloadable,
     * so that users who can view this report are able to download the latest attempt table.
     *
     * @param latest_attempt_table $table the table object
     * @param string $title the report title
     * @param context $context the report context object
     */
    public static function allow_downloadability_for_attempt_table(latest_attempt_table $table,
            string $title, context $context): void {
        if (has_capability('report/embedquestion:viewallprogress', $context) ||
            has_capability('report/embedquestion:viewmyprogress', $context)) {
            $download = optional_param('download', '', PARAM_ALPHA);
            $table->is_downloading($download);
        }
    }

    /**
     * Retun a moodle_url.
     * @param array|null $params
     * @param string $type php file name without extension ('index', 'activity')
     * @return moodle_url
     * @throws \moodle_exception
     */
    public static function get_url($params, $type = 'index') {
        global $CFG;
        return new moodle_url($CFG->wwwroot . "/report/embedquestion/$type.php", $params);
    }

    /**
     * Calculate summary information of a particular attempt.
     *
     * @param int $courseid
     * @param \question_attempt $attemptobj
     * @return array List of summary information.
     */
    public static function prepare_summary_attempt_information($courseid, $attemptobj): array {
        $sumdata = [];

        $question = $attemptobj->get_question();
        $student = \core_user::get_user($attemptobj->get_last_step()->get_user_id(),
                'id, picture, imagealt, firstname, lastname, firstnamephonetic, ' .
                'lastnamephonetic, middlename, alternatename, email');
        $userpicture = new user_picture($student);
        $userpicture->courseid = $courseid;
        $sumdata['user'] = [
                'title' => $userpicture,
                'content' => new action_link(new moodle_url('/user/view.php',
                        ['id' => $student->id]), fullname($student, true)),
        ];
        $sumdata['question'] = [
                'title' => get_string('question', 'moodle'),
                'content' => $question->name,
        ];
        $sumdata['completedon'] = [
                'title' => get_string('completedon', 'mod_quiz'),
                'content' => userdate($attemptobj->timemodified),
        ];

        return $sumdata;
    }

    /**
     * Get a suitable page title.
     *
     * @param context $context
     * @return string the string title.
     */
    public static function get_title(context $context): string {
        return get_string($context->contextlevel == CONTEXT_COURSE ? 'coursereporttitle' : 'activityreporttitle',
                'report_embedquestion', $context->get_context_name(false, false));
    }

    /**
     * Check that user can see the report.
     *
     * @param int $contextid Context id
     * @return bool
     */
    public static function user_can_see_report(int $contextid): bool {
        return attempt_tracker::user_has_attempt($contextid);
    }

    /**
     * Get user detail with identity fields.
     *
     * @param int $userid
     * @param context $context
     * @return array User object and extra fields value.
     */
    public static function get_user_details(int $userid, context $context): array {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        $user = \core_user::get_user($userid);
        profile_load_data($user);

        // Process display of user identity fields.
        $info = [];
        foreach (\core_user\fields::get_identity_fields($context) as $extrauserfield) {
            if (!empty($user->$extrauserfield)) {
                $info[] = $user->$extrauserfield;
            }
        }

        return [$user, $info];
    }

    /**
     *
     * Set navbar in the embedded report.
     *
     * @param string $title
     * @param context $context
     * @param string $showonly
     */
    public static function set_report_navbar(string $title, context $context, string $showonly = ''): void {
        global $PAGE;

        if ($context->contextlevel == CONTEXT_MODULE) {
            $PAGE->navbar->add($title, new moodle_url('/report/embedquestion/activity.php', ['cmid' => $context->instanceid]));
        } else if ($context->contextlevel == CONTEXT_COURSE && !empty($showonly)) {
            $PAGE->navbar->add($title, new moodle_url('/report/embedquestion/index.php', ['courseid' => $context->instanceid]));
        } else {
            $PAGE->navbar->add($title);
        }
        if (!empty($showonly)) {
            $PAGE->navbar->add($showonly);
        }
    }

    /**
     * Display a user's name along with their identity field values (if any).
     *
     * @param stdClass $user user object.
     * @param array $info identity field values to show.
     * @return string for display (not escaped).
     */
    public static function get_user_display(stdClass $user, array $info): string {
        if ($info) {
            return get_string('crumbtrailembedquestiondetail', 'report_embedquestion',
                    ['fullname' => fullname($user), 'info' => implode(', ', $info)]);
        } else {
            return fullname($user);
        }
    }

    /**
     * We ignore various internal question states that aren't meaningful to users.
     *
     * @param \question_state $state a question state.
     * @return bool whether to ignore.
     */
    protected static function is_ignored_question_state(\question_state $state): bool {
        // We can't get default status string for those state, so skip them.
        return $state === \question_state::$notstarted || $state === \question_state::$unprocessed;
    }

    /**
     * Get the options to show in the 'Latest state' field of the filter form.
     *
     * @return array choices for the select menu. All, then the recognised question states.
     */
    public static function get_question_state_filter_options(): array {
        $options = [report_display_options::LAST_ATTEMPT_STATUS_ALL => get_string('choosedots')];

        foreach (\question_state::get_all() as $state) {
            if (self::is_ignored_question_state($state)) {
                continue;
            }

            $statestring = $state->default_string(true);
            // Use the same for key and value, mainly so get_question_states_for_filter_option can work.
            $options[$statestring] = $statestring;
        }

        return $options;
    }

    /**
     * Several underlying question states may relate to one option in the dropdown. Get them all.
     *
     * @param string $wantedstate the state selected in the dropdown.
     * @return array the names of the underlying question_states.
     */
    public static function get_question_states_for_filter_option(string $wantedstate): array {
        $states = [];
        foreach (\question_state::get_all() as $state) {
            if (self::is_ignored_question_state($state)) {
                continue;
            }

            $statestring = $state->default_string(true);
            if ($statestring === $wantedstate) {
                $states[] = (string) $state;
            }
        }

        return $states;
    }

    /**
     * Get the options to show in the 'Question type' field of the filter form.
     *
     * @return array Choices for the select menu. All, then the recognised question types.
     */
    public static function get_qtype_names_filter_options(): array {
        $options = [report_display_options::LAST_ATTEMPT_STATUS_ALL => get_string('choosedots')];
        $createabletypes = \question_bank::get_creatable_qtypes();

        foreach ($createabletypes as $qtypename => $qtype) {
            $options[$qtypename] = $qtype->local_name();
        }

        return $options;
    }

    /**
     * Get file path of the file from temp directory.
     *
     * @param string $fullname name of the file include extension.E.g: sample.zip
     * @return string full directory path of the file.
     */
    public static function get_file_path_from_temporary_dir(string $fullname): string {
        return make_temp_directory('reportembedquestiontemp') . '/' . $fullname;
    }
}
