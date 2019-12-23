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
 * Abstract manager class for manipulating with records.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_blocksmanager;

defined('MOODLE_INTERNAL') || die();

abstract class base_manager {
    /**
     * View action.
     */
    const ACTION_VIEW = 'view';

    /**
     * Add action.
     */
    const ACTION_ADD = 'add';

    /**
     * Edit action.
     */
    const ACTION_EDIT = 'edit';

    /**
     * Delete action.
     */
    const ACTION_DELETE = 'delete';

    /**
     * Locally cached $OUTPUT object.
     * @var \bootstrap_renderer
     */
    protected $output;

    /**
     * region_manager constructor.
     */
    public function __construct() {
        global $OUTPUT;

        $this->output = $OUTPUT;
    }

    /**
     * Execute required action.
     *
     * @param string $action Action to execute.
     *
     * @throws \coding_exception
     */
    public function execute($action) {

        $this->set_external_page();

        switch($action) {
            case self::ACTION_ADD:
            case self::ACTION_EDIT:
                $this->edit($action, optional_param('id', null, PARAM_INT));
                break;

            case self::ACTION_DELETE:
                $this->delete(required_param('id', PARAM_INT));
                break;

            case self::ACTION_VIEW:
            default:
                $this->view();
                break;
        }
    }

    /**
     * Set external page for the manager.
     */
    abstract protected function set_external_page();

    /**
     * Return record instance.
     *
     * @param int $id
     * @param \stdClass|null $data
     *
     * @return \core\persistent
     */
    abstract protected function get_instance($id = 0, \stdClass $data = null);

    /**
     * Print out all records in a table.
     */
    abstract protected function display_all_records();

    /**
     * Returns a text for create new record button.
     * @return string
     */
    abstract protected function get_create_button_text();

    /**
     * Returns form for the record.
     *
     * @param \core\persistent|null $instance
     *
     * @return \core\form\persistent
     */
    abstract protected function get_form($instance);

    /**
     * New record heading string.
     * @return string
     */
    abstract protected function get_new_heading();

    /**
     * Edit record heading string.
     * @return string
     */
    abstract protected function get_edit_heading();

    /**
     * Returns base URL for the manager.
     * @return \moodle_url
     */
    abstract public static function get_base_url();

    /**
     * Execute edit action.
     *
     * @param string $action Could be edit or create.
     * @param null|int $id Id of the region or null if creating a new one.
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function edit($action, $id = null) {
        global $PAGE;

        $PAGE->set_url(new \moodle_url(static::get_base_url(), ['action' => $action, 'id' => $id]));
        $instance = null;

        if ($id) {
            $instance = $this->get_instance($id);
        }

        $form = $this->get_form($instance);

        if ($form->is_cancelled()) {
            redirect(new \moodle_url(static::get_base_url()));
        } else if ($data = $form->get_data()) {
            try {
                if (empty($data->id)) {
                    $persistent = $this->get_instance(0, $data);
                    $persistent->create();
                } else {
                    $instance->from_record($data);
                    $instance->update();
                }
                \core\notification::success(get_string('changessaved'));
            } catch (\Exception $e) {
                \core\notification::error($e->getMessage());
            }
            redirect(new \moodle_url(static::get_base_url()));
        } else {
            if (empty($instance)) {
                $this->header($this->get_edit_heading());
            } else {
                $this->header($this->get_new_heading());
            }
        }

        $form->display();
        $this->footer();
    }

    /**
     * Execute delete action.
     *
     * @param int $id ID of the region.
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function delete($id) {
        require_sesskey();
        $instance = $this->get_instance($id);
        $instance->delete();

        \core\notification::success(get_string('deleted'));
        redirect(new \moodle_url(static::get_base_url()));
    }

    /**
     * Execute view action.
     *
     * @throws \coding_exception
     */
    protected function view() {

        $this->header(get_string('manageregionlocking', 'tool_blocksmanager'));
        $this->print_add_button();
        $this->display_all_records();

        $this->footer();
    }

    /**
     * Print out add button.
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function print_add_button() {
        echo $this->output->single_button(
            new \moodle_url(static::get_base_url(), ['action' => self::ACTION_ADD]),
            $this->get_create_button_text()
        );
    }

    /**
     * Print out page header.
     * @param string $title Title to display.
     */
    protected function header($title) {
        echo $this->output->header();
        echo $this->output->heading($title);
    }

    /**
     * Print out the page footer.
     *
     * @return void
     */
    protected function footer() {
        echo $this->output->footer();
    }

}