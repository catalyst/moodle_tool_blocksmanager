![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/catalyst/moodle_tool_blocksmanager/ci.yml?branch=master)

# Block Manager #

An admin tool that allows you to control a block layout across units.

## Features ##
* Lock down functionality for the blocks in the regions.
  - Create locking rules for the regions in the specific course categories (block regions in courses from the selected course categories (including child categories) will be locked according to configured rules).
  - Create locking rules for specific blocks in the specific regions of the specific course categories (block rules will override region rules).
  - There is Bypass blocks locking ('tool/blocksmanager:bypasslocking') capability  that allows users to bypass created locking rules. 
* Add a specific set of blocks to the courses in the specific course categories:
  - Define region and weight for every block type.
  - Set visibility for each block type.
  - Reposition or add another block instance if it's already exist in the course.
  - Set up config data as a string if required.
  - Set whether to show the block in subcontexts.
  - Set the page type pattern.
* Add blocks to course module / activity / resource pages.

## Installation ##
1. Download files or clone the repository to /admin/tool/blocksmanager
2. Install the plugin as usual.
3. Add following line to your config.php.

```php
 $CFG->blockmanagerclass = '\\tool_blocksmanager\\block_manager';
```

## Usage ##

### Block management ###
This feature allows locking a specific block in a specific region through a selected course category so no one (expect users with 
'tool/blocksmanager:bypasslocking') can apply changes to a block configuration in that region.

Actions that could be locked per block:
- configuration
- deleting
- hiding 
- moving

To create set of rules to lock blocks, navigate to Site administration > Plugins > Admin tools > Blocks Manager > Blocks management    

### Region management ###
This feature gives an ability to lock a whole region through a selected course category. That means all blocks in the 
matching region will be locked from performing configured actions (expect for users with 'tool/blocksmanager:bypasslocking'). 

Actions that could be locked per region:
- configuration
- deleting
- hiding
- adding blocks to region  
- moving

To create set of rules to lock regions, navigate to Site administration > Plugins > Admin tools > Blocks Manager > Region management

### Set up blocks ### 
This features helps to create blocks in bulk through selected course categories (for example add a calendar block to every course 
for a selected category).

To do  that, navigate to Site administration > Plugins > Admin tools > Blocks Manager > Set up blocks

The form let you to select: 

- Region - what region to add a block to (e.g. side-pre, side-post)?
- Block - what block to add? 
- Categories - what categories to apply the block? 
- Weight - what block weight will be on those pages? 
- Config data - does block have any config? Could be applied here (but should be exact string of config that is stored in DB if you would add the block manually)? 
- Visible? - is block visible when added? 
- Add another instance (if exists)? - if the same block already exists, should we add another instance of that block? 
- Update instance (if exists)? - if the block already exists, should we update the block by new settings? 
- Reposition instance (if exists)? - if the block already exists, should we reposition the block?
- Show in subcontexts - should the block be displayed on course subpages (e.g. course page and activity pages)?
- Page type pattern - what page should the block be displayed? E.g. course-view-*, mod-assign-view and etc.
    - courses: course-view-* (https://examplemoodle.com/course/view.php?id=1)
    - assign modules: mod-assign-view (https://examplemoodle.com/mod/assign/view.php?id=1)
    - quiz modules: mod-quiz-view (https://examplemoodle.com/mod/quiz/view.php?id=1)
    - feedback modules: mod-feedback-view (https://examplemoodle.com/mod/feedback/view.php?id=1)
                                                                                                           
Once you configured all fields in the form, you click on "Add new line". This will update "Set of blocks" text field with a new configuration line. (This data will be used later for applying blocks.) 
Once all blocks are added to a set, click 'Apply set of blocks'. The form will be submitted, and the set of blocks will be applied 
by an adhoc task in the background. This means there will a little delay before you see new blocks appeared.

# Crafted by Catalyst IT


This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)
