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
 * Tests for region persistent class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class tool_blocksmanager_block_testcase extends advanced_testcase {

    /**
     * Initial set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test list of properties.
     */
    public function test_properties() {
        $actual = \tool_blocksmanager\block::properties_definition();
        $expected = [
            'region' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_NOT_ALLOWED,
            ],
            'block' => [
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
            'move' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
            'id' => [
                'default' => 0,
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'timecreated' => [
                'default' => 0,
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'timemodified' => [
                'default' => 0,
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'usermodified' => [
                'default' => 0,
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test required properties.
     */
    public function test_required_properties() {
        $this->assertTrue(\tool_blocksmanager\block::is_property_required('region'));
        $this->assertTrue(\tool_blocksmanager\block::is_property_required('block'));
        $this->assertTrue(\tool_blocksmanager\block::is_property_required('categories'));
        $this->assertFalse(\tool_blocksmanager\block::is_property_required('config'));
        $this->assertFalse(\tool_blocksmanager\block::is_property_required('remove'));
        $this->assertFalse(\tool_blocksmanager\block::is_property_required('hide'));
        $this->assertFalse(\tool_blocksmanager\block::is_property_required('move'));
    }

    /**
     * Test that can get action fields.
     */
    public function test_get_action_fields() {
        $this->assertEquals(['config', 'remove', 'hide', 'move'], \tool_blocksmanager\block::get_action_fields());
    }

}
