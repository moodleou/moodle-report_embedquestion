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
        $context = \context::instance_by_id($contextid);

        if ($context->contextlevel == CONTEXT_COURSE) {
            // Build the cache hierarchy if there is no cache for this context
            // or there is an old cache hierarchy that was created previously.
            if (self::is_cache_hierarchy_valid($context)) {
                // Build the parent context cache hierarchy.
                self::user_attempts_changed($context);
            }
            // First, check if the current context have value or not.
            if ($cache->get($context->id)['value']) {
                return $cache->get($context->id)['value'];
            }
            $cachedata = $cache->get($context->id)['subcontext'];
            // If current context do not have value, check the sub context.
            return array_search(true, $cachedata) != false;
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            $parentcontext = $context->get_parent_context();
            // Build the cache hierarchy if there is no cache for this context
            // or there is an old cache hierarchy that was created previously.
            if (self::is_cache_hierarchy_valid($parentcontext)) {
                // Build the parent context cache hierarchy.
                self::user_attempts_changed($parentcontext);
            }
            $cachedata = $cache->get($parentcontext->id)['subcontext'];
            if (!isset($cachedata[$context->id])) {
                self::user_attempts_changed($context);
                $cachedata = $cache->get($parentcontext->id)['subcontext'];
            }
            return $cachedata[$context->id];
        } else {
            throw new \coding_exception('Invalid context');
        }
    }

    /**
     * Invalidate the cache for given user and given context.
     *
     * @param \context $context Context
     */
    public static function user_attempts_changed(\context $context): void {
        global $DB;

        $cache = \cache::make(self::CACHE_COMPONENT, self::CACHE_AREA);

        if ($context->contextlevel == CONTEXT_COURSE) {
            // Build the parent context cache hierarchy.
            self::build_course_cache($context);
        } else if ($context->contextlevel == CONTEXT_MODULE) {
            // Get the parent context.
            $parentcontext = $context->get_parent_context();
            // Build the cache hierarchy if there is no cache for this context
            // or there is an old cache hierarchy that was created previously.
            if (self::is_cache_hierarchy_valid($parentcontext)) {
                // Build the parent context cache hierarchy.
                self::build_course_cache($parentcontext);
            } else {
                // Get the parent context cache.
                $cachedata = $cache->get($parentcontext->id)['subcontext'];
                // Set the sub context value.
                $cachedata[$context->id] = $DB->record_exists('report_embedquestion_attempt', ['contextid' => $context->id]);
                // Update the parent context cache again.
                $cache->set($parentcontext->id, [
                        'value' => $DB->record_exists('report_embedquestion_attempt', ['contextid' => $parentcontext->id]),
                        'subcontext' => $cachedata
                ]);
            }
        } else {
            throw new \coding_exception('Invalid context');
        }
    }

    /**
     * Build the cache hierarchy for given Course context.
     *
     * @param \context $context
     */
    public static function build_course_cache(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            throw new \coding_exception('Invalid context');
        }

        $course = get_course($context->instanceid);
        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();
        $cachehir = [];
        foreach ($cms as $cm) {
            $cachehir[$cm->context->id] = false;
        }

        if (!empty($cachehir)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($cachehir));

            $sql = "SELECT contextid, COUNT(id) as attemptno
                      FROM {report_embedquestion_attempt}
                     WHERE contextid $insql
                  GROUP BY contextid";

            $attemptinfos = $DB->get_records_sql_menu($sql, $inparams);

            foreach ($attemptinfos as $subcontextid => $total) {
                $cachehir[$subcontextid] = intval($total) > 0;
            }
        }

        $cache = \cache::make(self::CACHE_COMPONENT, self::CACHE_AREA);
        $cache->set($context->id, [
                'value' => $DB->record_exists('report_embedquestion_attempt', ['contextid' => $context->id]),
                'subcontext' => $cachehir
        ]);
    }

    /**
     * Check if there is no cache for this context or there is an old cache hierarchy that was created previously.
     *
     * @param \context $context Context
     * @return bool
     */
    private static function is_cache_hierarchy_valid(\context $context): bool {
        $cache = \cache::make(self::CACHE_COMPONENT, self::CACHE_AREA);

        return (!$cache->has($context->id) || ($cache->has($context->id) &&
                        (!isset($cache->get($context->id)['value']) || !isset($cache->get($context->id)['subcontext']))));
    }
}
