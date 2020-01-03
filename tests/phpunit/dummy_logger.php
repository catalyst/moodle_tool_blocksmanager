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
 * Dummy logger class for testing
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class blocks_manager_dummy_logger implements \tool_blocksmanager\logger_interface {

    /**
     * A list of log messages.
     * @var array
     */
    protected $logs = [];

    /**
     * @inheritdoc
     */
    public function log_messages(array $messages) {
        foreach ($messages as $message) {
            $this->log_message($message);
        }
    }

    /**
     * @inheritdoc
     */
    public function log_message(string $message) {
        $this->logs[] = $message;
    }

    /**
     * Return a list of log messages.
     * @return array
     */
    public function get_logs() {
        return $this->logs;
    }

}
