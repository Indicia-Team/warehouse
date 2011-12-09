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
	'LANG_Download' => 'Reports and Downloads',
	'LANG_Locations' => 'Locations',
	'LANG_Sites_Download' => 'Run a report to provide information on all the sites used for these surveys, plus their attributes. (CSV Format)',
	'LANG_Conditions_Download' => 'Run a report to provide information on all these surveys, including the conditions and the associated sites. This returns one row per survey, and excludes any species data. (CSV Format)',
	'LANG_Species_Download' => 'Run a report to provide information on species entered for these surveys. It includes the data for the surveys, conditions and the associated sites. This returns one row per occurrence. (CSV Format)',
	'LANG_Download_Button' => 'Download Report',
	'LANG_Edit' => 'Edit',
	'LANG_Add_Sample' => 'Add New Sample',
	'LANG_Add_Sample_Single' => 'Add Single Occurrence',
	'LANG_Add_Sample_Grid' => 'Add List of Occurrences',

	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site name',
	'LANG_Location_X_Label' => 'Site centre coordinates : X ',
	'LANG_Location_Y_Label' => 'Y ',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'Code GSL' => 'GSL Code',
	'Profondeur' => 'Depth',
	'LANG_PositionOutsideCommune' => 'The position you have choosen is outside the set of allowable Communes. You will not be able to save this position.',
	'LANG_CommuneLookUpFailed' => 'Commune Lookup Failed',
	'LANG_Save' => 'Save',
	'LANG_Cancel' => 'Cancel',
	'LANG_Submit' => 'Save',

	'validation_required' => 'Please enter a value for this field',

	'LANG_Location_Code_Label' => 'Code',
	'LANG_Location_Name_Label' => 'Name of the site',
	'LANG_Location_Name_Blank_Text' => 'Choose a location using its name',
	'Village' => 'Village / Locality',
	'Site type other' => 'If Others',
	'Site followup' => 'Pertinence of site for a regular followup',
	'LANG_SRef_Label' => 'Coordinates',
	'LANG_Georef_Label'=>'Search for place on map',
	'LANG_Georef_SelectPlace' => 'Select the correct one from the following places that were found matching your search. (Click on the list items to see them on the map.)',
	'LANG_Georef_NothingFound' => 'No place found with that name. Try a nearby town name.',
	'search' => 'Search',
	'Location Comment' => 'Comment',
	'Recorder names' => 'Observer(s)',
	'Accompanied By' => 'Accompanying people',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
	'Visit' => 'Visit',
	'Bat visit' => 'Visit',

	'LANG_CommonInstructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (5x5km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_Instructions2'=>"Either click on the map (ensuring that the select tool on the map is active) to select the site you wish to modify, or choose from the drop down list. You may then change its name, or modify or add Points, Lines, or Polygons to define the site shape. You must choose the correct draw tool on the map for each of these. You may drag the highlighted vertices. To delete a vertex, place the mouse over the vertex and press the 'd' or 'Delete' buttons.<br />You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_DE_Instructions2'=>"To choose a site, either click the relevant site on the map (ensuring that the select tool on the map is active) or pick it from the drop down list.<br />You may add a new site: click the 'Create a new site' button on the map, select the map tool for the type of item you wish to draw, and draw on the map, clicking on each point. Double click on the final point of a line or polygon to complete it. At this point you will notice some small red circles appear around the newly drawn feature: you can change the boundary by dragging these circles. To delete a point, place the mouse over the red circle, and press the 'd' or 'Delete' buttons on the keyboard.<br />Selecting an existing site will remove any new site.<br />It is only possible to change the details for a site (e.g. name or boundary) on this form once it has been saved if you are either an Admin user or are the only person to have registered a survey at this site.",
	'LANG_LocModTool_IDLabel'=>'Old site name',
	'LANG_DE_LocationIDLabel'=>'Site',
	'LANG_CommonChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_NoSitesInSquare'=>'There are no sites currently associated with this square',
	'LANG_CommonEmptyLocationID'=>'Choose an existing site',
	'LANG_CommonLocationNameLabel' => 'Site name',
	'LANG_LocModTool_NameLabel'=>'New site name',
	'LANG_LocModTool_DeleteLabel'=>'Delete site',
	'LANG_LocModTool_DeleteInstructions'=>'When a site is deleted, any existing visit data will still be available in the reports. New surveys for this square will not feature the site.',
	'LANG_TooFewPoints' => 'There are too few points in this polygon - there must be at least 3.',
	'LANG_TooFewLinePoints' => 'There are too few points in this line - there must be at least 2.',
	'LANG_CentreOutsideParent'=>'Warning: the centre of your new site is outside the square.',
	'LANG_PointOutsideParent'=>'Warning: the point you have created for your site is outside the square.',
	'LANG_LineOutsideParent'=>'Warning: the line you have created for your site has a centre which is outside the square.',
	'LANG_PolygonOutsideParent'=>'Warning: the polygon you have created for new site has a centre which is outside the square.',
	'LANG_ConfirmRemoveDrawnSite'=> "This action will remove the existing site you have created. Do you wish to continue?",
	'LANG_SelectTooltip'=>'Click on map to select a site',
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
	'LANG_Location_Type_Label'=>'Site Type',
	'LANG_Location_Type_Primary'=>'Submitted',
	'LANG_Location_Type_Secondary'=>'Confirmed',
	'LANG_CommonLocationCodeLabel'=>'Code',

	'LANG_Tab_otherinformation' => 'Conditions',
	'LANG_Date' => 'Date',
	'LANG_Site_Extra' => "(Visit number / Number of visits during winter)",
	'Cavity entrance' => 'Cavity entrance',
	'Cavity entrance comment' => 'If the closure system is defective',
	'Disturbances' => 'Disturbances',
	'Disturbances other comment' => 'If Others',
	'Temp Exterior' => "Temperature outside cavity (Celcius)",
	'Humid Exterior' => "Relative Humidity outside cavity (%)",
	'Temp Int 1' => "Temperature inside cavity - A (Celcius)",
	'Humid Int 1' => "Relative Humidity inside cavity - A (%)",
	'Temp Int 2' => "Temperature inside cavity - B (Celcius)",
	'Humid Int 2' => "Relative Humidity inside cavity - B (%)",
	'Temp Int 3' => "Temperature inside cavity - C (Celcius)",
	'Humid Int 3' => "Relative Humidity inside cavity - C (%)",
	'Positions marked' => 'Measurement location(s) indicated on map',
	'Reliability' => "Reliability (completeness) of the inventory",
	'Overall Comment' => 'Comment',

	'LANG_Tab_species' => 'Species',
	'species_checklist.species'=>'Species',
	'Bats Obs Type' => "Observation type",
	'SCLabel_Col1' => "Number of individuals",
	'SCLabel_Row1' => 'Alive',
	'SCLabel_Row2' => 'Dead',
	'LANG_Duplicate_Taxon' => 'You have chosen a taxon for which there is already an entry.',
	'Are you sure you want to delete this row?' => 'Etes-vous sûr de vouloir supprimer cette ligne?',

	'validation_no_observation' => "The <strong>No observation</strong> must be checked if and only if there is no data in the species grid.",
	'validation_fillgroup'=>'Please enter one of these three fields.'
	
	
);