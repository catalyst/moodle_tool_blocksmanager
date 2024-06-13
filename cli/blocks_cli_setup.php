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
 * CLI script to apply a set of blocks within the plugin tool_blocksmanager
 *
 * @package    tool_blocksmanager
 * @copyright  2024 Guillaume BARAT <guillaumebarat@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$longparams = [
        'filepath' => null,
        'help' => null
];

$shortmappings = [
        'f' => 'filepath',
        'h' => 'help'
];

// Get CLI params.
list($options, $unrecognized) = cli_get_params($longparams, $shortmappings);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['filepath'])) {
    echo
    "CLI script to apply a set of blocks for the plugin tool_blocksmanager from a file.

Options:
-f, --filepath  Path to the file that contains the set of block to apply.
-h, --help      Print out this help

Example:
\$ sudo -u www-data /usr/bin/php admin/tool/blocksmanager/cli/blocks_cli_setup.php --filepath=block_setup.txt
";

    die;
}

$file = $options['filepath'];

$data = file_get_contents($file);
mtrace('Getting data from ' . $options['filepath']);
if (empty($data)) {
    cli_error('The file is empty, CLI execution suspended');
}
// Calling the adhoc task creator from the plugin.
$task = new \tool_blocksmanager\task\apply_blocks_set_up();
$task->set_custom_data_as_string($data);

if (\core\task\manager::queue_adhoc_task($task)) {
    mtrace(get_string('queued', 'tool_blocksmanager'));
}

exit(0);
