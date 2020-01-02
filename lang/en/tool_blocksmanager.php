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
$string['blocksmanager:bypasslocking'] = 'Bypass blocks locking';
$string['privacy:metadata'] = 'The Blocks Manager plugin does not store any personal data.';
$string['error:lockedregion'] = 'The region is locked. Changes will not be saved.';
$string['error:lockedefaultregion'] = 'The default region is locked. Block has not been added';
$string['regionrequired'] = 'Region is required';
$string['manageblocklocking'] = 'Blocks management';
$string['manageregionlocking'] = 'Region management';
$string['addregionlocking'] = 'Add new locked region';
$string['addblocklocking'] = 'Add new locked block';
$string['newregionlocking'] = 'New locked region';
$string['newblocklocking'] = 'New locked block';
$string['editregionlocking'] = 'Edit locked region';
$string['editblocklocking'] = 'Edit locked block';
$string['col_block'] = 'Block';
$string['col_region'] = 'Region';
$string['col_categories'] = 'Categories';
$string['col_config'] = 'Configure?';
$string['col_remove'] = 'Delete?';
$string['col_hide'] = 'Hide?';
$string['col_movein'] = 'Add?';
$string['col_move'] = 'Move?';
$string['col_actions'] = 'Actions';
$string['no_regions'] = 'No locked regions';
$string['no_blocks'] = 'No locked blocks';
$string['field_block'] = 'Block';
$string['field_region'] = 'Region';
$string['field_categories'] = 'Categories';
$string['field_config'] = 'Allow to Configure?';
$string['field_remove'] = 'Allow to Delete?';
$string['field_hide'] = 'Allow to Hide?';
$string['field_movein'] = 'Allow to Add?';
$string['field_move'] = 'Allow to Move?';
$string['field_visible'] = 'Visible?';
$string['field_configdata'] = 'Config data';
$string['field_reposition'] = 'Reposition if exists in the region?';
$string['field_secondregion'] = 'Secondary region';
$string['field_secondweight'] = 'Secondary weight';
$string['availableregions'] = 'Potentially available regions:';
$string['duplicaterule'] = 'The same rule is already exist.';
$string['cantuseallregions'] = 'All regions cannot be selected. There is already an existing rule for this block with a specific region.';
$string['cantusespecificregion'] = 'Can\'t use specific region. There is already an existing All rule for this block.';
$string['addnew'] = 'Add new line';
$string['setofblocks'] = 'Set of blocks';
$string['applydesc'] = 'After clicking "Apply" button a new background task to apply provided set of blocks will be created.';
$string['notallrequired'] = 'Incorrect data: not all required fields provided';
$string['emptyregion'] = 'Incorrect data: empty region is not allowed';
$string['emptysecondregion'] = 'Incorrect data: empty secondary region is not allowed, if repositioning is enabled';
$string['emptyblockname'] = 'Incorrect data: empty block name is not allowed';
$string['incorrectcategory'] = 'Incorrect data: incorrect category id provided';
$string['setuptitle'] = 'Set up blocks';
$string['setupheading'] = 'Add specific set of blocks';
