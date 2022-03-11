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

use report_embedquestion\local\export\response_export;

/**
 * Unit test for the report_embedquestion response download.
 *
 * @package    report_embedquestion
 * @copyright  2021 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_download_test extends \advanced_testcase {

    /**
     * @var \testing_data_generator
     */
    private $generator;
    /**
     * @var \stdClass
     */
    private $course;
    /**
     * @var \filter_embedquestion_generator
     */
    private $attemptgenerator;
    /**
     * @var \context_course
     */
    private $coursecontext;
    /**
     * @var \stdClass
     */
    private $student1;
    /**
     * @var \stdClass
     */
    private $student2;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course(['shortname' => 'Embedquestion_Course']);
        $this->coursecontext = \context_course::instance($this->course->id);
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
        $this->student1 = $this->generator->create_user(['username' => 'student1']);
        $this->student2 = $this->generator->create_user(['username' => 'student2']);

        $this->generator->enrol_user($this->student1->id, $this->course->id, 'student');
        $this->generator->enrol_user($this->student2->id, $this->course->id, 'student');
    }

    public function test_get_zip_url_with_non_supported_qtype() {
        $question = $this->attemptgenerator->create_embeddable_question('truefalse', null, [],
                ['contextid' => $this->coursecontext->id]);

        $page = $this->generator->create_module('page', ['course' => $this->course->id,
                'content' => '<p>Try this question: ' . $this->attemptgenerator->get_embed_code($question) . '</p>']);

        $pagecontext = \context_module::instance($page->cmid);

        /** @var \filter_embedquestion\attempt $attempt */
        $attempt = $this->attemptgenerator->create_attempt_at_embedded_question($question, $this->student1, 'False', $pagecontext);

        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Invalid question usage id');

        response_export::get_response_zip_file_info([$attempt->get_question_usage()->get_id()], $pagecontext, 0);
    }

    /**
     * Test get_zip_url function with supported qtype.
     *
     * @dataProvider get_zip_url_with_supported_qtype_cases
     *
     * @param string $qtype Question type
     * @param string $which Which type of the given question type.
     * @param string $filename File name to test
     */
    public function test_get_zip_url_with_supported_qtype(string $qtype, string $which, string $repsonse, string $filename): void {
        global $CFG;

        if (!\question_bank::is_qtype_installed($qtype)) {
            $this->markTestSkipped();
        }

        $question = $this->attemptgenerator->create_embeddable_question($qtype, $which, [], [
                'contextid' => $this->coursecontext->id]);

        $page = $this->generator->create_module('page', ['course' => $this->course->id,
                'content' => '<p>Try this question: ' . $this->attemptgenerator->get_embed_code($question) . '</p>']);

        $pagecontext = \context_module::instance($page->cmid);

        /** @var \filter_embedquestion\attempt $attempt1 */
        $attempt1 = $this->attemptgenerator->create_attempt_at_embedded_question(
                $question, $this->student1, $repsonse, $pagecontext, '', 1);
        /** @var \filter_embedquestion\attempt $attempt2 */
        $attempt2 = $this->attemptgenerator->create_attempt_at_embedded_question(
                $question, $this->student1, $repsonse, $pagecontext, '', 2);
        /** @var \filter_embedquestion\attempt $attempt3 */
        $attempt3 = $this->attemptgenerator->create_attempt_at_embedded_question(
                $question, $this->student2, $repsonse, $pagecontext, '', 1);

        $questionusageids = [
                $attempt1->get_question_usage()->get_id(),
                $attempt2->get_question_usage()->get_id(),
                $attempt3->get_question_usage()->get_id()
        ];

        $zipinfo = response_export::get_response_zip_file_info($questionusageids, $pagecontext, 0);

        $expectedfilename = response_export::get_export_file_name($this->course, $pagecontext->get_context_name(false));
        [, $student1info] = utils::get_user_details($this->student1->id, $pagecontext);
        [, $student2info] = utils::get_user_details($this->student2->id, $pagecontext);
        $student1folder = get_string('crumbtrailembedquestiondetail', 'report_embedquestion',
                ['fullname' => fullname($this->student1), 'info' => implode(',', $student1info)]);
        $student2folder = get_string('crumbtrailembedquestiondetail', 'report_embedquestion',
                ['fullname' => fullname($this->student2), 'info' => implode(',', $student2info)]);
        $questionname = str_replace('/', '-', get_string('pluginname', 'qtype_' . $question->qtype));

        $this->assertIsArray($zipinfo);
        $this->assertArrayHasKey('file', $zipinfo);
        $this->assertArrayHasKey('size', $zipinfo);

        $filepath = $CFG->dataroot . '/cache/report_embedquestion/download/' . $zipinfo['file'] . '.zip';

        $this->assertEquals($expectedfilename, $zipinfo['file']);
        $this->assertGreaterThan(0, $zipinfo['size']);
        $this->assertEquals(filesize($filepath), $zipinfo['size']);

        $ziparchive = new \zip_archive();
        $ziparchive->open($filepath, \file_archive::OPEN);

        $archivefiles = $ziparchive->list_files();
        $this->assertIsArray($archivefiles);
        $this->assertCount(3, $archivefiles);
        foreach ($archivefiles as $file) {
            $possiblefilepaths = [
                $student1folder . '/' . $questionname . '/attempt0001/' . $filename,
                $student1folder . '/' . $questionname . '/attempt0002/' . $filename,
                $student2folder . '/' . $questionname . '/attempt0001/' . $filename,
            ];
            $this->assertContains($file->pathname, $possiblefilepaths);
            $this->assertGreaterThan(0, $file->size);
        }
        $ziparchive->close();
    }

    /**
     * Data provider for test_get_zip_url_with_supported_qtype tests.
     *
     * @return array
     */
    public function get_zip_url_with_supported_qtype_cases(): array {
        return [
                [
                        'recordrtc',
                        'audio',
                        '',
                        'recording.ogg'
                ],
                [
                        'essay',
                        'editorfilepicker',
                        '<p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p>',
                        'greeting.txt'
                ]
        ];
    }
}
