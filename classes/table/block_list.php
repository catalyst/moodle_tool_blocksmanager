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
 * Table to display a list of regions.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_blocksmanager\table;

use core\persistent;
use tool_blocksmanager\blocks_controller;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

class block_list extends region_list {

    /**
     * Return a list of all columns;
     * @return array
     */
    protected function get_columns() {
        return [
            'region',
            'block',
            'categories',
            'config',
            'remove',
            'hide',
            'move',
            'actions'
        ];
    }

    /**
     * Display column.
     *
     * @param persistent $record
     * @return string
     */
    public function col_block(persistent $record) {
        return $this->get_display_value($record, 'block');
    }

    /**
     * Return base URL for action buttons.
     * @return string
     */
    protected function get_base_url() {
        return blocks_controller::get_base_url();
    }

    /**
     * {@inheritDoc}
     */
    public function print_nothing_to_display() {
        echo \html_writer::div(get_string('no_blocks', 'tool_blocksmanager'));
    }

}
