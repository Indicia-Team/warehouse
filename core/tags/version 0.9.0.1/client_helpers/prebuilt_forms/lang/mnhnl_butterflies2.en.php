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
	'LANG_MustSelectParentFirst' => 'You must choose a square first, before creating a new location within it.',
	'LANG_Edit' => 'Edit',
	'LANG_SampleListGrid_Preamble' => 'Previously encoded survey list for ',
	'LANG_All_Users' => 'all users',
	'LANG_Add_Sample' => 'Add new sample',
	'LANG_Add_Sample_Single' => 'Add single occurrence',
	'LANG_Add_Sample_Grid' => 'Add list of occurrences',
	'LANG_Trailer_Text' => "Coordination of the biodiversity monitoring programme in Luxembourg: <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (Département Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Ministère du Développement durable et des Infrastructures</a> (Département de l'environnement)",

	'LANG_Locations' => 'Sites',
	'LANG_CommonInstructions1'=>'Choose a square (5x5km) by either picking it from the drop down list, or clicking it on the map. This square will then be highlighted on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (5x5km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_IDLabel'=>'Old site number',
	'LANG_DE_LocationIDLabel'=>'Site',
	'LANG_CommonChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_CommonEmptyLocationID'=>'Choose an existing site',
	'LANG_NoSitesInSquare'=>'There are no sites currently associated with this square',
	'LANG_NoSites'=>'There are currently no sites defined: please create a new one.',
	'LANG_Location_X_Label' => 'Site centre coordinates: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'LANG_CommonLocationNameLabel' => 'Site number',
	'LANG_LocModTool_DeleteLabel'=>'Delete site',
	'LANG_LocModTool_DeleteInstructions'=>'When a site is deleted, any existing visit data will still be available in the reports. New surveys for this square will not feature the site.',
	'LANG_Outside_Square_Reports'=>'Outside Square Checks',
	'LANG_Outside_Square_Download_1'=> 'This report provides a list of locations whose centres are outside their parent square',
	'LANG_Outside_Square_Download_2'=> 'This report provides a list of locations which have any part of their boundaries outside the boundaries of their parent square',

	'LANG_LocModTool_NameLabel'=>'New site number',
	'LANG_DE_Instructions2'=>"You may add new sites by choosing either the polygon or line control and then drawing the new sites' boundaries on the map, clicking on each point, and double clicking on the final point in each element to complete it. You may then add another line or polygon. The new sites will be added to the list of sites for this square, and given a default number. You can change the number of a new site using the 'Site number' field below. After adding a drawn element, you will notice some small red circles appear around the boundary you have just drawn: you can change the element by dragging these circles. To delete a shape vertex, place the mouse over the red circle, and press the 'Delete' button on the keyboard. Further new sites may be added by first clicking on the 'Start a new site' button, and then repeating the process.<br />Only one site's boundary will be modifiable at any one time. Should you wish to modify another new site, select the 'Click on the map to select a site' control, and then click on the site on the map. Pick the control for the type of drawn element you wish to modify. You will see the small red circles appear around the relevant elements for that site.<br />If you create a site, but do not save any data against it, it will NOT be recorded in the database.<br />It is not possible to change a site name or boundary on this form once it has been saved - this can be done by an Admin user using their special tool.",
	'LANG_LocModTool_Instructions2'=>"Either click on the map to select the site you wish to modify, or choose from the drop down list. You may then change its name, or its extent on the map by dragging the red vertices. To delete a shape vertex, place the mouse over the red circle and press the 'Delete' button.",
	'LANG_LocModTool_CantCreate' => "You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_LocationModTool_CommentLabel'=>'Comment',
	'LANG_LocModTool_ParentLabel'=>'New site square',

	'LANG_Data_Download' => 'These reports provide details of the data entered in the survey.',
	
	'LANG_TooFewPoints' => 'There are too few points in this polygon - there must be at least 3.',
	'LANG_TooFewLinePoints' => 'There are too few points in this line - there must be at least 2.',
	'LANG_CentreOutsideParent'=>'Warning: the centre of your new site is outside the square.',
	'LANG_PointOutsideParent'=>'Warning: the point you have created for your site is outside the square.',
	'LANG_LineOutsideParent'=>'Warning: the line you have created for your site has a centre which is outside the square.',
	'LANG_PolygonOutsideParent'=>'Warning: the polygon you have created for new site has a centre which is outside the square.',
	'LANG_ConfirmRemoveDrawnSite'=> "This action will remove the existing site you have created. Do you wish to continue?",
	'LANG_ChoseParentWarning'=> "You can only add a new site after picking a square.",
	'LANG_SelectTooltip'=>'Click on map to select a site or a square',
	'LANG_PolygonTooltip'=>'Draw polygon(s) for the site',
	'LANG_LineTooltip'=>'Draw line(s) for the site',
	'LANG_PointTooltip'=>'Add point(s) to the site',
	'LANG_CancelSketchTooltip'=>'Cancel this sketch',
	'LANG_UndoSketchPointTooltip'=>'Undo the last vertex created',
	'LANG_StartNewSite'=>'Start a new site',
	'LANG_RemoveNewSite'=>'Remove the selected new site',
	'LANG_ZoomToSite'=>'Zoom to site',
	'LANG_ZoomToParent'=>'Zoom to square (5x5km)',
	'LANG_ZoomToCountry'=>'View all Luxembourg',
	'LANG_DuplicateName'=>'Warning: there is another location with this name.',
	'LANG_PointsLegend'=>'Coordinates of individual points',
	'LANG_Grid_X_Label'=>'X',
	'LANG_Grid_Y_Label'=>'Y',
	'LANG_DeletePoint'=>'Delete this point',
	'LANG_AddPoint'=>'Add this point',
	'LANG_HighlightPoint'=>'Highlight this point',
	'LANG_SHP_Download_Legend'=> 'SHP File Downloads',
	'LANG_Shapefile_Download'=> 'These downloads provide zipped up shape files for the locations; due to the restrictions of the SHP file format, there are separate downloads for lines and polygons. Click to select:',
