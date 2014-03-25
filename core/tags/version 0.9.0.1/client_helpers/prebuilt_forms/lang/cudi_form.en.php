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
 * Additional language terms or overrides for cudi_form.
 *
 * @package	Client
 */
$custom_terms = array_merge($custom_terms, array(
  'LANG_Annotation_Name' => 'Annotation Name',
  'LANG_Location_Name' => 'Name',
  'LANG_Comment' => 'Reason For Change',
  'LANG_Location_Code' => 'Abbreviation',
  'LANG_Location_Created_By' => 'CUDI Form Completed By',
  'LANG_Location_Created_On' => 'Date Completed On',
  'LANG_Location_Surveys_Date' => 'Survey\'s Date Of First Use',
  'LANG_Location_Type' => 'Location Type',
  'LANG_Existing_Annotations' => 'Existing Annotations',
  'LANG_Annotation_Type' => 'Annotation Type',
  'LANG_Location_Preferred_Boundary' => 'Preferred Boundary',
  'LANG_Add_Location' => 'Add New Location',
  'LANG_No_User_Id' => 'This form is configured to show the user a grid of their existing records which they can add to or edit. ' .
    'To do this, the form requires that a function hostsite_get_user_field exists and returns their Indicia User ID' .
    'In Drupal, the Easy Login module in conjuction with the iForm module achieves this. '.
    'Alternatively you can tick the box "Skip initial grid of data" in the "User Interface" section of the Edit page for the form.'
  )
);