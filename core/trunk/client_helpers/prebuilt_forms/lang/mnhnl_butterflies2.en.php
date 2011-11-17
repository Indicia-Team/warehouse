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
	'LANG_LocModTool_ParentLabel'=>'Square (5x5km)',
	'LANG_LocModTool_ParentBlank'=>'Choose a square',
	'LANG_LocModTool_IDLabel'=>'Old site number',
	'LANG_LocModTool_NameLabel'=>'New site number',
	'LANG_LocModTool_DeleteLabel'=>'Delete site',
	'LANG_LocModTool_DeleteInstructions'=>'When a site is deleted, any existing visit data will still be available in the reports and when viewing the data entry form (though the site will no longer appear on the map). New surveys for this square will not feature the site.',
	'LANG_ParentLocationLayer'=>'Squares (5x5km)',
	'LANG_SiteLocationLayer'=>'Sites',
	'LANG_LocationModuleInstructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_LocationModuleInstructions2'=>"You may add new sites by drawing the new sites' boundaries on the map, clicking on each point, and double clicking on the final point in each boundary to complete it. The new sites will be added to the list of sites for this square, and given a default number. You can change the number of a new site using the conditions tab. At this point you will notice some small red circles appear around the boundary you have just drawn: you can change the boundary by dragging these circles. To delete a point, place the mouse over the red circle, and press the 'd' or 'Delete' buttons on the keyboard.<br />Only one site's boundary will be modifiable at any one time - generally it will be the last site created in this session. Should you wish to modify another new site, click on the site on the map, and you will see the small red circles appear around that site instead.<br />If you create a site, but do not save any data against it, it will NOT be recorded in the database.<br />It is not possible to change a site name or boundary on this form once it has been saved - this can be done by an Admin user using their special tool.",
	'LANG_LocModTool_Instructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_LocModTool_Instructions2'=>"Either click on the map to select the site you wish to modify, or choose from the drop down list. You may then change its name, or its extent on the map by dragging the red vertices. To delete a vertex, place the mouse over the vertex and press the 'd' or 'Delete' buttons. You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_ChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_Location_X_Label' => 'Site Centre Coordinates : X ',
	'LANG_Location_Y_Label' => 'Y ',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'Zoom to Parent' => 'Zoom to square (5x5km)',
	'Zoom to Location' => 'Zoom to site',
	'View All Country' => 'View all Luxembourg',
	'LANG_TooFewPoints' => 'There are too few points in this polygon - there must be at least 3. Internal data not updated.',
	'LANG_CentreOutsideParent'=>'Warning: the centre of your new site is outside the square.',
	'LANG_ConditionsGridInstructions'=>'Before any data can be entered onto a row of the grid below, and entered into the equivalent column in the species grid, the date for the visit to that site must be filled in. Additional sites may be added by drawing on the Map. Clicking on the Red X will either clear the data for that site, if the site was pre-existing, or the site will be deleted if you have added it during this session. ',
	'LANG_SpeciesGridInstructions'=>"Note species observed at each site and estimate their abundance.<br />Before any data can be entered into the grid below, the conditions for the visit to that site must be entered in the Conditions section. Additional sites may be added by drawing on the map in the Sites section. Additional species may be added by entering the name in the box below.  Clicking on the red 'X' will either clear the data for that species (if data has previously been entered for the species), or the species will be removed (if you have added it during this session).",
	'LANG_ModificationInstructions' => 'When drawing a new site: ',
//	Passage
	'speciesgrid:taxa_taxon_list_id'=>'Add species',
	'LANG_ConfirmSurveyDelete'=>'You are about to flag a survey as deleted. Do you wish to continue and delete survey ',
	'LANG_Conditions_Report_Download' => 'This Report provides details of the <strong>conditions</strong> recorded on each site for each survey, including if no observations where made. It does not include any species data.',
	'LANG_Download_Button' => 'Download',
	'LANG_Occurrence_Report_Download' => 'This Report provides details of the <strong>species</strong> and <strong>conditions</strong> recorded on each site for each survey.',
	'LANG_NumSites'=>'Number of Sites in this Square',
	"LANG_EmptyLocationID"=>'Choose an existing site',
	'Recorder names' => 'Observer(s)',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
	'LANG_CentreOutsideParent'=>'Warning: the centre of your new site is outside the square.',
// Date
//	'Start time'=>'Start time',
	'Duration'=>'Duration<br />(minutes)',
	'Temperature (Celsius)'=>'Temp<br />(C)',
	'Temperature'=>'Temp<br />(C)',
	'Windspeed'=>'Wind<br />(Bf)',
//	'Rain'=>'Rain',
	'Cloud cover'=>'Cloud cover<br />(%)',
// 'Reliability'=>'Reliability',
// No observation
	'LANG_conditionsgrid:clearconfirm' => 'You are about to clear the data for a site. If you do this any previously saved data (including species data for that site) will be lost. Do you still wish to continue?',
	'LANG_conditionsgrid:removeconfirm' => 'You are about to remove a newly created site. If you do this all entered data (including species data for that site) will be lost. Do you still wish to continue?',
	'LANG_speciesgrid:clearconfirm' => 'You are about to clear all the data for a species. If you do this all previously saved data will be lost. Do you still wish to continue?',
	'LANG_speciesgrid:removeconfirm' => 'You are about to remove a newly created species entry. If you do this all entered data for that species will be lost. Do you still wish to continue?',

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
	'LANG_Allocate_Locations' => 'Allocate squares',
	'LANG_Save_Location_Allocations' => 'Save Location Allocations',
	'speciesgrid:rowexists' => 'A row for this species already exists.',

);