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
 * NB has Drupal specific code.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/dynamic.php');

class iform_dynamic_person extends iform_dynamic {

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_dynamic_person_definition() {
    return array(
      'title'=>'Person entry form',
      'category' => 'General Purpose Data Entry Forms',
      'description'=>'A data entry form allowing people to be entered directly into the person table without needing a drupal account. '.
          'An example use might be for people wishing to register interest in events without needing a formal account.'
    );
  }
  
//Programmer note - Currently this form inherits quite a lot of redundant fields from dynamic.php suh as the map.
//Perhaps we can clear this up in the future, although too low priority to deal with right now.
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {    
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'structure',
          'caption'=>'Form Structure',
          'description'=>'Define the structure of the form. Each component goes on a new line and is nested inside the previous component where appropriate. The following types of '.
            "component can be specified. <br/>".
            "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page. (Alpha-numeric characters only)<br/>".
            "<strong>=*=</strong> indicates a placeholder for putting any custom attribute tabs not defined in this form structure. <br/>".
            "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
                "&nbsp;&nbsp;<strong>[first name]</strong> - a text box to enter the person's first name.<br/>".
                "&nbsp;&nbsp;<strong>[surname]</strong> - a text box to enter the person's surname.<br/>".
                "&nbsp;&nbsp;<strong>[email address]</strong> - a text box to enter the person's email address.<br/>".
            "<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to the control. The options ".
            "available depend on the control. For example @label=Last Name would set the untranslated label of a control to Last Name. Where the ".
            "option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @extraParams=[\"value1\",\"value2\"] ".
            "or a keyed array as @extraParams={\"preferred\":\"true\",\"orderby\":\"term\"}. " .
            "Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
            "classes to the control such as control-width-3). <br/>".
            "<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab. When this option is ".
            "used, you can change any of the control options for an individual custom attribute control by putting @control|option=value on the subsequent line(s). ".
            "For example, if a control is for psnAttr:4 then you can update it's label by specifying @psnAttr:4|label=New Label on the line after the [*].<br/>".
            "<strong>[psnAttr:<i>n</i>]</strong> is used to insert a particular custom attribute identified by its ID number<br/>".
            "<strong>@helpText</strong> is used to define help text to add to the tab, e.g. @helpText=Enter the surname of the person. <br/>".
            "<strong>all else</strong> is copied to the output html so you can add structure for styling.",
          'type'=>'textarea',
          'default' => 
              "=Information=\r\n".
              "[first name]\r\n".
              "[surname]\r\n".
              "[email address]\r\n".
              "[*]\r\n".
              "=*=",
          'group' => 'User Interface'
        ),
        array(
          'name'=>'defaults',
          'caption'=>'Default Values',
          'description'=>'Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, '.
              'the fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. person.first_name. ',
          'type'=>'textarea',
          'required' => false,
        ),
      )
    );
    return $retVal;
  }
  
  //Programmer note - This form is based on dynamic_location. However I have not implemented all the functionality the dynamic_location
  //form supports yet. This form does not currently support grid mode, however it will probably support this in the furture in some form,
  //so for now am leaving this code as it is, even though some of it is currently redundant.
  /** 
   * Determine whether to show a grid of existing records or a form for either adding a new record or editing an existing one.
   * @param array $args iform parameters. 
   * @param object $node node being shown. 
   * @return const The mode [MODE_GRID|MODE_NEW|MODE_EXISTING].
   */
  protected static function getMode($args, $node) {
    // Default to mode MODE_GRID or MODE_NEW depending on no_grid parameter
    $mode = (isset($args['no_grid']) && $args['no_grid']) ? self::MODE_NEW : self::MODE_GRID;
    
    if ($_POST && !is_null(data_entry_helper::$entity_to_load)) {
      // errors with new sample or entity populated with post, so display this data.
      $mode = self::MODE_EXISTING; 
    } else if (array_key_exists('person_id', $_GET)){
      // request for display of existing record
      $mode = self::MODE_EXISTING;
    } else if (array_key_exists('new', $_GET)){
      // request to create new record (e.g. by clicking on button in grid view)
      $mode = self::MODE_NEW;
      data_entry_helper::$entity_to_load = array();
    }
    return $mode;
  }
    
  // Get an existing person.
  protected static function getEntity($args, $auth) {
    data_entry_helper::$entity_to_load = array();
    data_entry_helper::load_existing_record($auth['read'], 'person', $_GET['person_id'], 'detail', false, false);    
  }
  
  protected static function getAttributes($args, $auth) {
    $id = isset(data_entry_helper::$entity_to_load['person:id']) ? 
            data_entry_helper::$entity_to_load['person:id'] : null;
    $attrOpts = array(
    'id' => $id
    ,'valuetable'=>'person_attribute_value'
    ,'attrtable'=>'person_attribute'
    ,'key'=>'person_id'
    ,'fieldprefix'=>'psnAttr'
    ,'extraParams'=>$auth['read']
    );
    $attributes = data_entry_helper::getAttributes($attrOpts, false);
    return $attributes;
  }
  
  /**
   * Retrieve the additional HTML to appear at the top of the first
   * tab or form section. This is a set of hidden inputs containing the website ID as well as an existing person's ID.
   * @param type $args 
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $r = $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";//.
    if (isset(data_entry_helper::$entity_to_load['person:id'])) {
      $r .= '<input type="hidden" id="person:id" name="person:id" value="' . data_entry_helper::$entity_to_load['person:id'] . '" />' . PHP_EOL;    
    }
    $r .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['person:id']), $auth['read']);
    return $r;
  }
  
  
  protected static function get_control_firstname($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_First_Name'),
      'fieldname' => 'person:first_name',
      'class' => 'control-width-5'
    ), $options));
  }
  
  protected static function get_control_surname($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Surname'),
      'fieldname' => 'person:surname',
      'class' => 'control-width-5'
    ), $options));
  }
  
  protected static function get_control_emailaddress($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Email_Address'),
      'fieldname' => 'person:email_address',
      'class' => 'control-width-5'
    ), $options));
  }
 

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $structure = array(
        'model' => 'person',
    );
    $s = submission_builder::build_submission($values, $structure);  
    return $s;
  }

  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {
     if (!isset($args['structure']) || empty($args['structure']))
      $args['structure'] = 
              "=Information=\r\n".
              "[first name]\r\n".
              "[surname]\r\n".
              "[email address]\r\n".
              "[*]\r\n".
              "=*=";
    return $args;
  }
  
  /** 
   * Override the default submit buttons to add a delete button where appropriate.
   */
  protected static function getSubmitButtons($args) {
    $r = '';
    if (!empty(data_entry_helper::$entity_to_load['person:id'])) {
      // use a button here, not input, as Chrome does not post the input value
      $r .= '<button type="submit" class="indicia-button" id="delete-button" name="delete-button" value="delete" >'.lang::get('Delete')."</button>\n";
      //Programmer note- this form is based on dynamic_location, dynami_location includes deletion capability. However for the person
      //form I have commented out the deletion code as I am currently uncertain of the suitability of this considering users can be
      //linked to user accounts. I don't want cause breakages, you may resinstate/extend this as required in the future when it is needed, for now it is not needed.
      /*
      data_entry_helper::$javascript .= "$('#delete-button').click(function(e) {
        if (!confirm(\"Are you sure you want to delete this person?\")) {
          e.preventDefault();
          return false;
        }
      });\n";
       */
    }
    $r .= '<input type="submit" class="indicia-button" id="save-button" value="'.lang::get('Submit')."\" />\n";
    return $r;
  }
  
}

