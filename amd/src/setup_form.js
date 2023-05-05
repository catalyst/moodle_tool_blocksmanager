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
 * @copyright 2019 Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    function() {
        /**
         * Module level variables.
         */
        var SetUpForm = {};

        /**
         * Selectors used in the code.
         */
        var SELECTORS = {
            DATA_TEXT_AREA: 'id_data',
            ADD_LINE_BUTTON: 'id_addline',
            REGION: 'id_region',
            BLOCK: 'id_block',
            CATEGORIES: 'id_categories',
            WEIGHT: 'id_weight',
            CONFIG_DATA: 'id_configdata',
            VISIBLE: 'id_visible',
            REPOSITION: 'id_reposition',
            ADD: 'id_add',
            UPDATE: 'id_update',
            SHOWINSUBCONTEXTS: 'id_showinsubcontexts',
            PAGETYPEPATTERN: 'id_pagetypepattern',
            SECOND_REGION: 'id_secondregion',
            SECOND_WEIGHT: 'id_secondweight',
        };

        /**
         * Initialise the class.
         *
         * @param {string} delimiter
         */
        SetUpForm.init = function(delimiter) {
            document.getElementById(SELECTORS.ADD_LINE_BUTTON).addEventListener('click', function() {
                var region = document.getElementById(SELECTORS.REGION).value;
                var block = document.getElementById(SELECTORS.BLOCK).value;
                var categories = document.getElementById(SELECTORS.CATEGORIES).value;
                var weight = document.getElementById(SELECTORS.WEIGHT).value;
                var configdata = document.getElementById(SELECTORS.CONFIG_DATA).value;
                var visible = document.getElementById(SELECTORS.VISIBLE).value;
                var reposition = document.getElementById(SELECTORS.REPOSITION).value;
                var add = document.getElementById(SELECTORS.ADD).value;
                var update = document.getElementById(SELECTORS.UPDATE).value;
                var showinsubcontexts = document.getElementById(SELECTORS.SHOWINSUBCONTEXTS).value;
                var pagetypepattern = document.getElementById(SELECTORS.PAGETYPEPATTERN).value;
                var secondregion = document.getElementById(SELECTORS.SECOND_REGION).value;
                var secondweight = document.getElementById(SELECTORS.SECOND_WEIGHT).value;
                var textArea = document.getElementById(SELECTORS.DATA_TEXT_AREA);

                var newLine = region + delimiter
                    + categories + delimiter
                    + block + delimiter
                    + weight + delimiter
                    + visible + delimiter
                    + reposition + delimiter
                    + configdata + delimiter
                    + add + delimiter
                    + update;

                if (reposition === '1') {
                    newLine = newLine + delimiter + secondregion;
                    newLine = newLine + delimiter + secondweight;
                }

                newLine = newLine + delimiter
                    + showinsubcontexts + delimiter
                    + pagetypepattern
                    + '\n';

                textArea.value = textArea.value + newLine;
            });
        };

        return SetUpForm;
    }
);

