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

    /**
     * Test get_zip_url function with supported qtype.
     *
     * @dataProvider get_zip_url_with_supported_qtype_cases
     *
     * @param string $qtype Question type.
     * @param string $which Which type of the given question type.
     * @param string $attachmentfilename The name of the attachment file to test.
     * @param string $filename File name to test.
     * @param int $expectedfiles The expected number of files.
     */
    public function test_get_zip_url_with_supported_qtype(string $qtype, ?string $which, string $repsonse,
            ?string $attachmentfilename, string $filename, int $expectedfiles): void {
        global $CFG, $PAGE;
        $PAGE->set_url('/');

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
                ['fullname' => fullname($this->student1), 'info' => implode(', ', $student1info)]);
        $student2folder = get_string('crumbtrailembedquestiondetail', 'report_embedquestion',
                ['fullname' => fullname($this->student2), 'info' => implode(', ', $student2info)]);
        $questionname = str_replace('/', '-', $question->name);

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
        $this->assertCount($expectedfiles, $archivefiles);

        $possiblefilepaths = [
            $student1folder . '/' . $questionname . '/attempt0001/' . $filename,
            $student1folder . '/' . $questionname . '/attempt0002/' . $filename,
            $student2folder . '/' . $questionname . '/attempt0001/' . $filename,
        ];
        if (!is_null($attachmentfilename)) {
            $possiblefilepaths = array_merge($possiblefilepaths, [
                $student1folder . '/' . $questionname . '/attempt0001/' . $attachmentfilename,
                $student1folder . '/' . $questionname . '/attempt0002/' . $attachmentfilename,
                $student2folder . '/' . $questionname . '/attempt0001/' . $attachmentfilename,
            ]);
        }
        foreach ($archivefiles as $file) {
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
            'Export file with question type recordrtc' => [
                'recordrtc',
                'audio',
                '',
                'recording.ogg',
                'Record audio question-response.html',
                6,
            ],
            'Export file with question type essay' => [
                'essay',
                'editorfilepicker',
                '<p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p>',
                'greeting.txt',
                'Essay question with filepicker and attachments-response.html',
                6,
            ],
            'Export file with question type truefalse' => [
                'truefalse',
                null,
                'True',
                null,
                'True-false question-response.html',
                3
            ],
            'Export file with question type shortanswer' => [
                'shortanswer',
                null,
                '',
                null,
                'Short answer question-response.html',
                3
            ],
        ];
    }

    /**
     * Data provider for test_format_filename tests.
     *
     * @return array The test case data.
     */
    public function get_filenames(): array {
        return [
            'Filename has slash' => [
                '/questionname/response.html', '-questionname-response.html'
            ],
            'Filename has slashes' => [
                '///questionname/response.html', '---questionname-response.html'
            ],
            'Filename has special character' => [
                '%!@#$%^&():\/questionname_response.html', '%!@#$%^()-questionname_response.html'
            ]
        ];
    }

    /**
     * Test for test_format_filename.
     *
     * @dataProvider get_filenames
     * @param string $filename
     * @param string $expectedfilename
     */
    public function test_format_filename(string $filename, string $expectedfilename): void {
        $this->assertEquals($expectedfilename, response_export::format_filename($filename));
    }

    /**
     * Data provider for test_replace_resource_urls tests.
     * @coversNothing
     *
     * @return array The test case data.
     */
    public function get_resources(): array {
        return [
            'Invalid HTML without closing p tag' => [
                '<p>This is broken but it might happen when HTML without closing p tag:
                    <img class="icon " alt="Correct"
                    title="Correct" src="https://192.168.216.49/OU1422/theme/image.php/osep/core/1665974909/i/grade_correct">',
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<?xml encoding="utf-8" ?><html><body><p>This is broken but it might happen when HTML without closing p tag:
                    <img class="icon " alt="Correct" title="Correct" src="./grade_correct.svg"></p></body></html>
'],
            'Valid HTML with closing p tag' => [
                '<p>This is valid HTML via closing p tag:
                    <img class="icon " alt="Correct"
                    title="Correct" src="https://192.168.216.49/OU1422/theme/image.php/osep/core/1665974909/i/grade_correct">
                </p>',
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<?xml encoding="utf-8" ?><html><body><p>This is valid HTML via closing p tag:
                    <img class="icon " alt="Correct" title="Correct" src="./grade_correct.svg">
                </p></body></html>
'],
            'HTML5 with audio/source tag' => [
                '<span class="qtype_recordrtc-media-player flex-grow-1">
                    <p>This is audio tag that is not supported in DOMDocument. We hide the warning messages when rendering.</p>
                    <audio controls="" class="w-100, mw-100">
                        <source src="https://192.168.216.49/OU1422/draftfile.php/5/user/draft/922115512/recording.mp3">
                    </audio>
                </span>',
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<?xml encoding="utf-8" ?><html><body><span class="qtype_recordrtc-media-player flex-grow-1">
                    <p>This is audio tag that is not supported in DOMDocument. We hide the warning messages when rendering.</p>
                    <audio controls="" class="w-100, mw-100">
                        <source src="./recording.mp3">
                    </source></audio>
                </span></body></html>
'],
            'HTML5 with svg tag' => [
                '<span class="filter_oumaths_equation filter_oumaths_svg">
                    <p>This is SVG tag that is not supported in DOMDocument. We hide the warning messages when rendering.</p>
                    <svg width="100" height="100">
                        <circle cx="50" cy="50" r="40" stroke="green" stroke-width="4" fill="yellow" />
                    </svg>
                </span>',
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<?xml encoding="utf-8" ?><html><body><span class="filter_oumaths_equation filter_oumaths_svg">
                    <p>This is SVG tag that is not supported in DOMDocument. We hide the warning messages when rendering.</p>
                    <svg width="100" height="100">
                        <circle cx="50" cy="50" r="40" stroke="green" stroke-width="4" fill="yellow"></circle>
                    </svg>
                </span></body></html>
']
        ];
    }

    /**
     * Test for test_replace_resource_urls.
     *
     * @dataProvider get_resources
     * @param string $bodystring HTML representation of the question.
     * @param string $expectedresult The expected HTML render.
     *
     * @covers ::replace_resource_urls
     */
    public function test_replace_resource_urls(string $bodystring, string $expectedresult): void {
        $responseexport = new response_export();
        $this->assertEquals($expectedresult,
            $responseexport->replace_resource_urls($bodystring, response_export::HTML_TAGS_WITH_SRC));
    }
}
