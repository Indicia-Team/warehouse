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
 * Language terms for the mnhnl_bird_transect_walks form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Trailer_Text' => "Coordination of the Common Bird Monitoring in Luxembourg: <a href='http://www.naturemwelt.lu/' target='_blank'>natur&ëmwelt</a> (Centrale ornithologique Luxembourg), <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (Département Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Ministère du Développement durable et des Infrastructures</a> (Département de l'environnement)",
	'LANG_not_logged_in' => 'You must be logged in to display the contents of this node.',
	'LANG_Error_When_Moving_Sample' => 'An error has occurred during the merge process. Failed to move an occurrence.',
	'LANG_Error_When_Deleting_Sample' => 'An error has occurred during the merge process. Failed to delete empty survey.',
	'LANG_Location_Layer' => 'Location Layer',
	'LANG_Occurrence_List_Layer'=> 'Occurrence List Layer',
	'LANG_Surveys' => 'Surveys',
	'LANG_Allocate_Locations' => 'Allocate Locations',
	'LANG_Transect' => 'Transect',
	'LANG_Date' => 'Date',
	'LANG_Visit_No' => 'Visit No',
	'LANG_Num_Occurrences' => '# Occurrences',
	'LANG_Num_Species' => '# Species',
	'LANG_Show' => 'Show',
	'LANG_Add_Survey' => 'Add New Survey',
	'LANG_Not_Allocated' => 'Not Allocated',
	'LANG_Survey' => 'Survey',
	'LANG_Show_Occurrence' => 'Show Occurrence',
	'LANG_Edit_Occurrence' => 'Edit Occurrence',
	'LANG_Add_Occurrence' => 'Add Occurrence',
	'LANG_Occurrence_List' => 'Occurrence List',
	'LANG_Read_Only_Survey' => 'This Survey is Read Only.',
	'LANG_Read_Only_Occurrence' => 'This Occurrence has been downloaded and is now Read Only.',
	'LANG_Found_Mergable_Surveys' => 'A number of surveys have been found which share the same transect and date combination as this one.',
	'LANG_Merge_With_ID' => 'Merge this survey with id',
	'LANG_Save_Survey_Details' => 'Save Survey Details',
	'LANG_Save_Survey_And_Close' => 'Save and Close Survey',
	'LANG_Close_Survey_Confirm' => 'Are you sure you wish to close this survey?',
	'LANG_Indicia_Warehouse_Error' => 'Error returned from Indicia Warehouse',
	'LANG_Survey_Already_Exists' => 'A Survey already exists with this transect/date combination. Are you sure you wish to create/save this one?',
	'LANG_Species' => 'Species',
	'LANG_Spatial_ref' => 'Spatial Ref.',
	'LANG_Click_on_map' => 'Click on map to set the spatial reference',
	'LANG_Comment' => 'Comment',
	'LANG_Save_Occurrence_Details' => 'Save Occurrence Details',
	'LANG_Territorial' => 'Territorial',
	'LANG_Count' => 'Count',
	'LANG_Highlight' => 'Highlight',
	'LANG_Download' => 'Reports and Downloads',
	'LANG_Direction_Report' => 'Run a report to check that all non downloaded closed surveys have been walked in the same direction as the previously entered survey on that location. Returns the surveys which are in a different direction.',
	'LANG_Direction_Report_Button' => 'Run Survey Direction Warning Report - CSV',
	'LANG_Verified_Data_Report' => 'Run a report to return all occurrences that have been verified.',
	'LANG_Verified_Data_Report_Button' => 'Run Verified Data Report - CSV',
	'LANG_Initial_Download' => 'Carry out initial download of closed surveys. Sweeps up all records which are in closed surveys but which have not been downloaded yet',
    'LANG_Initial_Download_Button' => 'Initial Download - CSV',
	'LANG_Confirm_Download' => 'Carry out confirmation download. This outputs the same data that will be included in the final download, but does not tag the data as downloaded. Only includes data in the last initial download unless a survey has since been reopened, when it will be excluded from this report.',
    'LANG_Confirm_Download_Button' => 'Confirmation Download - CSV',
	'LANG_Final_Download' => 'Carry out final download. Data will be marked as downloaded and no longer editable.',
    'LANG_Final_Download_Button' => 'Final Download - CSV',
	'LANG_Complete_Final_Download' => 'Output all previously downloaded data. This does not do the marking that the final download does.',
    'LANG_Complete_Final_Download_Button' => 'Complete Download - CSV',
    'LANG_Download_Occurrences' => 'Download CSV List of Occurrences',
	'LANG_No_Access_To_Occurrence' => 'This record is not a valid occurrence.',
	'LANG_No_Access_To_Location' => 'You have not been allocated the location against which this survey was carried out - you can not access this record.',
	'LANG_No_Access_To_Sample' => 'This record is not a valid top level sample.',
	'LANG_Page_Not_Available' => 'This page is not available at this time.',
	'LANG_Return' => 'Return to main survey selection screen',
//	'Atlas Code' => 'Atlas Code',
//	'Approximation?' => 'Approximation?',
//	'Overflying' => 'Overflying',
//	'Closed' => 'Closed',
//	'Cloud cover' => 'Cloud Cover',
//	'Confidence' => 'Confidence',
//	'Count' => 'Count',
//	'End time' => 'End Time',
	'No' => 'No',
	'validation_required' => 'Please enter a value for this field',
//	'Precipitation' => 'Precipitation',
//	'Reliability of this data' => 'Reliability of this data',
//	'Start time' => 'Start Time',
//	'Temperature (Celsius)' => 'Temperature (Celsius)',
//	'Territorial' => 'Territorial',
//	'Visit number in year' => 'Visit number in year',
//	'Walk started at end' => 'Walk started at end',
//	'Wind force' => 'Wind force',
	'Yes' => 'Yes'
);
