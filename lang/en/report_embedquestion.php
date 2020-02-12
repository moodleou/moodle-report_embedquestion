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
 * Embedded questions progress report language strings.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activityreporttitle'] = 'Embedded question progress for {$a}';
$string['attemptedon'] = 'Attempted on';
$string['attemptfinal'] = 'Latest attempt';
$string['attempts'] = 'Embedded question attempts';
$string['attemptsatquestion'] = 'Attempts at question {$a}';
$string['attemptsummary'] = 'Attempt summary';
$string['attemptsummaryfor'] = 'Attempt summary for:';
$string['attemptsummaryby'] = 'Attempted by: {$a}';
$string['attemptsummaryembedid'] = 'Embed id: {$a}';
$string['attemptsummarycontext'] = 'Location: {$a}';
$string['attemptsummaryquestion'] = 'Question: {$a}';
$string['coursereporttitle'] = 'Embedded question progress for {$a}';
$string['datefilter'] = 'Date filter';
$string['datefrom'] = 'From';
$string['dateto'] = 'To';
$string['embedquestion:viewallprogress'] = 'See embedded questions progress for others';
$string['embedquestion:viewmyprogress'] = 'See your own embedded questions progress';
$string['eventactivityreportviewed'] = 'Embedded questions progress activity report viewed';
$string['eventcoursereportviewed'] = 'Embedded questions progress course report viewed';
$string['err_filterdate'] = 'You cannot filter by both \'Look back\' and \'From/To\' at the same time. Please only use one.';
$string['err_filterdatetolesthan'] = 'The end date cannot be earlier than the start date.';
$string['filter'] = 'Filter';
$string['lookback'] = 'Look back';
$string['nday'] = '{$a} day';
$string['ndays'] = '{$a} days';
$string['nweek'] = '{$a} week';
$string['nweeks'] = '{$a} weeks';
$string['page-report-embedquestion-*'] = 'Any embedded questions progress report';
$string['page-report-embedquestion-activity'] = 'Activity embedded questions progress report';
$string['page-report-embedquestion-index'] = 'Course embedded questions progress report';
$string['pluginname'] = 'Embedded questions progress';
$string['privacy:metadata:core_question'] = 'The embedded questions progress report stores question usage information in the core_question subsystem.';
$string['privacy:metadata:report_embedquestion_attempt'] = 'Information about which embedded questions each user has attempted.';
$string['privacy:metadata:report_embedquestion_attempt:contextid'] = 'The Moodle context where the question was attempted';
$string['privacy:metadata:report_embedquestion_attempt:embedid'] = 'The internal id of the question that was embedded';
$string['privacy:metadata:report_embedquestion_attempt:pagename'] = 'The name of the page where the question was embedded';
$string['privacy:metadata:report_embedquestion_attempt:pageurl'] = 'The URL of the page where the question was embedded';
$string['privacy:metadata:report_embedquestion_attempt:timecreated'] = 'The time when the user started attempting this question';
$string['privacy:metadata:report_embedquestion_attempt:timemodified'] = 'The time when the user most recently interacted with this question';
$string['privacy:metadata:report_embedquestion_attempt:userid'] = 'The user who attempted the question';
$string['type'] = 'Type';
