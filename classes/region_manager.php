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
 * Region manager class for manipulating with regions on the edit page.
 *
 * @package     tool_blocksmanager
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_blocksmanager;

use tool_blocksmanager\table\region_list;

defined('MOODLE_INTERNAL') || die();


class region_manager {
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
     * Set up external page.
     */
    protected function set_external_page() {
        admin_externalpage_setup('tool_blocksmanager/region');
    }

    /**
     * Returns base URL.
     *
     * @return string
     */
    public static function get_base_url() {
        return '/admin/tool/blocksmanager/region.php';
    }

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

        $PAGE->set_url(new \moodle_url(self::get_base_url(), ['action' => $action, 'id' => $id]));

        if ($id) {
            $region = new region($id);
            $PAGE->navbar->add($region->get('region'));

        } else {
            $region = null;
            $PAGE->navbar->add(get_string('newregionlocking', 'tool_blocksmanager'));
        }

        $form = new form\region_form($PAGE->url->out(false), ['persistent' => $region]);

        if ($form->is_cancelled()) {
            redirect(new \moodle_url(self::get_base_url()));
        } else if ($data = $form->get_data()) {
            try {
                if (empty($data->id)) {
                    $persistent = new region(0, $data);
                    $persistent->create();
                } else {
                    $region->from_record($data);
                    $region->update();
                }
                \core\notification::success(get_string('changessaved'));
            } catch (\Exception $e) {
                \core\notification::error($e->getMessage());
            }
            redirect(new \moodle_url(self::get_base_url()));
        } else {
            if (empty($region)) {
                $this->header(get_string('newregionlocking', 'tool_blocksmanager'));
            } else {
                $this->header(get_string('editregionlocking', 'tool_blocksmanager'));
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
        $region = new region($id);
        $region->delete();

        redirect(new \moodle_url(self::get_base_url()));
    }

    /**
     * Execute view action.
     *
     * @throws \coding_exception
     */
    protected function view() {
        $records = region::get_records();

        $this->header(get_string('manageregionlocking', 'tool_blocksmanager'));
        $this->print_add_button();

        $table = new region_list();
        $table->display($records);

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
            new \moodle_url(self::get_base_url(), ['action' => self::ACTION_ADD]),
            get_string('addregionlocking', 'tool_blocksmanager')
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