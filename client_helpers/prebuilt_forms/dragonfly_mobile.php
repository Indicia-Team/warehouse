<?php

require_once('mobile_sample_occurrence.php');

global $indicia_templates;

class iform_dragonfly_mobile extends iform_mobile_sample_occurrence {

  public static function get_dragonfly_mobile_definition() {
    return array(
      'title'=>'Dragonfly App',
      'category' => 'Mobile',
      'description'=>'A mobile sample-occurrence form for Dragonfly App.'
    );
  }

  /**
   * Override the sensitivity control to create a simple select with default value
   * set by user profile.
   */
  protected static function get_control_certainty($auth, $args, $tabAlias, $options) {
    $options['data-iconpos'] = "right";
    $options['caption'] = "Certain";
    $options['fieldname'] = 'occAttr:223';
    $options['id'] = 'occAttr:223';
    $options['value'] = 1;
    $options['template'] = 'jqmCheckbox';

    return mobile_entry_helper::checkbox($options);
    //return mobile_entry_helper::apply_template('jqmCheckbox', $options);
  }


  /**
   * Get the date control.
   */
  protected static function get_control_totalcount(
    $auth, $args, $tabAlias, $options) {

    $options = array();
    $options['fieldname'] = 'occAttr:222';
    $options['id'] = 'occAttr:222';
    $options['caption'] = "Number";
    $options['value'] = 1;
    $options['class'] = 'numberInput';

    $input = mobile_entry_helper::apply_template('jqmNumberInput', $options);
    return $input;
  }

}

