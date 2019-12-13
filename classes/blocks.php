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

class blocks extends \block_manager {

    /**
     * Handle showing/processing the submission from the block editing form.
     * @return boolean true if the form was submitted and the new config saved. Does not
     *      return if the editing form was displayed. False otherwise.
     */
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
        //$editpage->set_context($block->context);
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
        //some functions like MoodleQuickForm::addHelpButton use $OUTPUT so we need to replace that to
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
            } else  if ($parentcontext->contextlevel == CONTEXT_USER) {
                // for user context
                // subpagepattern should be null
                if ($bits[0] == 'user' or $bits[0] == 'my') {
                    // we don't need subpagepattern in usercontext
                    $bi->subpagepattern = null;
                }
            }

            $warning = false;

            if (!$this->is_locked_layout($this->page->pagelayout) && !$this->is_locked_region($data->bui_defaultregion)) {
                $bi->defaultregion = $data->bui_defaultregion;
            } else {
                $warning = true;
            }

            $bi->defaultweight = $data->bui_defaultweight;
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
            $bp->visible = $data->bui_visible;


            if (!$this->is_locked_layout($this->page->pagelayout) && !$this->is_locked_region($data->bui_region)) {
                $bp->region = $data->bui_region;
            } else {
                $warning = true;
                $bp->region = $block->instance->region;
            }


            $bp->weight = $data->bui_weight;
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

            if ($warning) {
                redirect($this->page->url,
                    'Region is locked. Position changes is not saved.',
                    null,
                    notification::NOTIFY_ERROR
                );
            } else {
                redirect($this->page->url);

            }

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

        if ($this->is_locked_layout($this->page->pagelayout) && $this->is_locked_region($defaulregion)) {
            redirect($this->page->url,
                'Default region is locked. Block has not been added',
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
        if ($this->is_locked_layout($this->page->pagelayout) && $this->is_locked_region($block->instance->region)) {
            return [];
        }

        return parent::edit_controls($block);
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

        if ($this->is_locked_layout($this->page->pagelayout) && $this->is_locked_region($newregion)) {
            throw new \moodle_exception('Region is locked. Position changes will not be saved.');
        }

        parent::process_url_move();
    }

    /**
     * Check if bocks on provided layout can be locked.
     *
     * @param string $layout Layout name.
     *
     * @return bool
     * @throws \dml_exception
     */
    public function is_locked_layout(string $layout) {
        // Config has regions excluded from locking.
        return !$this->value_is_in_config($layout, 'excludedlayouts');
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

}