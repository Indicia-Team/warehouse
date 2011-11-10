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
	'LANG_Allocate_Locations' => 'Allocate squares',
	'LANG_Save_Location_Allocations' => 'Save',
	'LANG_Data_Download' => 'This Report provides details of the data entered in the surveys.',
	'LANG_Download_Button' => 'Download',

	'LANG_Edit' => 'Edit',
	'LANG_Add_Sample' => 'Add New Sample',
	'LANG_Add_Sample_Single' => 'Add Single Occurrence',
	'LANG_Add_Sample_Grid' => 'Add List of Occurrences',

	'LANG_Tab_site' => 'Site',
	'LANG_Parent_Location_Layer' => 'Squares',
	'LANG_Site_Location_Layer' => 'Existing Locations',
	'LANG_Location_Name_Label' => 'Site Name',
	'LANG_Location_X_Label' => 'Site Centre Coordinates : X ',
	'LANG_Location_Y_Label' => 'Y ',
	'LANG_LatLong_Bumpf' => '(LUREF geographical system, in metres)',
	'Zoom to Parent' => 'Zoom to square (5x5km)',
	'Zoom to Location' => 'Zoom to site',
	'View All Country' => 'View all Luxembourg',
	'LANG_LocModTool_Instructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_LocModTool_ParentLabel'=>'Square (5x5km)',
	'LANG_LocModTool_ParentBlank'=>'Choose a square',
	'LANG_ChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_EmptyLocationID'=>'Choose an existing site',
	'LANG_LocModTool_Instructions2'=>"Either click on the map to select the site you wish to modify, or choose from the drop down list. You may then change its name, or its extent on the map by dragging the red vertices. To delete a vertex, place the mouse over the vertex and press the 'd' or 'Delete' buttons. You can't create a new site using this tool - that has to be done within the survey data entry itself.",
	'LANG_LocModTool_IDLabel'=>'Old site name',
	'LANG_LocModTool_NameLabel'=>'New site name',
	'LANG_LocModTool_DeleteLabel'=>'Delete site',
	'LANG_LocModTool_DeleteInstructions'=>'When a site is deleted, any existing visit data will still be available in the reports and when viewing the data entry form (though the site will no longer appear on the map). New surveys for this square will not feature the site.',
	'LANG_LocationIDLabel'=>'Site',
	'LANG_LocationNameLabel'=>'Name',
	'LANG_LocationCommentLabel'=>'---NA---',
	'LANG_LocationCodeLabel'=>'---NA---',
	'LANG_Locations'=>'Sites',
	'LANG_LocationModuleInstructions1'=>'Choose a square (5x5km). This square will then be displayed on the Map, along with all existing sites associated with that square.',
	'LANG_LocationModuleInstructions2'=>"To choose a site, either click the relevant site on the map or pick it from the drop down list.<br />You may add a new site: ignore the site selection drop down list, and draw the new site's boundary on the map, clicking on each point. Double click on the final point to complete it. At this point you will notice some small red circles appear around the boundary you have just drawn: you can change the boundary by dragging these circles. To delete a point, place the mouse over the red circle, and press the 'd' or 'Delete' buttons on the keyboard.<br />Selecting an existing site will remove any new site, as will drawing another boundary.<br />It is not possible to change a site name or boundary on this form once it has been saved - this can be done by an Admin user using their special tool.",
	'LANG_LocationModTool_CommentLabel'=>'Location created by',
	'LANG_ExtendName'=>' : created by ',
	'LANG_ModificationInstructions' => 'When drawing a new site: ',
	'LANG_TooFewPoints' => 'There are too few points in this polygon - there must be at least 3. Internal data not updated.',
	'LANG_CentreOutsideParent'=>'Warning: the centre of your new site is outside the square.',
	'LANG_ChoseParentWarning'=> "You can only add a new site after picking a square.",
	'LANG_Save' => 'Save',
	'LANG_Cancel' => 'Cancel',
	"LANG_Submit" => 'Save',
	'validation_required' => 'Please enter a value for this field',

	'LANG_Tab_otherinformation' => 'Conditions',
	'LANG_Date' => 'Date',
	'Overall Comment' => 'Comments',
	'Recorder names' => 'Observer(s)',
	'LANG_RecorderInstructions'=>"To select more than one observer, keep the CTRL button down.",

	'Survey (1)' => 'Survey',
	'Survey (2)' => 'Survey',
	'Duration'=>'Duration (Minutes)',
	'Suitability Checkbox' => 'Suitability',
	'Picture Provided' => 'Picture',
	'Weather' => 'Weather Conditions',
//	Temperature (Celsius)
//	Cloud Cover
	'Rain Checkbox' => 'Rain',
	
	'LANG_Tab_species' => 'Species',
	'LANG_SpeciesInstructions'=>"For a newly created survey, this grid will be populated with rows for species immediately relevant to this survey. Additional species may be added using the control under the grid.<br />Additional rows may be added using the control for existing taxa if a different combination of Type/Stage/Sex/Behaviour is to be added.<br />There are various combinations Type/Stage/Sex/Behaviour which are not allowed (eg an 'egg' can not be a 'dead specimen'). Such banned combinations will be greyed out in the drop down lists. In addition, it is not possible to enter multiple rows for the same combination of Species/Type/Stage/Sex/Behaviour: again duplicate possiblities will be greyed out.<br />If you think a combination is valid, but you can not select it, first check that there is no other existing row with this combination.<br />The 'No observation' can only be selected when there are no undeleted rows in the grid (when it must be selected) - otherwise it is disabled. Click the red 'X' to delete the relevant row.",
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