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

/**
 * A class to apply set up item.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_item_processor {

    /**
     * Logger instance.
     * @var \tool_blocksmanager\logger_interface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \tool_blocksmanager\logger_interface $logger Logger instance.
     */
    public function __construct(logger_interface $logger) {
        $this->logger = $logger;
    }

    /**
     * Process multiple items.
     *
     * @param \tool_blocksmanager\setup_item[] $items A list of items to process.
     *
     * @throws \coding_exception
     */
    public function process_bulk(array $items) {
        foreach ($items as $item) {
            $this->process($item);
        }
    }

    /**
     * Process a single set up item
     *
     * @param \tool_blocksmanager\setup_item $item Set up item to process.
     */
    public function process(setup_item $item) {
        $courses = [];

        // Build a list of courses we need to go through.
        foreach ($item->get_categories() as $catid) {
            $coursecat = \core_course_category::get($catid, IGNORE_MISSING);
            if (!empty($coursecat) && $coursecat->has_courses()) {
                foreach ($coursecat->get_courses() as $course) {
                    $courses[] = $course;
                }
            }
        }

        foreach ($courses as $course) {
            $page = new \moodle_page();
            $page->set_course($course);
            $page->set_pagelayout('course');
            $page->set_pagetype('course-view-' . $course->format);

            if (!$page->blocks instanceof block_manager) {
                // TODO: disable whole plugin if block manager is not overridden.
                throw new \coding_exception('Terminate processing. Block manager class is not configured in config.php');
            }

            try {
                $page->blocks->add_region($item->get_region(), false);
                $page->blocks->load_blocks(true);
                $page->blocks->create_all_block_instances();

                if (!$page->blocks->is_block_present($item->get_blockname())) {

                    if (!key_exists($item->get_blockname(), $page->blocks->get_addable_blocks())) {
                        $this->logger->log_message('Skipped adding new instance of ' . $item->get_blockname()
                            . ' The block is not addable for the course page of course ' . $course->id);
                        continue;
                    }

                    $this->add_block($page, $item);
                    $this->logger->log_message('Added a new instance of ' . $item->get_blockname() . ' to course ' . $course->id);
                } else {
                    if ($item->get_reposition()) {
                        $existingblocks = $page->blocks->get_blocks_by_name($item->get_blockname());

                        foreach ($existingblocks as $existingblock) {
                            $page->blocks->reposition_block(
                                $existingblock->id,
                                $item->get_second_region(),
                                $item->get_second_weight()
                            );

                            $this->logger->log_message('Changed position of ' . $item->get_blockname()
                                . ' in course ' . $course->id);
                        }
                    } else if ($item->get_add()) {
                        if (key_exists($item->get_blockname(), $page->blocks->get_addable_blocks())) {
                            $this->add_block($page, $item);

                            $this->logger->log_message('Added another instance of ' . $item->get_blockname()
                                . ' to course ' . $course->id);

                        } else {
                            $this->logger->log_message('Skipped adding another instance of ' . $item->get_blockname()
                                . ' The block is not addable for the course page of course ' . $course->id);
                        }
                    } else {
                        $this->logger->log_message('Skipped adding another instance of ' . $item->get_blockname()
                            . ' The block is already exist in the course ' . $course->id);
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->log_message('Error processing block '. $item->get_blockname() . ' for ' . $course->id
                    . ' Error: '. $exception->getMessage());
                continue;
            }
        }
    }

    /**
     * Add a new block to the page.
     *
     * @param \moodle_page $page Page instance.
     * @param \tool_blocksmanager\setup_item $item Item with the block info.
     */
    protected function add_block(\moodle_page $page, setup_item $item) {
        // Add a new instance.
        $block = $page->blocks->add_block(
            $item->get_blockname(),
            $item->get_region(),
            $item->get_weight(),
            $item->get_showinsubcontexts(),
            $item->get_pagetypepattern(),
            null
        );
        // Set visibility and position.
        blocks_set_visibility($block, $page, $item->get_visible());
        // Update config data.
        if (!empty($item->get_config_data())) {
            $page->blocks->update_block_config_data($block, $item->get_config_data());
        }
    }

}
