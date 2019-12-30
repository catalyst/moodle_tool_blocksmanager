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
 * Region instance class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_blocksmanager;

defined('MOODLE_INTERNAL') || die();

use core\persistent;

class region extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'tool_blocksmanager_region';

    /**
     * @inheritdoc
     */
    protected static function define_properties() {
        return [
            'region' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_NOT_ALLOWED,
            ],
            'categories' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED,
            ],
            'config' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 1,
            ],
            'remove' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
            'hide' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 1,
            ],
            'movein' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
            'move' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
        ];
    }

    /**
     * Get a list of action fields.
     * @return array
     */
    public static function get_action_fields() {
        return ['config', 'remove', 'hide', 'movein', 'move'];
    }
}
