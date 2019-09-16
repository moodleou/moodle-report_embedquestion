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
 * Tests for the Embedded questions progress Moodle core integration.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');


/**
 * Tests for the Embedded questions progress Moodle core integration.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class report_embedquestion_lib_testcase extends advanced_testcase {

    /**
     * Tests extending the course-level navigation..
     */
    public function test_report_embedquestion_extend_navigation_course() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $node = new navigation_node(['text' => 'Parent node']);
        $course = $generator->create_course();
        $context = context_course::instance($course->id);

        report_embedquestion_extend_navigation_course($node, $course, $context);

        // TODO, in due course, we will need to verify the logic that the link only shows if there are embedded questions.

        $this->assertContains('embedquestionreport', $node->get_children_key_list());
        $reportnode = $node->find('embedquestionreport', navigation_node::TYPE_SETTING);
        $this->assertEquals('Embedded questions progress', $reportnode->get_content());
        $this->assertEquals(new moodle_url('/report/embedquestion/index.php',
                ['courseid' => $course->id]), $reportnode->action());
    }

    /**
     * Tests extending the course-level navigation..
     */
    public function test_report_embedquestion_extend_navigation_module() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $pagegenerator = $generator->get_plugin_generator('mod_page');
        $activity = $pagegenerator->create_instance(['course' => $course]);
        $node = new navigation_node(['text' => 'Parent node']);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);

        report_embedquestion_extend_navigation_module($node, $cm);

        // TODO, in due course, we will need to verify the logic that the link only shows if there are embedded questions.

        $this->assertContains('embedquestionreport', $node->get_children_key_list());
        $reportnode = $node->find('embedquestionreport', navigation_node::TYPE_SETTING);
        $this->assertEquals('Embedded questions progress', $reportnode->get_content());
        $this->assertEquals(new moodle_url('/report/embedquestion/activity.php',
                ['cmid' => $activity->cmid]), $reportnode->action());
    }
}
