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
// Tab Titles
	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_Locations' => 'Sites',
	'LANG_Trailer_Text' => "Coordination of the biodiversity monitoring programme in Luxembourg: <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (Département Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Ministère du Développement durable et des Infrastructures</a> (Département de l'environnement)",
// Navigation
	'LANG_Edit' => 'Edit',
	// Edit is unchanged in English
	'LANG_Add_Sample' => 'Add new sample',
	'LANG_Add_Sample_Single' => 'Add single occurrence',
	'LANG_Add_Sample_Grid' => 'Add list of occurrences',
	'LANG_Save' => 'Save',
	'save' => 'Save',
	'LANG_Cancel' => 'Cancel',
	'LANG_Submit' => 'Save',
	'next step'=>'Next step',
	'prev step'=>'Previous step',
// Main grid Selection
	// 'Site name' is unchanged in English
	// 'Actions' is unchanged in English
// Reports
	'LANG_Data_Download' => 'These reports provide details of the data entered in the survey.',
// Locations
	// 'Existing locations' is unchanged in English
	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site name',
	// 'Create New Location' is unchanged in English
	'LANG_Location_Name_Blank_Text' => 'Choose a location using its name',
	'LANG_Multiple_Location_Types' => 'Some sites are highlighted in red because they are not yet confirmed by an Admin user.',
	'SRef' => 'Coordinates',
	'LANG_SRef_Label' => 'Coordinates',
	'LANG_Location_X_Label' => 'Site centre coordinates: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'LANG_Location_Code_Label' => 'Code',
	'Location Comment' => 'Comment',
	'LANG_CommonInstructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (5x5km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_Instructions2'=>"Either click on the map (ensuring that the select tool on the map is active) to select the site you wish to modify, or choose from the drop down list. You may then change its name, or modify or add points to define the site. You may drag the highlighted points. To delete a point, place the mouse over the purple circle and press the 'Delete' button on the keyboard.<br />Selecting an existing site, re-clicking the 'Start a new site' (tick) button or clicking the 'Remove the selected new site' (red cross) button will remove any new site.",
	'LANG_LocModTool_CantCreate' => "You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_DE_Instructions2'=>"To choose a site, either click the relevant site on the map (ensuring that the select tool on the map is active) or pick it from the drop down list (then the selected site is highlighted in blue on the map).<br />You may add a new site: click the 'Start a new site' button on the map, select the point draw tool, and draw on the map, clicking on each point. You may drag the highlighted points. To delete a point, place the mouse over the purple circle, and press the 'Delete' button on the keyboard.<br />Selecting an existing site, re-clicking the 'Start a new site' (tick) button or clicking the 'Remove the selected new site' (red cross) button will remove any new site.<br />It is only possible to change the details for a site (e.g. name or boundary) on this form once it has been saved if you are either an Admin user or are the only person to have registered a survey at this site.",
	'LANG_LocModTool_IDLabel'=>'Old site name',
	'LANG_DE_LocationIDLabel'=>'Site',
	'LANG_CommonChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_NoSitesInSquare'=>'There are no sites currently associated with this square',
	'LANG_NoSites'=>'There are currently no sites defined: please create a new one.',
	'LANG_CommonEmptyLocationID'=>'Choose an existing site',
	'LANG_CommonLocationNameLabel' => 'Site name',
	'LANG_LocModTool_NameLabel'=>'New site name',
	'LANG_LocModTool_DeleteLabel'=>'Delete site',
	'LANG_LocModTool_DeleteInstructions'=>'When a site is deleted, any existing visit data will still be available in the reports.',
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
	'LANG_Location_Type_Label'=>'Site status',
	'LANG_Location_Type_Primary'=>'Submitted',
	'LANG_Location_Type_Secondary'=>'Confirmed',
	'LANG_CommonLocationCodeLabel'=>'Code',
	'LANG_LocationModTool_CommentLabel'=>'Comment',
	'LANG_DuplicateName'=>'Warning: there is another location with this name.',
	'LANG_PointsLegend'=>'Coordinates of individual points',
	'LANG_Grid_X_Label'=>'X',
	'LANG_Grid_Y_Label'=>'Y',
	'Latitude' => 'Coordinates: X',
	'Longitude' => 'Y',
	'LANG_DeletePoint'=>'Delete this point',
	'LANG_AddPoint'=>'Add this point',
	'LANG_HighlightPoint'=>'Highlight this point',
	'LANG_SHP_Download_Legend'=> 'SHP File Downloads',
	'LANG_Shapefile_Download'=> 'This download provide a zipped up shape files for the points in the locations. Click to select:',
