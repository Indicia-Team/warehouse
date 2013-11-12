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
        array(
          'name'=>'notesURL',
          'caption'=>'Notes URL',
          'description'=>'The URL path of the Species Details page used for viewing notes.
                          The URL path should not include server details or a preceeding slash 
                          e.g. node/216',
          'type'=>'textfield',
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
    indiciaData.notesURL = '".$args['notesURL']."';
    indiciaData.notesIcon = true;
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
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  
  public static function get_submission($values, $args) {
    // Any remembered fields need to be made available to the hook function outside this class.
    global $remembered;
    $remembered = isset($args['remembered']) ? $args['remembered'] : '';
    //Page only supported in grid mode at the moment.
    if (isset($values['gridmode']))
      $submission = data_entry_helper::build_sample_subsamples_occurrences_submission($values);
    else
      drupal_set_message('Please set the page to "gridmode"');
    //The parent sample method is always effort for Shorewatch
    $submission['fields']['sample_method_id']=$args['effort'];
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    //Get the platform height which is stored against the location itself.
    $locationAttributeValueRow = data_entry_helper::get_population_data(array(
      'table' => 'location_attribute_value',
      'extraParams' => $readAuth + array('location_id' => $submission['fields']['location_id']['value'], 'location_attribute_id' => $args['platform_height'])
    ));
    if (!empty($locationAttributeValueRow[0]['value']))
      $platformHeight = $locationAttributeValueRow[0]['value'];
    
    $enteredSref = $submission['fields']['entered_sref']['value'];
    //Cycle through each occurrence
    foreach($submission['subModels'] as &$occurrenceAndSubSampleRecord) {
      if (!empty($occurrenceAndSubSampleRecord['model']['subModels'][0]['model']['fields'])) {
        //Assign the attributes associated with the occurrence to a variable to make it easier to work with.
        $occurrenceAttrsToMatch = $occurrenceAndSubSampleRecord['model']['subModels'][0]['model']['fields'];

        foreach ($occurrenceAttrsToMatch as $occurrenceAttrToMatchKey=>$occurrenceAttrToMatch) {
          //Get the reticule and bearing.
          if (preg_match('/^occAttr:'.$args['bearing_to_sighting'].'.*/',$occurrenceAttrToMatchKey)) {
            $bearing = $occurrenceAttrToMatch['value'];
          }
          if (preg_match('/^occAttr:'.$args['reticules'].'.*/',$occurrenceAttrToMatchKey)) {
            $reticuleToUse = $occurrenceAttrToMatch['value'];
          }
        }   
        //Collect the actual reticules value by looking it up in the termlists_term view as currently we only have an id from the drop-down
        $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
        if (!empty($reticuleToUse)) {
          $termlistsTermsRow = data_entry_helper::get_population_data(array(
            'table' => 'termlists_term',
            'extraParams' => $readAuth + array('id' => $reticuleToUse)
          ));      
        }
        if (!empty($termlistsTermsRow[0]['term']))
          $reticules = $termlistsTermsRow[0]['term'];

      }
      //We calculate the grid ref of the cetacean by using the reticules/bearing/platform height and the original entered sptial reference. This is stored in the sub-sample associated with the occurrence.  
      if (empty($platformHeight) || empty($bearing) || empty($reticules))
        $occurrenceAndSubSampleRecord['model']['fields']['entered_sref']['value'] = $enteredSref;
      else
        $occurrenceAndSubSampleRecord['model']['fields']['entered_sref']['value'] = self::calculate_sighting_sref($bearing,$reticules,$platformHeight,$enteredSref, $args); 
      //Sub-sample sample method is always reticule sighting
      $occurrenceAndSubSampleRecord['model']['fields']['sample_method_id']['value'] = $args['reticule_sighting'];
      //We need to copy the location information to the sub-sample else it won't get
      //picked up in the cache occurrences table.
      if (!empty($submission['fields']['location_id']['value']));
        $occurrenceAndSubSampleRecord['model']['fields']['location_id']['value'] = $submission['fields']['location_id']['value'];
      if (!empty($submission['fields']['location_name']['value']));
        $occurrenceAndSubSampleRecord['model']['fields']['location_name']['value'] = $submission['fields']['location_name']['value'];
      //The following only applies to the Cetaceans Seen? which doesn't appear in adhoc mode.
      //Show the second tab when Cetaeans Seen is set
      if (!$args['adhocMode']) {
        //Start by assuming the second tab isn't showing.
        $gridTabShowing = false;
        //Find the value the user has set for Cetaceans Seen?
        foreach ($values as $key => $value) {
          if (substr($key, 0, 10) == "smpAttr:".$args['cetaceans_seen']) {
            if ($value==$args['cetaceans_seen_yes']) {
              $gridTabShowing   = true;
            }
          }
        }
        //If Cetacean's Seen is false then we need to delete any existing Occurrences that were on the second tab if the user is editing existing data.
        //Note this does not apply to adhoc mode where the grid is on the first tab, we have already checked for adhoc mode earlier so don't need to here.
        if ($gridTabShowing  === false) {
          $occurrenceAndSubSampleRecord['model']['fields']['deleted']['value']='t';
          $occurrenceAndSubSampleRecord['model']['subModels'][0]['model']['fields']['deleted']['value']='t';
        }
      }
    }
    return($submission);
  }
  
  /*
   * Method that calculates the spatial reference of cetacean sightings by using the spatial reference of 
   * the observer, the bearing to the sighting, the number of reticules and the platform height.
   */
  private static function calculate_sighting_sref($bearing,$reticules,$platformHeight,$enteredSref, $args) {
    //Convert the 50N 50E style latitude/longitude spatial reference format into pure numbers so we 
    //can manipulate it mathemtically.
    $convertedSref = self::lat_long_conversion_to_numbers($enteredSref);
    //Mathematical calculations provided by client, so this is merely a straight conversion into php
    $angleRetDeclination = 0.2865*6.28/360;
    $radians = $bearing*pi()/180;
    $angleBetween2Radii = acos(6370/(6370+$platformHeight));
    $simplifiedAngle = $angleBetween2Radii+$reticules*$angleRetDeclination;
    $radialDistance = (cos($simplifiedAngle )*(6370*sin($simplifiedAngle)-sqrt(pow(6370,2)*(pow(sin($simplifiedAngle),2))-2*6370*$platformHeight*pow(cos($simplifiedAngle),2))))*1000;
    $dLat = $radialDistance*(cos($radians)); 
    $angleLat = 2*((($dLat/1000)/(6370*2)));
    $dLong = $radialDistance*(sin($radians));
    $angleLong = 2*((($dLong/1000)/(6370*2)));
    $sightingSref['lat'] = $angleLat * (180/pi()) + $convertedSref['lat'];
    $sightingSref['long'] = $angleLong * (180/pi()) + $convertedSref['long'];
    //Convert back into 50N 50E style latitude/longitude spatial reference format 
    return self::lat_long_conversion_to_NE($sightingSref);
  }
  
  /*
   * Convert spatial references from a format like 50N 50W to 50 -50
   */  
  private static function lat_long_conversion_to_numbers($originalSref) {
    $splitOriginalSref = explode(' ', $originalSref);
    //if the last character of the latitude is S (south) then the latitude is negative.
    if (substr($splitOriginalSref[0], -1)==='S')
     $splitOriginalSref[0] = '-'.$splitOriginalSref[0];
    //always chop off the N or S from the end of the latitude.
    $splitOriginalSref[0] = substr_replace($splitOriginalSref[0],"",-1);
    //convert from string to a number for actual use.
    $convertedSref['lat'] = floatval($splitOriginalSref[0]);
    //if the last character of the latitude is W (west) then the longitude is negative.
    if (substr($splitOriginalSref[1], -1)==='W')  
     $splitOriginalSref[1] = '-'.$splitOriginalSref[1];
    //always chop off the E or W from the end of the longitude.
    $splitOriginalSref[1] = substr_replace($splitOriginalSref[1],"",-1);
    //convert from string to a number for actual use.
    $convertedSref['long'] = floatval($splitOriginalSref[1]);
    return $convertedSref;
  }
  
  /*
   * Convert spatial references from a format like 50 -50 to 50N 50W 
   */
  private static function lat_long_conversion_to_NE($sref) {
    //convert to string so we can manipulate the grid references
    $sref['lat'] = (string)$sref['lat'];
    $sref['long'] = (string)$sref['long'];
    //if the latitude sref is negative then it is south
    if ($sref['lat'][0]==='-') {
      $sref['lat'] = $sref['lat'].'S';
      $sref['lat'] = substr($sref['lat'], 1);
    } else {
      $sref['lat'] = $sref['lat'].'N';
    }
    //if the longitude sref is negative then it is west
    if ($sref['long'][0]==='-') {
      $sref['long'] = $sref['long'].'W';
      $sref['long'] = substr($sref['long'], 1);
    } else {
      $sref['long'] = $sref['long'].'E';
    }
    $convertedSref = $sref['lat'].' '.$sref['long'];
    return $convertedSref;
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
  
}
  


