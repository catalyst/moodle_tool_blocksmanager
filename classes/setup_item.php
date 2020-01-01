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
 * Set up item instance.
 *
 * @package    tool_blocksmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_blocksmanager;

defined('MOODLE_INTERNAL') || die();

class setup_item {

    /**
     * Data delimiter.
     */
    const DATA_DELIMITER = '||';

    /**
     * Region it put the block to.
     * @var string
     */
    protected $region;

    /**
     * A list of categories to apply set up item.
     * @var array
     */
    protected $categories;

    /**
     * A name of the block.
     * @var string
     */
    protected $blockname;

    /**
     * Block weight.
     * @var int
     */
    protected $weight = 0;

    /**
     * Block visibility.
     * @var int
     */
    protected $visible = 1;

    /**
     * Constructor.
     *
     * @param string $data Incoming data.
     *
     * @throws \tool_blocksmanager\invalid_setup_item_exception
     */
    public function __construct(string $data) {
        // Should be at least 3 values.
        if (substr_count($data, self::DATA_DELIMITER) < 2) {
            throw new invalid_setup_item_exception('notallrequired');
        }

        $values = explode(self::DATA_DELIMITER, trim($data));

        $this->region = trim($values[0]);

        if (empty($this->region)) {
            throw new invalid_setup_item_exception('emptyregion');
        }

        if (!empty($values[1])) {
            $catids = explode(',', trim($values[1]));
            foreach ($catids as $catid) {
                if (!is_numeric($catid)) {
                    throw new invalid_setup_item_exception('incorrectcategory');
                }
            }
        } else {
            throw new invalid_setup_item_exception('incorrectcategory');
        }

        $this->categories = helper::get_categories_and_children(trim($values[1]));
        $this->blockname = trim($values[2]);

        if (empty($this->blockname)) {
            throw new invalid_setup_item_exception('emptyblockname');
        }

        if (isset($values[3]) && is_numeric($values[3])) {
            $this->weight = trim($values[3]);
        }

        if (isset($values[4]) && is_numeric($values[4])) {
            $this->visible = trim($values[4]);
        }
    }

    /**
     * Returns region.
     *
     * @return mixed
     */
    public function get_region() {
        return $this->region;
    }

    /**
     * Returns categories.
     *
     * @return array
     */
    public function get_categories() {
        return $this->categories;
    }

    /**
     * Returns block name.
     *
     * @return mixed
     */
    public function get_blockname() {
        return $this->blockname;
    }

    /**
     * Returns block weight.
     *
     * @return int
     */
    public function get_weight(): int {
        return (int) $this->weight;
    }

    /**
     * Returns bock visibility.
     *
     * @return int
     */
    public function get_visible(): int {
        return (int) $this->visible;
    }

}
