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
 * Report filter form class.
 *
 * @package    report_embedquestion
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_embedquestion\form;

use context;
use report_embedquestion\attempt_tracker;
use report_embedquestion\report_display_options;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Report filter form class.
 *
 * @package    report_embedquestion
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter extends \moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'heading', get_string('filtering', 'report_embedquestion'));

        if (isset($this->_customdata['context'])) {
            /** @var context $context */
            $context = $this->_customdata['context'];
            if ($context->contextlevel == CONTEXT_COURSE) {
                // Only show the Location filtering for Course level report.
                $cache = \cache::make(attempt_tracker::CACHE_COMPONENT, attempt_tracker::CACHE_AREA);
                $cachedata = $cache->get($context->id)['subcontext'];

                $locations = [];
                foreach ($cachedata as $id => $hasembedquestion) {
                    if ($hasembedquestion) {
                        $locations[$id] = context::instance_by_id($id)->get_context_name(false, false);
                    }
                }

                $mform->addElement('autocomplete', 'locationids', get_string('reportattemptsfrom', 'quiz'), $locations,
                        ['multiple' => true, 'noselectionstring' => get_string('allactivities')]);
            }
        }

        // Look back.
        $options = [
            0 => get_string('choose'),
            DAYSECS => get_string('nday', 'report_embedquestion', 1),
            DAYSECS * 2 => get_string('ndays', 'report_embedquestion', 2),
            DAYSECS * 3 => get_string('ndays', 'report_embedquestion', 3),
            DAYSECS * 4 => get_string('ndays', 'report_embedquestion', 4),
            DAYSECS * 5 => get_string('ndays', 'report_embedquestion', 5),
            DAYSECS * 6 => get_string('ndays', 'report_embedquestion', 6),
            WEEKSECS => get_string('nweek', 'report_embedquestion', 1),
            WEEKSECS * 2 => get_string('nweeks', 'report_embedquestion', 2),
            WEEKSECS * 3 => get_string('nweeks', 'report_embedquestion', 3),
            WEEKSECS * 4 => get_string('nweeks', 'report_embedquestion', 4),
            WEEKSECS * 5 => get_string('nweeks', 'report_embedquestion', 5),
            WEEKSECS * 6 => get_string('nweeks', 'report_embedquestion', 6),
            WEEKSECS * 7 => get_string('nweeks', 'report_embedquestion', 7),
        ];
        $mform->addElement('select', 'lookback', get_string('lookback', 'report_embedquestion'), $options);

        // Date from.
        $mform->addElement('date_selector', 'datefrom', get_string('datefrom', 'report_embedquestion'), ['optional' => true]);
        $mform->disabledIf('datefrom', 'lookback', 'neq', 0);

        // Date to.
        $mform->addElement('date_selector', 'dateto', get_string('dateto', 'report_embedquestion'), ['optional' => true]);
        $mform->disabledIf('dateto', 'lookback', 'neq', 0);

        // Page size.
        $mform->addElement('text', 'pagesize', get_string('pagesize', 'mod_quiz'));
        $mform->setType('pagesize', PARAM_INT);
        $mform->setDefault('pagesize',
                get_user_preferences('report_embedquestion_pagesize', report_display_options::DEFAULT_REPORT_PAGE_SIZE));
        $mform->addRule('pagesize', get_string('err_numeric', 'form'), 'numeric', '', 'client');
        $mform->addRule('pagesize', get_string('err_maxlength', 'form', ['format' => 3]), 'maxlength', 3, 'client');

        $mform->closeHeaderBefore('submitbutton');
        $mform->addElement('submit', 'submitbutton', get_string('showreport', 'mod_quiz'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['lookback'] > 0 && ($data['datefrom'] > 0 || $data['dateto'] > 0)) {
            $errors['lookback'] = get_string('err_filterdate', 'report_embedquestion');
        }
        if ($data['datefrom'] > 0 && $data['dateto'] > 0 && $data['datefrom'] > $data['dateto']) {
            $errors['dateto'] = get_string('err_filterdatetolesthan', 'report_embedquestion');
        }
        if ($data['pagesize'] <= 0) {
            $errors['pagesize'] = get_string('err_pagesize', 'report_embedquestion');
        }
        return $errors;
    }
}
