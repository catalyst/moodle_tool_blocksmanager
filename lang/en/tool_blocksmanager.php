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
 * Plugin strings are defined here.
 *
 * @package     tool_blocksmanager
 * @category    string
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Blocks Manager';

$string['lockingheading'] = 'Region locking';
$string['lockedregions'] = 'Locked regions';
$string['lockedregions_desc'] = 'A comma separated list of regions to lock. E.g. side-pre,side-post,center. If locked, blocks cannot be added/deleted or moved/reordered within this region. <br />Regions potentially available for selected theme: {$a}';
$string['lockedcategories'] = 'Locked course categories';
$string['lockedcategories_desc'] = 'Block regions in courses from the selected course categories (including child categories) will be locked according to "Locked regions" settings.';
$string['blocksmanager:bypasslocking'] = 'Bypass blocks locking';
$string['privacy:metadata'] = 'The Blocks Manager plugin does not store any personal data.';
$string['error:lockedregion'] = 'The region is locked. Position changes will not be saved.';
$string['error:lockedefaultregion'] = 'The default region is locked. Block has not been added';