// Georeferencing
	'search' => 'Search',
	'LANG_Georef_Label'=>'Search for place on map',
	'LANG_Georef_SelectPlace' => 'Select the correct one from the following places that were found matching your search. (Click on the list items to see them on the map.)',
	'LANG_Georef_NothingFound' => 'No place found with that name. Try a nearby town name.',
	'LANG_PositionOutsideCommune' => 'The position you have chosen is outside the set of allowable Communes. You will not be able to save this position.',
	'LANG_CommuneLookUpFailed' => 'Commune Lookup Failed',
// Conditions
	// 'General'  is unchanged in English
	'Physical' => 'Physical characteristics of the cavity',
	// 'Microclimate' is unchanged in English
	'LANG_Date' => 'Date',
	'Recorder names' => 'Observer(s)',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
	'LANG_Site_Extra' => "(Visit number / Number of visits during winter)",
	'Overall Comment' => 'Comment',
// Species
	'species_checklist.species'=>'Species',
	'LANG_Duplicate_Taxon' => 'You have chosen a taxon for which there is already an entry.',
	'LANG_SpeciesInstructions'=>"Additional species may be added using the control under the grid. Only one row may be added per taxon.<br />Click the red 'X' to delete the relevant row.",
	//'Comment' is unchanged in English
// Attributes
	'Village' => 'Village / Locality',
	// 'Site type' is unchanged in English
	'Site type other' => 'If Others',
	'Code GSL' => 'GSL code',
	// 'Depth' is unchanged in English
	// 'Precision' is unchanged in English
	// 'Development' is unchanged in English
	'Site followup' => 'Pertinence of site for a regular followup',
	'Accompanied By' => 'Accompanying people',
	'Visit' => 'Visit',
	'Bat visit' => 'Visit',
	'Cavity entrance' => 'Cavity entrance',
	'Cavity entrance comment' => 'If the closure system is defective',
	'Disturbances' => 'Disturbances',
	'Disturbances other comment' => 'If Others',
	// 'Human frequentation' is unchanged in English
	'Temp Exterior' => "Temperature outside cavity (Celcius)",
	'Humid Exterior' => "Relative humidity outside cavity (%)",
	'Temp Int 1' => "Temperature inside cavity - A (Celcius)",
	'Humid Int 1' => "Relative humidity inside cavity - A (%)",
	'Temp Int 2' => "Temperature inside cavity - B (Celcius)",
	'Humid Int 2' => "Relative humidity inside cavity - B (%)",
	'Temp Int 3' => "Temperature inside cavity - C (Celcius)",
	'Humid Int 3' => "Relative humidity inside cavity - C (%)",
	'Positions marked' => 'Measurement location(s) indicated on map',
	'Reliability' => "Reliability (completeness) of the inventory",
	'Num alive' => 'Number alive',
	'Num dead' => 'Number dead',
	// 'Excrement' is unchanged in English
	'Occurrence reliability' => "Reliability",
	// 'No observation' is unchanged in English
// Validation
	'validation_required' => 'Please enter a value for this field',
	'validation_no_observation' => "The <strong>No observation</strong> must be checked if and only if there is no data in the species grid.",
	'validation_fillgroup'=>'Please enter one of these three fields.'
);