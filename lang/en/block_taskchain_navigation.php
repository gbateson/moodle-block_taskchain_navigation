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
 * blocks/taskchain_navigation/lang/en/block_taskchain_navigation.php
 *
 * @package    blocks
 * @subpackage taskchain_navigation
 * @copyright  2014 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

// essential strings
$string['pluginname'] = 'TaskChain Navigation';
$string['blockdescription'] = 'This block displays a navigation menu on the course page. The menu lists course section titles sorted by grade category.';
$string['blockname'] = 'TaskChain Navigation';
$string['blocknameplural'] = 'TaskChain navigation blocks';

// roles strings
$string['taskchain_navigation:addinstance'] = 'Add a new TaskChain Navigation block';

// more strings
$string['accesscontrol'] = 'Link to access control page';
$string['accesscontrol_help'] = 'This setting specifies whether or not to display a link to the "Access control" page

The "Access control" page is a web page that can be used to select course activities by section number, by activity type or by name, and then apply any of a number of common access control settings, such visibility and availability dates.

**Yes**  
&nbsp; a link to the access control page for this course will be displayed.

**No**  
&nbsp; a link to the access control page for this course will NOT be displayed.';
$string['accesscontrolpage'] = 'Access control page';
$string['accesscontrolsettings'] = 'Access control settings';
$string['accesspagedescription'] = 'On this page you can update settings for multiple Moodle activities, including resources and labels.';
$string['activitiesselected'] = 'The following activities were selected:';
$string['activityfilters'] = 'Activity filters';
$string['activityids'] = 'Specific activities';
$string['activitynamefilters'] = 'Activity name filters';
$string['activitytypes'] = 'Activity types';
$string['activityupdatefailure'] = 'Oops, one or more activities could not be updated.';
$string['activityupdatesuccess'] = 'The selected activities were updated successfully';
$string['activityuploadlimit'] = 'Activity upload limit';
$string['allgradeableactivities'] = 'All gradeable activities';
$string['apply'] = 'Apply';
$string['applyselectedvalues'] = 'Apply selected values to the following courses';
$string['applysettings'] = 'Apply settings';
$string['arrowdown'] = 'Next section arrow';
$string['arrowdown_help'] = 'Any character or html code specified here will be displayed on the right hand side of each section of the course as a clickable link that allows students to navigate to the next section in the course.

For convenience several unicode arrows are displayed here on the configuration page. Clicking any of these sample arrows, will copy that arrow to the text box for this setting.';
$string['arrowup'] = 'Previous section arrow';
$string['arrowup_help'] = 'Any character or html code specified here will be displayed on the right hand side of each section of the course as a clickable link that allows students to navigate to the previous section in the course.

For convenience several unicode arrows are displayed here on the configuration page. Clicking any of these sample arrows, will copy that arrow to the text box for this setting.';
$string['availablefrom'] = 'Available from';
$string['availableuntil'] = 'Available until';
$string['categorycollapse'] = 'Collapse single-child categories';
$string['categorycollapse_help'] = 'This setting specifies how grade categories which contain activities from only one section will be displayed in this block.

**No**  
&nbsp; both the grade category name and the section title will be displayed in this block

**Yes - display child name**  
&nbsp; only the section title will be displayed in this block

**Yes - display parent name**  
&nbsp; only the grade category name will be displayed in this block';
$string['categorygradeposition'] = 'Grade position';
$string['categoryignorechars'] = 'Ignore these characters';
$string['categoryignorechars_help'] = 'any characters specified here will be removed from the grade category names';
$string['categoryprefixes'] = 'Grade category name prefixes';
$string['categorysectionnum'] = 'Prepend section number';
$string['categorysectionnum_help'] = '**Yes**  
&nbsp; the section number will be prepended to the name of any grade categories whose activities are all in a single section of the course

**No**  
&nbsp; section numbers will not be added to any category names';
$string['categoryshortnames'] = 'Shorten category names';
$string['categoryshortnames_help'] = '**Yes**  
&nbsp; if the names of the child grade categories of a parent grade category share a common prefix with the name of the parent grade category, then the prefix will be removed from the names of the child grade categories