//	Passage
	'speciesgrid:taxa_taxon_list_id'=>'Add species',
	'LANG_ConfirmSurveyDelete'=>'You are about to flag a survey as deleted. Do you wish to continue and delete survey ',
	'LANG_NumSites'=>'Number of sites in this square',
	"LANG_EmptyLocationID"=>'Choose an existing site',
	'Recorder names' => 'Observer(s)',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
	'LANG_ConditionsGridInstructions'=>'Before any data can be entered onto a row of the grid below, and entered into the equivalent column in the species grid, the date for the visit to that site must be filled in. Additional sites may be added by drawing on the Map.<br />Clicking on the red X at the start of each group will either clear the conditions data for that site (if the site was pre-existing) or the site will be deleted if you have added it during this session.',
	'LANG_SpeciesGridInstructions'=>"Note species observed at each site and estimate their abundance.<br />Before any data can be entered into the grid below, the conditions for the visit to that site must be entered in the Conditions section. Additional sites may be added by drawing on the map in the Sites section. Additional species may be added by entering the name in the box below.  Clicking on the red 'X' will either clear the data for that species (if data has previously been entered for the species), or the species will be removed (if you have added it during this session).",
// Date
	'Butterfly2 Target Species'=>'Target species',
//	'Start time'
	'Duration'=>'Duration (mins)',
	'Temperature (Celsius)'=>'Temperature (C)',
	'Temperature'=>'Temperature (C)',
	'Windspeed'=>'Wind (Bf)',
//	'Rain'=>'Rain',
	'Cloud cover'=>'Cloud cover (%)',
// 'Reliability'=>'Reliability',
// No observation
	'LANG_conditionsgrid:clearconfirm' => 'You are about to remove the condition and species data associated with this site from this survey. Do you wish to continue?',
	'LANG_conditionsgrid:removeconfirm' => 'You are about to remove a newly created site. If you do this all conditions and species data you have entered against the site will be lost. Do you wish to continue?',
	'LANG_speciesgrid:clearconfirm' => 'You are about to clear all the data for a species on this survey. Do you wish to continue?',
	'LANG_speciesgrid:removeconfirm' => 'You are about to remove a newly created species entry from this survey. Do you wish to continue?',

	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site Name',
	'LANG_Georef_Label' => 'Search for Place on Map',
	// The search button may be changed by adding an entry for 'search'
	
	'LANG_Date' => 'Date',
	'LANG_Save' => 'Save',
	'LANG_Submit' => 'Save',
	'LANG_Cancel' => 'Cancel',

	'validation_integer' => "Please enter an integer.",
	'validation_required' => 'Required',
	'validation_no_observation' => "The <strong>No observation</strong> must be checked if and only if there is no data for this site in the species grid.",

	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_Allocate_Locations' => 'Allocate squares',
	'speciesgrid:rowexists' => 'A row for this species already exists.',
	'next step'=>'Next step',
	'prev step'=>'Previous step',
	'Overall Comment' => 'Overall comment'

);