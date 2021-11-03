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

    public function setUp() : void {
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

    public function test_exception_if_empty_region_provided() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: empty region is not allowed');

        $item = new \tool_blocksmanager\setup_item('||1,2||test');
    }

    public function test_exception_if_incorrect_category_provided() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: incorrect category id provided');

        $item = new \tool_blocksmanager\setup_item('region||1,category||test');
    }

    public function test_exception_if_empty_categories_provided() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: incorrect category id provided');

        $item = new \tool_blocksmanager\setup_item('region||||test');
    }

    public function test_exception_if_empty_blockname_provided() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: empty block name is not allowed');

        $item = new \tool_blocksmanager\setup_item('region||1,2||');
    }

    public function test_exception_if_empty_pagetypepattern_provided() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: empty page type pattern is not allowed');

        $item = new \tool_blocksmanager\setup_item('region||1,2||block||0||0||0||configdata||0||0||');
    }

    public function test_exception_if_reposition_and_empty_region_provided() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: empty secondary region is not allowed, if repositioning is enabled');

        $item = new \tool_blocksmanager\setup_item('region||1,2||block||1||1||1');
    }

    public function test_exception_if_reposition_and_add_new_block_at_the_same_time() {
        $this->expectException('\tool_blocksmanager\invalid_setup_item_exception');
        $this->expectExceptionMessage('Incorrect data: you should either reposition or add a new block');

        $item = new \tool_blocksmanager\setup_item('Region||4||activity_modules||-10||1||1||configdata||1||Secondary region||-10');
    }

    public function test_defaults() {
        $item = new \tool_blocksmanager\setup_item('region||1||test');
        $this->assertSame(0, $item->get_weight());
        $this->assertSame(true, $item->get_visible());
        $this->assertSame(false, $item->get_reposition());
        $this->assertSame('', $item->get_config_data());
        $this->assertSame('', $item->get_second_region());
        $this->assertSame(0, $item->get_second_weight());
        $this->assertSame(false, $item->get_showinsubcontexts());
        $this->assertSame(\tool_blocksmanager\setup_item::PAGE_TYPE_PATTERN_DEFAULT, $item->get_pagetypepattern());
    }

    public function test_categories_are_empty_if_not_found() {
        $item = new \tool_blocksmanager\setup_item('region||777||test');
        $this->assertSame([], $item->get_categories());
    }

    public function test_correct_data() {
        $category1 = $this->getDataGenerator()->create_category();
        $category11 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category111 = $this->getDataGenerator()->create_category(['parent' => $category11->id]);

        $item = new \tool_blocksmanager\setup_item(
            'region||' .$category1->id . '||test_name||-13||0||1||Config data||0||secondary region||13||1||*'
        );
        $this->assertSame('region', $item->get_region());
        $this->assertSame('test_name', $item->get_blockname());
        $this->assertSame([$category1->id, $category11->id, $category111->id], $item->get_categories());
        $this->assertSame(-13, $item->get_weight());
        $this->assertSame(false, $item->get_visible());
        $this->assertSame(true, $item->get_reposition());
        $this->assertSame('Config data', $item->get_config_data());
        $this->assertSame(false, $item->get_add());
        $this->assertSame('secondary region', $item->get_second_region());
        $this->assertSame(13, $item->get_second_weight());
        $this->assertSame(true, $item->get_showinsubcontexts());
        $this->assertSame('*', $item->get_pagetypepattern());
    }

}
