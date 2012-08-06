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

class iform_dynamic_location extends iform_dynamic {

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_dynamic_location_definition() {
    return array(
      'title'=>'Location entry form',
      'category' => 'General Purpose Data Entry Forms',
//      'helpLink'=>'',
      'description'=>'A data entry form for defining locations that can later be used to enter samples against. '.
          'An optional grid listing the user\'s locations allows them to be reloaded for editing. '.
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
        array(
          'name'=>'structure',
          'caption'=>'Form Structure',
          'description'=>'Define the structure of the form. Each component goes on a new line and is nested inside the previous component where appropriate. The following types of '.
            "component can be specified. <br/>".
            "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page. (Alpha-numeric characters only)<br/>".
            "<strong>=*=</strong> indicates a placeholder for putting any custom attribute tabs not defined in this form structure. <br/>".
            "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
                "&nbsp;&nbsp;<strong>[map]</strong><br/>".
                "&nbsp;&nbsp;<strong>[place search]</strong><br/>".
                "&nbsp;&nbsp;<strong>[spatial reference]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location name]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location code]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location type]</strong><br/>".
                "&nbsp;&nbsp;<strong>[location comment]</strong>. <br/>".
            "<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to the control. The options ".
            "available depend on the control. For example @label=Abundance would set the untranslated label of a control to Abundance. Where the ".
            "option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @occAttrClasses=[\"class1\",\"class2\"] ".
            "or a keyed array as @extraParams={\"preferred\":\"true\",\"orderby\":\"term\"}. " .
            "Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
            "classes to the control such as control-width-3). <br/>".
            "<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab. When this option is ".
            "used, you can change any of the control options for an individual custom attribute control by putting @control|option=value on the subsequent line(s). ".
            "For example, if a control is for smpAttr:4 then you can update it's label by specifying @smpAttr:4|label=New Label on the line after the [*].<br/>".
            "<strong>[locAttr:<i>n</i>]</strong> is used to insert a particular custom attribute identified by its ID number<br/>".
            "<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of the site.? <br/>".
            "<strong>all else</strong> is copied to the output html so you can add structure for styling.",
          'type'=>'textarea',
          'default' => 
              "=Place=\r\n".
              "?Please provide the spatial reference of the location. You can enter the reference directly, or search for a place then click on the map to set it.?\r\n".
              "[location name]\r\n".
              "[location code]\r\n".
              "[location type]\r\n".
              "[spatial reference]\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[location comment]\r\n".
              "[*]\r\n".
              "=*=",
          'group' => 'User Interface'
        ),
        array(
          'name' => 'grid_report',
          'caption' => 'Grid Report',
          'description' => 'Name of the report to use to populate the grid for selecting existing data from. The report must return a location_id '.
              'field for linking to the data entry form. As a starting point, try reports_for_prebuilt_forms/simple_location_list.',
          'type'=>'string',
          'group' => 'User Interface',
          'default' => 'reports_for_prebuilt_forms/simple_location_list'
        ),
        array(
          'name'=>'location_images',
          'caption'=>'Location Images',
          'description'=>'Should locations allow images to be uploaded?',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'User Interface'
        ),
        array(
          'name'=>'defaults',
          'caption'=>'Default Values',
          'description'=>'Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, '.
              'the fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. occurrence.record_status. '.
              'For date fields, use today to dynamically default to today\'s date. NOTE, currently only supports occurrence:record_status and '.
              'sample:date but will be extended in future.',
          'type'=>'textarea',
          'required' => false,
        ),
      )
    );
    return $retVal;
  }
  
  /** 
   * Determine whether to show a gird of existing records or a form for either adding a new record or editing an existing one.
   * @param array $args iform parameters. 
   * @param object $node node being shown. 
   * @return const The mode [MODE_GRID|MODE_NEW|MODE_EXISTING].
   */
  protected static function getMode($args, $node) {
    // Default to mode MODE_GRID or MODE_NEW depending on no_grid parameter
    $mode = (isset($args['no_grid']) && $args['no_grid']) ? MODE_NEW : MODE_GRID;                 
    
    if ($_POST && !is_null(data_entry_helper::$entity_to_load)) {
      // errors with new sample or entity populated with post, so display this data.
      $mode = MODE_EXISTING; 
    } else if (array_key_exists('location_id', $_GET)){
      // request for display of existing record
      $mode = MODE_EXISTING;
    } else if (array_key_exists('new', $_GET)){
      // request to create new record (e.g. by clicking on button in grid view)
      $mode = MODE_NEW;
      data_entry_helper::$entity_to_load = array();
    }
    return $mode;
  }

  /** 
   * Construct a grid of existing records.
   * @param array $args iform parameters. 
   * @param object $node node being shown. 
   * @param array $auth authentication tokens for accessing the warehouse. 
   * @return string HTML for grid.
   */
  protected static function getGrid($args, $node, $auth) {
    $r = '';
    $attributes = data_entry_helper::getAttributes(array(
      'valuetable'=>'sample_attribute_value'
      ,'attrtable'=>'sample_attribute'
      ,'key'=>'sample_id'
      ,'fieldprefix'=>'smpAttr'
      ,'extraParams'=>$auth['read']
      ,'survey_id'=>$args['survey_id']
    ), false);
    $r .= '<div id="locationList">' . 
            call_user_func(array(self::$called_class, 'getLocationListGrid'), $args, $node, $auth, $attributes) . 
          '</div>';
    return $r;  
  }
    
  // Get an existing location.
  protected static function getEntity($args, $auth) {
    data_entry_helper::$entity_to_load = array();
    data_entry_helper::load_existing_record($auth['read'], 'location', $_GET['location_id']);    
  }
  
  protected static function getAttributes($args, $auth) {
    $attrOpts = array(
    'id' => data_entry_helper::$entity_to_load['location:id']
    ,'valuetable'=>'location_attribute_value'
    ,'attrtable'=>'location_attribute'
    ,'key'=>'location_id'
    ,'fieldprefix'=>'locAttr'
    ,'extraParams'=>$auth['read']
    ,'survey_id'=>$args['survey_id']
    );
    $attributes = data_entry_helper::getAttributes($attrOpts, false);
    return $attributes;
  }
  
  protected static function getReloadPath () {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['location_id']);
    unset($reload['params']['newLocation']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.http_build_query($reload['params']);
    return $reloadPath;
  }
  
  protected static function getHidden ($args) {
    $hiddens = '';
    if (isset(data_entry_helper::$entity_to_load['location:id'])) {
      $hiddens .= '<input type="hidden" id="location:id" name="location:id" value="' . data_entry_helper::$entity_to_load['location:id'] . '" />' . PHP_EOL;    
    }
    return $hiddens;
  }
  
  protected static function get_control_locationname($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'location:name',
      'class' => 'control-width-5'
    ), $options));
  }

  protected static function get_control_locationcode($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Code'),
      'fieldname' => 'location:code',
      'class' => 'control-width-5'
    ), $options));
  }

  protected static function get_control_locationtype($auth, $args, $tabalias, $options) {
    // To limit the terms listed add a terms option to the Form Structure as a JSON array.
    // The terms must exist in the termlist that has external key indidia:location_types
    // e.g.
    // [location type]
    // @terms=["City","Town","Village"]
    
    // get the list of terms
    $filter = null;
    if (array_key_exists('terms', $options)) {
      $filter = $options['terms'];
    }
    $terms = helper_base::get_termlist_terms($auth, 'indicia:location_types', $filter);
          
    if (count($terms) == 1) {
      //only one location type so output as hidden control
      return '<input type="hidden" id="location:location_type_id" name="location:location_type_id" value="' . $terms[0]['id'] . '" />' . PHP_EOL;
    }
    elseif (count($terms) > 1) {
      // convert the $terms to an array of id => term
      $lookup = array();
      foreach ($terms as $term) {
        $lookup[$term['id']] = $term['term'];
      }
      return data_entry_helper::select(array_merge(array(
        'label' => lang::get('LANG_Location_Type'),
        'fieldname' => 'location:location_type_id',
        'lookupValues' => $lookup,
        'blankText' => lang::get('LANG_Blank_Text'),
      ), $options));
    }
  }

    protected static function get_control_locationcomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'location:comment',
      'label'=>lang::get('LANG_Comment')
    ), $options)); 
  }
 
    protected static function get_control_spatialreference($auth, $args, $tabalias, $options) {
      $options = array_merge($options, array('fieldname' => 'location:centroid_sref'));
      return parent::get_control_spatialreference($auth, $args, $tabalias, $options);
    }


  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // default for forms setup on old versions is grid - list of occurrences
    // Can't call getGridMode in this context as we might not have the $_GET value to indicate grid
    $structure = array(
        'model' => 'location',
    );
    // Either an uploadable file, or a link to a Flickr external detail means include the submodel
    if ((array_key_exists('location:image', $values) && $values['location:image'])
        || array_key_exists('location_image:external_details', $values) && $values['location_image:external_details']) {
      $structure['submodel'] = array(
          'model' => 'location_image',
          'fk' => 'location_id'
      );
    }
    $s = submission_builder::build_submission($values, $structure);
   
    // on first save of a new location, link it to the website.
    if (empty($values['location:id']))
      $s['subModels'] = array(
        array(
          'fkId' => 'location_id', 
          'model' => array(
            'id' => 'locations_website',
            'fields' => array(
              'website_id' => $args['website_id']
            )
          )
        )
      );

    return $s;
  }

  /**
   * When viewing the list of locations for this user, get the grid to insert into the page.
   * Filtering of locations is by Indicia User ID stored in the user profile.
   * Enable Easy Login module to achieve this function.
   */
  protected static function getLocationListGrid($args, $node, $auth, $attributes) {
    global $user;
    // User must be logged in before we can access their records.
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    
    // get the Indicia User ID attribute so we can filter the grid to this user
    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
    }
    
    if (!isset($iUserId) || !$iUserId) {
      return lang::get('LANG_No_User_Id');
    }
    
    // Subclassed forms may provide a getLocationListGridPreamble function
    if(method_exists(self::$called_class, 'getLocationListGridPreamble'))
      $r = call_user_func(array(self::$called_class, 'getLocationListGridPreamble'));
    else
      $r = '';
    
    $r .= data_entry_helper::report_grid(array(
      'id' => 'locations-grid',
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(self::$called_class, 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => array(
        'website_id' => $args['website_id'], 
        'iUserID' => $iUserId
      )
    ));    
    $r .= '<form>';    
    $r .= '<input type="button" value="' . lang::get('LANG_Add_Location') . '" ' .
            'onclick="window.location.href=\'' . url('node/'.($node->nid), array('query' => 'new')) . '\'">';    
    $r .= '</form>';
    return $r;
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected function getArgDefaults($args) {
     if (!isset($args['structure']) || empty($args['structure']))
      $args['structure'] = 
              "=Place=\r\n".
              "?Please provide the spatial reference of the location. You can enter the reference directly, or search for a place then click on the map to set it.?\r\n".
              "[location name]\r\n".
              "[location code]\r\n".
              "[location type]\r\n".
              "[spatial reference]\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[location comment]\r\n".
              "[*]\r\n".
              "=*=";
    if (!isset($args['location_images']))
      $args['location_images'] == false; 
    if (!isset($args['grid_report']))
      $args['grid_report'] = 'reports_for_prebuilt_forms/simple_location_list';
    return $args;
  }

  protected function getReportActions() {
    return array(array( 'display' => 'Actions', 
                        'actions' =>  array(array('caption' => lang::get('Edit'), 
                                                  'url'=>'{currentUrl}', 
                                                  'urlParams'=>array('location_id'=>'{id}')))
    ));
  }
  
}

