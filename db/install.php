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
 * Embed question report install script.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_embedquestion\utils;

/**
 * Extra install code for the Embedded questions progress report.
 */
function xmldb_report_embedquestion_install() {
    // In Moodle >=4.6, we need to update the question prototypes in a scheduled task
    // because (a) if this is an install, the qbank module hasn't been installed yet and
    // (b) we need to call question_bank_helper::get_default_open_instance_system_type
    // which cannot be used during install/upgrade.
    // We also don't want to do this during PHPUnit tests because it will cause unit test failed in core/question.
    if (!PHPUNIT_TEST) {
        $task = new report_embedquestion\task\setup_placeholder_question();
        core\task\manager::queue_adhoc_task($task);
    }
}
