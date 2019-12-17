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
 * Tests for blocks class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class tool_blocksmanager_blocks_testcase extends advanced_testcase {

    /**
     * Initial set up.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Data provide to test locke region.
     * @return array
     */
    public function is_locked_region_data_provider() {
        return [
            'Everything is empty' => ['', '', false],
            'Region is locked' => ['locked', 'test,locked,test1', true],
            'Region is not locked' => ['not_locked', 'test,locked,test1', false],
            'Config with spaces' => ['locked', '  test, locked  , test1 ', true],
            'Empty config' => ['notlocked', '', false],
            'Empty region' => ['', 'test,locked,test1, ,', false],
            'Space region' => [' ', 'test,locked,test1, ,', false],
        ];
    }

    /**
     * Test that we can check if the region is locked.
     *
     * @dataProvider  is_locked_region_data_provider
     *
     * @param $region
     * @param $config
     * @param $expected
     */
    public function test_is_locked_region($region, $config, $expected) {
        set_config('lockedregions', $config, 'tool_blocksmanager');
        $page = new moodle_page();
        $blocks = new \tool_blocksmanager\blocks($page);

        $this->assertEquals($expected, $blocks->is_locked_region($region));
    }
}
