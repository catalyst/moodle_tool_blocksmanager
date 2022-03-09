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
 * The class that control locking functionality for the block in the region.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locking_manager {
    /**
     * A list of valid actions.
     */
    const VALID_ACTIONS = ['config', 'remove', 'hide', 'movein', 'move'];

    /**
     * Moodle page.
     * @var \moodle_page
     */
    protected $page;

    /**
     * Course category set on this page.
     * @var
     */
    protected $category;

    /**
     * All locked blocks.
     * @var \core\persistent[]
     */
    protected $lockedblocks;

    /**
     * All locked regions.
     * @var \core\persistent[]
     */
    protected $lockedregions;

    /**
     * Constructor.
     *
     * @param \moodle_page $page
     */
    public function __construct(\moodle_page $page) {
        $this->page = $page;
        $this->category = $this->page->category;
        $this->lockedblocks = block::get_records();
        $this->lockedregions = region::get_records();
    }

    /**
     * Check can configure block in the provided region.
     *
     * @param string $blockname Name of the block.
     * @param string $region Name of the region.
     *
     * @return bool
     */
    public function can_configure(string $blockname, string $region) {
        return $this->can_action('config', $blockname, $region);
    }

    /**
     * Check can remove block in the provided region.
     *
     * @param string $blockname Name of the block.
     * @param string $region Name of the region.
     *
     * @return bool
     */
    public function can_remove(string $blockname, string $region) {
        return $this->can_action('remove', $blockname, $region);
    }

    /**
     * Check can hide block in the provided region.
     *
     * @param string $blockname Name of the block.
     * @param string $region Name of the region.
     *
     * @return bool
     */
    public function can_hide(string $blockname, string $region) {
        return $this->can_action('hide', $blockname, $region);
    }

    /**
     * Check can move block in the provided region.
     *
     * @param string $blockname Name of the block.
     * @param string $region Name of the region.
     *
     * @return bool
     */
    public function can_move(string $blockname, string $region) {
        return $this->can_action('move', $blockname, $region);
    }

    /**
     * Check can move block into the provided region.
     *
     * @param string $blockname Name of the block.
     * @param string $region Name of the region.
     *
     * @return bool
     */
    public function can_move_in(string $blockname, string $region) {
        return $this->can_action('movein', $blockname, $region);
    }

    /**
     * Check can move block out of the provided region.
     *
     * @param string $blockname Name of the block.
     * @param string $region Name of the region.
     *
     * @return bool
     */
    public function can_move_out(string $blockname, string $region) {
        return $this->can_action('remove', $blockname, $region);
    }

    /**
     * Check if provided action is valid.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function is_valid_action(string $action) {
        return in_array($action, self::VALID_ACTIONS);
    }

    /**
     * Check if provided action can be done on the block in the region.
     *
     * @param string $action Action needs to be done. See self::VALID_ACTIONS.
     * @param string $blockname Name of the block.
     * @param string $region Name of the region.
     *
     * @return bool
     */
    protected function can_action($action, $blockname, $region) {
        if (!$this->is_valid_action($action)) {
            throw new \coding_exception('Invalid action ' . $action);
        }

        if ($this->can_do_any_action()) {
            return true;
        }

        // We check actions only on course related pages. No limit on others.
        if (!empty($this->category)) {
            if (empty($this->category->id)) {
                throw new \coding_exception('Course category must have id');
            }

            // First, check blocks rules as they override region rules.
            // 0. Check if action is block action.
            // 1. Match by block name
            // 2. Check that region is locked for that block.
            // 3. Finally check that course category is locked for that block.
            if (in_array($action, block::get_action_fields())) {
                foreach ($this->lockedblocks as $lockedblock) {
                    if ($lockedblock->get('block') == $blockname) {
                        if ($lockedblock->get('region') === block::ALL_REGIONS || $lockedblock->get('region') == $region) {
                            if (in_array($this->category->id, $this->get_locked_categories($lockedblock->get('categories')))) {
                                return (bool) $lockedblock->get($action);
                            }
                        }
                    }
                }
            }

            // Second, check region rules.
            // 0. Check that action is region action.
            // 1. Match config by region name.
            // 2. Check if the region is locked in the provided category.
            if (in_array($action, region::get_action_fields())) {
                foreach ($this->lockedregions as $lockedregion) {
                    if ($lockedregion->get('region') == $region) {
                        if (in_array($this->category->id, $this->get_locked_categories($lockedregion->get('categories')))) {
                            return (bool) $lockedregion->get($action);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Return a list of all locked categories including all children for all provided categories.
     * @param string $lockedcats A string of comma separeted category ids.
     *
     * @return array
     */
    protected function get_locked_categories(string $lockedcats) {
        return helper::get_categories_and_children($lockedcats);
    }

    /**
     * Check if can do any action.
     *
     * @return bool
     */
    protected function can_do_any_action() {
        return has_capability('tool/blocksmanager:bypasslocking', \context_system::instance());
    }

}
