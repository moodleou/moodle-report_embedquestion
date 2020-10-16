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
 * Attempt tracker functions for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion;

defined('MOODLE_INTERNAL') || die();

/**
 * Attempt tracker functions for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_tracker {

    const CACHE_COMPONENT = 'report_embedquestion';
    const CACHE_AREA = 'reportattempttracker';

    /**
     * Check that given user has attempt at given context
     *
     * @param int $contextid Context id
     * @return bool
     */
    public static function user_has_attempt(int $contextid): bool {
        $cache = \cache::make(self::CACHE_COMPONENT, self::CACHE_AREA);

        if (!$cache->has($contextid)) {
            self::user_attempts_changed($contextid);
        }

        return $cache->get($contextid)['value'];
    }

    /**
     * Invalidate the cache for given user and given context.
     *
     * @param int $contextid Context id
     */
    public static function user_attempts_changed(int $contextid): void {
        global $DB;

        $cache = \cache::make(self::CACHE_COMPONENT, self::CACHE_AREA);
        $cache->set($contextid, ['value' => $DB->record_exists('report_embedquestion_attempt', ['contextid' => $contextid])]);
    }
}
