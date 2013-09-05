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
	,'LANG_Please_Refresh_Page' => "An error has been detected which prevents further use of this page. Please refresh this page to continue."
	,'LANG_Collection_Name_Label' => 'Name your Collection '
	,'LANG_Validate' => 'Save'
	,'LANG_Modify' => 'Modify'
	,'LANG_Reinitialise' => 'Reinitialise'
	,'LANG_Collection_Details' => 'Collection Details'
	,'Protocol' => 'Choose a protocol'
	,'LANG_Protocol_Title_Label' => 'Protocol'
	,'LANG_Unable_To_Reinit' => 'Unable to reinitialise because existing values do not pass validation'
	,'LANG_Confirm_Reinit' => 'Are you sure you want to reinitialise? All data against this collection will be deleted.'
	,'LANG_Collection_Trailer_Point_1' => 'You can only create one collection at a time,'
	,'LANG_Collection_Trailer_Point_2' => 'In other words, you can only create another collection when this collection has been either completed or reinitialised.'
	
	,'LANG_Upload' => 'Upload'
	,'LANG_Flower_Station' => "YOUR FLORAL STATION"
	,'LANG_Upload_Flower' => 'Upload a picture of the Flower'
	,'LANG_ID_Flower_Later' => 'You would prefer to identify it later:'
	,'LANG_Identify_Flower' => 'Select the name of this flower'
	,'LANG_Flower_Species' => "You know the taxon for this flower"
	,'LANG_Choose_Taxon' => "Choose a Taxon from the list"
	,'LANG_Flower_ID_Key_label' => "You don't know the name of this flower"
	,'LANG_Launch_ID_Key' => "Launch the identification tool"
	,'LANG_Cancel_ID' => "Abort the identification tool"
	,'LANG_Taxa_Returned' => "Taxa returned by ID Tool:"
	,'LANG_ID_Unrecognised' => 'The following were returned by the ID tool but are unrecognised: '
	,'LANG_Upload_Environment' => 'Upload a picture of the Environment'
	,'LANG_Location_Notes' => '<strong>Flower Location :</strong> click on the map or use the fields below:'
	,'LANG_Georef_Label' => 'Location'
	,'LANG_Georef_Notes' => '(This may be a village/town/city name, region, department or 5 digit postcode.)'
	,'LANG_Or' => 'or :'
	,'LANG_INSEE' => 'INSEE No.'
	,'LANG_NO_INSEE' => 'There is no area with this INSEE number (new or old).'
	,'LANG_Lat' => 'Lat./Long.'
	,'Flower Type' => "The Flower is"
	,'Habitat' => "The habitat is"
	,'Nearest House' => "Approximate distance between your flower and the nearest honeybee hive (metres)"
	,'within50m' => "Presence within 50m of a great culture in bloom"
	,'LANG_Validate_Flower' => 'Save this Floral Station'
	,'LANG_Must_Provide_Pictures' => 'Pictures must be provided for the Flower and Environment'
	,'LANG_Must_Provide_Location' => 'A location must be selected'
	
	,'LANG_Sessions_Title' => 'YOUR SESSION'
	,'LANG_Sessions_Title_Plural' => 'YOUR SESSIONS'
	,'LANG_Session' => 'Session'
	,'LANG_Date' => 'Date'
	,'LANG_Validate_Session' => 'Save Session'
	,'LANG_Validate_Session_Plural' => 'Save Sessions'
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
	,'LANG_Taxa_Unknown_In_Tool' => 'Taxa not known in ID tool'
	,'LANG_ID_Insect_Later' => 'You would prefer to identify it later:'
	,'LANG_ID_More_Precise' => 'You know a more precise identification:'
	,'LANG_ID_Comment' => 'Identification comments'
	,'LANG_Insect_Species' => 'You know the taxon for this insect:'
	,'LANG_Comment' => 'Comments'
	,'Number Insects' => "Number  of insects in the same place at the precise moment you took the photo"
	,'Foraging'=> "Is the photo of this insect NOT on the flower in the floral station"
	,'Foraging_Confirm'=> "If yes, did you see the insect gathering nectar on the floral station flower"
	,'Foraging_Validation'=> "SPIPOLL's protocol requires that any insect included in a collection must be either be photo'ed on the flower, or must be seen foraging on the flower."
	,'LANG_Validate_Insect' => 'Save Insect'
	,'LANG_Validate_Photos' => 'Save Photos'
	,'LANG_Must_Provide_Insect_Picture' => 'A picture of the insect must be provided'
	,'LANG_Confirm_Insect_Delete' => 'Are you sure you want to delete this insect?'
	,'LANG_Delete_Insect' => 'Delete Insect'
	
	,'validation_required' => 'Please enter a value for this field'
	,'validation_time' => 'Please enter a valid time (HH:MM)'
	,'validation_endtime_before_start' => 'The End Time must be after the Start Time'
	,'validation_time_less_than_20' => 'Your session lasted less than 20 minutes. Please check the start and end times.'
	,'validation_please_check' => 'Please Check'
	,'validation_time_not_20' => 'Your session did not last 20 minutes: the Flash protocol requires an observation period of 20 minutes. Please check the start and end times.'
	,'validation_session_date_error' => 'Your sessions are spread over a period exceeding three days. Please check the dates and times for your sessions.'
	
	,'ajax_error' => 'An error has occurred in the data transfer.'
	,'ajax_error_bumpf' => 'At this point we are not sure that the data in the database matches that in our form. You will be asked to refresh this page.'
	,'validation_integer' => "Please provide a integer, leave blank if unknown."
	,'LANG_Invalid_Location' => 'The format given for this Lat/Long combination is invalid'
	,'LANG_Session_Error' => "An internal error has occurred. There does not seem to be a session attached to this collection."
	
	,'LANG_Can_Complete_Msg' => 'You have identified the Flower and a sufficient percentage of insects, so you may now close the collection'
	,'LANG_Cant_Complete_Msg' => 'You have either: not identified the Flower, AND/OR not identified a sufficient percentage of insects. You will need to correct this before you can close the collection.'
	,'LANG_Complete_Collection' => 'Complete Collection'
	,'LANG_Trailer_Head' => 'After closing this collection'
	,'LANG_Trailer_Point_1' => "you can no longer add to this collection's list of insects: are you sure there are no more?"
	,'LANG_Trailer_Point_2' => "you can no longer change the different values describing this floral station, sessions and photos."
	,'LANG_Trailer_Point_3' => "you may modify the identification of the insects through 'My Collections'"
	,'LANG_Trailer_Point_4' => "you may create a new collection"
	,'LANG_Final_1' => 'This collection has been registered and added to your set of collections'
	,'LANG_Final_2' => "This collection may be accessed through 'My Collections', where you may change the identification of your insects (your collection may not be visible immediately in the galleries, but may take up to 5min to appear. Do not re-enter it)."
	,'LANG_Consult_Collection' => 'View this Collection'
	,'LANG_Create_New_Collection' => 'Create New Collection'
	
	,'LANG_Help_Button' => '?'
	,'LANG_Indicia_Warehouse_Error' => 'Error returned from Indicia Warehouse'
	
);