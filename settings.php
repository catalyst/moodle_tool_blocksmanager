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
 * Plugin administration pages are defined here.
 *
 * @package     tool_blocksmanager
 * @category    admin
 * @copyright   2019 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_blocksmanager_settings', get_string('pluginname', 'tool_blocksmanager'));
    $ADMIN->add('tools', $settings);
    if (!during_initial_install()) {

        // Region locking.
        $settings->add(new admin_setting_heading('tool_blocksmanager/lockingheading',
                new lang_string('lockingheading', 'tool_blocksmanager'),
                '')
        );

        // Course categories to apply locking.
        $settings->add(new admin_setting_configmultiselect('tool_blocksmanager/lockedcategories',
                new lang_string('lockedcategories', 'tool_blocksmanager'),
                new lang_string('lockedcategories_desc', 'tool_blocksmanager'),
                [],
                \core_course_category::make_categories_list()
            )
        );

        // Regions to lock.
        $regions = implode(', ', array_keys($PAGE->theme->get_all_block_regions()));
        $settings->add(new admin_setting_configtext('tool_blocksmanager/lockedregions',
                new lang_string('lockedregions', 'tool_blocksmanager'),
                new lang_string('lockedregions_desc', 'tool_blocksmanager', $regions),
                '')
        );

        // Visibility can be changed?
        $settings->add(new admin_setting_configcheckbox('tool_blocksmanager/unlockvisibility',
                new lang_string('unlockvisibility', 'tool_blocksmanager'),
                new lang_string('unlockvisibility_desc', 'tool_blocksmanager'),
                1)
        );

    }
}