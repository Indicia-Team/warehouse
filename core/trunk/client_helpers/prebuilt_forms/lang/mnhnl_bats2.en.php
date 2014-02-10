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
	'LANG_Locations' => 'Locations',
	'LANG_Data_Download' => 'These reports provide details of the data entered in the survey.',
	'LANG_Edit' => 'Edit',
	'LANG_Add_Sample' => 'Add new sample',
	'LANG_Add_Sample_Single' => 'Add single occurrence',
	'LANG_Add_Sample_Grid' => 'Add list of occurrences',
	'LANG_Trailer_Text' => "Coordination of the biodiversity monitoring programme in Luxembourg: <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (Département Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Ministère du Développement durable et des Infrastructures</a> (Département de l'environnement)",

	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site name',
	'LANG_Multiple_Location_Types' => 'Some sites are highlighted in red because they are not yet confirmed by an Admin user.',
	'LANG_Location_X_Label' => 'Site centre coordinates: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'Code GSL' => 'GSL Code',
	'Profondeur' => 'Depth',
	'LANG_PositionOutsideCommune' => 'The position you have chosen is outside the set of allowable Communes. You will not be able to save this position.',
	'LANG_CommuneLookUpFailed' => 'Commune Lookup Failed',
	'LANG_Save' => 'Save',
	'LANG_Cancel' => 'Cancel',
	'LANG_Submit' => 'Save',

	'validation_required' => 'Please enter a value for this field',
	'validation_integer' => 'Please enter an integer',

	'LANG_Location_Code_Label' => 'Code',
	'LANG_Location_Name_Blank_Text' => 'Choose a location using its name',
	'Site type2' => 'Site type',
	'LANG_SRef_Label' => 'Coordinates',
	'LANG_Georef_Label'=>'Search for place on map',
	'LANG_Georef_SelectPlace' => 'Select the correct one from the following places that were found matching your search. (Click on the list items to see them on the map.)',
	'LANG_Georef_NothingFound' => 'No place found with that name. Try a nearby town name.',
	'search' => 'Search',
	'Location Comment' => 'Comment',
	'Village' => 'Village / Locality',
	'VillageDD' => 'Village / Locality',
	'Site type other' => 'If Others',
	// 'Precision' is unchanged in English

	'LANG_CommonInstructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (5x5km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_Instructions2'=>"Either click on the map (ensuring that the select tool on the map is active) to select the site you wish to modify, or choose from the drop down list. You may then change its name, or move the position of the point. You may drag the point. To delete a point, place the mouse over the purple circle and press the 'Delete' button on the keyboard.<br />Selecting an existing site, re-clicking the 'Start a new site' (tick) button or clicking the 'Remove the selected new site' (red cross) button will remove any new site.",
	'LANG_LocModTool_CantCreate' => "You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_DE_Instructions2'=>"To choose a site, either click the relevant site on the map (ensuring that the select tool on the map is active) or pick it from the drop down list (then the selected site is highlighted in blue on the map).<br />You may add a new site: click the 'Start a new site' button on the map, and click on the map where you want the new site to be. Sites may be composed of one or more points. To delete a point, place the mouse over the purple circle and press the 'Delete' button on the keyboard.<br />Selecting an existing site, re-clicking the 'Start a new site' (tick) button or clicking the 'Remove the selected new site' (red cross) button will remove any new site.<br />It is only possible to change the details for a site (e.g. name or position) on this form once it has been saved if you are either an Admin user or are the only person to have registered a survey at this site.",
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
	'LANG_SHP_Download_Legend'=> 'SHP File Downloads',
	'LANG_Shapefile_Download'=> 'This download provide a zipped up shape files for the points in the locations. Click to select:',
	'LANG_PointsLegend'=>'Coordinates of individual points',
	'LANG_Grid_X_Label'=>'X',
	'LANG_Grid_Y_Label'=>'Y',
	'LANG_DeletePoint'=>'Delete this point',
	'LANG_AddPoint'=>'Add this point',
	'LANG_HighlightPoint'=>'Highlight this point',

	'Target Species' => 'Target species - specify the species that was targeted by the survey, if any',
	'LANG_Date' => 'Date',
	'LANG_Date_Explanation' => '(Indicate the date of the beginning of the survey)',
	'Recorder names' => 'Observer(s)',
	'Accompanied By' => 'Accompanying people',
	'Institution' => 'Institution(s)',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",
	'Site followup' => 'Pertinence of site for a regular followup',
	'Sketch provided' => 'A sketch has been provided with the paper copy',
	'Disturbances2' => 'Disturbances',
	'Disturbances other comment' => 'If Others',
	'Temperature' => 'Temperature (&degC)',
	'Wind force' => 'Wind force (Bf)',
	'Humid Exterior' => "Relative humidity (%)",
	'Reliability' => "Reliability (completeness) of the inventory",
	'Precipitation2' => 'Precipitation',
	'Overall Comment' => 'Comment',
	'LANG_Confirm_Survey_Method_Removal'=>'You are deselecting a survey method. This may result in some data being removed from the Species grid. Do you wish to continue?',

	'LANG_Tab_species' => 'Species',
	'species_checklist.species'=>'Species',
	'species_checklist.observations'=>'Observations',
	'LANG_SpeciesInstructions'=>"Report observations of any bat species separately using the control under the grid.<br />Click the red 'X' to delete the relevant row.<br />Observations may only be reported for the survey methods you selected in the Conditions grid.",
	'LANG_Duplicate_Taxon' => 'You have chosen a taxon for which there is already an entry.',
	'Num alive'=>'Individuals (alive)',
	'Num dead'=>'Individuals (dead)',
	'Emergence count'=>'Individuals',
	'Picture of Maternity Count'=>'Individuals',
	'No record'=>'No record (no bat species recorded during the survey)',
	'Species Comment'=>'Comment',

	'validation_observation_type'=>'At least one observation type must be provided.',
	'validation_method-presence'=>'At least one Survey method must be selected.',
	'validation_smg-endtime'=>'The end time must be after the start time',
	'validation_scNumDead'=>'Sum of alive and dead individuals must be equal to or greater than 1',
	'validation_no_record' => "The <strong>No record</strong> must be checked if and only if there is no data in the species grid.",
	'validation_taxon_data' => 'Data must be entered for at least one survey method for each taxon.',
	'next step'=>'Next step',
	'prev step'=>'Previous step'

);