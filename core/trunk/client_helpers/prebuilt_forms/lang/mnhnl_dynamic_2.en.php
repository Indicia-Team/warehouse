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
	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_SampleListGrid_Preamble' => 'Previously encoded survey list for ',
	'LANG_All_Users' => 'all users',
	'LANG_Allocate_Locations' => 'Allocate squares',
	'LANG_Data_Download' => 'These reports provide details of the data entered in the survey.',
	'LANG_TargetSpecies'=> 'Target species',
	'Target Species'=>'Target species',
	'LANG_SHP_Download_Legend'=> 'SHP File Downloads',
	'LANG_Shapefile_Download'=> 'These downloads provide zipped up shape files for the locations; due to the restrictions of the SHP file format, there are separate downloads for each of points, lines and polygons. Click to select:',
	'LANG_Edit' => 'Edit',
	'LANG_Add_Sample' => 'Add new sample',
	'LANG_Add_SubSample' => 'Add New Occurrence',
	'LANG_Add_SubSample_Single' => 'Add Single Occurrence',
	'LANG_Add_SubSample_Grid' => 'Add List of Occurrences',
	'LANG_Trailer_Text' => "Coordination of the biodiversity monitoring programme in Luxembourg: <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (Département Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Ministère du Développement durable et des Infrastructures</a> (Département de l'environnement)",
	'LANG_Locations'=>'Sites',

	'LANG_Tab_site' => 'Site',
	'LANG_CommonInstructions1'=>'Choose a square (5x5km) by either picking it from the drop down list, or clicking it on the map. This square will then be highlighted on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (5x5km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_Instructions2'=>"Either click on the map (ensuring that the select tool on the map is active) to select the site you wish to modify, or choose from the drop down list. You may then change its name, or modify or add Points, Lines, or Polygons to define the site shape. You must choose the correct draw tool on the map for each of these. You may drag the highlighted vertices. To delete a point (purple circle) or shape vertex (red circle), place the mouse over the circle and press the 'Delete' button.<br />Selecting an existing site, re-clicking the 'Start a new site' (tick) button or clicking the 'Remove the selected new site' (red cross) button will remove any new site.",
	'LANG_LocModTool_CantCreate' => "You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_DE_Instructions2'=>"To choose a site, either click the relevant site on the map (ensuring that the select tool on the map is active) or pick it from the drop down list (then the selected site is highlighted in blue on the map).<br />You may add a new site: ensure a square has been selected, click the 'Start a new site' button on the map, select the map tool for the type of item you wish to draw, and draw on the map, clicking on each point. Double click on the final point of a line or polygon to complete it. At this point you will notice some small red circles appear around the newly drawn feature: you can change the boundary by dragging these circles. To delete a point (purple circle) or shape vertex (red circle), place the mouse over the circle and press the 'Delete' button.<br />Selecting an existing site, re-clicking the 'Start a new site' (tick) button or clicking the 'Remove the selected new site' (red cross) button will remove any new site.<br />It is not possible to change a site name or boundary on this form once it has been saved - this can be done by an Admin user using their special tool.",
	'LANG_LocModTool_IDLabel'=>'Old site name',
	'LANG_DE_LocationIDLabel'=>'Site',
	'LANG_CommonChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_NoSitesInSquare'=>'There are no sites currently associated with this square',
	'LANG_NoSites'=>'There are currently no sites defined: please create a new one.',
	'LANG_CommonEmptyLocationID'=>'Choose an existing site',
	'LANG_Location_X_Label' => 'Site centre coordinates: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'LANG_CommonLocationNameLabel' => 'Site name',
	'LANG_LocModTool_NameLabel'=>'New site name',
	'LANG_CommonFilterNameLabel'=>'Existing site name',
	'LANG_LocModTool_DeleteLabel'=>'Delete site',
	'LANG_LocModTool_DeleteInstructions'=>'When a site is deleted, any existing visit data will still be available in the reports. The visit data will not be available via the data entry form, and the site will no longer appear on the map. You will not be able to undelete the site using this form.',
	'LANG_LocationModTool_CommentLabel'=>'Comment',
	'LANG_Location_Name_Blank_Text' => 'Choose a location using its name',
	'LANG_Outside_Square_Reports'=>'Outside Square Checks',
	'LANG_Outside_Square_Download_1'=> 'This report provides a list of locations whose centres are outside their parent square',
	'LANG_Outside_Square_Download_2'=> 'This report provides a list of locations which have any part of their boundaries outside the boundaries of their parent square',
	'LANG_LocModTool_ParentLabel'=>'New site square',

	'LANG_PositionInDifferentParent' => 'The position you have chosen is outside the selected parent. Do you wish to change the parent field to match the point?',
	'LANG_MustSelectParentFirst' => 'You must choose a square first, before creating a new location within it.',
	'LANG_TooFewPoints' => 'There are too few points in this polygon - there must be at least 3.',
	'LANG_TooFewLinePoints' => 'There are too few points in this line - there must be at least 2.',
	'LANG_CentreOutsideParent'=>'Warning: the centre of your new site is outside the square.',
	'LANG_PointOutsideParent'=>'Warning: the point you have created for your site is outside the square.',
	'LANG_LineOutsideParent'=>'Warning: the line you have created for your site has a centre which is outside the square.',
	'LANG_PolygonOutsideParent'=>'Warning: the polygon you have created for new site has a centre which is outside the square.',
	'LANG_ConfirmRemoveDrawnSite'=> "This action will remove the existing site you have created. Do you wish to continue?",
	'LANG_ChoseParentWarning'=> "You can only add a new site after picking a square.",
	'LANG_SelectTooltip'=>'Click on map to select a site (or a square if present)',
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
	'LANG_Save' => 'Save',
	'LANG_Cancel' => 'Cancel',
	"LANG_Submit" => 'Save',
	'LANG_DuplicateName'=>'Warning: there is another location with this name.',
	'LANG_PointsLegend'=>'Coordinates of individual points',
	'LANG_Grid_X_Label'=>'X',
	'LANG_Grid_Y_Label'=>'Y',
	'LANG_DeletePoint'=>'Delete this point',
	'LANG_AddPoint'=>'Add this point',
	'LANG_HighlightPoint'=>'Highlight this point',
	'Location Comment' => 'Comment',