**No**  
&nbsp; the full name of all child categories will always be displayed in this block';
$string['categoryshowweighting'] = 'Show grade weighting';
$string['categoryshowweighting_help'] = '**Yes**  
&nbsp; the weighting of a grade category will be appended to its name

**No**  
&nbsp; the weighting of a grade category will not be appended to its name';
$string['categoryskipempty'] = 'Skip empty categories';
$string['categoryskipempty_help'] = '**Yes**  
&nbsp; empty grade categories will not be displayed in this block

**No**  
&nbsp; grade categories will be displayed in this block even if they do not contain any activities';
$string['categoryskiphidden'] = 'Skip hidden categories';
$string['categoryskiphidden_help'] = '**Yes**  
&nbsp; hidden grade categories will not be displayed in this block

**No**  
&nbsp; grade categories will be displayed in this block even if they are hidden';
$string['categoryskipzeroweighted'] = 'Skip zero-weighted categories';
$string['categoryskipzeroweighted_help'] = '**Yes**  
&nbsp; grade categories with a weighting of zero will not be displayed in this block

**No**  
&nbsp; grade categories will be displayed in this block even if their weighting is zero';
$string['categorysuffixes'] = 'Grade category name suffixes';
$string['checkboxes'] = 'Checkboxes';
$string['completionattemptsexhausted_desc'] = 'Student must use all available quiz attempts';
$string['completionattemptsexhausted'] = 'Require all attempts';
$string['completioncompleted_desc'] = 'Student must have at least one completed attempt';
$string['completionpass_desc'] = 'Student must get a passing grade, as defined in the gradebook';
$string['completionscorerequired_desc'] = 'Student must get at least this minimum score:';
$string['completionstatusallscos_desc'] = 'Require all SCOs to return the required status.';
$string['completionstatusrequired_desc'] = 'Student must get at least one of each selected status:';
$string['completionsubmit'] = 'Require submission';
$string['completionview'] = 'Require view';
$string['conditioncmlabels'] = 'Include labels';
$string['conditioncmresources'] = 'Include resources';
$string['conditioncmungraded'] = 'Include ungraded activities';
$string['confirmapply'] = 'Are you sure you want to apply the selected settings? This action cannot be undone.';
$string['confirmcreategradecategories'] = 'Are you sure you want to create grade categories in the gradebook?';
$string['confirmdelete'] = 'Are you sure you want to delete the selected activities? This action cannot be undone.';
$string['confirmremovegradecategories'] = 'Are you sure you want to remove all empty grade categories from the gradebook?';
$string['confirmsortactivities'] = 'Are you sure you want to sort the activities on the course page?';
$string['confirmsortgradeitems'] = 'Are you sure you want to sort the items in the gradebook?';
$string['coursegrade'] = 'Course grade';
$string['coursegradeposition_help'] = 'xxx';
$string['coursenamefield'] = 'Name';
$string['coursenamefield_help'] = 'This setting defines the string to be used as the name for the course grade category in this block. Note that this setting has no effect if "Course grade category: Show" is set to "No", or the "Minimum category depth" is greater than zero.

**Short name**  
&nbsp; The course\'s short name will be used as the name for the course grade category in this block.

**Full name**  
&nbsp; The course\'s full name will be used as the name for the course grade category in this block.

**Grade**  
&nbsp; The "currentgrade" string, which is in the grades language pack, will be used as the name for the course grade category for this block.

**Your Grade**  
&nbsp; The "yourgrade" string, which is in the grades language pack, will be used as the name for the course grade category for this block.

**Course grade**  
&nbsp; The "coursegrade" string, which is in the language pack for this block, will be used as the name for the course grade category for this block.

