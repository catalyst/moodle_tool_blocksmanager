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

/**
 * Set up item instance.
 *
 * @package    tool_blocksmanager
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_item {

    /**
     * Data delimiter.
     */
    const DATA_DELIMITER = '||';

    const PAGE_TYPE_PATTERN_DEFAULT = 'course-view-*';

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
     * Should we reposition the block if exists?
     * @var int
     */
    protected $reposition = 0;

    /**
     * Should we try to add a new instance?
     * @var int
     */
    protected $add = 0;

    /**
     * Block config data.
     * @var
     */
    protected $configdata = '';

    /**
     * Secondary region it put the block to.
     * @var string
     */
    protected $secondregion = '';

    /**
     * Secondary block weight.
     * @var int
     */
    protected $secondweight = 0;

    /**
     * Show block in subcontexts..
     * @var int
     */
    protected $showinsubcontexts = 0;

    /**
     * Page type pattern.
     * @var int
     */
    protected $pagetypepattern = self::PAGE_TYPE_PATTERN_DEFAULT;

    /**
     * Constructor.
     *
     * @param string $data Incoming data.
     *
     *  0 - region
     *  1 - categories
     *  2 - block name
     *  3 - weight
     *  4 - visible
     *  5 - reposition
     *  6 - config data
     *  7 - add
     *
     *  If reposition = 0
     *
     *  8 - show in subcontexts
     *  9 - page type pattern
     *
     *  If reposition = 1
     *
     *  8 - second region
     *  9 - second weight
     *  10 - show in subcontexts
     *  11 - page type pattern
     *
     * @throws \tool_blocksmanager\invalid_setup_item_exception
     */
    public function __construct(string $data) {
        // Should be at least 3 values.
        if (substr_count($data, self::DATA_DELIMITER) < 2) {
            throw new invalid_setup_item_exception('notallrequired');
        }

        $values = explode(self::DATA_DELIMITER, trim($data));

        $this->region = (string) trim($values[0]);

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
        $this->blockname = (string) trim($values[2]);

        if (empty($this->blockname)) {
            throw new invalid_setup_item_exception('emptyblockname');
        }

        if (isset($values[3]) && is_numeric($values[3])) {
            $this->weight = (int) trim($values[3]);
        }

        if (isset($values[4]) && is_numeric($values[4])) {
            $this->visible = (int) trim($values[4]);
        }

        if (isset($values[5]) && is_numeric($values[5])) {
            $this->reposition = (int) trim($values[5]);
        }

        if (isset($values[6]) && is_string($values[6]) && !empty($values[6])) {
            $this->configdata = (string) trim($values[6]);
        }

        if (isset($values[7]) && is_numeric($values[7])) {
            $this->add = (int) trim($values[7]);
        }

        if ($this->reposition == 1) {
            if (isset($values[8])) {
                $this->secondregion = (string) trim($values[8]);
            }

            if (isset($values[9])) {
                $this->secondweight = (int) trim($values[9]);
            }
            $showinsubcontextindex = 10;
            $pagetypepatternindex  = 11;
        } else {
            $showinsubcontextindex = 8;
            $pagetypepatternindex  = 9;
        }

        if (!empty($this->reposition) && empty($this->secondregion)) {
            throw new invalid_setup_item_exception('emptysecondregion');
        }

        if (!empty($this->reposition) && !empty($this->add)) {
            throw new invalid_setup_item_exception('conflictreposition');
        }

        if (isset($values[$showinsubcontextindex]) && is_numeric($values[$showinsubcontextindex])) {
            $this->showinsubcontexts = (int) trim($values[$showinsubcontextindex]);
        }

        if (isset($values[$pagetypepatternindex])) {
            $this->pagetypepattern = (string) trim($values[$pagetypepatternindex]);
            if (empty($this->pagetypepattern)) {
                throw new invalid_setup_item_exception('emptypagetypepattern');
            }
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
     * @return bool
     */
    public function get_visible(): bool {
        return (bool) $this->visible;
    }

    /**
     * Returns config data.
     *
     * @return string
     */
    public function get_config_data(): string {
        return $this->configdata;
    }

    /**
     * Returns reposition.
     *
     * @return bool
     */
    public function get_reposition(): bool {
        return (bool) $this->reposition;
    }

    /**
     * Returns should we add a new block.
     *
     * @return bool
     */
    public function get_add() {
        return (bool) $this->add;
    }

    /**
     * Returns should be shown in subcontexts.
     *
     * @return bool
     */
    public function get_showinsubcontexts() {
        return (bool) $this->showinsubcontexts;
    }

    /**
     * Returns the page type pattern.
     *
     * @return string
     */
    public function get_pagetypepattern() {
        return  $this->pagetypepattern;
    }

    /**
     * Returns secondary region.
     *
     * @return mixed
     */
    public function get_second_region() {
        return $this->secondregion;
    }

    /**
     * Returns secondary weight.
     *
     * @return int
     */
    public function get_second_weight(): int {
        return (int) $this->secondweight;
    }

}
