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
    
    return data_entry_helper::select(array_merge(array(
      'fieldname'=>'occurrence:sensitivity_precision',
      'label'=>lang::get('ICPVeg Sensitivity'),
      'lookupValues' => array('50000'=>lang::get('ICPVeg Sensitivity 50km')),
      'blankText' => lang::get('ICPVeg Sensitivity blankText'),
      'default' => $default_value,
    ), $options));
  }

  /**
   * Returns a blank JQM page with fixed Header and Footer and generic default
   * back button.
   * @return array
   */
  public static function get_blank_page($id = NULL, $caption = NULL){
    //back button
    $options = array();
    $options['href'] = '#';
    $options['caption'] = 'Back';
    $options['icon'] = 'arrow-l';
    $options['iconpos'] = 'left';
    $back_button = mobile_entry_helper::apply_template('jqmBackButton', $options);

    //gps button
    $options = array();
    $options['onclick'] = "app.navigation.gpsPopup()";
    $options['href'] = "#";
    $options['id'] = "";
    $options['class'] = "geoloc_icon";
    $options['icon'] = "location";
    $options['iconpos'] = "notext";
    $gps_button = mobile_entry_helper::apply_template(
      'jqmButton', $options
    );

    return array(
      JQM_ATTR => array('id' => $id),
      JQM_CONTENT => array(
        JQM_HEADER => array(
          JQM_ATTR => array("data-position" => "fixed", "data-tap-toggle" => "false"),
          JQM_CONTENT => array($back_button, $caption, $gps_button)
        ),
        JQM_CONTENT => array(
          JQM_ATTR => array(),
          JQM_CONTENT => array()
        ),
        JQM_FOOTER => array(
          JQM_ATTR => array("data-position" => "fixed", "data-tap-toggle" => "false"),
          JQM_CONTENT => array()
        )
      )
    );
  }


  
}

