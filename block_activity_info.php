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
 * Version details
 *
 * @package    block_activity_info
 * @copyright  Sangita Kumari<sangita.nitc0059@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();
//require_once('lib.php');
require_once($CFG->libdir . '/filelib.php');
//require_once($CFG->dirroot.'/course/lib.php');


class block_activity_info extends block_list {

    function init() {
        $htmldisplay = ''; 
        $htmldisplay .= html_writer::start_tag('B',array('class'=>'title-heading'));
        $htmldisplay .= get_string('pluginname', 'block_activity_info');
        $htmldisplay .= html_writer::end_tag('B');
        $this->title = $htmldisplay;
    }

    function get_content() {
        global $CFG,$DB,$USER;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->footer = '';
        $course = $this->page->course;
        $modinfo = get_fast_modinfo($course);
        $userid = $USER->id;
        foreach($modinfo->cms as $cm) {
            $cmid = $cm->id;
            $date = gmdate(" j M Y",$cm->added);
            $activitystatus = self::get_activity_status($cmid,$userid);
            $modulsdetails[$cmid] = array('cmid' => $cmid,'activityname' => $cm->name,'addeddate' =>$date,'modname' =>$cm->modname,'status' => $activitystatus);
        }
        
        $sql = 'SELECT ra.contextid,ro.id from {role} ro JOIN {role_assignments} ra ON ro.id = ra.roleid
        Where ra.userid=? ORDER BY ra.id DESC LIMIT 1';
        $details = $DB->get_record_sql($sql,array($userid));
        $roleid = null;
        if(!empty($details)){
            $roleid = $details->id; // 5 is the student role id
        }
        foreach ($modulsdetails as $cmid => $moddetail) {
           $this->content->items[] = self::get_display_data($moddetail,$roleid);
        }
        return $this->content;
    }
    //This function is used to display block data
    function get_display_data($moddetail,$roleid){
        global $USER;
        $modname = $moddetail['modname'];
        $cmid = $moddetail['cmid'];
        $modlink =new moodle_url('/mod/'.$modname.'/view.php',
        array('id' => $cmid));
        $startdate = $moddetail['addeddate'] ;
        $htmldisplay = '';
        $htmldisplay .= html_writer::start_tag('div',  array('class' => 'row activityinfo'));
        $htmldisplay .= html_writer::start_tag('a',array('href'=>$modlink));
        $htmldisplay .= $moddetail['cmid'];
        $htmldisplay .= ' - ';
        $htmldisplay .= $moddetail['activityname'];
        $htmldisplay .= ' - ';
        $htmldisplay .= $startdate;
        //5 is the student role id . status will only show for student not for other roles
        if($roleid == 5){
            $htmldisplay .= ' - ';
            $htmldisplay .= $moddetail['status'];
        }
        $htmldisplay .= html_writer::end_tag('a');
        $htmldisplay .= html_writer::end_tag('div');

        return $htmldisplay;

    }
    //This function is used to find Activity completion status
    function get_activity_status($cmid,$userid){
        global $DB;
        $activitystatus = $DB->get_record('course_modules_completion',array('coursemoduleid'=>$cmid,'userid' =>$userid));
        $status = '';
        if(!empty($activitystatus)){  
            if($activitystatus->completionstate == 1){
                $status = get_string('completed', 'block_activity_info');
            }else if($activitystatus->completionstate == 2){
                $status = get_string('completepass', 'block_activity_info');
            }else if($activitystatus->completionstate == 3){
                $status = get_string('completefail', 'block_activity_info');
            }
        }else{
            $status = get_string('notstarted', 'block_activity_info');
        }

        return $status;
    }
    /**
     * Returns the role that best describes this blocks contents.
     *
     * This returns 'navigation' as the blocks contents is a list of links to activities and resources.
     *
     * @return string 'navigation'
     */
    public function get_aria_role() {
        return 'navigation';
    }

    function applicable_formats() {
        // return array('all' => true, 'mod' => false, 'my' => false, 'admin' => false,
        //             'tag' => false);
        return array('course' => true);
    }
}


