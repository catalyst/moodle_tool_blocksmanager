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
 * Tests for helper  class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blocksmanager_helper_test extends advanced_testcase {

    /**
     * Initial set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test can get categories and their children.
     */
    public function test_get_categories_and_children() {
        $category1 = $this->getDataGenerator()->create_category();
        $category11 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category111 = $this->getDataGenerator()->create_category(['parent' => $category11->id]);
        $category2 = $this->getDataGenerator()->create_category();

        $this->assertSame(
            [$category1->id, $category11->id, $category111->id],
            \tool_blocksmanager\helper::get_categories_and_children($category1->id)
        );

        $this->assertSame(
            [$category11->id, $category111->id],
            \tool_blocksmanager\helper::get_categories_and_children($category11->id)
        );

        $this->assertSame(
            [$category111->id],
            \tool_blocksmanager\helper::get_categories_and_children($category111->id)
        );

        $this->assertSame(
            [$category2->id],
            \tool_blocksmanager\helper::get_categories_and_children($category2->id)
        );

        $this->assertSame(
            [$category1->id, $category11->id, $category111->id],
            \tool_blocksmanager\helper::get_categories_and_children($category1->id . ',' . $category11->id)
        );
    }

}
