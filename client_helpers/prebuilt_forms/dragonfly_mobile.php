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
   * Get the sample photo control
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_samplephoto(
    $auth, $args, $tabAlias, $options) {
    $defaults = array(
      'fieldname' => 'sample:image',
    );
    $opts = array_merge($defaults, $options);
    return '<div id="photo"><div id="photo-picker">' . data_entry_helper::image_upload($opts) . '</div></div>';
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

  /**
   * Returns the main form's JQM page.
   * Only called when using One Page interface.
   * back button.
   * @return array
   */
  public static function get_main_page($id = NULL){
    $caption = "<h1 id='" . $id . "_heading'></h1>";
    $page = static::get_blank_page($id, $caption);
    $gps =  mobile_entry_helper::apply_template('jqmButton', array(
      'id' => 'sref-top-button',
      'href' => '#sref',
      'caption' => 'GPS',
      'class' => '',
      'icon' => 'location',
      'iconpos' => 'notext'));
    $date = mobile_entry_helper::apply_template('jqmButton', array(
      'id' => 'date-top-button',
      'href' => '#date',
      'caption' => 'Date',
      'class' => '',
      'icon' => 'calendar',
      'iconpos' => 'notext'));
    $page[JQM_CONTENT][JQM_HEADER][JQM_CONTENT][] =
      "<div class='ui-btn-right' data-role='controlgroup'
        data-type='horizontal'>" . $date . $gps . "</div>";

    //submit button
    $options = array();
    $options['id'] = "entry-form-submit";
    $options['align'] = "right";
    $options['caption'] = "Save";

    $page[JQM_CONTENT][JQM_FOOTER][JQM_CONTENT][] =
      mobile_entry_helper::apply_template('jqmControlSubmitButton', $options);
    mobile_entry_helper::apply_template('jqmControlSubmitButton', $options);

    return $page;
  }

  /**
   * Returns blank JQM page with fixed header and footer.
   * @param null $id
   * @return array
   */
  public static function get_blank_page($id = NULL, $caption = NULL){
    $options = array();
    $options['href'] = '#';
    $options['caption'] = 'Back';
    $options['icon'] = 'arrow-l';
    $options['iconpos'] = 'notext';
    $back_button = "<div class='ui-btn-left' data-role='controlgroup'
        data-type='horizontal'>";
    $back_button .= mobile_entry_helper::apply_template('jqmBackButton', $options);
    $back_button .= "</div>";

    return array(
      JQM_ATTR => array('id' => $id),
      JQM_CONTENT => array(
        JQM_HEADER => array(
          JQM_ATTR => array("data-position" => "fixed"),
          JQM_CONTENT => array($back_button, $caption)
        ),
        JQM_CONTENT => array(
          JQM_ATTR => array(),
          JQM_CONTENT => array()
        ),
        JQM_FOOTER => array(
          JQM_ATTR => array("data-position" => "fixed"),
          JQM_CONTENT => array()
        )
      )
    );
  }
}