**Current grade**  
&nbsp; The "currentgrade" string, which is in the language pack for this block, will be used as the name for the course grade category for this block.';
$string['coursepageshortcuts'] = 'Course page shortcuts';
$string['coursesections'] = 'Course sections';
$string['courseuploadlimit'] = 'Course upload limit';
$string['createdgradecategories'] = 'Grade book categories have been created';
$string['creategradecategories'] = 'Create grade categories';
$string['creategradecategories_help'] = 'Clicking the "Create grade categories" link will initiate creating of the grade categories in the gradebook, one for each section of the course.

Grade items for activities will be moved into their respective
categories.'; $string['currentgrade'] = 'Current grade';
$string['currentsection'] = 'Current section';
$string['currentsection_help'] = 'If this setting is set to "Yes", then
this block will display a list of course sections that allows a teacher
to select which section in the course is the current section.';
$string['dates'] = 'Dates';
$string['defaultsection'] = 'Default section';
$string['defaultsection_help'] = 'This setting is the default section of
the course to be displayed to users if the "Display only one section"
setting is "Yes"

If the "Display only one section" setting is "No", then this setting has no effect.';
$string['defaulttitle'] = 'TaskChain Navigation';
$string['editsettings'] = 'Link to edit settings';
$string['editsettings_help'] = '**Yes**  
&nbsp; a link directly to the settings page for this block will be displayed to teachers and administrators, even when the course page is not in edit mode.

**No**  
&nbsp; a link to the settings page for this block will not be displayed';
$string['endofsection'] = 'End of section';
$string['erasecompletion'] = 'Erase data';
$string['erasecompletion_help'] = 'All completion data on selected activities will be erased';
$string['exclude'] = 'Exclude';
$string['exportsettings'] = 'Export settings';
$string['exportsettings_help'] = 'This link allows you export the configuration settings for this block to a file that you can import into a similar block in another course.';
$string['externalcss'] = 'Custom stylesheet URL';
$string['externalcss_help'] = 'This setting, if specified, is the absolute URL of a stylesheet that is to be used when displaying this block';
$string['firstsection'] = 'First';
$string['gradebooklink'] = 'Link to grade book';
$string['gradebooklink_help'] = '**Yes**  
&nbsp; a link directly to the grade book will be displayed in this block

**No**  
&nbsp; a link to the grade book will not be displayed in this block';
$string['gradecategorynames'] = 'Grade category names';
$string['gradedisplay'] = 'Grade display';
$string['gradedisplay_help'] = 'xxx';
$string['gradeexplanation'] = 'Settings for individual student grades in the gradebook.';
$string['gradeitemhidden'] = 'Hide grade item';
$string['gradelinecolor'] = 'Grade line color';
$string['gradelinecolor_help'] = 'The color of the line running from the activity name to the grade. Any CSS color is acceptable, but do not include the leading hash-sign, \'#\', for RGB colors.';
$string['gradelinestyle'] = 'Grade line style';
$string['gradelinestyle_help'] = 'The style of the line running from the activity name to the grade.';
$string['gradelinewidth'] = 'Grade line width';
$string['gradelinewidth_help'] = 'The width of the line running from the activity name to the grade. Include the width number and the unit, e.g. 640px';
$string['groupscountusers'] = 'Count group members';
$string['groupscountusers_help'] = '**Yes**  
&nbsp; The group menu will show how many members are in each group.

**No**  
&nbsp; The group menu will show only the names of groups in this course.';
$string['groupslabel'] = 'Show group menu label';
$string['groupslabel_help'] = '**Yes**  
&nbsp; If the group menu is displayed, it will appear with a label saying "Group"

**No**  
&nbsp; The group menu label will not be displayed.';
$string['groupsmenu'] = 'Show group menu';
$string['groupsmenu_help'] = '**Yes**  
&nbsp; If averages are enabled on this block, the teacher can choose to show scores for students in a specific group in this course.

**No**  
&nbsp; No list of groups will be displayed to any user.A "teacher" is considered to be someone who has the "moodle/grade:viewall" capability in the course where this block is displayed.

If the course is using "separate groups" and the user is a non-editing teacher assigned to a specific group, he or she can only see groups to which he or she belongs.

