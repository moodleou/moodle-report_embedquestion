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

require_once(__DIR__ . '/../lib.php');

/**
 * Tests for the Embedded questions progress Moodle core integration.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class report_embedquestion_lib_testcase extends \advanced_testcase {

    /**
     * @var \testing_data_generator
     */
    protected $generator;

    /**
     * @var \filter_embedquestion_generator
     */
    protected $attemptgenerator;

    /**
     * Setup
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->generator = $this->getDataGenerator();
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
    }

    /**
     * Tests extending the course-level navigation..
     */
    public function test_report_embedquestion_extend_navigation_course() {
        $this->setAdminUser();
        $node = new \navigation_node(['text' => 'Parent node']);
        $course = $this->generator->create_course();
        $context = \context_course::instance($course->id);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse', null, [], ['contextid' => $context->id]);
        $user = $this->generator->create_user();
        $this->generator->enrol_user($user->id, $course->id, 'student');

        // Verify that the Embedded questions progress link will not exist if there is no attempt yet.
        report_embedquestion_extend_navigation_course($node, $course, $context);
        $this->assertNotContains('embedquestionreport', $node->get_children_key_list());

        // Verify that the Embedded questions progress link will exist after the student has attempted.
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'False', $context);
        report_embedquestion_extend_navigation_course($node, $course, $context);
        $this->assertContains('embedquestionreport', $node->get_children_key_list());
        $reportnode = $node->find('embedquestionreport', \navigation_node::TYPE_SETTING);
        $this->assertEquals('Embedded questions progress', $reportnode->get_content());
        $this->assertEquals(new \moodle_url('/report/embedquestion/index.php',
                ['courseid' => $course->id]), $reportnode->action());
    }

    /**
     * Tests extending the course-level navigation..
     */
    public function test_report_embedquestion_extend_navigation_module() {
        $this->setAdminUser();
        $course = $this->generator->create_course();
        $coursecontext = \context_course::instance($course->id);
        $pagegenerator = $this->generator->get_plugin_generator('mod_page');
        $question = $this->attemptgenerator->create_embeddable_question('truefalse', null, [], ['contextid' => $coursecontext->id]);
        $activity = $pagegenerator->create_instance(['course' => $course,
                'content' => '<p>Try this question: ' . $this->attemptgenerator->get_embed_code($question) . '</p>']);
        $pagecontext = \context_module::instance($activity->cmid);
        $user = $this->generator->create_user();
        $this->generator->enrol_user($user->id, $course->id, 'student');
        $node = new \navigation_node(['text' => 'Parent node']);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        // Verify that the Embedded questions progress link will not exist if there is no attempt yet.
        report_embedquestion_extend_navigation_module($node, $cm);
        $this->assertNotContains('embedquestionreport', $node->get_children_key_list());

        // Verify that the Embedded questions progress link will exist after the student has attempted.
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'False', $pagecontext);
        report_embedquestion_extend_navigation_module($node, $cm);
        $this->assertContains('embedquestionreport', $node->get_children_key_list());
        $reportnode = $node->find('embedquestionreport', \navigation_node::TYPE_SETTING);
        $this->assertEquals('Embedded questions progress', $reportnode->get_content());
        $this->assertEquals(new \moodle_url('/report/embedquestion/activity.php',
                ['cmid' => $activity->cmid]), $reportnode->action());
    }

    public function test_report_embedquestion_questions_in_use_detects_question_in_use() {
        $user = $this->generator->create_user();
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'True');

        $this->assertTrue(questions_in_use([$question->id]));
    }

    public function test_report_embedquestion_questions_in_use_does_not_report_unattempted_question() {
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');

        $this->assertFalse(questions_in_use([$question->id]));
    }
}
