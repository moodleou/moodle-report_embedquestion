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
 * Unit test for the report_embedquestion latest_attempt_table methods.
 *
 * @package    report_embedquestion
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_embedquestion_latest_attempt_table_testcase extends \advanced_testcase {

    /** @var \testing_data_generator */
    protected $generator;

    /** @var \stdClass */
    protected $course;

    /** @var \context_course */
    protected $context;

    /** @var \mod_forum_generator */
    protected $forumgenerator;

    /** @var \filter_embedquestion_generator */
    protected $attemptgenerator;

    /** @var report_display_options */
    protected $displayoptions;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course();
        $this->forumgenerator = $this->generator->get_plugin_generator('mod_forum');
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
        $this->context = \context_course::instance($this->course->id);
        $this->displayoptions = new report_display_options($this->course->id, null);
    }

    public function test_latest_attempt_table_no_filter() {
        // Check sql query wuth no filter.
        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
    }

    public function test_latest_attempt_table_filter_lookback() {
        $now = time();
        $this->displayoptions->lookback = WEEKSECS * 3; // 3 weeks.
        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertArrayHasKey('lookback', $table->sql->params);
        $this->assertEqualsWithDelta($now - $this->displayoptions->lookback,
                $table->sql->params['lookback'], 1); // Allow 1s passing to prevent random fails.
    }

    public function test_latest_attempt_table_filter_dates() {
        $now = time();
        $this->displayoptions->datefrom = $now - (WEEKSECS * 4); // From 28 days ago.
        $this->displayoptions->dateto = $now - (DAYSECS * 6); // To 6 days ago.

        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);
        $expectedwhere = "
                AND qas.timecreated > :datefrom
                AND qas.timecreated < :dateto";
        $this->assertStringContainsString($expectedwhere, $table->sql->where);

        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertArrayHasKey('datefrom', $table->sql->params);
        $this->assertArrayHasKey('dateto', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertEquals($this->displayoptions->datefrom, $table->sql->params['datefrom']);
        $this->assertEquals($this->displayoptions->dateto + DAYSECS, $table->sql->params['dateto']);
    }

    public function test_latest_attempt_table_filter_datefrom() {
        $now = time();
        $this->displayoptions->datefrom = $now - (WEEKSECS * 4); // From 28 days ago.

        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);
        $expectedwhere = "AND qas.timecreated > :datefrom";
        $this->assertStringContainsString($expectedwhere, $table->sql->where);

        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertArrayHasKey('datefrom', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertEquals($this->displayoptions->datefrom, $table->sql->params['datefrom']);
    }

    public function test_latest_attempt_table_filter_dateto() {
        $now = time();
        $this->displayoptions->dateto = $now - (WEEKSECS * 2); // From 14 days ago.

        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);
        $expectedwhere = "AND qas.timecreated < :dateto";
        $this->assertStringContainsString($expectedwhere, $table->sql->where);
        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertArrayHasKey('dateto', $table->sql->params);
        $this->assertEquals($this->displayoptions->dateto + DAYSECS, $table->sql->params['dateto']);
    }

    /**
     * Test the latest_attempt_table with location filter.
     */
    public function test_latest_attempt_table_filter_locations() {
        $page1 = $this->generator->create_module('page', ['course' => $this->course->id, 'content' => '<p>Page 1: </p>']);
        $page2 = $this->generator->create_module('page', ['course' => $this->course->id, 'content' => '<p>Page 2: </p>']);
        $pagecontext1 = \context_module::instance($page1->cmid);
        $pagecontext2 = \context_module::instance($page2->cmid);

        $this->displayoptions->locationids = [$pagecontext1->id, $pagecontext2->id];

        $table = new latest_attempt_table($this->context, $this->course->id, null, $this->displayoptions);

        $keyparam1 = array_search($pagecontext1->id, $table->sql->params);
        $keyparam2 = array_search($pagecontext2->id, $table->sql->params);

        $this->assertStringContainsString('location', $keyparam1);
        $this->assertStringContainsString('location', $keyparam2);
        $this->assertArrayHasKey($keyparam1, $table->sql->params);
        $this->assertEquals($pagecontext1->id, $table->sql->params[$keyparam1]);
        $this->assertArrayHasKey($keyparam2, $table->sql->params);
        $this->assertEquals($pagecontext2->id, $table->sql->params[$keyparam2]);

        $expectedwhere = " AND r.contextid IN (:$keyparam1,:$keyparam2)";
        $this->assertStringContainsString($expectedwhere, $table->sql->where);
    }
}
