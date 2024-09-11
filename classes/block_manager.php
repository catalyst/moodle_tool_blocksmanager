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

// @codingStandardsIgnoreStart

namespace tool_blocksmanager;

use core\output\notification;
use moodle_exception;
use stdClass;
use context;
use context_course;
use context_system;
use block_base;
use moodle_url;

/**
 * Custom block manager.
 *
 * @package    tool_blocksmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_manager extends \block_manager {

    /**
     * A list of locked categories.
     * @var array
     */
    protected $lockedcategories;

    /**
     * Updates block configuration in the database
     *
     * @param block_base $block
     * @param stdClass $data data from the block edit form
     * @return void
     */
    public function save_block_data(block_base $block, stdClass $data): void {
        global $DB;

        $bi = new stdClass;
        $bi->id = $block->instance->id;

        // This may get overwritten by the special case handling below.
        $bi->pagetypepattern = $data->bui_pagetypepattern;
        $bi->showinsubcontexts = (bool) $data->bui_contexts;
        if (empty($data->bui_subpagepattern) || $data->bui_subpagepattern == '%@NULL@%') {
            $bi->subpagepattern = null;
        } else {
            $bi->subpagepattern = $data->bui_subpagepattern;
        }

        $systemcontext = context_system::instance();
        $frontpagecontext = context_course::instance(SITEID);
        $parentcontext = context::instance_by_id($data->bui_parentcontextid);

        // Updating stickiness and contexts.  See MDL-21375 for details.
        if (has_capability('moodle/site:manageblocks', $parentcontext)) { // Check permissions in destination.

            // Explicitly set the default context.
            $bi->parentcontextid = $parentcontext->id;

            if ($data->bui_editingatfrontpage) {   // The block is being edited on the front page.

                // The interface here is a special case because the pagetype pattern is
                // totally derived from the context menu.  Here are the excpetions.   MDL-30340 .

                switch ($data->bui_contexts) {
                    case BUI_CONTEXTS_ENTIRE_SITE:
                        // The user wants to show the block across the entire site.
                        $bi->parentcontextid = $systemcontext->id;
                        $bi->showinsubcontexts = true;
                        $bi->pagetypepattern = '*';
                        break;
                    case BUI_CONTEXTS_FRONTPAGE_SUBS:
                        // The user wants the block shown on the front page and all subcontexts.
                        $bi->parentcontextid = $frontpagecontext->id;
                        $bi->showinsubcontexts = true;
                        $bi->pagetypepattern = '*';
                        break;
                    case BUI_CONTEXTS_FRONTPAGE_ONLY:
                        // The user want to show the front page on the frontpage only.
                        $bi->parentcontextid = $frontpagecontext->id;
                        $bi->showinsubcontexts = false;
                        $bi->pagetypepattern = 'site-index';
                        // This is the only relevant page type anyway but we'll set it explicitly just
                        // in case the front page grows site-index-* subpages of its own later.
                        break;
                }
            }
        }

        $bits = explode('-', $bi->pagetypepattern);
        // Hacks for some contexts.
        if (($parentcontext->contextlevel == CONTEXT_COURSE) && ($parentcontext->instanceid != SITEID)) {
            // For course context
            // is page type pattern is mod-*, change showinsubcontext to 1.
            if ($bits[0] == 'mod' || $bi->pagetypepattern == '*') {
                $bi->showinsubcontexts = 1;
            } else {
                $bi->showinsubcontexts = 0;
            }
        } else if ($parentcontext->contextlevel == CONTEXT_USER) {
            // For user context subpagepattern should be null.
            if ($bits[0] == 'user' || $bits[0] == 'my') {
                // We don't need subpagepattern in usercontext.
                $bi->subpagepattern = null;
            }
        }

        // Blocks Manager custom code.
        $warning = false;

        // Changing default region.
        if ($block->instance->defaultregion != $data->bui_defaultregion) {
            if ($this->get_locking_manager()->can_move_in($block->instance->blockname, $data->bui_defaultregion) &&
                $this->get_locking_manager()->can_move_out($block->instance->blockname, $block->instance->defaultregion)
            ) {
                $bi->defaultregion = $data->bui_defaultregion;
            } else {
                $warning = true;
            }
        }

        // Changing default weight.
        if ($block->instance->defaultweight != $data->bui_defaultweight) {
            if ($this->get_locking_manager()->can_move($block->instance->blockname, $block->instance->defaultregion)) {
                $bi->defaultweight = $data->bui_defaultweight;
            } else {
                $warning = true;
            }
        }
        // Blocks Manager custom code.

        $bi->timemodified = time();
        $DB->update_record('block_instances', $bi);

        if (!empty($block->config)) {
            $config = clone ($block->config);
        } else {
            $config = new stdClass;
        }
        foreach ($data as $configfield => $value) {
            if (strpos($configfield, 'config_') !== 0) {
                continue;
            }
            $field = substr($configfield, 7);
            $config->$field = $value;
        }
        $block->instance_config_save($config);



        $bp = new stdClass;
        $bp->visible = $data->bui_visible;
        $bp->region = $data->bui_region;
        $bp->weight = $data->bui_weight;

        // Blocks Manager custom code.
        // Change visibility.
        if ($block->instance->visible != $data->bui_visible) {
            if ($this->get_locking_manager()->can_hide(
                $block->instance->blockname,
                $block->instance->region)
                // $this->page->category)
            ) {
                $bp->visible = $data->bui_visible;
            } else {
                $bp->visible = $block->instance->visible;
                $warning = true;
            }
        }

        // Move regions.
        if ($block->instance->region != $data->bui_region) {
            if ($this->get_locking_manager()->can_move_in($block->instance->blockname, $data->bui_region) &&
                $this->get_locking_manager()->can_move_out($block->instance->blockname, $block->instance->region)
            ) {
                $bp->region = $data->bui_region;
            } else {
                $warning = true;
                $bp->region = $block->instance->region;
            }
        } else {
            $bp->region = $block->instance->region;
        }

        // Move inside region.
        if ($block->instance->weight != $data->bui_weight) {
            if ($this->get_locking_manager()->can_move($block->instance->blockname, $data->bui_region)) {
                $bp->weight = $data->bui_weight;
            } else {
                $warning = true;
                $bp->weight = $block->instance->weight;
            }
        } else {
            $bp->weight = $block->instance->weight;
        }
        // Blocks Manager custom code.

        $needbprecord = !$data->bui_visible || $data->bui_region != $data->bui_defaultregion ||
            $data->bui_weight != $data->bui_defaultweight;

        if ($block->instance->blockpositionid && !$needbprecord) {
            $DB->delete_records('block_positions', array('id' => $block->instance->blockpositionid));

        } else if ($block->instance->blockpositionid && $needbprecord) {
            $bp->id = $block->instance->blockpositionid;
            $DB->update_record('block_positions', $bp);

        } else if ($needbprecord) {
            $bp->blockinstanceid = $block->instance->id;
            $bp->contextid = $this->page->context->id;
            $bp->pagetype = $this->page->pagetype;
            if ($this->page->subpage) {
                $bp->subpage = $this->page->subpage;
            } else {
                $bp->subpage = '';
            }
            $DB->insert_record('block_positions', $bp);
        }

        // Blocks Manager custom code.
        if ($warning) {
            redirect($this->page->url,
                get_string('error:lockedregion', 'tool_blocksmanager'),
                null,
                notification::NOTIFY_ERROR
            );
        }
        // Blocks Manager custom code.
    }

    /**
     * When passed a block name create a new instance of the block in the specified region.
     *
     * @param string $blockname Name of the block to add.
     * @param null|string $blockregion If defined add the new block to the specified region.
     * @return ?block_base
     */
    public function add_block_at_end_of_default_region($blockname, $blockregion = null) {
        // Check if the user has permission to add this block to the region.
        if (!$this->get_locking_manager()->can_move_in($blockname, $this->get_default_region())) {
            \core\notification::add(
                get_string('error:lockedregion', 'tool_blocksmanager'),
                notification::NOTIFY_ERROR
            );
            return null;
        }

        return parent::add_block_at_end_of_default_region($blockname, $blockregion);
    }

    /**
     * Override standard block control display.
     *
     * @param object $block Block instance.
     *
     * @return \an|array
     */
    public function edit_controls($block) {
        global $CFG;

        $controls = array();
        $actionurl = $this->page->url->out(false, array('sesskey' => sesskey()));
        $blocktitle = !empty($block->title) ? $block->title : $block->arialabel;
        $blockregion = !empty($block->instance->region) ? $block->instance->region : $block->instance->defaultregion;

        if ($this->page->user_can_edit_blocks() &&
            $this->get_locking_manager()->can_move($block->instance->blockname, $blockregion)
        ) {
            // Move icon.
            $str = new \lang_string('moveblock', 'block', $blocktitle);
            $controls[] = new \action_menu_link_primary(
                new \moodle_url($actionurl, array('bui_moveid' => $block->instance->id)),
                new \pix_icon('t/move', $str, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str,
                array('class' => 'editing_move')
            );

        }

        if (($this->page->user_can_edit_blocks() || $block->user_can_edit()) &&
            $this->get_locking_manager()->can_configure($block->instance->blockname, $blockregion)
        ) {
            // Edit config icon - always show - needed for positioning UI.
            $str = new \lang_string('configureblock', 'block', $blocktitle);
            $controls[] = new \action_menu_link_secondary(
                new \moodle_url($actionurl, array('bui_editid' => $block->instance->id)),
                new \pix_icon('t/edit', $str, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str,
                array('class' => 'editing_edit')
            );
        }

        if ($this->page->user_can_edit_blocks() && $block->instance_can_be_hidden() &&
            $this->get_locking_manager()->can_hide($block->instance->blockname, $blockregion)
        ) {
            // Show/hide icon.
            if ($block->instance->visible) {
                $str = new \lang_string('hideblock', 'block', $blocktitle);
                $url = new \moodle_url($actionurl, array('bui_hideid' => $block->instance->id));
                $icon = new \pix_icon('t/hide', $str, 'moodle', array('class' => 'iconsmall', 'title' => ''));
                $attributes = array('class' => 'editing_hide');
            } else {
                $str = new \lang_string('showblock', 'block', $blocktitle);
                $url = new \moodle_url($actionurl, array('bui_showid' => $block->instance->id));
                $icon = new \pix_icon('t/show', $str, 'moodle', array('class' => 'iconsmall', 'title' => ''));
                $attributes = array('class' => 'editing_show');
            }
            $controls[] = new \action_menu_link_secondary($url, $icon, $str, $attributes);
        }

        // Assign roles.
        if (get_assignable_roles($block->context, ROLENAME_SHORT)) {
            $rolesurl = new \moodle_url('/admin/roles/assign.php', array('contextid' => $block->context->id,
                'returnurl' => $this->page->url->out_as_local_url()));
            $str = new \lang_string('assignrolesinblock', 'block', $blocktitle);
            $controls[] = new \action_menu_link_secondary(
                $rolesurl,
                new \pix_icon('i/assignroles', $str, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str, array('class' => 'editing_assignroles')
            );
        }

        // Permissions.
        if (has_capability('moodle/role:review', $block->context) or get_overridable_roles($block->context)) {
            $rolesurl = new \moodle_url('/admin/roles/permissions.php', array('contextid' => $block->context->id,
                'returnurl' => $this->page->url->out_as_local_url()));
            $str = get_string('permissions', 'role');
            $controls[] = new \action_menu_link_secondary(
                $rolesurl,
                new \pix_icon('i/permissions', $str, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str, array('class' => 'editing_permissions')
            );
        }

        // Change permissions.
        if (has_any_capability(array('moodle/role:safeoverride', 'moodle/role:override', 'moodle/role:assign'), $block->context)) {
            $rolesurl = new \moodle_url('/admin/roles/check.php', array('contextid' => $block->context->id,
                'returnurl' => $this->page->url->out_as_local_url()));
            $str = get_string('checkpermissions', 'role');
            $controls[] = new \action_menu_link_secondary(
                $rolesurl,
                new \pix_icon('i/checkpermissions', $str, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str, array('class' => 'editing_checkroles')
            );
        }

        if ($this->user_can_delete_block($block) &&
            $this->get_locking_manager()->can_remove($block->instance->blockname, $blockregion)
        ) {
            // Delete icon.
            $str = new \lang_string('deleteblock', 'block', $blocktitle);
            $controls[] = new \action_menu_link_secondary(
                new \moodle_url($actionurl, array('bui_deleteid' => $block->instance->id)),
                new \pix_icon('t/delete', $str, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str,
                array('class' => 'editing_delete')
            );
        }

        if (!empty($CFG->contextlocking) && has_capability('moodle/site:managecontextlocks', $block->context)) {
            $parentcontext = $block->context->get_parent_context();
            if (empty($parentcontext) || empty($parentcontext->locked)) {
                if ($block->context->locked) {
                    $lockicon = 'i/unlock';
                    $lockstring = get_string('managecontextunlock', 'admin');
                } else {
                    $lockicon = 'i/lock';
                    $lockstring = get_string('managecontextlock', 'admin');
                }
                $controls[] = new \action_menu_link_secondary(
                    new \moodle_url(
                        '/admin/lock.php',
                        [
                            'id' => $block->context->id,
                        ]
                    ),
                    new \pix_icon($lockicon, $lockstring, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                    $lockstring,
                    ['class' => 'editing_lock']
                );
            }
        }

        return $controls;
    }

    /**
     * Handle showing or hiding a block.
     * @return boolean true if anything was done. False if not.
     */
    public function process_url_show_hide() {
        if ($blockid = optional_param('bui_hideid', null, PARAM_INT)) {
            $newvisibility = 0;
        } else if ($blockid = optional_param('bui_showid', null, PARAM_INT)) {
            $newvisibility = 1;
        } else {
            return false;
        }

        require_sesskey();

        $block = $this->page->blocks->find_instance($blockid);

        if (!$this->get_locking_manager()->can_hide($block->instance->blockname, $block->instance->region)) {
            return false;
        }

        return parent::process_url_show_hide();
    }

    /**
     * Handle deleting a block.
     * @return boolean true if anything was done. False if not.
     */
    public function process_url_delete() {
        $blockid = optional_param('bui_deleteid', null, PARAM_INT);

        if (!$blockid) {
            return false;
        }

        require_sesskey();
        $block = $this->page->blocks->find_instance($blockid);
        if ($this->user_can_delete_block($block) &&
            !$this->get_locking_manager()->can_remove($block->instance->blockname, $block->instance->region)
        ) {
            return false;
        }

        return parent::process_url_delete();
    }

    /**
     * Override standard move action.
     *
     * - if trying to move to locked region - throw and exception.
     *
     * @return bool|void
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function process_url_move() {
        $blockid = optional_param('bui_moveid', null, PARAM_INT);
        if (!$blockid) {
            return false;
        }

        require_sesskey();

        $block = $this->find_instance($blockid);

        if (!$this->page->user_can_edit_blocks()) {
            throw new \moodle_exception('nopermissions', '', $this->page->url->out(), get_string('editblock'));
        }

        $newregion = optional_param('bui_newregion', '', PARAM_ALPHANUMEXT);
        $newweight = optional_param('bui_newweight', null, PARAM_FLOAT);

        // Moving inside region -> check can move.
        if ($newregion == $block->instance->region &&
            !$this->get_locking_manager()->can_move($block->instance->blockname, $block->instance->region)
        ) {
            throw new \moodle_exception('error:lockedregion', 'tool_blocksmanager');
        }

        // Moving outside region -> check move in a new region and move out from the old region.
        if ($newregion != $block->instance->region) {
            if (!$this->get_locking_manager()->can_move_in($block->instance->blockname, $newregion) ||
                !$this->get_locking_manager()->can_move_out($block->instance->blockname, $block->instance->region)
            ) {
                throw new \moodle_exception('error:lockedregion', 'tool_blocksmanager');
            }
        }

        parent::process_url_move();
    }

    /**
     * Get a list of existing block instances by block name.
     *
     * @param string $blockname A name of the block.
     *
     * @return array
     */
    public function get_blocks_by_name(string $blockname) {

        if (empty($this->blockinstances)) {
            return [];
        }

        $blocks = [];

        foreach ($this->blockinstances as $region) {
            foreach ($region as $instance) {
                if (empty($instance->instance->blockname)) {
                    continue;
                }
                if ($instance->instance->blockname == $blockname) {
                    $blocks[] = $instance->instance;
                }
            }
        }
        return $blocks;
    }

    /**
     * Update block config data.
     *
     * @param object $block Block instance.
     * @param string $configdata Config data to set.
     */
    public function update_block_config_data($block, string $configdata) {
        global $DB;

        $block->configdata = $configdata;
        $DB->update_record('block_instances', $block);
    }

    /**
     * Return locking manager.
     *
     * @return \tool_blocksmanager\locking_manager
     */
    public function get_locking_manager() {
        return new locking_manager($this->page);
    }

}
// @codingStandardsIgnoreEnd

