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
 * Backup task for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');


if (trait_exists('backup_questions_attempt_data_trait')) {
    /**
     * Backup task for report_embedquestion.
     */
    class backup_report_embedquestion_plugin extends backup_report_plugin {
        use backup_questions_attempt_data_trait;

        /**
         * Is there any data to backup here?
         *
         * @return array
         * @throws dml_exception
         */
        protected function get_include_condition() {
            global $DB;

            if ($DB->record_exists('report_embedquestion_attempt',
                    ['contextid' => $this->task->get_contextid()])) {
                $result = 'include';
            } else {
                $result = '';
            }

            return ['sqlparam' => $result];
        }

        /**
         * Method called when a course is being backed up.
         *
         * @return backup_plugin_element
         */
        protected function define_course_plugin_structure() {
            return $this->define_context_plugin_structure($this->get_setting_value('users'));
        }

        /**
         * Method called when an activity is being backed up.
         *
         * @return backup_plugin_element
         */
        protected function define_module_plugin_structure() {
            return $this->define_context_plugin_structure($this->get_setting_value('userinfo'));
        }

        /**
         * Same structure is used for both courses and activities.
         *
         * @param bool $includeusers whether the relevant setting for user data is on.
         * @return backup_plugin_element
         */
        protected function define_context_plugin_structure(bool $includeusers) {
            $plugin = $this->get_plugin_element(null, $this->get_include_condition(), 'include');
            $pluginwrapper = new backup_nested_element($this->get_recommended_name());

            // Define each element separated.
            $attempts = new backup_nested_element('report_embedquestion_attempts');
            $attempt = new backup_nested_element('report_embedquestion_attempt', ['id'],
                    ['userid', 'embedid', 'questionusageid', 'pagename', 'pageurl', 'timecreated', 'timemodified']);

            // Define source - which only applies if the backup includes user data.
            if ($includeusers) {
                $attempt->set_source_table('report_embedquestion_attempt',
                        array('contextid' => backup::VAR_CONTEXTID));
            }

            // Build the tree.
            $plugin->add_child($pluginwrapper);
            $pluginwrapper->add_child($attempts);
            $attempts->add_child($attempt);

            // Add the question attempt data.
            $this->add_question_usages($attempt, 'questionusageid');

            return $plugin;
        }
    }
}
