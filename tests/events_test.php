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
 * Tests for the Embedded questions progress events.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class for tests related to this plugins log events.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class report_embedquestion_events_testcase extends advanced_testcase {

    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    /**
     * Test cases for the course report viewed event tests.
     *
     * @return array test cases.
     */
    public function course_report_viewed_cases(): array {
        // 2 is admin user. 1 is guest. At this point in PHP unit, $USER is not set.
        // CID will get replaced by course id in the test.
        return [
                'view all users report' => [0, 0, [],
                        "The user with id '2' viewed the embedded questions progress " .
                        "for the course with id 'CID'."],
                'view group report' => [0, 1, ['groupid' => 1],
                        "The user with id '2' viewed the embedded questions progress " .
                        "of the group with id '1' for the course with id 'CID'."],
                'view other user report' => [1, 0, ['userid' => 1],
                        "The user with id '2' viewed the embedded questions progress " .
                        "of the user with id '1' for the course with id 'CID'."],
                'view own user report' => [2, 0, ['userid' => 2],
                        "The user with id '2' viewed their embedded questions progress " .
                        "for the course with id 'CID'."],
        ];
    }

    /**
     * Test the course report viewed event.
     *
     * @dataProvider course_report_viewed_cases
     *
     * @param int $relateduserid user whose report was viewed, or 0.
     * @param int $groupid group whose report was viewed, or 0.
     * @param array $extraurlparams expected extra params to see in the URL.
     * @param string $expecteddescription expected description.
     */
    public function test_course_report_viewed(int $relateduserid, int $groupid,
                array $extraurlparams, string $expecteddescription) {

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        // Trigger event for log report viewed.
        $event = \report_embedquestion\event\course_report_viewed::create([
                'context' => $context,
                'relateduserid' => $relateduserid,
                'other' => ['groupid' => $groupid],
            ]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf('\report_embedquestion\event\course_report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEventContextNotUsed($event);
        $this->assertEquals(new moodle_url('/report/embedquestion/index.php', ['courseid' => $course->id] + $extraurlparams),
                $event->get_url());
        $this->assertEquals(str_replace('CID', $course->id, $expecteddescription),
                $event->get_description());
    }

    /**
     * Test the course report viewed event has to have relateduserid set.
     */
    public function test_course_report_viewed_related_user_validation() {
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $this->expectException('coding_exception');
        \report_embedquestion\event\course_report_viewed::create([
                'context' => $context,
                'other' => ['groupid' => 0],
        ]);
    }

    /**
     * Test the course report viewed event has to have groupid set.
     */
    public function test_course_report_viewed_groupid_validation() {
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $this->expectException('coding_exception');
        \report_embedquestion\event\course_report_viewed::create([
                'context' => $context,
                'relateduserid' => 0,
                'other' => [],
        ]);
    }

    /**
     * Test the course report viewed event has to have the right sort of context.
     */
    public function test_course_report_viewed_context_validation() {
        $course = $this->getDataGenerator()->create_course();
        $context = context_system::instance();

        $this->expectException('coding_exception');
        \report_embedquestion\event\course_report_viewed::create([
                'context' => $context,
                'relateduserid' => 0,
                'other' => ['groupid' => 0],
        ]);
    }

    /**
     * Test cases for the activity report viewed event tests.
     *
     * @return array test cases.
     */
    public function activity_report_viewed_cases(): array {
        // 2 is admin user. 1 is guest. At this point in PHP unit, $USER is not set.
        // CMID will get replaced by activity id in the test.
        return [
                'view all users report' => [0, 0, [],
                        "The user with id '2' viewed the embedded questions progress " .
                        "for the activity with id 'CMID'."],
                'view group report' => [0, 1, ['groupid' => 1],
                        "The user with id '2' viewed the embedded questions progress " .
                        "of the group with id '1' for the activity with id 'CMID'."],
                'view other user report' => [1, 0, ['userid' => 1],
                        "The user with id '2' viewed the embedded questions progress " .
                        "of the user with id '1' for the activity with id 'CMID'."],
                'view own user report' => [2, 0, ['userid' => 2],
                        "The user with id '2' viewed their embedded questions progress " .
                        "for the activity with id 'CMID'."],
        ];
    }

    /**
     * Helper function to setup a page.
     *
     * @return stdClass the page from the generator.
     */
    protected function create_page(): stdClass {
        $generator  = $this->getDataGenerator();
        $course = $generator->create_course();
        $pagegenerator = $generator->get_plugin_generator('mod_page');
        return $pagegenerator->create_instance(['course' => $course]);
    }

    /**
     * Test the activity report viewed event.
     *
     * @dataProvider activity_report_viewed_cases
     *
     * @param int $relateduserid user whose report was viewed, or 0.
     * @param int $groupid group whose report was viewed, or 0.
     * @param array $extraurlparams expected extra params to see in the URL.
     * @param string $expecteddescription expected description.
     */
    public function test_activity_report_viewed_all_users(int $relateduserid, int $groupid,
            array $extraurlparams, string $expecteddescription) {

        $activity = $this->create_page();
        $context = context_module::instance($activity->cmid);

        // Trigger event for log report viewed.
        $event = \report_embedquestion\event\activity_report_viewed::create([
                'context' => $context,
                'relateduserid' => $relateduserid,
                'other' => ['groupid' => $groupid],
        ]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf('\report_embedquestion\event\activity_report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEventContextNotUsed($event);
        $this->assertEquals(new moodle_url('/report/embedquestion/activity.php',
                ['cmid' => $activity->cmid] + $extraurlparams),
                $event->get_url());
        $this->assertEquals(str_replace('CMID', $activity->cmid, $expecteddescription),
                $event->get_description());
    }

    /**
     * Test the activity report viewed event has to have relateduserid set.
     */
    public function test_activity_report_viewed_related_user_validation() {
        $activity = $this->create_page();
        $context = context_module::instance($activity->cmid);

        $this->expectException('coding_exception');
        \report_embedquestion\event\activity_report_viewed::create([
                'context' => $context,
                'other' => ['groupid' => 0],
        ]);
    }

    /**
     * Test the activity report viewed event has to have groupid set.
     */
    public function test_activity_report_viewed_groupid_validation() {
        $activity = $this->create_page();
        $context = context_module::instance($activity->cmid);

        $this->expectException('coding_exception');
        \report_embedquestion\event\activity_report_viewed::create([
                'context' => $context,
                'relateduserid' => 0,
                'other' => [],
        ]);
    }

    /**
     * Test the activity report viewed event has to have the right sort of context.
     */
    public function test_activity_report_viewed_context_validation() {
        $activity = $this->create_page();
        $context = context_course::instance($activity->course);

        $this->expectException('coding_exception');
        \report_embedquestion\event\activity_report_viewed::create([
                'context' => $context,
                'relateduserid' => 0,
                'other' => ['groupid' => 0],
        ]);
    }
}
