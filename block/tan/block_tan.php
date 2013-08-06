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
 * tan block caps.
 *
 * @package    block_tan
 * @copyright  GTN GmbH 2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
 

	
class block_tan extends block_base {
	
    function init() {
        $this->title = get_string('pluginname', 'block_tan');
    }

    function get_content() {
    	if(!isloggedin()){
    		return $this->content;
    	}
    	
    	if(isset($_POST['TAN'])){
			$error = $this->apply_TAN($_POST['TAN']);
		}
 		global $CFG, $DB, $COURSE;

		$dbman = $DB->get_manager();

    	try {
        	$tanenrolment = ($dbman->table_exists('enrol_tan'));
    	} catch(dml_read_exception $e) {
        	return $this->content;
   		}
 		
 		if($tanenrolment){
 			$this->content = new stdClass();
 		
 			$context = context_system::instance();
 			if ((has_capability('block/tan:addinstance', $context))) {
				$this->content->footer = '<a href="'.$CFG->wwwroot.'/enrol/tan/tangenerator.php"> '.get_string('generator', 'block_tan').' </a></br></br>';
 			}
        
        	if(isset($error)){
        		$this->content->footer .= $error;
        	}
			$this->content->footer .= '<form action="" method="POST">';
    		$this->content->footer .= '<input type="text" length="7" name="TAN"/>';
    		$this->content->footer .= '<input type ="submit" value="'.get_string('applytan', 'block_tan').'"/></form>';
 		}
 		
        return $this->content;
    }

    public function instance_allow_multiple() {
          return true;
    }

    function has_config() {return false;}
	
	function apply_TAN($tancode){
		global $DB, $COURSE, $USER, $CFG;
		
		$tancodes = $DB->get_records('enrol_tan', array('used'=>0), null, 'tancode');
		
		if(!isset($tancodes[$tancode])){
			return get_string('passwordinvalid', 'enrol_self');
		}else{
			$tan = $DB->get_record('enrol_tan', array('tancode'=>$tancode));
			$courseid = $tan->courseid;
			
			$context = context_course::instance($courseid);
									
			if(!is_enrolled($context, $USER)){	//enrol user to course
				
				//create parameters for enrolment
				$enrolment = array('roleid' => 5,'userid' => $USER->id,
								'courseid' => $courseid,'timestart' => 0,
								'timeend' => 0,'suspend' => 0);
			
				//check for plugin
				$enrol = enrol_get_plugin('tan');	
			
				//get all enrolement instances of the course (self/guest/manuel/...)
				$enrolinstances = enrol_get_instances($enrolment['courseid'], true);
			
				foreach ($enrolinstances as $courseenrolinstance) {
					if ($courseenrolinstance->enrol == "tan") {
						$instance = $courseenrolinstance;
						break;
					}
				}
				
				if (!empty($instance)) {
			
					//how long is the enrolment valid?
					$enrolDB = $DB->get_record('enrol', array('enrol'=>'tan', 'courseid'=>$courseid));
				
					$starttime = time();
					$endtime = 0;
					if($enrolDB->enrolperiod == 0 && $enrolDB->enrolenddate == 0){
						$endtime = 0;
					}else if($enrolDB->enrolperiod == 0 && $enrolDB->enrolenddate > 0){
						$endtime = $enrolDB->enrolenddate;
					}else if($enrolDB->enrolperiod > 0 && $enrolDB->enrolenddate == 0){
						$endtime = $starttime + $enrolDB->enrolperiod;
					}else{
						if(($enrolDB->enrolperiod+$starttime) >= $enrolDB->enrolenddate){										
							$endtime = $enrolDB->enrolenddate;
						}else{ 
							$endtime = $starttime + $enrolDB->enrolperiod;
						}
					}
					
					$enrolment['timestart'] = $starttime;
					$enrolment['timeend'] = $endtime;
					
					//enrol the user
					$enrol->enrol_user($instance, $enrolment['userid'], $enrolment['roleid'],
					$enrolment['timestart'], $enrolment['timeend'], $enrolment['suspend']);
					
					$update = new stdClass();
					$update->id=$tan->id;
					$update->used = 1;
					$update->userid = $USER->id;
					$update->timestamp = time();
					$DB->update_record('enrol_tan', $update);
					
					redirect($CFG->wwwroot."/course/view.php?id=".$courseid);
				}
				
			}else{
				return get_string('already', 'block_tan');
			}
					
		}
	}
}


