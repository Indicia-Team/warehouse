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
	'LANG_Add_Sample' => 'Add New Sample',
	'LANG_Add_SubSample' => 'Add New Occurrence',
	'LANG_Add_SubSample_Single' => 'Add Single Occurrence',
	'LANG_Add_SubSample_Grid' => 'Add List of Occurrences',

	'LANG_Tab_species' => 'What Did You See?',
	// The species and presence column titles in the grid may be set by adding entries for 'species_checklist.species'
	// and 'species_checklist.present'. All the others are not translated, but taken directly from the attribute caption.
	'LANG_Sample_Comment_Label' => 'Description of Others field',

	'LANG_Tab_place' => 'Where Was It?',
	'LANG_SRef_Label' => 'Spatial Ref',
	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Site Name',
	'LANG_Georef_Label' => 'Search for Place on Map',
	// The search button may be changed by adding an entry for 'search'
	
	'LANG_Other_Information_Tab' => 'Other Information',
	'LANG_Date' => 'Date',

	'Voucher' => 'Voucher Specimen taken?',
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

	'validation_required' => 'Please enter a value for this field'

);