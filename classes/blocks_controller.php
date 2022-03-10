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

use tool_blocksmanager\table\block_list;

/**
 * Block controller class for manipulating with blocks on the edit page.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blocks_controller extends base_controller {

    /**
     * @inheritdoc
     */
    protected function get_instance($id = 0, \stdClass $data = null) {
        return new block($id, $data);
    }

    /**
     * @inheritdoc
     */
    protected function get_form($instance) {
        global $PAGE;

        return new form\blocks_form($PAGE->url->out(false), ['persistent' => $instance]);
    }

    /**
     * @inheritdoc
     */
    protected function display_all_records() {
        $records = block::get_records();

        $table = new block_list();
        $table->display($records);
    }

    /**
     * @inheritdoc
     */
    protected function get_create_button_text() {
        return get_string('addblocklocking', 'tool_blocksmanager');
    }

    /**
     * @inheritdoc
     */
    protected function set_external_page() {
        admin_externalpage_setup('tool_blocksmanager/block');
    }

    /**
     * @inheritdoc
     */
    public static function get_base_url() {
        return '/admin/tool/blocksmanager/block.php';
    }

    /**
     * @inheritdoc
     */
    protected function get_view_heading() {
        return get_string('manageblocklocking', 'tool_blocksmanager');
    }

    /**
     * @inheritdoc
     */
    protected function get_new_heading() {
        return get_string('newblocklocking', 'tool_blocksmanager');
    }

    /**
     * @inheritdoc
     */
    protected function get_edit_heading() {
        return get_string('editblocklocking', 'tool_blocksmanager');
    }
}
