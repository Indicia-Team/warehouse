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
	'LANG_Add_Sample' => 'Add new sample',
	'LANG_Add_Sample_Single' => 'Add single occurrence',
	'LANG_Add_Sample_Grid' => 'Add list of occurrences',
	'LANG_Data_Download' => 'These reports provide details of the data entered in the survey.',
	'LANG_Trailer_Text' => "Coordination of the biodiversity monitoring programme in Luxembourg: <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (Département Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Ministère du Développement durable et des Infrastructures</a> (Département de l'environnement)",

	'LANG_Tab_aboutyou' => 'About You',      
	'LANG_Tab_Instructions_aboutyou' => '<strong>About You</strong><br/>Please tell us about yourself first.',
	// Can also add entries each of the attribute captions used in this tab.
	// Note these do not have LANG_ prefixes.

	'LANG_Tab_species' => 'What Did You See?',
	'LANG_Tab_Instructions_species' => '<strong>Species Selection</strong><br/>Please click on all the species that you observed, enter the relevant additional information, and then move on to the next stage.',
	// The species and presence column titles in the grid may be set by adding entries for 'species_checklist.species'
	// and 'species_checklist.present'. All the others are not translated, but taken directly from the attribute caption.
	'LANG_Sample_Comment_Label' => 'Description of Others field',

	'LANG_Tab_place' => 'Where Was It?',
	'LANG_Tab_Instructions_place' => '<strong>Place Selection</strong><br/>Please either enter the spatial reference of the observation if you know it, or click on the map to specify the place as accurately as you can.',
	'LANG_SRef_Label' => 'Spatial Ref',
	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site Name',
	'LANG_Georef_Label' => 'Search for Place on Map',
	// The search button may be changed by adding an entry for 'search'
	
	'LANG_Other_Information_Tab' => 'Other Information',
	'LANG_Tab_Instructions _otherinformation'=>'<strong>Other Information</strong><br/>Please tell us when the observation took place, the biotope, whether a voucher specimen was taken (in the case where identification was difficult, to allow verification), and whether the observation details have been completed.',
	'LANG_Date' => 'Date',
	// Below gives an example of setting the biotope and voucher attribute captions used in this tab.
	// Note these do not have LANG_ prefixes.
	'MNHNL Collaborators 1 Biotope' => 'Biotope',
	'Voucher' => 'Voucher Specimen taken?',
	// Can also add entries for 'Yes' and 'No' for the voucher attribute
	'LANG_Record_Status_Label' => 'Record Status',
	'LANG_Record_Status_I' => 'In Progress',
	'LANG_Record_Status_C' => 'Completed',
	'LANG_Record_Status_V' => 'Verified', // NB not used
	'LANG_Image_Label' => 'Upload Image',
	'LANG_Save' => 'Save',

	'validation_required' => 'Required',
	'LANG_Cancel'=>'Cancel',
	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_Allocate_Locations' => 'Allocate Locations',
	'LANG_Transect' => 'Transect',
	'Transect' => 'Transect',
	'MNHNL Month' => 'Month',
	'transectgrid:taxa_taxon_list_id' => 'Add Species',
	'sectionlist:species' => 'Species',
	'sectionlist:section' => 'Section',
	'sectionlist:numberlabel' => 'Number of Sections',
	'transectgrid:confirmremove' => 'You are about to delete a species grid. If you do this any previously saved data will be lost. Do you still wish to remove ',
	'sectionlist:confirmremove' => 'You are about to delete a species row. If you do this any previously saved data will be lost. Do you still wish to remove ',
	'sectionlist:confirmremovecolumns' => 'You are about to remove some sections. If you do this any previously saved data will be lost. Do you still wish to do this?',
	'transectgrid:rowexists' => 'A grid for this species already exists. It is present under the preferred name of ',
	'sectionlist:rowexists' => 'A row for this species already exists. It is present under the preferred name of ',
	'transectgrid:bumpf1' => 'Observations along the transect and inside the vitual box ("X"), observations along the transect and outside the vitual box ("/") and casual observations ("O").',
	'transectgrid:bumpf2' => 'Note: when a species record is associated with multiple codes for the same grid, please consider this order of priority (1) "X", (2) "/" and (3) "O".',
	'next step'=>'Next step',
	'prev step'=>'Previous step'
	

);