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
 * Behat steps for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test because this file is required by Behat.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps for report_embedquestion.
 *
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_report_embedquestion extends behat_base {

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype                     | name meaning      | description                                        |
     * | Progress report for Course   | Course shortname  | The Embedded Question progress report for Course   |
     * | Progress report for Activity | Activity idnumber | The Embedded Question progress report for Activity |
     * | Course admin                 | Course shortname  | Core page, not ours, but we need to get there      |
     *
     * @param string $type identifies which type of page this is, e.g. 'Progress report for Course'.
     * @param string $identifier identifies the particular course/page, e.g. 'C1'.
     * @return moodle_url
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch ($type) {
            case 'Progress report for Course':
                return new moodle_url('/report/embedquestion/index.php',
                        ['courseid' => $this->get_course_by_course_shortname($identifier)]);
            case 'Progress report for Activity':
                return new moodle_url('/report/embedquestion/activity.php',
                        ['cmid' => $this->get_cmid_by_idnumber($identifier)]);
            case 'Course admin':
                return new moodle_url('/course/admin.php',
                        ['courseid' => $this->get_course_by_course_shortname($identifier)]);
            default:
                throw new Exception('Unrecognised filter_embedquestion page type "' . $type . '."');
        }
    }

    /**
     * Get a course id by shortname.
     *
     * @param string $shortname Course shortname.
     * @return int Id of the course.
     */
    protected function get_course_by_course_shortname(string $shortname): int {
        global $DB;

        if (!$id = $DB->get_field('course', 'id', ['shortname' => $shortname])) {
            throw new Exception('The specified course with shortname "' . $shortname . '" does not exist');
        }
        return $id;
    }

    /**
     * Get a cm id by id number.
     *
     * @param string $idnumber Id number of the activity
     * @return int Cmid of the activity.
     */
    protected function get_cmid_by_idnumber(string $idnumber): int {
        global $DB;

        if (!$cmid = $DB->get_field('course_modules', 'id', ['idnumber' => $idnumber])) {
            throw new Exception('The specified activity with idnumber "' . $idnumber . '" does not exist');
        }
        return $cmid;
    }
}
