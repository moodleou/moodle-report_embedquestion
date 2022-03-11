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

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/externallib.php');

use backup;
use backup_controller;
use base_setting;
use restore_controller;

/**
 * Tests for the Embedded questions progress report backup and restore code.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class backup_test extends \advanced_testcase {

    /**
     * @var \testing_data_generator
     */
    protected $generator;

    /**
     * @var \filter_embedquestion_generator
     */
    protected $attemptgenerator;

    protected function setUp(): void {
        parent::setUp();
        $this->generator = $this->getDataGenerator();
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
    }

    /**
     * Duplicate a page with embedded questions with attempts.
     *
     * The attempts should not be coped.
     */
    public function test_duplicate_activity_should_not_copy_attempts() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course with a page that embeds a question.
        $course = $this->generator->create_course();
        $coursecontext = \context_course::instance($course->id);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse',
                null, [], ['contextid' => $coursecontext->id]);
        $page = $this->generator->create_module('page', ['course' => $course->id,
                'content' => '<p>Try this question: ' .
                        $this->attemptgenerator->get_embed_code($question) . '</p>']);
        $pagecontext = \context_module::instance($page->cmid);

        // Create a student with an attempt at that question.
        $user = $this->generator->create_user();
        $this->generator->enrol_user($user->id, $course->id, 'student');
        $this->attemptgenerator->create_attempt_at_embedded_question(
                $question, $user, 'True', $pagecontext);

        // Save some counts, so we can verify that they don't change.
        $numberofembeddedattempts = $DB->count_records('report_embedquestion_attempt');
        $numberofusages = $DB->count_records('question_usages');

        // Duplicate the page.
        duplicate_module($course, get_fast_modinfo($course)->get_cm($page->cmid));

        // Verify the copied page.
        $this->assertCount(2, get_fast_modinfo($course)->instances['page']);

        // Verify that the attempt was not copied.
        $this->assertEquals($numberofembeddedattempts,
                $DB->count_records('report_embedquestion_attempt'));
        $this->assertEquals($numberofusages, $DB->count_records('question_usages'));
    }

    /**
     * Backup and restore a course containing a page with embedded questions with attempts.
     *
     * The attempts should be coped.
     */
    public function test_backup_and_restore_course_with_user_data_should_copy_attempts() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        [$course, $page, $user] = $this->create_course_with_page_with_attempted_question();

        // Backup and restore the course with user data.
        $newcourseinfo = \core_course_external::duplicate_course($course->id, 'Copied course',
                'CC', $course->category, true, [['name' => 'users', 'value' => true]]);
        $newcourse = get_course($newcourseinfo['id']);

        // Verify the copied page.
        $pages = get_fast_modinfo($newcourse)->instances['page'];
        $newpage = reset($pages);
        $this->assertEquals($page->name, $newpage->name);

        // Verify the copied attempt in the course context.
        $newcoursecontext = \context_course::instance($newcourse->id);
        $copiedattempt = $DB->get_record('report_embedquestion_attempt',
                ['contextid' => $newcoursecontext->id], '*', MUST_EXIST);
        $this->assertEquals($user->id, $copiedattempt->userid);
        $quba = \question_engine::load_questions_usage_by_activity($copiedattempt->questionusageid);
        $this->assertCount(1, $quba->get_slots());
        $this->assertEquals(\question_state::$gradedwrong, $quba->get_question_state(1));

        // Verify the copied attempt in the page.
        $newpagecontext = \context_module::instance($newpage->id);
        $copiedattempt = $DB->get_record('report_embedquestion_attempt',
                ['contextid' => $newpagecontext->id], '*', MUST_EXIST);
        $this->assertEquals($user->id, $copiedattempt->userid);
        $quba = \question_engine::load_questions_usage_by_activity($copiedattempt->questionusageid);
        $this->assertCount(1, $quba->get_slots());
        $this->assertEquals(\question_state::$gradedright, $quba->get_question_state(1));
    }

    /**
     * Delete and restore a page activity with embedded questions with attempts.
     *
     * The attempts should be restored.
     */
    public function test_restore_deleted_page_with_attempt() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // We want the course bin to be enabled.
        set_config('coursebinenable', 1, 'tool_recyclebin');

        [$course, $page, $user] = $this->create_course_with_page_with_attempted_question();

        // Delete page.
        course_delete_module($page->cmid);
        \phpunit_util::run_all_adhoc_tasks();
        $pages = get_coursemodules_in_course('page', $course->id);
        $this->assertEquals(0, count($pages));

        // Restore page.
        $recyclebin = new \tool_recyclebin\course_bin($course->id);

        foreach ($recyclebin->get_items() as $item) {
            $recyclebin->restore_item($item);
        }
        $pages = get_coursemodules_in_course('page', $course->id);
        $this->assertEquals(1, count($pages));
        $restoredpage = array_pop($pages);

        // Verify the attempt is in the page.
        $newpagecontext = \context_module::instance($restoredpage->id);
        $copiedattempt = $DB->get_record('report_embedquestion_attempt',
            ['contextid' => $newpagecontext->id], '*', MUST_EXIST);
        $this->assertEquals($user->id, $copiedattempt->userid);
        $quba = \question_engine::load_questions_usage_by_activity($copiedattempt->questionusageid);
        $this->assertCount(1, $quba->get_slots());
        $this->assertEquals(\question_state::$gradedright, $quba->get_question_state(1));
    }

    /**
     * Backup and restore a page with embedded questions with attempts.
     *
     * The attempts should be coped.
     */
    public function test_backup_and_restore_page_with_user_data_should_copy_attempts() {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        [$course, $page, $user] = $this->create_course_with_page_with_attempted_question();

        // Backup the page. To make restore easy, we use MODE_IMPORT, but then re-enable users.
        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $page->cmid,
                backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT,
                $USER->id);

        $bc->get_plan()->get_setting('users')->set_status(base_setting::NOT_LOCKED);
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->get_plan()->get_setting('page_' . $page->cmid . '_userinfo')->set_status(base_setting::NOT_LOCKED);
        $bc->get_plan()->get_setting('page_' . $page->cmid . '_userinfo')->set_value(true);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Rename the page so we can recognise the right one after restore.
        $DB->set_field('page', 'name', 'Original page', ['id' => $page->id]);

        // Restore the page into the same course.
        $rc = new restore_controller($backupid, $course->id,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id,
                backup::TARGET_CURRENT_ADDING);
        $precheck = $rc->execute_precheck();
        $this->assertTrue($precheck);
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored page.
        $pages = get_fast_modinfo($course)->instances['page'];
        foreach ($pages as $newpage) {
            if ($newpage->name != 'Original page') {
                break;
            }
        }

        // Verify the copied attempt in the page.
        $newpagecontext = \context_module::instance($newpage->id);
        $copiedattempt = $DB->get_record('report_embedquestion_attempt',
                ['contextid' => $newpagecontext->id], '*', MUST_EXIST);
        $this->assertEquals($user->id, $copiedattempt->userid);
        $quba = \question_engine::load_questions_usage_by_activity($copiedattempt->questionusageid);
        $this->assertCount(1, $quba->get_slots());
        $this->assertEquals(\question_state::$gradedright, $quba->get_question_state(1));
    }

    /**
     * Helper method for the setup.
     * @return array [$course, $page, $user].
     */
    protected function create_course_with_page_with_attempted_question(): array {
        // Create a course with a page that embeds a question.
        $course = $this->generator->create_course(
                ['fullname' => 'Original course', 'shortname' => 'OC']);
        $coursecontext = \context_course::instance($course->id);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse',
                null, [], ['contextid' => $coursecontext->id]);
        $page = $this->generator->create_module('page', ['course' => $course->id,
                'content' => '<p>Try this question: ' .
                        $this->attemptgenerator->get_embed_code($question) . '</p>']);
        $pagecontext = \context_module::instance($page->cmid);

        // Create a student with an attempt at that question.
        $user = $this->generator->create_user();
        $this->generator->enrol_user($user->id, $course->id, 'student');
        $this->attemptgenerator->create_attempt_at_embedded_question(
                $question, $user, 'True', $pagecontext);

        // Also add an attempt in the course context (as if the question was embedded in a label).
        $this->attemptgenerator->create_attempt_at_embedded_question(
                $question, $user, 'False', $coursecontext);
        return [$course, $page, $user];
    }
}
