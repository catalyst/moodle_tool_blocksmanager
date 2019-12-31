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
 * Tests for setup_item class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class tool_blocksmanager_setup_item_testcase extends advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_delimiter() {
        $this->assertSame('||', \tool_blocksmanager\setup_item::DATA_DELIMITER);
    }

    public function test_exception_if_not_all_required_fileds() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: not all required fields provided');

        $item = new \tool_blocksmanager\setup_item('region||1');
    }

    public function test_exception_if_incorrect_category_provided() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: incorrect category id provided');

        $item = new \tool_blocksmanager\setup_item('region||1,category||test');
    }

    public function test_weight_and_visible_defaults() {
        $item = new \tool_blocksmanager\setup_item('region||1||test');
        $this->assertSame(0, $item->get_weight());
        $this->assertSame(1, $item->get_visible());
    }

    public function test_categories_are_empty_if_not_found() {
        $item = new \tool_blocksmanager\setup_item('region||777||test');
        $this->assertSame([], $item->get_categories());
    }

    public function test_correct_data() {
        $category1 = $this->getDataGenerator()->create_category();
        $category11 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category111 = $this->getDataGenerator()->create_category(['parent' => $category11->id]);

        $item = new \tool_blocksmanager\setup_item('region||' .$category1->id . '||test_name||-13||0');
        $this->assertSame('region', $item->get_region());
        $this->assertSame('test_name', $item->get_blockname());
        $this->assertSame([$category1->id, $category11->id, $category111->id], $item->get_categories());
        $this->assertSame(-13, $item->get_weight());
        $this->assertSame(0, $item->get_visible());
    }

}
