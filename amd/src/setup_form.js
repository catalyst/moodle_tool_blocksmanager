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
 * JavaScript for set up form.
 *
 * @module    tool_blocksmanager/setup_form
 * @package   tool_blocksmanager
 * @copyright 2019 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['jquery'], function($) {
        /**
         * Module level variables.
         */
        var SetUpForm = {};

        /**
         * Selectors used in the code.
         */
        var SELECTORS = {
            DATA_TEXT_AREA: '#id_data',
            ADD_LINE_BUTTON: '#id_addline',
            REGION: '#id_region',
            BLOCK: '#id_block',
            CATEGORIES: '#id_categories',
            WEIGHT: '#id_weight',
            CONFIG_DATA: '#id_configdata',
            VISIBLE: '#id_visible',
            REPOSITION: '#id_reposition',
            ADD: '#id_add',
            SECOND_REGION: '#id_secondregion',
            SECOND_WEIGHT: '#id_secondweight',

        };

        /**
         * Initialise the class.
         */
        SetUpForm.init = function(delimiter) {
            $(SELECTORS.ADD_LINE_BUTTON).click(function () {
                var region = $(SELECTORS.REGION).val();
                var block = $(SELECTORS.BLOCK).val();
                var categories = $(SELECTORS.CATEGORIES).val();
                var weight = $(SELECTORS.WEIGHT).val();
                var configdata = $(SELECTORS.CONFIG_DATA).val();
                var visible = $(SELECTORS.VISIBLE).val();
                var reposition = $(SELECTORS.REPOSITION).val();
                var add = $(SELECTORS.ADD).val();
                var secondregion = $(SELECTORS.SECOND_REGION).val();
                var secondweight = $(SELECTORS.SECOND_WEIGHT).val();
                var textArea = $(SELECTORS.DATA_TEXT_AREA);

                var newLine = region + delimiter
                    + categories + delimiter
                    + block + delimiter
                    + weight + delimiter
                    + visible + delimiter
                    + reposition + delimiter
                    + configdata + delimiter
                    + add;

                if (reposition === '1') {
                    newLine = newLine + delimiter + secondregion;
                    newLine = newLine + delimiter + secondweight;
                }

                newLine = newLine + '\n';

                textArea.val(textArea.val() + newLine);
            });
        };

        return SetUpForm;
    });
