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
 * blocks/taskchain_navigation/accesscontrol.php
 *
 * @package    blocks
 * @subpackage taskchain_navigation
 * @copyright  2014 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Include required files */
require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/taskchain_navigation/block_taskchain_navigation.php');
require_once($CFG->dirroot.'/rating/lib.php');

// ==================================
// advanced grading (Moodle >= 2.2)
// disabled until fully functional
// ==================================
//if (file_exists($CFG->dirroot.'/grade/grading/lib.php')) {
//    require_once($CFG->dirroot.'/grade/grading/lib.php');
//}

$id = required_param('id', PARAM_INT); // block_instance id
$plugin = 'block_taskchain_navigation';

if (! $block_instance = $DB->get_record('block_instances', array('id' => $id))) {
    print_error('invalidinstanceid', $plugin);
}

if (! $block = $DB->get_record('block', array('name' => $block_instance->blockname))) {
    print_error('invalidblockid', $plugin, $block_instance->blockid);
}

if (class_exists('context')) {
    $context = context::instance_by_id($block_instance->parentcontextid);
} else {
    $context = get_context_instance_by_id($block_instance->parentcontextid);
}

if (! $course = $DB->get_record('course', array('id' => $context->instanceid))) {
    print_error('invalidcourseid', $plugin, $block_instance->pageid);
}
$course->context = $context;

require_login($course->id);
require_capability('moodle/course:manageactivities', $context);
// moodle/course:activityvisibility
// moodle/course:manageactivities
// moodle/course:viewhiddenactivities

switch (true) {
    case optional_param('apply',  '', PARAM_ALPHA): $action = 'apply';  break;
    case optional_param('cancel', '', PARAM_ALPHA): $action = 'cancel'; break;
    case optional_param('delete', '', PARAM_ALPHA): $action = 'delete'; break;
    default: $action = '';
}

if ($action=='cancel') {
    $url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
    if (function_exists('sesskey')) {
        $url .= '&sesskey='.sesskey();
    }
    // return to course page
    redirect($url);
}

define('PREVIOUS_ANY_COURSE',   -1);
define('PREVIOUS_ANY_SECTION',  -2);
define('PREVIOUS_SAME_COURSE',  -3);
define('PREVIOUS_SAME_SECTION', -4);
define('NEXT_ANY_COURSE',       -5);
define('NEXT_ANY_SECTION',      -6);
define('NEXT_SAME_COURSE',      -7);
define('NEXT_SAME_SECTION',     -8);

$blockname = get_string('blockname', $plugin);
$pagetitle = get_string('accesscontrolsettings', $plugin);

// $SCRIPT is set by initialise_fullme() in 'lib/setuplib.php'
// It is the path below $CFG->wwwroot of this script
$url = new moodle_url($SCRIPT, array('id' => $id));

$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($pagetitle, $url);

require_head_js($plugin);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
echo $OUTPUT->box_start('generalbox');

if (data_submitted()) {
    // check Moodle session is valid ...
    if (function_exists('require_sesskey')) {
        require_sesskey();
    } else if (function_exists('confirm_sesskey')) {
        confirm_sesskey();
    }
}

// set up date menus
$years = array();
for ($i=1970; $i<=2050; $i++) {
    $years[$i] = $i;
}
$months = array();
for ($i=1; $i<=12; $i++) {
    $months[$i] = userdate(gmmktime(12,0,0, $i, 15, 2000), '%B');
}
$days = array();
for ($i=1; $i<=31; $i++) {
    $days[$i] = $i;
}
$hours = array();
for ($i=0; $i<=23; $i++) {
    $hours[$i] = sprintf('%02d', $i);
}
$minutes = array();
for ($i=0; $i<60; $i+=1) {
    $minutes[$i] = sprintf('%02d', $i);
}

// show the access control form
taskchain_navigation_accesscontrol_form($course, $block_instance, $action);

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);

/**
 * taskchain_navigation_accesscontrol_form
 *
 * @param object $course
 * @param object $block_instance
 * @param string $action
 * @return void, but send HTML form to browser
 */
