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
 * Unit test for the report_embedquestion util methods.
 *
 * @package    report_embedquestion
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/../classes/utils.php');
use report_embedquestion\utils;


/**
 * Unit tests for the util methods.
 *
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_embedquestion_utils_testcase extends advanced_testcase {

    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->generator = $this->getDataGenerator();
        $this->course = $this->generator->create_course();
        $this->attemptgenerator = $this->generator->get_plugin_generator('filter_embedquestion');
    }

    public function test_get_activity_link () {
        global $DB;
        $pagegen = $this->generator->get_plugin_generator('mod_page');
        $page1 = $pagegen->create_instance(['course' => $this->course]);
        $question = $this->attemptgenerator->create_embeddable_question('truefalse');
        $catid = $DB->get_field('question_categories', 'idnumber', ['id' => $question->category]);
        $cmid = $page1->id;

        $attempt = new stdClass();
        $attempt->pagename = $this->course->shortname . ':' . $page1->name;
        $attempt->pageurl = '/mod/page/view.php?id=' . $page1->id;
        $attempt->embedid = $catid . '/' . $question->idnumber;
        $embedid = $attempt->embedid;
        $expected = "<a href=\"https://www.example.com/moodle/mod/page/view.php?id=$cmid#$embedid\">Page 1</a>";
        $actual = \report_embedquestion\utils::get_activity_link($attempt);
        $this->assertEquals($expected, $actual);
    }

    public function test_get_user_link() {
        global $DB;
        $courseid = $this->course->id;
        $user = $this->generator->create_user();
        $userid = $user->id;
        $username = $user->username;
        $expected = "<a href=\"https://www.example.com/moodle/user/view.php?id=$userid&amp;course=$courseid\">$username</a>";
        $actual = \report_embedquestion\utils::get_user_link($this->course->id, $user->id, $user->username);
        $this->assertEquals($expected, $actual);
    }

    public function test_get_formatted_date () {
        $timestamp = time();
        $datetimeformat = 'd M Y, H:i:s';
        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $expected = $date->format($datetimeformat);
        $actual = userdate($timestamp);
        print_object($actual);
        $actual = \report_embedquestion\utils::get_formatted_date($timestamp, $datetimeformat);
        $this->assertEquals($expected, $actual);
    }
}
