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
 * blocks/taskchain_navigation/db/install.php
 *
 * @package    blocks
 * @subpackage taskchain_navigation
 * @copyright  2014 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

function xmldb_block_taskchain_navigation_install() {
    global $DB;

    // convert all quizport_navigation blocks to taskchain_navigation
    $params = array('blockname' => 'quizport_navigation');
    $DB->set_field('block_instances', 'blockname', 'taskchain_navigation', $params);
}
