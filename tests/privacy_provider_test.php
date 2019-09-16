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
 * Tests for the Embedded questions progress privacy provider.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use report_embedquestion\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/tests/privacy_helper.php');


/**
 * Tests for the Embedded questions progress privacy provider.
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class report_embedquestion_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {

    use core_question_privacy_helper;

    /**
     * @var filter_embedquestion_generator
     */
    protected $attemptgenerator;

    protected function setUp() {
        parent::setUp();
        $this->attemptgenerator = $this->getDataGenerator()->get_plugin_generator('filter_embedquestion');
    }

    /**
     * Helper: get the context and subcontext for a question.
     * @param stdClass $question question.
     * @return array context and subcontext array.
     */
    protected function get_context_and_subcontext(stdClass $question): array {
        [$embedid, $context] = $this->attemptgenerator->get_embed_id_and_context($question);
        $subcontext = [
                get_string('attempts', 'report_embedquestion'),
                get_string('attemptsatquestion', 'report_embedquestion', (string) $embedid),
        ];
        return [$context, $subcontext];
    }

    /**
     * Test that a user who has no data gets no contexts
     */
    public function test_get_contexts_for_userid_no_data() {
        global $USER;
        $this->resetAfterTest();

        $contextlist = provider::get_contexts_for_userid($USER->id);
        $this->assertEmpty($contextlist);
    }

    /**
     * Test for provider::get_contexts_for_userid() when there is no quiz attempt at all.
     */
    public function test_get_contexts_for_userid_with_attempt() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'True');

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($DB->get_field('question_categories', 'contextid', ['id' => $question->category]),
                $contextlist->current()->id);
    }

    /**
     * Test for provider::get_users_in_context() when there is no data.
     */
    public function test_get_users_in_context_no_data() {
        $this->resetAfterTest();

        $userlist = new userlist(context_course::instance(SITEID), 'report_embedquestion');
        provider::get_users_in_context($userlist);
        $this->assertEquals([], $userlist->get_userids());
    }

    /**
     * Test for provider::get_users_in_context() with data.
     */
    public function test_get_users_in_context_with_attempt() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $anotheruser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user(); // This user should not be included in the results.
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'True');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $anotheruser, 'True');

        [$context] = $this->get_context_and_subcontext($question);

        $userlist = new userlist($context, 'report_embedquestion');
        provider::get_users_in_context($userlist);
        // Once we no longer need to support Moodle 3.6, this can be:
        // $this->assertEqualsCanonicalizing([$user->id, $anotheruser->id], $userlist->get_userids());
        // until then, the true here means canonicalise. The preceding option arguments are the defaults.
        $this->assertEquals([$user->id, $anotheruser->id],
                $userlist->get_userids(), '', 0.0, 10, true);
    }

    /**
     * The export function should handle an empty contextlist properly.
     */
    public function test_export_user_data_no_data() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $approvedcontextlist = new approved_contextlist(
                core_user::get_user($user->id), 'report_embedquestion', []);

        provider::export_user_data($approvedcontextlist);

        // No data should have been exported.
        $this->assertDebuggingNotCalled();
        /** @var \core_privacy\tests\request\content_writer $writer */
        $writer = writer::with_context(context_system::instance());
        $this->assertFalse($writer->has_any_data_in_any_context());
    }

    /**
     * Export data for a user who has made an attempt.
     */
    public function test_export_user_data_with_data() {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setUser();

        $user = $this->getDataGenerator()->create_user();
        $anotheruser = $this->getDataGenerator()->create_user();
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'True');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $anotheruser, 'False');

        // Fetch the contexts - only one context should be returned.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        // Perform the export and check the data.
        $this->setUser($user);
        $approvedcontextlist = new approved_contextlist(core_user::get_user($user->id),
                'report_embedquestion', $contextlist->get_contextids());
        provider::export_user_data($approvedcontextlist);

        // Ensure that the overall was exported correctly.
        [$context, $subcontext] = $this->get_context_and_subcontext($question);
        /** @var \core_privacy\tests\request\content_writer $writer */
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $exporteddata = $writer->get_data($subcontext);
        $this->assertEquals('Test embed location', $exporteddata->pagename);
        $this->assertEquals($CFG->wwwroot . '/', $exporteddata->pageurl);

        // Fetch the attempt data.
        $questionscontext = $subcontext;
        $questionscontext[] = 'Questions';
        $questionscontext[] = '1';
        $attemptdata = $writer->get_data($questionscontext);

        $this->assertEquals('True/false question', $attemptdata->name);
        $this->assertEquals('The answer is true.', $attemptdata->question);
        $this->assertEquals('True', $attemptdata->answer);
        $this->assertEquals('1.00', $attemptdata->mark);
        $this->assertEquals('You should have selected true.', $attemptdata->generalfeedback);
    }

    /**
     * Test delete all data with no data.
     */
    public function test_delete_data_for_all_users_in_context_no_data() {
        global $DB;
        $this->resetAfterTest();

        provider::delete_data_for_all_users_in_context(context_course::instance(SITEID));

        $this->assertEquals(0, $DB->count_records('report_embedquestion_attempt'));
        $this->assertEquals(0, $DB->count_records('question_usages'));
    }

    /**
     * Test delete all data when a user has made an attempt.
     */
    public function test_delete_data_for_all_users_in_context_with_data() {
        global $DB;
        $this->resetAfterTest(true);

        // This is the attempt we will try to delete.
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse',
                null, null, ['contextid' => $coursecontext->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'True');

        // This attempt should not be affected.
        $otherquestion = $this->attemptgenerator->create_embeddable_question('truefalse');
        $otheruser = $this->getDataGenerator()->create_user();
        $this->attemptgenerator->create_attempt_at_embedded_question($otherquestion, $otheruser, 'True');

        // Delete all data for all users in the context under test.
        provider::delete_data_for_all_users_in_context($coursecontext);

        // The quiz attempt should have been deleted from this quiz.
        $this->assertEquals(0, $DB->count_records('report_embedquestion_attempt',
                ['contextid' => $coursecontext->id]));
        $this->assertEquals(0, $DB->count_records('question_usages',
                ['contextid' => $coursecontext->id]));

        // But not for the other quiz.
        $frontpagecontext = context_course::instance(SITEID);
        $this->assertEquals(1, $DB->count_records('report_embedquestion_attempt',
                ['contextid' => $frontpagecontext->id]));
        $this->assertEquals(1, $DB->count_records('question_usages',
                ['contextid' => $frontpagecontext->id]));
    }

    /**
     * The delete function should handle an empty contextlist properly.
     */
    public function test_delete_data_for_user_no_data() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $approvedcontextlist = new approved_contextlist(
                core_user::get_user($user->id), 'report_embedquestion', []);

        provider::delete_data_for_user($approvedcontextlist);
        $this->assertDebuggingNotCalled();
    }

    /**
     * Export + Delete quiz data for a user who has made a single attempt.
     */
    public function test_delete_data_for_user_with_data() {
        global $DB;
        $this->resetAfterTest();

        // This is the attempt we will try to delete.
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $user = $this->getDataGenerator()->create_user();
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'True');

        // This attempt should not be affected.
        $otheruser = $this->getDataGenerator()->create_user();
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $otheruser, 'True');

        // Delete data for $user in the context under test.
        $context = context_course::instance(SITEID);
        $approvedcontextlist = new approved_contextlist(
                core_user::get_user($user->id), 'report_embedquestion', [$context->id]);
        provider::delete_data_for_user($approvedcontextlist);
        $this->assertDebuggingNotCalled();

        // The quiz attempt should have been deleted from this quiz.
        $this->assertEquals(1, $DB->count_records('report_embedquestion_attempt',
                ['contextid' => $context->id]));
        $this->assertEquals(1, $DB->count_records('question_usages',
                ['contextid' => $context->id]));
    }

    /**
     * Test for provider::delete_data_for_users().
     */
    public function test_delete_data_for_users() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $user, 'True');
        $this->attemptgenerator->create_attempt_at_embedded_question($question, $otheruser, 'True');

        [$context] = $this->get_context_and_subcontext($question);
        $approveduserlist = new approved_userlist($context, 'question_usages', [$user->id]);
        provider::delete_data_for_users($approveduserlist);

        // Only the attempt of user2 should be remained in quiz1.
        // Only attempt by $otheruser is left.
        $this->assertEquals([$otheruser->id => 1], $DB->get_records_menu(
                'report_embedquestion_attempt', ['contextid' => $context->id], 'id', 'userid, 1'));
    }
}
