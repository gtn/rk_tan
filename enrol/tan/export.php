<?php
require_once dirname(__FILE__)."/../../config.php";

$courseid = required_param('courseid',  PARAM_INT);

if(isset($_POST['amount'])){
	$amount = (int) $_POST["amount"];
	if(strpos($amount, "max")){
		$amount = $DB->count_records('enrol_tan', array('courseid'=>$courseid, 'used'=>0));
	}		
}	
else
	error(get_string('parammissing', 'enrol_tan'));
	
$context = get_context_instance(CONTEXT_SYSTEM);

require_login($courseid);
require_capability('enrol/tan:tangen', $context);

if(!$tans = $DB->get_records('enrol_tan', array("courseid"=>$courseid, "used"=>0), null, 'tancode', 0, $amount)){
	error(get_string('notan', 'enrol_tan'));
}
$coursename =  $DB->get_field('course', 'shortname', array("id"=>$courseid));

header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=TAN-".$coursename.".txt");
header("Pragma: no-cache");
header("Expires: 0");

foreach ($tans as $tan) {
    
    echo $tan->tancode."\r\n";
    
}