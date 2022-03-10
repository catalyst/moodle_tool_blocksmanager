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

namespace tool_blocksmanager\form;

use tool_blocksmanager\block;

/**
 * Form to manipulate with blocks.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blocks_form extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'tool_blocksmanager\\block';

    /**
     * @inheritdoc
     */
    protected function definition() {
        global $PAGE;

        $mform = $this->_form;

        $mform->addElement('text', 'region', get_string('field_region', 'tool_blocksmanager'));
        $mform->addRule('region', get_string('required'), 'required', null, 'client');

        $regions = implode(', ', array_keys([block::ALL_REGIONS => ''] + $PAGE->theme->get_all_block_regions()));
        $mform->addElement('static', 'availableregions', get_string('availableregions', 'tool_blocksmanager'), $regions);

        $blocks = [];
        foreach ($PAGE->blocks->get_installed_blocks() as $block) {
            $blocks[$block->name] = $block->name;
        }

        $mform->addElement('select', 'block', get_string('field_block', 'tool_blocksmanager'), $blocks);

        $mform->addElement('select',
            'categories',
            get_string('field_categories', 'tool_blocksmanager'),
            \core_course_category::make_categories_list(),
            ['multiple' => true]
        );

        $mform->addElement('selectyesno', 'config', get_string('field_config', 'tool_blocksmanager'));
        $mform->addElement('selectyesno', 'remove', get_string('field_remove', 'tool_blocksmanager'));
        $mform->addElement('selectyesno', 'hide', get_string('field_hide', 'tool_blocksmanager'));
        $mform->addElement('selectyesno', 'move', get_string('field_move', 'tool_blocksmanager'));

        $this->add_action_buttons();
    }

    /**
     * Convert fields.
     *
     * @param \stdClass $data The data.
     * @return \stdClass
     */
    protected static function convert_fields(\stdClass $data) {
        $data = parent::convert_fields($data);

        if (!empty($data->categories)) {
            $data->categories = implode(',', $data->categories);
        }

        return $data;
    }

    /**
     * Get the default data.
     *
     * @return \stdClass
     */
    protected function get_default_data() {
        $data = parent::get_default_data();

        $data->categories = explode(',', $data->categories);

        return $data;
    }

    /**
     * Extra validation.
     *
     * @param  \stdClass $data Data to validate.
     * @param  array $files Array of files.
     * @param  array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    protected function extra_validation($data, $files, array &$errors) {
        global $DB;

        $id = optional_param('id', null, PARAM_INT);

        $newerrors = array();

        if (empty($data->region)) {
            $newerrors['region'] = get_string('regionrequired', 'tool_blocksmanager');
        }

        // Check if can use All regions.
        if ($data->region == block::ALL_REGIONS) {
            $select = $DB->sql_compare_text('categories') . " = ? AND block = ? AND region <> ?";
            $params = [$data->categories, $data->block, block::ALL_REGIONS];
            $error = get_string('cantuseallregions', 'tool_blocksmanager');
        } else {
            $select = $DB->sql_compare_text('categories') . " = ? AND block = ? AND region = ? ";
            $params = [$data->categories, $data->block, block::ALL_REGIONS];
            $error = get_string('cantusespecificregion', 'tool_blocksmanager');
        }

        if (!empty($id)) {
            $select .= ' AND id <> ?';
            $params[] = $id;
        }

        if ($records = block::get_records_select($select, $params)) {
            $newerrors['region'] = $error;
        }

        // Check duplicates.
        $select = $DB->sql_compare_text('categories') . " = ? AND block = ? AND region = ?";
        $params = [$data->categories, $data->block, $data->region];

        if (!empty($id)) {
            $select .= ' AND id <> ?';
            $params[] = $id;
        }

        if ($records = block::get_records_select($select, $params)) {
            $newerrors['region'] = get_string('duplicaterule', 'tool_blocksmanager');
        }

        return $newerrors;
    }
}