function taskchain_navigation_accesscontrol_form($course, $block_instance, $action) {
    global $CFG, $DB, $OUTPUT, $PAGE;
    global $years, $months, $days, $hours, $minutes;

    // site and system contexts
    if (class_exists('context')) {
        $sitecontext = context_course::instance(SITEID);
        $systemcontext = context_system::instance();
    } else {
        $sitecontext = get_context_instance(CONTEXT_COURSE, SITEID);
        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    }
    $hassiteconfig = has_capability('moodle/site:config', $systemcontext);

    // decode block config settings, in case they are needed later
    $block_instance->config = unserialize(base64_decode($block_instance->configdata));

    // we need the DB manager to check which
    // DB tables and fields are available
    $dbman = $DB->get_manager();

    $plugin = 'block_taskchain_navigation';
    $select_size = 5;

    $cm_namelength = 40;
    $cm_headlength = 10;
    $cm_taillength = 10;

    $section_namelength = 48;
    $section_headlength = 18;
    $section_taillength = 18;

    // get previous or default form values
    $sections_array = optional_param_array('sections', array(), PARAM_INT);
    $modules_array  = optional_param_array('modules',  array(), PARAM_ALPHA);
    $cmids_array    = optional_param_array('cmids',    array(), PARAM_INT);
    $include        = optional_param('include', '', PARAM_TEXT);
    $exclude        = optional_param('exclude', '', PARAM_TEXT);
    $visibility     = optional_param('visibility', -1, PARAM_INT);

    // available from/until dates
    $time  = time();
    list($availablefrom, $availablefromdate, $availablefromdisable) = get_timestamp_and_date('availablefrom',  null, $time);
    list($availableuntil, $availableuntildate, $availableuntildisable) = get_timestamp_and_date('availableuntil', null, $time);
    list($viewableuntil, $viewableuntildate, $viewableuntildisable) = get_timestamp_and_date('viewableuntil', null, $time);
    list($viewablefrom, $viewablefromdate, $viewablefromdisable) = get_timestamp_and_date('viewablefrom', null, $time);
    list($cutoff, $cutoffdate, $cutoffdisable) = get_timestamp_and_date('cutoff', null, $time);
    list($gradingdue, $gradingduedate, $gradingduedisable) = get_timestamp_and_date('gradingdue', null, $time);

    $sortgradeitems   = optional_param('sortgradeitems',   0, PARAM_INT);
    $creategradecats  = optional_param('creategradecats',  0, PARAM_INT);
    $removegradecats  = optional_param('removegradecats',  0, PARAM_INT);
    $rating           = optional_param('rating',           0, PARAM_INT);
    $maxgrade         = optional_param('maxgrade',       100, PARAM_INT);
    $gradepass        = optional_param('gradepass',       60, PARAM_INT);
    $gradecat         = optional_param('gradecat',         0, PARAM_INT);
    $gradeitemhidden  = optional_param('gradeitemhidden',  0, PARAM_INT);
    $extracredit      = optional_param('extracredit',      0, PARAM_INT);
    $regrade          = optional_param('regrade',          0, PARAM_INT);

    $gradeoverridden  = optional_param('gradeoverridden',  0, PARAM_INT);
    $gradeexcluded    = optional_param('gradeexcluded',    0, PARAM_INT);
    $gradehidden      = optional_param('gradehidden',      0, PARAM_INT);
    $gradelocked      = optional_param('gradelocked',      0, PARAM_INT);

    list($gradehiddenuntil, $gradehiddenuntildate, $gradehiddenuntildisable) = get_timestamp_and_date('gradehiddenuntil', null, $time);
    list($gradelocktime, $gradelocktimedate, $gradelocktimedisable) = get_timestamp_and_date('gradelocktime', null, $time);

    $modgradetype     = optional_param('modgradetype',    '', PARAM_ALPHA);
    $modgradescale    = optional_param('modgradescale',    0, PARAM_INT);
    $modgradepoint    = optional_param('modgradepoint',  100, PARAM_INT);

    $groupmode        = optional_param('groupmode',        0, PARAM_INT);
    $groupingid       = optional_param('groupingid',       0, PARAM_INT);
    $groupmembersonly = optional_param('groupmembersonly', 0, PARAM_INT);

    $sortactivities   = optional_param('sortactivities',   0, PARAM_INT);
    $visible          = optional_param('visible',          1, PARAM_INT);
    $indent           = optional_param('indent',           0, PARAM_INT);
    $section          = optional_param('section',          0, PARAM_INT);
    $position         = optional_param('position',         0, PARAM_INT);

    $uploadlimit      = optional_param('uploadlimit',      0, PARAM_INT);
    $siteuploadlimit  = get_config(null, 'maxbytes');
    $courseuploadlimit = $course->maxbytes;
    $uploadlimitmenu  = get_max_upload_sizes($siteuploadlimit, $courseuploadlimit);

    $removeconditions = optional_param('removeconditions', 0, PARAM_INT);
    $removecompletion = optional_param('removecompletion', 0, PARAM_INT);
    $erasecompletion  = optional_param('erasecompletion',  0, PARAM_INT);

    // course_modules_availability OR course_modules.availability
    $conditiontypemust      = optional_param('conditiontypemust', '', PARAM_TEXT); // ''=must, '!'=must not
    $conditiontypejoin      = optional_param('conditiontypejoin', '&',  PARAM_TEXT); // '&'=AND, '|'=OR
    $conditiondatedirection = optional_param_array('conditiondatedirection', array(0),   PARAM_INT);
    $conditiongradeitemid   = optional_param_array('conditiongradeitemid',   array(0),   PARAM_INT);
    $conditiongrademin      = optional_param_array('conditiongrademin',      array(60),  PARAM_INT);
    $conditiongrademax      = optional_param_array('conditiongrademax',      array(100), PARAM_INT);
    $conditionfieldname     = optional_param_array('conditionfieldname',     array(''),  PARAM_ALPHANUM);
    $conditionfieldoperator = optional_param_array('conditionfieldoperator', array(''),  PARAM_ALPHANUM);
    $conditionfieldvalue    = optional_param_array('conditionfieldvalue',    array(''),  PARAM_ALPHANUM);
    $conditiongroupid       = optional_param_array('conditiongroupid',       array(0),   PARAM_INT);
    $conditiongroupingid    = optional_param_array('conditiongroupingid',    array(0),   PARAM_INT);
    $conditioncmid          = optional_param_array('conditioncmid',          array(0),   PARAM_INT); // may be negative NEXT/PREVIOUS_ANY_COURSE/SECTION
    $conditioncmungraded    = optional_param_array('conditioncmungraded',    array(0),   PARAM_INT); // 0=skip, 1=include ungraded activities
    $conditioncmresources   = optional_param_array('conditioncmresources',   array(0),   PARAM_INT); // 0=skip, 1=include resources
    $conditioncmlabels      = optional_param_array('conditioncmlabels',      array(0),   PARAM_INT); // 0=skip, 1=include labels
    $conditioncmcompletion  = optional_param_array('conditioncmcompletion',  array(1),   PARAM_INT); // 0=incomplete, 1=complete, 2=pass, 3=fail
    $conditionaction        = optional_param_array('conditionaction',        array(1),   PARAM_INT); // 0=hide, 1=show(greyed out)

    // course_modules.xxx
    $completiontracking = optional_param('completiontracking', 0, PARAM_INT);
    $completionday      = optional_param('completionday',      0, PARAM_INT);
    $completionmonth    = optional_param('completionmonth',    0, PARAM_INT);
    $completionyear     = optional_param('completionyear',     0, PARAM_INT);

    // there may also be a number of activity-specific completion fields
    // (e.g. the "completionpass" field used by the Quiz and TaskChain modules)

    // there may also be a number of fields to enable/disable filters
    // (e.g. "filterglossary", "filtermediaplugin")

    // modgrade
    $modgrade = 0;
    switch ($modgradetype) {
        case 'point': $modgrade += $modgradepoint; break;
        case 'scale': $modgrade -= $modgradescale; break;
    }

    // Competency settings
    $competencyrule = optional_param('competencyrule', 0, PARAM_INT);

    $conditiontype = (object)array(
        'must' => $conditiontypemust,
        'join' => $conditiontypejoin
    );

    $conditiondate = array();
    $conditiondatetime = array();
    foreach ($conditiondatedirection as $i => $d) {
        switch ($d) {
            case 1: $d = '>='; break;
            case 2: $d = '<='; break;
            default: $d = '';
        }
        list($t, $date, $disable) = get_timestamp_and_date('conditiondatetime', $i, $time);
        $conditiondate[$i] = (object)array(
            'type' => 'date',
            'd' => $d, // direction
            't' => $t  // timestamp
        );
        $conditiondatetime[$i] = $date;
    }

    $conditiongrade = array();
    foreach ($conditiongradeitemid as $i => $id) {
        if ($id==0) {
            continue;
        }
        $conditiongrade[] = (object)array(
            'type' => 'grade',
            'id'   => $id,
            'min'  => (empty($conditiongrademin[$i]) ?   0 : $conditiongrademin[$i]),
            'max'  => (empty($conditiongrademax[$i]) ? 100 : $conditiongrademax[$i]),
        );
    }

    $conditionfield = array();
    foreach ($conditionfieldname as $i => $name) {
        if ($name=='') {
            continue;
        }
        $conditionfield[] = (object)array(
            'type' => 'profile',
            'sf'   => $name,
            'op'   => (empty($conditionfieldoperator[$i]) ? '' : $conditionfieldoperator[$i]),
            'v'    => (empty($conditionfieldvalue[$i]) ?    '' : $conditionfieldvalue[$i]),
        );
    }

    $conditiongroup = array();
    foreach ($conditiongroupid as $i => $id) {
        if ($id==0) {
            continue;
        }
        $conditiongroup[] = (object)array(
            'type' => 'group',
            'id'   => $id,
        );
    }

    $conditiongrouping = array();
    foreach ($conditiongroupingid as $i => $id) {
        if ($id==0) {
            continue;
        }
        $conditiongrouping[] = (object)array(
            'type' => 'grouping',
            'id'   => $id,
        );
    }

    $conditioncm = array();
    foreach ($conditioncmid as $i => $id) {
        if ($id==0) {
            continue;
        }
        $conditioncm[] = (object)array(
            'type'      => 'completion',
            'cm'        => $id,
            'e'         => (isset($conditioncmcompletion[$i]) ? $conditioncmcompletion[$i] : 1),
            'ungraded'  => (empty($conditioncmungraded[$i])   ? 0 : 1), // will be removed later
            'resources' => (empty($conditioncmresources[$i])  ? 0 : 1), // will be removed later
            'labels'    => (empty($conditioncmlabels[$i])     ? 0 : 1)  // will be removed later
        );
    }

    if ($completionday && $completionmonth && $completionyear) {
        $completiondate = make_timestamp($completionyear,
                                         $completionmonth,
                                         $completionday,
                                         0, 0, 0, 99, false);
    } else {
        $completiondate = 0;
    }

    // add standard settings
    $settings = array('availablefrom',   'availableuntil',
                      'viewableuntil',   'viewablefrom',
                      'cutoff',          'gradingdue',
                      'rating',          'modgrade',
                      'maxgrade',        'gradepass',      'gradecat',
                      'gradeitemhidden', 'extracredit',    'regrade',
                      'gradeoverridden', 'gradehidden',    'gradehiddenuntil',
                      'gradeexcluded',   'gradelocked',    'gradelocktime',
                      'groupmode',       'groupingid',     'groupmembersonly',
                      'visible',         'indent',         'section',
                      'uploadlimit');

    // add switches to enable/disable filters
    $filters = filter_get_available_in_context($course->context);
    foreach (array_keys($filters) as $filter) {
        $setting = 'filter'.$filter;
        $settings[] = $setting;
        $$setting = optional_param($setting, null, PARAM_INT);
    }

    // add "availability" settings, if enabled
    if (empty($CFG->enableavailability)) {
        $enableavailability = false;
    } else {
        $enableavailability = true;
    }
    if ($enableavailability) {
        array_push($settings, 'removeconditions',  'conditiontype',  'conditiondate',
                              'conditiongrade',    'conditionfield', 'conditiongroup',
                              'conditiongrouping', 'conditioncm',    'conditionaction');
    }

    // add "completion" settings, if enabled
    if (empty($CFG->enablecompletion) || empty($course->enablecompletion)) {
        $enablecompletion = false;
    } else {
        $enablecompletion = true;
    }
    if ($enablecompletion) {
        array_push($settings, 'removecompletion', 'erasecompletion', 'completiontracking', 'completiondate');
    }

    // are we using competencies
    // (available in Moodle >= 3.1)
    if (get_config('core_competency', 'enabled')) {
        $enablecompetency = true;
    } else {
        $enablecompetency = false;
    }
    if ($enablecompetency) {
        array_push($settings, 'competencyrule');
    }

    // custom html tags that delimit section title in the section summary
    if (empty($block_instance->config->sectiontitletags)) {
        $sectiontags = '';
    } else {
        $sectiontags = $block_instance->config->sectiontitletags;
    }
    $sectiontags = optional_param('sectiontags', $sectiontags, PARAM_TEXT);

    // set course section type
    if ($course->format=='weeks') {
        $sectiontype = 'week';
    } else if ($course->format=='topics') {
        $sectiontype = 'topic';
    } else {
        $sectiontype = 'section';
    }

    if (count($sections_array) || count($modules_array) || $include || $exclude || $visibility>=0 || count($cmids_array)) {
        $select_defaultvalue = true;
    } else {
        $select_defaultvalue = false;
    }

    // set date format for course sections
    $weekdateformat = '%b %d'; // get_string('strftimedateshort');

    if ($sortactivities) {

        $select = 'cm.id, gi.sortorder';
        $from   = '{grade_items} gi '.
                  'JOIN {modules} m ON gi.itemmodule = m.name '.
                  'JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = gi.iteminstance';
        $where  = 'gi.courseid = ? AND gi.itemtype = ?';
        $order  = 'gi.sortorder';
        $params = array($course->id, 'mod');
        $items = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params);

        $select = 'id,sequence,section,summary';
        $from   = '{course_sections}';
        $where  = 'course = ? AND sequence IS NOT NULL AND sequence <> ?';
        $order  = 'section';
        $params = array($course->id, '');
        $sections = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params);

        if ($items && $sections) {

            $modinfo = get_fast_modinfo($course);
            $rebuild_course_cache = false;

            // create a copy of the cms array in order to prevent
            // the following PHP Notice when trying unset($modinfo->cms[$cmid])
            // "Indirect modification of overloaded property course_modinfo::$cms has no effect"
            $cms = $modinfo->cms;

            foreach (array_keys($sections) as $id) {
                $sequence = explode(',', $sections[$id]->sequence);
                $sequence = array_flip($sequence);
                foreach (array_keys($sequence) as $cmid) {
                    if (array_key_exists($cmid, $items)) {
                        // assign new sortorder to activity
                        $sequence[$cmid] = $items[$cmid]->sortorder;
                    } else if (isset($cms[$cmid])) {
                        // not a grade book item (e.g. label)
                        $name = urldecode($cms[$cmid]->name);
                        $name = block_taskchain_navigation::filter_text($name);
                        $name = trim(strip_tags($name));
                        $sequence[$cmid] = $name;
                        unset($cms[$cmid]);
                    } else {
                        unset($sequence[$cmid]); // shouldn't happen !!
                    }
                }

                uasort($sequence, 'activity_sequence_uasort');

                $sequence = array_keys($sequence);
                $sequence = implode(',', $sequence);
                if ($sequence != $sections[$id]->sequence) {
                    $DB->set_field('course_sections', 'sequence', $sequence, array('id' => $id));
                    $rebuild_course_cache = true;
                }
            }
            if ($rebuild_course_cache) {
                rebuild_course_cache($course->id);
                if (class_exists('course_modinfo')) {
                    // Moodle >= 2.4
                    get_fast_modinfo($course, 0, true);
                } else {
                    // Moodle <= 2.3
                    get_fast_modinfo('reset');
                }
                $course = $DB->get_record('course', array('id' => $course->id));
            }
        }
        unset($items, $sections, $modinfo, $sequence, $name, $cms, $cmid, $id);
    }

    $cms      = array();
    $modules  = array();
    $sections = array();

    $availablemods  = array();
    $activitymods   = array();
    $cutoffmods     = array();
    $gradingduemods = array();
    $filemods       = array();
    $gradingareas   = array();
    $gradingmods    = array();
    $labelmods      = array();
    $ratingmods     = array();
    $resourcemods   = array();
    $viewablemods   = array();

    $completionfields = array();
    $durationfields = array('completiontimespent');

    $count_cmids = 0;
    $selected_cmids = array();

    $strman = get_string_manager();

    // cache of section visibility by sectionnum
    // Note: could be ommited if we are not bothered about visibility
    // if ($visibility>=0 || array_key_exists('visible', $selected_settings)) {
    // }
    $section_visible = $DB->get_records_menu('course_sections', array('course' => $course->id), 'section', 'section, visible');
    $section_visible = array_map('intval', $section_visible);
    $section_visible[0] = 1; // intro is always visible

    // determine to use when generating image URLs
    if (method_exists($PAGE->theme, 'image_url')) {
        $image_url = 'image_url'; // Moodle >= 3.3
    } else {
        $image_url = 'pix_url'; // Moodle <= 3.2
    }

    $modinfo = get_fast_modinfo($course);
    foreach ($modinfo->sections as $sectionnum => $cmids) {

        // loop through the course modules
        foreach ($cmids as $cmid) {

            if (empty($modinfo->cms[$cmid])) {
                continue; // shouldn't happen
            }

            // shortcut to current course modules
            $cm = $modinfo->cms[$cmid];

            $count_cmids++;

            $sections[$sectionnum] = true;

            if (empty($modules[$cm->modname])) {
                $modules[$cm->modname] = get_string('modulename', $cm->modname);

                // check use of standard grading, cut-off date, read-only dates
                $filepath = "$CFG->dirroot/mod/$cm->modname/mod_form.php";
                if (file_exists($filepath)) {
                    $filecontents = file_get_contents($filepath);
                    $has_available = preg_match("/'(accesscontrol|availability|timing)'/", $filecontents);
                    $has_viewable = preg_match('/timeview(from|to)/', $filecontents);
                    $has_cutoff = preg_match('/cutoffdate/', $filecontents);
                    $has_gradingdue = preg_match('/gradingduedate/', $filecontents);
                    $has_standardgrading = preg_match('/standard_grading_coursemodule_elements/', $filecontents);
                } else {
                    $has_available = in_array($cm->modname, array('assign', 'choice', 'data', 'feedback', 'forum', 'hotpot', 'lesson', 'quiz', 'reader', 'scorm', 'workshop'));
                    $has_viewable = in_array($cm->modname, array('data'));
                    $has_cutoff = in_array($cm->modname, array('assign'));
                    $has_gradingdue = in_array($cm->modname, array('assign'));
                    $has_standardgrading = in_array($cm->modname, array('assign', 'data', 'forum', 'glossary', 'lesson', 'lti', 'quiz'));
                }
                // The EnglishCentral module defines its form elements in "classes/utils.php".
                if ($cm->modname=='englishcentral') {
                    $has_available = true;
                    $has_viewable = true;
                }

                // The Quiz module calls "standard_grading_coursemodule_elements()"
                // but then immediately removes the "grade" field - not sure why ?!
                if ($cm->modname=='quiz') {
                    $has_standardgrading = false;
                }

                if ($has_available) {
                    $availablemods[$cm->modname] = $modules[$cm->modname];
                }
                if ($has_viewable) {
                    $viewablemods[$cm->modname] = $modules[$cm->modname];
                }
                if ($has_cutoff) {
                    $cutoffmods[$cm->modname] = $modules[$cm->modname];
                }
                if ($has_gradingdue) {
                    $gradingduemods[$cm->modname] = $modules[$cm->modname];
                }
                if ($has_standardgrading) {
                    $gradingmods[$cm->modname] = $modules[$cm->modname];
                }

                $filepath = "$CFG->dirroot/mod/$cm->modname/lib.php";
                if ($has_libfile = file_exists($filepath)) {
                    $has_completion = plugin_supports('mod', $cm->modname, FEATURE_COMPLETION_HAS_RULES, false);
                } else {
                    $has_completion = false;
                }

                // get completion fields
                if ($enablecompletion) {
                    if ($has_completion) {
                        $fields = $DB->get_columns($cm->modname);
                        $names = array_keys($fields);
                        $names = preg_grep('/^completion.+$/', $names);
                        $names = array_values($names); // re-index the array
                    } else {
                        $names = array(); // no module-specific fields
                        $fields = array();
                    }
                    if ($has_libfile) {
                        // fields that are common to all modules - see "lib/moodleform_mod.php"
                        if (plugin_supports('mod', $cm->modname, FEATURE_GRADE_HAS_GRADE, false)) {
                            array_unshift($names, 'completiongrade');
                        }
                        if (plugin_supports('mod', $cm->modname, FEATURE_COMPLETION_TRACKS_VIEWS, false)) {
                            array_unshift($names, 'completionview');
                        }
                    }
                    foreach ($names as $name) {
                        if (empty($completionfields[$name])) {
                            $settings[] = $name;
                            if (isset($_POST[$name]) && is_array($_POST[$name])) {
                                $$name = optional_param_array($name, array(), PARAM_INT);
                                $$name = array_sum($$name); // i.e. same as logical AND
                            } else {
                                $$name = optional_param($name, 0, PARAM_INT);
                            }
                            if (in_array($name, $durationfields)) {
                                $$name *= optional_param($name.'_unit', 1, PARAM_INT);
                            }
                            $completionfields[$name] = get_completionfield($strman, $plugin, $cm->modname, $name, $$name, $fields);
                        }
                        $completionfields[$name]->mods[$cm->modname] = $modules[$cm->modname];
                    }
                    unset($fields, $names, $name);
                }

                // get file sitewide upload limits, if any, for this module
                switch ($cm->modname) {
                    case 'assign'     : $filemods[$cm->modname] = get_config('assignsubmission_file', 'maxbytes'); break;
                    case 'assignment' : $filemods[$cm->modname] = get_config(null, 'assignment_maxbytes'); break;
                    case 'forum'      : $filemods[$cm->modname] = get_config(null, 'forum_maxbytes'); break;
                    case 'workshop'   : $filemods[$cm->modname] = get_config('workshop', 'maxbytes'); break;
                }

                if ($has_libfile) {
                    $is_label    = (plugin_supports('mod', $cm->modname, FEATURE_NO_VIEW_LINK, false)==true);
                    $is_resource = (plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER)==MOD_ARCHETYPE_RESOURCE);
                    $has_rating  = (plugin_supports('mod', $cm->modname, FEATURE_RATE, false)==true);
                } else {
                    $is_label    = in_array($cm->modname, array('label'));
                    $is_resource = in_array($cm->modname, array('book', 'folder', 'imscp', 'page', 'resource', 'url'));
                    $has_rating  = in_array($cm->modname, array('data', 'forum', 'glossary'));
                }
                if ($has_grading = defined('FEATURE_ADVANCED_GRADING')) {
                    // Moodle >= 2.2
                    if ($has_libfile) {
                        $has_grading = (plugin_supports('mod', $cm->modname, FEATURE_ADVANCED_GRADING, false)==true);
                    } else {
                        $has_grading = in_array($cm->modname, array('assign'));
                    }
                }
                if ($is_label) {
                    $labelmods[] = $cm->modname;
                } else if ($is_resource) {
                    $resourcemods[] = $cm->modname;
                } else {
                    $activitymods[] = $cm->modname;
                }
                if ($has_rating) {
                    $ratingmods[$cm->modname] = $modules[$cm->modname];
                }
                // ==================================
                // disabled until fully functional
                // ==================================
                //if ($has_grading && class_exists('grading_manager')) {
                //    $gradingareas[$cm->modname] = grading_manager::available_areas('mod_'.$cm->modname);
                //    if (empty($gradingareas[$cm->modname])) {
                //        unset($gradingareas[$cm->modname]);
                //    }
                //}
                unset($filepath, $filecontents, $has_available, $has_completion,
                      $has_cutoff, $has_grading, $has_gradingdue,
                      $has_libfile, $has_rating, $has_standardgrading,
                      $has_viewable, $is_label, $is_resource);
            }

            if (empty($cms[$sectionnum])) {
                $cms[$sectionnum] = array();
            }

            if (in_array($cmid, $cmids_array)) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }

            $url = $PAGE->theme->$image_url('icon', $cm->modname)->out();
            $style = ' style="background-image: url('.$url.'); '.
                             'background-repeat: no-repeat; '.
                             'background-position: 1px 2px; '.
                             'background-size: 1em; '. // 16px
                             'min-height: 20px; '.
                             'padding-left: 12px;"';
            $name = urldecode($cm->name);
            $name = block_taskchain_navigation::filter_text($name);
            $name = trim(strip_tags($name));
            $name = block_taskchain_navigation::trim_text($name, $cm_namelength, $cm_headlength, $cm_taillength);

            $cms[$sectionnum][] = '<option value="'.$cm->id.'"'.$selected.$style.'>'.$name.'</option>';

            $select = $select_defaultvalue;
            if ($select && count($sections_array)) {
                $select = in_array($cm->sectionnum, $sections_array);
            }
            if ($select && count($modules_array)) {
                $select = in_array($cm->modname, $modules_array);
            }
            if ($select && $include) {
                $select = preg_match('/'.$include.'/', $cm->name);
            }
            if ($select && $exclude) {
                $select = (! preg_match('/'.$exclude.'/', $cm->name));
            }
            if ($select && $visibility>=0) {
                if ($section_visible[$cm->sectionnum]) {
                    $select = ($visibility==$cm->visible);
                } else {
                    // in a hidden section, we need to check the activity module's "visibleold" setting
                    $select = ($visibility==$DB->get_field('course_modules', 'visibleold', array('id' => $cm->id)));
                }
            }
            if ($select && count($cmids_array)) {
                $select = in_array($cm->id, $cmids_array);
            }
            if ($select) {
                $selected_cmids[$cm->id] = $cm;
            }
        }
    }

    // Calculate default start/end times by finding the
    // best (= most common) times in the current $course.
    if (empty($_POST)) {

        // Cache the list of time fields in modules.
        $fields = get_mod_fields();

        if (count($availablemods)) {
            set_default_time($fields, $availablemods, $availablefrom, $availablefromdate, 'availablefrom', $course->id);
            set_default_time($fields, $availablemods, $availableuntil, $availableuntildate, 'availableuntil', $course->id);
        }
        if (count($viewablemods)) {
            set_default_time($fields, $viewablemods, $viewablefrom, $viewablefromdate, 'viewablefrom', $course->id);
            set_default_time($fields, $viewablemods, $viewableuntil, $viewableuntildate, 'viewableuntil', $course->id);
        }
        if (count($cutoffmods)) {
            set_default_time($fields, $cutoffmods, $cutoff, $cutoffdate, 'cutoff', $course->id);
        }
        if (count($gradingmods)) {
            set_default_time($fields, $gradingmods, $gradingdue, $gradingduedate, 'gradingdue', $course->id);
        }
    }

    // $completionfields now contains additional "completion"
    // fields used by activity modules on this Moodle site
    // these fields have also been added to $settings

    // Sort the list of mods that use each completion field.
    foreach ($completionfields as $name => $field) {
        asort($field->mods);
        $completionfields[$name] = $field;
    }

    $selected_settings = array();
    if ($action=='apply') {
        foreach ($settings as $setting) {
            $select = 'select_'.$setting;
            if (optional_param($select, 0, PARAM_INT)) {
                $selected_settings[] = $setting;
            }
        }
    }

    $sectionmenu = array(-1 => get_string('currentsection', $plugin),
                        '-' => '-----'); // separator
    $sectionnums = array_keys($sections);

    if ($sections = $DB->get_records('course_sections', array('course' => $course->id), 'section', 'section,name,summary')) {
        foreach ($sections as $sectionnum => $sectioninfo) {

            // extract section title from section name or summary
            if ($text = block_taskchain_navigation::filter_text($sectioninfo->name)) {
                $text = block_taskchain_navigation::trim_text($text, $section_namelength, $section_headlength, $section_taillength);
            } else if ($text = block_taskchain_navigation::filter_text($sectioninfo->summary)) {
                // remove script and style blocks
                $select = '/\s*<(script|style)[^>]*>.*?<\/\1>\s*/is';
                $text = preg_replace($select, '', $text);

                if ($tags = $sectiontags) {
                    $tags = preg_split('/[^a-zA-Z0-9]+/', $tags);
                    $tags = array_map('trim', $tags);
                    $tags = array_filter($tags);
                    $tags = implode('|', $tags);
                    if ($tags) {
                        $tags .= '|';
                    }
                }
                $tags .= 'h1|h2|h3|h4|h5|h6';
                if (preg_match('/<('.$tags.')\b[^>]*>(.*?)<\/\1>/is', $text, $matches)) {
                    $text = $matches[2];
                } else {
                    // otherwise, get first line of text
                    $text = preg_split('/<br[^>]*>/', $text);
                    $text = array_map('strip_tags', $text);
                    $text = array_map('trim', $text);
                    $text = array_filter($text);
                    if (empty($text)) {
                        $text = '';
                    } else {
                        $text = reset($text);
                    }
                }
                $text = trim(strip_tags($text));
                $text = block_taskchain_navigation::trim_text($text, $section_namelength, $section_taillength, $section_taillength);
            }

            // set default section title, if necessary
            if ($text=='') {
                $format = 'format_'.$course->format;
                switch (true) {
                    case ($sectiontype=='week' && $sectionnum > 0):
                        $date = $course->startdate + 7200 + (($sectionnum - 1) * 604800);
                        $text = userdate($date, $weekdateformat).' - '.userdate($date + 518400, $weekdateformat);
                        break;

                    case $strman->string_exists('section'.$sectionnum.'name', $format):
                        $text = get_string('section'.$sectionnum.'name', $format);
                        break;

                    case $strman->string_exists('sectionname', $format):
                        $text = get_string('sectionname', $format).' '.$sectionnum;
                        break;

                    case $strman->string_exists($sectiontype, 'moodle'):
                        $text = get_string('sectionname').' '.$sectionnum;
                        break;

                    default:
                        $text = $sectiontype.' '.$sectionnum;
                }
            }

            // assign section title
            if (in_array($sectionnum, $sectionnums)) {
                $sections[$sectionnum] = $text;
            } else {
                unset($sections[$sectionnum]);
            }
            $sectionmenu[$sectionnum] = $text;
        }
    }

    foreach ($cms as $sectionnum => $options) {
        unset($cms[$sectionnum]);
        $text = $sections[$sectionnum];
        $cms[$text] = '<optgroup label="'.$text.'">'."\n".implode("\n", $options).'</optgroup>';
    }

    $cms = implode("\n", $cms);
    $cms = '<select id="id_cmids" name="cmids[]" size="'.min($select_size, $count_cmids).'" multiple="multiple">'."\n".$cms."\n".'</select>'."\n";

    foreach ($sections as $sectionnum => $text) {
        if (in_array($sectionnum, $sections_array)) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        $sections[$sectionnum] = '<option value="'.$sectionnum.'"'.$selected.'>'.$text.'</option>';
    }

    $count_sections = count($sections);
    $sections = implode("\n", $sections);
    $sections = '<select id="id_sections" name="sections[]" size="'.min($select_size, $count_sections).'" multiple="multiple">'."\n".$sections."\n".'</select>'."\n";

    asort($modules);
    foreach ($modules as $module => $text) {
        if (in_array($module, $modules_array)) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        if (empty($CFG->modpixpath)) {
            $style= ''; // shouldn't happen
        } else {
            $url = $CFG->modpixpath.'/'.$module.'/icon.gif';
            $style = ' style="background-image: url('.$url.'); background-repeat: no-repeat; background-position: 1px 2px; min-height: 20px; padding-left: 20px;"';
        }
        $modules[$module] = '<option value="'.$module.'"'.$selected.$style.'>'.$text.'</option>';
    }

    $count_modules = count($modules);
    $modules = implode("\n", $modules);
    $modules = '<select id="id_modules" name="modules[]" size="'.min($select_size, $count_modules).'" multiple="multiple">'."\n".$modules."\n".'</select>'."\n";

    $visibilitymenu = array(
       -1  => '',
        0  => get_string('hidden', 'grades'),
        1  => get_string('visible'),
    );

    if ($strman->string_exists('showoncoursepage', 'moodle')) {
        // Moodle >= 3.3
        $visiblemenu = array(
            0 => get_string('hidefromstudents'),
            1 => get_string('showoncoursepage'),
        );
        if ($allowstealth = $CFG->allowstealth) {
            $visiblemenu[-1] = get_string('hideoncoursepage');
        }
    } else {
        // Moodle >= 3.2
        $visiblemenu = array(
            0 => get_string('hide'),
            1 => get_string('show'),
        );
        $allowstealth = null;
    }

    $ratings = new rating_manager();
    $ratings = $ratings->get_aggregate_types();

    if ($strman->string_exists('modgradetypenone', 'grades')) {
        // Moodle >= 2.7
        $modgradetypes = array('none'  => get_string('modgradetypenone',  'grades'),
                               'scale' => get_string('modgradetypescale', 'grades'),
                               'point' => get_string('modgradetypepoint', 'grades'));
    } else {
        // Moodle <= 2.6
        $modgradetypes = array('none'  => get_string('typenone',  'grades'),
                               'scale' => get_string('typescale', 'grades'),
                               'point' => get_string('points',    'grades'));
    }
    $modgradescales = get_scales_menu($course->id);


    $maxgrades = array();
    $gradepassmenu = array();
    for ($i=100; $i>=1; $i--) {
        $maxgrades[$i] = $i.'%';
        $gradepassmenu[$i] = $i.'%';
    }
    $maxgrades[0] = get_string('nograde');

    $gradecategories = grade_get_categories_menu($course->id);

    $groupmodes = array(
        NOGROUPS       => get_string('groupsnone'),
        SEPARATEGROUPS => get_string('groupsseparate'),
        VISIBLEGROUPS  => get_string('groupsvisible')
    );

    //groupings selector - used for normal grouping mode or also when restricting access with groupmembersonly
    $groupings = array();
    if ($records = $DB->get_records('groupings', array('courseid' => $course->id))) {
        $groupings = array(0 => get_string('none'));
        foreach ($records as $record) {
            $groupings[$record->id] = format_string($record->name);
        }
    }

    $indentmenu = array();
    for ($i=-5; $i<=5; $i++) {
        if ($i==0) {
            $indentmenu[$i] = get_string('reset');
        } else {
            $indentmenu[$i] = ($i<0 ? '-' : '+').abs($i);
        }
    }

    $positionmenu = array(
        1 => get_string('startofsection', $plugin),
        2 => get_string('endofsection',   $plugin)
    );

    if ($strman->string_exists('direction_from', 'availability_date')) {
        // Moodle >= 2.7
        $conditiondatedirectionmenu = array(
            1 => get_string('direction_from', 'availability_date'),
            2 => get_string('direction_until', 'availability_date')
        );
    } else {
        // Moodle >= 2.6
        $conditiondatedirectionmenu = array(
            1 => get_string('from'),
            2 => get_string('durationuntil', 'calendar')
        );
    }

    $conditiontypemustmenu      = array();
    $conditiontypejoinmenu      = array();
    $conditiongradeitemidmenu   = array();
    $conditioncmidmenu          = array();
    $conditionfieldnamemenu     = array();
    $conditionfieldoperatormenu = array();
    $conditiongroupidmenu       = array();
    $conditiongroupingidmenu    = array();
    $conditionactionmenu        = array();
    $conditioncmcompletionmenu  = array();

    if ($enableavailability) {
        $basemenuitems = array(
            '0' => get_string('none', 'moodle'),
            '00' => '=====',
            PREVIOUS_ANY_COURSE   => get_string('previousanycourse',   $plugin),
            PREVIOUS_ANY_SECTION  => get_string('previousanysection',  $plugin),
            PREVIOUS_SAME_COURSE  => get_string('previoussamecourse',  $plugin),
            PREVIOUS_SAME_SECTION => get_string('previoussamesection', $plugin),
            '000' => '=====',
            NEXT_ANY_COURSE       => get_string('nextanycourse',       $plugin),
            NEXT_ANY_SECTION      => get_string('nextanysection',      $plugin),
            NEXT_SAME_COURSE      => get_string('nextsamecourse',      $plugin),
            NEXT_SAME_SECTION     => get_string('nextsamesection',     $plugin),
            '0000' => '====='
        );

        $items = grade_item::fetch_all(array('courseid' => $course->id));
        $categories = grade_category::fetch_all(array('courseid' => $course->id));

        if ($items) {
            uasort($items, 'grade_items_uasort');

            // http://en.wikipedia.org/wiki/Box-drawing_character
            $chars = (object)array(
                'branch' => block_taskchain_navigation::textlib('entities_to_utf8', '&#x251C;').' ', // ├
                'trunk'  => block_taskchain_navigation::textlib('entities_to_utf8', '&#x2502;').' ', // │
                'end'    => block_taskchain_navigation::textlib('entities_to_utf8', '&#x2514;').' ', // └
                'space'  => block_taskchain_navigation::textlib('entities_to_utf8', '&#xA0;').' '.
                            block_taskchain_navigation::textlib('entities_to_utf8', '&#xA0;').' ',
                'error'  => 'x ' // shouldn't be required !!
            );

            $depth = 0;
            $ids = array_keys($items);
            foreach($ids as $i => $id) {
                $item = $items[$id];

                if ($depth==0 || $item->is_course_item()) {
                    $depth = 1;
                    $spacer = '';
                    $spacers = array('');
                } else if ($item->is_category_item()) {
                    if (array_key_exists($item->iteminstance, $categories)) {
                        $depth = $categories[$item->iteminstance]->depth;
                    } else {
                        $depth = 2; // shouldn't happen !!
                    }
                    $spacers = array_slice($spacers, 0, $depth - 1);
                    $spacer = implode('', $spacers);
                }

                if ($item->is_category_item() && array_key_exists($item->iteminstance, $categories)) {
                    $name = $categories[$item->iteminstance]->get_name();
                } else {
                    // 2020-11-30: removed the TRUE flag for get_name()
                    //   because it only affects grade categories and
                    //   if a category grade item gets to this point then
                    //   it is a faulty item whose category does not exist.
                    //   (see /lib/grade/grade_item.php, around line 1425)
                    $name = $item->get_name();
                }

                $char = get_grade_tree_char($depth, $i, $ids, $items, $categories, $chars);
                $name = block_taskchain_navigation::trim_text($spacer.$char.$name,
                                                              $cm_namelength,
                                                              $cm_headlength + ($depth * 2),
                                                              $cm_taillength);
                $items[$id] = $name;

                if ($item->is_category_item()) {
                    if ($char==$chars->end) {
                        $spacers[] = $chars->space;
                    } else {
                        $spacers[] = $chars->trunk;
                    }
                    $spacer = implode('', $spacers);
                }
            }
            if (count($items)) {
                $conditiongradeitemidmenu = $basemenuitems + $items;
            }
        }

        if ($enablecompletion) {
            $items = array();
            $modinfo = get_fast_modinfo($course);
            foreach($modinfo->cms as $id => $cm) {
                if ($cm->completion) {
                    $items[$id] = $cm->name;
                }
            }
            if (count($items)) {
                asort($items);
                $conditioncmidmenu = $basemenuitems + $items;
            }
        }
        $conditionfieldnamemenu = array(
            '' => get_string('none', 'moodle'),
        );
        $conditionfieldoperatormenu = array();

        $filepath = $CFG->dirroot.'/availability/condition/profile/classes/frontend.php';
        if (file_exists($filepath)) {
            // Moodle >= 2.7
            $contents = file_get_contents($filepath);
            $search = "/'([a-zA-Z0-9]+)' => get_user_field_name\('\\1'\)/";
            if (preg_match_all($search, $contents, $items)) {
                foreach ($items[1] as $item) {
                    $conditionfieldnamemenu[$item] = get_user_field_name($item);
                }
            }
            $search = "/(?<=')op_([a-zA-Z0-9]+)(?=')/";
            if (preg_match_all($search, $contents, $items)) {
                foreach ($items[1] as $i => $item) {
                    $conditionfieldoperatormenu[$item] = get_string($items[0][$i], 'availability_profile');
                }
            }
            require_once($CFG->dirroot.'/user/profile/lib.php');
            if ($items = profile_get_custom_fields(true)) {
                foreach ($items as $item) {
                    $conditionfieldnamemenu[$item->shortname] = $item->name;
                }
            }
        } else if (method_exists('condition_info', 'get_condition_user_fields')) {
            // Moodle >= 2.4
            if ($items = condition_info::get_condition_user_fields(array('context' => $course->context))) {
                $conditionfieldnamemenu += $items;
            }
            $conditionfieldoperatormenu = condition_info::get_condition_user_field_operators();
        } else {
            // Moodle <= 2.3 doesn't have conditional user fields
            $conditionfieldnamemenu = array();
        }

        if ($dbman->field_exists('course_modules', 'availability')) {
            // Moodle >= 2.7
            if ($items = groups_get_all_groups($course->id)) {
                foreach ($items as $item) {
                    $name = $item->name;
                    $name = block_taskchain_navigation::filter_text($name);
                    $name = block_taskchain_navigation::trim_text($name);
                    $conditiongroupidmenu[$item->id] = $name;
                }
            }
            if ($items = groups_get_all_groupings($course->id)) {
                foreach ($items as $item) {
                    $name = $item->name;
                    $name = block_taskchain_navigation::filter_text($name);
                    $name = block_taskchain_navigation::trim_text($name);
                    $conditiongroupingidmenu[$item->id] = $name;
                }
            }
        }

        $str = new stdClass();
        if ($strman->string_exists('accessrestrictions', 'availability')) {
            // Moodle >= 2.7
            $str->accessrestrictions = get_string('accessrestrictions', 'availability');
            $str->datetitle  = get_string('title', 'availability_date');
            $str->userfield  = get_string('conditiontitle', 'availability_profile');
            $str->gradetitle = get_string('title',          'availability_grade');
            $str->grademin   = get_string('option_min',     'availability_grade');
            $str->grademax   = get_string('option_max',     'availability_grade');
            $str->activitycompletion = get_string('activitycompletion', 'completion');
            $conditioncmcompletionmenu = array(
                COMPLETION_COMPLETE      => get_string('option_complete',   'availability_completion'),
                COMPLETION_INCOMPLETE    => get_string('option_incomplete', 'availability_completion'),
                COMPLETION_COMPLETE_PASS => get_string('option_pass',       'availability_completion'),
                COMPLETION_COMPLETE_FAIL => get_string('option_fail',       'availability_completion')
            );
            $str->showavailability = get_string('display', 'form');
            // Note: CONDITION_STUDENTVIEW_xxx constants are not defined in Moodle >= 2.9
            $hide = (defined('CONDITION_STUDENTVIEW_HIDE') ? CONDITION_STUDENTVIEW_HIDE : 0);
            $show = (defined('CONDITION_STUDENTVIEW_SHOW') ? CONDITION_STUDENTVIEW_SHOW : 1);
            $conditionactionmenu = array(
                $hide => get_string('hidden_all', 'availability'),
                $show => get_string('shown_all',  'availability')
            );
            $str->conditiontype  = get_string('label_sign',              'availability'); // Restriction type
            $str->conditiontype1 = get_string('listheader_sign_before',  'availability'); // Student [MUST (NOT)]
            $str->conditiontype2 = get_string('listheader_multi_before', 'availability'); // match [ANY | ALL]
            $str->conditiontype3 = get_string('listheader_multi_after',  'availability'); // of the following
            $conditiontypemustmenu = array(
                ''  => get_string('listheader_sign_pos',  'availability'), // must
                '!' => get_string('listheader_sign_neg',  'availability'), // must not
            );
            $conditiontypejoinmenu = array(
                '&' => get_string('listheader_multi_and', 'availability'), // all
                '|' => get_string('listheader_multi_or',  'availability'), // any
            );
        } else {
            // Moodle <= 2.6
            $str->accessrestrictions = get_string('availabilityconditions', 'condition');
            $str->datetitle  = get_string('date');
            if ($strman->string_exists('userfield', 'condition')) {
                // Moodle >= 2.4
                $str->userfield = get_string('userfield',   'condition');
            }
            $str->gradetitle = get_string('gradecondition', 'condition');
            $str->grademin   = get_string('grade_atleast',  'condition');
            $str->grademax   = get_string('grade_upto',     'condition');
            $str->activitycompletion = get_string('completioncondition', 'condition');
            $conditioncmcompletionmenu = array(
                COMPLETION_COMPLETE      => get_string('completion_complete',   'condition'),
                COMPLETION_INCOMPLETE    => get_string('completion_incomplete', 'condition'),
                COMPLETION_COMPLETE_PASS => get_string('completion_pass',       'condition'),
                COMPLETION_COMPLETE_FAIL => get_string('completion_fail',       'condition')
            );
            $str->showavailability = get_string('showavailability', 'condition');
            $conditionactionmenu = array(
                CONDITION_STUDENTVIEW_HIDE => get_string('showavailability_hide', 'condition'),
                CONDITION_STUDENTVIEW_SHOW => get_string('showavailability_show', 'condition')
            );
        }

        if ($strman->string_exists('modgradetype', 'grades')) {
            // Moodle >= 2.7
            $str->modgradetype  = get_string('modgradetype',      'grades');
            $str->modgradenone  = get_string('modgradetypenone',  'grades');
            $str->modgradescale = get_string('modgradetypescale', 'grades');
            $str->modgradepoint = get_string('modgrademaxgrade',  'grades');
        } else {
            // Moodle <= 2.6
            $str->modgradetype  = get_string('gradetype',    'grades');
            $str->modgradescale = get_string('typenone',     'grades');
            $str->modgradescale = get_string('typescale',    'grades');
            $str->modgradepoint = get_string('aggregatemax', 'rating');
        }

        $icon = new pix_icon('i/hide', '');
        $icon = $OUTPUT->render($icon);
        $str->showavailability = $icon.' '.$str->showavailability;

        if ($enablecompletion) {
            $str->conditioncmungraded  = get_string('conditioncmungraded', $plugin);
            $str->conditioncmresources = get_string('conditioncmresources', $plugin);
            $str->conditioncmlabels    = get_string('conditioncmlabels', $plugin);
        }
    }

    if ($enablecompletion) {
        $completiontrackingmenu = array(
            0 => get_string('completion_none', 'completion'),
            1 => get_string('completion_manual', 'completion'),
            2 => get_string('completion_automatic', 'completion'),
        );
    } else {
        $completiontrackingmenu = array();
    }

    if (class_exists('core_competency\\course_module_competency')) { // Moodle >= 3.1
        $competencyrulemenu = \core_competency\course_module_competency::get_ruleoutcome_list();
    } else {
        $competencyrulemenu = array();
    }

    $filtermenu = array(TEXTFILTER_INHERIT => '',
                        TEXTFILTER_OFF     => get_string('off', 'filters'),
                        TEXTFILTER_ON      => get_string('on', 'filters'));
    $filterdefaulton  = get_string('defaultx', 'filters', $filtermenu[TEXTFILTER_ON]);
    $filterdefaultoff = get_string('defaultx', 'filters', $filtermenu[TEXTFILTER_OFF]);

    // initialize state flags
    $success               = null;
    $started_list          = false;
    $reset_filter_caches   = false;
    $rebuild_course_cache  = false;
    $regrade_course_grades = false;

    // create grade categories, if necessary
    if ($creategradecats) {
        $modinfo = get_fast_modinfo($course);

        // default aggregate is "simple weighted mean of grades"
        // TODO: set default aggregate from form
        $aggregation = GRADE_AGGREGATE_WEIGHTED_MEAN2;

        // get max sortorder from database
        if ($sortorder = $DB->get_field('grade_items', 'MAX(sortorder)', array('courseid' => $course->id))) {
            $sortorder ++;
        } else {
            $sortorder = 1;
        }

        // create course grade category - not usually necessary
        $fullname = '?'; // special name for course grade category
        $params = array('courseid' => $course->id, 'depth' => 1, 'fullname' => $fullname);
        if ($course_grade_category_id = $DB->get_field('grade_categories', 'id', $params)) {
            $DB->set_field('grade_categories', 'aggregation', $aggregation, $params);
        } else {
            $course_grade_category_id = create_grade_category(
                $course, $fullname, null, $aggregation, 0.0, $sortorder++, GRADE_DISPLAY_TYPE_PERCENTAGE
            );
        }

        // create/update section grade categories
        foreach ($sectionmenu as $sectionnum => $sectiontext) {
            if (empty($modinfo->sections[$sectionnum])) {
                continue;
            }
            $grade_category_id = 0;
            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                if (empty($modinfo->cms[$cmid])) {
                    continue;
                }
                $params = array('courseid'     => $course->id,
                                'itemtype'     => 'mod',
                                'itemmodule'   => $modinfo->cms[$cmid]->modname,
                                'iteminstance' => $modinfo->cms[$cmid]->instance);
                if (! $grade_items = $DB->get_records('grade_items', $params)) {
                    continue;
                }
                // note that some activities can have more than on grade item per instance
                // e.g. mod_workshop creates both "submission" and "assessment" grade items
                foreach ($grade_items as $grade_item) {
                    if ($grade_category_id==0) {
                        $fullname = $sectiontext;
                        $params = array('courseid' => $course->id,
                                        'depth'    => 2,
                                        'fullname' => $fullname);
                        if ($grade_category_id = $DB->get_field('grade_categories', 'id', $params)) {
                            $DB->set_field('grade_categories', 'aggregation', $aggregation, array('id' => $grade_category_id));
                        } else {
                            $grade_category_id = create_grade_category(
                                $course, $fullname, $course_grade_category_id,  $aggregation, 0.0, $sortorder++
                            );
                        }
                    }
                    $DB->set_field('grade_items', 'categoryid', $grade_category_id, array('id' => $grade_item->id));
                    $DB->set_field('grade_items', 'sortorder',  $sortorder++,       array('id' => $grade_item->id));
                }
            }
        }

        $regrade_course_grades = true;
    }

    // remove grade categories, if necessary
    if ($removegradecats) {
        $select = 'DISTINCT categoryid';
        $from   = '{grade_items}';
        $where  = 'courseid = ? AND itemtype <> ? AND itemtype <> ?';
        $params = array($course->id, 'course', 'category');

        $select = "id IN (SELECT $select FROM $from WHERE $where)";
        if ($ids = $DB->get_records_select('grade_categories', $select, $params, 'id', 'id,path')) {
            foreach (array_keys($ids) as $id) {
                $ids[$id] = trim($ids[$id]->path, '/');
            }
            $ids = array_filter($ids);
            $ids = implode('/', $ids);
            $ids = explode('/', $ids);
            $ids = array_unique($ids);
        } else if ($ids = $DB->get_records('grade_categories', array('courseid' => $course->id, 'depth' => 1, 'fullname' => '?'))) {
            $ids = array(key($ids)); // the course category
        }
        if (empty($ids)) {
            $ids = '';
            $params = array();
        } else {
            list($ids, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_QM, '', false); // i.e. NOT IN
        }

        $select = 'courseid = ?'.($ids=='' ? '' : " AND id $ids");
        array_unshift($params, $course->id);
        if ($DB->delete_records_select('grade_categories', $select, $params)) {
            $regrade_course_grades = true;
        }

        $select = 'itemtype = ? AND courseid = ?'.($ids=='' ? '' : " AND iteminstance $ids");
        array_unshift($params, 'category');
        if ($DB->delete_records_select('grade_items', $select, $params)) {
            $regrade_course_grades = true;
        }

        unset($select, $from, $where, $params, $ids, $id);
    }

    // sort grade categories, if necessary
    if ($sortgradeitems) {
        $select = 'courseid = ? AND itemtype = ?';
        $params = array($course->id, 'mod');
        if ($items = $DB->get_records_select('grade_items', $select, $params, 'sortorder')) {

            $categories = array();
            foreach ($items as $item) {
                $categoryid = $item->categoryid;

                $cmid = 0;
                foreach ($modinfo->cms as $cmid => $cm) {
                    if ($item->itemmodule==$cm->modname && $item->iteminstance==$cm->instance) {
                        $cmid = $cm->id;
                        break;
                    }
                }

                if ($cmid) {
                    if (empty($categories[$categoryid])) {
                        $categories[$categoryid] = array();
                    }
                    $categories[$categoryid][$cmid] = $item->sortorder;
                }
            }

            $modinfo_cmids = array_keys($modinfo->cms);
            foreach (array_keys($categories) as $categoryid) {

                // get available cm ids and sortorder numbers
                $cmids = array_keys($categories[$categoryid]);
                $sortorder = array_values($categories[$categoryid]);

                // get course page sort order for each cm
                $cmids = array_flip($cmids);
                foreach (array_keys($cmids) as $cmid) {
                    $cmids[$cmid] = array_search($cmid, $modinfo_cmids);
                }

                // sort cmids according to course page order
                asort($cmids);

                // remove course page order info
                $cmids = array_keys($cmids);

                // assign an available sort order to each cm's grade item
                $select = 'courseid = ? AND itemtype = ? AND itemmodule = ? AND iteminstance = ?';
                foreach ($cmids as $i => $cmid) {
                    $params = array($course->id, 'mod', $modinfo->cms[$cmid]->modname, $modinfo->cms[$cmid]->instance);
                    $DB->set_field_select('grade_items', 'sortorder', $sortorder[$i], $select, $params);
                }
            }
        }
        unset($items, $categories, $cmids, $sortorder, $modinfo_cmids);
    }

    // update activities, if necessary
    if (count($selected_cmids) && (count($selected_settings) || $action=='delete')) {

        $success = true;
        $fields = get_mod_fields();
        $table_columns = array();

        // make sure mod pix path is set
        if (empty($CFG->modpixpath)) {
            $CFG->modpixpath = $CFG->dirroot.'/mod';
        }

        foreach ($selected_cmids as $cmid => $cm) {

            $updated = false;
            $skipped = false;
            $regrade_item_id = 0;

            // get the $instance of this $cm (include idnumber for grading)
            $instance = $DB->get_record($cm->modname, array('id' => $cm->instance));
            $instance->cmidnumber = $cm->idnumber;
            $update_instance = false;

            // get module context
            $modulecontext = context_module::instance($cm->id);

            if ($action=='delete') {

                if (function_exists('course_delete_module')) {
                    // Moodle >= 2.5
                    course_delete_module($cm->id);
                } else {
                    // Moodle <= 2.4
                    $filepath = $CFG->dirroot.'/mod/'.$cm->modname.'/lib.php';
                    if (! file_exists($filepath)) {
                        $msg = "$cm->modname lib.php not found at $filepath";
                        echo $OUTPUT->notification($msg);
                    }
                    require_once($filepath);

                    $deleteinstancefunction = $cm->modname.'_delete_instance';
                    if (! function_exists($deleteinstancefunction)) {
                        $msg = "$cm->modname delete function not found ($deleteinstancefunction)";
                        echo $OUTPUT->notification($msg);
                    }

                    // copied from "course/mod.php"
                    if (! $deleteinstancefunction($cm->instance)) {
                        $msg = "Could not delete the $cm->modname (instance id=$cm->instance)";
                        echo $OUTPUT->notification($msg);
                    }
                    if (! delete_course_module($cm->id)) {
                        $msg = "Could not delete the $cm->modname (coursemodule, id=$cm->id)";
                        echo $OUTPUT->notification($msg);
                    }
                    if (! $sectionid = $DB->get_field('course_sections', 'id', array('course' => $cm->course, 'section' => $cm->sectionnum))) {
                        $msg = "Could not get section id (course id=$cm->course, section num=$cm->sectionnum)";
                        echo $OUTPUT->notification($msg);
                    }
                    if (! delete_mod_from_section($cm->id, $sectionid)) {
                        $msg = "Could not delete the $cm->modname (id=$cm->id) from that section (id=$sectionid)";
                        echo $OUTPUT->notification($msg);
                    }

                    add_to_log($cm->course, 'course', 'delete mod', "view.php?id=$cm->course", "$cm->modname $cm->instance", $cm->id);
                }

                $rebuild_course_cache = true;
                $updated = true;
            }

            // only check completion/conditions once per $cm
            $conditions_checked = false;
            $completion_updated = false;

            // Note: $selected_settings should only contain anything if $action=='apply'
            foreach ($selected_settings as $setting) {
                switch ($setting) {

                    // activity instance settings
                    case 'availablefrom':
                    case 'availableuntil':
                    case 'viewableuntil':
                    case 'viewablefrom':
                    case 'cutoff':
                    case 'gradingdue':
                    case 'maxgrade':
                    case 'rating':

                        if ($cm->modname=='taskchain') {
                            $table = 'taskchain_chains';
                            $id    = $DB->get_field($table, 'id', array('parenttype' => 0, 'parentid' => $cm->instance));
                        } else {
                            $table = $cm->modname;
                            $id    = $cm->instance;
                        }

                        // get list of fields in this $table
                        if (empty($table_columns[$table])) {
                            $table_columns[$table] = $DB->get_columns($table);
                            foreach (array_keys($table_columns[$table]) as $field) {
                                $table_columns[$table][$field] = true;
                            }
                            if ($table=='reader' && array_key_exists('availablefrom', $table_columns[$table])) {
                                // until 2017.06.12, these fields were called timeopen/close
                                $fields['reader']['availablefrom'] = 'availablefrom';
                                $fields['reader']['availableuntil'] = 'availableuntil';
                            }
                        }

                        // convert setting name to database field name
                        if (isset($fields[$cm->modname][$setting])) {
                            $field = $fields[$cm->modname][$setting];
                        } else if ($setting=='cutoff' || $setting=='gradingdue') {
                            $field = $setting.'date';
                        } else {
                            $field = $setting;
                        }

                        // update activity instance record, if field exists
                        if (empty($table_columns[$table][$field])) {
                            $skipped = true;
                        } else if ($DB->set_field($table, $field, $$setting, array('id' => $id))) { // $$ is deliberate
                            $updated = true;
                        } else {
                            $success = false;
                        }
                        break;

                    // course_module settings
                    case 'visible':
                        if ($section_visible[$cm->sectionnum]) {
                            $rebuild_course_cache = true;
                            $field = 'visible';
                        } else {
                            // hidden section - use "visibleold" field
                            // Note: there is no need to rebuild cache
                            $field = 'visibleold';
                        }
                        if ($DB->set_field('course_modules', $field, ($$setting==0 ? 0 : 1), array('id' => $cm->id))) {
                            $updated = true;
                            if (isset($allowstealth)) { // Moodle >= 3.3
                                $DB->set_field('course_modules', 'visibleoncoursepage', ($$setting < 0 ? 0 : 1), array('id' => $cm->id));
                            }
                        } else {
                            $success = false;
                        }
                        break;

                    case 'indent':
                        switch (true) {
                            case $indent ==0: $set = 'indent = 0'; break;
                            case $indent > 0: $set = "indent = (indent + $indent)"; break;
                            case $indent < 0: $set = "indent = (CASE WHEN indent < ABS($indent) THEN 0 ELSE indent - ABS($indent) END)"; break;
                        }
                        if ($DB->execute("UPDATE {$CFG->prefix}course_modules SET $set WHERE id = $cm->id")) {
                            $rebuild_course_cache = true;
                            $updated = true;
                        } else {
                            $success = false;
                        }
                        break;

                    case 'section':
                        if ($cm->sectionnum==$section) {
                            $skipped = true;
                        } else {
                            // remove cm from old section
                            $params = array('course' => $course->id, 'section' => $cm->sectionnum);
                            if ($sectionid = $DB->get_field('course_sections', 'id', $params)) {
                                $sequence = $DB->get_field('course_sections', 'sequence', $params);
                                if (is_string($sequence)) {
                                    $sequence = explode(',', $sequence);
                                    $sequence = array_filter($sequence); // remove blanks
                                    $sequence = preg_grep('/^'.$cm->id.'$/', $sequence, PREG_GREP_INVERT);
                                    $sequence = implode(',', $sequence);
                                    $DB->set_field('course_sections', 'sequence', $sequence, $params);
                                }
                                // add cm to target $section
                                if ($position==1) {
                                    $add_cm_to_sequence = 'array_unshift'; // prepend to start of section
                                } else {
                                    $add_cm_to_sequence = 'array_push'; // append to end of section
                                }
                                $params = array('course' => $course->id, 'section' => ($section >= 0 ? $section : $cm->sectionnum));
                                $sectionid = $DB->get_field('course_sections', 'id', $params);
                                $sequence = $DB->get_field('course_sections', 'sequence', $params);
                                if (is_string($sequence)) {
                                    $sequence = explode(',', $sequence);
                                    $sequence = array_filter($sequence); // remove blanks
                                    $sequence = preg_grep('/^'.$cm->id.'$/', $sequence, PREG_GREP_INVERT);
                                } else {
                                    $sequence = array(); // shouldn't happen !!
                                }
                                $add_cm_to_sequence($sequence, $cm->id);
                                $sequence = implode(',', $sequence);
                                $DB->set_field('course_sections', 'sequence', $sequence, $params);
                                $DB->set_field('course_modules', 'section', $sectionid, array('id' => $cm->id));
                                $updated = true;
                                $rebuild_course_cache = true;
                            }
                        }
                        break;

                    // uploadlimit
                    case 'uploadlimit':
                        switch ($cm->modname) {

                            case 'assign': // Moodle >= 2.3
                                $table = 'assign_plugin_config';
                                $params = array('assignment' => $cm->instance,
                                                'subtype'    => 'assignsubmission',
                                                'plugin'     => 'file',
                                                'name'       => 'maxsubmissionsizebytes');
                                if ($DB->record_exists($table, $params)) {
                                    if ($DB->set_field($table, 'value', $$setting, $params)) {
                                        $updated = true;
                                    } else {
                                        $success = false;
                                    }
                                } else {
                                    $params['value'] = $$setting;
                                    if ($DB->insert_record($table, $params)) {
                                        $updated = true;
                                    } else {
                                        $success = false;
                                    }
                                }
                                break;

                            case 'assignment': // Moodle <= 2.2
                            case 'forum':
                            case 'workshop':
                                if ($DB->set_field($cm->modname, 'maxbytes', $$setting, array('id' => $cm->instance))) {
                                    $updated = true;
                                } else {
                                    $success = false;
                                }
                                break;

                            // skip all other modules
                            default: $skipped = true;
                        }
                        break;

                    // course module settings
                    case 'groupmode':
                    case 'groupingid':
                    case 'groupmembersonly':
                        if ($DB->set_field('course_modules', $setting, $$setting, array('id' => $cm->id))) {
                            $updated = true;
                            $rebuild_course_cache = true;
                        } else {
                            $success = false;
                        }
                        break;

                    // gradebook item settings
                    case 'gradecat':
                    case 'gradeitemhidden':
                    case 'gradepass':
                        $select = 'courseid = ? AND itemtype = ? AND itemmodule = ? AND iteminstance = ?';
                        $params = array($course->id, 'mod', $cm->modname, $cm->instance);
                        switch ($setting) {
                            case 'gradecat':        $field = 'categoryid'; break;
                            case 'gradeitemhidden': $field = 'hidden';     break;
                            default: $field = $setting;
                        }
                        if ($DB->set_field_select('grade_items', $field, $$setting, $select, $params)) {
                            $updated = true;
                            $select .= ' AND itemnumber = ?'; // e.g. workshop has TWO items
                            $params[] = 0;
                            $regrade_item_id = $DB->get_field_select('grade_items', 'id', $select, $params);
                        } else {
                            $success = false;
                        }
                        break;

                    // gradebook grades settings
                    case 'gradeexcluded':
                    case 'gradeoverridden':
                    case 'gradehidden':
                    case 'gradehiddenuntil':
                    case 'gradelocked':
                    case 'gradelocktime':
                        $success = false;
                        switch ($setting) {
                            case 'gradehiddenuntil': $field = 'hidden'; break;
                            default: $field = substr($setting, 5); // e.g. locked
                        }
                        $select = 'courseid = ? AND itemtype = ? AND itemmodule = ? AND iteminstance = ?';
                        $params = array($course->id, 'mod', $cm->modname, $cm->instance);
                        if ($grade_items = $DB->get_records_select('grade_items', $select, $params, 'itemnumber')) {
                            $regrade_item_id = key($grade_items); // usually there's only one grade item
                            foreach ($grade_items as $grade_item) {
                                $params = array('itemid' => $grade_item->id);
                                if ($DB->set_field('grade_grades', $field, $$setting, $params)) {
                                    $success = true;
                                    $updated = true;
                                }
                            }
                        }
                        break;

                    // extra credit
                    case 'extracredit':
                        $skipped = true;

                        $select = 'courseid = ? AND itemtype = ? AND itemmodule = ? AND iteminstance = ? AND itemnumber = ?';
                        $params = array($course->id, 'mod', $cm->modname, $cm->instance, 0);
                        if ($grade_item = $DB->get_record_select('grade_items', $select, $params)) {

                            $select = 'id = ? AND aggregation IN (?, ?, ?)';
                            $params = array($grade_item->categoryid,
                                            GRADE_AGGREGATE_WEIGHTED_MEAN2,
                                            GRADE_AGGREGATE_EXTRACREDIT_MEAN,
                                            GRADE_AGGREGATE_SUM);
                            if ($grade_category = $DB->get_record_select('grade_categories', $select, $params)) {

                                $skipped = false;
                                if ($DB->set_field('grade_items', 'aggregationcoef', $extracredit, array('id' => $grade_item->id))) {
                                    $updated = true;
                                    $regrade_item_id = $grade_item->id;
                                } else {
                                    $success = false;
                                }
                            }
                        }
                        break;

                    // regrade activity
                    case 'regrade':

                        // Note: the lib.php for this mod was included earlier

                        // If we use just the "update_grades" function,
                        // we cannot know if it is successful or not ...
                        // $update_grades = $cm->modname.'_update_grades';
                        // ... so we use the following functions instead:
                        $get_user_grades = $cm->modname.'_get_user_grades';
                        $grade_item_update = $cm->modname.'_grade_item_update';

                        $skipped = true;
                        if (property_exists($instance, 'assessed') && empty($instance->assessed)) {
                            // Could also check: array_key_exists($cm->modname, $ratingmods)
                            // Skip data, forum and glossary if rating is not enabled.
                        } else if (function_exists($get_user_grades) && function_exists($grade_item_update)) {
                            $grades = $get_user_grades($instance);
                            if ($grade_item_update($instance, $grades)==GRADE_UPDATE_OK) { // GRADE_UPDATE_OK = 0
                                $updated = true;
                            } else {
                                $success = false;
                            }
                            $skipped = false;
                        }
                        break;

                    case 'removeconditions':
                        if ($removeconditions) {
                            if ($dbman->field_exists('course_modules', 'availability')) {
                                // Moodle >= 2.7
                                $DB->set_field('course_modules', 'availability', '', array('id' => $cm->id));
                                $updated = true;
                            } else {
                                // Moodle <= 2.6
                                if ($dbman->field_exists('course_modules', 'availablefrom')) {
                                    $DB->set_field('course_modules', 'availablefrom', 0, array('id' => $cm->id));
                                    $DB->set_field('course_modules', 'availableuntil', 0, array('id' => $cm->id));
                                    $DB->set_field('course_modules', 'showavailability', 0, array('id' => $cm->id));
                                    $updated = true;
                                }
                                if ($dbman->table_exists('course_modules_availability')) {
                                    $DB->delete_records('course_modules_availability', array('coursemoduleid' => $cm->id));
                                    $updated = true;
                                }
                                if ($dbman->table_exists('course_modules_avail_fields')) {
                                    $DB->delete_records('course_modules_avail_fields', array('coursemoduleid' => $cm->id));
                                    $updated = true;
                                }
                            }
                            $rebuild_course_cache = true;
                        }
                        break;

                    case 'conditiontype':
                    case 'conditiondate':
                    case 'conditiongrade':
                    case 'conditionfield':
                    case 'conditiongroup':
                    case 'conditiongrouping':
                    case 'conditioncm':
                    case 'conditionaction':
                        if ($conditions_checked==false) {
                            $conditions_checked = true;
                            $conditions = array_merge($conditiondate, $conditiongrade, $conditionfield, $conditiongroup, $conditiongrouping, $conditioncm);
                            update_course_module_availability($labelmods, $resourcemods, $course, $cm, $conditiontype, $conditions, $conditionaction, $updated, $skipped);
                            if ($updated) {
                                $rebuild_course_cache = true;
                            }
                        }
                        break;

                    case 'removecompletion':
                        if ($removecompletion) {
                            $table = 'course_modules';
                            $params = array('id' => $cm->id);
                            $names = array('completion' => 0,
                                           'completionview' => 0,
                                           'completionexpected' => 0,
                                           'completiongradeitemnumber' => null);
                            foreach ($names as $name => $disabled) {
                                $value = $DB->get_field($table, $name, $params);
                                if (isset($value)) {
                                    $value = intval($value);
                                }
                                if ($value===$disabled) {
                                    $skipped = true;
                                } else {
                                    $updated = $DB->set_field($table, $name, $disabled, $params);
                                }
                            }
                            $params = array('id' => $cm->instance);
                            foreach ($completionfields as $name => $field) {
                                if ($field->cmfield) {
                                    continue; // e.g. completionview/grade
                                }
                                if (array_key_exists($cm->modname, $field->mods)) {
                                    if ($DB->get_field($cm->modname, $name, $params)) {
                                        $updated = $DB->set_field($cm->modname, $name, 0, $params);
                                    } else {
                                        $skipped = true;
                                    }
                                }
                            }
                            if ($updated) {
                                $completion_updated = true;
                            }
                        }
                        break;

                    case 'erasecompletion':
                        if ($erasecompletion) {
                            $completion_updated = true;
                            $updated = true;
                        } else {
                            $skipped = true;
                        }
                        break;

                    case 'completiontracking':
                        update_course_module_completion('course_modules', $cm->id, 'completion', $completiontracking, $updated, $skipped, $completion_updated);
                        break;

                    case 'completiondate':
                        update_course_module_completion('course_modules', $cm->id, $setting, $completiondate, $updated, $skipped, $completion_updated);
                        break;

                    case 'completionview':
                        if (array_key_exists($cm->modname, $completionfields[$setting]->mods)) {
                            update_course_module_completion('course_modules', $cm->id, $setting, $completionview, $updated, $skipped, $completion_updated);
                        }
                        break;

                    case 'completiongrade':
                        if (array_key_exists($cm->modname, $completionfields[$setting]->mods)) {
                            // course_modules.completiongradeitemnumber
                            // see "set_moduleinfo_defaults()" in "course/modlib.php"
                            // null=disabled, 0=enabled (i.e. require grade)
                            $completiongradeitemnumber = ($completiongrade ? 0 : null);
                            update_course_module_completion('course_modules', $cm->id, 'completiongradeitemnumber', $completiongradeitemnumber, $updated, $skipped, $completion_updated);
                        }
                        break;

                    case 'competencyrule':
                        $data = (object)array(
                            'coursemodule' => $cm->id,
                            'competencies' => array(),
                            'competency_rule' => $competencyrule
                        );
                        // see "admin/tool/lp/lib.php"
                        $data = tool_lp_coursemodule_edit_post_actions($data, $course);
                        $updated = true;
                        break;

                    case 'modgrade':
                        if (array_key_exists($cm->modname, $ratingmods) || array_key_exists($cm->modname, $gradingmods)) {
                            $assessed = ($modgrade==0 ? 0 : 1);
                            if (property_exists($instance, 'assessed') && $instance->assessed != $assessed) {
                                $instance->assessed = $assessed;
                                $update_instance = true;
                            }
                            if (property_exists($instance, 'scale') && $instance->scale != $modgrade) {
                                $instance->scale = $modgrade; // data, forum, glossary
                                $update_instance = true;
                            }
                            if (property_exists($instance, 'grade') && $instance->grade != $modgrade) {
                                $instance->grade = $modgrade; // assignment, lesson
                                $update_instance = true;
                            }
                            if ($update_instance && $regrade_item_id==0) {
                                $select = 'courseid = ? AND itemtype = ? AND itemmodule = ? AND iteminstance = ? AND itemnumber = ?';
                                $params = array($course->id, 'mod', $cm->modname, $cm->instance, 0);
                                $regrade_item_id = $DB->get_field_select('grade_items', 'id', $select, $params);
                            }
                        }
                        break;

                    default:
                        if (array_key_exists($setting, $completionfields)) {
                            // completion fields
                            $field = $completionfields[$setting];
                            if (array_key_exists($cm->modname, $field->mods)) {
                                update_course_module_completion($cm->modname, $cm->instance, $setting, $$setting, $updated, $skipped, $completion_updated);
                            }
                        } else if (substr($setting, 0, 6)=='filter') {
                            // filter fields
                            $filter = substr($setting, 6);
                            if (in_array($$setting, array(TEXTFILTER_ON, TEXTFILTER_OFF, TEXTFILTER_INHERIT))) {
                                filter_set_local_state($filter, $modulecontext->id, $$setting);
                                $reset_filter_caches = true;
                                $updated = true;
                            } else {
                                $skipped = true;
                            }
                        } else {
                            // unexpected setting - shouldn't happen !!
                            echo('Unknown setting, '.$setting. ', not processed').html_writer::empty_tag('br');
                        }

                } // end switch
            } // end foreach $selected_settings

            if ($update_instance) {
                $DB->update_record($cm->modname, $instance);
                $updated = true;
            }

            if ($completion_updated) {
                $completion = $completiontracking;

                // if automatic completion (=2) is requested,
                // check that some completion conditions are set
                if ($completion==2) {
                    $completion = 0;
                    $table = 'course_modules';
                    $params = array('id' => $cm->id);
                    $names = array('completionview' => 0,
                                   'completionexpected' => 0,
                                   'completiongradeitemnumber' => null);
                    foreach ($names as $name => $disabled) {
                        $value = $DB->get_field($table, $name, $params);
                        if (isset($value)) {
                            $value = intval($value);
                        }
                        if ($value !== $disabled) {
                            $completion = $completiontracking;
                        }
                    }
                    foreach ($completionfields as $field) {
                        $name = $field->name;
                        if (property_exists($instance, $name) && $instance->$name) {
                            $completion = $completiontracking;
                        }
                    }
                }

                // force completion to be something sensible
                update_course_module_completion('course_modules', $cm->id, 'completion', $completion, $updated, $skipped, $completion_updated);

                // get full $cm record
                if (method_exists($cm, 'get_course_module_record')) {
                    // Moodle >= 2.7
                    $cm = $cm->get_course_module_record(true);
                } else {
                    // Moodle <= 2.6
                    $cm = get_coursemodule_from_id($cm->modname, $cm->id, $cm->course, true);
                }

                // prevent "Cannot find grade item" error in "lib/completionlib.php"
                $params = array('courseid'     => $cm->course,
                                'itemtype'     => 'mod',
                                'itemmodule'   => $cm->modname,
                                'iteminstance' => $cm->instance);
                if (! grade_item::fetch_all($params)) {
                    $cm->completiongradeitemnumber = null; // disable grade completion
                }

                $completion = new completion_info($course);
                $completion->reset_all_state($cm);
                $rebuild_course_cache = true;
            }

            if ($regrade_item_id) {
                $regrade_course_grades = true;
                $DB->set_field('grade_items', 'needsupdate', 1, array('id' => $regrade_item_id));
                $DB->set_field('grade_items', 'needsupdate', 1, array('courseid' => $course->id, 'itemtype' => 'course'));
            }

            if ($started_list==false) {
                $started_list = true;
                echo '<table border="0" cellpadding="4" cellspacing="4" class="selectedactivitylist"><tbody>'."\n";
                if (count($selected_settings)) {
                    echo '<tr><th colspan="2">'.get_string('settingsselected', $plugin).'</th></tr>'."\n";
                    foreach ($selected_settings as $setting) {
                        list($name, $value) = format_setting(
                            $setting, $$setting, $str,
                            $ratings, $modgradetypes, $modgradescales,
                            $gradecategories, $groupmodes, $groupings,
                            $visiblemenu, $indentmenu, $sectionmenu, $positionmenu, $uploadlimitmenu,
                            $conditiontypemustmenu, $conditiontypejoinmenu, $conditiongradeitemidmenu,
                            $conditioncmidmenu, $conditioncmcompletionmenu,
                            $conditionfieldnamemenu, $conditionfieldoperatormenu,
                            $conditiongroupidmenu, $conditiongroupingidmenu,
                            $conditionactionmenu, $completiontrackingmenu,
                            $completionfields, $competencyrulemenu,
                            $filters, $filtermenu, $filterdefaulton, $filterdefaultoff
                        );
                        echo '<tr><td class="itemname">'.$name.':</td><td class="itemvalue">'.$value.'</td></tr>'. "\n";
                    }
                }
                echo '<tr><th colspan="2">'.get_string('activitiesselected', $plugin).'</th></tr>'."\n";
             }

            echo '<tr><td class="itemname">';
            if ($updated) {
                echo '<span class="updated">'.get_string('updated', 'moodle', $cm->modname).'</span>';
            } else if ($skipped) {
                echo '<span class="skipped">'.get_string('skipped').' '.$cm->modname.'</span>';
            } else {
                echo '<span class="failure">'.get_string('fail', 'install').' '.$cm->modname.'</span>';
            }
            echo '</td><td class="itemvalue">';
            $url = $PAGE->theme->$image_url('icon', $cm->modname)->out();
            echo '<img src="'.$url.'" class="icon" title="'.s(get_string('modulename', $cm->modname)).'"></img> ';

            $name = urldecode($cm->name);
            $name = block_taskchain_navigation::filter_text($name);
            $name = trim(strip_tags($name));
            $name = block_taskchain_navigation::trim_text($name, $cm_namelength, $cm_headlength, $cm_taillength);

            $params = array('update' => $cm->id, 'return' => 1);
            $url = new moodle_url("/course/modedit.php", $params);

            $params = array('onclick' => 'this.target = "_blank"');
            echo html_writer::link($url, $name, $params);

            echo '</td></tr>'."\n";
        }
    }

    if ($sortgradeitems || $creategradecats || $removegradecats || $reset_filter_caches || $rebuild_course_cache || $regrade_course_grades || isset($success)) {
        if ($started_list==false) {
            $started_list = true;
            echo '<table border="0" cellpadding="4" cellspacing="4" class="selectedactivitylist"><tbody>'."\n";
        }
        if ($sortgradeitems) {
            echo '<tr><td class="notifymessage" colspan="2">';
            $msg = get_string('sortedgradeitems', $plugin);
            echo $OUTPUT->notification($msg, 'notifysuccess');
            echo '</td></tr>'."\n";
        }
        if ($creategradecats) {
            echo '<tr><td class="notifymessage" colspan="2">';
            $msg = get_string('createdgradecategories', $plugin);
            echo $OUTPUT->notification($msg, 'notifysuccess');
            echo '</td></tr>'."\n";
        }
        if ($removegradecats) {
            echo '<tr><td class="notifymessage" colspan="2">';
            $msg = get_string('removedgradecategories', $plugin);
            echo $OUTPUT->notification($msg, 'notifysuccess');
            echo '</td></tr>'."\n";
        }
        if ($reset_filter_caches) {
            echo '<tr><td class="notifymessage" colspan="2">';
            echo get_string('resettingfiltercache', $plugin).' ... ';
            filter_manager::reset_caches();
            //unset($FILTERLIB_PRIVATE->active[$context->id]);
            echo get_string('ok').'</td></tr>'."\n";
        }
        if ($rebuild_course_cache) {
            echo '<tr><td class="notifymessage" colspan="2">';
            echo get_string('rebuildingcoursecache', $plugin).' ... ';
            rebuild_course_cache($course->id);
            echo get_string('ok').'</td></tr>'."\n";
        }
        if ($regrade_course_grades) {
            echo '<tr><td class="notifymessage" colspan="2">';
            echo get_string('recalculatingcoursegrades', $plugin).' ... ';
            grade_regrade_final_grades($course->id);
            echo get_string('ok').'</td></tr>'."\n";
        }
        if ($success===true) {
            echo '<tr><td class="notifymessage" colspan="2">';
            $msg = get_string('success');
            echo $OUTPUT->notification($msg, 'notifysuccess');
            echo '</td></tr>'."\n";
        }
        if ($success===false) {
            echo '<tr><td class="notifymessage" colspan="2">';
            $msg = get_string('activityupdatefailure', $plugin);
            echo $OUTPUT->notification($msg, 'notifyproblem');
            echo '</td></tr>'."\n";
        }
    }
    if ($started_list) {
        $started_list = false;
        echo '</tbody></table>'."\n";
    }

    $modtypes = array();
    if (count($labelmods)) {
        sort($labelmods);
        $modtypes[] = '"labels": ["'.implode('", "', $labelmods).'"]';
    }
    if (count($resourcemods)) {
        sort($resourcemods);
        $modtypes[] = '"resources": ["'.implode('", "', $resourcemods).'"]';
    }
    if (count($activitymods)) {
        sort($activitymods);
        $modtypes[] = '"activities": ["'.implode('", "', $activitymods).'"]';
    }
    if ($modtypes = implode(",\n    ", $modtypes)) {
        $modtypes = "\n    ".$modtypes."\n";
    }

    echo '<script type="text/javascript">'."\n";
    echo "//<![CDATA[\n";

    echo "if (window.TCN==null) {\n";
    echo "    window.TCN = {};\n";
    echo "}\n";    
    echo "TCN.msg = {\n".
         '    "all": "'.js(get_string("all")).'",'."\n".
         '    "none": "'.js(get_string("none")).'",'."\n".
         '    "labels": "'.js(get_string("modulenameplural", "label")).'",'."\n".
         '    "resources": "'.js(get_string("resources")).'",'."\n".
         '    "activities": "'.js(get_string("activities")).'",'."\n".
         '    "nosettings": "'.js(get_string("nosettingsselected", $plugin)).'",'."\n".
         '    "noactivities": "'.js(get_string("noactivitiesselected", $plugin)).'",'."\n".
         '    "confirmapply": "'.js(get_string("confirmapply", $plugin)).'",'."\n".
         '    "confirmdelete": "'.js(get_string("confirmdelete", $plugin)).'"'."\n".
         '};'."\n";
    echo "TCN.modtypes = {".$modtypes."};\n";
    echo "//]]>\n";
    echo '</script>'."\n";

    echo '<form method="post" action="accesscontrol.php" enctype="multipart/form-data">'."\n";
    echo '<table border="0" cellpadding="4" cellspacing="4" width="720" style="margin: auto;" class="blockconfigtable">'."\n";

    echo '<tr>'."\n";
    echo '<td colspan="2" class="blockdescription">'.nl2br(get_string('accesspagedescription', $plugin)).'</td>'."\n";
    echo '<td class="itemselect">'.get_string('select').' '.$OUTPUT->help_icon('selectsettings', $plugin).'</td>'."\n";
    echo '</tr>'."\n";

    // ============================
    // Activity filters
    // ============================
    //
    print_sectionheading(get_string('activityfilters', $plugin), 'activityfilters', false);

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('sectiontags', $plugin).':</td>';
    echo '<td class="itemvalue">';
    echo '<input id="id_sectiontags" type="text" name="sectiontags" size="15" value="'.$sectiontags.'" />';
    echo ' '.$OUTPUT->help_icon('sectiontitletags', $plugin);
    echo '</td>'."\n";
    echo '<td class="itemselect">&nbsp;</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('sections', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">'.$sections.'</td>'."\n";
    echo '<td class="itemselect">&nbsp;</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('activitytypes', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">'.$modules.'</td>'."\n";
    echo '<td class="itemselect">&nbsp;</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('activitynamefilters', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">';
    echo '    <div class="subitem">';
    echo '        <div class="subname">'.get_string('include', $plugin).':</div>';
    echo '        <input id="id_include" type="text" name="include" size="15" value="'.$include.'" />';
    echo '    </div>';
    echo '    <div class="subitem">';
    echo '        <div class="subname">'.get_string('exclude', $plugin).':</div>';
    echo '        <input id="id_exclude" type="text" name="exclude" size="15" value="'.$exclude.'" />';
    echo '    </div>';
    echo '</td>'."\n";
    echo '<td class="itemselect">&nbsp;</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('visibility', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($visibilitymenu, 'visibility', $visibility, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">&nbsp;</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('activityids', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">'.$cms.'</td>'."\n";
    echo '<td class="itemselect">&nbsp;</td>'."\n";
    echo '</tr>'."\n";

    // ============================
    // Availability dates
    // ============================
    //
    print_sectionheading(get_string('dates', $plugin), 'dates', true);

    asort($availablemods);
    if ($modnames = implode(', ', $availablemods)) {
        $modnames = get_string('usedby', $plugin, $modnames);

        $name = 'availablefrom';
        $label = get_string($name, $plugin);
        print_date_row($name, $label, $availablefromdate, $availablefromdisable, $modnames);

        $name = 'availableuntil';
        $label = get_string($name, $plugin);
        print_date_row($name, $label, $availableuntildate, $availableuntildisable, $modnames);
    }

    asort($viewablemods);
    if ($modnames = implode(', ', $viewablemods)) {
        $modnames = get_string('usedby', $plugin, $modnames);

        $name = 'viewablefrom';
        $label = get_string($name, $plugin);
        print_date_row($name, $label, $viewablefromdate, $viewablefromdisable, $modnames);

        $name = 'viewableuntil';
        $label = get_string($name, $plugin);
        print_date_row($name, $label, $viewableuntildate, $viewableuntildisable, $modnames);
    }

    asort($cutoffmods);
    if ($modnames = implode(', ', $cutoffmods)) {
        $modnames = get_string('usedby', $plugin, $modnames);

        $name = 'cutoff';
        $label = get_string($name.'date', 'assign');
        print_date_row($name, $label, $cutoffdate, $cutoffdisable, $modnames);
    }

    asort($gradingduemods);
    if ($modnames = implode(', ', $gradingduemods)) {
        $modnames = get_string('usedby', $plugin, $modnames);

        $name = 'gradingdue';
        $label = get_string($name.'date', 'assign');
        print_date_row($name, $label, $gradingduedate, $gradingduedisable, $modnames);
    }

    // ============================
    // Grade items
    // ============================
    //
    echo '<tr class="sectionheading" id="id_section_grades">'."\n";
    echo '<th colspan="2">';
    echo get_string('gradeitems', 'grades');
    echo ' &nbsp; <span class="sortgradeitems">';
    if ($sortgradeitems) {
        echo ' '.get_string('sortedgradeitems', $plugin);
    } else {
        $href = $CFG->wwwroot.'/blocks/taskchain_navigation/accesscontrol.php?id='.$block_instance->id.'&sortgradeitems=1&sesskey='.sesskey();
        $onclick = 'return confirm("'.js(get_string('confirmsortgradeitems', $plugin)).'")';
        echo '<a href="'.s($href).'" onclick="'.s($onclick).'">'.get_string('sortgradeitems', $plugin).'</a> ';
        echo $OUTPUT->help_icon('sortgradeitems', $plugin);
    }
    echo '</span>';
    echo ' &nbsp; <span class="creategradecategories">';
    if ($creategradecats) {
        echo ' '.get_string('createdgradecategories', $plugin);
    } else {
        $href = $CFG->wwwroot.'/blocks/taskchain_navigation/accesscontrol.php?id='.$block_instance->id.'&creategradecats=1&sesskey='.sesskey();
        $onclick = 'return confirm("'.js(get_string('confirmcreategradecategories', $plugin)).'")';
        echo '<a href="'.s($href).'" onclick="'.s($onclick).'">'.get_string('creategradecategories', $plugin).'</a> ';
        echo $OUTPUT->help_icon('creategradecategories', $plugin);
    }
    echo '</span>';
    echo ' &nbsp; <span class="removegradecategories">';
    if ($removegradecats) {
        echo ' '.get_string('removedgradecategories', $plugin);
    } else {
        $href = $CFG->wwwroot.'/blocks/taskchain_navigation/accesscontrol.php?id='.$block_instance->id.'&removegradecats=1&sesskey='.sesskey();
        $onclick = 'return confirm("'.js(get_string('confirmremovegradecategories', $plugin)).'")';
        echo '<a href="'.s($href).'" onclick="'.s($onclick).'">'.get_string('removegradecategories', $plugin).'</a> ';
        echo $OUTPUT->help_icon('removegradecategories', $plugin);
    }
    echo '</span>';
    echo '</th>'."\n";
    echo '<th class="toggle"></th>'."\n";
    echo '</tr>'."\n";

    asort($ratingmods);
    if ($modnames = implode(', ', $ratingmods)) {
        // 'data', 'forum', 'glossary'
        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('aggregatetype', 'rating').':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::select($ratings, 'rating', $rating, '').' ';
        echo '('.get_string('usedby', $plugin, $modnames).')';
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('rating'), (! this.checked))";
        echo html_writer::checkbox('select_rating', 1, optional_param('select_rating', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";
    }

    asort($gradingmods);
    if ($modnames = implode(', ', $gradingmods)) {
        // 'assign', 'data', 'forum', 'glossary', 'lesson', 'lti'
        // see "lib/form/modgrade.php"
        if ($strman->string_exists('modgrade', 'grades')) {
            // Moodle >= 2.7
            $grade = get_string('modgrade', 'grades');
        } else {
            // Moodle <= 2.6
            $grade = get_string('grade', 'grades');
        }
        echo '<tr>'."\n";
        echo '<td class="itemname">'.$grade.' / '.get_string('typescale', 'grades').':</td>'."\n";
        echo '<td class="itemvalue">';
        echo '('.get_string('usedby', $plugin, $modnames).')<br />';

        $script = 'var x=this.options[this.selectedIndex].value;';
        $script .= 'this.form.elements["modgradescale"].disabled=(x!="scale");';
        $script .= 'this.form.elements["modgradepoint"].disabled=(x!="point");';
        $script .= 'return true;';
        $params = array('onchange' => $script);
        echo $str->modgradetype.': '.html_writer::select($modgradetypes, 'modgradetype',  $modgradetype, '', $params).'<br />';

        $params = array();
        if ($modgradetype != 'scale') {
            $params['disabled'] = 'disabled';
        }
        echo $str->modgradescale.': '.html_writer::select($modgradescales, 'modgradescale', $modgradescale, '', $params).'<br />';

        $params = array('type' => 'text',
                        'size' => '15',
                        'name' => 'modgradepoint',
                        'value' => $modgradepoint);
        if ($modgradetype != 'point') {
            $params['disabled'] = 'disabled';
        }
        echo $str->modgradepoint.': '.html_writer::empty_tag('input', $params);
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $names = "'modgradetype', 'modgradescale', 'modgradepoint'";
        $script = ''; // "return set_disabled(this.form, new Array($names), (! this.checked))";
        echo html_writer::checkbox('select_modgrade', 1, optional_param('select_modgrade', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";
    }

    if (count($gradingareas)) {
        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('gradingmanagement', 'grading').':</td>'."\n";
        echo '<td class="itemvalue">';
        foreach ($gradingareas as $modname => $areas) {
            echo "<p>";
            foreach ($areas as $areaname => $areatext) {
                echo $gradingmods[$modname]." ($areatext)<br />";
            }
            echo "</p>\n";
        }
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('grading'), (! this.checked))";
        echo html_writer::checkbox('select_grading', 1, optional_param('select_grading', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";
    }

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('maximumgrade', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($maxgrades, 'maxgrade', $maxgrade, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('maxgrade'), (! this.checked))";
    echo html_writer::checkbox('select_maxgrade', 1, optional_param('select_maxgrade', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('gradepass', 'grades').':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($gradepassmenu, 'gradepass', $gradepass, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('gradepass'), (! this.checked))";
    echo html_writer::checkbox('select_gradepass', 1, optional_param('select_gradepass', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('gradecategory', 'grades').':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($gradecategories, 'gradecat', $gradecat, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('gradecat'), (! this.checked))";
    echo html_writer::checkbox('select_gradecat', 1, optional_param('select_gradecat', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('gradeitemhidden', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select_yes_no('gradeitemhidden', $gradeitemhidden);
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('gradeitemhidden'), (! this.checked))";
    echo html_writer::checkbox('select_gradeitemhidden', 1, optional_param('select_gradeitemhidden', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('aggregationcoefextra', 'grades').':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select_yes_no('extracredit', $extracredit);
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('extracredit'), (! this.checked))";
    echo html_writer::checkbox('select_extracredit', 1, optional_param('select_extracredit', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('regrade', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select_yes_no('regrade', $regrade);
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('regrade'), (! this.checked))";
    echo html_writer::checkbox('select_regrade', 1, optional_param('select_regrade', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    // ============================
    // Individual grades
    // ============================
    //
    $name = 'gradeexplanation';
    $label = get_string($name, $plugin);
    $label = html_writer::tag('span', $label, array('class' => $name));

    $name = 'grades';
    $label = get_string($name, 'grades').' '.$label;
    print_sectionheading($label, $name, true);

    $name = 'gradeoverridden';
    $label = get_string('overridden', 'grades');
    print_yes_no_row($name, $label, $gradeoverridden);

    $name = 'gradeexcluded';
    $label = get_string('excluded', 'grades');
    print_yes_no_row($name, $label, $gradeexcluded);

    $name = 'gradehidden';
    $label = get_string('hidden', 'grades');
    print_yes_no_row($name, $label, $gradehidden);

    $name = 'gradehiddenuntil';
    $label = get_string('hiddenuntil', 'grades');
    print_date_row($name, $label, $gradehiddenuntildate, $gradehiddenuntildisable);

    $name = 'gradelocked';
    $label = get_string('locked', 'grades');
    print_yes_no_row($name, $label, $gradelocked);

    $name = 'gradelocktime';
    $label = get_string('locktime', 'grades');
    print_date_row($name, $label, $gradelocktimedate, $gradelocktimedisable);

    // ============================
    // Groups
    // ============================
    //
    print_sectionheading(get_string('groups'), 'groups', true);

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('groupmode').':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($groupmodes, 'groupmode', $groupmode, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('groupmode'), (! this.checked))";
    echo html_writer::checkbox('select_groupmode', 1, optional_param('select_groupmode', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    if (count($groupings)) {
        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('grouping', 'group').':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::select($groupings, 'groupingid', $groupingid, '');
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('groupingid'), (! this.checked))";
        echo html_writer::checkbox('select_groupingid', 1, optional_param('select_groupingid', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";

        if ($strman->string_exists('groupmembersonly', 'group')) {
            echo '<tr>'."\n";
            echo '<td class="itemname groupmembersonly">'.get_string('groupmembersonly', 'group').':</td>'."\n";
            echo '<td class="itemvalue">';
            echo html_writer::checkbox('groupmembersonly', 1, $groupmembersonly);
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('groupmembersonly'), (! this.checked))";
            echo html_writer::checkbox('select_groupmembersonly', 1, optional_param('select_groupmembersonly', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }
    }

    // ============================
    // Course page
    // ============================
    //
    echo '<tr class="sectionheading" id="id_section_coursepage">'."\n";
    echo '<th colspan="2">';
    echo get_string('stickyblockscourseview', 'admin'); // Course page
    echo ' &nbsp; <span class="sortgradeitems">';
    if ($sortactivities) {
        echo ' '.get_string('sortedactivities', $plugin);
    } else {
        $href = $CFG->wwwroot.'/blocks/taskchain_navigation/accesscontrol.php?id='.$block_instance->id.'&sortactivities=1&sesskey='.sesskey();
        $onclick = 'return confirm("'.get_string('confirmsortactivities', $plugin).'")';
        echo '<a href="'.s($href).'" onclick="'.js($onclick).'">'.get_string('sortactivities', $plugin).'</a> ';
        echo $OUTPUT->help_icon('sortactivities', $plugin);
    }
    echo '</span>';
    echo '</th>'."\n";
    echo '<th class="toggle"></th>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('visible').':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($visiblemenu, 'visible', $visible, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('visible'), (! this.checked))";
    echo html_writer::checkbox('select_visible', 1, optional_param('select_visible', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    echo '<tr>'."\n";
    echo '<td class="itemname">'.get_string('indent', $plugin).':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($indentmenu, 'indent', $indent, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('indent'), (! this.checked))";
    echo html_writer::checkbox('select_indent', 1, optional_param('select_indent', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    if ($strman->string_exists('moveto', 'question')) {
        // Moodle >= 2.2
        $moveto = get_string('moveto', 'question');
    } else {
        // Moodle <= 2.1
        $moveto = get_string('movehere');
    }
    echo '<tr>'."\n";
    echo '<td class="itemname">'.$moveto.':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select($sectionmenu, 'section', $section, '');
    echo ' ';
    echo html_writer::select($positionmenu, 'position', $position, '');
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    $script = ''; // "return set_disabled(this.form, new Array('section', 'position'), (! this.checked))";
    echo html_writer::checkbox('select_section', 1, optional_param('select_section', 0, PARAM_INT), '', array('onclick' => $script));
    echo '</td>'."\n";
    echo '</tr>'."\n";

    // ============================
    // Files and uploads
    // ============================
    //
    if (count($filemods)) {
        print_sectionheading(get_string('fileuploads', 'install'), 'files', true);

        $href = 'http://php.net/manual/'.substr(current_language(), 0, 2).'/ini.core.php';
        $icon = html_writer::empty_tag('img', array('src' => $PAGE->theme->$image_url('i/info', ''), 'title' => get_string('info')));
        $params = array('onclick' => 'this.target="_blank"');

        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('phpuploadlimit', $plugin).':</td>'."\n";
        echo '<td class="itemvalue">';
        if ($limit = ini_get('upload_max_filesize')) {
            $limit = display_size(get_real_size($limit)).' upload_max_filesize ';
            echo html_writer::tag('span', $limit, array('class' => 'uploadlimit'));
            echo html_writer::link($href.'#ini.upload-max-filesize', $icon, $params);
            echo html_writer::empty_tag('br');
        }
        if ($limit = ini_get('post_max_size')) {
            $limit = display_size(get_real_size($limit)).' post_max_size ';
            echo html_writer::tag('span', $limit, array('class' => 'uploadlimit'));
            echo html_writer::link($href.'#ini.post-max-size', $icon, $params);
        }
        echo '</td>'."\n";
        echo '<td class="itemselect"></td>'."\n";
        echo '</tr>'."\n";

        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('siteuploadlimit', $plugin).':'.'</td>'."\n";
        echo '<td class="itemvalue">';
        // Site administration -> Security -> Site policies: Maximum uploaded file size
        if ($siteuploadlimit) {
            $limit = display_size($siteuploadlimit);
        } else {
            $limit = get_string('phpuploadlimit', $plugin);
            $limit = get_string('sameas', $plugin, $limit);
            $limit = html_writer::tag('i', $limit);
        }
        echo html_writer::tag('span', $limit, array('class' => 'uploadlimit'));
        if (has_capability('moodle/course:update', $sitecontext)) {
            $href = new moodle_url('/admin/settings.php', array('section' => 'sitepolicies'));
            $icon = html_writer::empty_tag('img', array('src' => $PAGE->theme->$image_url('i/settings', ''), 'title' => get_string('update')));
            echo html_writer::link($href, $icon, array('onclick' => 'this.target="_blank"'));
        }
        echo '</td>'."\n";
        echo '<td class="itemselect"></td>'."\n";
        echo '</tr>'."\n";

        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('courseuploadlimit', $plugin).':</td>'."\n";
        echo '<td class="itemvalue">';
        if ($courseuploadlimit) {
            $limit = display_size($courseuploadlimit);
        } else {
            $limit = get_string('siteuploadlimit', $plugin);
            $limit = get_string('sameas', $plugin, $limit);
            $limit = html_writer::tag('i', $limit);
        }
        echo html_writer::tag('span', $limit, array('class' => 'uploadlimit'));
        if (has_capability('moodle/course:update', $course->context)) {
            $href = new moodle_url('/course/edit.php', array('id' => $course->id));
            $icon = html_writer::empty_tag('img', array('src' => $PAGE->theme->$image_url('i/settings', ''), 'title' => get_string('update')));
            echo html_writer::link($href, $icon, array('onclick' => 'this.target="_blank"'));
        }
        echo '</td>'."\n";
        echo '<td class="itemselect"></td>'."\n";
        echo '</tr>'."\n";

        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('pluginuploadlimits', $plugin).':</td>'."\n";
        echo '<td class="itemvalue">';
        foreach ($filemods as $name => $limit) {
            if ($limit) {
                $limit = display_size($limit);
            } else {
                $limit = get_string('courseuploadlimit', $plugin);
                $limit = get_string('sameas', $plugin, $limit);
                $limit = html_writer::tag('i', $limit);
            }
            $limit = get_string('pluginname', $name).': '.$limit;
            echo html_writer::tag('span', $limit, array('class' => 'uploadlimit'));
            if ($hassiteconfig) {
                if ($name=='assign') {
                    $href = $name.'submission_file';
                } else {
                    $href = 'modsetting'.$name;
                }
                $href = new moodle_url('/admin/settings.php', array('section' => $href));
                $icon = html_writer::empty_tag('img', array('src' => $PAGE->theme->$image_url('i/settings', ''), 'title' => get_string('update')));
                echo html_writer::link($href, $icon, array('onclick' => 'this.target="_blank"'));
            }
            echo html_writer::empty_tag('br');
        }
        echo '</td>'."\n";
        echo '<td class="itemselect"></td>'."\n";
        echo '</tr>'."\n";

        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('activityuploadlimit', $plugin).':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::select($uploadlimitmenu, 'uploadlimit', $uploadlimit, '');
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('uploadlimit'), (! this.checked))";
        echo html_writer::checkbox('select_uploadlimit', 1, optional_param('select_uploadlimit', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";
    }

    // ============================
    // Active filters
    // ============================
    //
    if (count($filters)) {
        print_sectionheading(get_string('actfilterhdr', 'filters'), 'filters', true);
        foreach ($filters as $filter => $filterinfo) {
            if ($filterinfo->inheritedstate==TEXTFILTER_ON) {
                $filtermenu[TEXTFILTER_INHERIT] = $filterdefaulton;
            } else {
                $filtermenu[TEXTFILTER_INHERIT] = $filterdefaultoff;
            }
            $setting = 'filter'.$filter;
            echo '<tr>'."\n";
            echo '<td class="itemname">'.filter_get_name($filter).':</td>'."\n";
            echo '<td class="itemvalue">';
            echo html_writer::select($filtermenu, $setting, $$setting, '');
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('$setting'), (! this.checked))";
            echo html_writer::checkbox('select_'.$setting, 1, optional_param('select_'.$setting, 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }
    }

    // ============================
    // Access restrictions (Moodle >= 2.7)
    // Restrict access     (Moodle <= 2.6)
    // ============================
    //
    if ($enableavailability) {

        echo '<tr class="sectionheading" id ="id_section_availability">'."\n";
        echo '<th colspan="2">'.$str->accessrestrictions.'</th>'."\n";
        echo '<th class="toggle"></th>'."\n";
        echo '</tr>'."\n";

        echo '<tr>'."\n";
        echo '<td class="itemname removeconditions">'.get_string('removeconditions', $plugin).':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::checkbox('removeconditions', 1, $removeconditions, get_string('removeconditions_help', $plugin));
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('removeconditions'), (! this.checked), true)";
        echo html_writer::checkbox('select_removeconditions', 1, optional_param('select_removeconditions', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";

        // =====================
        // condition type
        // =====================

        if (count($conditiontypemustmenu)) {
            echo '<tr>'."\n";
            echo '<td class="itemname conditiontype">'.$str->conditiontype.':</td>'."\n";
            echo '<td class="itemvalue">';
            echo html_writer::start_tag('p');
            echo $str->conditiontype1.' '; // Student [MUST / MUST NOT]
            echo html_writer::select($conditiontypemustmenu, 'conditiontypemust', $conditiontypemust, '').' ';
            echo $str->conditiontype2.' '; // match [ANY / ALL]
            echo html_writer::select($conditiontypejoinmenu, 'conditiontypejoin', $conditiontypejoin, '').' ';
            echo $str->conditiontype3;     // of the following
            echo html_writer::end_tag('p');
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('conditiontypemust', 'conditiontypejoin'), (! this.checked))";
            echo html_writer::checkbox('select_conditiontype', 1, optional_param('select_conditiontype', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }

        // =====================
        // condition dates
        // =====================
        //
        echo '<tr>'."\n";
        echo '<td class="itemname">'.$str->datetitle.':</td>'."\n";
        echo '<td class="itemvalue">';
        $names = array();
        $i_max = count($conditiondatedirection);
        for ($i=0; $i<$i_max; $i++) {
            $conditiondatetime[$i]['minutes'] = intval($conditiondatetime[$i]['minutes']) - (intval($conditiondatetime[$i]['minutes']) % 5);
            echo html_writer::start_tag('p');
            echo html_writer::select($conditiondatedirectionmenu, "conditiondatedirection[$i]", $conditiondatedirection[$i], '').' ';
            echo html_writer::select($days,    "conditiondatetimeday[$i]",     intval($conditiondatetime[$i]['mday']),    '').' ';
            echo html_writer::select($months,  "conditiondatetimemonth[$i]",   intval($conditiondatetime[$i]['mon']),     '').' ';
            echo html_writer::select($years,   "conditiondatetimeyear[$i]",    intval($conditiondatetime[$i]['year']),    '').' ';
            echo html_writer::select($hours,   "conditiondatetimehours[$i]",   intval($conditiondatetime[$i]['hours']),   '').' ';
            echo html_writer::select($minutes, "conditiondatetimeminutes[$i]", intval($conditiondatetime[$i]['minutes']), '').' ';
            echo html_writer::end_tag('p');
            $names[] = "conditiondatedirection[$i]";
            $names[] = "conditiondatetimeday[$i]";
            $names[] = "conditiondatetimemonth[$i]";
            $names[] = "conditiondatetimeyear[$i]";
            $names[] = "conditiondatetimehours[$i]";
            $names[] = "conditiondatetimeminutes[$i]";
        }
        $names = implode("', '", $names);
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('$names'), (! this.checked))";
        echo html_writer::checkbox('select_conditiondate', 1, optional_param('select_conditiondate', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";

        // =====================
        // condition grades
        // =====================
        //
        if (count($conditiongradeitemidmenu)) {
            echo '<tr>'."\n";
            echo '<td class="itemname">'.$str->gradetitle.':</td>'."\n";
            echo '<td class="itemvalue">';
            $names = array();
            $i_max = count($conditiongradeitemid);
            for ($i=0; $i<$i_max; $i++) {
                echo html_writer::start_tag('p');
                echo html_writer::select($conditiongradeitemidmenu, 'conditiongradeitemid['.$i.']', $conditiongradeitemid[$i], '', array('class' => 'conditiongradeitemid')).' ';
                echo html_writer::empty_tag('br');
                echo $str->grademin.' ';
                echo ' <input id="id_conditiongrademin['.$i.']" type="text" name="conditiongrademin['.$i.']" size="3" value="'.$conditiongrademin[$i].'" />% ';
                echo html_writer::empty_tag('img', array('src' => $OUTPUT->$image_url('spacer'), 'class' => 'spacer', 'width' => '30px'));
                echo $str->grademax.' ';
                echo ' <input id="id_conditiongrademax['.$i.']" type="text" name="conditiongrademax['.$i.']" size="3" value="'.$conditiongrademax[$i].'" />% ';
                echo html_writer::end_tag('p');
                $names[] = 'conditiongradeitemid['.$i.']';
                $names[] = 'conditiongrademin['.$i.']';
                $names[] = 'conditiongrademax['.$i.']';
            }
            $names = implode("', '", $names);
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('$names'), (! this.checked))";
            echo html_writer::checkbox('select_conditiongrade', 1, optional_param('select_conditiongrade', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }

        // =====================
        // condition userfields
        // =====================
        //
        if (count($conditionfieldnamemenu)) {
            echo '<tr>'."\n";
            echo '<td class="itemname">'.$str->userfield.':</td>'."\n";
            echo '<td class="itemvalue">';
            $names = array();
            $i_max = count($conditionfieldname);
            for ($i=0; $i<$i_max; $i++) {
                echo html_writer::start_tag('p');
                echo html_writer::select($conditionfieldnamemenu, 'conditionfieldname['.$i.']', $conditionfieldname[$i], '', array('class' => 'conditionfieldname')).' ';
                echo html_writer::select($conditionfieldoperatormenu, 'conditionfieldoperator['.$i.']', $conditionfieldoperator[$i], '', array('class' => 'conditionfieldoperator')),' ';
                echo '<input id="id_conditionfieldvalue['.$i.']" type="text" name="conditionfieldvalue['.$i.']" size="15" value="'.$conditionfieldvalue[$i].'" />';
                echo html_writer::end_tag('p');
                $names[] = 'conditionfieldname['.$i.']';
                $names[] = 'conditionfieldoperator['.$i.']';
                $names[] = 'conditionfieldvalue['.$i.']';
            }
            $names = implode("', '", $names);
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('$names'), (! this.checked))";
            echo html_writer::checkbox('select_conditionfield', 1, optional_param('select_conditionfield', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }

        // =====================
        // condition groups
        // =====================
        //
        if (count($conditiongroupidmenu)) {
            echo '<tr>'."\n";
            echo '<td class="itemname">'.get_string('group').':</td>'."\n";
            echo '<td class="itemvalue">';
            $names = array();
            $i_max = count($conditiongroupid);
            for ($i=0; $i<$i_max; $i++) {
                echo html_writer::start_tag('p');
                echo html_writer::select($conditiongroupidmenu, 'conditiongroupid['.$i.']', $conditiongroupid[$i], '', array('class' => 'conditiongroupid'));
                echo html_writer::end_tag('p');
                $names[] = 'conditiongroupid['.$i.']';
            }
            $names = implode("', '", $names);
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('$names'), (! this.checked))";
            echo html_writer::checkbox('select_conditiongroup', 1, optional_param('select_conditiongroup', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }

        // =====================
        // condition groupings
        // =====================
        //
        if (count($conditiongroupingidmenu)) {
            echo '<tr>'."\n";
            echo '<td class="itemname">'.get_string('grouping', 'group').':</td>'."\n";
            echo '<td class="itemvalue">';
            $names = array();
            $i_max = count($conditiongroupingid);
            for ($i=0; $i<$i_max; $i++) {
                echo html_writer::start_tag('p');
                echo html_writer::select($conditiongroupingidmenu, 'conditiongroupingid['.$i.']', $conditiongroupingid[$i], '', array('class' => 'conditiongroupingid'));
                echo html_writer::end_tag('p');
                $names[] = 'conditiongroupingid['.$i.']';
            }
            $names = implode("', '", $names);
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('$names'), (! this.checked))";
            echo html_writer::checkbox('select_conditiongrouping', 1, optional_param('select_conditiongrouping', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }

        // =====================
        // conditions completion
        // of other activities
        // =====================
        //
        if (count($conditioncmidmenu)) {
            echo '<tr>'."\n";
            echo '<td class="itemname">'.$str->activitycompletion.':</td>'."\n";
            echo '<td class="itemvalue">';
            $names = array();
            $i_max = count($conditioncmid);
            for ($i=0; $i<$i_max; $i++) {
                echo html_writer::start_tag('p');
                echo html_writer::select($conditioncmidmenu, 'conditioncmid['.$i.']', $conditioncmid[$i], '', array('class' => 'conditioncmid'));
                echo html_writer::empty_tag('br');
                echo html_writer::checkbox('conditioncmungraded['.$i.']', 1, $conditioncmungraded[$i], $str->conditioncmungraded, array('class' => 'conditioncmungraded'));
                echo html_writer::empty_tag('br');
                echo html_writer::checkbox('conditioncmresources['.$i.']', 1, $conditioncmresources[$i], $str->conditioncmresources, array('class' => 'conditioncmresources'));
                echo html_writer::empty_tag('br');
                echo html_writer::checkbox('conditioncmlabels['.$i.']', 1, $conditioncmlabels[$i], $str->conditioncmlabels, array('class' => 'conditioncmlabels'));
                echo html_writer::empty_tag('br');
                echo html_writer::select($conditioncmcompletionmenu, 'conditioncmcompletion['.$i.']', $conditioncmcompletion[$i], '', array('class' => 'conditioncmcompletion'));
                echo html_writer::end_tag('p');
                $names[] = 'conditioncmid['.$i.']';
                $names[] = 'conditioncmungraded['.$i.']';
                $names[] = 'conditioncmresources['.$i.']';
                $names[] = 'conditioncmlabels['.$i.']';
                $names[] = 'conditioncmcompletion['.$i.']';
            }
            $names = implode("', '", $names);
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('$names'), (! this.checked))";
            echo html_writer::checkbox('select_conditioncm', 1, optional_param('select_conditioncm', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }

        // =====================
        // conditions actions
        // =====================
        //
        if (count($conditionactionmenu)) {
            echo '<tr>'."\n";
            echo '<td class="itemname">'.$str->showavailability.':</td>'."\n";
            echo '<td class="itemvalue">';
            $names = array();
            $i_max = count($conditionaction);
            for ($i=0; $i<$i_max; $i++) {
                echo html_writer::start_tag('p');
                echo html_writer::select($conditionactionmenu, 'conditionaction['.$i.']', $conditionaction[$i], '', array('class' => 'conditionaction'));
                echo html_writer::end_tag('p');
                $names[] = 'conditionaction['.$i.']';
            }
            $names = implode("', '", $names);
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $script = ''; // "return set_disabled(this.form, new Array('$names'), (! this.checked))";
            echo html_writer::checkbox('select_conditionaction', 1, optional_param('select_conditionaction', 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }
    }

    // ============================
    // Activity completion
    // ============================
    //
    if ($enablecompletion) {

        print_sectionheading(get_string('activitycompletion', 'completion'), 'completion', true);

        echo '<tr>'."\n";
        echo '<td class="itemname removecompletion">'.get_string('removecompletion', $plugin).':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::checkbox('removecompletion', 1, $removecompletion, get_string('removecompletion_help', $plugin));
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('removecompletion'), (! this.checked), true)";
        echo html_writer::checkbox('select_removecompletion', 1, optional_param('select_removecompletion', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";

        echo '<tr>'."\n";
        echo '<td class="itemname erasecompletion">'.get_string('erasecompletion', $plugin).':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::checkbox('erasecompletion', 1, $erasecompletion, get_string('erasecompletion_help', $plugin));
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('erasecompletion'), (! this.checked), true)";
        echo html_writer::checkbox('select_erasecompletion', 1, optional_param('select_erasecompletion', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";

        // =====================
        // completion type
        // none/manual/automatic
        // =====================
        //
        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('completion', 'completion').':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::select($completiontrackingmenu, 'completiontracking', $completiontracking, '');
        echo html_writer::empty_tag('br').'('.get_string('usedbyall', $plugin).')';
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('completiontracking'), (! this.checked))";
        echo html_writer::checkbox('select_completiontracking', 1, optional_param('select_completiontracking', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";

        // =====================
        // require view
        // =====================
        //
        //echo '<tr>'."\n";
        //echo '<td class="itemname">'.get_string('completionview', 'completion').':</td>'."\n";
        //echo '<td class="itemvalue">';
        //echo html_writer::checkbox('completionview', 1, $completionview, get_string('completionview_desc', 'completion'));
        //echo '</td>'."\n";
        //echo '<td class="itemselect">';
        //$script = ''; // "return set_disabled(this.form, new Array('completionview'), (! this.checked), true)";
        //echo html_writer::checkbox('select_completionview', 1, optional_param('select_completionview', 0, PARAM_INT), '', array('onclick' => $script));
        //echo '</td>'."\n";
        //echo '</tr>'."\n";

        // =====================
        // require grade
        // =====================
        //
        //echo '<tr>'."\n";
        //echo '<td class="itemname">'.get_string('completionusegrade', 'completion').':</td>'."\n";
        //echo '<td class="itemvalue">';
        //echo html_writer::checkbox('completiongrade', 1, $completiongrade, get_string('completionusegrade_desc', 'completion'));
        //echo '</td>'."\n";
        //echo '<td class="itemselect">';
        //$script = ''; // "return set_disabled(this.form, new Array('completiongrade'), (! this.checked), true)";
        //echo html_writer::checkbox('select_completiongrade', 1, optional_param('select_completiongrade', 0, PARAM_INT), '', array('onclick' => $script));
        //echo '</td>'."\n";
        //echo '</tr>'."\n";

        // =====================
        // completion date
        // =====================
        //
        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('completionexpected', 'completion').':</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::select_time('days',   'completionday',   $completiondate, 1).' ';
        echo html_writer::select_time('months', 'completionmonth', $completiondate, 1).' ';
        echo html_writer::select_time('years',  'completionyear',  $completiondate, 1);
        echo html_writer::empty_tag('br').'('.get_string('usedbyall', $plugin).')';
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('completionday', 'completionmonth', 'completionyear'), (! this.checked))";
        echo html_writer::checkbox('select_completiondate', 1, optional_param('select_completiondate', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";

        // =====================
        // activity-specific
        // completion settings
        // =====================
        //
        foreach ($completionfields as $name => $field) {
            $text = $field->text;
            $desc = $field->desc;
            $type = $field->type;
            if ($text==$desc) {
                $desc = '';
            }
            if (empty($field->params['name'])) {
                $fieldname = $name;
            } else {
                $fieldname = $field->params['name'];
            }
            if ($modnames = implode(', ', $field->mods)) {
                $modnames = get_string('usedby', $plugin, $modnames);
                $modnames = html_writer::tag('span', "($modnames)", array('class' => 'modnames'));
                if ($desc) {
                    $modnames = html_writer::empty_tag('br').$modnames;
                }
            }
            echo '<tr>'."\n";
            echo '<td class="itemname">'.$text.':</td>'."\n";
            echo '<td class="itemvalue">';
            switch ($type) {
                case 'checkbox':
                    echo html_writer::checkbox($name, 1, $$name, ' '.$desc.$modnames, $field->params);
                    break;
                case 'duration':
                    $options = implode('', $field->options);
                    echo $desc.' '.
                         html_writer::empty_tag('input', $field->params['number']).' '.
                         html_writer::tag('select', $options, $field->params['unit']).' '.$modnames;
                    break;
                case 'select':
                    $options = implode('', $field->options);
                    echo $desc.' '.html_writer::tag('select', $options, $field->params).$modnames;
                    break;
                case 'textbox':
                    echo $desc.' '.html_writer::empty_tag('input', $field->params).' '.$modnames;
                    break;
            }
            echo '</td>'."\n";
            echo '<td class="itemselect">';
            $fieldnames = "'$fieldname'";
            if ($type=='duration') {
                $fieldnames .= ",'".$fieldname."_unit'";
            }
            $script = ($type=='checkbox' ? 'true' : 'false'); // sync_checkbox
            $script = ''; // "return set_disabled(this.form, new Array($fieldnames), (! this.checked), $script)";
            echo html_writer::checkbox('select_'.$name, 1, optional_param('select_'.$name, 0, PARAM_INT), '', array('onclick' => $script));
            echo '</td>'."\n";
            echo '</tr>'."\n";
        }
    }

    // ============================
    // Activity competency
    // ============================
    //
    if ($enablecompetency) {

        print_sectionheading(get_string('competencies', 'competency'), 'competency', true);

        echo '<tr>'."\n";
        echo '<td class="itemname">'.get_string('uponcoursemodulecompletion', 'tool_lp').'</td>'."\n";
        echo '<td class="itemvalue">';
        echo html_writer::select($competencyrulemenu, 'competencyrule', $competencyrule, '');
        echo html_writer::empty_tag('br').'('.get_string('usedbyall', $plugin).')';
        echo '</td>'."\n";
        echo '<td class="itemselect">';
        $script = ''; // "return set_disabled(this.form, new Array('competencyrule'), (! this.checked))";
        echo html_writer::checkbox('select_competencyrule', 1, optional_param('select_competencyrule', 0, PARAM_INT), '', array('onclick' => $script));
        echo '</td>'."\n";
        echo '</tr>'."\n";
    }

    // ============================
    // Actions
    // ============================
    //
    print_sectionheading(get_string('actions'), 'actions', false);

    echo '<tr>'."\n";
    echo '<td class="itemname">&nbsp;</td>'."\n";
    echo '<td class="itemvalue">'."\n";
    echo '<input type="submit" name="apply" value="'.get_string('applysettings', $plugin).'" />'."\n";
    echo ' &nbsp; &nbsp; '."\n";
    echo '<input type="submit" name="cancel" value="'.get_string('cancel').'" />'."\n";
    echo ' &nbsp; &nbsp; '."\n";
    echo '<input type="submit" name="delete" value="'.get_string('delete').'" />'."\n";
    echo '<input type="hidden" name="id" value="'.$block_instance->id.'" />'."\n";
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />'."\n";
    echo '</td><td></td>'."\n";
    echo '</tr>'."\n";

    echo '</table>'."\n";
    echo '</form>'."\n";
}

function get_mod_fields() {
    $fields = array('availablefrom', 'availableuntil','viewablefrom', 'viewableuntil', 'maxgrade', 'rating');
    $fields = array(
        'assign'         => array_combine($fields, array('allowsubmissionsfromdate', 'duedate', '', '', 'grade', '')),
        'assignment'     => array_combine($fields, array('timeavailable', 'timedue', '', '', 'grade', '')),
        'attendance'     => array_combine($fields, array('', '', '', '', 'grade', '')),
        'choice'         => array_combine($fields, array('timeopen', 'timeclose', '', '', '', '')),
        'data'           => array_combine($fields, array('timeavailablefrom', 'timeavailableto', 'timeviewfrom', 'timeviewto', 'scale', 'assessed')),
        'englishcentral' => array_combine($fields, array('activityopen', 'activityclose', 'videoopen', 'videoclose', 'grade', '')),
        'feedback'       => array_combine($fields, array('timeopen', 'timeclose', '', '', '', '')),
        'forum'          => array_combine($fields, array('assesstimestart', 'assesstimefinish', '', '', 'scale', 'assessed')),
        'glossary'       => array_combine($fields, array('assesstimestart', 'assesstimefinish', '', '', 'scale', 'assessed')),
        'hotpot'         => array_combine($fields, array('timeopen', 'timeclose', '', '', 'grade', '')),
        'lesson'         => array_combine($fields, array('available', 'deadline', '', '', 'grade', '')),
        'questionnaire'  => array_combine($fields, array('opendate', 'closedate', '', '', 'grade', '')),
        'quiz'           => array_combine($fields, array('timeopen', 'timeclose', '', '', 'grade', '')),
        'reader'         => array_combine($fields, array('timeopen', 'timeclose', '', '', 'maxgrade', '')),
        'scorm'          => array_combine($fields, array('timeopen', 'timeclose', '', '', 'maxgrade', '')),
        'taskchain'      => array_combine($fields, array('timeopen', 'timeclose', '', '', 'gradelimit', '')),
        'workshop'       => array_combine($fields, array('submissionstart', 'submissionend', 'assessmentstart', 'assessmentend', 'grade ', ''))
    );
    return $fields;
}

function set_default_time(&$fields, &$mods, &$timestamp, &$date, $timefield, $courseid) {
    global $DB;

    $sql = array();
    foreach (array_keys($mods) as $mod) {
        if (array_key_exists($mod, $fields)) {
            if (array_key_exists($timefield, $fields[$mod])) {
                $sql[$mod] = $fields[$mod][$timefield];
            }
        }

    }
    foreach ($sql as $mod => $field) {
        $select = 'CONCAT('."'$mod-'".', id) AS modid, '.$field.' AS modtime';
        $from = '{'.$mod.'}';
        $where = 'course = ? AND '.$field.' IS NOT NULL AND '.$field.' > ?';
        $sql[$mod] = "SELECT $select FROM $from WHERE $where";
        $params[] = $courseid;
        $params[] = 0;
    }
    if ($sql = implode(') UNION (', $sql)) {
        $modtimes = array();
        if ($records = $DB->get_records_sql_menu("($sql)", $params)) {
            foreach ($records as $modid => $modtime) {
                // N: day of week (1 = Monday)
                // H: 24-hour format of an hour with leading zeros
                // i: Minutes with leading zeros
                $modtime = date('N-H-i-0', $modtime);
                if (empty($modtimes[$modtime])) {
                    $modtimes[$modtime] = 1;
                } else {
                    $modtimes[$modtime]++;
                }
            }
        }

        // Sort by value in reverse order
        // (i.e. most common value first)
        // and maintain key association.
        arsort($modtimes);

        // Select first key, if any.
        if ($modtime = key($modtimes)) {
            // Y: year as 4-digit number
            // n: month (without leading zeros)
            // j: day of month (without leading zeroes)
            list($year, $month, $day) = explode('-', date('Y-n-j', $timestamp));
            list($wday, $hours, $mins, $secs) = explode('-', $modtime);
            $timestamp = make_timestamp($year, $month, $day, $hours, $mins, $secs);
            $date[0] = $timestamp;
            $date['seconds'] = $secs;
            $date['minutes'] = $mins;
            $date['hours'] = $hours;
        }
    }
}

function format_setting($name, $value, $str,
                        $ratings, $modgradetypes, $modgradescales,
                        $gradecategories, $groupmodes, $groupings,
                        $visiblemenu, $indentmenu, $sectionmenu, $positionmenu, $uploadlimitmenu,
                        $conditiontypemustmenu, $conditiontypejoinmenu, $conditiongradeitemidmenu,
                        $conditioncmidmenu, $conditioncmcompletionmenu,
                        $conditionfieldnamemenu, $conditionfieldoperatormenu,
                        $conditiongroupidmenu, $conditiongroupingidmenu, $conditionactionmenu,
                        $completiontrackingmenu, $completionfields, $competencyrulemenu,
                        $filters, $filtermenu, $filterdefaulton, $filterdefaultoff) {

    $plugin = 'block_taskchain_navigation';
    switch ($name) {

        case 'availablefrom':
        case 'availableuntil':
            $name = get_string($name, $plugin);
            $value = ($value ? userdate($value) : get_string('disable'));
            break;

        case 'viewableuntil':
            $name = get_string('viewableuntil', $plugin);
            $value = ($value ? userdate($value) : get_string('disable'));
            break;

        case 'viewablefrom':
            $name = get_string('viewablefrom', $plugin);
            $value = ($value ? userdate($value) : get_string('disable'));
            break;

        case 'cutoff':
        case 'gradingdue':
            $name = get_string($name.'date', 'assign');
            $value = ($value ? userdate($value) : get_string('disable'));
            break;

        case 'visible':
            $name = get_string('visible');
            $value = $visiblemenu[$value];
            break;

        case 'rating':
            $name = get_string('rating', 'rating');
            $value = $ratings[$value];
            break;

        case 'modgrade':
            $name = get_string('grade', 'grades').' / '.get_string('typescale', 'grades');
            switch (true) {
                case ($value < 0): $value = $str->modgradescale.' - '.$modgradescales[abs($value)]; break;
                case ($value > 0): $value = $str->modgradepoint.' = '.$value; break;
                default:           $value = $str->modgradenone;
            }
            break;

        case 'maxgrade':
            $name = get_string('maximumgrade', $plugin);
            $value = $value.'%';
            break;

        case 'gradepass':
            $name = get_string('gradepass', 'grades');
            $value = $value.'%';
            break;

        case 'gradecat':
            $name = get_string('gradecategory', 'grades');
            $value = $gradecategories[$value];
            break;

        case 'gradeitemhidden':
            $name = get_string('gradeitemhidden', $plugin);
            $value = format_yesno($value);
            break;

        case 'extracredit':
            $name = get_string('extracredit', 'grades');
            $value = format_yesno($value);
            break;

        case 'regrade':
            $name = get_string('regrade', $plugin);
            $value = format_yesno($value);
            break;

        case 'gradeoverridden':
        case 'gradeexcluded':
        case 'gradehidden':
        case 'gradelocked':
            $name = get_string('grade', 'grades').': '.get_string(substr($name, 5), 'grades');
            $value = format_yesno($value);
            break;

        case 'gradehiddenuntil':
        case 'gradelocktime':
            $name = get_string('grade', 'grades').': '.get_string(substr($name, 5), 'grades');
            $value = ($value ? userdate($value) : get_string('disable'));
            break;

        case 'groupmode':
            $name = get_string('groupmode');
            $value = $groupmodes[$value];
            break;

        case 'groupingid':
            $name = get_string('grouping', 'group');
            $value = $groupings[$value];
            break;

        case 'groupmembersonly':
            $name = get_string('groupmembersonly', 'group');
            $value = format_yesno($value);
            break;

        case 'indent':
            $name = get_string('indent', $plugin);
            $value = $indentmenu[$value];
            break;

        case 'section':
            $name = get_string('section');
            $value = $sectionmenu[$value];
            break;

        case 'position':
            $name = get_string('position', $plugin);
            $value = $positionmenu[$value];
            break;

        case 'uploadlimit':
            $name = get_string('activityuploadlimit', $plugin);
            $value = $uploadlimitmenu[$value];
            break;

        case 'removeconditions':
            $name = get_string('removeconditions', $plugin);
            $value = format_yesno($value);
            break;

        case 'conditiontype':
            $name = $str->$name;
            $value = $str->conditiontype1.' '.
                     block_taskchain_navigation::textlib('strtoupper', $conditiontypemustmenu[$value->must]).' '.
                     $str->conditiontype2.' '.
                     block_taskchain_navigation::textlib('strtoupper', $conditiontypejoinmenu[$value->join]).' '.
                     $str->conditiontype3;
            break;

        case 'conditiondate':
            $strman = get_string_manager();
            if ($strman->string_exists('pluginname', $plugin)) {
                // Moodle >= 2.7
                $plugin = 'availability_date';
                $name = get_string('pluginname', $plugin);
                $from = 'short_from';
                $until = 'short_until';
            } else {
                // Moodle <= 2.6
                $name = get_string('availability');
                $plugin = 'condition';
                $from = 'requires_date';
                $until = 'requires_date_before';
            }
            foreach ($value as $i => $v) {
                $str = ($v->d=='>=' ? $from : $until);
                $str = get_string($str, $plugin, userdate($v->t));
                $value[$i] = html_writer::tag('p', $str);
            }
            $value = implode('', $value);
            break;

        case 'conditionfield':
            $strman = get_string_manager();
            if ($strman->string_exists('title', 'availability_grade')) {
                // Moodle >= 2.7
                $name = get_string('conditiontitle', 'availability_profile');
            } else {
                // Moodle <= 2.6
                $name = get_string('availablefrom', 'condition');
            }
            foreach ($value as $i => $v) {
                $value[$i] = html_writer::start_tag('p').
                            $conditionfieldnamemenu[$v->sf].' '.
                            $conditionfieldoperatormenu[$v->op].' '.
                            $v->v.
                            html_writer::end_tag('p');
            }
            $value = implode('', $value);
            break;

        case 'conditiongrade':
            $name = $str->gradetitle;
            foreach ($value as $i => $v) {
                $value[$i] = html_writer::start_tag('p').
                            ltrim($conditiongradeitemidmenu[$v->id], '│└ ').' '.
                            $str->grademin.' '.$v->min.'% '.
                            $str->grademax.' '.$v->max.'%'.
                            html_writer::end_tag('p');
            }
            $value = implode('', $value);
            break;

        case 'conditiongroupid':
            foreach ($value as $i => $v) {
                $value[$i] = html_writer::tag('p', $conditiongroupidmenu[$v]);
            }
            $value = implode('', $value);
            break;

        case 'conditiongroupingid':
            foreach ($value as $i => $v) {
                $value[$i] = html_writer::tag('p', $conditiongroupingidmenu[$v]);
            }
            $value = implode('', $value);
            break;

        case 'conditionaction':
            $name = get_string('display', 'form');
            foreach ($value as $i => $v) {
                $value[$i] = html_writer::tag('p', $conditionactionmenu[$v]);
            }
            $value = implode('', $value);
            break;

        case 'conditioncm':
            $name = $str->activitycompletion;
            foreach ($value as $i => $v) {
                $txt = array();
                if ($v->ungraded) {
                    $txt[] = get_string('conditioncmungraded', $plugin);
                }
                if ($v->resources) {
                    $txt[] = get_string('conditioncmresources', $plugin);
                }
                if ($v->labels) {
                    $txt[] = get_string('conditioncmlabels', $plugin);
                }
                if ($txt = implode(', ', $txt)) {
                    $txt = block_taskchain_navigation::textlib('strtolower', " ($txt)");
                }
                $value[$i] = html_writer::tag('p', $conditioncmidmenu[$v->cm]."$txt ".$conditioncmcompletionmenu[$v->e]);
            }
            $value = implode('', $value);
            break;

        case 'conditioncmlabels':
        case 'conditioncmresources':
        case 'conditioncmungraded':
            $name = get_string($name, $plugin);
            $value = format_yesno($value);
            break;

        case 'removecompletion':
        case 'erasecompletion':
            $name = get_string($name, $plugin);
            $value = format_yesno($value);
            break;

        case 'completiontracking':
            $name = get_string('completion', 'completion');
            $value = $completiontrackingmenu[$value];
            break;

        case 'completiondate':
            $name = get_string('completionexpected', 'completion');
            $value = ($value ? userdate($value) : get_string('disable'));
            break;

        case 'competencyrule':
            $name = get_string('uponcoursemodulecompletion', 'tool_lp');
            $value = $competencyrulemenu[$value];
            break;

        default:
            if (array_key_exists($name, $completionfields)) {
                $field = $completionfields[$name];
                $name = $field->text;
                switch ($field->type) {
                    case 'checkbox':
                        $value = format_yesno($value);
                        break;
                    case 'duration':
                        list($value, $unit) = convert_seconds_to_duration($value);
                        $value .= ' '.get_duration_units($unit);
                        break;
                    case 'select':
                        $i = 1;
                        $num = $value;
                        $value = array();
                        while (($ii = pow(2, $i)) && $ii <= $num) {
                            if ($ii & $num) {
                                $value[] = strip_tags($field->options[$ii]);
                            }
                            $i++;
                        }
                        $value = array_filter($value);
                        $value = implode(', ', $value);
                        break;
                    case 'textbox':
                        $value = number_format($value);
                        break;
                }
            } else if (substr($name, 0, 6)=='filter') {
                $name = substr($name, 6);
                switch ($value) {
                    case TEXTFILTER_ON:
                    case TEXTFILTER_OFF:
                        $value = $filtermenu[$value];
                        break;
                    case TEXTFILTER_INHERIT:
                        if ($filters[$name]->inheritedstate==TEXTFILTER_ON) {
                            $value = $filterdefaulton;
                        } else {
                            $value = $filterdefaultoff;
                        }
                        break;
                }
                $name = filter_get_name($name);
            }
    }
    return array($name, $value);
}

function require_head_js() {
    global $CFG, $PAGE;

    $version = '';
    $min_version = '1.11.0'; // Moodle 2.7
    $search = '/jquery-([0-9.]+)(\.min)?\.js$/';

    // make sure jQuery version is high enough
    //     Moodle 2.5 has jQuery 1.9.1
    //     Moodle 2.6 has jQuery 1.10.2
    //     Moodle 2.7 has jQuery 1.11.0
    //     Moodle 2.8 has jQuery 1.11.1
    //     Moodle 2.9 has jQuery 1.11.1
    if (method_exists($PAGE->requires, 'jquery')) {
        // Moodle >= 2.5
        if ($version=='') {
            include($CFG->dirroot.'/lib/jquery/plugins.php');
            if (isset($plugins['jquery']['files'][0])) {
                if (preg_match($search, $plugins['jquery']['files'][0], $matches)) {
                    $version = $matches[1];
                }
            }
        }
        if ($version=='') {
            $filename = $CFG->dirroot.'/lib/jquery/jquery*.js';
            foreach (glob($filename) as $filename) {
                if (preg_match($search, $filename, $matches)) {
                    $version = $matches[1];
                    break;
                }
            }
        }
        if (version_compare($version, $min_version) < 0) {
            $version = '';
        }
    }

    // plugin name and jquery path for this block
    $plugin = 'block_taskchain_navigation';
    $jquery = '/blocks/taskchain_navigation/jquery';

    // include jquery files
    if ($version) {
        // Moodle >= 2.7
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        $PAGE->requires->jquery_plugin('ui.touch-punch', $plugin);
    } else {
        // Moodle <= 2.6
        $PAGE->requires->js($jquery.'/jquery.js', true);
        $PAGE->requires->js($jquery.'/jquery-ui.js', true);
        $PAGE->requires->js($jquery.'/jquery-ui.touch-punch.js', true);
    }

    // include custom jquery for this page
    $PAGE->requires->js($jquery.'/accesscontrol.js', true);
}

function print_sectionheading($text, $sectionname, $expandable) {
    echo '<tr class="sectionheading" id="id_section_'.$sectionname.'">'."\n";
    if ($expandable) {
        echo '<th colspan="2">'.$text.'</th><th class="toggle"></th>'."\n";
    } else {
        echo '<th colspan="3">'.$text.'</th>'."\n";
    }
    echo '</tr>'."\n";
}

function print_yes_no_row($name, $label, $value) {
    echo '<tr>'."\n";
    echo '<td class="itemname">'.$label.':</td>'."\n";
    echo '<td class="itemvalue">';
    echo html_writer::select_yes_no($name, $value);
    echo '</td>'."\n";
    echo '<td class="itemselect">';
    echo html_writer::checkbox('select_'.$name, 1, optional_param('select_'.$name, 0, PARAM_INT));
    echo '</td>'."\n";
    echo '</tr>'."\n";
}

function print_date_row($name, $label, $date, $disable, $modnames='') {
    global $years, $months, $days, $hours, $minutes;

    if (empty($date)) {
        $date = time();
    }

    echo '<tr>'."\n";
    echo '<td class="itemname">'.$label.':</td>'."\n";

    echo '<td class="itemvalue">';
    $date['minutes'] = intval($date['minutes']) - (intval($date['minutes']) % 5);
    echo html_writer::select($days,    $name.'day',     intval($date['mday']),    '').' ';
    echo html_writer::select($months,  $name.'month',   intval($date['mon']),     '').' ';
    echo html_writer::select($years,   $name.'year',    intval($date['year']),    '').' ';
    echo html_writer::select($hours,   $name.'hours',   intval($date['hours']),   '').' ';
    echo html_writer::select($minutes, $name.'minutes', intval($date['minutes']), '').' ';
    echo html_writer::checkbox($name.'disable', '1', $disable, get_string('disable'));
    if ($modnames) {
        echo html_writer::empty_tag('br').'('.$modnames.')';
    }
    echo '</td>'."\n";

    echo '<td class="itemselect">';
    $checked = optional_param('select_'.$name, 0, PARAM_INT);
    echo html_writer::checkbox('select_'.$name, 1, $checked, '');
    echo '</td>'."\n";
    echo '</tr>'."\n";
}

function js($str) {
    // encode a string for javascript
    $replace_pairs = array(
        // backslashes and quotes
        '\\'=>'\\\\', "'"=>"\\'", '"'=>'\\"',
        // newlines (win = "\r\n", mac="\r", linux/unix="\n")
        "\r\n"=>'\\n', "\r"=>'\\n', "\n"=>'\\n',
        // other (closing tag is for XHTML compliance)
        "\0"=>'\\0', '</'=>'<\\/'
    );
    return strtr($str, $replace_pairs);
}

function format_yesno($value, $yes='yes', $no='no') {
    if ($value) {
        return get_string($yes);
    } else {
        return get_string($no);
    }
}

function activity_sequence_uasort($a, $b) {
    if (is_numeric($a)) {
        if (! is_numeric($b)) {
            // $a (gradebook item) goes AFTER $b (label/resource)
            return 1;
        }
    } else {
        if (is_numeric($b)) {
            // $a (label/resource) goes BEFORE $b (gradebook item)
            return -1;
        }
    }
    if ($a < $b) {
        return -1; // $a goes BEFORE $b
    }
    if ($a > $b) {
        return 1; // $a goes AFTER $b
    }
    return 0; // $a and $b are EQUAL
}

function grade_items_uasort($a, $b) {
    if ($a->sortorder < $b->sortorder) {
        return -1; // $a goes BEFORE $b
    }
    if ($a->sortorder > $b->sortorder) {
        return 1; // $a goes AFTER $b
    }
    if ($a->is_course_item()) {
        return -1;
    }
    if ($b->is_course_item()) {
        return 1;
    }
    if ($a->is_category_item()) {
        return -1;
    }
    if ($b->is_category_item()) {
        return 1;
    }
    return 0;
}

function update_course_module_completion($table, $id, $fieldname, $fieldvalue, &$updated, &$skipped, &$completion_updated) {
    global $DB;
    $params = array('id' => $id);
    if ($DB->get_field($table, $fieldname, $params)===$fieldvalue) {
        $skipped = true;
        return true;
    }
    if ($DB->set_field($table, $fieldname, $fieldvalue, $params)) {
        $completion_updated = true;
        $updated = true;
        return true;
    }
    return false; // field could not be updated
}

function update_course_module_availability($labelmods, $resourcemods, $course, $cm, $conditiontype, $new_conditions, $new_actions, &$updated, &$skipped) {
    global $DB;

    if (property_exists($cm, 'availability')) {
        // Moodle >= 2.7

        if ($availability = $DB->get_field('course_modules', 'availability', array('id' => $cm->id))) {
            $availability = json_decode($availability);
        } else {
            $availability = new stdClass();
        }

        if (! isset($availability->op)) {
            $availability->op = '&';
        }
        if (! isset($availability->c)) {
            $availability->c = array();
        }
        if (! isset($availability->showc)) {
            $availability->showc = array();
        }
        if (! isset($availability->show)) {
            $availability->show = false;
        }

        $update = false;

        $op = ($conditiontype->must==''  ? ''  : '!').
              ($conditiontype->join=='&' ? '&' : '|');

        $show = (($op=='&' || $op=='!|') ? false  : true);

        if ($availability->op != $op) {
            $availability->op = $op;
            $update = true;
        }

        if ($availability->show != $show) {
            $availability->show = $show;
            $update = true;
        }

        foreach (array_keys($new_conditions) as $i) {
            $new = clone($new_conditions[$i]);
            if (! is_valid_restriction($new, $labelmods, $resourcemods, $course, $cm)) {
                continue;
            }
            if (isset($new_actions[$i])) {
                // 0: CONDITION_STUDENTVIEW_HIDE
                // 1: CONDITION_STUDENTVIEW_SHOW
                $showc = ($new_actions[$i] ? 1 : 0);
            } else {
                $showc = 0;
            }
            $found = false;
            foreach ($availability->c as $old) {
                $params = false;
                if ($old->type==$new->type) {
                    switch ($old->type) {
                        case 'completion': $params = array('cm', 'e');          break;
                        case 'date':       $params = array('d',  't');          break;
                        case 'grade':      $params = array('id', 'min', 'max'); break;
                        case 'group':      $params = array('id');               break;
                        case 'grouping':   $params = array('id');               break;
                        case 'profile':    $params = array('sf', 'op', 'v');    break;
                    }
                }
                if ($params) {
                    $found = true;
                    foreach ($params as $param) {
                        if (isset($old->$param) && $old->$param==$new->$param) {
                            // do nothing
                        } else {
                            $found = false;
                        }
                    }
                }
                if ($found) {
                    break;
                }
            }
            if ($found==false) {
                $update = true;
                $availability->c[] = $new;
                $availability->showc[] = $showc;
            }
        }
        if ($update) {
            if ($show) {
                $availability->showc = array();
            } else {
                unset($availability->show);
                if (empty($availability->showc)) {
                    $showc = (empty($new_actions[0]) ? 0 : 1);
                    $availability->showc = array_fill(0, count($availability->c), $showc);
                }
            }
            $availability = json_encode($availability);
            if (preg_match_all('/(?<="showc":\[).*?(?=\])/', $availability, $matches, PREG_OFFSET_CAPTURE)) {
                $replace = array('0' => 'false',
                                 '1' => 'true');
                $i_max = (count($matches[0]) - 1);
                for ($i=$i_max; $i>=0; $i--) {
                    list($match, $start) = $matches[0][$i];
                    $availability = substr_replace($availability, strtr($match, $replace), $start, strlen($match));
                }
            }
            $updated = $DB->set_field('course_modules', 'availability', $availability, array('id' => $cm->id));
        } else {
            $skipped = true;
        }

    } else { // Moodle <= 2.6

        $i = 0;
        if (isset($new_actions[$i])) {
            // 0: CONDITION_STUDENTVIEW_HIDE
            // 1: CONDITION_STUDENTVIEW_SHOW
            $showc = ($new_actions[$i] ? 1 : 0);

            // update showavailability
            $table = 'course_modules';
            $fields = array('showavailability' => $showc);
            $params = array('id' => $cm->id);
            update_availability_table($table, $fields, $params, $update, false);
        }

        $update = false;
        foreach (array_keys($new_conditions) as $i) {
            $new = clone($new_conditions[$i]);
            if (! is_valid_restriction($new, $labelmods, $resourcemods, $course, $cm)) {
                continue;
            }
            switch ($new->type) {
                case 'completion':
                    $table = 'course_modules_availability';
                    $fields = array(
                        'requiredcompletion' => $new->e
                    );
                    $params = array(
                        'coursemoduleid' => $cm->id,
                        'sourcecmid'     => $new->cm,
                    );
                    update_availability_table($table, $fields, $params, $update);
                    break;

                case 'date':
                    $table = 'course_modules';
                    $field = ($new->d=='>=' ? 'availablefrom' : 'availableuntil');
                    $fields = array($field => $new->t);
                    $params = array('id' => $cm->id);
                    update_availability_table($table, $fields, $params, $update, false);
                    break;

                case 'grade':
                    $table = 'course_modules_availability';
                    $fields = array(
                        'grademin' => $new->min,
                        'grademax' => $new->max
                    );
                    $params = array(
                        'coursemoduleid' => $cm->id,
                        'gradeitemid'    => $new->id
                    );
                    update_availability_table($table, $fields, $params, $update);
                    break;

                case 'profile':
                    $table = 'course_modules_avail_fields';
                    $fields = array();
                    $params = array(
                        'coursemoduleid' => $cm->id,
                        'userfield'      => $new->sf,
                        'operator'       => $new->op,
                        'value'          => $new->v
                    );
                    update_availability_table($table, $fields, $params, $update);
                    break;
            }
        }
        if ($update) {
            $updated = true;
        } else {
            $skipped = true;
        }
    }

}
function is_valid_restriction($condition, $labelmods, $resourcemods, $course, $cm) {

    switch ($condition->type) {

        case 'completion':
            $condition->cm = fix_condition_targetid($labelmods,
                                                    $resourcemods,
                                                    $course, $cm,
                                                    $condition->cm,
                                                    $condition->ungraded,
                                                    $condition->resources,
                                                    $condition->labels, true);
            unset($condition->ungraded, $condition->resources, $condition->labels);
            return ($condition->cm ? true : false);

        case 'date':
            return ($condition->d && $condition->t);

        case 'grade':
            $condition->id = fix_condition_targetid($labelmods,
                                                    $resourcemods,
                                                    $course, $cm,
                                                    $condition->id);
            return ($condition->id ? true : false);

        case 'group':
            return ($condition->id ? true : false);

        case 'grouping':
            return ($condition->id ? true : false);

        case 'profile':
            return ($condition->sf && $condition->op);

        default:
            return true; // shouldn't happen !!
    }
}
function update_availability_table($table, $fields, $params, &$update, $add_if_missing=true) {
    global $DB;
    if ($DB->record_exists($table, $params)) {
        foreach ($fields as $field => $value) {
            if ($DB->get_field($table, $field, $params) != $value) {
                $DB->set_field($table, $field, $value, $params);
                $update = true;
            }
        }
    } else if ($add_if_missing) {
        foreach ($fields as $field => $value) {
            $params[$field] = $value;
        }
        $params = (object)$params;
        if ($params->id = $DB->insert_record($table, $params)) {
            $update = true;
        }
    }
}
function create_grade_category($course, $fullname='', $parentid=null, $aggregation=0, $aggregationcoef=0.0, $sortorder=0, $display=0) {
    global $DB;

    $time = time();
    if (is_null($parentid)) {
        // course grade item
        $depth = 1;
        $fullname = '?';
        $itemname = null;
        $itemtype = 'course';
    } else {
        // category grade item
        if (! $parent = $DB->get_record('grade_categories', array('id' => $parentid))) {
            die("Could not get parent grade_category: $fullname (parentid=$parentid)");
        }
        $depth = $parent->depth + 1;
        $itemtype = 'category';

        if (($pos = strpos($fullname, ':')) || ($pos = strpos($fullname, '?'))) {
            $itemname = substr($fullname, 0, $pos);
        } else {
            $itemname = '';
        }
    }
    if ($grade_category = $DB->get_record('grade_categories', array('courseid' => $course->id, 'depth' => $depth, 'fullname' => $fullname))) {
        // do nothing
    } else {
        $grade_category = (object)array(
            'courseid'     => $course->id,
            'parent'       => $parentid,
            'depth'        => $depth,
            'path'         => '',
            'fullname'     => addslashes($fullname),
            'aggregation'  => $aggregation,
            'timecreated'  => $time,
            'timemodified' => $time
        );
        if (! $grade_category->id = $DB->insert_record('grade_categories', $grade_category)) {
            die("Could not create grade category record: $fullname");
        }

        if (is_null($parentid)) {
            $path = "/$grade_category->id/";
        } else {
            $path = $parent->path."$grade_category->id/";
        }
        $DB->set_field('grade_categories', 'path', $path, array('id' => $grade_category->id));
    }

    // reset slashed strings
    $grade_category->fullname = $fullname;

    if ($grade_item = $DB->get_record('grade_items', array('courseid' => $course->id, 'itemtype' => 'category', 'iteminstance' => $grade_category->id))) {
        $grade_item->itemname = $itemname;
        $grade_item->aggregationcoef = $aggregationcoef;
        $grade_item->sortorder = $sortorder;
        $grade_item->display = $display;
        if (! $DB->update_record('grade_items', $grade_item)) {
            die("Could not update grade item record for category: $itemname (id=$grade_item->id)");
        }
    } else {
        $grade_item = (object)array(
            'courseid'        => $course->id,
            'itemname'        => $itemname,
            'itemtype'        => $itemtype, // e.g. 'course', 'category', 'mod'
            'iteminstance'    => $grade_category->id,
            'gradetype'       => GRADE_TYPE_VALUE,
            'aggregationcoef' => $aggregationcoef,
            'sortorder'       => $sortorder,
            'display'         => $display,
            'timecreated'     => $time,
            'timemodified'    => $time
        );
        if (! $grade_item->id = $DB->insert_record('grade_items', $grade_item)) {
            die("Could not create grade item record for category: $itemname");
        }
    }

    return $grade_category->id;
}

function get_grade_tree_char($depth, $i, $ids, $items, $categories, $chars) {
    global $DB;

    $id = $ids[$i];
    $item = $items[$id];

    if ($depth <= 1 || $item->is_course_item()) {
        return ''; // no spacer needed
    }

    $is_manual = $item->is_manual_item();
    $is_external = $item->is_external_item();
    $is_category = $item->is_category_item();

    $i_min = ($i + 1);
    $i_max = count($ids);
    for ($i = $i_min; $i < $i_max; $i++) {

        $id = $ids[$i];
        $item = $items[$id];

        if ($item->is_category_item()) {
            $categoryid = $item->iteminstance;
        } else {
            $categoryid = $item->categoryid;
        }

        if (! array_key_exists($categoryid, $categories)) {
            return $chars->error; // shouldn't happen !!
        }

        switch (true) {

            case $is_category:
                if ($item->is_category_item()) {
                    if ($categories[$categoryid]->depth == $depth) {
                        return $chars->branch;
                    }
                    if ($categories[$categoryid]->depth < $depth) {
                        return $chars->end;
                    }
                }
                break;

            case $is_manual:
            case $is_external:
                if ($item->is_external_item() || $item->is_manual_item()) {
                    return $chars->branch;
                }
                if ($item->is_category_item()) {
                    if ($categories[$categoryid]->depth > $depth) {
                        return $chars->branch;
                    }
                    return $chars->end;
                }
                break;
        }
    }

    // this is the last item at this $depth
    return $chars->end;
}

function fix_condition_targetid($labelmods, $resourcemods, $course, $cm, $targetid,
                                $includeungraded=false, $includeresources=false,
                                $includelabels=false, $requirecompletion=false)  {
    global $DB;

    if ($targetid >= 0) {
        return intval($targetid);
    }

    if (! $modinfo = get_fast_modinfo($course)) {
        return 0; // shouldn't happen !!
    }

    // set default search values
    $id = 0;
    $modname = '';
    $sectionnum = -1;
    $requiregraded = false;

    // restrict search values
    if ($targetid > 0) {
        // specific activity
        $id = $targetid;
    } else {
        if ($targetid==PREVIOUS_SAME_COURSE || $targetid==PREVIOUS_SAME_SECTION || $targetid==NEXT_SAME_COURSE || $targetid==NEXT_SAME_SECTION) {
            // same type of activity
            $modname = $cm->modname;
        }
        if ($targetid==PREVIOUS_ANY_SECTION || $targetid==PREVIOUS_SAME_SECTION || $targetid==NEXT_ANY_SECTION || $targetid==NEXT_SAME_SECTION) {
            // same section
            $sectionnum = $cm->sectionnum;
        }
        if ($includeungraded==false) {
            $requiregraded = true;
        }
    }

    // get grade info, if required (we just need to know if an activity has a grade or not)
    if ($requiregraded) {

        // basic SQL to get grade items for graded activities
        $select = 'cm.id, gi.id AS gradeitemid, gi.itemtype, gi.itemmodule, gi.iteminstance, gi.gradetype';
        $from   = '{grade_items} gi'.
                  ' LEFT JOIN {modules} m ON gi.itemmodule = m.name'.
                  ' LEFT JOIN {course_modules} cm ON m.id = cm.module AND gi.iteminstance = cm.instance';
        $where  = "gi.courseid = ? AND gi.itemtype = ? AND gi.gradetype <> ?";
        $params = array($course->id, 'mod', GRADE_TYPE_NONE);

        // restrict results to single module, if we can
        if ($modname) {
            $where .= ' AND gi.itemmodule = ?';
            $params[] = $modname;
        }

        // restrict results to current section, if we can
        if ($sectionnum >= 0) {
            $from  .= ' LEFT JOIN {course_sections} cs ON cs.id = cm.section';
            $where .= ' AND cs.section = ?';
            $params[] = $sectionnum;
        }

        $gradedcms = $DB->get_records_sql("SELECT $select FROM $from WHERE $where", $params);
    }

    // are we searching for a PREVIOUS activity (usually we are)
    $previous = ($targetid==PREVIOUS_ANY_COURSE ||
                 $targetid==PREVIOUS_ANY_SECTION ||
                 $targetid==PREVIOUS_SAME_COURSE ||
                 $targetid==PREVIOUS_SAME_SECTION);

    // get cm ids (reverse order if necessary)
    $coursemoduleids = array_keys($modinfo->cms);
    if ($previous) {
        $coursemoduleids = array_reverse($coursemoduleids);
    }

    // search for next, previous or specific course module
    $found = false;
    foreach ($coursemoduleids as $coursemoduleid) {
        if (method_exists($modinfo, 'get_cm')) {
            $coursemodule = $modinfo->get_cm($coursemoduleid);
        } else {
            $coursemodule = $modinfo->cms[$coursemoduleid];
        }
        if ($id && $coursemoduleid != $id) {
            continue; // wrong activity
        }
        if ($sectionnum >= 0) {
            if ($previous) {
                if ($coursemodule->sectionnum > $sectionnum) {
                    continue; // later section
                }
                if ($coursemodule->sectionnum < $sectionnum) {
                    return 0; // previous section
                }
            } else {
                if ($coursemodule->sectionnum < $sectionnum) {
                    continue; // earlier section
                }
                if ($coursemodule->sectionnum > $sectionnum) {
                    return 0; // later section
                }
            }
        }
        if ($requirecompletion && empty($coursemodule->completion)) {
            continue; // cm does not have completion conditions
        }
        if ($requiregraded && empty($gradedcms[$coursemoduleid])) {
            continue; // skip ungraded activity
        }
        if ($modname && $coursemodule->modname != $modname) {
            continue; // wrong module
        }
        if ($includelabels==false && in_array($coursemodule->modname, $labelmods)) {
            continue; // skip labels
        }
        if ($includeresources==false && in_array($coursemodule->modname, $resourcemods)) {
            continue; // skip resources
        }
        if ($found || $coursemoduleid==$id) {
            if (class_exists('\core_availability\info_module')) {
                // Moodle >= 2.7
                $is_visible = \core_availability\info_module::is_user_visible($coursemodule);
            } else {
                // Moodle <= 2.6
                // Indirect modification of overloaded property
                // cm_info::$availableinfo has no effect
                // lib/datalib.php on line 1588
                $is_visible = coursemodule_visible_for_user($coursemodule);
            }
            if ($is_visible) {
                if ($requirecompletion) {
                    // return cm id
                    return intval($coursemoduleid);

                } else {
                    // return grade item id
                    return intval($gradedcms[$coursemoduleid]->gradeitemid);
                }
            }
            if ($coursemoduleid==$id) {
                // required cm is not visible to this user
                return 0;
            }
        }
        if ($coursemoduleid==$cm->id) {
            $found = true;
        }
    }

    // next/prev cm not found
    return 0;
}

function get_timestamp_and_date($name, $i, $default) {
    if ($disable = optional_param($name.'disable', 0, PARAM_INT)) {
        $timestamp = 0;
        $date = $default;
    } else {
        if ($i===null) {
            $year    = optional_param($name.'year',    0, PARAM_INT);
            $month   = optional_param($name.'month',   0, PARAM_INT);
            $day     = optional_param($name.'day',     0, PARAM_INT);
            $hours   = optional_param($name.'hours',   0, PARAM_INT);
            $minutes = optional_param($name.'minutes', 0, PARAM_INT);
        } else {
            $year    = optional_param_array($name.'year',    array(), PARAM_INT);
            $month   = optional_param_array($name.'month',   array(), PARAM_INT);
            $day     = optional_param_array($name.'day',     array(), PARAM_INT);
            $hours   = optional_param_array($name.'hours',   array(), PARAM_INT);
            $minutes = optional_param_array($name.'minutes', array(), PARAM_INT);
            $year    = (empty($year[$i]) ?    0 : $year[$i]);
            $month   = (empty($month[$i]) ?   0 : $month[$i]);
            $day     = (empty($day[$i]) ?     0 : $day[$i]);
            $hours   = (empty($hours[$i]) ?   0 : $hours[$i]);
            $minutes = (empty($minutes[$i]) ? 0 : $minutes[$i]);
        }
        if ($year) {
            $seconds  = 0; // always 0
            $timezone = 99; // always 99
            $applydst = false; // always false
            $timestamp = make_timestamp($year, $month, $day, $hours, $minutes, $seconds, $timezone, $applydst);
        } else {
            $timestamp = $default;
        }
        $date = $timestamp;
    }
    return array($timestamp, usergetdate($date), $disable);
}

function get_completionfield($strman, $plugin, $modname, $name, $value, $fields) {

    // -----------------------------------------------
    // SQL to determine non-standard completion fields
    // -----------------------------------------------
    // SELECT TABLE_NAME, COLUMN_NAME
    // FROM information_schema.COLUMNS
    // WHERE TABLE_SCHEMA = 'mdl_401' AND TABLE_NAME IN (
    //     SELECT REPLACE(plugin, 'mod_', 'mdl_')
    //     FROM mdl_28.mdl_config_plugins
    //     WHERE plugin LIKE 'mod_%' AND name = 'version'
    // ) AND COLUMN_NAME LIKE 'completion%'
    // ORDER BY COLUMN_NAME, TABLE_NAME

    // most fields are NOT cm fields
    $cmfield = false;

    $options = array();
    switch (true) {

        case ($name=='completionview'):
            $cmfield = true;
            if ($strman->string_exists('completion_automatic', 'completion')) {
                // Moodle >= 4.3
                $text = get_string('completionview', $plugin);
            } else {
                // Moodle <= 4.2
                $text = get_string('completionview', 'completion');
            }
            $desc = get_string('completionview_desc', 'completion');
            $type = 'checkbox';
            break;

        case ($name=='completiongrade'):
            $cmfield = true;
            $text = get_string('completionusegrade', 'completion');
            $desc = get_string('completionusegrade_desc', 'completion');
            $type = 'checkbox';
            break;

        case ($modname=='forum'):
            // fields: discussions, replies, posts
            $text = get_string($name.'group', $modname);
            $desc = get_string($name, $modname);
            $type = 'textbox';
            break;

        case ($modname=='glossary'):
            // fields: entries
            $text = get_string($name.'group', $modname);
            $desc = get_string($name, $modname);
            $type = 'textbox';
            break;

        case ($modname=='lesson'):
            // fields: completionendreached, completiontimespent
            switch ($name) {
                case 'completiontimespent':
                    $type = 'duration';
                    $text = get_string($name.'group', $modname);
                    $desc = get_string($name.'desc', $modname, '[x]');
                    break;
                case 'completionendreached':
                default:
                    $type = 'checkbox';
                    $text = get_string($name, $modname);
                    $desc = get_string($name.'_desc', $modname);
            }
            break;

        case ($modname=='scorm'):
            // fields: statusrequired, scorerequired, statusallscos
            $text = get_string($name, $modname);
            $desc = get_string($name.'_desc', $plugin);
            switch ($name) {
                case 'completionstatusrequired':
                    $type = 'select';
                    $options = array(2 => 'passed', 4 => 'completed');
                    break;
                case 'completionscorerequired':
                    $type = 'textbox';
                    break;
                default:
                    $type = 'checkbox';
            }
            break;

        case ($name=='completionattemptsexhausted'):
            // modules: quiz
            $text = get_string($name, $plugin);
            $desc = get_string($name.'_desc', $plugin);
            $type = 'checkbox';
            break;

        case ($name=='completionpass'):
            // modules: quiz, taskchain, reader
            $text = block_taskchain_get_string($strman, $name, $modname, 'quiz');
            $desc = get_string($name.'_desc', $plugin);
            $type = 'checkbox';
            break;

        case ($name=='completionsubmit'):
            // modules: assign(ment), choice, feedback, questionnaire
            $text = get_string($name, $plugin);
            $desc = block_taskchain_get_string($strman, $name, $modname, 'assign');
            $type = 'checkbox';
            break;

        case ($name=='completionentries'):
            // modules: data, glossary
            $text = get_string($name, 'glossary');
            $desc = get_string($name.'desc', 'glossary', '[x]');
            $type = 'textbox';
            break;

        case ($name=='completiontimespent'):
            // modules: lesson
            $text = get_string($name.'group', 'lesson');
            $desc = get_string($name.'desc', 'lesson', '[x]');
            $type = 'textbox';
            break;
        default:
            // e.g. taskchain.completionmingrade
            // and taskchain.completioncompleted
            $text = get_string($name, $modname);
            $desc = '';
            if ($fields[$name]->max_length > 2) {
                $type = 'textbox';
            } else {
                $type = 'checkbox';
            }
    }

    switch ($type) {

        case 'checkbox':
            $params = array('id'     => "id_$name",
                            'class'  => 'completionfield');
            break;

        case 'duration':
            // adjust $value and set $unit
            list($value, $unit) = convert_seconds_to_duration($value);

            $options = get_duration_units();
            foreach ($options as $i => $option) {
                $params = array('value' => $i);
                if ($i==$unit) {
                    $params['selected'] = 'selected';
                }
                $options[$i] = html_writer::tag('option', $option, $params);
            }

            $params = array(
                'number' => array('id'     => 'id_'.$name,
                                  'name'   => $name,
                                  'type'   => 'text',
                                  'size'   => 4,
                                  'maxlen' => 4,
                                  'value'  => $value,
                                  'class'  => 'completionfield'),
                'unit' => array('id'       => 'id_'.$name.'_unit',
                                'name'     => $name.'_unit',
                                'class'    => 'completionfield',
                                'selected' => $unit));
            break;

        case 'select':
            foreach ($options as $i => $option) {
                $option = get_string($option, $modname);
                $params = array('value' => $i);
                if ($value & $i) {
                    $params['selected'] = 'selected';
                }
                $options[$i] = html_writer::tag('option', $option, $params);
            }
            $params = array('id'       => "id_$name",
                            'name'     => $name.'[]',
                            'multiple' => 'multiple',
                            'size'     => count($options),
                            'class'    => 'completionfield');
            break;

        case 'textbox':
            $params = array('id'     => "id_$name",
                            'name'   => $name,
                            'type'   => 'text',
                            'size'   => 4,
                            'maxlen' => 4,
                            'value'  => $value,
                            'class'  => 'completionfield');
            break;

        default:
            //echo 'Unknown completion element type: '.$type;
            //die;
    }

    return (object)array(
        'name' => $name,
        'text' => $text,
        'desc' => $desc,
        'type' => $type,
        'mods' => array(),
        'params' => $params,
        'options' => $options,
        'cmfield' => $cmfield
    );
}

function get_duration_units($unit=null) {
    $units = array(WEEKSECS => get_string('weeks'),
                   DAYSECS  => get_string('days'),
                   HOURSECS => get_string('hours'),
                   MINSECS  => get_string('minutes'),
                   1        => get_string('seconds'));
    if ($unit===null) {
        return $units;
    }
    if (array_key_exists($unit, $units)) {
        return $units[$unit];
    }
    return $unit; // unknown $unit - shouldn't happen !!
}

function convert_seconds_to_duration($seconds) {
    if (empty($seconds)) {
        return array(0, 60);
    }
    $units = get_duration_units();
    foreach ($units as $unit => $text) {
        if (($seconds % $unit)==0) {
            return array($seconds / $unit, $unit);
        }
    }
    return array($seconds, 1); // shouldn't happen !!
}

function block_taskchain_get_string($strman, $name, $modname1, $modname2) {
    if ($strman->string_exists($name, $modname2)) {
        if ($strman->string_deprecated($name, $modname2)) {
            // get the string without the "deprecated" message
            $strings = $strman->load_component_strings($modname2, current_language());
            return $strings[$name];
        } else {
            return get_string($name, $modname2);
        }
    } else {
        return get_string($name, $modname1);
    }
}
