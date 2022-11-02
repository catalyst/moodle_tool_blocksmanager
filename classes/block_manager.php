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
use moodle_page;
use stdClass;
use context;
use context_system;
use context_course;
use moodle_url;
use core_tag_tag;

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
     * Override standard edit actions:
     *
     *  - if a trying to save block to blocked region - don't save and display error after redirect.
     *
     * @return bool
     * @throws \block_not_on_page_exception
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function process_url_edit() {
        global $CFG, $DB, $PAGE, $OUTPUT;

        $blockid = optional_param('bui_editid', null, PARAM_INT);
        if (!$blockid) {
            return false;
        }

        require_sesskey();
        require_once($CFG->dirroot . '/blocks/edit_form.php');

        $block = $this->find_instance($blockid);

        if (!$block->user_can_edit() && !$this->page->user_can_edit_blocks()) {
            throw new moodle_exception('nopermissions', '', $this->page->url->out(), get_string('editblock'));
        }

        $editpage = new moodle_page();
        $editpage->set_pagelayout('admin');
        $editpage->blocks->show_only_fake_blocks(true);
        $editpage->set_course($this->page->course);
        $editpage->set_context($this->page->context);
        if ($this->page->cm) {
            $editpage->set_cm($this->page->cm);
        }
        $editurlbase = str_replace($CFG->wwwroot . '/', '/', $this->page->url->out_omit_querystring());
        $editurlparams = $this->page->url->params();
        $editurlparams['bui_editid'] = $blockid;
        $editpage->set_url($editurlbase, $editurlparams);
        $editpage->set_block_actions_done();
        // At this point we are either going to redirect, or display the form, so
        // overwrite global $PAGE ready for this. (Formslib refers to it.)
        $PAGE = $editpage;
        $output = $editpage->get_renderer('core');
        $OUTPUT = $output;

        $formfile = $CFG->dirroot . '/blocks/' . $block->name() . '/edit_form.php';
        if (is_readable($formfile)) {
            require_once($formfile);
            $classname = 'block_' . $block->name() . '_edit_form';
            if (!class_exists($classname)) {
                $classname = 'block_edit_form';
            }
        } else {
            $classname = 'block_edit_form';
        }

        $mform = new $classname($editpage->url, $block, $this->page);
        $mform->set_data($block->instance);

        if ($mform->is_cancelled()) {
            redirect($this->page->url);

        } else if ($data = $mform->get_data()) {
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
            if (has_capability('moodle/site:manageblocks', $parentcontext)) { // Check permissions in destination

                // Explicitly set the default context
                $bi->parentcontextid = $parentcontext->id;

                if ($data->bui_editingatfrontpage) {   // The block is being edited on the front page

                    // The interface here is a special case because the pagetype pattern is
                    // totally derived from the context menu.  Here are the excpetions.   MDL-30340

                    switch ($data->bui_contexts) {
                        case BUI_CONTEXTS_ENTIRE_SITE:
                            // The user wants to show the block across the entire site
                            $bi->parentcontextid = $systemcontext->id;
                            $bi->showinsubcontexts = true;
                            $bi->pagetypepattern  = '*';
                            break;
                        case BUI_CONTEXTS_FRONTPAGE_SUBS:
                            // The user wants the block shown on the front page and all subcontexts
                            $bi->parentcontextid = $frontpagecontext->id;
                            $bi->showinsubcontexts = true;
                            $bi->pagetypepattern  = '*';
                            break;
                        case BUI_CONTEXTS_FRONTPAGE_ONLY:
                            // The user want to show the front page on the frontpage only
                            $bi->parentcontextid = $frontpagecontext->id;
                            $bi->showinsubcontexts = false;
                            $bi->pagetypepattern  = 'site-index';
                            // This is the only relevant page type anyway but we'll set it explicitly just
                            // in case the front page grows site-index-* subpages of its own later
                            break;
                    }
                }
            }

            $bits = explode('-', $bi->pagetypepattern);
            // hacks for some contexts
            if (($parentcontext->contextlevel == CONTEXT_COURSE) && ($parentcontext->instanceid != SITEID)) {
                // For course context
                // is page type pattern is mod-*, change showinsubcontext to 1
                if ($bits[0] == 'mod' || $bi->pagetypepattern == '*') {
                    $bi->showinsubcontexts = 1;
                } else {
                    $bi->showinsubcontexts = 0;
                }
            } else if ($parentcontext->contextlevel == CONTEXT_USER) {
                // for user context
                // subpagepattern should be null
                if ($bits[0] == 'user' or $bits[0] == 'my') {
                    // we don't need subpagepattern in usercontext
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
                    $bi->defaultregion = $data->bui_defaultregion;
                } else {
                    $warning = true;
                }
            }
            // Blocks Manager custom code.

            $bi->timemodified = time();
            $DB->update_record('block_instances', $bi);

            if (!empty($block->config)) {
                $config = clone($block->config);
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
            $bp->visible = $block->instance->visible;

            // Blocks Manager custom code.
            // Change visibility.
            if ($block->instance->visible != $data->bui_visible) {
                if ($this->get_locking_manager()->can_hide(
                    $block->instance->blockname,
                    $block->instance->region,
                    $this->page->category)
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
            } else {
                redirect($this->page->url);
            }
            // Blocks Manager custom code.

        } else {
            $strheading = get_string('blockconfiga', 'moodle', $block->get_title());
            $editpage->set_title($strheading);
            $editpage->set_heading($strheading);
            $bits = explode('-', $this->page->pagetype);
            if ($bits[0] == 'tag' && !empty($this->page->subpage)) {
                // better navbar for tag pages
                $editpage->navbar->add(get_string('tags'), new moodle_url('/tag/'));
                $tag = core_tag_tag::get($this->page->subpage);
                // tag search page doesn't have subpageid
                if ($tag) {
                    $editpage->navbar->add($tag->get_display_name(), $tag->get_view_url());
                }
            }
            $editpage->navbar->add($block->get_title());
            $editpage->navbar->add(get_string('configuration'));
            echo $output->header();
            echo $output->heading($strheading, 2);
            $mform->display();
            echo $output->footer();
            exit;
        }
    }

    /**
     * Override standard functionality.
     *
     * - If default region is locked - don't add any blocks.
     *
     * @param $blockname
     */
    public function add_block_at_end_of_default_region($blockname) {
        $defaulregion = $this->get_default_region();

        if (!$this->get_locking_manager()->can_move_in($blockname, $defaulregion)) {
            redirect($this->page->url,
                get_string('error:lockedefaultregion', 'tool_blocksmanager'),
                null,
                notification::NOTIFY_ERROR
            );
        }

        parent::add_block_at_end_of_default_region($blockname);
    }

    /**
     * Override standard block control display.
     *
     * @param $block
     *
     * @return \an|array
     */
    public function edit_controls($block) {
        global $CFG;

        $controls = array();
        $actionurl = $this->page->url->out(false, array('sesskey' => sesskey()));
        $blocktitle = $block->title;
        if (empty($blocktitle)) {
            $blocktitle = $block->arialabel;
        }
        $blockregion = $block->instance->region;
        if (empty($blockregion)) {
            $blockregion = $block->instance->defaultregion;
        }

        if ($this->page->user_can_edit_blocks() &&
            $this->get_locking_manager()->can_move($block->instance->blockname, $blockregion)
        ) {
            // Move icon.
            $str = new \lang_string('moveblock', 'block', $blocktitle);
            $controls[] = new \action_menu_link_primary(
                new moodle_url($actionurl, array('bui_moveid' => $block->instance->id)),
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
                new moodle_url($actionurl, array('bui_editid' => $block->instance->id)),
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
                $url = new moodle_url($actionurl, array('bui_hideid' => $block->instance->id));
                $icon = new \pix_icon('t/hide', $str, 'moodle', array('class' => 'iconsmall', 'title' => ''));
                $attributes = array('class' => 'editing_hide');
            } else {
                $str = new \lang_string('showblock', 'block', $blocktitle);
                $url = new moodle_url($actionurl, array('bui_showid' => $block->instance->id));
                $icon = new \pix_icon('t/show', $str, 'moodle', array('class' => 'iconsmall', 'title' => ''));
                $attributes = array('class' => 'editing_show');
            }
            $controls[] = new \action_menu_link_secondary($url, $icon, $str, $attributes);
        }

        // Assign roles.
        if (get_assignable_roles($block->context, ROLENAME_SHORT)) {
            $rolesurl = new moodle_url('/admin/roles/assign.php', array('contextid' => $block->context->id,
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
            $rolesurl = new moodle_url('/admin/roles/permissions.php', array('contextid' => $block->context->id,
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
            $rolesurl = new moodle_url('/admin/roles/check.php', array('contextid' => $block->context->id,
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
                new moodle_url($actionurl, array('bui_deleteid' => $block->instance->id)),
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
                    new moodle_url(
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
            throw new moodle_exception('nopermissions', '', $this->page->url->out(), get_string('editblock'));
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
            if (!$this->get_locking_manager()->can_move_in($block->instance->blockname, $newregion)  ||
                !$this->get_locking_manager()->can_move_out($block->instance->blockname, $block->instance->region)
            ) {
                throw new \moodle_exception('error:lockedregion', 'tool_blocksmanager');
            }
        }

        parent::process_url_move();
    }

    /**
     * Override core function to be able to return block instance.
     *
     * {@inheritDoc}
     *
     * @return stdClass
     */
    public function add_block($blockname, $region, $weight, $showinsubcontexts, $pagetypepattern = null, $subpagepattern = null) {
        global $DB;
        // Allow invisible blocks because this is used when adding default page blocks, which
        // might include invisible ones if the user makes some default blocks invisible.
        $this->check_known_block_type($blockname, true);
        $this->check_region_is_known($region);

        if (empty($pagetypepattern)) {
            $pagetypepattern = $this->page->pagetype;
        }

        $blockinstance = new stdClass;
        $blockinstance->blockname = $blockname;
        $blockinstance->parentcontextid = $this->page->context->id;
        $blockinstance->showinsubcontexts = !empty($showinsubcontexts);
        $blockinstance->pagetypepattern = $pagetypepattern;
        $blockinstance->subpagepattern = $subpagepattern;
        $blockinstance->defaultregion = $region;
        $blockinstance->defaultweight = $weight;
        $blockinstance->configdata = '';
        $blockinstance->timecreated = time();
        $blockinstance->timemodified = $blockinstance->timecreated;
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

        // Ensure the block context is created.
        \context_block::instance($blockinstance->id);

        // If the new instance was created, allow it to do additional setup.
        if ($block = block_instance($blockname, $blockinstance)) {
            $block->instance_create();
        }

        return $blockinstance;
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
