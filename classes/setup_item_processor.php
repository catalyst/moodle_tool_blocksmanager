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

require_once($CFG->dirroot.'/course/lib.php');

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
     * Process a single set up item.
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

                    // This is a workaround https://tracker.moodle.org/browse/MDL-77397.
                    // We need to reset to stdClass to make core happy.
                    $record = new \stdClass();
                    foreach ($course as $key => $value) {
                        $record->$key = $value;
                        $record->format = $course->format;
                    }

                    $courses[] = $record;
                }
            }
        }

        foreach ($courses as $course) {

            $pagetypesegments = explode('-', $item->get_pagetypepattern());

            // Check if block is to be added to a course module.
            if ($pagetypesegments[0] == 'mod') {
                $this->process_module($item, $pagetypesegments, $course);
            } else {
                $this->process_course($item, $course);
            }
        }
    }

    /**
     * Process blocks to be added to modules.
     *
     * @param \tool_blocksmanager\setup_item $item Item with the block info.
     * @param array $pagetypesegments Array of page type segments (i.e. [mod, assign, view])
     * @param  \stdClass $course Course object.
     */
    protected function process_module(setup_item $item, array $pagetypesegments, \stdClass $course): void {

        // Get list of modules.
        $moduletypes = get_module_types_names();
        $currentmoduletype = $pagetypesegments[1];

        // Check if pagetypepattern contains a valid module.
        if (array_key_exists($currentmoduletype, $moduletypes)) {

            $moduleinstances = get_coursemodules_in_course($currentmoduletype, $course->id);

            if (empty($moduleinstances)) {
                $this->logger->log_message('Skipped adding new instance of ' . $item->get_blockname() . '. '
                  . ' The course with id ' . $course->id . ' does not contain an instance of the '
                  . $currentmoduletype . ' module.');
                return;
            }

            foreach ($moduleinstances as $moduleid => $module) {

                $context = \context_module::instance($moduleid);

                // Create page.
                $page = new \moodle_page();
                $page->set_context($context);
                $page->set_cm($module, $course);
                $page->set_pagetype($item->get_pagetypepattern());

                if (!$page->blocks instanceof block_manager) {
                    // TODO: disable whole plugin if block manager is not overridden.
                    throw new \coding_exception(
                      'Terminate processing. Block manager class is not configured in config.php');
                }

                try {
                    $blockexist = $this->is_block_exist($page, $item);

                    if (!$blockexist) {
                        $blockadded = $this->add_block($page, $item);

                        if (!$blockadded) {
                            $this->logger->log_message('Skipped adding new instance of ' . $item->get_blockname()
                                . ' The block is not addable for module ' . $moduleid . ' in course ' . $course->id);
                            continue;
                        }

                        $this->logger->log_message('Added a new instance of ' . $item->get_blockname() . ' to '
                                    . $currentmoduletype . ' ' . $moduleid . ' in course ' . $course->id);
                    } else {
                        $this->change_block($page, $item, $currentmoduletype, $moduleid);
                    }
                } catch (\Exception $exception) {
                    $this->logger->log_message('Error processing block '. $item->get_blockname() . ' for module '
                          . $currentmoduletype . ' ' . $moduleid . ' Error: '. $exception->getMessage());
                    continue;
                }
            }
        } else {
            $this->logger->log_message('Skipped adding new instance of ' . $item->get_blockname() . '. '
              . ' The given page type "' . $item->get_pagetypepattern()
              . '" does not contain a valid course module (' . $currentmoduletype . ').');
        }
    }


    /**
     * Process blocks to be added to courses.
     *
     * @param \tool_blocksmanager\setup_item $item Item with the block info.
     * @param  \stdClass $course Course object.
     */
    protected function process_course(setup_item $item, \stdClass $course): void {
        $page = new \moodle_page();
        $page->set_course($course);
        $page->set_pagelayout('course');
        $page->set_pagetype('course-view-' . $course->format);

        if (!$page->blocks instanceof block_manager) {
            // TODO: disable whole plugin if block manager is not overridden.
            throw new \coding_exception('Terminate processing. Block manager class is not configured in config.php');
        }
        try {

            $blockexist = $this->is_block_exist($page, $item);

            if (!$blockexist) {
                $blockadded = $this->add_block($page, $item);

                if (!$blockadded) {
                    $this->logger->log_message('Skipped adding new instance of ' . $item->get_blockname()
                        . ' The block is not addable for the course page of course ' . $course->id);
                    return;
                }

                $this->logger->log_message('Added a new instance of ' . $item->get_blockname()
                  . ' to course ' . $course->id);
            } else {
                $this->change_block($page, $item, 'course', $course->id);
            }
        } catch (\Exception $exception) {
            $this->logger->log_message('Error processing block '. $item->get_blockname() . ' for ' . $course->id
                . ' Error: '. $exception->getMessage());
            return;
        }
    }

    /**
     * Check if block exists on a page.
     *
     * @param \moodle_page $page Page instance.
     * @param \tool_blocksmanager\setup_item $item Item with the block info.
     * @return bool True if exists on given page. False otherwise.
     */
    protected function is_block_exist(\moodle_page $page, setup_item $item): bool {
        // Create block instances.
        $page->blocks->add_region($item->get_region(), false);
        $page->blocks->load_blocks(true);
        $page->blocks->create_all_block_instances();

        // Check if block exists.
        $blockexist = $page->blocks->is_block_present($item->get_blockname());

        return $blockexist;
    }

    /**
     * Add a new block to the page.
     *
     * @param \moodle_page $page Page instance.
     * @param \tool_blocksmanager\setup_item $item Item with the block info.
     * @return bool True if block added. False if block not added.
     */
    protected function add_block(\moodle_page $page, setup_item $item): bool {

        $blockaddable = key_exists($item->get_blockname(), $page->blocks->get_addable_blocks());
        if (!$blockaddable) {
            return false;
        }

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

        return true;
    }

    /**
     * Change the block by repositioning, changing its configuration, or adding another instance.
     *
     * @param \moodle_page $page Page instance.
     * @param \tool_blocksmanager\setup_item $item Item with the block info.
     * @param string $pagetype Contains the type of page. i.e. course, assign, quiz.
     * @param int $typeid Contains the id of the page.
     */
    protected function change_block(\moodle_page $page, setup_item $item, string $pagetype, int $typeid): void {

        if (!$page->blocks instanceof block_manager) {
            // TODO: disable whole plugin if block manager is not overridden.
            throw new \coding_exception('Terminate processing. Block manager class is not configured in config.php');
        }

        // We can only either reposition, add a new instance of the existing block or update existing block.
        // We can't have the combination of any of these actions together.
        // This is controlled by a set up form UI where you select only one of the actions.

        if ($item->get_reposition()) {
            $existingblocks = $page->blocks->get_blocks_by_name($item->get_blockname());

            foreach ($existingblocks as $existingblock) {
                $page->blocks->reposition_block(
                    $existingblock->id,
                    $item->get_second_region(),
                    $item->get_second_weight()
                );

                $this->logger->log_message('Changed position of ' . $item->get_blockname()
                    . ' in ' . $pagetype . ' ' . $typeid);
            }
        } else if ($item->get_add()) {
            if (key_exists($item->get_blockname(), $page->blocks->get_addable_blocks())) {
                $this->add_block($page, $item);

                $this->logger->log_message('Added another instance of ' . $item->get_blockname()
                    . ' to ' . $pagetype . ' ' . $typeid);

            } else {
                $this->logger->log_message('Skipped adding another instance of ' . $item->get_blockname()
                    . ' The block is not addable for ' . $pagetype . ' ' . $typeid);
            }
        } else if ($item->get_update()) {
            $existingblocks = $page->blocks->get_blocks_by_name($item->get_blockname());

            foreach ($existingblocks as $existingblock) {
                $this->update_block($existingblock, $page, $item);
                $this->logger->log_message('Updated instance of ' . $item->get_blockname()
                    . ' in ' . $pagetype . ' ' . $typeid);
            }
        } else {
            $this->logger->log_message('Skipped adding another instance of ' . $item->get_blockname()
                . ' The block already exists in ' . $pagetype . ' ' . $typeid);
        }
    }

    /**
     * Update existing block on provided page.
     *
     * @param \stdClass $block Block record to update.
     * @param \moodle_page $page Page instance.
     * @param \tool_blocksmanager\setup_item $item Item with the block info.
     */
    protected function update_block(\stdClass $block, \moodle_page $page, setup_item $item) {
        global $DB;

        // Update Page type pattern and Show in subcontexts via SQL as there is no API.
        $block->showinsubcontexts = $item->get_showinsubcontexts();
        $block->pagetypepattern = $item->get_pagetypepattern();
        $DB->update_record('block_instances', $block);

        // Update config if required.
        if (!empty($item->get_config_data())) {
            $page->blocks->update_block_config_data($block, $item->get_config_data());
        }

        // Set visibility.
        blocks_set_visibility($block, $page, $item->get_visible());
    }

}
