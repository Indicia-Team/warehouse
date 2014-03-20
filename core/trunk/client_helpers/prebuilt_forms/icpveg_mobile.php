<?php

require_once('mobile_sample_occurrence.php');

global $indicia_templates;

// Remove colon compared to default indicia label.
$indicia_templates['label'] = '<label for="{id}"{labelClass}>{label}</label>'."\n";

class iform_icpveg_mobile extends iform_mobile_sample_occurrence {
  
  public static function get_icpveg_mobile_definition() {
    return array(
      'title'=>'ICP Vegetaion Ozone Injury Survey MOBILE!',
      'category' => 'Specific Surveys',
      'description'=>'A mobile sample-occurrence form for ICP Vegetation.'
    );
  }

  /**
   * Override the sensitivity control to create a simple select with default value 
   * set by user profile.
   */
  protected static function get_control_sensitivity($auth, $args, $tabAlias, $options) {
    // Obtain the default value for the user.
    global $user;
    $user = user_load($user->uid);
    $field_values = field_get_items('user', $user, 'field_icpveg_permission');
    $default_value = $field_values[0]['value'];
    if($default_value == 0) {
      // Where Drupal stores 0, we want the Warehouse field to be NULL to indicate
      // no blurring of detail.
      $default_value = '';
    }
    
    return data_entry_helper::select(array(
      'fieldname'=>'occurrence:sensitivity_precision',
      'label'=>lang::get('ICPVeg Sensitivity'),
      'lookupValues' => array('50000'=>lang::get('ICPVeg Sensitivity 50km')),
      'blankText' => lang::get('ICPVeg Sensitivity blankText'),
      'default' => $default_value,
    ));
  }
  
}

