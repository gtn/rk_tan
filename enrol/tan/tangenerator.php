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
 * TAN generator for TAN enrolment plugin
 *
 * @package    enrol_tan
 * @copyright  2013 gtn gmbh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once dirname(__FILE__)."/../../config.php";
require_once('lib.php');

$context = context_system::instance();

require_login();
require_capability('enrol/tan:tangen', $context);

$courseid = optional_param('courseid', "", PARAM_INT);
$viewid = optional_param('viewid', 0, PARAM_INT);
$amount = optional_param('amount', 0, PARAM_INT);

$url = '/enrol/tan/tangenerator.php?';
$PAGE->set_url($url);
$PAGE->set_context($context);

if($amount > 0){	//generate TANs
	$tancodes = $DB->get_records('enrol_tan', array(), null, 'tancode');
	for($i=0; $i<$amount; $i++){
		$newtancode = randomstring();
		while(isset($tancodes[$newtancode])){
			$newtancode = randomstring();
		}
	$record = new stdClass();
	$record->courseid = $courseid;
	$record->tancode = $newtancode;
	$lastinsertid = $DB->insert_record('enrol_tan', $record);
	}	
}

$records = $DB->get_records_menu('course',null,'id','id, fullname');$courses = array();

foreach($records as $id=>$course){
	$enrolinstances = enrol_get_instances($id, true);
	$instance = NULL;	
	foreach ($enrolinstances as $courseenrolinstance) {
		if ($courseenrolinstance->enrol == "tan") {
			$instance = $courseenrolinstance;
			break;
		}
	}
	if(!empty($instance))
		$courses[$id] = $course;
}

if(count($courses)>0){
	
	if(isset($courseid)){
		if($courses && $courseid == 0)
			$courseid = key($courses);	
	}else{
		$courseid = current($courses);
	}
	
	//get data for renderer
	$data = $DB->get_records('enrol_tan', array('courseid'=>$courseid), null, "tancode, used, userid");
	foreach($data as $row){
		$row->name = $DB->get_record('user', array('id'=>$row->userid), "lastname, firstname");
	}
	
	//show used, free or all
	if($viewid != 0){
		$record = $data;
		$data = array();
		foreach($record as $row){
			if($row->used == $viewid-1)
				$data[] = $row;
		}
	}
}else{
	$error = get_string('nocourseyet', 'enrol_tan');
}
	
/**
 * create a TAN with special signs, numbers and letters
 * length of TAN 
 */
function randomstring() {
  $chars = "%=*!#abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
  srand((double)microtime()*1000000);
  $pass = "";
  for($i=0; $i<5; $i++){ 
    $num = rand() % strlen($chars);
    $pass .= $chars[$num];
  }
  return $pass;
}


$available = $DB->count_records('enrol_tan', array('courseid'=>$courseid, 'used'=>0));

enrol_tan_print_header();
?>


<html>
<head>
	<title> <?=get_string('generator', 'enrol_tan')?> </title>
	<link rel="stylesheet" type="text/css" href="styles.css">
	<style type="text/css"></style>
</head>

<body>
<?
//pull-down for courses
echo get_string('choosecourse', 'enrol_tan');
echo html_writer::select($courses, 'tan_tangenerator_courses',array($courseid),null,
		array("onchange"=>"document.location.href='".$CFG->wwwroot.$url."viewid=".$viewid."&courseid='+this.value;"));

//pull-down for views
echo "</br></br>".get_string('chooseview', 'enrol_tan');
echo html_writer::select(array(get_string('all', 'enrol_tan'), get_string('free', 'enrol_tan'), get_string('used', 'enrol_tan')), 'tan_tangenerator_views',array($viewid),null,
		array("onchange"=>"document.location.href='".$CFG->wwwroot.$url."courseid=".$courseid."&viewid='+this.value;"));

?>
<div id="tan_tangenerator">
<?php
	$output = $PAGE->get_renderer('enrol_tan');
	
	if(isset($error)){
		echo $output->render_tangenerator(NULL, NULL, $viewid, $error);
		echo '<a href = "'.$CFG->wwwroot.'">'.get_string('back', 'enrol_tan').'</a>';
		exit;
	}else{
		echo $output->render_tangenerator($data, $courses[$courseid], $viewid);	
	}
	
?>
</div>
</br>
<form action="<?= $CFG->wwwroot.$url.'courseid='.$courseid."&viewid=".$viewid?>" method="post">
	<input type="text" name="amount" value="max. 1000" length="10" onfocus="if(this.value=='max. 1000'){this.value='';}" onblur="if(this.value==''){this.value='max. 1000';}"/>
	<input type="submit" value="<?php echo get_string('generate', 'enrol_tan')?>"/>
</form>
<form action="<?=$CFG->wwwroot.'/enrol/tan/export.php?courseid='.$courseid?>" method="post"/>
	<input type="text" name="amount" value="max. <?=$available?>" length="10" onfocus="if(this.value=='max. <?=$available?>'){this.value='';}" onblur="if(this.value==''){this.value='max. <?=$available?>';}"/>
	<input type = "submit" value ="<?php echo get_string('export', 'enrol_tan')?>"/>
</form>
</body>
</html>

<?php echo $OUTPUT->footer();?>
	