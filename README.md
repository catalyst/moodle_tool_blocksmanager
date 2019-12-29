# Block Manager #

An admin tool that allows you to control a block layout across units.

## Features ##
* Lock down functionality for the blocks in the regions.
  - Create locking rules for the regions in the specific course categories (block regions in courses from the selected course categories (including child categories) will be locked according to configured rules).
  - Create locking rules for specific blocks in the specific regions of the specific course categories (block rules will override region rules).
  - There is Bypass blocks locking ('tool/blocksmanager:bypasslocking') capability  that allows users to bypass created locking rules. 
* TODO: Add a specific set of blocks to ALL units, in a region.
* TODO: Define default region and weight for every block type, so that existing blocks in units are placed in those regions and weighted accordingly.

## Installation ##
1. Download files or clone the repository to /admin/tool/blocksmanager
2. Install the plugin as usual.
3. Add following line to your config.php.

```php
 $CFG->blockmanagerclass = '\\tool_blocksmanager\\block_manager';
```

# Crafted by Catalyst IT


This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)
