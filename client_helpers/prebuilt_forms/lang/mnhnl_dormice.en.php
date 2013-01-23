<?php
/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

global $custom_terms;

/**
 * Language terms for the survey_reporting_form_2 form.
 *
 * @package	Client
 */
// Translation strings for when in Dormouse mode, on top of dynamic_2 ones.
// this file should be copied to the file 'node.<nid>.en.php';
global $custom_term_overrides;
$custom_term_overrides[] = array(
	'Dormice habitat type' => 'Habitat type'
	,'Dormice succession' => 'Succession'
	,'Dormice bordering habitat type' => 'Bordering habitat type(s)'
	,'Dormice shrub diversity' => 'Shrub diversity'
	,'Start time' => 'Start time (hh:mm)'
	,'End time' => 'End time (hh:mm)'
	,'Temperature' => 'Temperature (&degC)'
	,'LANG_CommonInstructions1'=>'Choose a square (1x1km) by either picking it from the drop down list, or clicking it on the map. This square will then be highlighted on the map, along with all existing sites associated with that square.'
	,'LANG_CommonParentLabel'=>'Square (1x1km)'
	,'LANG_ZoomToParent'=>'Zoom to square (1x1km)'
	,'Dormouse stage' => 'Stage'
	,'Dormouse sex' => 'Sex'
	,'Dormouse Occurrence Type' => 'Record Type'
	,'Count'=>'Number'
	,'Dormouse nest height'=>'Nest height (cm)'
	,'Dormouse nest diameter'=>'Nest diameter (cm)'
	,'Dormouse distance to support'=>'Distance from nest to external edge of nest support (cm)'
	,'Dormouse distance to stand'=>'Distance from nest to external edge of stand (m)'
	,'Dormouse nest composition'=>'Nest composition'
	,'Dormouse nest status'=>'Nest status'
	,'Dormouse tree layer'=>'Tree layer above the nest'
	,'LANG_SpeciesInstructions'=>"It is only possible to record occurrences for the Hazel Dormouse on this form. Records may be added using the button under the grid.<br />The X and Y fields of a record may be set by either directly entering the numbers in the fields, or by clicking on the map. Once entered, the X and Y values may be changed by clicking on the map again in a new position, or by changing the values in the text fields. The position of the current record being modified (which is highlighted by a blue border) is shown by a purple circle. Any other records are shown as red circles.<br />The 'No observation' can only be selected when there are no undeleted rows in the grid (when it must be selected) - otherwise it is disabled. Click the red 'X' to delete the relevant row."
	,'LANG_Location_Name' => 'Site number'
	,'Site name' => 'Site number'
	,'LANG_LocModTool_IDLabel'=>'Old site number'
	,'LANG_CommonLocationNameLabel' => 'Site number'
	,'LANG_LocModTool_NameLabel'=>'New site number'
	,'LANG_CommonFilterNameLabel'=>'Existing site number'
	,'Occurrence reliability'=>'Observation reliability'
	,'LANG_Shapefile_Download'=> 'These downloads provide zipped up shape files for the locations; due to the restrictions of the SHP file format, there are separate downloads for lines and polygons. Click to select:'

);