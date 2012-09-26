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
	,'Dormice bordering habitat type' => 'Bordering habitat type'
	,'Dormice shrub diversity' => 'Shrub diversity'
	,'Start time' => 'Start time (HH:MM)'
	,'End time' => 'End time (HH:MM)'
	,'Temperature' => 'Temperature (&degC)'
	,'LANG_CommonInstructions1'=>'Choose a square (1x1km). This square will then be displayed on the map, along with all existing sites associated with that square.'
	,'LANG_CommonParentLabel'=>'Square (1x1km)'
	,'LANG_ZoomToParent'=>'Zoom to square (1x1km)'
	,'Dormouse stage' => 'Stage'
	,'Dormouse sex' => 'Sex'
	,'Dormouse Specimen'=>'Specimen'
	,'Dormouse Nest'=>'Nest'
	,'Count'=>'Number'
	,'Dormouse nest height'=>'Nest height'
	,'Dormouse nest diameter'=>'Nest diameter'
	,'Dormouse distance to support'=>'Distance from nest to external edge of nest support'
	,'Dormouse distance to stand'=>'Distance from nest to external edge of stand'
	,'Dormouse nest composition'=>'Nest composition'
	,'Dormouse nest status'=>'Nest status'
	,'Dormouse tree layer'=>'Tree layer above the nest'
	,'LANG_SpeciesInstructions'=>"It is only possible to record occurrences for the Hazel Dormouse on this form. Records may be added using the button under the grid.<br />The 'No observation' can only be selected when there are no undeleted rows in the grid (when it must be selected) - otherwise it is disabled. Click the red 'X' to delete the relevant row.",
	
);