// Georeferencing
	'search' => 'Search',
	'LANG_Georef_Label'=>'Search for place on map',
	'LANG_Georef_SelectPlace' => 'Select the correct one from the following places that were found matching your search. (Click on the list items to see them on the map.)',
	'LANG_Georef_NothingFound' => 'No place found with that name. Try a nearby town name.',

	'LANG_Date' => 'Date',
	'Overall Comment' => 'Comments',
	'Recorder names' => 'Observer(s)',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
	'Reptile Visit' => 'Visit',
	'Duration'=>'Duration (minutes)',
	'Unsuitability' => 'Unsuitable site for target species',
	'Picture provided' => 'Picture',
	'Weather' => 'Weather conditions',
	'Temperature' => 'Temperature (&deg Celsius)',
	'Temperature (Celsius)' => 'Temperature (&deg Celsius)',
//	Cloud cover
//	'Rain' => 'Rain',

	'LANG_Tab_species' => 'Species',
	'LANG_SpeciesInstructions'=>"Additional species may be added using the control under the grid.<br />Additional rows may be added using the control for existing taxa if a different combination of Type/Stage/Sex/Behaviour is to be added.<br />There are various combinations Type/Stage/Sex/Behaviour which are not allowed (eg an 'egg' can not be a 'dead specimen'). Such banned combinations will be greyed out in the drop down lists. In addition, it is not possible to enter multiple rows for the same combination of Species/Type/Stage/Sex/Behaviour: again duplicate possiblities will be greyed out.<br />If you think a combination is valid, but you can not select it, first check that there is no other existing row with this combination.<br />The 'No observation' can only be selected when there are no undeleted rows in the grid (when it must be selected) - otherwise it is disabled. Click the red 'X' to delete the relevant row.",
	'species_checklist.species'=>'Species',
	'Count'=>'Number',
	'Occurrence reliability'=>'Reliability',
	'Reliability'=>'Survey reliability',

	// Can also add entries for 'Yes' and 'No' for the voucher attribute
	'LANG_Record_Status_Label' => 'Record Status',
	'LANG_Record_Status_I' => 'In Progress',
	'LANG_Record_Status_C' => 'Completed',
	'LANG_Record_Status_V' => 'Verified', // NB not used
	'LANG_Image_Label' => 'Upload Image',
	'LANG_Save' => 'Save',
	'LANG_Save_Redisplay' => 'Save and Redisplay',
	'LANG_Save_and_New' => 'Save then Enter New Record',
	'LANG_Cancel' => 'Cancel',

	'LANG_Supersample_Layer' => 'Parent',
	'LANG_Subsample_Layer' => 'Children',

	'LANG_Duplicate_Taxon' => 'You have chosen a taxon for which there is already an entry.',
	'Num alive'=>'Individuals (alive)',
	'Num dead'=>'Individuals (dead)',
	'Emergence count'=>'Individuals',
	'Picture of Maternity Count'=>'Individuals',
	'Species Comment'=>'Comment',

	'validation_required' => 'Please enter a value for this field',
	'LANG_Location_X_Label' => 'Site centre coordinates: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_Species_X_Label' => 'X',
	'LANG_Species_Y_Label' => 'Y',
	'validation_no_observation' => "The <strong>No observation</strong> must be checked if and only if there is no data in the species grid.",
	'validation_targ-presence'=>'At least one target species must be selected.',
	'validation_end_time'=>'The end time must be after the start time',
	'validation_integer' => 'Please enter an integer',
	'next step'=>'Next step',
	'prev step'=>'Previous step'
	
);