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
 * Custom block manager.
 *
 * @package    tool_blocksmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

defined('MOODLE_INTERNAL') || die();

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

            if (!$this->is_locked_region($data->bui_defaultregion) && !$this->is_locked_course_category($this->page->category)) {
                $bi->defaultregion = $data->bui_defaultregion;
            } else if ($block->instance->defaultregion != $data->bui_defaultregion) {
                $warning = true;
            }

            if (!$this->is_locked_region($data->bui_defaultregion) && !$this->is_locked_course_category($this->page->category)) {
                $bi->defaultweight = $data->bui_defaultweight;
            } else if ($block->instance->defaultweight != $data->bui_defaultweight) {
                $warning = true;
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

            // Blocks Manager custom code.
            if (!$this->is_locked_region($data->bui_region) && !$this->is_locked_course_category($this->page->category)) {
                $bp->visible = $data->bui_visible;
            } else {
                if ($this->can_change_visibility($block)) {
                    $bp->visible = $data->bui_visible;
                } else {
                    $bp->visible = $block->instance->visible;
                    $warning = true;
                }
            }

            if (!$this->is_locked_region($data->bui_region) && !$this->is_locked_course_category($this->page->category)) {
                $bp->region = $data->bui_region;
            } else {
                $warning = true;
                $bp->region = $block->instance->region;
            }

            if (!$this->is_locked_region($data->bui_region) && !$this->is_locked_course_category($this->page->category)) {
                $bp->weight = $data->bui_weight;
            } else {
                $warning = true;
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

        if ($this->is_locked_region($defaulregion) && $this->is_locked_course_category($this->page->category)) {
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
     * - If block is in locked region - don't display any controls.
     *
     * @param $block
     *
     * @return \an|array
     */
    public function edit_controls($block) {
        if ($this->is_locked_region($block->instance->region) && $this->is_locked_course_category($this->page->category)) {
            $controls = [];

            $actionurl = $this->page->url->out(false, array('sesskey' => sesskey()));
            $blocktitle = $block->title;
            if (empty($blocktitle)) {
                $blocktitle = $block->arialabel;
            }

            if ((get_config('tool_blocksmanager', 'unlockconfig')) && ($this->page->user_can_edit_blocks() || $block->user_can_edit())) {
                // Edit config icon - always show - needed for positioning UI.
                $str = new \lang_string('configureblock', 'block', $blocktitle);
                $controls[] = new \action_menu_link_secondary(
                    new moodle_url($actionurl, array('bui_editid' => $block->instance->id)),
                    new \pix_icon('t/edit', $str, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                    $str,
                    array('class' => 'editing_edit')
                );
            }

            if ($this->can_change_visibility($block)) {
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

            return $controls;
        }

        return parent::edit_controls($block);
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

        if (!$this->can_change_visibility($block)) {
            return false;
        }

        return parent::process_url_show_hide();
    }

    /**
     * Check if visibility can be changed.
     *
     * @param \block_base $block Block instance.
     *
     * @return bool
     * @throws \dml_exception
     */
    public function can_change_visibility($block) {
        return  get_config('tool_blocksmanager', 'unlockvisibility')
            && $this->page->user_can_edit_blocks()
            && $block->instance_can_be_hidden();
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
        $newregion = optional_param('bui_newregion', '', PARAM_ALPHANUMEXT);

        if ($this->is_locked_region($newregion) && $this->is_locked_course_category($this->page->category)) {
            throw new \moodle_exception('error:lockedregion', 'tool_blocksmanager');
        }

        parent::process_url_move();
    }

    /**
     * Check if provided region is locked.
     *
     * @param string $region Region name.
     *
     * @return bool
     * @throws \dml_exception
     */
    public function is_locked_region(string $region) {
        return $this->value_is_in_config($region, 'lockedregions');
    }

    /**
     * Check if provided value in config.
     *
     * This method will check comma separated list of values stored in config text field.
     *
     * @param $value
     * @param $configname
     *
     * @return bool
     * @throws \dml_exception
     */
    protected function value_is_in_config(string $value, string $configname) {
        $result = false;

        $configvalue = get_config('tool_blocksmanager', $configname);
        if (!empty($value) && !empty($configvalue)) {
            $configvalue = explode(',', $configvalue);
            $configvalue = array_map('trim', $configvalue);
            $result = in_array($value, $configvalue);
        }

        return $result;
    }

    /**
     * Check if the provided course category is locked.
     *
     * @param stdClass | null $category Course category.
     *
     * @return bool
     */
    public function is_locked_course_category($category) {
        if (empty($category)) {
            return false;
        } else {
            if (empty($category->id)) {
                throw new \coding_exception('Course category must have id');
            }

            return in_array($category->id, $this->get_locked_categories());
        }
    }

    /**
     * Get list of locked categories.
     *
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_locked_categories() {
        if (isset($this->lockedcategories) && is_array($this->lockedcategories)) {
            return $this->lockedcategories;
        }

        $this->lockedcategories = [];
        $lockedcats = get_config('tool_blocksmanager', 'lockedcategories');

        if (!empty($lockedcats)) {
            $lockedcats = explode(',', $lockedcats);

            foreach ($lockedcats as $cat) {
                if ($category = \core_course_category::get($cat, IGNORE_MISSING)) {
                    $this->lockedcategories[] = $cat;
                    $this->lockedcategories = array_merge(
                        $this->lockedcategories,
                        $category->get_all_children_ids()
                    );
                }

            }
        }

        return $this->lockedcategories;
    }

}