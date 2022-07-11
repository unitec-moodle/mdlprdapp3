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
 * Webservice definitions for block_carousel
 *
 * @package   block_carousel
 *
 * @copyright TRL Education Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$functions = [
    'block_carousel_update_slide_order' => [
        'classname' => 'block_carousel\external\external',
        'methodname' => 'process_update_action',
        'description' => 'Process AJAX slide arrangement.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => ['moodle/block:edit'],
    ],
    'block_carousel_record_interaction' => [
        'classname' => 'block_carousel\external\external',
        'methodname' => 'record_interaction',
        'description' => 'Record a user interaction with a slide.',
        'type' => 'update',
        'ajax' => true,
        'capabilities' => [],
    ]
];
