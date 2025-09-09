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
 * blocks/taskchain_navigation/block_taskchain_navigation.php
 *
 * @package    blocks
 * @subpackage taskchain_navigation
 * @copyright  2014 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/lib/gradelib.php');

/**
 * class: block_taskchain_navigation
 *
 * @package    blocks
 * @subpackage taskchain_navigation
 * @copyright 2014 Gordon Bateson <gordon.bateson@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_taskchain_navigation extends block_base {

    /** the current group id */
    protected $groupid = null;

    /** the current grouping id */
    protected $groupingid = null;

    /**
     * Initialize the title, groupid and groupingid.
     */
    function init() {
        $this->title = get_string('blockname', 'block_taskchain_navigation');
        list($groupid, $groupingid) = $this->get_groupid();
    }

    /**
     * applicable_formats
     *
     * @return xxx
     */
    function applicable_formats() {
        return array('course' => true);
    }

    /**
     * hide_header
     *
     * @return xxx
     */
    function hide_header() {
        return empty($this->config->title);
    }

    /**
     * instance_allow_config
     *
     * @return xxx
     */
    function instance_allow_config() {
        return true;
    }

    /**
     * specialization
     */
    function specialization() {
        global $COURSE, $DB, $USER, $displaysection, $section;

        // set default config values
        $defaults = array(
            'title'               => get_string('defaulttitle', 'block_taskchain_navigation'),

            'showcourse'          => 0,
            'coursenamefield'     => 'shortname', // fullname, shortname, grade, yourgrade, coursegrade, currentgrade, or specifictext
            'coursenametext'      => '', // used when coursenamefield=="specifictext"
            'coursegradeposition' => 0, // 0=first, 1=last (i.e. below subcategories)

            'minimumdepth'        => 0,
            'maximumdepth'        => 0,
            'categoryskipempty'   => 0,
            'categoryskiphidden'  => 0,
            'categoryskipzeroweighted' => 0,
            'categorycollapse'    => 0, // 0=no, 1=yes (child name), 2=yes (parent name)

            'categoryshortnames'   => 0,
            'categoryshowweighting' => 0,
            'categorysectionnum'   => 0,
            'categoryignorechars'  => '',

            'categoryprefixlength' => 0, // fixed length prefix
            'categoryprefixchars'  => '', // chars which mark the end of the prefix
            'categoryprefixlong'   => 0, // 0=short as possible, 1=long as possible
            'categoryprefixkeep'   => 0, // 0=remove, 1=keep

            'categorysuffixlength' => 0, // fixed length suffix
            'categorysuffixchars'  => '', // chars which mark the start of the suffix
            'categorysuffixlong'   => 0, // 0=short as possible, 1=long as possible
            'categorysuffixkeep'   => 0, // 0=remove, 1=keep

            'sectionshowhidden'    => 2, // 0=hide, 1=linked text, 2=unlinked text
            'sectionshowburied'    => 0, // 0=hide, 1=promote to first visible grade category
            'sectionshowungraded'  => 0, // 0=hide, 1-4=show if (1) activities or (2) resources or (3) labels or (4) always
            'sectionshowzeroweighted'  => 0, // 0=hide, 1=show, 2=merge with ungraded sections
            'sectionshowuncategorized' => 0, // show above (0) or below (1) main grade categories

            'sectiontitletags'     => '',
            'sectionshorttitles'   => 0,
            'sectionignorecase'    => 0,
            'sectionignorechars'   => '',

            'sectionprefixlength'  => 0, // fixed length prefix
            'sectionprefixchars'   => '', // chars which mark the end of the prefix
            'sectionprefixlong'    => 0, // 0=short as possible, 1=long as possible
            'sectionprefixkeep'    => 0, // 0=remove, 1=keep

            'sectionsuffixlength'  => 0, // fixed length suffix
            'sectionsuffixchars'   => '', // chars which mark the start of the suffix
            'sectionsuffixlong'    => 0, // 0=short as possible, 1=long as possible
            'sectionsuffixkeep'    => 0, // 0=remove, 1=keep

            'gradedisplay'       => 1, // 0=raw score, 1=percent, 2=weighted, 3=letter
            'showaverages'       => 1, // 0=no, 1=yes
            'highgrade'          => 90,
            'mediumgrade'        => 60,
            'lowgrade'           => 0,
            'showactivitygrades' => '',
            'gradelinecolor'     => '666',
            'gradelinestyle'     => 'dashed',
            'gradelinewidth'     => '640px',

            'sectionjumpmenu' => 1, // 0=hide, 1=show
            'sectionnumbers'  => 1, // 0=hide, 1=show
            'singlesection'   => 0, // 0=no, 1=yes
            'defaultsection'  => 1,
            'arrowup'         => '', // previous section
            'arrowdown'       => '', // next section

            'editsettings'    => 0, // 0=hide, 1=show
            'accesscontrol'   => 0, // 0=hide, 1=show
            'gradebooklink'   => 0, // 0=hide, 1=show
            'hiddensections'  => 0, // 0=hide, 1=show
            'hiddensectionstitle' => 0, // 0=number, 1=text, 2=number and text
            'hiddensectionsstyle' => 0, // 0=checkboxes, 1=multi-select
            'namelength'      => 28, // 0=no limit
            'headlength'      => 10, // 0=no limit
            'taillength'      => 10, // 0=no limit
            'currentsection'  => 0, // 0=hide, 1=show

            'groupsmenu'      => 0, // 0=hide, 1=show groups menu
            'groupslabel'     => 0, // 0=hide, 1=show label next to groups menu
            'groupscountusers' => 0, // 0=hide 1=show count of users in each group in groups menu
            'groupssort'      => 0, // 0=name, 1=idnumber, 2=timecreated, 3=timemodified

            'loginasmenu'     => 0, // 0=hide, 1=show loginas menu
            'loginassort'     => 1, // 0=fullname, 1=firstname, 2=lastname, 3=username, 4=idnumber

            'moodlecss'       => 2, // 0=none, 1=simple view, 2=user report
            'externalcss'     => '',
            'internalcss'     => '',
        );

        if (! isset($this->config)) {
            $this->config = new stdClass();
        }

        // Fix problems with incomplete object, caused by class not existing before unserialize.
        // "The script tried to execute a method or access a property of an incomplete object."
        if (get_class($this->config)=='__PHP_Incomplete_Class') {
            $this->config = get_object_vars($this->config);
            $this->config = (object)$this->config;
            unset($this->config->__PHP_Incomplete_Class_Name);
        }

        foreach ($defaults as $name => $value) {
            if (! isset($this->config->$name)) {
                $this->config->$name = $value;
            }
        }

        // load user-defined title (may be empty)
        $this->title = $this->config->title;

        if (empty($COURSE->context)) {
            $COURSE->context = self::context(CONTEXT_COURSE, $COURSE->id);
        }

        $this->config->numsections = self::get_numsections($COURSE);

        // make sure user is only shown one course section at a time

        if (isset($USER->id) && isset($COURSE->id) && $this->config->singlesection) {
            $update = false;
            if (function_exists('course_get_display')) {
                // Moodle <= 2.2
                $displaysection = course_get_display($COURSE->id);
            } else {
                // Moodle >= 2.3
                $name = get_class($this).'_course'.$COURSE->id;
                $displaysection = get_user_preferences($name, 0);
                if ($displaysection==$section) {
                    // do nothing
                } else {
                    $displaysection = $section;
                    $update = true;
                }
            }
            if ($displaysection==0) {
                // no course section is currently selected for this user
                if ($displaysection = $this->config->defaultsection) {
                    // use default display section
                } else if ($displaysection = $COURSE->marker) {
                    // use highlighted section
                } else {
                    // use first visible section
                    $select = 'course = ? AND section > ? AND visible = ?';
                    $params = array($COURSE->id, 0, 1);
                    $displaysection = $DB->get_field_select('course_sections', 'MIN(section)', $select, $params);
                }
                $update = true;
            }
            if ($update) {
                if (function_exists('course_set_display')) {
                    // Moodle <= 2.2
                    course_set_display($COURSE->id, $displaysection);
                } else {
                    // Moodle >= 2.3
                    $name = get_class($this).'_course'.$COURSE->id;
                    set_user_preference($name, $displaysection);
                }
            }
        }

        // disable up/down arrows on Moodle >= 2.3
        if (function_exists('course_get_format')) {
            $this->config->arrowup = '';
            $this->config->arrowdown = '';
        }

        // disable hiddensections functionality, if the block is in the right column
        if (isset($this->instance->region) && $this->instance->region==BLOCK_POS_RIGHT) {
            $this->config->hiddensections = 0;
        }

        if (has_capability('moodle/course:manageactivities', $COURSE->context)) {
            $this->fix_course_format();
            $this->fix_section_visibility();
            $this->fix_course_marker();
        }

        $this->config->displaysection  = $displaysection;
        $this->config->courseformat    = $this->get_course_format($COURSE);
        $this->config->sectiontype     = $this->get_section_type();
        $this->config->coursestartdate = $COURSE->startdate;
    }

    /**
     * the method overrides the standard instance_config_save()
     * it tries to apply selected settings to similar blocks
     * in other courses in which this user can edit blocks
     *
     * @param object $data contains the new config form data
     * @param boolean $pinned (optional, default=false)
     * @return xxx
     */
    function instance_config_save($data, $pinned=false) {
        global $COURSE, $DB, $USER;

        // do nothing if user hit the "cancel" button
        if (optional_param('cancel', 0, PARAM_INT)) {
            return true;
        }

        $name = 'showactivitygrades';
        $types = $name.'types';
        switch (true) {

            case (empty($data->$name)):
                $data->$name = array();
                break;

            case ($data->$name=='all'):
                $data->$name = array('all');
                break;

            case ($data->$name=='specific'):
                if (empty($data->$types)) {
                    $data->$name = array();
                } else {
                    $data->$name = $data->$types;
                }
                break;

            default:
                $data->$name = array();
        }
        if (isset($data->$types)) {
            unset($data->$types);
        }

        $js = '';
        if (empty($data->showactivitygrades)) {
            $data->showactivitygrades = '';
        } else {
            $data->showactivitygrades = implode(',', $data->showactivitygrades);
            $js .= "<script type=\"text/javascript\">\n";
            $js .= "//<![CDATA[\n";
            $js .= "(function(){\n";
            $js .= "    var src = location.href.substr(0, location.href.indexOf('/course/'));\n";
            $js .= "    src += '/mod/taskchain/courselinks.js.php?id=';\n";
            $js .= "    src += location.href.replace(new RegExp('^.*?id=([0-9]+).*\$'), '\$1');\n";
            $js .= "    src += '&rnd=' + Math.ceil(10000 * Math.random());\n";
            $js .= "    src += '&showgrades=1';\n";
            $js .= "    src += '&showaverages={$data->showaverages}';\n";
            $js .= "    src += '&displayasblock=1';\n";
            $js .= "    src += '&mods={$data->showactivitygrades}';\n";
            $js .= "    var script = document.createElement(\"SCRIPT\");\n";
            $js .= "    script.src = src;\n";
            $js .= "    script.async = true;\n";
            $js .= "    document.head.appendChild(script);\n";
            $js .= "})();\n";
            $js .= "//]]>\n";
            $js .= "</script>\n";
        }

        $modinfo = get_fast_modinfo($COURSE, $USER->id);
        $section = self::get_section_info($modinfo, 0);
        $summary = $section->summary;

        // remove previous javascript, if any, $summary
        $search = '/<script[^>]*>.*?<\/script>[ \t]*[\r\n]*/s';
        if (preg_match_all($search, $summary, $matches, PREG_OFFSET_CAPTURE)) {
            foreach (array_reverse($matches[0]) as $match) {
                // $match: [0] = matched string, [1] = offset to start of string
                if (strpos($match[0], 'courselinks.js.php')) {
                    $summary = substr_replace($summary, '', $match[1], strlen($match[0]));
                }
            }
        }

        // append new javascript to $summary
        $summary .= $js;

        // update $section summary if necessary
        if ($summary != $section->summary) {
            $DB->set_field('course_sections', 'summary', $summary, array('id' => $section->id));
            rebuild_course_cache($COURSE->id, true);
        }

        if ($data->singlesection) {
            if (function_exists('course_get_format')) {
                // Moodle >= 2.3
                $update = false;
                if ($options = course_get_format($COURSE)->get_format_options()) {
                    if (empty($options['coursedisplay'])) {
                        $update = true;
                        $options['coursedisplay'] = 1; // COURSE_DISPLAY_MULTIPAGE;
                    }
                    if (empty($options['hiddensections'])) {
                        $update = true;
                        $options['hiddensections'] = 1; // completely invisible
                    }
                }
                if ($update) {
                    course_get_format($COURSE)->update_course_format_options($options);
                }
            }
        }

        // expand "select_sectiontextlength", if required
        if (isset($data->select_sectiontextlength)) {
            $configs = array('name', 'head', 'tail');

            $langs = get_string_manager()->get_list_of_translations();
            $langs = array_keys($langs);
            array_unshift($langs, '');

            foreach ($langs as $lang) {
                $lang = substr($lang, 0, 2);
                foreach ($configs as $config) {
                    $selectname = 'select_'.$config.'length'.$lang;
                    $data->$selectname = $data->select_sectiontextlength;
                }
            }
            unset($data->select_sectiontextlength);
        }

        $selected = array();
        $contextids = array();

        $vars = get_object_vars($data);
        foreach ($vars as $name => $value) {
            if ($name=='mycourses') {
                $contextids = $value;
                unset($data->$name);
                continue; // field is special
            }
            $selectname = 'select_'.$name;
            if (empty($_POST[$selectname])) {
                continue; // field not selected
            }
            $selected[$name] = $value;
        }
        unset($vars, $name, $value);

        // get contextids of courses (excluding this one) in which user can edit blocks
        if ($contextids = implode(',', $contextids)) {

            // get TaskChain navigation blocks in selected courses
            $select = "blockname = ? AND pagetypepattern = ? AND parentcontextid IN ($contextids)";
            $params = array($this->instance->blockname, 'course-view-*');
            if ($instances = $DB->get_records_select('block_instances', $select, $params)) {

                // user requires this capbility to update blocks
                $capability = 'block/taskchain_navigation:addinstance';

                // update values in the selected block instances
                foreach ($instances as $instance) {
                    if (class_exists('context')) {
                        $context = context::instance_by_id($instance->parentcontextid);
                    } else {
                        $context = get_context_instance_by_id($instance->parentcontextid);
                    }
                    if (has_capability($capability, $context)) {
                        $instance->config = unserialize(base64_decode($instance->configdata));
                        if (empty($instance->config)) {
                            $instance->config = new stdClass();
                        }
                        foreach ($selected as $name => $value) {
                            $instance->config->$name = $value;
                        }
                        $instance->configdata = base64_encode(serialize($instance->config));
                        $DB->set_field('block_instances', 'configdata', $instance->configdata, array('id' => $instance->id));
                    }
                }
            }
        }

        //  save config settings as usual
        return parent::instance_config_save($data, $pinned);
    }

    /**
     * get_content
     *
     * @return xxx
     */
    function get_content() {
        global $COURSE, $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = (object)array(
            'text' => '',
            'footer' => ''
        );

        if (empty($this->instance)) {
            return $this->content; // shouldn't happen !!
        }

        if (empty($COURSE)) {
            return $this->content; // shouldn't happen !!
        }

        $items = $this->get_gradeitems();
        $this->get_finalgrades($items);

        // get modinfo (used to find out which section each mod is in)
        $modinfo = get_fast_modinfo($COURSE, $USER->id);

        $sectionnums = array();
        switch ($this->config->sectionshowungraded) {
            case 1: // show if activity
            case 2: // show if activity or resource
            case 3: // show if activity or resource or label
                foreach ($modinfo->sections as $sectionnum => $cmids) {
                    if ($sectionnum==0) {
                        continue; // skip section 0
                    }
                    foreach ($cmids as $cmid) {
                        if (empty($modinfo->cms[$cmid])) {
                            continue; // shouldn't happen
                        }
                        if ($this->config->sectionshowungraded <= 2 && $modinfo->cms[$cmid]->modname=='label') {
                            continue;
                        }
                        if ($this->config->sectionshowungraded <= 1 && $modinfo->cms[$cmid]->modname=='resource') {
                            continue;
                        }
                        $sectionnums[$sectionnum] = false; // $modinfo->cms[$cmid]->uservisible
                        break;
                    }
                }
                break;
            case 4: // show even if no activity, resource or label
                $sectionnums = array_fill(1, $this->config->numsections, false);
                break;
        }

        $ungradedid = -1;
        if ($this->config->sectionshowungraded) {
            $items[$ungradedid] = $this->get_ungraded_gradeitem($ungradedid);
        }

        $courserowspan = 0;

        // process grade items in reverse order
        $ids = array_keys($items);
        foreach (array_reverse($ids) as $id) {

            switch ($items[$id]->itemtype) {
                case 'mod':
                    // get module name and instance id
                    $itemmodule = $items[$id]->itemmodule;
                    $iteminstance = $items[$id]->iteminstance;

                    // find out what section this mod is in
                    if (isset($modinfo->instances[$itemmodule][$iteminstance])) {
                        $sectionnum = $modinfo->instances[$itemmodule][$iteminstance]->sectionnum;
                        $items[$id]->sectionnum = $sectionnum;

                        $graded = true;
                        $visible = true;
                        if ($this->is_zeroweighted($items[$id])) {
                            if ($this->config->sectionshowzeroweighted==0) { // hide
                                $visible = false;
                            }
                            if ($this->config->sectionshowzeroweighted==2) { // merge
                                $graded = false;
                            }
                        }
                        if ($visible) {
                            $sectionnums[$sectionnum] = $graded;
                        }
                    }
                    break;

                case 'course':
                case 'category':
                    // get the formatted display grade
                    $this->get_displaygrade($items, $id);
                    break;
            }
        }

        // transfer ungraded sections to a seperate "ungraded" $item
        foreach ($sectionnums as $sectionnum => $graded) {
            if ($graded==false) {
                $items[$ungradedid]->sectionnums[$sectionnum] = $graded;
            }
        }

        // remove the "ungraded" $item, if it is empty
        if (isset($items[$ungradedid]) && empty($items[$ungradedid]->sectionnums)) {
            unset($items[$ungradedid]);
            $id = array_search($ungradedid, $ids);
            unset($ids[$id]);
        }

        // get info about sections that we need
        $sectioninfo = $this->format_sectioninfo($modinfo, $sectionnums);

        foreach (array_reverse($ids) as $id) {
            switch ($items[$id]->itemtype) {

                case 'course':
                    if ($this->config->showcourse || ($this->config->minimumdepth && $items[$id]->depth >= $this->config->minimumdepth)) {
                        if ($name = $this->config->coursenamefield) {
                            switch ($name) {
                                case 'fullname':
                                case 'shortname':
                                    $name = $COURSE->$name;
                                    break;
                                case 'grade':
                                case 'yourgrade':
                                    $name = get_string($name, 'grades');
                                    break;
                                case 'coursegrade':
                                case 'currentgrade':
                                    $name = get_string($name, 'block_taskchain_navigation');
                                    break;
                                case 'specifictext':
                                    $name = $this->config->coursenametext;
                                    break;
                            }
                        }
                        $items[$id]->fullname = self::filter_text($name);
                        $courserowspan++;
                    } else {
                        // don't show the course category
                        unset($items[$id]);
                    }
                    break;

                case 'category':
                    // move this grade item to its parent category
                    if ($parentid = $this->locate_grade_category($items, $id)) {

                        $show_category = true;
                        $move_category = true;
                        $move_sections = true;

                        if ($this->config->minimumdepth) {
                            if ($items[$id]->depth < $this->config->minimumdepth) {
                                $show_category = false;
                                $move_category = false;
                                $move_sections = false;
                            } else if ($items[$parentid]->depth < $this->config->minimumdepth) {
                                $move_category = false;
                            }
                        }

                        if ($this->config->maximumdepth) {
                            if ($items[$id]->depth > $this->config->maximumdepth) {
                                $show_category = false;
                                $move_category = false;
                            } else if ($items[$parentid]->depth > $this->config->maximumdepth) {
                                $move_category = false;
                            }
                        }

                        if ($this->config->categoryskipempty) {
                            if (empty($items[$id]->subgrades) && empty($items[$id]->sectionnums)) {
                                $show_category = false;
                                $move_category = false;
                                $move_sections = false;
                            }
                        }

                        // get grade category weighting - see $this->get_displaygrade()
                        $weighting = $items[$id]->displaygrade->weighting;

                        if ($this->config->categoryskipzeroweighted) {
                            // skip this category if the parent is "weighted mean of grades"
                            // and this category has a weighting of zero
                            if ($items[$parentid]->aggregation==GRADE_AGGREGATE_WEIGHTED_MEAN) {
                                if ($weighting==0.0) {
                                    $show_category = false;
                                    $move_category = false;
                                    $move_sections = false;
                                }
                            }
                        }

                        if ($this->config->categoryshowweighting && $show_category) {
                            $items[$id]->fullname = preg_replace('/\s*[0-9]+%$/', '', $items[$id]->fullname);
                            if ($items[$parentid]->aggregation==GRADE_AGGREGATE_WEIGHTED_MEAN) {
                                $items[$id]->fullname .= ' '.$this->fix_precision($weighting).'%';
                            }
                        }

                        //switch ($this->config->gradedisplay) {
                        //    case 0: // raw score
                        //        break;
                        //    case 1: // percent
                        //        break;
                        //    case 2: // weighted
                        //        break;
                        //    case 3: // grade letter
                        //        break;
                        //}

                        if ($move_category) {
                            // increment the number of rows required for subgrades of parent
                            if (empty($items[$parentid]->rowspan)) {
                                $items[$parentid]->rowspan = 1;
                            }
                            if (isset($items[$id]->rowspan)) {
                                $items[$parentid]->rowspan += $items[$id]->rowspan;
                            } else {
                                $items[$parentid]->rowspan++;
                            }

                            // copy this record to the (beginning of) the parent's subgrades
                            if (empty($items[$parentid]->subgrades)) {
                                $items[$parentid]->subgrades = array();
                            }
                            array_unshift($items[$parentid]->subgrades, $items[$id]);
                        }


                        // transfer section nums to parent grade category
                        if ($move_category==false && $show_category==false && $move_sections) {
                            if ($this->config->sectionshowburied) {
                                if (isset($items[$id]->sectionnums)) {
                                    if (empty($items[$parentid]->sectionnums)) {
                                        $items[$parentid]->sectionnums = array();
                                    }
                                    foreach ($items[$id]->sectionnums as $sectionnum => $truefalse) {
                                        $items[$parentid]->sectionnums[$sectionnum] = $truefalse;
                                    }
                                }
                            }
                        }

                        // remove $item, if it is no longer required
                        if ($move_category || $show_category==false) {
                            unset($items[$id]);
                        }

                        if ($show_category) {
                            $courserowspan++;
                        }
                    }
                    break;

                case 'mod':
                    // find out what section this mod is in
                    if (isset($items[$id]->sectionnum)) {

                        $sectionnum = $items[$id]->sectionnum;

                        // make sure we have a required section and  parent grade cateogry
                        if (empty($sectioninfo[$sectionnum])) {
                            $parentid = 0;
                        } else {
                            $parentid = $this->locate_grade_category($items, $id);
                        }
                        if ($parentid) {

                            $graded = true;
                            $visible = true;

                            // handle "zero-weighted" sections
                            if ($this->is_zeroweighted($items[$id])) {
                                if ($this->config->sectionshowzeroweighted==0) { // hide
                                    $visible = false;
                                }
                                if ($this->config->sectionshowzeroweighted==2) { // merge
                                    $parentid = $ungradedid;
                                }
                            }

                            if ($visible) {
                                // create array of sectionnums, if required
                                if (empty($items[$parentid]->sectionnums)) {
                                    $items[$parentid]->sectionnums = array();
                                }

                                // add this section number to this $item's parent
                                $items[$parentid]->sectionnums[$sectionnum] = true;
                            }
                        }
                    }

                    // remove mod record
                    unset($items[$id]);
                    break;

            } // end switch
        }

        $depths = array();
        $this->collapse_categories($items, $sectioninfo, $courserowspan, $depths);
        $this->format_categories($items, $sectioninfo, $courserowspan, $depths);
        $this->format_shortcuts($sectioninfo, $modinfo, $depths);

        if ($this->content->text) {
            $caption = '';
            $caption .= $this->get_group_menu();
            $caption .= $this->get_loginas_menu();
            if ($caption) {
                $caption = '<caption>'.$caption.'</caption>'."\n";
            }

            $this->content->text = "\n"
                .$this->get_js()
                .'<div'.$this->get_css_class(1).'>'."\n"
                .'<table'.$this->get_css_class(2).'>'."\n"
                .$caption
                .'<tbody>'."\n"
                .$this->content->text
                .'</tbody></table>'."\n"
                .'</div>'."\n"
            ;
        }

        return $this->content;
    }

    /**
     * is_zeroweighted
     *
     * @param xxx $record a "mod" grade item record
     * @return xxx true if $record is zero-weighted, false otherwise
     */
    function is_zeroweighted($record) {
        if ($record->grademax==0.0) {
            return true;
        }
        if ($this->is_extracredit($record)) {
            return true;
        }
        return false;
    }

    /**
     * get_ungraded_gradeitem
     *
     * @param xxx $id
     * @return xxx
     */
    function get_ungraded_gradeitem($id) {
        return (object)array(
            'itemtype' => 'ungraded',
            'itemmodule' => '',
            'iteminstance' => 0,
            'sortorder' => 0,
            'gradecategoryid' => 0,
            'parentgradecategoryid' => 0,
            'fullname' => get_string('sectionshowungraded', 'block_taskchain_navigation'),
            'path' => '',
            'depth' => ($this->config->showcourse ? 1 : $this->config->minimumdepth),
            'aggregation' => 0,
            'aggregationcoef' => 0,
            'gradetype' => 1,
            'grademin' => 0,
            'grademax' => 0,
            'id' => $id,
            'finalgrade' => 0,
            'sectionnums' => array()
        );
    }

    /**
     * get_course_gradeitem
     *
     * @param xxx $id
     * @return xxx
     */
    function get_course_gradeitem() {
        global $COURSE;
        return (object)array(
            'itemtype' => 'course',
            'itemmodule' => '',
            'iteminstance' => 0,
            'sortorder' => 0,
            'gradecategoryid' => 0,
            'parentgradecategoryid' => 0,
            'fullname' => self::filter_text($COURSE->shortname),
            'path' => '',
            'depth' => 1,
            'aggregation' => 0,
            'aggregationcoef' => 0,
            'gradetype' => 1,
            'grademin' => 0,
            'grademax' => 0,
            'id' => 0,
            'finalgrade' => 0,
            'sectionnums' => array(),
            'usercount' => 0,
            'rawgrade' => 0,
            'finalgrade' => 0
        );
    }

    /**
     * format_displaygrade
     *
     * @param xxx $record
     * @param xxx $href
     * @return xxx
     */
    function format_displaygrade($record, $href) {
        $text = $record->displaygrade->text;
        $class = $record->displaygrade->class;
        if ($href) {
            $text = '<a href="'.$href.'">'.$text.'</a>';
        }
        return '<div class="'.$class.'">'.$text.'</div>';
    }

    /**
     * get_siblingcount
     *
     * @param xxx $records (passed by reference)
     * @param xxx $id
     * @return xxx
     */
    function get_siblingcount(&$records, $id) {

        // get target itemtype and parentgradecategoryid
        $itemtype = $records[$id]->itemtype;
        $parentgradecategoryid = $records[$id]->parentgradecategoryid;

        // count all records with the required itemtype and parentgradecategoryid
        $count = 0;
        foreach ($records as $record) {
            if ($this->is_sibling_gradeitem($record, $itemtype, $parentgradecategoryid)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * get_siblingsumgrade
     *
     * @param xxx $records (passed by reference)
     * @param xxx $id
     * @return xxx
     */
    function get_siblingsumgrade(&$records, $id) {

        // get target itemtype and parentgradecategoryid
        $itemtype = $records[$id]->itemtype;
        $parentgradecategoryid = $records[$id]->parentgradecategoryid;

        // sum grade range for all records with the required itemtype and parentgradecategoryid
        $sumgrade = 0;
        foreach ($records as $record) {
            if ($this->is_sibling_gradeitem($record, $itemtype, $parentgradecategoryid)) {
                $sumgrade += ($record->grademax - $record->grademin);
            }
        }

        return $sumgrade;
    }

    /**
     * is_sibling_gradeitem
     *
     * @param xxx $itemtype
     * @param xxx $parentgradecategoryid
     * @param xxx $record
     * @return boolean
     */
    function is_sibling_gradeitem($record, $itemtype, $parentgradecategoryid) {
        if ($record->itemtype==$itemtype && $record->parentgradecategoryid==$parentgradecategoryid) {
            if ($this->is_extracredit($record)) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * is_extracredit
     *
     * @param xxx $record
     * @return boolean
     */
    function is_extracredit($record) {
        switch ($record->aggregation) {
            case GRADE_AGGREGATE_WEIGHTED_MEAN2:   // = 11
            case GRADE_AGGREGATE_EXTRACREDIT_MEAN: // = 12
            case GRADE_AGGREGATE_SUM:              // = 13
                return ($record->aggregationcoef > 0.0);
            default:
                return false;
        }
    }

    /**
     * get_letter
     *
     * @param xxx $num
     * @return xxx
     */
    function get_letter($num) {
        global $COURSE, $DB;

        static $letters = null;
        if (is_null($letters)) {

            $letters = array();

            $ids = explode('/', trim($COURSE->context->path, '/'));
            foreach (array_reverse($ids) as $id) {
                if ($records = $DB->get_records('grade_letters', array('contextid' => $id), 'lowerboundary DESC')) {
                    foreach ($records as $record) {
                        $letters[$record->lowerboundary] = $record->letter;
                    }
                    break;
                }
            }
        }

        foreach ($letters as $boundary => $letter) {
            if ($num >= $boundary) {
                return $letter;
            }
        }

        // no letter found - shouldn't happen !!
        return '--';
    }

    /**
     * fix_precision
     *
     * @param xxx $num
     * @return xxx
     */
    function fix_precision($num) {
        if ($num < 10) {
            $precision = 1;
        } else {
            $precision = 0;
        }
        return round($num, $precision);
    }

    /**
     * get_displaygrade
     *
     * @param xxx $records (passed by reference)
     * @param xxx $id
     * @return xxx
     */
    function get_displaygrade(&$records, $id) {

        $num = 0;
        $text = '';
        $class = '';
        $weighting = 0;

        if ($parentid = $this->locate_grade_category($records, $id)) {
            if (isset($records[$parentid]->aggregation)) {
                switch ($records[$parentid]->aggregation) {
                    case GRADE_AGGREGATE_WEIGHTED_MEAN:
                        // 10 = Weighted mean
                        $weighting = $records[$id]->aggregationcoef;
                        break;
                    case GRADE_AGGREGATE_WEIGHTED_MEAN2:
                        // 11 = Simple weighted mean
                        $weighting = (1 / $this->get_siblingcount($records, $id));
                        break;
                    case GRADE_AGGREGATE_EXTRACREDIT_MEAN:
                        // 12 = Weighted mean (with extra credit)
                        $weighting = $records[$id]->aggregationcoef;
                        break;
                    case GRADE_AGGREGATE_SUM:
                        // 13 = Natural (used to be "Sum of grades")
                        $weighting = ($records[$id]->grademax - $records[$id]->grademin);
                        $weighting = ($weighting / $this->get_siblingsumgrade($records, $id));
                        break;
                }
            }
        }

        if (isset($records[$id]->finalgrade)) {
            $text = $records[$id]->finalgrade;

            if (is_numeric($text)) {
                $num = $this->fix_precision($text);
                switch ($this->config->gradedisplay) {
                    case 0: // raw grade points
                        $text = "$num";
                        break;
                    case 1: // percent
                        $text = $num.'%';
                        break;
                    case 2: // weighted grade
                        $text = $this->fix_precision($num * $weighting).'%';
                        break;
                    case 3: // grade letter
                        $text = $this->get_letter($num);
                        break;
                }
                if ($records[$id]->usercount) {
                    $text .= ' ('.$records[$id]->usercount.')';
                }
                // scale the numeric value to the min - max range
                if ($scaled_num = ($records[$id]->grademax - $records[$id]->grademin)) {
                    $scaled_num = (($num - $records[$id]->grademin) / $scaled_num);
                    $scaled_num = $this->fix_precision(100 * $scaled_num);
                } else {
                    $scaled_num = 0;
                }
                // determine the CSS class from the scaled numeric value
                switch (true) {
                    case ($scaled_num >= $this->config->highgrade):   $class = 'highgrade';   break;
                    case ($scaled_num >= $this->config->mediumgrade): $class = 'mediumgrade'; break;
                    case ($scaled_num >= $this->config->lowgrade):    $class = 'lowgrade';    break;
                    default: $class = 'nograde';
                }
            }

        } else {
            $text = '--';
            $class = 'nograde';
        }

        $records[$id]->displaygrade = (object)array(
            'num'=>$num, 'text'=>$text, 'class'=>$class, 'weighting'=>$weighting, 'courseweighting'=>0
        );
    }

    /**
     * get_js
     *
     * @return xxx
     */
    function get_js() {
        if ($js = $this->get_js_addstyles().$this->get_js_addarrows()) {
            $js = ''
                .'<script type="text/javascript">'."\n"
                ."//<![CDATA[\n"
                .$js
                ."//]]>\n"
                .'</script>'."\n"
            ;
        }
        return $js;
    }

    /**
     * get_js_addstyles
     *
     * @return xxx
     */
    function get_js_addstyles() {
        $js = '';

        if ($this->config->moodlecss==1) { // 1=simple view, 2=user report
            //$href = new moodle_url('/grade/edit/tree/tree.css');
            //$js .= ''
            //    ."    var obj = document.createElement('link');\n"
            //    ."    obj.setAttribute('rel', 'stylesheet');\n"
            //    ."    obj.setAttribute('type', 'text/css');\n"
            //    ."    obj.setAttribute('href', '$href');\n"
            //    ."    document.getElementsByTagName('head')[0].appendChild(obj);\n"
            //;
        }

        if ($href = $this->config->externalcss) {
            $js .= ''
                ."    var obj = document.createElement('link');\n"
                ."    obj.setAttribute('rel', 'stylesheet');\n"
                ."    obj.setAttribute('type', 'text/css');\n"
                ."    obj.setAttribute('href', '".$href."');\n"
                ."    document.getElementsByTagName('head')[0].appendChild(obj);\n"
            ;
        }

        if ($css = $this->get_internalcss()) {
            $js .= ''
                ."    var txt = '$css';\n"
                ."    var obj = document.createElement('style');\n"
                ."    obj.setAttribute('type', 'text/css');\n"
                ."    if (obj.styleSheet) {\n"
                ."        obj.styleSheet.cssText = txt;\n"
                ."    } else {\n"
                ."        obj.appendChild(document.createTextNode(txt));\n"
                ."    }\n"
                ."    document.getElementsByTagName('head')[0].appendChild(obj);\n"
            ;
        }

        if ($js) {
            $js = ''
                ."function taskchain_navigation_addstyles() {\n"
                .$js
                ."}\n"
                ."taskchain_navigation_addstyles();\n"
            ;
        }

        return $js;
    }

    /**
     * get_js_addarrows
     *
     * @return xxx
     */
    function get_js_addarrows() {

        $js = '';
        $arrowup = '';
        $arrowdown = '';

        if ($this->config->displaysection && ($this->config->arrowup || $this->config->arrowdown)) {

            // encode arrows as javascript unicode
            if ($this->config->displaysection > 1) {
                $arrowup = self::textlib('utf8_to_entities', $this->config->arrowup);
                $arrowup = preg_replace('/&#x([0-9a-fA-F]+);/', '\\u\1', $arrowup);
            }

            if ($this->config->displaysection < $this->config->numsections) {
                $arrowdown = self::textlib('utf8_to_entities', $this->config->arrowdown);
                $arrowdown = preg_replace('/&#x([0-9a-fA-F]+);/', '\\u\1', $arrowdown);
            }
        }

        if ($arrowup) {
            $courseid = $this->page->course->id;
            $section = ($this->config->displaysection - 1);
            $href = new moodle_url('/course/view.php', array('id' => $courseid, 'section' => $section));
            $js .= ''
                ."        var lnk = document.createElement('a');\n"
                ."        lnk.setAttribute('href', '$href');\n"
                ."        lnk.appendChild(document.createTextNode('$arrowup'));\n"
                ."        obj.lastChild.appendChild(lnk);\n"
            ;
        }
        if ($arrowup && $arrowdown) {
            $js .= ''
                ."        obj.lastChild.appendChild(document.createElement('br'));\n"
            ;
        }
        if ($arrowdown) {
            $courseid = $this->page->course->id;
            $section = ($this->config->displaysection + 1);
            $href = new moodle_url('/course/view.php', array('id' => $courseid, 'section' => $section));
            $js .= ''
                ."        var lnk = document.createElement('a');\n"
                ."        lnk.setAttribute('href', '$href');\n"
                ."        lnk.appendChild(document.createTextNode('$arrowdown'));\n"
                ."        obj.lastChild.appendChild(lnk);\n"
            ;
        }

        if ($js) {
            $js = ''
                ."function taskchain_navigation_onload() {\n"
                ."    var obj = document.getElementById('section-".$this->config->displaysection."');\n"
                ."    if (obj && obj.lastChild) {\n"
                .$js
                ."    }\n"
                ."}\n"
                ."if (window.addEventListener) {\n"
                ."    window.addEventListener('load', taskchain_navigation_onload, false);\n"
                ."} else if (window.attachEvent) {\n"
                ."    window.attachEvent('onload', taskchain_navigation_onload)\n"
                ."}\n"
            ;
        }

        return $js;
    }

    /**
     * get_internalcss
     *
     * @return xxx
     */
    function get_internalcss() {
        $css = '';

        if (empty($this->config->sectionjumpmenu)) {
            // hide the jump menu
            $css .= 'div.jumpmenu {'."\n";
            $css .= '    display: none;'."\n";
            $css .= '}'."\n";
        }

        if (empty($this->config->sectionnumbers)) {
            // hide everything in left side of each section
            $css .= '#course-view .section .left {'."\n";
            $css .= '    visibility: hidden;'."\n";
            $css .= '    width: 0px;'."\n";
            $css .= '}'."\n";
        }

        if ($this->config->singlesection) {
            // hide first link in right side of each section
            // this should be the link to expand all sections
            $css .= '#course-view .section .right a:first-child {'."\n";
            $css .= '    display: none;'."\n";
            $css .= '}'."\n";
        }

        if ($internalcss = trim($this->config->internalcss)) {
            // add $internalcss last, so it can override the default settings
            $css .= $internalcss."\n";
        }

        if ($css) {
            // escape css and put into one line of javascript text
            $css = preg_replace('/\s+/', ' ', trim($css));
            $css = str_replace(array('\\', "'"), array('\\\\', "\\'"), $css);
        }

        return $css;
    }

    /**
     * format_shortcuts
     *
     * @param xxx $sectioninfo
     * @param xxx $modinfo
     * @param xxx $depths
     * @return xxx
     */
    function format_shortcuts($sectioninfo, $modinfo, $depths) {
        global $COURSE, $PAGE, $USER;

        $sections = self::get_section_info_all($modinfo);

        $rows = array();
        if ($this->config->gradebooklink) {
            if ($this->can_grade_viewall()) {
                // teacher
                $viewgrades = true;
            } else {
                // student
                $viewgrades = ($COURSE->showgrades && $this->can_grade_view());
            }
            if ($viewgrades) {
                $href = new moodle_url('/grade/report/index.php', array('id' => $COURSE->id));
                $rows[] = '<a href="'.$href.'">'.get_string('showgradedetails', 'block_taskchain_navigation').'</a>';
            }
        }
        if (has_capability('moodle/course:manageactivities', $COURSE->context)) {
            if ($this->config->accesscontrol) {
                $href = new moodle_url('/blocks/taskchain_navigation/accesscontrol.php', array('id' => $this->instance->id));
                $rows[] = '<a href="'.$href.'">'.get_string('accesscontrolpage', 'block_taskchain_navigation').'</a>';
            }
            if ($this->config->editsettings && $PAGE->user_can_edit_blocks()) { // user_allowed_editing
                $params = array('id' => $COURSE->id, 'bui_editid' => $this->instance->id);
                $href = new moodle_url('/course/view.php', $params);
                if (empty($USER->editing)) {
                    // Edit mode is currently off, so we need to switch it on and redirect.
                    $href = $href->out_as_local_url(false);
                    $params = array('id' => $COURSE->id, 'edit' => 1, 'sesskey' => sesskey(), 'return' => $href);
                    $href = new moodle_url('/course/view.php', $params);
                }
                $rows[] = '<a href="'.$href.'">'.get_string('editsettings', 'moodle').'</a>';
            }
            if ($this->config->hiddensections) {
                $content = ''
                    .'<script type="text/javascript">'."\n"
                    ."//<![CDATA[\n"
                    ."function set_hidden_sections(tagname, hiddensections) {\n"
                    ."    var s = 'form.hiddensectionsform ' + tagname + '[name^=hiddensection]';\n"
                    ."    var obj = document.querySelectorAll(s);\n"
                    ."    for (var i=0; i<obj.length; i++) {\n"
                    ."        switch (obj[i].type) {\n"
                    ."            case 'checkbox':\n"
                    ."            case 'radio':\n"
                    ."                var ii = parseInt(obj[i].name.substr(14));\n"
                    ."                if (typeof(hiddensections)=='string') {\n"
                    ."                    obj[i].checked = (hiddensections=='all' ? true : false);\n"
                    ."                } else {\n"
                    ."                    obj[i].checked = (hiddensections[ii] ? true : false);\n"
                    ."                }\n"
                    ."                break;\n"
                    ."            case 'select':\n"
                    ."            case 'select-multiple':\n"
                    ."                for (var ii=0; ii<obj[i].options.length; ii++) {\n"
                    ."                    if (typeof(hiddensections)=='string') {\n"
                    ."                        obj[i].options[ii].selected = (hiddensections=='all' ? true : false);\n"
                    ."                    } else {\n"
                    ."                        obj[i].options[ii].selected = (hiddensections[ii+1] ? true : false);\n"
                    ."                    }\n"
                    ."                }\n"
                    ."                break;\n"
                    ."        }\n"
                    ."    }\n"
                    ."}\n"
                    ."//]]>\n"
                    ."</script>\n"
                ;
                if ($this->config->hiddensectionsstyle==0) {
                    // Note: $tagname needs to be uppercase
                    $tagname = 'INPUT'; // checkbox or radio
                } else {
                    $tagname = 'SELECT';
                    $size = min(6, count($sections)-1);
                    $content .= '<select name="hiddensection[]" multiple="multiple" size="'.$size.'">';
                }
                $reset = array();
                $hide_all = true;
                $show_all = true;
                foreach ($sections as $sectionnum => $section) {
                    if ($sectionnum==0) {
                        continue;
                    }
                    if ($sectionnum > $this->config->numsections) {
                        continue;
                    }
                    if ($section->visible) {
                        $class = '';
                        $selected = '';
                        $hide_all = false;
                    } else {
                        $class = ' class="hiddensection"';
                        if ($this->config->hiddensectionsstyle==0) {
                            $selected = ' checked="checked"';
                        } else {
                            $selected = ' selected="selected"';
                        }
                        $show_all = false;
                        $reset[] = $sectionnum;
                    }
                    // hiddensectionstitle: 0=number, 1=text, 2=number and text
                    if ($this->config->hiddensectionstitle==0) {
                        $tag = 'span';
                        $style = ' style="white-space: nowrap;"';
                        $text2 = '';
                    } else { // 1, 2
                        $tag = 'div';
                        if (isset($sectioninfo[$sectionnum])) {
                            $text2 = $sectioninfo[$sectionnum]->text;
                        } else {
                            $text2 = $this->get_sectiontext($modinfo, $sectionnum);
                        }
                        $text2 = $this->trim_name($text2);
                        $style = '';
                    }
                    if ($this->config->hiddensectionstitle==0 || $this->config->hiddensectionstitle==2) {
                        $text1 = $sectionnum;
                    } else {
                        $text1 = '';
                    }
                    // hiddensectionsstyle: 0=checkboxes, 1=multi-select menu
                    if ($this->config->hiddensectionsstyle==0) {
                        $content .= ' <'.$tag.$style.'>'.$text1.'<input type="checkbox" name="hiddensection['.$sectionnum.']" value="'.$sectionnum.'"'.$selected.$class.' />'.$text2.'</'.$tag.'>';
                    } else {
                        if ($this->config->hiddensectionstitle==2) {
                            $text1 .= '. ';
                        }
                        $content .= '<option value="'.$sectionnum.'"'.$selected.$class.'>'.$text1.$text2.'</option>';
                    }
                }
                if ($this->config->hiddensectionsstyle==1) {
                    $content .= '</select>';
                }
                if ($hide_all || $show_all) {
                    $reset = '';
                } else {
                    $reset = '{'.implode(':true,', $reset).':true}'; // JSON object / associative array()
                    $reset = ''
                        .'<a href="'."javascript:set_hidden_sections('$tagname',$reset);".'">'.get_string('reset').'</a>'
                        .' / '
                    ;
                }
                if ($content) {
                    $href = new moodle_url('/course/view.php', array('id' => $COURSE->id));
                    $rows[] = ''
                        .'<form class="hiddensectionsform" method="post" action="'.$href.'"><div>'
                        .'<input type="hidden" name="sesskey" value="'.sesskey().'" />'
                        .'<input type="hidden" name="hiddensections" value="1" />'
                        .'<input class="submitbutton" type="submit" value="'.get_string('go').'" />'
                        .get_string('hiddensections', 'block_taskchain_navigation').': '
                        .'<div class="hiddensectionscheckboxes">'
                        .$content
                        .' &nbsp; '
                        .'<span class="allresetnone">'
                        .'<a href="'."javascript:set_hidden_sections('$tagname','all');".'">'.get_string('all').'</a>'
                        .' / '
                        .$reset
                        .'<a href="'."javascript:set_hidden_sections('$tagname','none');".'">'.get_string('none').'</a>'
                        .'</span>'
                        .'</div>'
                        .'</div></form>'."\n"
                    ;
                }
            }
            if ($this->config->currentsection) {
                $content = '';
                for ($sectionnum=0; $sectionnum<=$this->config->numsections; $sectionnum++) {
                    if ($sectionnum==$COURSE->marker) {
                        $class = ' class="currentsection"';
                        $selected = ' selected="selected"';
                    } else {
                        $class = '';
                        $selected = '';
                    }
                    $content .= '<option value="'.$sectionnum.'"'.$selected.$class.'>'.$sectionnum.'</option>';
                }
                if ($content) {
                    $href = new moodle_url('/course/view.php', array('id' => $COURSE->id));
                    $rows[] = ''
                        .'<form class="currentsectionform" method="post" action="'.$href.'"><div>'
                        .'<input type="hidden" name="sesskey" value="'.sesskey().'" />'
                        .'<input class="submitbutton" type="submit" value="'.get_string('go').'" />'
                        .get_string('currentsection', 'block_taskchain_navigation').': '
                        .'<select name="marker">'.$content.'</select>'
                        .'</div></form>'."\n"
                    ;
                }
            }
        }
        if (count($rows)) {
            $colspan = count($depths) + 3;
            $td = '<td colspan="'.$colspan.'" style="padding-left: 6px; padding-right: 2px;">';
            $this->content->text .= '<tr>'.$td.implode('</td></tr><tr>'.$td, $rows).'</td></tr>'."\n";
        }
    }

    /**
     * format_categories
     *
     * @param xxx $records (passed by reference)
     * @param xxx $sectioninfo (passed by reference)
     * @param xxx $courserowspan
     * @param xxx $depths
     * @param xxx $parentname (optional, default='')
     * @return xxx
     */
    function format_categories(&$records, &$sectioninfo, $courserowspan, $depths, $parentname='') {

        if (empty($records)) {
            return false;
        }

        // map absolute depth relative depth
        $depths = array_keys($depths);
        $depths = array_flip($depths);
        $maxdepth = count($depths);

        $categoryprefixlen = null;
        $categorysuffixlen = null;

        if ($this->config->categoryshortnames) {
            foreach ($records as $record) {
                $len = $this->count_matching_chars($record->fullname, $parentname, true);
                if (is_null($categoryprefixlen) || $len < $categoryprefixlen) {
                    $categoryprefixlen = $len;
                }
                $len = $this->count_matching_chars($record->fullname, $parentname, false);
                if (is_null($categorysuffixlen) || $len < $categorysuffixlen) {
                    $categorysuffixlen = $len;
                }
            }
        }

        $oddrow = true;
        $oddeven = '';

        if ($this->config->moodlecss==2) {
            $th = 'td'; // user grade report
        } else {
            $th = 'th'; // simple view
        }

        foreach ($records as $record) {

            if ($record->itemtype=='mod') {
                continue;
            }

            if ($oddrow) {
                $oddrow = false;
                $oddeven = 'odd';
            } else {
                $oddrow = true;
                $oddeven = 'even';
            }

            $this->content->text .= '<tr'.$this->get_css_class(3, $oddeven, $record).'>'."\n";

            if ($record->itemtype=='course') {
                $record->rowspan = $courserowspan;
            }

            if ($record->itemtype=='ungraded') {
                // don't print LH cushion cell
                unset($record->rowspan);
            } else {
                if (empty($record->rowspan) && $depths[$record->depth] < $maxdepth) {
                    $record->rowspan = 1;
                }
                if (isset($record->rowspan)) {
                    if ($record->rowspan > 1) {
                        $rowspan = ' rowspan="'.$record->rowspan.'"';
                    } else {
                        $rowspan = '';
                    }
                    $this->content->text .= '<'.$th.$rowspan.$this->get_css_class(4, $oddeven, $record).'></'.$th.'>'."\n";
                }
            }

            if ($record->itemtype=='ungraded') {
                // the padding, name and score cells will be merged
                $colspan = $maxdepth + 2;
            } else if ($record->itemtype=='course' && $this->config->minimumdepth>1) {
                // set colspan for course grade category
                $colspan = $maxdepth + 1;
            } else {
                $colspan = ($maxdepth - $depths[$record->depth]);
            }

            if ($colspan > 1) {
                $colspan = ' colspan="'.$colspan.'"';
            } else {
                $colspan = '';
            }
            $rowspan = '';

            // set grade category name and href
            $sectionnum = 0;
            $categoryname = '';
            $categoryhref = '';
            if ($record->itemtype=='ungraded') {
                // do nothing - i.e. don't show a category name
            } else {
                $categoryname = $record->fullname;
                $categoryname = $this->fix_prefix_suffix($categoryname, 'category', $categoryprefixlen, $categorysuffixlen);

                // link grade category to section, if required
                if ($this->config->categorycollapse && empty($record->subgrades) && isset($record->sectionnums) && count($record->sectionnums)==1) {

                    // $sectionnum is the get first (and only) key in $record->sectionnums
                    $sectionnums = array_keys($record->sectionnums);
                    $sectionnum = array_shift($sectionnums);

                    if (isset($sectioninfo[$sectionnum])) {
                        if ($this->config->categorycollapse==1) {
                            $categoryname = $this->get_section_text($sectioninfo, $sectionnum);
                        } else {
                            $categoryname = $this->get_section_link($sectioninfo, $sectionnum, $categoryname);
                        }
                        if ($this->config->categorysectionnum) {
                            $categoryname = "$sectionnum. $categoryname";
                        }
                        $categoryhref = $sectioninfo[$sectionnum]->href;
                    }

                    // prevent display of section
                    unset($record->sectionnums);
                }
            }

            $this->content->text .= '<td'.$colspan.$this->get_css_class(5, $oddeven, $record, $sectionnum).'>';
            $this->content->text .= $categoryname;

            // format section links, if required
            if (isset($record->sectionnums)) {
                $this->format_sectionlinks($sectioninfo, $record, $oddeven);
            }

            $this->content->text .= '</td>'."\n";

            // format category grade
            if ($record->itemtype=='ungraded') {
                // do nothing
            } else {
                $displaygrade = $this->format_displaygrade($record, $categoryhref);
                $this->content->text .= '<td'.$rowspan.$this->get_css_class(6, $oddeven, $record).'>'.$displaygrade.'</td>'."\n";
            }

            $this->content->text .= '</tr>'."\n";

            if (isset($record->subgrades)) {
                $this->format_categories($record->subgrades, $sectioninfo, $courserowspan, $depths, $record->fullname);
            }
        }
    }

    /**
     * format_sectionlinks
     *
     * @param xxx $sectioninfo (passed by reference)
     * @param xxx $record (passed by reference)
     * @param xxx $oddeven
     * @return xxx
     */
    function format_sectionlinks(&$sectioninfo, &$record, $oddeven) {

        if (empty($record->sectionnums)) {
            return false;
        }

        $sectionnums = array_keys($record->sectionnums);
        sort($sectionnums);

        $prefixlen = null;
        $suffixlen = null;

        if ($this->config->sectionshorttitles) {
            foreach($sectionnums as $sectionnum) {

                if (isset($sectioninfo[$sectionnum])) {
                    $text = $sectioninfo[$sectionnum]->text;

                    $len = $this->count_matching_chars($record->fullname, $text, true, $this->config->sectionignorecase);
                    if (is_null($prefixlen) || $len < $prefixlen) {
                        $prefixlen = $len;
                    }

                    $text = self::textlib('substr', $text, $len); // remove prefix

                    $len = $this->count_matching_chars($record->fullname, $text, false, $this->config->sectionignorecase);
                    if (is_null($suffixlen) || $len < $suffixlen) {
                        $suffixlen = $len;
                    }
                }
            }
        }

        $this->content->text .= '<ul'.$this->get_css_class(7, $oddeven, $record).'>'."\n";
        foreach($sectionnums as $sectionnum) {
            $this->content->text .= ''
                .'<li'.$this->get_css_class(8, $oddeven, $record, $sectionnum).'>'
                .$this->get_section_text($sectioninfo, $sectionnum, $prefixlen, $suffixlen)
                .'</li>'."\n"
            ;
        }
        $this->content->text .= '</ul>'."\n";
    }

    /**
     * collapse_categories
     *
     * @param xxx $records (passed by reference)
     * @param xxx $sectioninfo (passed by reference)
     * @param xxx $courserowspan (passed by reference)
     * @param xxx $depths (passed by reference)
     * @return xxx
     */
    function collapse_categories(&$records, &$sectioninfo, &$parentrowspan, &$depths) {
        $ids = array_keys($records);
        foreach ($ids as $id) {

            // save original rowspan of this record
            if (isset($records[$id]->rowspan)) {
                $rowspan = $records[$id]->rowspan;
            }

            $depth = $records[$id]->depth;
            if ($this->config->minimumdepth==0 || $depth >= $this->config->minimumdepth) {
                if ($this->config->maximumdepth==0 || $depth <= $this->config->maximumdepth) {
                    $depths[$depth] = true;
                }
            }

            if ($this->config->categorycollapse) {
                if (empty($records[$id]->sectionnums)) {
                    if (isset($records[$id]->subgrades) && count($records[$id]->subgrades)==1) {
                        $subgrade = array_shift($records[$id]->subgrades);
                        if ($this->config->categorycollapse==1) {
                            $records[$id]->fullname = $subgrade->fullname;
                        }
                        if (isset($subgrade->sectionnums)) {
                            $records[$id]->sectionnums = $subgrade->sectionnums;
                        }
                        if (isset($subgrade->subgrades)) {
                            $records[$id]->subgrades = $subgrade->subgrades;
                        }
                        $records[$id]->rowspan--;
                    }
                }
            }

            if (isset($records[$id]->subgrades)) {
                $this->collapse_categories($records[$id]->subgrades, $sectioninfo, $records[$id]->rowspan, $depths);
            }

            // decrement parent rowspan by the amount that this record's rowspan has changed
            if (isset($records[$id]->rowspan)) {
                $parentrowspan -= ($rowspan - $records[$id]->rowspan);
            }
        }
    }

    /**
     * get_css_class
     *
     * @param int $type
     *     1 : outer DIV
     *     2 : main TABLE
     *     3 : category TR
     *     4 : category rowspan TH
     *     5 : category name TD
     *     6 : category score TD
     *     7 : sections UL (requires $sectionnum > 0)
     *     8 : section LI (requires $sectionnum > 0)
     * @param xxx $oddeven (optional, default='')
     * @param xxx $record (optional, default=null)
     * @param xxx $sectionnum (optional, default=0)
     * @return xxx
     */
    function get_css_class($type, $oddeven='', $record=null, $sectionnum=0) {
        static $showcurrentlocation = true;
        $class = array();

        switch ($type) {
            case 1: // outer DIV
                switch ($this->config->moodlecss) {
                    case 1: array_push($class, 'gradetreebox'); break;
                    case 2: break;
                }
                break;
            case 2: // main TABLE
                switch ($this->config->moodlecss) {
                    case 1: array_push($class, 'generaltable'); break;
                    case 2: array_push($class, 'generaltable', 'user-grade'); break;
                }
                break;
            case 3: // category TR
                switch ($this->config->moodlecss) {
                    case 1:
                        // convert record path to class selectors for parent records
                        //     $record->path: /106/551/552/559/
                        //     $class: class="category c106 c551 c552"
                        $str = substr($record->path, 0, strrpos($record->path, '/', -2));
                        array_push($class, 'category'.str_replace('/', ' c', $str));
                        break;
                    case 2: break;
                }
                if ($record->itemtype=='ungraded') {
                    array_push($class, 'ungraded');
                }
                break;
            case 4: // category rowspan TH
                switch ($this->config->moodlecss) {
                    case 1: array_push($class, 'cell', 'rowspan', 'level'.$record->depth); break;
                    case 2: array_push($class, $oddeven.'d'.$record->depth, 'b1l', 'b1t', 'b1b', 'rowspan'); break;
                }
                break;
            case 5: // category name TD
                switch ($this->config->moodlecss) {
                    case 1:
                        array_push($class, 'cell', 'name');
                        if ($record->itemtype != 'ungraded') {
                            array_push($class, 'level'.$record->depth);
                        }
                        break;
                    case 2:
                        if ($record->itemtype != 'ungraded') {
                            array_push($class, $oddeven.'d'.$record->depth);
                        }
                        array_push($class, 'b1t', 'b1b', 'name');
                        if ($record->itemtype=='course' || isset($record->subgrades) || (isset($record->rowspan) && $record->rowspan==1)) {
                            // don't show left border
                        } else {
                            // show border-left
                            array_push($class, 'b1l');
                            if ($record->itemtype=='ungraded') {
                                // the ungraded row has only one-cell
                                // so it needs both left and right styles
                                array_push($class, 'b1r');
                            }
                        }
                        break;
                }
                if ($sectionnum && $sectionnum==$this->config->displaysection && $showcurrentlocation) {
                    //$showcurrentlocation = false;
                    array_push($class, 'currentsection');
                }
                break;
            case 6: // category score TD
                switch ($this->config->moodlecss) {
                    case 1: array_push($class, 'cell', 'last', 'level'.$record->depth); break;
                    case 2: array_push($class, $oddeven.'d'.$record->depth, 'b1t', 'b1b', 'b1r', 'last'); break;
                }
                break;
            case 7: // sections UL
                if ($record->itemtype=='ungraded') {
                    array_push($class, 'ungraded');
                }
                break;
            case 8: // section LI
                if ($sectionnum && $sectionnum==$this->config->displaysection && $showcurrentlocation) {
                    //$showcurrentlocation = false;
                    array_push($class, 'currentsection');
                }
                break;
        }

        if ($class = implode(' ', $class)) {
            $class = ' class="'.$class.'"';
        }

        return $class;
    }

    /**
     * get_course_format
     *
     * @param  object $course
     * @return string course format
     */
    public function get_course_format($course) {
        if (isset($course->format)) {
            return $course->format;
        } else {
            $params = array('courseid' => $course->id);
            return $DB->get_field('course_format_options', 'format', $params);
        }
    }



    /**
     * get_section_type
     *
     * @return string course format
     */
    public function get_section_type() {
        switch ($this->config->courseformat) {
            case 'weeks' : return 'week';
            case 'topics': return 'topic';
            default      : return 'section';
            // "scorm", "social", or a custom format
            // "singleactivity" or "formatlegacy"
        }
    }

    /**
     * get_section_text
     *
     * @param xxx $sectioninfo (passed by reference)
     * @param xxx $sectionnum
     * @param xxx $prefixlen (optional, default=null)
     * @param xxx $suffixlen (optional, default=null)
     * @return xxx
     */
    function get_section_text(&$sectioninfo, $sectionnum, $prefixlen=null, $suffixlen=null) {
        $text = '';
        if (isset($sectioninfo[$sectionnum])) {
            $text = $sectioninfo[$sectionnum]->text;
            $text = $this->fix_prefix_suffix($text, 'section', $prefixlen, $suffixlen);
        }
        return $this->get_section_link($sectioninfo, $sectionnum, $text);
    }

    /**
     * get_section_link
     *
     * @param xxx $sectioninfo (passed by reference)
     * @param xxx $sectionnum
     * @param xxx $text
     * @return xxx
     */
    function get_section_link(&$sectioninfo, $sectionnum, $text) {
        if ($text=='') {
            $text = $this->get_default_sectiontext($sectionnum);
        }
        if (isset($sectioninfo[$sectionnum])) {
            if ($class = $sectioninfo[$sectionnum]->class) {
                $text = '<span'.$class.'>'.$text.'</span>';
            }
            if ($title = strip_tags($sectioninfo[$sectionnum]->text)) {
                $title = ' title="'.$title.'"';
            }
            if ($href = $sectioninfo[$sectionnum]->href) {
                $text = '<a href="'.$href.'"'.$title.'>'.$text.'</a>';
            }
        }
        return $text;
    }

    /**
     * fix_prefix_suffix
     *
     * @param xxx $text
     * @param xxx $type "section" or "category"
     * @param xxx $prefixlen (optional, default=0) length of prefix that matches parent and sibling records
     * @param xxx $suffixlen (optional, default=0) length of suffix that matches parent and sibling records
     * @return xxx
     */
    function fix_prefix_suffix($text, $type, $prefixlen=0, $suffixlen=0) {
        static $search = null;
        static $replace = null;

        // set up $search and $replace (first time only)
        if (is_null($search)) {
            $search = new stdClass();
            $replace = new stdClass();
        }

        $prefixkeep = $type.'prefixkeep';
        $suffixkeep = $type.'suffixkeep';

        // set up $search->$type and $replace->$type (first time only)
        if (! isset($search->$type)) {

            $search->$type = array();
            $replace->$type = array();

            $prefixchars = $type.'prefixchars';
            $prefixlong  = $type.'prefixlong';

            if ($chars = $this->config->$prefixchars) {
                $chars = implode('|', array_map('preg_quote', self::str_split($chars), array('/')));
                if ($this->config->$prefixlong) {
                    array_push($search->$type, "/^(.*)($chars)(.*?)$/u");
                } else {
                    array_push($search->$type, "/^(.*?)($chars)(.*)$/u");
                }
                if ($this->config->$prefixkeep) {
                    array_push($replace->$type, '\1');
                } else {
                    array_push($replace->$type, '\3');
                }
            }

            $suffixchars = $type.'suffixchars';
            $suffixlong  = $type.'suffixlong';

            if ($chars = $this->config->$suffixchars) {
                $chars = implode('|', array_map('preg_quote', self::str_split($chars), array('/')));
                if ($this->config->$suffixlong) {
                    array_push($search->$type, "/^(.*?)($chars)(.*)$/u");
                } else {
                    array_push($search->$type, "/^(.*)($chars)(.*?)$/u");
                }
                if ($this->config->$suffixkeep) {
                    array_push($replace->$type, '\3');
                } else {
                    array_push($replace->$type, '\1');
                }
            }

            $ignorechars = $type.'ignorechars';
            if ($chars = $this->config->$ignorechars) {
                $chars = implode('|', array_map('preg_quote', self::str_split($chars), array('/')));
                array_push($search->$type, "/$chars/u");
                array_push($replace->$type, '');
            }

            if (empty($search->$type)) {
                $search->$type = false;
                $replace->$type = false;
            }
        }

        $prefixlength = $type.'prefixlength';
        if ($this->config->$prefixlength) {
            $prefixlen = $this->config->$prefixlength;
        }

        $suffixlength = $type.'suffixlength';
        if ($this->config->$suffixlength) {
            $suffixlen = $this->config->$suffixlength;
        }

        if ($prefixlen) {
            if ($this->config->$prefixkeep) {
                $text = self::textlib('substr', $text, 0, $prefixlen);
            } else {
                $len = self::textlib('strlen', $text) - $prefixlen;
                $text = self::textlib('substr', $text, $prefixlen, $len);
            }
        }

        if ($suffixlen) {
            $len = self::textlib('strlen', $text) - $suffixlen;
            if ($this->config->$suffixkeep) {
                $text = self::textlib('substr', $text, $len, $suffixlen);
            } else {
                $text = self::textlib('substr', $text, 0, $len);
            }
        }

        if ($search->$type) {
            $text = preg_replace($search->$type, $replace->$type, $text);
        }

        return $text;
    }

    /**
     * get_sectiontext
     *
     * @param xxx $modinfo
     * @param xxx $sectionnum
     * @return xxx
     */
    function get_sectiontext($modinfo, $sectionnum) {

        $text = '';
        if ($section = self::get_section_info($modinfo, $sectionnum)) {
            if (isset($section->name)) {
                $text = self::filter_text($section->name);
            }
            if ($text=='') {
                $text = self::filter_text($section->summary);
                if ($tags = trim($this->config->sectiontitletags)) {
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
            }
        }
        if ($text=='') {
            $text = $this->get_default_sectiontext($sectionnum);
        }
        return $text;
    }

    /**
     * format_sectioninfo
     *
     * @param xxx $modinfo
     * @param xxx $sectionnums
     * @return xxx
     */
    function format_sectioninfo($modinfo, $sectionnums) {

        $sectioninfo = array();

        $sections = self::get_section_info_all($modinfo);
        if (empty($sections)) {
            $sections = array(); // shouldn't happen !!
        }

        $courseid = $modinfo->get_course_id();

        $canviewhiddensections = $this->can_course_viewhiddensections();

        foreach ($sections as $sectionnum => $section) {

            if ($sectionnum==0 || ! array_key_exists($sectionnum, $sectionnums)) {
                continue;
            }

            $text = $this->get_sectiontext($modinfo, $sectionnum);

            $class = array();

            // check section visibility
            if ($canviewhiddensections) {
                $uservisible = true;
            } else if (get_class($section)=='section_info') {
                $uservisible = $section->uservisible;
            } else {
                $uservisible = $section->visible;
            }

            // sectionshowhidden: 0=hide, 1=show linked text, 2=show unlinked text
            if ($uservisible) {
                $showlink = true;
                $showtext = true;
            } else {
                $class[] = 'dimmed_text';
                $showtext = ($this->config->sectionshowhidden >= 1);
                $showlink = ($this->config->sectionshowhidden == 1);
            }

            if ($showlink) {
                $href = new moodle_url('/course/view.php', array('id'=>$courseid));
                if ($this->config->singlesection) {
                    $href->param('section', $sectionnum);
                } else {
                    $href->set_anchor("section-$sectionnum");
                }
            } else {
                $href = '';
            }

            if ($class = implode(' ', $class)) {
                $class = ' class="'.$class.'"';
            }

            if ($showtext) {
                $sectioninfo[$sectionnum] = (object)array('text'=>$text, 'href'=>$href, 'class'=>$class, 'graded'=>$sectionnums[$sectionnum]);
            }
        }

        return $sectioninfo;
    }

    /**
     * get_default_sectiontext
     *
     * @param int $sectionnum
     * @return xxx
     */
    function get_default_sectiontext($sectionnum) {
        if ($this->config->sectiontype=='week' && $sectionnum > 0) {
            $dateformat = get_string('strftimedateshort');
            $date = $this->config->coursestartdate + 7200 + (($sectionnum - 1) * 604800);
            return userdate($date, $dateformat).' - '.userdate($date + 518400, $dateformat);
        }

        $strman = get_string_manager();
        $format = 'format_'.$this->config->courseformat;

        $string = 'section'.$sectionnum.'name';
        if ($strman->string_exists($string, $format)) {
            return get_string($string, $format);
        }

        $string = 'sectionname';
        if ($strman->string_exists($string, $format)) {
            return get_string($string, $format).' '.$sectionnum;
        }

        // default section name, e.g. Topic 1
        $string = $this->config->sectiontype;
        if ($strman->string_exists($string, 'moodle')) {
            $string = get_string($string, 'moodle');
        }
        return $string.' '.$sectionnum;
    }

    /**
     * can_grade_view
     *
     * @return boolean true if user can view their own grades, false otherwise
     */
    function can_grade_view() {
        global $COURSE;
        $capability = 'moodle/grade:view';
        return has_capability($capability, $COURSE->context);
    }

    /**
     * can_grade_viewall
     *
     * @return boolean true if user can view all student grades, false otherwise
     */
    function can_grade_viewall() {
        global $COURSE;
        $capability = 'moodle/grade:viewall';
        return has_capability($capability, $COURSE->context);
    }


    /**
     * can_course_viewhiddensections
     *
     * @return boolean true if user can view hidden course sections, false otherwise
     */
    function can_course_viewhiddensections() {
        global $COURSE;
        $capability = 'moodle/course:viewhiddensections';
        return has_capability($capability, $COURSE->context);
    }

    /**
     * get_gradeitems
     */
    function get_gradeitems() {
        global $COURSE, $DB;

        $categories = array();

        $select = 'id, categoryid, itemtype, itemmodule, iteminstance, sortorder, '.
                  'grademax, grademin, aggregationcoef, gradetype, scaleid';
        // "aggregationcoef" is the "Extra credit" setting.
        // It is not available for ALL grade types of "aggregation".
        // Its precise meaning depends on the "aggregation" of the parent category.
        //     Simple Weighted Mean of Grades
        //     Natural (=Sum of Grades)
        //         =0 : no effect
        //         >0 : grade is added to mean
        //     Mean of Grades (with Extra Credit)
        //         =0 : no effect
        //         >0 : grade is multiplied by this number and added to mean

        // possible values for "gradetype":
        //     GRADE_TYPE_NONE  (0 : None  - no grading possible)
        //     GRADE_TYPE_VALUE (1 : Value - numeric within grade max and min)
        //     GRADE_TYPE_SCALE (2 : Scale - a item in from a scale list)
        //     GRADE_TYPE_TEXT  (3 : Text  - feedback only)

        $from = '{grade_items}';
        $where = 'courseid = ? AND gradetype <> ?';
        $params = array($COURSE->id, GRADE_TYPE_NONE);
        if ($this->config->categoryskiphidden) {
            $where .= ' AND hidden = ?';
            $params[] = 0;
        }
        $order = 'sortorder';

        if ($items = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params)) {
            foreach ($items as $id => $item) {
                if ($item->itemtype=='course' || $item->itemtype=='category') {
                    $categoryid = $item->iteminstance;
                } else if ($item->itemtype=='mod') {
                    $categoryid = $item->categoryid;
                } else {
                    $categoryid = 0;
                }
                if ($categoryid) {
                    $categories[$categoryid] = true;
                    $items[$id]->gradecategoryid = $categoryid;
                } else {
                    unset($items[$id]); // shouldn't happen !!
                }
            }
        } else {
            $items = array(); // no grade items - unusual !!
        }

        if (count($categories)) {
            $select = 'id, parent, fullname, path, depth, aggregation';
            $from   = '{grade_categories}';
            list($where, $params) = $DB->get_in_or_equal(array_keys($categories));
            $categories = $DB->get_records_sql("SELECT $select FROM $from WHERE id $where", $params);
        }

        foreach ($items as $id => $item) {
            if (empty($categories[$item->gradecategoryid])) {
                unset($items[$id]); // shouldn't happen !!
            } else {
                $category = $categories[$item->gradecategoryid];
                $items[$id]->parentgradecategoryid = $category->parent;
                $items[$id]->fullname = $category->fullname;
                $items[$id]->depth = $category->depth;
                $items[$id]->path = $category->path;
                if (isset($category->aggregation)) {
                    $items[$id]->aggregation = $category->aggregation;
                }
            }
        }

        return $items;
    }
    /**
     * get_finalgrades
     *
     * @param xxx $items (passed by reference)
     * @return xxx
     */
    function get_finalgrades(&$items) {
        global $CFG, $COURSE, $DB, $USER;

        if (! $itemids = implode(',', array_keys($items))) {
            return false; // no grade items - unusual !!
        }

        $showgrades = true;
        $showaverages = false;

        if ($this->can_grade_viewall()) {
            // teacher (or admin)
            $showaverages = $this->config->showaverages;
        } else {
            // student
            if (empty($COURSE->showgrades)) {
                $showgrades = false;
            }
            if (! $this->can_grade_view()) {
                $showgrades = false;
            }
        }

        if ($showgrades==false) {
            foreach ($items as $id => $item) {
                $items[$id]->usercount = 0;
                $items[$id]->rawgrade = '';
                $items[$id]->finalgrade = '';
            }
            return;
        }

        if ($showaverages) {
            // a teacher - or at least someone who can view all users' grades

            $select = 'itemid,'
                     .'SUM(rawgrade) AS sum_rawgrade,'
                     .'COUNT(rawgrade) AS count_rawgrade,'
                     .'SUM(finalgrade) AS sum_finalgrade,'
                     .'COUNT(finalgrade) AS count_finalgrade';
            $from    = '{grade_grades}';
            $where   = "itemid IN ($itemids)";
            $groupby = 'GROUP BY itemid';

            // get active groupid for this course during this $SESSION
            list($groupid, $groupingid) = $this->get_groupid();

            // get groupmode: 0=NOGROUPS, 1=VISIBLEGROUPS, 2=SEPARATEGROUPS
            $groupmode = groups_get_course_groupmode($COURSE);

            if ($groupmode==NOGROUPS || $groupmode==VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $COURSE->context)) {
                // user can access all users and groups in the course
                $mygroupsonly = false;
            } else {
                // user can only see members in groups to which (s)he belongs
                // (e.g. non-editing teacher when groups are separate)
                $mygroupsonly = true;
            }

            if ($mygroupsonly || $groupid || $groupingid) {
                // select only users from specific group(s) in this course
                $groupids = "SELECT id FROM {$CFG->prefix}groups WHERE courseid=$COURSE->id";
                if ($groupid) {
                    $groupids .= " AND id =$groupid";
                } else if ($groupingid) {
                    $groupids .= " AND id IN (SELECT groupid FROM {$CFG->prefix}groupings_groups WHERE groupingid = $groupingid)";
                }
                if ($mygroupsonly) {
                    $groupids = "SELECT groupid FROM {$CFG->prefix}groups_members WHERE userid=$USER->id AND groupid IN ($groupids)";
                }
                $where .= " AND userid IN (SELECT DISTINCT userid FROM {$CFG->prefix}groups_members WHERE groupid IN ($groupids))";
            } else {
                // select all students in this course
                $roleid = "SELECT id FROM {$CFG->prefix}role WHERE shortname='student'";
                if ($role_assignments = $DB->get_records_select_menu('role_assignments', "roleid=($roleid) AND contextid=".$COURSE->context->id, null, '', 'id,userid')) {
                    if ($userids = implode(',', array_values($role_assignments))) {
                        $where .= " AND userid IN ($userids)";
                    }
                }
            }
        } else {
            // show only the current user's grades (e.g. student)
            $select = 'id,itemid,rawgrade,finalgrade';
            $from    = '{grade_grades}';
            $where   = "itemid IN ($itemids) AND userid = $USER->id";
            $groupby = '';
        }

        if ($grades = $DB->get_records_sql("SELECT $select FROM $from WHERE $where $groupby")) {
            foreach ($grades as $grade) {
                $id = $grade->itemid;
                if (isset($grade->count_finalgrade)) {
                    $items[$id]->usercount  = $grade->count_finalgrade;
                    $items[$id]->rawgrade   = $grade->sum_rawgrade / max(1, $grade->count_rawgrade);
                    $items[$id]->finalgrade = $grade->sum_finalgrade / max(1, $grade->count_finalgrade);
                } else {
                    $items[$id]->usercount  = 0;
                    $items[$id]->rawgrade   = $grade->rawgrade;
                    $items[$id]->finalgrade = $grade->finalgrade;
                }
            }
        }
    }

    /**
     * locate_grade_category
     *
     * @param xxx $records (passed by reference)
     * @param xxx $id
     * @return xxx
     */
    function locate_grade_category(&$records, $id) {

        if ($records[$id]->itemtype=='course') {
            return 0;
        }

        $gradecategoryids = explode('/', trim($records[$id]->path, '/'));
        if ($records[$id]->itemtype=='category') {
            array_pop($gradecategoryids);
        }
        $gradecategoryids = array_reverse($gradecategoryids);

        foreach ($gradecategoryids as $gradecategoryid) {
            foreach ($records as $record) {
                if ($record->itemtype=='mod') {
                    continue;
                }
                if ($record->gradecategoryid==$gradecategoryid) {
                    return $record->id;
                }
            }
            // no direct parent gradecategory - shouldn't happen
            // however, we keep going and see if there is a grandparent
        }

        // ancestor gradecategory not found - defintely shouldn't happen !!
        return 0;
    }

    /**
     * count_matching_chars
     *
     * @param $a string
     * @param $b string
     * @param $forward bool start from the beginning (true) or end (false) of the string
     * @param $ignorecase bool (optional, default=false) ignore (true) or detect (false) differences in case
     * @return int
     */
    function count_matching_chars($a, $b, $forward, $ignorecase=false) {

        $a_len = self::textlib('strlen', $a);
        if ($a_len==0) {
            return 0;
        }

        $b_len = self::textlib('strlen', $b);
        if ($b_len==0) {
            return 0;
        }

        if ($ignorecase) {
            $a = self::textlib('strtoupper', $a);
            $b = self::textlib('strtoupper', $b);
        }

        if ($a_len==$b_len && $a==$b) {
            return $a_len;
        }

        $i = 0;
        $i_max = min($a_len, $b_len);

        if ($forward) {
            while ($i<$i_max && self::textlib('substr', $a, $i, 1)==self::textlib('substr', $b, $i, 1)) {
                $i++;
            }
        } else {
            while ($i<$i_max && self::textlib('substr', $a, ($a_len - $i - 1), 1)==self::textlib('substr', $b, ($b_len - $i - 1), 1)) {
                $i++;
            }
        }
        return $i;
    }

    /**
     * fix_course_format
     */
    function fix_course_format() {
        global $COURSE;
        if (function_exists('course_get_format')) {
            // Moodle >= 2.3
            $update = false;
            $options = course_get_format($COURSE)->get_format_options();
            if ($this->config->singlesection) {
                if (empty($options['coursedisplay'])) {
                    $options['coursedisplay'] = 1; // one section per page
                    $update = true;
                }
                if (empty($options['hiddensections'])) {
                    $options['hiddensections'] = 1; // completely hide
                    $update = true;
                }
            } else {
                if (isset($options['coursedisplay']) && $options['coursedisplay']==1) {
                    $options['coursedisplay'] = 0; // all sections on one page
                    $update = true;
                }
            }
            if ($update) {
                course_get_format($COURSE)->update_course_format_options($options);
            }
        }
    }

    /**
     * fix_section_visibility
     */
    function fix_section_visibility() {
        global $CFG, $COURSE, $DB, $USER, $modinfo, $mods;

        $modinfo = get_fast_modinfo($COURSE, $USER->id);
        $sections = self::get_section_info_all($modinfo);

        if (empty($sections)) {
            return false;
        }

        if (! $hiddensections = optional_param('hiddensections', 0, PARAM_INT)) {
            return false;
        }
        $hiddensection = optional_param_array('hiddensection', array(), PARAM_INT);

        if ($this->config->hiddensectionsstyle==1) {
            // multi-select menu
            $hiddensection = array_flip($hiddensection);
            foreach ($hiddensection as $sectionnum => $i) {
                $hiddensection[$sectionnum] = true;
            }
        }

        $hide_section_ids = array();
        $show_section_ids = array();

        foreach ($sections as $sectionnum => $section) {
            if ($sectionnum==0) {
                continue; // shoudn't happen !!
            }
            if ($sectionnum > $this->config->numsections) {
                continue; // section is not visible on course page
            }
            if (empty($hiddensection[$sectionnum])) {
                if ($section->visible==0) {
                    // section is hidden but teacher wants it to be visible
                    $sections[$sectionnum]->visible = 1;
                    $show_section_ids[] = $section->id;
                }
            } else {
                if ($section->visible==1) {
                    // section is visble but teacher wants it to be hidden
                    $sections[$sectionnum]->visible = 0;
                    $hide_section_ids[] = $section->id;
                }
            }
        }

        $rebuild_course_cache = false;

        if ($ids = implode(',', $hide_section_ids)) {
            $select = "course=$COURSE->id AND id IN ($ids)";
            $DB->set_field_select('course_sections', 'visible', 0, $select);

            $update = "{$CFG->prefix}course_modules";
            $set    = "visibleold=visible, visible=0";
            $where  = "course=$COURSE->id AND section IN ($ids)";
            $DB->execute("UPDATE $update SET $set WHERE $where");

            $rebuild_course_cache = true;
        }

        if ($ids = implode(',', $show_section_ids)) {
            $select = "course=$COURSE->id AND id IN ($ids)";
            $DB->set_field_select('course_sections', 'visible', 1, $select);

            $update = "{$CFG->prefix}course_modules";
            $set    = "visible=visibleold";
            $where  = "course=$COURSE->id AND section IN ($ids)";
            $DB->execute("UPDATE $update SET $set WHERE $where");

            $rebuild_course_cache = true;
        }

        if ($rebuild_course_cache) {
            rebuild_course_cache($COURSE->id);

            if (isset($COURSE->modinfo)) {
                $COURSE->modinfo = $DB->get_field('course', 'modinfo', array('id' => $COURSE->id));
            }

            if (isset($modinfo)) {
                $modinfo = get_fast_modinfo($COURSE, $USER->id);
            }

            if (isset($mods)) {
                foreach ($mods as $id => $mod) {
                    if (in_array($mod->section, $hide_section_ids)) {
                        if ($mod->visible==1) {
                            $mods[$id]->visibleold = $mods[$id]->visible;
                            $mods[$id]->visible = 0;
                        }
                    }
                    if (in_array($mod->section, $show_section_ids)) {
                        if ($mod->visible==0) {
                            $mods[$id]->visible = $mods[$id]->visibleold;
                        }
                    }
                }
            }
        }
    }

    /**
     * trim_name
     *
     * @param xxx $name
     * @return
     */
    function trim_name($name) {
        list($namelength, $headlength, $taillength) = $this->get_namelength();
        return self::trim_text($name, $namelength, $headlength, $taillength);
    }

    /**
     * get_namelength
     *
     * @return array($namelength, $headlength, $taillength)
     */
    function get_namelength() {
        static $namelength = null;
        static $headlength = null;
        static $taillength = null;

        if (is_null($namelength)) {
            $lang = $this->get_lang_code();

            $namelength = 'namelength'.$lang;
            $headlength = 'headlength'.$lang;
            $taillength = 'taillength'.$lang;

            // get name length details for this language
            $namelength = $this->config->$namelength; // 28
            $headlength = $this->config->$headlength; // 10
            $taillength = $this->config->$taillength; // 10

            // hiddensectionsstyle: 0=checkboxes, 1=multi-select menu
            if ($this->config->hiddensectionsstyle==1) {
                $namelength -= 4;
                $headlength -= 1;
                $taillength -= 1;
            }

            // hiddensectionstitle: 0=number, 1=text, 2=number and text
            if ($this->config->hiddensectionstitle==2) {
                $namelength -= 4;
                $headlength -= 3;
            }

            if ($namelength < 0) {
                $namelength = 0;
            }
            if ($headlength < 0) {
                $headlength = 0;
            }
            if ($taillength < 0) {
                $taillength = 0;
            }
        }

        return array($namelength, $headlength, $taillength);
    }

    /**
     * get_lang_code
     *
     * @return string
     */
    function get_lang_code() {
        static $lang = null;

        if (isset($lang)) {
            return $lang;
        }

        $lang = substr(current_language(), 0, 2);

        $namelength = 'namelength'.$lang;
        if (isset($this->config->$namelength)) {
            return $lang;
        }

        $lang = 'en';

        $namelength = 'namelength'.$lang;
        if (isset($this->config->$namelength)) {
            return $lang;
        }

        $lang = '';
        return $lang;
    }

    /**
     * fix_course_marker
     */
    function fix_course_marker() {
        global $COURSE;

        // get default marker value
        $marker = (empty($COURSE->marker) ? 0 : $COURSE->marker);

        // get marker value from form
        $marker = optional_param('marker', $marker, PARAM_INT);

        // update if necessary
        if ($marker==$COURSE->marker) {
            // do nothing
        } else {
            if (has_capability('moodle/course:setcurrentsection', $this->page->context)) {
                $COURSE->marker = $marker;
                course_set_marker($COURSE->id, $marker);
            }
        }
    }

    /**
     *
     */
    function get_group_menu() {
        global $CFG, $COURSE, $DB, $USER;

        if (! $this->config->groupsmenu) {
            return ''; // group menu not required
        }

        if (! $this->config->showaverages) {
            return ''; // grade averages not required
        }

        if (! $this->can_grade_viewall()) {
            return ''; // user is a student
        }

        $groupmode = groups_get_course_groupmode($COURSE);

        if ($groupmode == NOGROUPS) {
            return ''; // no groups in this course
        }

        // Get all groupings and groups for this course.
        $gdata = groups_get_course_data($COURSE->id);

        // Map: group_id => count of groupings it belongs to.
        $groupids = [];
        if (isset($gdata->groups)) {
            $groupids = array_fill_keys(array_keys($gdata->groups), 0);
        }

        // Map: grouping_id => array of group_ids in that grouping.
        $groupingids = [];
        if (isset($gdata->groupings)) {
            $groupingids = array_fill_keys(array_keys($gdata->groupings), []);
        }

        // Build membership & counts.
        if (isset($gdata->mappings)) {
            foreach ($gdata->mappings as $mapping) {
                $groupid = (int)$mapping->groupid;
                $groupingid = (int)$mapping->groupingid;

                // Increment how many groupings this group belongs to.
                if (! isset($groupids[$groupid])) {
                    $groupids[$groupid] = 0; // safety, in case of odd data
                }
                $groupids[$groupid]++;

                // Add this group to its parent grouping list.
                if (! isset($groupingids[$groupingid])) {
                    $groupingids[$groupingid] = []; // safety
                }
                $groupingids[$groupingid][] = $groupid;
            }
        }

        // Detect any groups that are not in any grouping (shouldn't be any).
        $groupingids[0] = array_keys(array_filter($groupids, function ($count) {
            return ($count === 0);
        }));
        unset($groupids); // No longer needed.

        // Remove all empty groupings (those with no member groups).
        $groupingids = array_filter($groupingids, function ($groupids) {
            return (count($groupids));
        });

        // If there is only one grouping, we don't show groupnames.
        if (count($groupingids) <= 1) {
            $showgroupingname = false;
        } else {
            $showgroupingname = true;
        }

        $menu = '';
        list($targetgroupid, $targetgroupingid) = $this->get_groupid();

        foreach ($groupingids as $groupingid => $groupids) {

            if ($showgroupingname) {
                $name = $gdata->groupings[$groupingid]->name;
                $params = ['value' => -$groupingid, 'class' => 'text-bold'];
                if ($groupingid == $targetgroupingid) {
                    $params['selected'] = 'selected';
                }
                $menu .= \html_writer::tag('option', $name, $params);
            }
            $lastgroupid = end($groupids);

            foreach ($groupids as $groupid) {
                $name = $gdata->groups[$groupid]->name;
                if ($showgroupingname) {
                    if ($groupid == $lastgroupid) {
                        $name = '└ '.$name;
                    } else {
                        $name = '├ '.$name;
                    }
                }
                $params = ['value' => $groupid];
                if ($groupid == $targetgroupid) {
                    $params['selected'] = 'selected';
                }
                $menu .= \html_writer::tag('option', $name, $params);
            }
        }

        if ($menu) {
            // Enclose the menu options in a SELECT element.
            $params = [
                'name' => 'groupmenu',
                'id' => 'id_groupmenu',
                'onchange' => 'this.form.submit();'
            ];
            $menu = \html_writer::tag('select', $menu, $params);

            // Prepend the Group label, if required.
            if ($this->config->groupslabel) {
                $menu = get_string('group').': '.$menu;
            }

            // Append a "Go" button.
            $params = [
                'id' => 'id_groupsubmit',
                'class' => 'submitbutton',
                'type' => 'submit',
                'value' => get_string('go'),
            ];
            $menu .= \html_writer::empty_tag('input', $params);

            // Hide Go button if JS is available.
            $menu .= ''
                .'<script type="text/javascript">'."\n"
                .'//<![CDATA['."\n"
                .'document.getElementById("id_groupsubmit").style.display = "none";'."\n"
                .'//]]>'."\n"
                .'</script>'."\n"
            ;

            // Enclose the menu in a DIV element.
            $menu = \html_writer::tag('div', $menu);

            // Enclose the DIV element in a FORM element.
            $url = new moodle_url('/course/view.php', array('id' => $COURSE->id));
            if ($section = optional_param('section', 0, PARAM_INT)) {
                $url->param('section', $section);
            }
            $params = ['class' => 'groupmenu', 'method' => 'post', 'action' => "$url"];
            $menu = \html_writer::tag('form', $menu, $params);
        }

        return $menu;
    }

    /**
     * get_loginas_menu
     */
    function get_loginas_menu() {
        global $CFG, $COURSE, $DB, $USER;

        if (! $this->config->loginasmenu) {
            return ''; // loginas menu not required
        }

        if (isset($USER->realuser) && $USER->realuser) {
            return ''; // we are already logged in as someone else
        }

        if (! has_capability('moodle/user:loginas', $COURSE->context)) {
            return ''; // cannot "loginas" - probably as student
        }

        $select = self::get_userfields();
        $from   = $CFG->prefix.'user';
        $where  = '';
        $orderby  = 'firstname, lastname';

        // get active groupid for this course during this $SESSION
        list($groupid, $groupingid) = $this->get_groupid();

        // get groupmode: 0=NOGROUPS, 1=VISIBLEGROUPS, 2=SEPARATEGROUPS
        $groupmode = groups_get_course_groupmode($COURSE);

        if ($groupmode==NOGROUPS || $groupmode==VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $COURSE->context)) {
            // user can access all users and groups in the course
            $mygroupsonly = false;
        } else {
            // user can only see members in groups to which (s)he belongs
            // (e.g. non-editing teacher when groups are separate)
            $mygroupsonly = true;
        }

        if ($mygroupsonly || $groupid) {
            // select only users from specific group(s) in this course
            $groupids = "SELECT id FROM {$CFG->prefix}groups WHERE courseid=$COURSE->id";
            if ($groupid) {
                $groupids .= " AND id=$groupid";
            }
            if ($mygroupsonly) {
                $groupids = "SELECT groupid FROM {$CFG->prefix}groups_members WHERE userid=$USER->id AND groupid IN ($groupids)";
            }
            $where .= " id IN (SELECT DISTINCT userid FROM {$CFG->prefix}groups_members WHERE groupid IN ($groupids))";
        } else {
            // select all students in this course
            $roleid = "SELECT id FROM {$CFG->prefix}role WHERE shortname='student'";
            if ($role_assignments = $DB->get_records_select_menu('role_assignments', "roleid=($roleid) AND contextid=".$COURSE->context->id, null, '', 'id,userid')) {
                if ($userids = implode(',', array_values($role_assignments))) {
                    $where .= "id IN ($userids)";
                }
            }
        }

        switch ($this->config->loginassort) {
            case 0:
                if (isset($CFG->fullnamedisplay)) {
                    $orderby = $CFG->fullnamedisplay;
                } else {
                    $a = (object)array('firstname'=>'firstname', 'lastname'=>'lastname');
                    $orderby = get_string('fullnamedisplay', '', $a);
                }
                if (substr($orderby, 0, 8)== 'lastname') {
                    $orderby = 'lastname, firstname';
                } else {
                    $orderby = 'firstname, lastname';
                }
                break;
            case 1: $orderby = 'firstname, lastname'; break;
            case 2: $orderby = 'lastname, firstname'; break;
            case 3: $orderby = 'username'; break;
            case 4: $orderby = 'idnumber'; break;
            default: $orderby = 'id'; // shouldn't happen !!
        }

        $users = false;
        if ($where) {
            $users = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $orderby");
        }
        if (empty($users)) {
            return '';
        }

        $menu = "\n";
        $menu .= '<form action="'.$CFG->wwwroot.'/course/loginas.php" method="get">';
        $menu .= '<div>';

        $menu .= '<input type="hidden" name="id" value="'.$COURSE->id.'" />';
        $menu .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

        $menu .= '<select name="user" onchange="this.form.submit()">';
        $menu .= '<option value="0" />'.get_string('loginas').' ...</option>';
        foreach ($users as $user) {
            $menu .= '<option value="'.$user->id.'" />'.$this->trim_name(fullname($user)).'</option>';
        }
        $menu .= '</select>';

        $menu .= '<input id="useridsubmit" class="submitbutton" type="submit" value="'.get_string('go').'" />';
        $menu .= '</div>';
        $menu .= '</form>'."\n";

        $menu .= '<script type="text/javascript">'."\n";
        $menu .= '//<![CDATA['."\n";
        $menu .= 'document.getElementById("useridsubmit").style.display = "none";'."\n";
        $menu .= '//]]>'."\n";
        $menu .= '</script>'."\n";

        return $menu;
    }

    /**
     * Set (and cache) the active group ID and grouping ID for this course
     * during the current $SESSION, based on capabilities, request params,
     * and prior user/session preferences.
     *
     * Uses $COURSE->groupmode and the user's capabilities to determine
     * which group(s) are visible/accessible, reads any incoming form values
     * (groupmenu / group), validates them, and then caches the resolved IDs
     * in both $SESSION and user preferences.
     *
     * Globals:
     * - $COURSE stdClass Course record (expects ->id, ->groupmode, optionally ->context)
     * - $USER stdClass Current user
     *
     * Side effects:
     * - Updates $this->groupid and $this->groupingid
     * - Persists resolved IDs into $SESSION and user preferences
     *
     * @return array{0:int,1:int} Array with two integers: [groupid, groupingid]
     */
    protected function get_groupid() {
        global $COURSE, $USER;
    
        // if we have already set the groupid,
        // just return it and stop here
        if (isset($this->groupid)) {
            return [$this->groupid, $this->groupingid];
        }
    
        // intialize groupid
        $this->groupid = 0;
        $this->groupingid = 0;
    
        // if groups are not used in this course,
        // set to groupid to zero and stop here
        if (empty($COURSE->groupmode) || $COURSE->groupmode==NOGROUPS) {
            return [$this->groupid, $this->groupingid];
        }
    
        // get course context
        if (isset($COURSE->context)) {
            $context = $COURSE->context;
        } else {
            $context = self::context(CONTEXT_COURSE, $COURSE->id);
        }
    
        // get groupmode: 0=NOGROUPS, 1=VISIBLEGROUPS, 2=SEPARATEGROUPS
        if (has_capability('moodle/site:accessallgroups', $context)) {
            $groupmode = 'aag';
        } else {
            $groupmode = $COURSE->groupmode;
        }
    
        // Get the active group and grouping for this course.
        $groupid = self::get_session_value('group', $groupmode);
        $groupingid = self::get_session_value('grouping', $groupmode);
    
        // Override previously set $groupid with incoming form value, if any
        // and then store $groupid locally, if it is valid
        $groupid = optional_param('groupmenu', $groupid, PARAM_INT);
        if ($groupid = optional_param('group', $groupid, PARAM_INT)) {
            if ($groupid >= 0) {
                $groupingid = 0;
            } else {
                $groupingid = abs($groupid);
                $groupings = groups_get_all_groupings($COURSE->id);
                if (array_key_exists($groupingid, $groupings)) {
                    $this->groupingid = $groupingid;
                    $groupid = 0;
                } else {
                    $groupingid = 0; // Shouldn't happen !!
                }
            }
        }
    
        // If groupid is set, check that it is viewable by the current user.
        if ($groupid) {
            if ($groupmode==SEPARATEGROUPS) {
                $groups = groups_get_all_groups($COURSE->id, $USER->id);
            } else {
                $groups = groups_get_all_groups($COURSE->id); // all groups
            }
            if ($groups && count($groups)) {
                if (array_key_exists($groupid, $groups)) {
                    $this->groupid = $groupid;
                } else if ($groupmode==SEPARATEGROUPS) {
                    $this->groupid = key($groups);
                }
            }
        }
    
        // cache $this->groupid for other scripts (via $SESSION)
        // and also later sessions (passing it via user preferences)
        self::set_session_value('group', $groupmode, $this->groupid);
        self::set_session_value('grouping', $groupmode, $this->groupingid);
    
        // return valid groupid and groupingid.
        return [$groupid, $groupingid];
    }
    
    /**
     * Read the active group or grouping ID for the current course and groupmode
     * from $SESSION if present; otherwise fall back to the stored user preference.
     *
     * The value is kept per course and per groupmode (VISIBLEGROUPS, SEPARATEGROUPS, 'aag'),
     * and additionally keyed by the course default grouping ID to separate contexts.
     *
     * Globals:
     * - $COURSE stdClass  Course record (expects ->id, ->defaultgroupingid)
     * - $SESSION stdClass PHP session storage object
     *
     * @param string $name Either 'group' or 'grouping' (determines which value is read)
     * @param int|string $groupmode Course group mode (NOGROUPS|VISIBLEGROUPS|SEPARATEGROUPS) or 'aag' for access-all-groups
     * @return int The active group ID or grouping ID (0 if none)
     */
    public static function get_session_value($name, $groupmode) {
        global $COURSE, $SESSION;
    
        $active = 'active'.$name; // e.g. "activegroup"
        $courseid = $COURSE->id;
        $defaultid = $COURSE->defaultgroupingid;
    
        // the user_preference that stores the groupid for this course
        // this is used to maintain the preference between sessions
        $preferencename = 'taskchain_navigation_'.$name.'id_'.$courseid;
    
        if (! isset($SESSION->$active)) {
            $SESSION->$active = array();
        }
        if (! isset($SESSION->$active[$courseid])) {
            $SESSION->$active[$courseid] = array(
                VISIBLEGROUPS => array(),
                SEPARATEGROUPS => array(),
                'aag' => array()
            );
        }
        if (! isset($SESSION->$active[$courseid][$groupmode])) {
             $SESSION->$active[$courseid][$groupmode] = array();
        }
        if (isset($SESSION->$active[$courseid][$groupmode][$defaultid])) {
            return $SESSION->$active[$courseid][$groupmode][$defaultid];
        } else {
            return get_user_preferences($preferencename, 0); // first time this $SESSION
        }
    }
    
    /**
     * Persist the active group or grouping ID for the current course and groupmode
     * into $SESSION and also into user preferences for cross-session continuity.
     *
     * Values are stored per course and per groupmode (VISIBLEGROUPS, SEPARATEGROUPS, 'aag'),
     * additionally keyed by the course default grouping ID.
     *
     * Globals:
     * - $COURSE stdClass  Course record (expects ->id, ->defaultgroupingid)
     * - $SESSION stdClass PHP session storage object
     *
     * Side effects:
     * - Mutates $SESSION
     * - Calls set_user_preference() to persist the value per user
     *
     * @param string            $name      Either 'group' or 'grouping' (determines which value is written)
     * @param int|string        $groupmode Course group mode (NOGROUPS|VISIBLEGROUPS|SEPARATEGROUPS) or 'aag' for access-all-groups
     * @param int               $value     The group ID or grouping ID to store (0 if none)
     * @return void
     */
    public static function set_session_value($name, $groupmode, $value) {
        global $COURSE, $SESSION;
    
        $active = 'active'.$name; // e.g. "activegroup"
        $courseid = $COURSE->id;
        $defaultid = $COURSE->defaultgroupingid; // Should this be groupingid?
        $SESSION->$active[$courseid][$groupmode][$defaultid] = $value;
    
        $preferencename = 'taskchain_navigation_'.$name.'id_'.$courseid;
        set_user_preference($preferencename, $value);
    }

    /**
     * trim_text
     *
     * @param   string   $text
     * @param   integer  $textlength (optional, default=28)
     * @param   integer  $headlength (optional, default=10)
     * @param   integer  $taillength (optional, default=10)
     * @return  string
     */
    static public function trim_text($text, $textlength=28, $headlength=10, $taillength=10) {
        $strlen = self::textlib('strlen', $text);
        if ($strlen > $textlength) {
            $head = self::textlib('substr', $text, 0, $headlength);
            $tail = self::textlib('substr', $text, $strlen - $taillength, $taillength);
            $text = $head.' ... '.$tail;
        }
        return $text;
    }

    /**
     * get_userfields
     *
     * @param string $tableprefix name of database table prefix in query
     * @param array  $extrafields extra fields to be included in result (do not include TEXT columns because it would break SELECT DISTINCT in MSSQL and ORACLE)
     * @param string $idalias     alias of id field
     * @param string $fieldprefix prefix to add to all columns in their aliases, does not apply to 'id'
     * @return string
     */
     static public function get_userfields($tableprefix='', array $extrafields=null, $idalias='id', $fieldprefix='') {

        // Moodle >= 3.11 (see "user/classes/fields.php")
        if (class_exists('\\core_user\\fields')) {
            $fields = \core_user\fields::for_userpic();
            if ($extrafields) {
                // NOTE: "..." is the SPLAT operator and is available in PHP >= 5.6
                // https://lornajane.net/posts/2014/php-5-6-and-the-splat-operator
                // $fields->including(...$extrafields);
                // However, the splat-operator produces a syntax error in PHP 5.4,
                // and we don't use $extrafields anyway, so we stick with the old synxtax
                $fields->including($extrafields);
            }
            $fields = $fields->get_sql($tableprefix, false, $fieldprefix, $idalias, false)->selects;
            if ($tableprefix === '') {
                // If no table alias is specified, don't add {user}. in front of fields.
                $fields = str_replace('{user}.', '', $fields);
            }
            // Maintain legacy behaviour where the field list was done with 'implode' and no spaces.
            $fields = str_replace(', ', ',', $fields);
            return $fields;
        }

        // Moodle >= 2.6
        if (class_exists('user_picture')) {
            return user_picture::fields($tableprefix, $extrafields, $idalias, $fieldprefix);
        }

        // Moodle <= 2.5
        $fields = array('id', 'firstname', 'lastname', 'picture', 'imagealt', 'email');
        if ($tableprefix || $extrafields || $idalias) {
            if ($tableprefix) {
                $tableprefix .= '.';
            }
            if ($extrafields) {
                $fields = array_unique(array_merge($fields, $extrafields));
            }
            if ($idalias) {
                $idalias = " AS $idalias";
            }
            if ($fieldprefix) {
                $fieldprefix = " AS $fieldprefix";
            }
            foreach ($fields as $i => $field) {
                $fields[$i] = "$tableprefix$field".($field=='id' ? $idalias : ($fieldprefix=='' ? '' : "$fieldprefix$field"));
            }
        }
        return implode(',', $fields); // 'u.id AS userid, u.username, u.firstname, u.lastname, u.picture, u.imagealt, u.email';
    }

    /**
     * get_numsections
     *
     * a wrapper method to offer consistent API for $course->numsections
     * in Moodle 2.0 - 2.3, "numsections" is a field in the "course" table
     * in Moodle >= 2.4, "numsections" is in the "course_format_options" table
     *
     * @uses   $DB
     * @param  mixed   $course, either object (DB record) or integer (id)
     * @return integer $numsections
     */
    static public function get_numsections($course) {
        global $DB;
        if (is_numeric($course)) {
            $course = $DB->get_record('course', array('id' => $course));
        }
        if (isset($course->numsections)) {
            // Moodle <= 2.3
            return $course->numsections;
        }
        if (isset($course->format)) {
            $format = course_get_format($course);
            if (method_exists($format, 'get_last_section_number')) {
                // Moodle >= 3.4
                return $format->get_last_section_number();
            }
            if (method_exists($format, 'get_format_options')) {
                // Moodle >= 2.4
                $options = $format->get_format_options();
                if (isset($options['numsections'])) {
                    return $options['numsections'];
                }
            }
        }
        // Last try - shouldn't be necessary.
        if ($modinfo = get_fast_modinfo($course)) {
            if ($sections = $modinfo->get_section_info_all()) {
                if (is_array($sections) && count($sections)) {
                    // The keys are section numbers.
                    return max(array_keys($sections));
                }
            }
        }
        return 0; // shouldn't happen !!
    }

    /**
     * get_section_info
     *
     * a wrapper method to offer consistent API
     * to get info for a single section
     *
     * @param xxx $modinfo
     * @param xxx $sectionnum
     * @return xxx
     */
    static function get_section_info($modinfo, $sectionnum) {
        global $DB;

        if (method_exists($modinfo, 'get_section_info')) {
            // Moodle >= 2.3
            return $modinfo->get_section_info($sectionnum);
        }

        // Moodle <= 2.2
        $params = array('course' => $modinfo->get_course_id(),
                        'section' => $sectionnum);
        return $DB->get_record('course_sections', $params);
    }

    /**
     * get_section_info_all
     *
     * a wrapper method to offer consistent API
     * to get info for all sections
     *
     * @param xxx $modinfo
     * @return xxx
     */
    static function get_section_info_all($modinfo) {
        global $DB;

        if (method_exists($modinfo, 'get_section_info_all')) {
            // Moodle >= 2.3
            return $modinfo->get_section_info_all();
        }

        // Moodle <= 2.2
        $info = array();
        $params = array('course' => $modinfo->get_course_id());
        if ($sections = $DB->get_records('course_sections', $params, 'section')) {
            foreach ($sections as $section) {
                $sectionnum = $section->section;
                $info[$sectionnum] = $section;
            }
        }
        return $info;
    }

    /**
     * str_split
     *
     * @param xxx $str
     * @param xxx $split_length (optional, default=1)
     * @return xxx
     */
    static public function str_split($str, $split_length=1){
        $array = array();
        $i_max = self::textlib('strlen', $str);
        for($i=0; $i<$i_max; $i+=$split_length){
            $array[] = self::textlib('substr', $str, $i, $split_length);
        }
        return $array;
    }

    /**
     * filter_text
     *
     * @param string $text
     * @return string
     */
    static public function filter_text($text) {
        global $PAGE;

        $filter = filter_manager::instance();

        if (method_exists($filter, 'setup_page_for_filters')) {
            // Moodle >= 2.3
            $filter->setup_page_for_filters($PAGE, $PAGE->context);
        }

        return $filter->filter_text($text, $PAGE->context);
    }

    /**
     * textlib
     *
     * a wrapper method to offer consistent API for textlib class
     * in Moodle 2.0 and 2.1, $textlib is first initiated, then called
     * in Moodle 2.2 - 2.5, we use only static methods of the "textlib" class
     * in Moodle >= 2.6, we use only static methods of the "core_text" class
     *
     * @param string $method
     * @param mixed any extra params that are required by the textlib $method
     * @return result from the textlib $method
     * @todo Finish documenting this function
     */
    static public function textlib() {
        if (class_exists('core_text')) {
            // Moodle >= 2.6
            $textlib = 'core_text';
        } else if (method_exists('textlib', 'textlib')) {
            // Moodle 2.0 - 2.2
            $textlib = textlib_get_instance();
        } else {
            // Moodle 2.3 - 2.5
            $textlib = 'textlib';
        }
        $args = func_get_args();
        $method = array_shift($args);
        $callback = array($textlib, $method);
        return call_user_func_array($callback, $args);
    }

    /**
     * context
     *
     * a wrapper method to offer consistent API to get contexts
     * in Moodle 2.0 and 2.1, we use get_context_instance() function
     * in Moodle >= 2.2, we use static context_xxx::instance() method
     *
     * @param integer $contextlevel
     * @param integer $instanceid (optional, default=0)
     * @param int $strictness (optional, default=0 i.e. IGNORE_MISSING)
     * @return required context
     * @todo Finish documenting this function
     */
    static public function context($contextlevel, $instanceid=0, $strictness=0) {
        if (class_exists('context_helper')) {
            // use call_user_func() to prevent syntax error in PHP 5.2.x
            $class = context_helper::get_class_for_level($contextlevel);
            return call_user_func(array($class, 'instance'), $instanceid, $strictness);
        } else {
            return get_context_instance($contextlevel, $instanceid);
        }
    }
}

if (! function_exists('optional_param_array')) {
    // Moodle <= 2.1
    function optional_param_array($name, $default, $type) {
        return optional_param($name, $default, $type);
    }
}
