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
 * Embedded questions progress course report viewed event.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_embedquestion\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Embedded questions progress course report viewed event.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *      - int groupid: Group to display.
 * }
 *
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_report_viewed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('eventcoursereportviewed', 'report_embedquestion');
    }

    public function get_description() {
        if ($this->relateduserid) {
            if ($this->userid == $this->relateduserid) {
                return "The user with id '$this->userid' viewed their embedded questions progress " .
                        "for the course with id '$this->courseid'.";
            } else {
                return "The user with id '$this->userid' viewed the embedded questions progress " .
                        "of the user with id '$this->relateduserid' for the course with id '$this->courseid'.";
            }
        } else if ($this->other['groupid']) {
            return "The user with id '$this->userid' viewed the embedded questions progress " .
                    "of the group with id '{$this->other['groupid']}' for the course with id '$this->courseid'.";
        } else {
            return "The user with id '$this->userid' viewed the embedded questions progress " .
                    "for the course with id '$this->courseid'.";
        }
    }

    public function get_url() {
        $params = ['courseid' => $this->courseid];
        if ($this->relateduserid) {
            $params['userid'] = $this->relateduserid;
        } else if ($this->other['groupid']) {
            $params['groupid'] = $this->other['groupid'];
        }
        return new \moodle_url('/report/embedquestion/index.php', $params);
    }

    protected function validate_data() {
        parent::validate_data();

        if ($this->context->contextlevel !== CONTEXT_COURSE) {
            throw new \coding_exception('The \'context\' must be a course context.');
        }

        if (!isset($this->other['groupid'])) {
            throw new \coding_exception('The \'groupid\' value must be set in other.');
        }

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
    }

    public static function get_other_mapping() {
        return [
            'groupid' => ['db' => 'groups', 'restore' => 'group'],
        ];
    }
}
