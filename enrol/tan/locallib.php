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
 * TAN enrol plugin implementation.
 *
 * @package    enrol_tan
 * @copyright  2013 gtn gmbh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class enrol_tan_enrol_form extends moodleform {
    protected $instance;
    protected $toomany = false;

    /**
     * Overriding this function to get unique form id for multiple self enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    public function definition() {
        global $DB;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('tan');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'tanheader', $heading);
		
		$mform->addElement('passwordunmask', 'enrolpassword', get_string('password', 'enrol_tan'),
                array('id' => 'enrolpassword_'.$instance->id));

        $this->add_action_buttons(false, get_string('enrolme', 'enrol_tan'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        global $DB, $CFG, $COURSE, $USER;
		
        $errors = parent::validation($data, $files);
        $instance = $this->instance;

		$tancodes = $DB->get_records('enrol_tan', array('courseid'=>$COURSE->id, 'used'=>0), null, 'tancode');
		if(!isset($tancodes[$data['enrolpassword']])){
			$errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_self');
		}else{
			$tan = $DB->get_record('enrol_tan', array('tancode'=>$data['enrolpassword']));
			$update = new stdClass();
			$update->id=$tan->id;
			$update->used = 1;
			$update->userid = $USER->id;
			$update->timestamp = time();
			$DB->update_record('enrol_tan', $update);
		}
			         
        return $errors;
    }
}