If the course is not using groups, or it is using visible groups, or if the user is a teacher or other user who has the "moodle/site:accessallgroups" capability in this course, the full list of groups will be displayed.';
$string['groupssort'] = 'Sort groups menu';
$string['groupssort_help'] = '**Name**  
&nbsp; The list of groups will be sorted by group name.

**ID number**  
&nbsp; The list of groups will be sorted by group ID number.

**Date and time created**  
&nbsp; The list of groups will sorted by the date and time that each group was created.

**Date and time modified**  
&nbsp; The list of groups will sorted by the date and time that each group was last modified.';
$string['head'] = 'Head';
$string['hiddensections'] = 'Hidden sections';
$string['hiddensections_help'] = 'If this setting is set to "Yes", then this block will display a list of course sections that allows a teacher to select which sections are visible and which are hidden.

The other settings here specify whether the list is to be displayed as a series of checkboxes or a multi-select menu, and whether to use the section numbers or sections titles in the list. Long section titles may be truncated in order to fit in one line across the block.';
$string['hidelabels'] = 'Hide labels';
$string['highgrade'] = 'Minimum high grade';
$string['highgrade_help'] = 'The lower limit for grades that will be will be treated as a high grade and will be displayed with a green background.';
$string['ignore'] = 'Ignore';
$string['ignorecase'] = 'Ignore upper/lower case';
$string['ignorecase_help'] = 'When comparing the prefixes and suffixes of section titles, this setting specifies whether to ignore or detect differences between upper and lowercase letters.

**Yes**  
&nbsp; differences between upper and lowercase letters will be ignored

**No**  
&nbsp; differences between upper and lowercase letters will be detected';
$string['importsettings'] = 'Import settings';
$string['importsettings_help'] = 'This link takes you to a screen where you can import configuration settings from a TaskChain navigation block configuration settings file.

A settings file is created using the export link on a TaskChain navigation block configuration settings page.';
$string['include'] = 'Include';
$string['indent'] = 'Indent';
$string['internalcss'] = 'Custom internal CSS';
$string['internalcss_help'] = 'This setting specifies any additional custom CSS definitions that are to be used when displaying this block';
$string['invalidblockname'] = 'Invalid block name in block instance record: id={$a->id}, blockname={$a->blockname}';
$string['invalidcontextid'] = 'Invalid parentcontextid in block instance record: id = {$a->id}, parentcontextid = {$a->parentcontextid}';
$string['invalidcourseid'] = 'Invalid instanceid in course context record: id={$a->id}, instanceid={$a->instanceid}';
$string['invalidimportfile'] = 'Import file was missing, empty or invalid';
$string['invalidinstanceid'] = 'Invalid block instance id: id = {$a}';
$string['lastsection'] = 'Last';
$string['loginasmenu'] = 'Show login-as menu';
$string['loginasmenu_help'] = '**Yes**  
&nbsp; A list of student names will displayed, allowing teachers to easily select and login as any student in the course.

**No**  
&nbsp; The login-as menu will not be displayed.';
$string['loginassort'] = 'Sort login-as menu';
$string['loginassort_help'] = '**First name**  
&nbsp; The list of students will be sorted by first name.

**Surname**  
&nbsp; The list of students will be sorted by surname (i.e. last name).

**Username**  
&nbsp; The list of students will be sorted by Moodle username.

**ID number**  
&nbsp; The list of students will be sorted by ID number. Note, that this is the ID number as it appears on the user profile, not the "id" field in the user table of the Moodle database';
$string['long'] = 'Long';
$string['lowgrade'] = 'Minimum low grade';
$string['lowgrade_help'] = 'The lower limit for grades that will be will be treated as a low grade and will be displayed with a red background.';
$string['maximumdepth'] = 'Maximum depth';
$string['maximumdepth_help'] = 'This setting specifies the maximum grade category depth that will be displayed in this block.';
$string['maximumgrade'] = 'Maximum grade';
$string['mediumgrade'] = 'Minimum medium grade';
$string['mediumgrade_help'] = 'The lower limit for grades that will be will be treated as a medium grade and will be displayed with an orange background.';
$string['mergewithungradedsections'] = 'Merge with ungraded sections';
$string['minimumdepth'] = 'Minimum depth';
$string['minimumdepth_help'] = 'This setting specifies the minimum grade category depth that will be displayed in this block.

