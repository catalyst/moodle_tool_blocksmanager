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
 * Form to manipulate with regions.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_blocksmanager\form;

use tool_blocksmanager\region;

defined('MOODLE_INTERNAL') || die();

class region_form extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'tool_blocksmanager\\region';

    /**
     * @inheritdoc
     */
    protected function definition() {
        global $PAGE;

        $mform = $this->_form;

        $mform->addElement('text', 'region', get_string('field_region', 'tool_blocksmanager'));
        $mform->addRule('region', get_string('required'), 'required', null, 'client');

        $regions = implode(', ', array_keys($PAGE->theme->get_all_block_regions()));
        $mform->addElement('static', 'availableregions', get_string('availableregions', 'tool_blocksmanager'), $regions);

        $mform->addElement('select',
            'categories',
            get_string('field_categories', 'tool_blocksmanager'),
            \core_course_category::make_categories_list(),
            ['multiple' => true]
        );

        $mform->addElement('selectyesno', 'config', get_string('field_config', 'tool_blocksmanager'));
        $mform->addElement('selectyesno', 'remove', get_string('field_remove', 'tool_blocksmanager'));
        $mform->addElement('selectyesno', 'hide', get_string('field_hide', 'tool_blocksmanager'));
        $mform->addElement('selectyesno', 'movein', get_string('field_movein', 'tool_blocksmanager'));
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

        // Convert the single properties into a group.
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
        $newerrors = array();

        if (empty($data->region)) {
            $newerrors['region'] = get_string('regionrequired', 'tool_blocksmanager');
        }

        if (empty($data->id)) {
            if ($records = region::get_records(['region' => $data->region, 'categories' => $data->categories])) {
                $newerrors['region'] = get_string('duplicaterule', 'tool_blocksmanager');
            }
        }

        return $newerrors;
    }
}
