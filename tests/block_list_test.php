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
 * Tests for block_list class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_list_test extends advanced_testcase {

    /**
     * Initial set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test display correct data.
     */
    public function test_display_values() {
        // phpcs:ignore moodle.PHP.ForbiddenGlobalUse.BadGlobal
        global $PAGE;

        // phpcs:ignore moodle.PHP.ForbiddenGlobalUse.BadGlobal
        $PAGE->set_url(new moodle_url('/'));
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $table = new \tool_blocksmanager\table\block_list();

        // Test Yes display.
        $region = new \tool_blocksmanager\block(0, (object)[
            'region' => 'Test region 1',
            'block' => 'Test block 1',
            'categories' => $category1->id . ',' . $category2->id,
            'config' => 1,
            'remove' => 1,
            'hide' => 1,
            'move' => 1,
        ]);
        $this->assertEquals('Test region 1', $table->col_region($region));
        $this->assertEquals('Test block 1', $table->col_block($region));
        $this->assertEquals($category1->name . '<BR />' . $category2->name, $table->col_categories($region));
        $this->assertEquals('Yes', $table->col_config($region));
        $this->assertEquals('Yes', $table->col_remove($region));
        $this->assertEquals('Yes', $table->col_hide($region));
        $this->assertEquals('Yes', $table->col_move($region));

        // Test No display.
        $region = new \tool_blocksmanager\block(0, (object)[
            'region' => 'Test region 2',
            'block' => 'Test block 2',
            'categories' => $category1->id . ',' . $category2->id,
            'config' => 0,
            'remove' => 0,
            'hide' => 0,
            'move' => 0,
        ]);
        $this->assertEquals('Test region 2', $table->col_region($region));
        $this->assertEquals('Test block 2', $table->col_block($region));
        $this->assertEquals($category1->name . '<BR />' . $category2->name, $table->col_categories($region));
        $this->assertEquals('No', $table->col_config($region));
        $this->assertEquals('No', $table->col_remove($region));
        $this->assertEquals('No', $table->col_hide($region));
        $this->assertEquals('No', $table->col_move($region));
    }

    /**
     * Test display correct data if category is not exist.
     */
    public function test_display_not_existing_category() {
        // phpcs:ignore moodle.PHP.ForbiddenGlobalUse.BadGlobal
        global $PAGE;

        // phpcs:ignore moodle.PHP.ForbiddenGlobalUse.BadGlobal
        $PAGE->set_url(new \moodle_url('/'));
        $category1 = $this->getDataGenerator()->create_category();
        $table = new \tool_blocksmanager\table\block_list();

        // Test Yes display.
        $region = new \tool_blocksmanager\block(0, (object)[
            'region' => 'Test region',
            'block' => 'Test block',
            'categories' => $category1->id . ',777,999',
        ]);
        $this->assertEquals('Test region', $table->col_region($region));
        $this->assertEquals('Test block', $table->col_block($region));

        $this->assertEquals($category1->name, $table->col_categories($region));
    }
}
