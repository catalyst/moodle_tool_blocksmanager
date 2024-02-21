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

namespace tool_blocksmanager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once('dummy_logger.php');

/**
 * Tests for region persistent class.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_item_processor_test extends \advanced_testcase {

    /**
     * Initial set up.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test that processing is terminated if blockmanagerclass is not overridden.
     *
     * @covers \tool_blocksmanager\setup_item
     * @covers \tool_blocksmanager\setup_item_processor
     * @return void
     */
    public function test_thrown_exception_if_blockmanagerclass_is_not_overridden() {
        set_config('blockmanagerclass', '');

        $category1 = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Coding error detected, it must be fixed by a programmer: ' .
            'Terminate processing. Block manager class is not configured in config.php');

        $data = 'side-pre||' . $category1->id .'||search_forums||-10||1||1||Config data||0||Secondary region||-10||0||*';
        $item = new setup_item($data);
        $processor = new setup_item_processor(new dummy_logger());
        $processor->process($item);
    }

    /**
     * Test basic processing.
     *
     * @covers \tool_blocksmanager\setup_item
     * @covers \tool_blocksmanager\setup_item_processor
     * @return void
     */
    public function test_process() {
        global $DB;

        set_config('blockmanagerclass', '\\tool_blocksmanager\\block_manager');

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        $this->assertSame(0, $DB->count_records('block_instances', ['blockname' => 'search_forums']));

        // We have to encode and serialize 'Config data' string to make sure blocks API doesn't explode.
        // See https://github.com/catalyst/moodle_tool_blocksmanager/issues/35 for more context.
        $configdata = base64_encode(serialize('Config data'));

        // Add search_forums block to category 1. So it should be added to course 1 and course 2.
        $data = 'side-pre||' . $category1->id .'||search_forums||-10||1||0||' . $configdata . '||0||0||0||*';
        $item = new setup_item($data);
        $logger = new dummy_logger();
        $processor = new setup_item_processor($logger);
        $processor->process($item);

        $logs = $logger->get_logs();
        $this->assertCount(2, $logs);
        $this->assertSame('Added a new instance of search_forums to course ' . $course2->id, $logs[0]);
        $this->assertSame('Added a new instance of search_forums to course ' . $course1->id, $logs[1]);

        $blocks = $DB->get_records('block_instances', ['blockname' => 'search_forums']);
        $this->assertCount(2, $blocks);

        foreach ($blocks as $block) {
            $this->assertEquals('*', $block->pagetypepattern);
            $this->assertEquals('side-pre', $block->defaultregion);
            $this->assertEquals('-10', $block->defaultweight);
            $this->assertEquals('czoxMToiQ29uZmlnIGRhdGEiOw==', $block->configdata);

            $position = $DB->get_record('block_positions', ['blockinstanceid' => $block->id]);
            $this->assertEquals(1, $position->visible);
            $this->assertEquals('side-pre', $position->region);
            $this->assertEquals('-10', $position->weight);
            $this->assertEquals('course-view-topics', $position->pagetype);
        }

        // Now let's reposition just added blocks.
        $data = 'side-pre||' . $category1->id .'||search_forums||-10||1||1||||0||0||side-pre||10||0||*';
        $item = new setup_item($data);
        $logger = new dummy_logger();
        $processor = new setup_item_processor($logger);
        $processor->process($item);

        $logs = $logger->get_logs();
        $this->assertCount(2, $logs);
        $this->assertSame('Changed position of search_forums in course ' . $course2->id, $logs[0]);
        $this->assertSame('Changed position of search_forums in course ' . $course1->id, $logs[1]);

        foreach ($blocks as $block) {
            $this->assertEquals('*', $block->pagetypepattern);
            $this->assertEquals('side-pre', $block->defaultregion);
            $this->assertEquals('-10', $block->defaultweight);
            $this->assertEquals('czoxMToiQ29uZmlnIGRhdGEiOw==', $block->configdata);

            $position = $DB->get_record('block_positions', ['blockinstanceid' => $block->id]);
            $this->assertEquals(1, $position->visible);
            $this->assertEquals('side-pre', $position->region);
            $this->assertEquals('10', $position->weight);
            $this->assertEquals('course-view-topics', $position->pagetype);
        }

        // Now update existing blocks. We will change:
        // - visibility to 0
        // - page type pattern to 'course-view-*'
        // - show in subcontexts to 1
        // - config data to 'New config data'.
        $configdata = base64_encode(serialize('New config data'));
        $data = 'side-pre||' . $category1->id . '||search_forums||-10||0||0||' . $configdata . '||0||1|||1||course-view-*';

        $item = new setup_item($data);
        $logger = new dummy_logger();
        $processor = new setup_item_processor($logger);
        $processor->process($item);

        $logs = $logger->get_logs();
        $this->assertCount(2, $logs);
        $this->assertSame('Updated instance of search_forums in course ' . $course2->id, $logs[0]);
        $this->assertSame('Updated instance of search_forums in course ' . $course1->id, $logs[1]);

        $blocks = $DB->get_records('block_instances', ['blockname' => 'search_forums']);
        $this->assertCount(2, $blocks);
        foreach ($blocks as $block) {
            $this->assertEquals('course-view-*', $block->pagetypepattern);
            $this->assertEquals('side-pre', $block->defaultregion);
            $this->assertEquals('-10', $block->defaultweight);
            $this->assertEquals('czoxNToiTmV3IGNvbmZpZyBkYXRhIjs=', $block->configdata);

            $position = $DB->get_record('block_positions', ['blockinstanceid' => $block->id]);
            $this->assertEquals(0, $position->visible);
            $this->assertEquals('side-pre', $position->region);
            $this->assertEquals('course-view-topics', $position->pagetype);
        }

        // Let's test adding a block to different course modules.
        $modules = ['assign', 'book', 'forum', 'quiz'];

        foreach ($modules as $module) {
            $logger = new dummy_logger();

            $moduleinstance = $this->getDataGenerator()->create_module($module, ['course' => $course3]);
            $data = 'side-post||' . $category2->id . '||blog_menu||-10||1||0||||0||0||0||mod-'. $module . '-view';
            $item = new setup_item($data);
            $processor = new setup_item_processor($logger);
            $processor->process($item);

            $logs = $logger->get_logs();
            $this->assertSame('Added a new instance of blog_menu to ' . $module . ' ' . $moduleinstance->cmid
              . ' in course ' . $course3->id, $logs[0]);
        }

        $blocks = $DB->get_records('block_instances', ['blockname' => 'blog_menu']);
        $this->assertCount(4, $blocks);

        // Let's test adding a block to different course modules while block already added to main course page.
        $logger = new dummy_logger();
        $data = 'side-post||' . $category2->id . '||blog_menu||-10||1||0||||0||0||0||course-view-*';
        $item = new setup_item($data);
        $logger = new dummy_logger();
        $processor = new setup_item_processor($logger);
        $processor->process($item);
        $logs = $logger->get_logs();
        $this->assertSame('Added a new instance of blog_menu to course ' . $course3->id, $logs[0]);

        foreach ($modules as $module) {
            $logger = new dummy_logger();
            $moduleinstance = $this->getDataGenerator()->create_module($module, ['course' => $course3]);
            $data = 'side-post||' . $category2->id . '||blog_menu||-10||1||0||||0||0||0||mod-'. $module . '-view';
            $item = new setup_item($data);
            $processor = new setup_item_processor($logger);
            $processor->process($item);
            $logs = $logger->get_logs();
            $this->assertSame('Added a new instance of blog_menu to ' . $module . ' ' . $moduleinstance->cmid
              . ' in course ' . $course3->id, $logs[1]);
        }

        // Block should now be in 9 locations - course main page, and each module created (8).
        $blocks = $DB->get_records('block_instances', ['blockname' => 'blog_menu']);
        $this->assertCount(9, $blocks);
    }
}
