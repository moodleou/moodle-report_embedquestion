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
 * Restore step for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_report_embedquestion_plugin extends restore_report_plugin {
    use restore_questions_attempt_data_trait;

    /**
     * @var stdClass data can only be inserted after the question attept
     * data is stored and we know the usage id. Therefore,
     * process_report_embedquestion_attempt sets this, and then inform_new_usage_id
     * saves it.
     */
    protected $currentattemptdata;

    /**
     * Method called when a course is being restored.
     *
     * @return restore_path_element[];
     */
    protected function define_course_plugin_structure() {
        if (!$this->get_setting_value('users')) {
            // Not including userdata, so nothing to do.
            return [];
        }

        return $this->define_context_plugin_structure();
    }

    /**
     * Method called when an activity is being restored.
     *
     * @return restore_path_element[];
     */
    protected function define_module_plugin_structure() {
        if (!$this->get_setting_value('userinfo')) {
            // Not including userdata, so nothing to do.
            return [];
        }

        return $this->define_context_plugin_structure();
    }

    /**
     * Same structure is used for both courses and activities.
     *
     * @return restore_path_element[];
     */
    protected function define_context_plugin_structure() {
        $paths = [];

        // Our data.
        $attempt = new restore_path_element('report_embedquestion_attempt',
                $this->get_pathfor('/report_embedquestion_attempts/report_embedquestion_attempt'));
        $paths[] = $attempt;

        // Add question attempt data.
        $this->add_question_usages($attempt, $paths);

        return $paths;
    }

    public function process_report_embedquestion_attempt($data) {
        $data = (object) $data;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->contextid = $this->task->get_contextid();

        // We cannot insert this record until the question attempt data has been
        // restored, so stash this away for now. It will be picked up in
        // inform_new_usage_id which will be called soon.
        $this->currentattemptdata = $data;
    }

    public function process_question_attempt($data) {
        global $DB;
        // We need to override the trait method. It we are restoring a
        // single-activity backup, then the questions won't be included
        // (because the questions always come from course context).
        // In this case, it is quite likely that the right thing to do
        // is to link to the same questionid - if it exists on this system.
        if (!$this->get_mapping('question', $data['questionid'])) {
            $question = $DB->get_record('question', ['id' => $data['questionid']]);
            if ($question) {
                $this->set_mapping('question', $data['questionid'], $data['questionid']);
                // However, for this to work, we need to set the 'info' of the mapping,
                // which we can only do at a low level.
                $mapping = $this->get_mapping('question', $data['questionid']);
                restore_dbops::set_backup_ids_record($this->get_restoreid(),
                        'question', $mapping->itemid, $mapping->newitemid, $mapping->parentitemid,
                        (object) ['qtype' => $question->qtype]);
            }
        }
        $this->restore_question_attempt_worker($data, '');
    }

    protected function inform_new_usage_id($newusageid) {
        global $DB;
        // Retrieve data stashed by process_report_embedquestion_attempt.
        $data = $this->currentattemptdata;
        $data->questionusageid = $newusageid;
        $DB->insert_record('report_embedquestion_attempt', $data);
    }
}
