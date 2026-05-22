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
 * Embedded questions progress report version information
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026050400;
$plugin->requires  = 2026042000;
$plugin->component = 'report_embedquestion';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '2.0 for Moodle 5.2+';

$plugin->dependencies = [
    'filter_embedquestion' => 2025050100,
    'quiz_answersheets' => 2025061000,
];

$plugin->outestssufficient = true;
