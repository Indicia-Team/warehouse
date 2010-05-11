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
 * Language terms for the pollenators form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Insufficient_Privileges' => "You do not have sufficient privileges to have access to the 'Create a Collection' page"
	,'LANG_Collection_Name_Label' => 'Name your Collection'
	,'LANG_Validate' => 'Save'
	,'LANG_Modify' => 'Modify'
	,'LANG_Reinitialise' => 'Reinitialise'
	,'LANG_Collection_Details' => 'Collection Details'
	,'Protocol' => 'Choose a protocol'
	,'LANG_Protocol_Title_Label' => 'Protocol'
	,'LANG_Unable_To_Reinit' => 'Unable to reinitialise because existing values do not pass validation'
	,'LANG_Confirm_Reinit' => 'Are you sure you want to reinitialise? All data against this collection will be deleted.'
	
	,'LANG_Flower_Station' => "YOUR FLORAL STATION"
	,'LANG_Upload_Flower' => 'Upload a picture of the Flower'
	,'LANG_ID_Flower_Later' => 'You would prefer to identify it later:'
	,'LANG_Identify_Flower' => 'Select the name of this flower'
	,'LANG_Flower_Species' => "You know the taxon for this flower"
	,'LANG_Choose_Taxon' => "Choose a Taxon from the list"
	,'LANG_Flower_ID_Key_label' => "You don't know the name of this flower"
	,'LANG_Launch_ID_Key' => "Launch the identification tool"
	,'LANG_Upload_Environment' => 'Upload a picture of the Environment'
	,'LANG_Environment_Notes' => 'This image must reflect the botanical environment of the flower (typically a 2 meter wide area)'
	,'LANG_Location_Notes' => 'Flower Location : click on the map or use the fields below:'
	,'LANG_Georef_Label' => 'Location'
	,'LANG_Georef_Notes' => '(This may be a village/town/city name, region, department or 5 digit postcode.)'
	,'LANG_Or' => 'or :'
	,'LANG_INSEE' => 'INSEE No.'
	,'LANG_Lat' => 'Lat./Long.'
	,'Flower Type' => "The Flower is"
	,'Habitat' => "The habitat is"
	,'Nearest House' => "Approximate distance between your flower and the nearest honeybee hive (metres)"
	,'LANG_Validate_Flower' => 'Save this Floral Station'
	,'LANG_Must_Provide_Pictures' => 'Pictures must be provided for the Flower and Environment'
	,'LANG_Must_Provide_Location' => 'A location must be selected'
	
	,'LANG_Sessions_Title' => 'My Sessions'
	,'LANG_Session' => 'Session'
	,'LANG_Date' => 'Date'
	,'LANG_Validate_Session' => 'Save Session'
	,'LANG_Add_Session' => 'Add Session'
	,'LANG_Delete_Session' => 'Delete this Session'
	,'LANG_Cant_Delete_Session' => "The session can't be deleted as there are still some insectsw associated with it."
	,'LANG_Confirm_Session_Delete' => 'Are you sure you want to delete this session?'
	
	,'LANG_Photos' => 'YOUR INSECT PHOTOS'
	,'LANG_Photo_Blurb' => 'Enter or modify your observations.'
	,'LANG_Upload_Insect' => 'Upload a picture of the insect'
	,'LANG_Identify_Insect' => 'Select the name of this insect:'
	,'LANG_Insect_ID_Key_label' => 'You do not know the name of the insect:'
	,'LANG_Launch_ID_Tool' => 'Launch the identification key tool'
	,'LANG_ID_Insect_Later' => 'You would prefer to identify it later:'
	,'LANG_Insect_Species' => 'You know the taxon for this insect:'
	,'LANG_Comment' => 'Comments'
	,'Number Insects' => "Number  of insects in the same place at the precise moment you took the photo"
	,'Foraging'=> "Click this box if you took a picture of this insect on the flower, but did not see it feeding"
	,'LANG_Validate_Insect' => 'Save Insect'
	,'LANG_Validate_Photos' => 'Save Photos'
	,'LANG_Must_Provide_Insect_Picture' => 'A picture of the insect must be provided'
	,'LANG_Confirm_Insect_Delete' => 'Are you sure you want to delete this insect?'
	,'LANG_Delete_Insect' => 'Delete Insect'
	
	,'validation_required' => 'Please enter a value for this field'
	
	,'LANG_Can_Complete_Msg' => 'You have identified the Flower and a sufficient number of insects, so you may now close the collection'
	,'LANG_Cant_Complete_Msg' => 'You have either: not identified the Flower, AND/OR not identified a sufficient number of insects. You will need to correct this before you can close the collection.'
	,'LANG_Complete_Collection' => 'Complete Collection'
	,'LANG_Trailer_Head' => 'After closing this collection'
	,'LANG_Trailer_Point_1' => "you can no longer add to this collection's list of insects: are you sure there are no more?"
	,'LANG_Trailer_Point_2' => "you can no longer change the different values describing this floral station, sessions and photos."
	,'LANG_Trailer_Point_3' => "you may modify the identification of the insects through 'My Collections'"
	,'LANG_Trailer_Point_4' => "you may create a new collection"
	,'LANG_Final_1' => 'This collection has been registered and added to your set of collections'
	,'LANG_Final_2' => "This collection may be accessed through 'My Collections', where you may change the identification of your insects"
	,'LANG_Consult_Collection' => 'View this Collection'
	,'LANG_Create_New_Collection' => 'Create New Collection'
	
	,'LANG_Help_Button' => '?'
	,'LANG_Indicia_Warehouse_Error' => 'Error returned from Indicia Warehouse'
	
);