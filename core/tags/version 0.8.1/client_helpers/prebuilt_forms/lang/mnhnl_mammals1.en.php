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
	'LANG_Save_Location_Allocations' => 'Save',
	'LANG_Date' => 'Date',
	'LANG_Save' => 'Save',
	'LANG_Save_Redisplay' => 'Save and Redisplay',
	'LANG_Save_and_New' => 'Save then enter another new occurrence',
	'LANG_Cancel' => 'Cancel',
	'Latitude' => 'Coordinates: X',
	'Longitude' => 'Y',
	
	'LANG_CommonInstructions1'=>'Choose a square (1x1km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (1x1km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_IDLabel'=>'Old site name',
	'LANG_DE_LocationIDLabel'=>'Site',
	'LANG_CommonChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_CommonEmptyLocationID'=>'Choose an existing site',
	'LANG_NoSitesInSquare'=>'There are no sites currently associated with this square',
	'LANG_NoSites'=>'There are currently no sites defined: please create a new one.',
	'LANG_Location_X_Label' => 'Site centre coordinates: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'LANG_CommonLocationNameLabel' => 'Site',
	'LANG_LocModTool_DeleteLabel'=>'Delete site',
	'LANG_LocModTool_DeleteInstructions'=>'When a site is deleted, any existing visit data will still be available in the reports.',
	'LANG_LocModTool_NameLabel'=>'New site name',
	'LANG_DE_Instructions2'=>"To choose a site, either click the relevant site on the map (ensuring that the select tool on the map is active) or pick it from the drop down list.<br />You may add a new site: ensure a square has been selected, click the 'Start a new site' button on the map, select the map tool for the type of item you wish to draw, and draw on the map, clicking on each point. Double click on the final point of a line or polygon to complete it. At this point you will notice some small red circles appear around the newly drawn feature: you can change the boundary by dragging these circles. To delete a point, place the mouse over the red circle, and press the 'd' or 'Delete' buttons on the keyboard.<br />Selecting an existing site will remove any new site.<br />It is not possible to change a site name or boundary on this form once it has been saved - this can be done by an Admin user using their special tool.",	'LANG_LocModTool_Instructions2'=>"Either click on the map to select the site you wish to modify, or choose from the drop down list. You may then change its name, or its extent on the map by dragging the red vertices. To delete a vertex, place the mouse over the vertex and press the 'd' or 'Delete' buttons. You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_TooFewPoints' => 'There are too few points in this polygon - there must be at least 3.',
	'LANG_TooFewLinePoints' => 'There are too few points in this line - there must be at least 2.',
	'LANG_CentreOutsideParent'=>'Warning: the centre of your new site is outside the square.',
	'LANG_PointOutsideParent'=>'Warning: the point you have created for your site is outside the square.',
	'LANG_LineOutsideParent'=>'Warning: the line you have created for your site has a centre which is outside the square.',
	'LANG_PolygonOutsideParent'=>'Warning: the polygon you have created for new site has a centre which is outside the square.',
	'LANG_ConfirmRemoveDrawnSite'=> "This action will remove the existing site you have created. Do you wish to continue?",
	'LANG_ChoseParentWarning'=> "You can only add a new site after picking a square.",
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
	'LANG_DuplicateName'=>'Warning: there is another location with this name.',
	'LANG_PointsLegend'=>'Coordinates of individual points',
	'LANG_Grid_X_Label'=>'X',
	'LANG_Grid_Y_Label'=>'Y',
	'LANG_DeletePoint'=>'Delete this point',
	'LANG_AddPoint'=>'Add this point',
	'LANG_HighlightPoint'=>'Highlight this point',
	'LANG_SHP_Download_Legend'=> 'SHP File Downloads',
	'LANG_Shapefile_Download'=> 'These downloads provide zipped up shape files for the locations; due to the restrictions of the SHP file format, there are separate downloads for lines and polygons. Click to select:',
	'LANG_Date' => 'Date',
	'Recorder names' => 'Observer(s)',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
	'Overall Comment' => 'Comment'
	
);