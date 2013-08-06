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
 * TAN enrolment renderer
 *
 * @package    enrol_tan
 * @copyright  2013 gtn gmbh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class enrol_tan_renderer extends plugin_renderer_base {
	public function render_tangenerator($data, $course, $viewid, $error=NULL){
		
		if(isset($error)){
			return html_writer::tag("p", $error);
		}else{
			$table = new html_table();
			$table->attributes['class'] = 'tan_renderer';
			$head = array();
			
			$cellhead = new html_table_cell();
			$cellhead->text = html_writer::tag("p", "TAN");
			$head[] = $cellhead;
			
			if($viewid != 1){
				$cellhead = new html_table_cell();
				$cellhead->text = html_writer::tag("p", get_string('usedfrom', 'enrol_tan'));
				$head[] = $cellhead;
			}
				
			$table->head = $head;
			
			$rows = array();
			
			foreach($data as $tan){
				$row = new html_table_row();
				if(strcmp($tan->used, "0") == 0){
					$cell = new html_table_cell();
					$cell->text = html_writer::tag("p", $tan->tancode);
					//$cell->text = "0:  ".$tan->tancode;
					$cell->attributes['class'] = 'not_used';
				}else{
					$cell = new html_table_cell();
					$cell->text = html_writer::tag("p", $tan->tancode);
					//$cell->text = "1:   ".$tan->tancode;
					$cell->attributes['class'] = 'used';
				}
				$row->cells[] = $cell;
				
				if($viewid != 1){
					if(isset($tan->name->lastname) && isset($tan->name->firstname)){
						$cell = new html_table_cell();
						$cell->text = html_writer::tag("p", $tan->name->firstname." ".$tan->name->lastname);
						$cell->attributes['class'] = 'used';
						$row->cells[] = $cell;
					}else{
						$cell = new html_table_cell();
						$cell->text = html_writer::tag("p", get_string('available', 'enrol_tan'));
						$cell->attributes['class']='free';
						$row->cells[] = $cell;
					}
				}
				$rows[] = $row;
			}
		
			$table->data = $rows;
			
			return html_writer::tag("div", html_writer::table($table), array("id"=>"enrol_tan"));
		}
	}
}
?>