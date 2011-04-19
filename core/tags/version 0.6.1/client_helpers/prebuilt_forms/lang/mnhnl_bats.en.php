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
$custom_terms = array(
	// 'Edit' is left unchanged
	'LANG_Edit' => 'Edit',
	'LANG_Add_Sample' => 'Add New Sample',
	'LANG_Add_Sample_Single' => 'Add Single Occurrence',
	'LANG_Add_Sample_Grid' => 'Add List of Occurrences',

	'LANG_SRef_Label' => 'Spatial Ref',
	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site Name',
	
	'LANG_Save' => 'Save',

	'validation_required' => 'Please enter a value for this field',

	'LANG_Location_Code_Label' => 'Code',
	'LANG_Location_Code_Blank_Text' => 'Choose a location using its code',
	'LANG_Location_Name_Label' => 'Name of the site',
	'LANG_Location_Name_Blank_Text' => 'Choose a location using its name',
	'village' => 'Village / Locality',
	'site type' => 'Site Type',
	'site type other' => 'If Others',
	'site followup' => 'Pertinence of site for a regular followup',
	'LANG_SRef_Label' => 'Coordinates',
	'LANG_Georef_Label'=>'Search for place on map',
	'LANG_Georef_SelectPlace' => 'Select the correct one from the following places that were found matching your search. (Click on the list items to see them on the map.)',
	'LANG_Georef_NothingFound' => 'No place found with that name. Try a nearby town name.',
	'search' => 'Search',
	'Location Comment' => 'Comment',

	'LANG_Tab_otherinformation' => 'Conditions',
	'LANG_Date' => 'Date',
	'cavity entrance' => 'Cavity Entrance',
	'cavity entrance comment' => 'If the closure system is defective',
	'disturbances' => 'Disturbances',
	'disturbances other comment' => 'If Others',
	'Bats Temp Exterior' => "Temperature outside cavity (Celcius)",
	'Bats Humid Exterior' => "Relative Hunidity outside cavity (%)",
	'Bats Temp Int 1' => "Temperature inside cavity - A (Celcius)",
	'Bats Humid Int 1' => "Relative Hunidity inside cavity - A (%)",
	'Bats Temp Int 2' => "Temperature inside cavity - B (Celcius)",
	'Bats Humid Int 2' => "Relative Hunidity inside cavity - B (%)",
	'Bats Temp Int 3' => "Temperature inside cavity - C (Celcius)",
	'Bats Humid Int 3' => "Relative Hunidity inside cavity - C (%)",
	'Positions Marked' => 'Measurement Location(s) indicated on map',
	'Bats Reliability' => "Reliability (completeness) of the inventory",
	'Overall Comment' => 'Comment',

	'LANG_Tab_species' => 'Species',
	'excrement' => 'Excrement',
	'corpse' => 'Corpses',
	'sleepy' => 'Lethargic',
	'Number: previous area' => 'Number of individuals in previously explored area',
	'Number: new area' => 'Number of individuals in newly explored area',

	'validation_no_observation' => "The <strong>No observation</strong> must be checked if and only if there is no data in the species grid."

	
);