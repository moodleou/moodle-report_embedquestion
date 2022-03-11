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
 * Unit test for the report_embedquestion attempt_tracker methods.
 *
 * @package    report_embedquestion
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_embedquestion_attempt_tracker_testcase extends \advanced_testcase {

    /**
     * @var \testing_data_generator
     */
    protected $generator;

    /**
     * @var \filter_embedquestion_generator
     */
    protected $attemptgenerator;

    /**
     * Setup.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->generator = $this->getDataGenerator();
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
    }

    /**
     * Test the user_has_attempt function.
     */
    public function test_user_has_attempt() {
        global $DB;

        $course = $this->generator->create_course();
        $coursecontext = \context_course::instance($course->id);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse', null, [], ['contextid' => $coursecontext->id]);
        $page = $this->generator->create_module('page', ['course' => $course->id,
                'content' => '<p>Try this question: ' . $this->attemptgenerator->get_embed_code($question) . '</p>']);
        $pagecontext = \context_module::instance($page->cmid);

        // Create a student with an attempt at that question.
        $user = $this->generator->create_user();
        $this->generator->enrol_user($user->id, $course->id, 'student');

        // Make the cache.
        $cache = \cache::make(attempt_tracker::CACHE_COMPONENT,
                attempt_tracker::CACHE_AREA);

        // Verify that there is no cache at this time.
        $this->assertFalse($cache->has($coursecontext->id));

        // Verify that there is no attempt for the given context and user.
        $this->assertFalse(attempt_tracker::user_has_attempt($coursecontext->id));
        $this->assertFalse(attempt_tracker::user_has_attempt($pagecontext->id));

        // Verify that now we have the cache at this time.
        $this->assertTrue($cache->has($coursecontext->id));
        // Verify that the value from the cache is False.
        $this->assertEquals([
                'value' => false,
                'subcontext' => [$pagecontext->id => false]
        ], $cache->get($coursecontext->id));

        // Insert the dummy data to report_embedquestion_attempt table.
        // We do not want to use create_attempt_at_embedded_question here because it will trigger the cache invalidation event.
        $now = time();
        $attemptinfo = [
                'userid' => $user->id,
                'contextid' => $pagecontext->id,
                'embedid' => (string) $question->idnumber,
                'questionusageid' => 1,
                'pagename' => 'Test cache',
                'pageurl' => (new \moodle_url('/report/embedquestion/activity.php'))->out_as_local_url(false),
                'timecreated' => $now,
                'timemodified' => $now
        ];
        $DB->insert_record('report_embedquestion_attempt', (object) $attemptinfo);

        // Verify that the value from the cache is still False.
        $this->assertEquals([
                'value' => false,
                'subcontext' => [$pagecontext->id => false],
        ], $cache->get($coursecontext->id));
        // Verify that the function will get the value from the cache, not from the database.
        $this->assertFalse(attempt_tracker::user_has_attempt($coursecontext->id));
        $this->assertFalse(attempt_tracker::user_has_attempt($pagecontext->id));

        // Invalidate the cache.
        attempt_tracker::user_attempts_changed($pagecontext);

        // Verify that the value from the cache is updated to True.
        $this->assertEquals([
                'value' => false,
                'subcontext' => [$pagecontext->id => true]
        ], $cache->get($coursecontext->id));
        // Verify that the function will return the correct value.
        $this->assertTrue(attempt_tracker::user_has_attempt($coursecontext->id));
        $this->assertTrue(attempt_tracker::user_has_attempt($pagecontext->id));

        // Remove the dummy data.
        $DB->delete_records('report_embedquestion_attempt', ['questionusageid' => $attemptinfo['questionusageid']]);

        // Verify that the value from the cache is still True.
        $this->assertEquals([
                'value' => false,
                'subcontext' => [$pagecontext->id => true]
        ], $cache->get($coursecontext->id));
        // Verify that the function will get the value from the cache, not from the database.
        $this->assertTrue(attempt_tracker::user_has_attempt($coursecontext->id));
        $this->assertTrue(attempt_tracker::user_has_attempt($pagecontext->id));

        // Invalidate the cache.
        attempt_tracker::user_attempts_changed($pagecontext);

        // Verify that the value from the cache is updated to True.
        $this->assertEquals([
                'value' => false,
                'subcontext' => [$pagecontext->id => false]
        ], $cache->get($coursecontext->id));
        // Verify that the function will return the correct value.
        $this->assertFalse(attempt_tracker::user_has_attempt($coursecontext->id));
        $this->assertFalse(attempt_tracker::user_has_attempt($pagecontext->id));
    }

    /**
     * Test the user_has_attempt function with old cache hierarchy.
     */
    public function test_user_has_attempt_with_old_cache_hierarchy() {
        $course = $this->generator->create_course();
        $coursecontext = \context_course::instance($course->id);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse', null, [], ['contextid' => $coursecontext->id]);
        $page = $this->generator->create_module('page', ['course' => $course->id,
                'content' => '<p>Try this question: ' . $this->attemptgenerator->get_embed_code($question) . '</p>']);
        $pagecontext = \context_module::instance($page->cmid);

        // Create a student with an attempt at that question.
        $user = $this->generator->create_user();
        $this->generator->enrol_user($user->id, $course->id, 'student');

        // Make the cache.
        $cache = \cache::make(attempt_tracker::CACHE_COMPONENT,
                attempt_tracker::CACHE_AREA);

        // Verify that there is no cache at this time.
        $this->assertFalse($cache->has($coursecontext->id));

        // Create old cache hierarchy.
        $cache->set($coursecontext->id, ['value' => false]);
        // Verify that there is no attempt for the given context and user.
        $this->assertFalse(attempt_tracker::user_has_attempt($coursecontext->id));
        // Verify the cache hierarchy will be converted to the new one.
        $this->assertEquals([
                'value' => false,
                'subcontext' => [$pagecontext->id => false]
        ], $cache->get($coursecontext->id));

        // Create old cache hierarchy.
        $cache->set($coursecontext->id, ['value' => false]);
        // Verify that there is no attempt for the given context and user.
        $this->assertFalse(attempt_tracker::user_has_attempt($pagecontext->id));
        // Verify the cache hierarchy will be converted to the new one.
        $this->assertEquals([
                'value' => false,
                'subcontext' => [$pagecontext->id => false]
        ], $cache->get($coursecontext->id));
    }
}
