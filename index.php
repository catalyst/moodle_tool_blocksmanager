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


require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');

admin_externalpage_setup('tool_blocksmanager/setup');

$url = new moodle_url('/admin/tool/blocksmanager/index.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('setuptitle', 'tool_blocksmanager'));
$PAGE->set_heading(get_string('setupheading', 'tool_blocksmanager'));

$returnurl = new moodle_url('/admin/tool/blocksmanager/index.php');

$mform = new \tool_blocksmanager\form\setup_form();
$PAGE->requires->js_call_amd('tool_blocksmanager/setup_form', 'init', [\tool_blocksmanager\setup_item::DATA_DELIMITER]);

$formdata = $mform->get_data();

if (!empty($formdata->data)) {
    $task = new \tool_blocksmanager\task\apply_blocks_set_up();
    $task->set_custom_data_as_string($formdata->data);
    if (\core\task\manager::queue_adhoc_task($task)) {
        \core\notification::success(get_string('queued', 'tool_blocksmanager'));
        redirect($returnurl);
    }
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