Note that if the "Course grade category: Show" setting is set to "Yes".
then the course grade category, whose depth is zero, will be shown regardless of the "Minimum depth" setting';
$string['moodlecss'] = 'Standard Moodle styles';
$string['moodlecss_help'] = 'This setting specifies which of the standard Moodle stylesheets should be used when displaying this block.

**None**  
&nbsp; no standard Moodle stylesheet will be used

**Gradebook setup (Moodle 3.0 and later)**  
&nbsp; the stylesheet for the "Gradebook setup" page in the gradebook for Moodle 3.0 and later will be used

**Categories and items (Moodle 2.8 and 2.9)**  
&nbsp; the stylesheet for the "Categories and items" page in the gradebook for Moodle 2.8 and 2.9 will be used

**Simple view (Moodle 2.7 and earlier)**  
&nbsp; the stylesheet for the "Simple view" in the gradebook for Moodle 2.7 and earlier will be used

**User report**  
&nbsp; the stylesheet for the "User report" in the Moodle grade book will be used';
$string['multiselect'] = 'Multi-select menu';
$string['mycourses'] = 'My courses';
$string['mycourses_help'] = 'On this list you can specify other courses to which you wish to copy this block\'s settings. The list only includes courses where you are a teacher and which already have a TaskChain navigation block.';
$string['namefield'] = 'Course category name';
$string['nextanycourse'] = 'Next activity, of any type, in this course';
$string['nextanysection'] = 'Next activity, of any type, in this section';
$string['nextsamecourse'] = 'Next activity, of the same type, in this course';
$string['nextsamesection'] = 'Next activity, of the same type, in this section';
$string['noactivitiesselected'] = 'Please select some activities.';
$string['noinstancesincourse'] = 'There are no instances of the Taskchain Navigation block in this course.';
$string['nosettingsselected'] = 'Please select some settings you wish to apply.';
$string['phpuploadlimit'] = 'PHP upload limit';
$string['pluginnameblocks'] = 'TaskChain Navigation blocks';
$string['pluginuploadlimits'] = 'Plugin upload limits';
$string['prefixchars'] = 'Prefix delimiters';
$string['prefixchars_help'] = 'If any characters are specified here, they will be used to detect the end of the prefix.

&nbsp; For a "short" prefix, the prefix ends at the **first** of these characters that is detected.
&nbsp; For a "long" prefix, the prefix ends at the **last** of these characters that is detected.';
$string['prefixkeep'] = 'Keep or remove prefix';
$string['prefixkeep_help'] = '**Remove**  
&nbsp; the prefix will be removed and the rest of the name or title will be kept

**Keep**  
&nbsp; the prefix will be kept and the rest of the name or title will be removed';
$string['prefixlength'] = 'Fixed prefix length';
$string['prefixlength_help'] = 'This setting specifies the number of characters in a fixed-length prefix.';
$string['prefixlong'] = 'Long or short prefix';
$string['prefixlong_help'] = '**Short**  
&nbsp; the shortest possible prefix will be used

