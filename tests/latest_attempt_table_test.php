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
 * Unit test for the report_embedquestion latest_attempt_table methods.
 *
 * @package    report_embedquestion
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../classes/latest_attempt_table.php');
use report_embedquestion\latest_attempt_table;
use report_embedquestion\utils;


/**
 * Unit tests for the latest_attempt_table methods.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_embedquestion_latest_attempt_table_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course();
        $this->forumgenerator = $this->generator->get_plugin_generator('mod_forum');
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
        $this->context = \context_course::instance($this->course->id);
    }

    public function test_latest_attempt_table_no_filter() {
        // Check sql query wuth no filter.
        $table = new latest_attempt_table($this->context, $this->course->id, 0, null, null);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
    }

    public function test_latest_attempt_table_filter_lookback() {
        $now = time();
        $filter = new stdclass();
        $filter->lookback = WEEKSECS * 3; // 3 weeks.
        $filter->datefrom = 0;
        $filter->dateto = 0;
        $table = new latest_attempt_table($this->context, $this->course->id, 0, null, $filter);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertArrayHasKey('lookback', $table->sql->params);
        $this->assertEquals($now - $filter->lookback, $table->sql->params['lookback']);
    }

    public function test_latest_attempt_table_filter_dates() {
        $now = time();
        $filter = new stdclass();
        $filter->lookback = 0;
        $filter->datefrom = $now - (WEEKSECS * 4); // From 28 days ago.
        $filter->dateto = $now - (DAYSECS * 6); // To 6 days ago.

        $table = new latest_attempt_table($this->context, $this->course->id, 0, null, $filter);
        $contextid = $this->context->id;
        $expectedwhere = " (r.contextid = :contextid
     OR cxt.path LIKE '%/$contextid/%')
     AND (qas.timecreated > :datefrom AND qas.timecreated < :dateto)";
        $this->assertContains($expectedwhere, $table->sql->where);

        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertArrayHasKey('datefrom', $table->sql->params);
        $this->assertArrayHasKey('dateto', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertEquals($filter->datefrom, $table->sql->params['datefrom']);
        $this->assertEquals($filter->dateto + DAYSECS, $table->sql->params['dateto']);
    }

    public function test_latest_attempt_table_filter_datefrom() {
        $now = time();
        $filter = new stdclass();
        $filter->lookback = 0;
        $filter->datefrom = $now - (WEEKSECS * 4); // From 28 days ago.
        $filter->dateto = 0;

        $table = new latest_attempt_table($this->context, $this->course->id, 0, null, $filter);
        $contextid = $this->context->id;
        $expectedwhere = " (r.contextid = :contextid
     OR cxt.path LIKE '%/$contextid/%')
     AND qas.timecreated > :datefrom";
        $this->assertContains($expectedwhere, $table->sql->where);

        $table = new latest_attempt_table($this->context, $this->course->id, 0, null, $filter);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertArrayHasKey('datefrom', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertEquals($filter->datefrom, $table->sql->params['datefrom']);
    }

    public function test_latest_attempt_table_filter_dateto() {
        $now = time();
        $filter = new stdclass();
        $filter->lookback = 0;
        $filter->datefrom = 0;
        $filter->dateto = $now - (WEEKSECS * 2); // From 14 days ago.

        $table = new latest_attempt_table($this->context, $this->course->id, 0, null, $filter);
        $contextid = $this->context->id;
        $expectedwhere = " (r.contextid = :contextid
     OR cxt.path LIKE '%/$contextid/%')
     AND qas.timecreated < :dateto";
        $this->assertContains($expectedwhere, $table->sql->where);
        $table = new latest_attempt_table($this->context, $this->course->id, 0, null, $filter);
        $this->assertArrayHasKey('contextid', $table->sql->params);
        $this->assertEquals($this->context->id, $table->sql->params['contextid']);
        $this->assertArrayHasKey('dateto', $table->sql->params);
        $this->assertEquals($filter->dateto + DAYSECS, $table->sql->params['dateto']);
    }
}
