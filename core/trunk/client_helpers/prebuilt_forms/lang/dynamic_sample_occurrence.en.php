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

include_once 'dynamic.en.php';

/**
 * Additional language terms or overrides for dynamic_sample_occurrence form.
 *
 * @package	Client
 */
$custom_terms = array_merge($custom_terms, array(
	'LANG_Add_Sample' => 'Add New Sample',
        'LANG_Add_Sample_Single' => 'Add Single Occurrence',
        'LANG_Add_Sample_Grid' => 'Add List of Occurrences',

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
	
	'LANG_Other_Information_Tab' => 'Other Information',
	'LANG_Tab_Instructions _otherinformation'=>'<strong>Other Information</strong><br/>Please tell us when the observation took place, the biotope, whether a voucher specimen was taken (in the case where identification was difficult, to allow verification), and whether the observation details have been completed.',

        'LANG_Record_Status_Label' => 'Record Status',
	'LANG_Record_Status_I' => 'In Progress',
	'LANG_Record_Status_C' => 'Completed',
	'LANG_Record_Status_V' => 'Verified', // NB not used

        'LANG_No_User_Id'=> 'This form is configured to show the user a grid of their existing records which they can add to or edit. ' .
          'To do this, the form requires that either it must be used with a survey that includes the CMS User ID attribute in the '.
          'list of attributes configured for the survey on the warehouse or that a function hostsite_get_user_field exists and returns' .
          'their Indicia User ID. This allows records to be tagged against the user. ' .
          'Alternatively you can tick the box "Skip initial grid of data" in the "User Interface" section of the Edit page for the form.'
)
);