**Long**  
&nbsp; the longest prefix will be used';
$string['previousanycourse'] = 'Previous activity, of any type, in this course';
$string['previousanysection'] = 'Previous activity, of any type, in this section';
$string['previoussamecourse'] = 'Previous activity, of the same type, in this course';
$string['previoussamesection'] = 'Previous activity, of the same type, in this section';
$string['promotetovisiblegradecategory'] = 'Promote to visible grade category';
$string['readonlyfrom'] = 'Read-only from';
$string['readonlyuntil'] = 'Read-only until';
$string['rebuildingcoursecache'] = 'Re-building course cache';
$string['recalculatingcoursegrades'] = 'Re-calculating course grades';
$string['regrade'] = 'Regrade';
$string['removecompletion'] = 'Remove settings';
$string['removecompletion_help'] = 'Previous completion settings on selected activities will be removed';
$string['removeconditions'] = 'Remove restrictions';
$string['removeconditions_help'] = 'Previous access restrictions on selected activities will be removed';
$string['removedgradecategories'] = 'Empty grade categories have been removed from the gradebook';
$string['removegradecategories'] = 'Remove empty grade categories';
$string['removegradecategories_help'] = 'Clicking the "Remove empty grade categories" link will initiate the removal of empty grade categories from the gradebook.';
$string['resettingfiltercache'] = 'Resetting filter cache';
$string['sameas'] = 'same as {$a}';
$string['save'] = 'Save';
$string['sectionignorechars'] = 'Ignore these characters';
$string['sectionignorechars_help'] = 'any characters specified here will be removed from the section titles';
$string['sectionjumpmenu'] = 'Display section jump menu';
$string['sectionjumpmenu_help'] = 'This setting specifies whether or not to display the menu allowing students to jump between sections on the course page.

**Yes**  
&nbsp; the jump will be displayed on the course page

**No**  
&nbsp; the jump menu on the course page will be hidden';
$string['sectionnumber'] = 'Section number';
$string['sectionnumbers'] = 'Display section numbers';
$string['sectionnumbers_help'] = 'This setting specifies whether or not to display section numbers on left hand side of each section displayed on the course page.

**Yes**  
&nbsp; the section numbers will be displayed on the course page

**No**  
&nbsp; the section numbers on the course page will be hiddenIf the course is using a format, such as the weekly course format, that does not show section numbers, then this setting will have no effect.';
$string['sectionnumbertext'] = 'Section number and text';
$string['sectionprefixes'] = 'Section title prefixes';
$string['sections'] = 'Sections';
$string['sectionshorttitles'] = 'Shorten section titles';
$string['sectionshorttitles_help'] = '**Yes**  
&nbsp; if the titles of the sections within a grade category share a common prefix with the name of the grade category, then the prefix will be removed from the titles of those sections

**No**  
&nbsp; the full title of all sections will always be displayed in this block';
$string['sectionshowburied'] = 'Buried sections';
$string['sectionshowburied_help'] = 'A "buried" section is one that contains only activities in grade categories that are below the maximum grade category to be displayed in this block. Ordinarily,
this block would not display links to such sections, but you can force the links to be displayed by promoting then to higher grade categories using this setting.

**Hide**  
&nbsp; buried sections will not be displayed in this block

**Promote to visible grade category**  
&nbsp; buried sections will be promoted to the nearest visible grade category';
$string['sectionshowhidden'] = 'Hidden sections';
$string['sectionshowhidden_help'] = '**Hide**  
&nbsp; hidden sections will not be displayed in this block

**Show (with link)**  
&nbsp; links to hidden sections will be displayed in this block

**Show (without link)**  
&nbsp; students viewing this block will see the names of hidden sections without a link to the section';
$string['sectionshowuncategorized'] = 'Uncategorized sections';
$string['sectionshowuncategorized_help'] = 'An "uncategorized" section is one that contains activities that are not in any of the main grade categories for this course.

**Show above main grade categories**  
&nbsp; links to uncategorized sections will be displayed above the first main grade category for this course

**Show below main grade categories**  
&nbsp; links to uncategorized sections will be displayed below the last main grade category for this course';
$string['sectionshowungraded'] = 'Ungraded sections';
$string['sectionshowungraded_help'] = 'A "ungraded" section is one that contains no activities that appear in the Moodle gradebook.
This includes empty sections, as well as sections that contain only a mixture of labels, resources and ungraded activities such as ungraded glossaries and forums. Ordinarily, this block would not display links to such sections, but you can force the links to be displayed using this setting.

**Hide**  
&nbsp; ungraded sections will not be displayed in this block

**Show if activities exist**  
&nbsp; links to ungraded sections that contain at least one activity will be displayed in this block

**Show if activities or resources exist**  
&nbsp; links to ungraded sections that contain at least one activity or resource will be displayed in this block

