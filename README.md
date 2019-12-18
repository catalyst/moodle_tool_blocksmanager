# Block Manager #

An admin tool that allows you to control a block layout across units.

## Features ##
* Lock down functionality of a block region.
  - Set **Locked course categories** (Block regions in courses from the selected course categories (including child categories) will be locked according to "Locked regions" settings).
  - Set **Locked regions** (blocks cannot be added/deleted or moved/reordered within these regions).
  - Unlock **Visibility settings** (visibility settings will be available for the blocks in the locked regions).
  - Unlock **Block configuration** (configuration of the blocks will be available in the locked regions). 
* TODO: Add a specific set of blocks to ALL units, in a region.
* TODO: Define default region and weight for every block type, so that existing blocks in units are placed in those regions and weighted accordingly.

## Installation ##
1. Download files or clone the repository to /admin/tool/blocksmanager
2. Install the plugin as usual.
3. Add following line to your config.php.

```php
 $CFG->blockmanagerclass = '\\tool_blocksmanager\\blocks';
```

# Crafted by Catalyst IT


This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)
