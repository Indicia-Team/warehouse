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
	'LANG_Edit' => 'Edit',
	'LANG_SampleListGrid_Preamble' => 'Previously encoded survey list for ',
	'LANG_All_Users' => 'all users',
	'LANG_Add_Sample' => 'Add New Sample',
	'LANG_Add_Sample_Single' => 'Add Single Occurrence',
	'LANG_Add_Sample_Grid' => 'Add List of Occurrences',
	'LANG_Download' => 'Reports',
	'LANG_Locations' => 'Sites',
	'validation_integer' => "Please enter an integer.",
	'LANG_Lux5kgrid'=>'Luxembourg 5x5km Grid number',
	'LANG_Lux5kgrid_blank'=>'Choose a square',
	'LANG_ParentLocationLayer'=>'5x5km Grid',
	'LANG_SiteLocationLayer'=>'Sites',
	'LANG_LocationModuleInstructions1'=>'Choose a 5x5km Grid square. This square will then be displayed on the Map, along with all existing sites associated with that square. The conditions and species grids will then be set up with rows and columns for those sites. ',
	'LANG_LocationModuleInstructions2'=>'Additional sites may be added by drawing a perimeter on the map, clicking on each vertex. Double click on the final point to complete it. It will then be added to the list of sites, and given a number as a default name. If you create a site, but do not save any data against it, it will NOT be recorded in the database. It is also not possible to change a site name or boundary on this form once it has been saved - this can be done by an Admin user using their special tool. ',
	'LANG_LocationModuleInstructions3'=>'Choose a 5x5km Grid square. This square will then be displayed on the Map, along with all existing sites associated with that square.',
	'LANG_LocationModuleInstructions4'=>"Either click on the map to select the Site you wish to modify, or choose from the drop down list. You may then change its name, or its extent on the map by dragging the red vertices. To delete a vertex, place the mouse over the vertex and press the 'd' or 'Delete' buttons.",
	'LANG_ConditionsGridInstructions'=>'Before any data can be entered onto a row of the grid below, and entered into the equivalent column in the species grid, the date for the visit to that site must be filled in. Additional sites may be added by drawing on the Map. Clicking on the Red X will either clear the data for that site, if the site was pre-existing, or the site will be deleted if you have added it during this session. ',
	'LANG_SpeciesGridInstructions'=>'Note species observed at each site and estimate their abundance.<br />Before any data can be entered onto a column of the grid below, the date for the visit to that site must be filled in on the Conditions Grid. Additional sites may be added by drawing on the Map. Additional species may be added by entering the name in the control below.  Clicking on the Red X will either clear the data for that species, if the species had previously entered data, or the species will be removed if you have added it during this session. ',
	'MNHNL Butterfly de Jour Passage'=>'Passage',
	'speciesgrid:taxa_taxon_list_id'=>'Add Species to Grid',
	'LANG_ConfirmSurveyDelete'=>'You are about to flag a survey as deleted. Do you wish to continue and delete survey ',
	'LANG_Conditions_Report_Download' => 'This Report provides details of the conditions recorded on each site for each survey, including if no observations where made. It does not include any species data. CSV format.',
	'LANG_Download_Button' => 'Download',
	'LANG_Occurrence_Report_Download' => 'This Report provides details of the species recorded on each site for each survey. It includes the conditions on the sites. CSV format.',
	'LANG_NumSites'=>'Number of Sites in this Square',
	"LANG_EmptyLocationID"=>'Choose a Site',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
// Date
// Start Time
	'Duration'=>'Duration<br />(Minutes)',
	'Temperature (Celsius)'=>'Temp<br />(C)',
	'Numeric Windspeed'=>'Windspeed<br />(Bf)',
	'Rain Checkbox'=>'Rain',
	'Numeric Cloud Cover'=>'Cloud Cover<br />(%)',
	'Bats Reliability'=>'Reliability',
// No Observation
	'LANG_conditionsgrid:clearconfirm' => 'You are about to clear the data for a site. If you do this any previously saved data (including species data for that site) will be lost. Do you still wish to continue?',
	'LANG_conditionsgrid:removeconfirm' => 'You are about to remove a newly created site. If you do this all entered data (including species data for that site) will be lost. Do you still wish to continue?',
	'LANG_speciesgrid:clearconfirm' => 'You are about to clear all the data for a species. If you do this all previously saved data will be lost. Do you still wish to continue?',
	'LANG_speciesgrid:removeconfirm' => 'You are about to remove a newly created species entry. If you do this all entered data for that species will be lost. Do you still wish to continue?',

	'LANG_SRef_Label' => 'Spatial Ref',
	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site Name',
	'LANG_Georef_Label' => 'Search for Place on Map',
	// The search button may be changed by adding an entry for 'search'
	
	'LANG_Date' => 'Date',
	'LANG_Save' => 'Save',
	'LANG_Submit' => 'Save',
	'LANG_Cancel' => 'Cancel',

	'validation_required' => 'Required',
	'validation_no_observation' => "The <strong>No observation</strong> must be checked if and only if there is no data for this site in the species grid.",

	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_Allocate_Locations' => 'Allocate Squares',
	'LANG_Save_Location_Allocations' => 'Save Location Allocations',
	'speciesgrid:rowexists' => 'A row for this species already exists.',

);