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
 * Ad-hoc task to apply blocks set up rules.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_blocksmanager\task;

use tool_blocksmanager\invalid_setup_item_exception;
use tool_blocksmanager\setup_item;

defined('MOODLE_INTERNAL') || die();


class apply_blocks_set_up extends \core\task\adhoc_task {

    /**
     * @inheritdoc
     */
    public function execute() {
        $todo = [];
        $items = $this->get_custom_data_as_string();
        $items = explode("\n", $items);

        // Build a list of items to go through.
        foreach ($items as $item) {
            if (!empty($item)) {
                try {
                    $todo[] = new setup_item($item);
                } catch (invalid_setup_item_exception $exception) {
                    mtrace('Invalid blocks setup item: ' . $exception->getMessage());
                }
            }
        }

        // TODO: apply each setup item.
    }

}