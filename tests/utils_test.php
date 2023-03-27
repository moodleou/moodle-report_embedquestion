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

/**
 * Unit tests for the util methods.
 *
 * @package    report_embedquestion
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils_test extends \advanced_testcase {

    /** @var \testing_data_generator */
    protected $generator;

    /** @var \stdClass */
    protected $course;

    /** @var \mod_forum_generator */
    protected $forumgenerator;

    /** @var \filter_embedquestion_generator */
    protected $attemptgenerator;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course();
        $this->forumgenerator = $this->generator->get_plugin_generator('mod_forum');
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
    }

    public function test_get_activity_link () {
        global $DB;
        $pagegen = $this->generator->get_plugin_generator('mod_page');
        $page1 = $pagegen->create_instance(['course' => $this->course]);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $catid = $DB->get_field('question_categories', 'idnumber', ['id' => $question->category]);
        $cmid = $page1->id;

        $attempt = new \stdClass();
        $attempt->pagename = $this->course->shortname . ':' . $page1->name;
        $attempt->pageurl = '/mod/page/view.php?id=' . $page1->id;
        $attempt->embedid = $catid . '/' . $question->idnumber;
        $embedid = $attempt->embedid;
        $expected = "<a href=\"https://www.example.com/moodle/mod/page/view.php?id=$cmid#$embedid\">$attempt->pagename</a>";
        $actual = utils::get_activity_link($attempt);
        $this->assertEquals($expected, $actual);
    }

    public function test_get_user_link() {
        $courseid = $this->course->id;
        $user = $this->generator->create_user();
        $userid = $user->id;
        $username = $user->username;
        $expected = "<a href=\"https://www.example.com/moodle/user/view.php?id=$userid&amp;course=$courseid\">$username</a>";
        $actual = utils::get_user_link($this->course->id, $user->id, $user->username);
        $this->assertEquals($expected, $actual);
    }

    public function test_get_grade() {
        global $CFG;
        $courseid = $this->course->id;
        $fraction = 0.6666667;
        $amaxmark = 1.0000000;
        $decimalpoints = grade_get_setting($courseid, 'decimalpoints', $CFG->grade_decimalpoints);
        if ($decimalpoints == 2) { // Default decimal point.
            $actual = utils::get_grade($courseid, $fraction, $amaxmark);
            $this->assertEquals('0.67/1.00', $actual);
        }
    }

    public function test_get_url() {
        global $CFG;
        $params = ['courseid' => $this->course->id];
        $expected = new \moodle_url($CFG->wwwroot . "/report/embedquestion/index.php", $params);
        $actual = utils::get_url($params);
        $this->assertEquals($expected, $actual);

        $forum1 = $this->forumgenerator->create_instance(['course' => $this->course]);
        $params = ['cmid' => $forum1->id];
        $expected = new \moodle_url($CFG->wwwroot . "/report/embedquestion/activity.php", $params);
        $actual = utils::get_url($params, 'activity');
        $this->assertEquals($expected, $actual);
    }

    public function test_get_filter_data() {
        $expected = new \stdClass();
        $expected->locationids = [];
        $expected->lookback = 0;
        $expected->datefrom = 0;
        $expected->dateto = 0;
        $expected->pagesize = report_display_options::DEFAULT_REPORT_PAGE_SIZE;
        $expected->lastattemptstatus = report_display_options::LAST_ATTEMPT_STATUS_ALL;
        $expected->questiontype = report_display_options::QUESTION_TYPE_ALL;

        $displayoptions = new report_display_options($this->course->id, null);
        $actual = $displayoptions->get_initial_form_data();
        $this->assertEquals($expected, $actual);
    }

    public function test_get_question_state_filter_options() {
        $result = utils::get_question_state_filter_options();
        $this->assertArrayHasKey(\question_state::$gradedright->default_string(true), $result);
        $this->assertArrayHasKey(\question_state::$complete->default_string(true), $result);
    }

    public function test_get_question_states_for_filter_option() {
        $this->assertEquals(
                [(string) \question_state::$gradedright, (string) \question_state::$mangrright],
                utils::get_question_states_for_filter_option(\question_state::$gradedright->default_string(true)));

        $this->assertEquals(
                [(string) \question_state::$complete],
                utils::get_question_states_for_filter_option(\question_state::$complete->default_string(true)));
    }

    public function test_get_qtype_names_filter_options() {
        // This test only works if a static cache question_bank::$questionconfig had not yet been populated.
        // This cache is private, and there is no API to reset it, so we have to use reflection.
        $ref = new \ReflectionProperty(\question_bank::class, 'questionconfig');
        $ref->setAccessible(true);
        $ref->setValue(null);

        set_config('multichoice' . '_disabled', 1, 'question');
        $expected = utils::get_qtype_names_filter_options();
        $this->assertArrayNotHasKey('multichoice', $expected);
    }

    /**
     * Test get attempt summary link function.
     *
     * @dataProvider test_get_attempt_summary_link_provider
     * @covers \util::get_attempt_summary_link
     *
     * @param object $attempt The attempt object.
     * @param string $expectedurl The expected url link.
     */
    public function test_get_attempt_summary_link(object $attempt, string $expectedurl) {
        $this->setTimezone('UTC');
        $actual = utils::get_attempt_summary_link($attempt);
        $this->assertEquals($expectedurl, $actual);
    }

    /**
     * Data provider for test_get_attempt_summary_link() test cases.
     *
     * @coversNothing
     * @return array List of data sets (test cases)
     */
    public function test_get_attempt_summary_link_provider() {
        return [
            'Course' => [
                (object) [
                    'pageurl' => '/course/view.php?id=4',
                    'contextlevel' => CONTEXT_COURSE,
                    'userid' => '3',
                    'instanceid' => '4',
                    'questionusageid' => '470',
                    'questionattemptsteptime' => 1675326072,
                ],
                '<a title="Attempt summary" href=' .
                    '"https://www.example.com/moodle/report/embedquestion/index.php?courseid=4&amp;userid=3&amp;usageid=470">' .
                        'Thursday, 2 February 2023, 8:21 AM</a>',
            ],
            'Activity' => [
                (object) [
                    'pageurl' => '/mod/page/view.php?id=30',
                    'contextlevel' => CONTEXT_MODULE,
                    'userid' => '4',
                    'instanceid' => '30',
                    'questionusageid' => '477',
                    'questionattemptsteptime' => 1675241978,
                ],
                '<a title="Attempt summary" href=' .
                    '"https://www.example.com/moodle/report/embedquestion/activity.php?cmid=30&amp;userid=4&amp;usageid=477">' .
                        'Wednesday, 1 February 2023, 8:59 AM</a>',
            ],
        ];
    }

    /**
     * Test get the correct file path of the zip for report.
     *
     * @covers \util::get_file_path_from_temporary_dir
     */
    public function test_get_file_path_from_temporary_dir() {
        global $CFG;
        $actual = utils::get_file_path_from_temporary_dir('sample' . '.zip');
        $this->assertEquals($CFG->tempdir . '/reportembedquestiontemp/sample.zip', $actual);
    }
}
