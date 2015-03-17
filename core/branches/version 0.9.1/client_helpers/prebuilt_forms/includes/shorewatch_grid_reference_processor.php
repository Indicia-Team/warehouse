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
 * @package  Client
 * @author   Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  
  function create_submission($values, $args) {
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
      if (empty($platformHeight) || empty($bearing) || empty($reticules)) {
        $occurrenceAndSubSampleRecord['model']['fields']['entered_sref']['value'] = $enteredSref;
      } else {
        $occurrenceAndSubSampleRecord['model']['fields']['entered_sref']['value'] = calculate_sighting_sref($bearing,$reticules,$platformHeight,$enteredSref, $args); 
      
        
      }
      //Sub-sample sample method is always reticule sighting
      $occurrenceAndSubSampleRecord['model']['fields']['sample_method_id']['value'] = $args['reticule_sighting'];
      //We need to copy the location information to the sub-sample else it won't get
      //picked up in the cache occurrences table.
      if (!empty($submission['fields']['location_id']['value']))
        $occurrenceAndSubSampleRecord['model']['fields']['location_id']['value'] = $submission['fields']['location_id']['value'];
      if (!empty($submission['fields']['location_name']['value']))
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
  function calculate_sighting_sref($bearing,$reticules,$platformHeight,$enteredSref, $args) {
    //Calculations done assuming Platform Height is in km, however Platform Height is stored in metres, so divide by 1000.
    $platformHeight=$platformHeight/1000;
    //Convert the 50N 50E style latitude/longitude spatial reference format into pure numbers so we 
    //can manipulate it mathemtically.
    $convertedSref = lat_long_conversion_to_numbers($enteredSref);
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
    return lat_long_conversion_to_NE($sightingSref);
  }
  
  /*
   * Convert spatial references from a format like 50N 50W to 50 -50
   */  
  function lat_long_conversion_to_numbers($originalSref) {
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
  function lat_long_conversion_to_NE($sref) {
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