**Show if activities, resources or labels exist**  
&nbsp; links to ungraded sections that contain at least one activity, resource or label will be displayed in this block

**Show even if no activities, resources or labels exist**  
&nbsp; links to ungraded sections will always be shown even if they are empty';
$string['sectionshowzeroweighted'] = 'Zero-weighted sections';
$string['sectionshowzeroweighted_help'] = 'A "zero-weighted" section is one that contains only activities whose weighting is zero.

**Hide**  
&nbsp; links to zero-weighted sections will not be displayed

**Show**  
&nbsp; links to zero-weighted sections will be displayed

**Merge with ungraded sections**  
&nbsp; zero-weighted sections will be merged into the list of ungraded sections that appears under the main grade categories';
$string['sectionsuffixes'] = 'Section title suffixes';
$string['sectiontags'] = 'Section tags';
$string['sectiontext'] = 'Section text';
$string['sectiontextlength'] = 'Section text length';
$string['sectiontextlength_help'] = 'These settings specify how to display section texts that are too long to be displayed in a single line in the block.

If the length of the section text exceeds the "Total" number of characters specified here,
then the text will be reformatted as HEAD ... TAIL,
where HEAD is the number of characters from the beginning of the text,
and TAIL is the number of characters from the end of the text.

You can specify separate values for each of the languages used in this course.
Note that any setting having a value of zero will effectively be disabled.';
$string['sectiontitles'] = 'Section titles';
$string['sectiontitletags_help'] = 'In course sections that have no explicit name, the HTML tags listed here are used to extract a title from the section summary.

If no title is detected using h1-h6 tags or the tags specified here, then the first line of the section summary will be used as the section title.

If the section name and summary are empty, then the topic number or week dates will be used as the section title.';
$string['sectiontitletags'] = 'Section title HTML tags';
$string['selectallnone'] = 'Select';
$string['selectallnone_help'] = 'The checkboxes in this column allow you to select certain settings in this block and copy them to TaskChain navigation blocks in other Moodle courses on this site.

Settings can be selected individually, or you can use the "All" or
"None" links to select all or none of the settings with one click.

To select the courses to which you wish copy this block\'s settings,
use the course menu at the bottom of this block configuration page.

Note that you can only copy the settings to courses in which you are a teacher
(or administrator) and which already have a TaskChain navigation block.

To copy these settings to blocks in other Moodle sites, use the "export"
function on this page, and the "import" function of the block on the destination site.';
$string['selectsettings'] = 'Select';
$string['selectsettings_help'] = 'To select the activities to which you wish apply the settings, use the filters in the "Activity filters" section of this page.

The "+" and "-" icons in this column allow you to show or hide each section of this form.

When a section is visible, the checkboxes in this column allow you to select which settings are to be applied.';
$string['settingsmenu'] = 'Settings menu';
$string['settingsselected'] = 'The following settings were selected:';
$string['short'] = 'Short';
$string['showabovemaingradecategories'] = 'Show above main grade categories';
$string['showactivitygrades'] = 'Show activity grades';
$string['showactivitygrades_help'] = 'The grades of any activity types selected here will be displayed next to the activities that appear in the main content of the Moodle course page.';
$string['showaverages'] = 'Show averages to teachers';
$string['showaverages_help'] = '**Yes**  
&nbsp; on a teacher\'s course page, this block will display, for each grade category, the average grade for students in the class and the number of students who have attempted activities in the grade category.

**No**  
&nbsp; this block will always display only the grades of the current user, even if the current user is a teacherA "teacher" is considered to be someone who has the "moodle/grade:viewall"
capability in the course where this block is displayed.

This block is aware of groups, so if the course is using "separate groups"
and a teacher is a non-editing teacher assigned to a specific group, i.e. he or she can only see students from his or her own group, then the teacher will see the average grade only for students in the groups to which he or she belongs.

