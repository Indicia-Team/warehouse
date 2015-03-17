<?php

require_once('dynamic_sample_occurrence.php');

global $indicia_templates;

// Remove colon compared to default indicia label.
$indicia_templates['label'] = '<label for="{id}"{labelClass}>{label}</label>'."\n";
// Use style to set thumbnail width. Remove <br> as label is a block item.
$indicia_templates ['file_box_uploaded_image'] = '<a class="fancybox" href="{origfilepath}">'
        . '<img src="{thumbnailfilepath}" style="width:{imagewidth}px;"/>'
        . '</a>'
        . '<input type="hidden" name="{idField}" id="{idField}" value="{idValue}" />'
        . '<input type="hidden" name="{pathField}" id="{pathField}" value="{pathValue}" />'
        . '<input type="hidden" name="{typeField}" id="{typeField}" value="{typeValue}" />'
        . '<input type="hidden" name="{typeNameField}" id="{typeNameField}" value="{typeNameValue}" />'
        . '<input type="hidden" name="{deletedField}" id="{deletedField}" value="{deletedValue}" class="deleted-value" />'
        . '<input type="hidden" id="{isNewField}" value="{isNewValue}" />'
        . '<label for="{captionField}">Caption:</label>'
        . '<input type="text" maxlength="100" style="width: {imagewidth}px" name="{captionField}" id="{captionField}" value="{captionValue}"/>';


class iform_icpveg_ozone extends iform_dynamic_sample_occurrence {
  
  public static function get_icpveg_ozone_definition() {
    return array(
      'title'=>'ICP Vegetaion Ozone Injury Survey',
      'category' => 'Specific Surveys',
      'description'=>'A sample-occurrence form for ICP Vegetation.'
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

