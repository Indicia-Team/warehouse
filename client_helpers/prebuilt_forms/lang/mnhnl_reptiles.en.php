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
	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_Download' => 'Reports',
	'LANG_SampleListGrid_Preamble' => 'Previously encoded survey list for ',
	'LANG_All_Users' => 'all users',
	'LANG_Allocate_Locations' => 'Allocate 5k Grid',
	'LANG_Save_Location_Allocations' => 'Save',
	'LANG_Data_Download' => 'This Report provides details of the data entered in the surveys, in CSV format.',
	'LANG_Download_Button' => 'Download',

	'LANG_Edit' => 'Edit',
	'LANG_Add_Sample' => 'Add New Sample',
	'LANG_Add_Sample_Single' => 'Add Single Occurrence',
	'LANG_Add_Sample_Grid' => 'Add List of Occurrences',

	'LANG_Tab_site' => 'Site',
	'LANG_Parent_Location_Layer' => 'Squares',
	'LANG_Site_Location_Layer' => 'Existing Locations',
	'LANG_Lux5kgrid' => 'Square (5x5km)',
	'LANG_Lux5kgrid_blank' => 'Please choose a square',
	'LANG_Location_Name_Label' => 'Site',
	'LANG_Location_X_Label' => 'Coordinates : X ',
	'LANG_Location_Y_Label' => 'Y ',

	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',

	'LANG_Save' => 'Save',
	'LANG_Cancel' => 'Cancel',
	'validation_required' => 'Please enter a value for this field',

	'LANG_Tab_otherinformation' => 'Conditions',
	'LANG_Date' => 'Date',
	'Overall Comment' => 'Comments',
	'Recorder names' => 'Observer(s)',
	'Reptile Survey 1' => 'Survey',
//	Duration
	'Suitability Checkbox' => 'Suitability',
	'Picture Provided' => 'Picture',
	'Weather' => 'Weather Conditions',
//	Temperature (Celsius)
//	Cloud Cover
	'Rain Checkbox' => 'Rain',
	
	'LANG_Tab_species' => 'Species',
	'species_checklist.species'=>'Species',
	'Count'=>'Number',
	'Occurrence Reliability'=>'Reliability',
//	Counting
	'Reptile Occurrence Type'=>'Type',
	'Reptile Occurrence Stage'=>'Stage',
	'Reptile Occurrence Sex'=>'Sex',
	'Reptile Occurrence Behaviour'=>'Behaviour',
	
	'validation_no_observation' => "The <strong>No observation</strong> must be checked if and only if there is no data in the species grid."
	

	
);