Administrators, and other users who have the "moodle/site:accessallgroups"
capability in this course, will see the average grade for all students enrolled in the course,
regardless of the group settings.';
$string['showbelowmaingradecategories'] = 'Show below main grade categories';
$string['showcourse'] = 'Show course category';
$string['showcourse_help'] = '**Yes**  
&nbsp; the course grade category will be displayed in this block, even if the minimum grade category depth is greater than zero.

**No**  
&nbsp; the course grade category will only be shown in this block if the minimum grade category depth is set to "None"';
$string['showgradedetails'] = 'Show grade details';
$string['showlabels'] = 'Show labels';
$string['showwithlink'] = 'Show (with link)';
$string['showwithoutlink'] = 'Show (without link)';
$string['singlesection'] = 'Display only one section';
$string['singlesection_help'] = '**Yes**  
&nbsp; users will be forced to view only one section of the course at a time

**No**  
&nbsp; users will be allowed to expand the course to view all sections if they want to';
$string['siteuploadlimit'] = 'Site upload limit';
$string['specificactivitytypes'] = 'Specific activity types';
$string['specifictext'] = 'Specific text';
$string['sortactivities'] = 'Sort activities';
$string['sortactivities_help'] = 'Clicking the "Sort activities" link will initiate sorting of activities on the course page so that they appear in the same order as they are in the grade book.

Note that the sorting does not change the order of course sections, nor does it move activities from one course section to another.

Items that do not appear in the grade book, such as labels and resources, will be moved to the top of their respective sections and sorted alphabetically.';
$string['sortedactivities'] = 'Course page activities have been sorted';
$string['sortedgradeitems'] = 'Grade book items have been sorted';
$string['sortgradeitems'] = 'Sort grade items';
$string['sortgradeitems_help'] = 'Clicking the "Sort grade items" link will initiate sorting of the grade book items so that activities appear in the same order as they are on the course page.

Note that the sorting does not change the order of grade categories, nor does it move activities from one grade category to another.';
$string['standardgrading'] = 'Standard grading';
$string['startofsection'] = 'Start of section';
$string['styles'] = 'CSS and styles';
$string['suffixchars'] = 'Suffix delimiters';
$string['suffixchars_help'] = 'If any characters are specified here, they will be used to detect the beginning of the suffix.

For a "short" suffix, the suffix starts at the **last** of these characters that is detected.
For a "long" suffix, the suffix starts at the **first** of these characters that is detected.';
$string['suffixkeep'] = 'Keep or remove suffix';
$string['suffixkeep_help'] = '**Remove**  
&nbsp; the suffix will be removed and the rest of the name or title will be kept

**Keep**  
&nbsp; the suffix will be kept and the rest of the name or title will be removed';
$string['suffixlength'] = 'Fixed suffix length';
$string['suffixlength_help'] = 'This setting specifies the number of characters in a fixed-length suffix.';
$string['suffixlong'] = 'Long or short suffix';
$string['suffixlong_help'] = '**Short**  
&nbsp; the shortest possible suffix will be used

**Long**  
&nbsp; the longest suffix will be used';
$string['tail'] = 'Tail';
$string['timecreated'] = 'Date and time created';
$string['timemodified'] = 'Date and time modified';
$string['title'] = 'Title';
$string['title_help'] = 'This is the string that will be displayed as the title of this block.
If this field is blank, no title will be displayed for this block.';
$string['total'] = 'Total';
$string['ungradedshow1'] = 'Show if activities exist';
$string['ungradedshow2'] = 'Show if activities or resources exist';
$string['ungradedshow3'] = 'Show if activities or resources or labels exist';
$string['ungradedshow4'] = 'Show even if no activities, resources or labels exist';
$string['usechildcategory'] = 'Yes - display child name';
$string['usedby'] = 'used by {$a}';
$string['usedbyall'] = 'used by ALL activities, resources and labels';
$string['useparentcategory'] = 'Yes - display parent name';
$string['validimportfile'] = 'Configuration settings were successfully imported';
$string['viewablefrom'] = 'Read-only until';
$string['viewableuntil'] = 'Read-only from';
$string['visibility'] = 'Visibility filter';
