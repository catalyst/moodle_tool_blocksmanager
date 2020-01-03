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
 * Tests for locking manager class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once('dummy_logger.php');

class tool_blocksmanager_setup_item_processor_testcase extends advanced_testcase {

    /**
     * Logger for testing
     * @var \blocks_manager_dummy_logger
     */
    protected $logger;

    /**
     * Initial set up.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->logger = new blocks_manager_dummy_logger();
    }

    /**
     * Test that processing is terminated if blockmanagerclass is not overridden.
     */
    public function test_thrown_exception_if_blockmanagerclass_is_not_overridden() {
        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);


        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Coding error detected, it must be fixed by a programmer: ' .
            'Terminate processing. Block manager class is not configured in config.php');

        $data = 'side-pre||' . $category1->id .'||search_forums||-10||1||1||Config data||Secondary region||-10';
        $item = new \tool_blocksmanager\setup_item($data);
        $processor = new \tool_blocksmanager\setup_item_processor($this->logger);
        $processor->process($item);
    }

    /**
     * Test basic processing.
     */
    public function test_process() {
        global $CFG, $DB;

        $CFG->blockmanagerclass = '\\tool_blocksmanager\\block_manager';

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $this->assertSame(0, $DB->count_records('block_instances', ['blockname' => 'search_forums']));

        $data = 'side-pre||' . $category1->id .'||search_forums||-10||1||1||Config data||Secondary region||-10';
        $item = new \tool_blocksmanager\setup_item($data);
        $processor = new \tool_blocksmanager\setup_item_processor($this->logger);
        $processor->process($item);

        $blocks = $DB->get_records('block_instances', ['blockname' => 'search_forums']);
        $this->assertCount(2, $blocks);

        foreach ($blocks as $block) {
            $this->assertEquals('course-view-*', $block->pagetypepattern);
            $this->assertEquals('side-pre', $block->defaultregion);
            $this->assertEquals('-10', $block->defaultweight);
            $this->assertEquals('Config data', $block->configdata);

            $position = $DB->get_record('block_positions', ['blockinstanceid' => $block->id]);
            $this->assertEquals(1, $position->visible);
            $this->assertEquals('side-pre', $position->region);
            $this->assertEquals('course-view-topics', $position->pagetype);
        }

        $logs = $this->logger->get_logs();
        $this->assertCount(2, $logs);
        $this->assertSame('Added a new instance of search_forums to course ' . $course2->id, $logs[0]);
        $this->assertSame('Added a new instance of search_forums to course ' . $course1->id, $logs[1]);
    }

}
