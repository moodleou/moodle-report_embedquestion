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
 * Attempt download functions for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\local\export;
use context;
use file_archive;
use question_bank;
use stored_file;
use zip_archive;

defined('MOODLE_INTERNAL') || die();

/**
 * Attempt download functions for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_export {

    /**
     * Return an array listing all the question types which might have response files.
     *
     * @return array qtype name (e.g. essay) => array of response file area names.
     */
    protected static function get_qtype_response_files(): array {
        $qtypefileareas = [];

        foreach (question_bank::get_all_qtypes() as $qtype) {
            $areas = $qtype->response_file_areas();
            if ($areas) {
                $qtypefileareas[$qtype->name()] = $areas;
            }
        }

        return $qtypefileareas;
    }

    /**
     * Check that the given qtype name has response contain file or not
     *
     * @param string $qtypename Qtype name
     * @return bool
     */
    public static function is_qtype_has_response_contain_file(string $qtypename): bool {
        return array_key_exists($qtypename, self::get_qtype_response_files());
    }

    /**
     * Get all the response files by the given question usage ids, context. After that, archive all the files into a zip file and
     * return the zip file's info such as file name and file size.
     *
     * @param array $questionusageids Question usage ids
     * @param context $context Context
     * @return array [file => File name, size => File size]
     */
    public static function get_response_zip_file_info(array $questionusageids, context $context, int $userid): array {
        global $CFG, $DB;

        $hasfile = false;
        $coursecontext = $context->get_course_context();
        $course = get_course($coursecontext->instanceid);

        $zipfilename = $course->shortname;
        if ($userid) {
            // Append the student's username to the zip's filename.
            $user = \core_user::get_user($userid, get_all_user_name_fields(true) . ',username');
            $zipfilename .= '_' . $user->username;
        }
        $zipfilename .= '_' . date('Ymd') . '_' . $context->get_context_name(false, false);
        $zipfilename = str_replace(' ', '-', $zipfilename);

        // Cache folder.
        $cachefolder = $CFG->dataroot . '/cache/report_embedquestion/download';
        // Check the cache folder is exist and create one if not.
        check_dir_exists($cachefolder, true, true);
        $filepath = $cachefolder . '/' . $zipfilename . '.zip';
        if (file_exists($filepath)) {
            // Remove the old file if exist.
            unlink($filepath);
        }
        // Create a new zip file.
        $zip_archive = new zip_archive();
        $zip_archive->open($filepath, file_archive::CREATE);

        foreach ($questionusageids as $qubaid) {
            if (!$userid) {
                $attemptinfo = $DB->get_record('report_embedquestion_attempt', ['questionusageid' => $qubaid], '*', MUST_EXIST);
                $userinfo = \core_user::get_user($attemptinfo->userid, get_all_user_name_fields(true) . ',username');
            }
            $quba = \question_engine::load_questions_usage_by_activity($qubaid);
            foreach ($quba->get_slots() as $slotno) {
                $question = $quba->get_question($slotno);
                $slotnoformat = 'attempt' . str_pad($slotno, 4, '0', STR_PAD_LEFT);
                // Check that the question has the responses files or not.
                if (self::is_qtype_has_response_contain_file($question->get_type_name())) {
                    $qa = $quba->get_question_attempt($slotno);
                    $fileareas = $qa->get_question()->qtype->response_file_areas();
                    foreach ($fileareas as $filearea) {
                        $files = $qa->get_last_qt_files($filearea, $quba->get_owning_context()->id);
                        if (!$files) {
                            // This attempt has no files in this area.
                            continue;
                        }
                        $hasfile = true;
                        foreach ($files as $file) {
                            /** @var stored_file $file */
                            $localname = '';
                            if (!$userid) {
                                $localname = $userinfo->username . '/';
                            }
                            $localname .= $question->get_type_name() . '/' . $slotnoformat . '/' . $file->get_filename();
                            $zip_archive->add_file_from_string($localname, $file->get_content());
                        }
                    }
                }
            }
        }

        if (!$hasfile) {
            throw new \coding_exception('Invalid question usage id');
        }

        // Persist the zip file to the disk.
        $zip_archive->close();

        return [
                'file' => $zipfilename,
                'size' => filesize($filepath)
        ];
    }
}
