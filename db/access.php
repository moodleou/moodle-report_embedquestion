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
 * Embedded questions progress report capability definitions.
 *
 * @package   report_embedquestion
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Ability for a user to see their own progress at embedded questions.
    'report/embedquestion:viewmyprogress' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Ability for a user to see the progress of other users (subject to
    // other conditions like separate groups mode).
    'report/embedquestion:viewallprogress' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Ability for a user to delete the attempts of other users.
    'report/embedquestion:deleteanyattempt' => [
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => [
                    'editingteacher' => CAP_ALLOW,
                    'manager' => CAP_ALLOW,
            ],
            'clonepermissionsfrom' => 'report/embedquestion:deleteattempt',
    ],
    // Ability for a user to delete the own attempts.
    'report/embedquestion:deletemyattempt' => [
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => [
                    'student' => CAP_ALLOW,
                    'teacher' => CAP_ALLOW,
                    'editingteacher' => CAP_ALLOW,
                    'manager' => CAP_ALLOW,
            ],
    ],
];
