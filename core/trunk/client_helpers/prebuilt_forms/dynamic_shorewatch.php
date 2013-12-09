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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/dynamic.php');
require_once('dynamic_sample_occurrence.php');
require_once('includes/shorewatch_grid_reference_processor.php');

class iform_dynamic_shorewatch extends iform_dynamic_sample_occurrence {
  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_dynamic_shorewatch_definition() {
    return array(
      'title'=>'Shorewatch sample with occurrences form',
      'category' => 'Forms for specific surveying methods',
      'description'=>'A sample and occurrence entry form with a grid listing the user\'s occurrences.' .
        'The form supports an "adhoc mode" which is more geared to use by the general public.' .
        'The attributes on the form are dynamically generated from the survey setup on the Indicia Warehouse.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        //As the attribute ids will vary between different databases, we need to manually
        //map the attribute ids to variables in the code
        array(
          'name'=>'observer_name',
          'caption'=>'Observer Name',
          'description'=>'Indicia ID for the sample attribute that is the name of the observer.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'observer_email',
          'caption'=>'Observer Email',
          'description'=>'Indicia ID for the sample attribute that is the email of the observer.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'observer_phone_number',
          'caption'=>'Observer Phone Number',
          'description'=>'Indicia ID for the sample attribute that is the phone number of the observer.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'start_time',
          'caption'=>'Start time',
          'description'=>'Indicia ID for the sample attribute that records the start time of the watch.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'end_time',
          'caption'=>'End time',
          'description'=>'Indicia ID for the sample attribute that records the end time of the watch.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'cetaceans_seen',
          'caption'=>'Cetaceans seen?',
          'description'=>'Indicia ID for the sample attribute that records whether Cetaceans have been seen.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'cetaceans_seen_yes',
          'caption'=>'Cetaceans Seen Yes Answer',
          'description'=>'Indicia ID for the termlists_term that stores the Yes answer for Cetaceans Seen?.',
          'type'=>'string',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'sea_state',
          'caption'=>'Sea state?',
          'description'=>'Indicia ID for the sample attribute that records sea state.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'visibility',
          'caption'=>'Visibility?',
          'description'=>'Indicia ID for the sample attribute that records visibility.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'non_cetacean_marine_animals_seen',
          'caption'=>'Non cetacean marine animals seen?',
          'description'=>'Indicia ID for the sample attribute that records whether non-cetacean marine animals have been seen.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'feeding_birds_seen',
          'caption'=>'Feeding birds seen?',
          'description'=>'Indicia ID for the sample attribute that records whether feeding birds have been seen.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'number_of_people_spoken_to_during_watch',
          'caption'=>'Number of people spoken to during watch?',
          'description'=>'Indicia ID for the sample attribute that records the number of people spoken to during the watch.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'wdcs_newsletter',
          'caption'=>'WDCS newsletter opt-in',
          'description'=>'Indicia ID for the sample attribute that records whether a guest has chosen to receive
            the WDCS newsletter.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Sample Attributes'
        ),
        array(
          'name'=>'bearing_to_sighting',
          'caption'=>'Bearing to sighting',
          'description'=>'Indicia ID for the occurrence attribute that stores the bearing to the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'reticules',
          'caption'=>'Reticules',
          'description'=>'Indicia ID for the occurrence attribute that holds the number of reticules.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'reticules_from',
          'caption'=>'Reticules from',
          'description'=>'Indicia ID for the occurrence attribute that stores whether the Reticules value
            is from the land or sky.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'distance_estimate',
          'caption'=>'Distance Estimate',
          'description'=>'Indicia ID for the occurrence attribute that stores the distance estimate to the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'adults',
          'caption'=>'Adults',
          'description'=>'Indicia ID for the occurrence attribute that stores the number of adults associated with the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'calves',
          'caption'=>'Calves',
          'description'=>'Indicia ID for the occurrence attribute that stores the number of Calves associated with the sighting.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'activity',
          'caption'=>'Activity',
          'description'=>'Indicia ID for the occurrence attribute that stores whether a sighting is travelling or staying.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Occurrence Attributes'
        ),
        array(
          'name'=>'behaviour',
          'caption'=>'Behaviour',
          'description'=>'Indicia ID for the occurrence attribute that stores whether a sighting is calm or active.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Occurrence Attributes'
        ),
        array(
          'name'=>'platform_height',
          'caption'=>'Platform Height',
          'description'=>'Indicia ID for the location attribute that stores the platform height.',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Shorewatch Location Attributes'
        ),
        array(
          'name'=>'effort',
          'caption'=>'Effort',
          'description'=>'Indicia ID for the termlists_term that stores the effort sample method id.',
          'type'=>'select',
          'table'=>'termlists_term',
          'valueField'=>'id',
          'captionField'=>'term',
          'group'=>'Shorewatch Sample Methods'
        ),
        array(
          'name'=>'reticule_sighting',
          'caption'=>'Reticule Sighting',
          'description'=>'Indicia ID for the termlists_term that stores the reticule sighting sample method id.',
          'type'=>'select',
          'table'=>'termlists_term',
          'valueField'=>'id',
          'captionField'=>'term',
          'group'=>'Shorewatch Sample Methods'
        ),
        array(
          'name'=>'adhocMode',
          'caption'=>'Run the page in ad-hoc mode?',
          'description'=>'Select if you wish to run the page in ad-hoc mode',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Shorewatch Page Configuration'
        ),
      )
    );
    return $retVal;
  }

  /**
   * 
   * @param type $args The args for the page including the options selected on the edit tab.
   * @param type $node The node
   * @return type The parent get_form method html
   */
  public static function get_form($args, $node) { 
    $pageMode = self::getMode($args, $node);
    global $user;
    //Always override any role a user has with a larger role if they have that role.
    $roleType = 'guest';
    if (in_array('volunteer', $user->roles))
     $roleType = 'volunteer'; 
    if (in_array('staff', $user->roles))
     $roleType = 'staff';  
    if (in_array('data manager', $user->roles))
     $roleType = 'data manager';  
    //Get the data such as phone number associated with the user
    $user_fields = user_load($user->uid);
    
    $userPhoneNum = null; 
    $userEmail= null;
    $userName= null;
    if (!empty($user_fields->field_main_phone_number['und']['0']['value']))
      $userPhoneNum = $user_fields->field_main_phone_number['und']['0']['value'];
    
    if (!empty($user->mail))
      $userEmail = $user->mail;
    else 
      //we need to pass javascript an empty string rather than null variable
      $userEmail = '';
    
    if (!empty($user->name))
      $userName = "$user->name";
    else 
      //we need to pass javascript an empty string rather than null variable
      $userName = "''";
    
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    //Need to collect the user's name from the people table
    $defaultUserData = data_entry_helper::get_report_data(array(
      'dataSource'=>'library/users/get_people_details_for_website_or_user',
      'readAuth'=>$readAuth,
      'extraParams'=>array('user_id' => hostsite_get_user_field('indicia_user_id'), 'website_id' => $args['website_id'])
    ));
    
    //Pass the javascript the sample id if in edit mode.
    if (!empty($_GET['sample_id']))
      data_entry_helper::$javascript .= "indiciaData.sample_id = ".$_GET['sample_id'].";\n";
    
    //Allow the user to match up the attributes on the edit tab so we don't hard code attribute ids
    //Pass these into javascript
    data_entry_helper::$javascript .= "
    indiciaData.person_name = '".$defaultUserData[0]['fullname_surname_first']."';     
    indiciaData.observer_name_attr_id = ".$args['observer_name'].";
    indiciaData.observer_email_attr_id = ".$args['observer_email'].";
    indiciaData.observer_phone_number_attr_id = ".$args['observer_phone_number'].";
    
    indiciaData.start_time_attr_id = ".$args['start_time'].";
    indiciaData.end_time_attr_id = ".$args['end_time'].";  
      
    indiciaData.sea_state_attr_id = ".$args['sea_state'].";  
    indiciaData.visibility_attr_id = ".$args['visibility'].";  
    indiciaData.cetaceans_seen_attr_id = ".$args['cetaceans_seen'].";
    indiciaData.non_cetacean_marine_animals_seen_attr_id = ".$args['non_cetacean_marine_animals_seen'].";
    indiciaData.feeding_birds_seen_attr_id = ".$args['feeding_birds_seen'].";
    indiciaData.number_of_people_spoken_to_during_watch_attr_id = ".$args['number_of_people_spoken_to_during_watch'].";

    indiciaData.wdcs_newsletter_attr_id = ".$args['wdcs_newsletter'].";
    indiciaData.bearing_to_sighting_attr_id = ".$args['bearing_to_sighting'].";
    indiciaData.reticules_attr_id = ".$args['reticules'].";
    indiciaData.reticules_from_attr_id = ".$args['reticules_from'].";
    indiciaData.distance_estimate_attr_id = ".$args['distance_estimate']."; 
    indiciaData.adults_attr_id = ".$args['adults'].";
    indiciaData.calves_attr_id = ".$args['calves']."; 
    indiciaData.activity_attr_id = ".$args['activity'].";
    indiciaData.behaviour_attr_id = ".$args['behaviour'].";
    
    indiciaData.user_phone_number = \"$userPhoneNum\"; 
    indiciaData.user_email = \"$userEmail\";   
    indiciaData.username = \"$userName\";
    indiciaData.roleType = \"$roleType\"; 
    indiciaData.adhocMode = \"".$args['adhocMode']."\";
    indiciaData.tenMinMessage = \"".lang::get('SW efforts should always be 10 mins. If you saw a sightings that started after the 10 mins then please use the ad-hoc form')."\";
    indiciaData.emailPhoneMessage = \"".lang::get('Please supply a phone number or email address')."\";  
    indiciaData.addSpeciesMessage = \"".lang::get('Please add a species')."\";";
    //We need to put root folder into indiciadata so the addrowtogrid.js code knows the details of the server
    //we are running without hard coding it
    //The user needs to specify which page to go to when the user clicks on the notes icon on the species grid
    //The indiciaData.notesIcon is used by the addrowtogrid.js code so that the code for notes on the species grid is not run by old code.
    data_entry_helper::$javascript .= "
    indiciaData.rootFolder = '".data_entry_helper::getRootFolder()."';    
    ";
    
    $r = '';
    //We need a div so we can can put the form into read only mode when required.
    $r = '<div id = "disableDiv">';
    $r .= parent::get_form($args, $node); 
    $r .= '</div>';
    //Get a list of sightings, this is used to determine if there are any that are verified or if there are no sightings at all.
    //If we find this is the case then we set the form into read only mode.
    if (!empty($_GET['sample_id'])) {
      $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
      $sightingsData = data_entry_helper::get_report_data(array(
        'dataSource'=>'reports_for_prebuilt_forms/Shorewatch/get_sightings_for_sample',
        'readAuth'=>$readAuth,
        'extraParams'=>array('sample_id' => $_GET['sample_id'])
      ));
    }
    $verifiedDataDetected=false;
    //Check for sample id as we don't do this in add mode.
    if (!empty($_GET['sample_id'])&&empty($sightingsData)) {
      $r = '<div><i>This page is locked as it does not having any sightings.</i></div>'.$r;
      data_entry_helper::$javascript .= "$('[id*=_lock]').remove();\n";
      data_entry_helper::$javascript .= "$('#disableDiv').find('input, textarea, text, button, select').attr('disabled','disabled');\n"; 
      data_entry_helper::$javascript .= "$('.species-grid, .page-notice, .indicia-button').hide();\n"; 
      //If the page is locked then we don't run the logic on the Save/Next Step button
      //as this logic enables the button when we don't want it enabled.
      data_entry_helper::$javascript .= "indiciaData.dontRunCetaceanSaveButtonLogic=true;"; 
    }
    if (!empty($sightingsData)) {
      //If any verified sightings are found then put page into read-only mode.
      foreach ($sightingsData as $sightingData) {
        if ($verifiedDataDetected===false) {         
          if ($sightingData['verification_status']==='D' || $sightingData['verification_status']==='V' || $sightingData['verification_status']==='R') {
            $r = '<div><i>This page is locked as it has at least 1 verified sighting.</i></div>'.$r;
            data_entry_helper::$javascript .= "$('[id*=_lock]').remove();\n $('.remove-row').remove();\n";
            data_entry_helper::$javascript .= "$('.scImageLink,.scClonableRow').hide();\n";
            data_entry_helper::$javascript .= "$('.edit-taxon-name,.remove-row').hide();\n";
            data_entry_helper::$javascript .= "$('#disableDiv').find('input, textarea, text, button, select').attr('disabled','disabled');\n";  
            //If the page is locked then we don't run the logic on the Save/Next Step button
            //as this logic enables the button when we don't want it enabled.
            data_entry_helper::$javascript .= "indiciaData.dontRunCetaceanSaveButtonLogic=true;"; 
            $verifiedDataDetected=true;
          }
        }
      }
    }     
    return $r;
  }
  
  /**
   * Get the observer control as an autocomplete.
   */
  protected static function get_control_observerautocomplete($auth, $args, $tabAlias, $options) {
    global $user;
    //Get the name of the currently logged in user
    $defaultUserData = data_entry_helper::get_report_data(array(
      'dataSource'=>'library/users/get_people_details_for_website_or_user',
      'readAuth'=>$auth['read'],
      'extraParams'=>array('user_id' => hostsite_get_user_field('indicia_user_id'), 'website_id' => $args['website_id'])
    ));
    //If we are in edit mode then we need to get the name of the saved observer for the sample
    if ((!empty($_GET['sample_id'])) && !empty($args['observer_name'])) {
      $existingUserData = data_entry_helper::get_population_data(array(
        'table' => 'sample_attribute_value',
        'extraParams' => $auth['read'] + array('sample_id' => $_GET['sample_id'], 'sample_attribute_id' => $args['observer_name'])
      ));
    }
    $observer_list_args=array_merge_recursive(array(
      'extraParams'=>array_merge($auth['read'])
    ), $options);
    $observer_list_args['label']=t('Observer Name');
    $observer_list_args['extraParams']['website_id']=$args['website_id'];
    $observer_list_args['captionField']='fullname_surname_first';
    $observer_list_args['id']='obSelect:'.$args['observer_name'];
    $observer_list_args['report'] = 'library/users/get_people_details_for_website_or_user';
    //Auto-fill the observer name with the name of the observer of the existing saved sample if it exists,
    //else default to current user name
    if (!empty($existingUserData[0]['value'])) {
      $observer_list_args['defaultCaption'] = $existingUserData[0]['value'];
    } else {
      if (empty($_GET['sample_id']))
        $observer_list_args['defaultCaption'] = $defaultUserData[0]['fullname_surname_first'];
    }
    return data_entry_helper::autocomplete($observer_list_args);
  }

  /**
   * Returns the species checklist input control.
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $options additional options for the control, e.g. those configured in the form structure.
   * @return HTML for the species_checklist control.
   */
  protected static function get_control_species($auth, $args, $tabAlias, $options) {
    $options['subSamplePerRow']=true;
    $options['speciesControlToUseSubSamples']=true;
    $r = parent::get_control_species($auth, $args, $tabAlias, $options); 
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  
  public static function get_submission($values, $args) {
    return create_submission($values, $args);
  }
}
  


