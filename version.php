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
 * blocks/taskchain_navigation/version.php
 *
 * @package    blocks
 * @subpackage taskchain_navigation
 * @copyright  2014 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

$plugin->component = 'block_taskchain_navigation';
$plugin->maturity  = MATURITY_STABLE;
$plugin->requires  = 2010112400; // Moodle 2.0
$plugin->version   = 2020103026;
$plugin->release   = '2020-10-30 (26)';

if (defined('ANY_VERSION')) { // Moodle >= 2.2
    $plugin->dependencies = array('mod_taskchain' => ANY_VERSION);
}
