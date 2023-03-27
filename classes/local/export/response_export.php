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

namespace report_embedquestion\local\export;

use DOMDocument;
use report_embedquestion\utils;
use context;
use question_bank;
use stdClass;
use stored_file;
use zip_archive;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/quiz/report/answersheets/classes/output/match/renderer.php');
require_once($CFG->dirroot . '/mod/quiz/report/answersheets/classes/output/renderer.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');

/**
 * Attempt download functions for report_embedquestion.
 *
 * @package   report_embedquestion
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_export {

    /** @var array Array tag name we need to get in html documment. */
    const HTML_TAGS_WITH_SRC = ['audio', 'video', 'img', 'source'];

    /** @var array The url of resources. */
    protected $urlresources = [];

    /** @var array The url of icons. */
    protected $urlicons = [];

    /** @var string The pre-loaded css content. */
    protected $csscontent = '';

    /** @var array The list of question types have javascript need to include. */
    const QUESTION_TYPES_HAVE_JS = ['ddmarker', 'ddimageortext', 'ddwtos', 'crossword', 'pmatch'];

    /** @var context The context of the course. */
    protected $coursecontext;

    /** @var stdClass course object */
    protected $course;

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
        global $DB, $PAGE;
        $self = new self();

        $self->coursecontext = $context->get_course_context();
        $self->course = get_course($self->coursecontext->instanceid);
        $zipfilename = self::get_export_file_name($self->course, $context->get_context_name(false, false));

        // Get zip file path from temporary folder.
        $filepath = utils::get_file_path_from_temporary_dir($zipfilename . '.zip');
        if (file_exists($filepath)) {
            // Remove the old file if exist.
            unlink($filepath);
        }
        // Create a new zip file.
        $ziparchive = new zip_archive();
        $ziparchive->open($filepath);

        // Generate the css content first.
        if (!$self->csscontent = $PAGE->theme->get_css_cached_content()) {
            $self->csscontent = $PAGE->theme->get_css_content();
        }

        // If there is more than one user, we will create a subfolder for their own user information.
        $issubfolder = count($questionusageids) === 1 ? true : false;

        foreach ($questionusageids as $qubaid) {
            if (!$userid) {
                $attemptinfo = $DB->get_record('report_embedquestion_attempt', ['questionusageid' => $qubaid], '*', MUST_EXIST);
                [$user, $info] = utils::get_user_details($attemptinfo->userid, $context);
            }
            $quba = \question_engine::load_questions_usage_by_activity($qubaid);
            foreach ($quba->get_slots() as $slotno) {
                $question = $quba->get_question($slotno);
                $qa = $quba->get_question_attempt($slotno);

                if (count($quba->get_slots()) === 1 && $qa->get_state() === \question_state::$todo) {
                    continue;
                }

                // Setup directory.
                $filedirectory = '';
                if (!$userid && !$issubfolder) {
                    $filedirectory = get_string('crumbtrailembedquestiondetail', 'report_embedquestion',
                            ['fullname' => fullname($user), 'info' => implode(', ', $info)]) . '/';
                }
                $slotnoformat = 'attempt' . str_pad($slotno, 4, '0', STR_PAD_LEFT);
                $questionname = self::format_filename($question->name);
                $filedirectory .= $questionname . '/' . $slotnoformat . '/';

                // Check that the question has the responses files or not.
                if (self::is_qtype_has_response_contain_file($question->get_type_name())) {
                    $fileareas = $qa->get_question()->qtype->response_file_areas();
                    foreach ($fileareas as $filearea) {
                        $files = $qa->get_last_qt_files($filearea, $quba->get_owning_context()->id);
                        if (!$files) {
                            // This attempt has no files in this area.
                            continue;
                        }
                        /** @var stored_file $file */
                        foreach ($files as $file) {
                            $ziparchive->add_file_from_string($filedirectory . $file->get_filename(), $file->get_content());
                        }
                    }
                }

                $self->render_question_to_html_in_zip($ziparchive, $filedirectory, $quba, $qa);
                $self->render_resources_to_html_in_zip($ziparchive, $filedirectory);
                $self->render_icon_to_html_in_zip($ziparchive, $filedirectory);
            }
        }

        // Persist the zip file to the disk.
        $ziparchive->close();

        return [
                'file' => $zipfilename,
                'size' => filesize($filepath)
        ];
    }

    /**
     *  Process data to render question by html.
     *
     * @param zip_archive $ziparchive Zip archive instance.
     * @param string $filedirectory File directory.
     * @param \question_usage_by_activity $quba The usage to check.
     * @param \question_attempt $qa Question attempt to check.
     */
    public function render_question_to_html_in_zip(zip_archive $ziparchive, string $filedirectory,
            \question_usage_by_activity $quba, \question_attempt $qa): void {
        global $OUTPUT, $PAGE;

        $page = new \moodle_page();
        $page->set_context($this->coursecontext);
        $page->set_course($this->course);
        $page->set_pagelayout('report');
        $page->set_pagetype('report-embedquestion-activity');
        $page->set_url($PAGE->url);

        $behaviouroutput = $page->get_renderer(get_class($qa->get_behaviour()));
        $qtoutput = \quiz_answersheets\utils::get_question_renderer($page, $qa);
        $qoutput = $page->get_renderer('quiz_answersheets', 'core_question_override');
        $displayoption = new \question_display_options();
        $questionname = self::format_filename($qa->get_question()->name);
        if (\quiz_answersheets\utils::should_show_combined_feedback($qa->get_question()->get_type_name())) {
            $displayoption->generalfeedback = \question_display_options::HIDDEN;
            $displayoption->numpartscorrect = \question_display_options::HIDDEN;
            $displayoption->rightanswer = \question_display_options::HIDDEN;
        }
        $displayoption->context = $quba->get_owning_context();

        $bodystring = $qoutput->question($qa, $behaviouroutput, $qtoutput, $displayoption, 0);
        $bodystring = $this->replace_resource_urls($bodystring, self::HTML_TAGS_WITH_SRC);
        $jshead = $jstopbody = $jsendcode = '';
        if (in_array($qa->get_question()->get_type_name(), self::QUESTION_TYPES_HAVE_JS)) {
            $jshead = $page->requires->get_head_code($page, $OUTPUT);
            $jstopbody = $page->requires->get_top_of_body_code($OUTPUT);
            $jsendcode = $page->requires->get_end_code();
        }

        $htmlstring = "<!DOCTYPE html>
                <html lang='en'>
                <head>
                    <title>$questionname</title>
                    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                    $jshead
                    <style>
                        $this->csscontent
                    </style>
                </head>
                <body style='padding: 0 15px'>
                    $jstopbody
                    $bodystring
                    $jsendcode
                </body>
                </html>";

        $ziparchive->add_file_from_string($filedirectory . $questionname . '-response.html', $htmlstring);
    }

    /**
     * Get all icons to render in the HTML file.
     *
     * @param zip_archive $ziparchive Zip archive instance.
     * @param string $filedirectory File directory.
     */
    public function render_icon_to_html_in_zip(zip_archive $ziparchive, string $filedirectory): void {
        global $COURSE, $CFG;

        array_filter($this->urlicons);
        if (empty($this->urlicons)) {
            return;
        }

        $themename = $COURSE->theme ? $COURSE->theme : $CFG->theme;
        $theme = \theme_config::load($themename);

        foreach ($this->urlicons as $index => $url) {
            [$icon, $imagename] = self::get_icon_from_themeurl($url, $theme);

            if ($icon) {
                $ziparchive->add_file_from_string($filedirectory . $imagename . '.svg', $icon);
            }
            unset($this->urlicons[$index]);
        }
    }

    /**
     * Get all resources such as image, audio, video in the plugingfile to render in the HTML file.
     *
     * @param zip_archive $ziparchive Zip archive instance.
     * @param string $filedirectory File directory.
     */
    public function render_resources_to_html_in_zip(zip_archive $ziparchive, string $filedirectory): void {
        array_filter($this->urlresources);
        if (empty($this->urlresources)) {
            return;
        }

        foreach ($this->urlresources as $index => $url) {
            $this->get_resource($url, $ziparchive, $filedirectory);
            unset($this->urlresources[$index]);
        }
    }

    /**
     * Export file from url.
     *
     * @param string $url The pluginfile URL.
     * @param zip_archive $ziparchive Zip archive instance.
     * @param string $filedirectory File directory.
     */
    public function get_resource(string $url, zip_archive $ziparchive, string $filedirectory): void {
        $file = self::get_file_from_pluginfile_url($url);

        if ($file) {
            $ziparchive->add_file_from_string($filedirectory . $file->get_filename(), $file->get_content());
        }
    }

    /**
     * Replace url in the tag resource such as image, video, audio.
     *
     * @param string $bodystring HTML representation of the question.
     * @param array $tags The name tag to get url resources
     * @return string HTML string to export.
     */
    public function replace_resource_urls(string $bodystring, array $tags): string {
        // Load HTML and suppress any parsing errors (DOMDocument->loadHTML() does not current support HTML5 tags).
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $bodystring, LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();
        // Find all tags.
        foreach ($tags as $tag) {
            if ($nodes = $dom->getElementsByTagName($tag)) {
                // Replace nodes with the new url text without overriding DOM elements.
                for ($i = ($nodes->length - 1); $i >= 0; $i--) {
                    $node = $nodes->item($i);
                    $url = ($node->hasAttribute('src')) ? $node->getAttribute('src') : '';
                    $urlexplode = explode('/', ltrim($url, '/'));
                    if (in_array("pluginfile.php", $urlexplode)
                            || in_array("draftfile.php", $urlexplode)) {
                        array_push($this->urlresources, $url);
                        $this->update_src_url($node, $urlexplode);
                    }

                    // With icon we add full path icon.
                    if (in_array("theme", $urlexplode)) {
                        array_push($this->urlicons, $url);
                        $this->update_src_url($node, $urlexplode, true);
                    }
                }
            }
        }

        return $dom->saveHTML();
    }

    /**
     * Update url for source element.
     *
     * @param $node DOMNode The node needs to update the URL in the tags matched.
     * @param $urlexplode array The origin URL has been exploded.
     * @param false $iscon If icon is true we concatenation .svg with URL.
     */
    public function update_src_url($node, $urlexplode, $iscon = false): void {
        $newurl = './' . $urlexplode[count($urlexplode) - 1];
        if ($iscon) {
            $newurl = './' . $urlexplode[count($urlexplode) - 1] . '.svg';
        }

        $node->setAttribute('src', $newurl);
    }

    /**
     * Get the file for a pluginfile URL. If it doesn't exist, it's not created.
     *
     * @param string $url Pluginfile URL.
     * @return \stored_file|bool Stored_file instance if exists, false if not.
     */
    public static function get_file_from_pluginfile_url(string $url) {
        // Decode the URL before start processing it.
        $url = new \moodle_url(urldecode($url));

        // Remove params from the URL (such as the 'forcedownload=1'), to avoid errors.
        $url->remove_params(array_keys($url->params()));
        $path = $url->out_as_local_url();

        // We only need the slasharguments.
        $path = substr($path, strpos($path, '.php/') + 5);
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        $itemid = array_pop($parts);

        // Get the contextid, component and filearea.
        $contextid = array_shift($parts);
        $component = array_shift($parts);
        $filearea = array_shift($parts);

        // Get the file.
        $fs = get_file_storage();
        $hashname = $fs->get_pathname_hash($contextid, $component, $filearea, $itemid, '/', $filename);

        return $fs->get_file_by_hash($hashname);
    }

    /**
     * Get the icon for a themeplugin URL and path of icon.
     *
     * @param string $url Icon url.
     * @param \theme_config $theme An instance of themconfig class.
     * @return array A pair of icon file and path icon
     */
    public static function get_icon_from_themeurl(string $url, \theme_config $theme): array {

        $url = substr($url, strpos($url, '.php/') + 5);

        list($themename, $component, $rev, $icon) = explode('/', $url, 4);
        $pathicon = explode("/", $icon);
        $pathicon = array_pop($pathicon);
        $iconfile = $theme->resolve_image_location($icon, $component, true);

        if (empty($iconfile)) {
            return [null, null];
        }

        return [file_get_contents($iconfile), $pathicon];
    }

    /**
     * Get the export filename.
     *
     * @param stdClass $course
     * @param string $activityname
     * @return string
     */
    public static function get_export_file_name(stdClass $course, string $activityname): string {
        $base = clean_filename(get_string('downloadresponse_filename', 'report_embedquestion'));
        $shortname = clean_filename($course->shortname);
        if ($shortname == '' || $shortname == '_') {
            $shortname = $course->id;
        }

        return "$shortname $activityname $base";
    }

    /**
     * Format filename by removing suspicious or troublesome characters.
     *
     * @return string The filename is formatted.
     */
    public static function format_filename(string $filename): string {
        return clean_filename(str_replace('/', '-', $filename));
    }
}
