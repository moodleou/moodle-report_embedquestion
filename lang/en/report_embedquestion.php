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
$string['attempt-detail-page'] = 'Attempt detail view';
$string['attemptsummary'] = 'Attempt summary';
$string['attemptsummaryfor'] = 'Attempt summary for:';
$string['attemptsummaryby'] = 'Attempted by: {$a}';
$string['attemptsummaryembedid'] = 'Embed id: {$a}';
$string['attemptsummarycontext'] = 'Location: {$a}';
$string['cachedef_reportattempttracker'] = 'Report attempt tracker cache';
$string['attemptsummaryquestion'] = 'Question: {$a}';
$string['closeattemptview'] = 'Close question attempt review';
$string['coursereporttitle'] = 'Embedded question progress for {$a}';
$string['crumbtrailembedquestiondetail'] = '{$a->fullname} ({$a->info})';
$string['datefilter'] = 'Date filter';
$string['datefrom'] = 'Attempts made after';
$string['dateto'] = 'Attempts made before';
$string['downloadresponseinfo'] = 'Some of your question responses contain files that you can download. These may be attachments such as audio/video recordings, or longer essay-form responses.';
$string['downloadresponseinfo_help'] = 'You can download all audio/video or essay-form responses in this document using the \'Download all response files\' button, or you can select individual questions and use the \'Download selected response files\' button. Note that either button will only export the responses for Essay or \'Record audio/video\' questions.';
$string['downloadresponse_backtoreport'] = 'Return to the embedded question progress table';
$string['downloadresponse_buttonall'] = 'Download all response files';
$string['downloadresponse_buttonselected'] = 'Download selected response files';
$string['downloadresponse_downloadto_device'] = 'Download to device';
$string['downloadresponse_error'] = 'The file you requested to download is no longer available.';
$string['downloadresponse_exportsuccessheader'] = 'Your file has been exported';
$string['downloadresponse_exportserviceinfo'] = 'You can download, or export, these files for use offline or on a mobile device, using the links below.';
$string['downloadresponse_filename'] = ' question files';
$string['downloadresponse_title'] = 'Download zip or export the response files';
$string['embedquestion:viewallprogress'] = 'See embedded questions progress for others';
$string['embedquestion:viewmyprogress'] = 'See your own embedded questions progress';
$string['embedquestion:deleteanyattempt'] = 'Delete the attempts of other users';
$string['embedquestion:deletemyattempt'] = 'Delete the own attempts';
$string['eventactivityreportviewed'] = 'Embedded questions progress activity report viewed';
$string['eventcoursereportviewed'] = 'Embedded questions progress course report viewed';
$string['err_filterdate'] = 'You cannot filter by both \'Look back\' and \'From/To\' at the same time. Please only use one.';
$string['err_filterdatetolesthan'] = 'The end date cannot be earlier than the start date.';
$string['err_pagesize'] = 'The page size can not be a negative number';
$string['filtering'] = 'Filtering';
$string['latestattemptstatus'] = 'Latest attempt status';
$string['locationfilter'] = 'Location filter';
$string['lookback'] = 'Attempts made in the';
$string['nday'] = 'Last {$a} day';
$string['ndays'] = 'Last {$a} days';
$string['nweek'] = 'Last {$a} week';
$string['nweeks'] = 'Last {$a} weeks';
$string['page-report-embedquestion-*'] = 'Any embedded questions progress report';
$string['page-report-embedquestion-activity'] = 'Activity embedded questions progress report';
$string['page-report-embedquestion-index'] = 'Course embedded questions progress report';
$string['page-report-embedquestion-progress-detail'] = 'Embedded question progress detail';
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
$string['privacy:preference:report_embedquestion_attempt:pagesize'] = 'The number of attempts to show on each page of the report';
$string['type'] = 'Type';
$string['showeverybody'] = 'Show everybody';
$string['showonly'] = 'Show only';
$string['showonly_heading'] = 'Showing only {$a}';
$string['viewallattemptsforthisuser'] = 'View all embedded question latest attempts for this user';

