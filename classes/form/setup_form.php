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
 * Form to manipulate with blocks.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_blocksmanager\form;

use tool_blocksmanager\block_manager;
use tool_blocksmanager\invalid_setup_item_exception;
use tool_blocksmanager\setup_item;

defined('MOODLE_INTERNAL') || die();

class setup_form extends \moodleform {

    /**
     * @inheritdoc
     */
    protected function definition() {
        global $PAGE;

        $blocks = [];
        foreach ($PAGE->blocks->get_installed_blocks() as $block) {
            $blocks[$block->name] = $block->name;
        }

        $weightoptions = [];
        for ($i = -block_manager::MAX_WEIGHT; $i <= block_manager::MAX_WEIGHT; $i++) {
            $weightoptions[$i] = $i;
        }
        $first = reset($weightoptions);
        $weightoptions[$first] = get_string('bracketfirst', 'block', $first);
        $last = end($weightoptions);
        $weightoptions[$last] = get_string('bracketlast', 'block', $last);

        $regions = implode(', ', array_keys($PAGE->theme->get_all_block_regions()));

        $this->_form->addElement('static', 'availableregions', get_string('availableregions', 'tool_blocksmanager'), $regions);
        $this->_form->addElement('text', 'region', get_string('field_region', 'tool_blocksmanager'));
        $this->_form->addElement('select', 'block', get_string('field_block', 'tool_blocksmanager'), $blocks);
        $this->_form->addElement('select', 'categories', get_string('field_categories', 'tool_blocksmanager'),
            \core_course_category::make_categories_list(), ['multiple' => true]);
        $this->_form->addElement('select', 'weight', get_string('weight', 'block'), $weightoptions);
        $this->_form->addElement('button', 'addline', get_string('addnew', 'tool_blocksmanager'));
        $this->_form->addElement('textarea', 'data', get_string('setofblocks', 'tool_blocksmanager'), 'cols="80" rows="20"');
        $this->_form->addRule('data', null, 'required');
        $this->_form->setType('data', PARAM_RAW);

        $this->add_action_buttons(false, 'Apply set of blocks');
        $this->_form->addElement('static', 'availableregions', null, get_string('applydesc', 'tool_blocksmanager'));

    }

    /**
     * @inheritdoc
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $rules = explode("\n", $data['data']);

        foreach ($rules as $key => $rule) {
            if (!empty($rule)) {
                try {
                    $item = new setup_item($rule);
                } catch (invalid_setup_item_exception $exception) {
                    $errors['data'] = $exception->getMessage();
                }
            }
        }

        return $errors;
    }
}
