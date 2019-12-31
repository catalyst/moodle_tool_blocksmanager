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
 * Helper class.
 *
 * @package     tool_blocksmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_blocksmanager;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Helper function to return a list of categories including all children for all provided categories.
     *
     * @param string $catids A string of comma separated category ids.
     *
     * @return array
     */
    public static function get_categories_and_children(string $catids) {
        $result = [];

        if (!empty($catids)) {
            $catids = explode(',', $catids);

            foreach ($catids as $cat) {
                if ($category = \core_course_category::get($cat, IGNORE_MISSING)) {
                    $result[] = $cat;
                    $result = array_merge(
                        $result,
                        $category->get_all_children_ids()
                    );
                }
            }
        }

        return $result;
    }

}
