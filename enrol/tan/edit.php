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
 * Adds new instance of enrol_tan to specified course
 * or edits current instance.
 *
 * @package    enrol_tan
 * @copyright  2013 gtn gmbh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('edit_form.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/tan:config', $context);

$PAGE->set_url('/enrol/tan/edit.php', array('courseid'=>$course->id, 'id'=>$instanceid));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id'=>$course->id));
if (!enrol_is_enabled('tan')) {
    redirect($return);
}

/** @var enrol_tan_plugin $plugin */
$plugin = enrol_get_plugin('tan');

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'tan', 'id'=>$instanceid), '*', MUST_EXIST);

} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$course->id)));

    $instance = (object)$plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    $instance->status   = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
}

// Merge these two settings to one value for the single selection element.
if ($instance->notifyall and $instance->expirynotify) {
    $instance->expirynotify = 2;
}
unset($instance->notifyall);

$mform = new enrol_tan_edit_form(NULL, array($instance, $plugin, $context));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if ($data->expirynotify == 2) {
        $data->expirynotify = 1;
        $data->notifyall = 1;
    } else {
        $data->notifyall = 0;
    }
    if (!$data->expirynotify) {
        // Keep previous/default value of disabled expirythreshold option.
        $data->expirythreshold = $instance->expirythreshold;
    }
    if ($instance->id) {
        $reset = ($instance->status != $data->status);

        //$instance->name           = $data->name;
        //$instance->password       = $data->password;
		$instance->roleid         = $data->roleid;
		$instance->enrolperiod    = $data->enrolperiod;
        $instance->expirynotify   = $data->expirynotify;
		$instance->expirythreshold = $data->expirythreshold;
		$instance->customint4     = $data->customint4;
		$instance->customtext1    = $data->customtext1;
        $instance->timemodified   = time();
		$instance->notifyall	  = $data->notifyall;
        $DB->update_record('enrol', $instance);

        if ($reset) {
            $context->matan_dirty();
        }

    } else {
        $fields = array(
            'customint4'      => $data->customint4,
            'customtext1'     => $data->customtext1,
            'roleid'          => $data->roleid,
            'enrolperiod'     => $data->enrolperiod,
            'expirynotify'    => $data->expirynotify,
            'notifyall'       => $data->notifyall,
            'expirythreshold' => $data->expirythreshold);
        $plugin->add_instance($course, $fields);
    }

    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_tan'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_tan'));
$mform->display();
echo $OUTPUT->footer();
