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
require_once('includes/report.php');
global $fieldsToHoldInCountUnitBoundary;
$fieldsToHoldInCountUnitBoundary = array('boundary_geom','geom','comment');
class iform_cudi_form extends iform_dynamic {
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_cudi_form_definition() {
    return array(
      'title'=>'Cudi Form',
      'category' => 'CUDI forms',
//      'helpLink'=>'',
      'description'=>'TODO. '
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
                "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference control<br/>".
                "&nbsp;&nbsp;<strong>[place search]</strong> - zooms the map to the entered location.<br/>".
                "&nbsp;&nbsp;<strong>[spatial reference]</strong> - a location must always have a spatial reference.<br/>".
                "&nbsp;&nbsp;<strong>[location name]</strong> - a text box to enter a descriptive name for the locataion.<br/>".
                "&nbsp;&nbsp;<strong>[location code]</strong> - a text box to enter an identifying code for the location.<br/>".
                "&nbsp;&nbsp;<strong>[location type]</strong> - a list to select the location type (hidden if a filter limits this to a single type).<br/>".
                "&nbsp;&nbsp;<strong>[location comment]</strong> - a text box for comments.<br/>".
                "&nbsp;&nbsp;<strong>[location photo]</strong> - a photo upload for location images. <br/>".
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
          'name'=>'list_all_locations',
          'caption'=>'List all locations',
          'description'=>'Should the user be given the option to list all locations in the grid rather than just their own? '.
              'To use this, the selected report must have an ownData parameter and return an editable field. ' .
              'See reports_for_prebuilt_forms/simple_location_list_2 for an example.',
          'type'=>'boolean',
          'required' => false,
          'default'=>false,
          'group'=>'User Interface'
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
          'description'=>'Supply the ID of the Proferred Boundary Location Attribute in the database.',
          'type'=>'textarea',
          'required' => false,
        ),
        array(
          'name'=>'preferred_boundary_attribute_id',
          'caption'=>'Preferred Boundary Attribute Id',
          'description'=>'The location type id of the preferred boundary attribute type.',
          'type'=>'string',
          'required' => false,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'count_unit_location_type_id',
          'caption'=>'Count Unit Location Type Id',
          'description'=>'The location type id of the Count Unit location type.',
          'type'=>'string',
          'required' => false,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'count_unit_boundary_location_type_id',
          'caption'=>'Count Unit Boundary Location Type Id',
          'description'=>'The location type id of the Count Unit Boundary location type.',
          'type'=>'string',
          'required' => false,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'administrator_mode',
          'caption'=>'Administrator Mode?',
          'description'=>'Place page into administrator mode. This enables extra functionality and better privileges for performing certain tasks.',
          'type'=>'boolean',
          'required' => false,
          'group'=>'Administrator Mode'
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
    $mode = (isset($args['no_grid']) && $args['no_grid']) ? self::MODE_NEW : self::MODE_GRID;
    
    if ($_POST && !is_null(data_entry_helper::$entity_to_load)) {
      // errors with new sample or entity populated with post, so display this data.
      $mode = self::MODE_EXISTING; 
    } else if (array_key_exists('location_id', $_GET)){
      // request for display of existing record
      $mode = self::MODE_EXISTING;
    } else if (array_key_exists('new', $_GET)){
      // request to create new record (e.g. by clicking on button in grid view)
      $mode = self::MODE_NEW;
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
    $r = '<div id="locationList">' . 
            call_user_func(array(self::$called_class, 'getLocationListGrid'), $args, $node, $auth) . 
          '</div>';
    return $r;  
  }
    
  // Get an existing location.
  protected static function getEntity($args, $auth) {
    data_entry_helper::$entity_to_load = array();
    self::load_existing_record($args, $auth, 'location', $_GET['location_id']);
  }
  
  /*
   * Return the id of the preferred count unit boundary location (or latest one if preferred isn't specified) when loading an existing count unit location.
   * If a preferred boundary is not found then return null
   */
  protected static function getIdForCountUnitBoundaryIfApplicable($args, $auth) {
    $preferredBoundaryValueReportData = data_entry_helper::get_report_data(array(
      'dataSource'=>'library/location_attribute_values/location_attribute_values_for_location_or_location_attribute_id',
      'readAuth'=>$auth['read'],
      'extraParams'=>array('count_unit_id' => $_GET['location_id'], 'preferred_boundary_location_attribute_id' => $args['preferred_boundary_attribute_id'])
    ));
    $preferredBoundaryValue = $preferredBoundaryValueReportData[0]['preferred_boundary'];
    if (empty($preferredBoundaryValue)) //{
      $preferredBoundaryValue = null;
    return $preferredBoundaryValue;
  }
  
  /**
   * Method which populates data_entry_helper::$entity_to_load with the values from an existing
   * record. Useful when reloading data to edit.
   * @param array $readAuth Read authorisation tokens
   * @param string $entity Name of the entity to load data from.
   * @param integer $id ID of the database record to load
   * @param string $view Name of the view to load attributes from, normally 'list' or 'detail'. 
   * @param boolean $sharing Defaults to false. If set to the name of a sharing task 
   * (reporting, peer_review, verification, data_flow or moderation), then the record can be 
   * loaded from another client website if a sharing agreement is in place.
   * @link https://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/website-agreements.html
   * @param boolean $loadImages If set to true, then image information is loaded as well.
   */
  public static function load_existing_record($args, $auth, $entity, $id, $view = 'detail', $sharing = false, $loadImages = false) {
    global $fieldsToHoldInCountUnitBoundary;
    $parentRecord = data_entry_helper::get_population_data(array(
      'table' => $entity,
      'extraParams' => $auth['read'] + array('id' => $id, 'view' => $view),
      'nocache' => true,
      'sharing' => $sharing
    ));
    $preferredBoundaryId = self::getIdForCountUnitBoundaryIfApplicable($args, $auth);
    if (!empty($preferredBoundaryId)) {
      $preferredBoundaryRecord = data_entry_helper::get_population_data(array(
        'table' => $entity,
        'extraParams' => $auth['read'] + array('id' => $preferredBoundaryId, 'view' => $view),
        'nocache' => true,
        'sharing' => $sharing
      ));
    }
    if (isset($parentRecord['error'])) throw new Exception($parentRecord['error']);
    if (isset($preferredBoundaryRecord['error'])) throw new Exception($preferredBoundaryRecord['error']);
    
    // set form mode
    if (data_entry_helper::$form_mode===null) data_entry_helper::$form_mode = 'RELOAD';
    // populate the entity to load with the record data
    // If the data is held in the Count Unit Boundary record, then load that, else load the data from the parent
    foreach($parentRecord[0] as $key => $value) {
      if (!empty($preferredBoundaryRecord) && (in_array($key,$fieldsToHoldInCountUnitBoundary)))
        data_entry_helper::$entity_to_load["$entity:$key"] = $preferredBoundaryRecord[0][$key];
      else 
        data_entry_helper::$entity_to_load["$entity:$key"] = $value;
    }
    //TODO->Images not tested
    if ($loadImages) {
      $images = data_entry_helper::get_population_data(array(
        'table' => $entity . '_image',
        'extraParams' => $auth['read'] + array($entity . '_id' => $id),
        'nocache' => true,
        'sharing' => $sharing
      ));
      if (isset($images['error'])) throw new Exception($parentRecord['error']);
      foreach($images as $image) {
        data_entry_helper::$entity_to_load[$entity . '_image:id:' . $image['id']]  = $image['id'];
        data_entry_helper::$entity_to_load[$entity . '_image:path:' . $image['id']] = $image['path'];
        data_entry_helper::$entity_to_load[$entity . '_image:caption:' . $image['id']] = $image['caption'];
      }
    }
  }
  
  /*
   * Load the location attributes applicable for use
   */
  protected static function getAttributes($args, $auth) { 
    //Always hide the preferred boundary textbox as the user shouldn't see it.
    //Only hide, don't remove it, as we still need the value from it.
    data_entry_helper::$javascript = "
      $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').hide();
      $('[for=\"locAttr\\\\:".$args['preferred_boundary_attribute_id']."\"]').hide();\n
    ";
    //Get attributes associated with the parent
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $attrOpts = array(
      'id' =>$_GET['location_id']  
      ,'valuetable'=>'location_attribute_value'
      ,'attrtable'=>'location_attribute'
      ,'key'=>'location_id'
      ,'fieldprefix'=>'locAttr'
      ,'extraParams'=>$auth['read']
      ,'survey_id'=>$args['survey_id']
      ,'location_type_id'=>$args['count_unit_location_type_id']      
    );
    $mainAttributes = data_entry_helper::getAttributes($attrOpts, false);
    
    //Get attributes associated with the child boundary
    $boundaryLocationId =  self::getIdForCountUnitBoundaryIfApplicable($args, $auth);
    if (!empty($boundaryLocationId)) {
      $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
      $attrOpts = array(
        'id' =>$boundaryLocationId
        ,'valuetable'=>'location_attribute_value'
        ,'attrtable'=>'location_attribute'
        ,'key'=>'location_id'
        ,'fieldprefix'=>'locAttr'
        ,'extraParams'=>$auth['read']
        ,'survey_id'=>$args['survey_id']
        ,'location_type_id'=>$args['count_unit_boundary_location_type_id']      
      );
      $boundaryAttributes = data_entry_helper::getAttributes($attrOpts, false); 
    }
    //Merge the parent and child boundary attributes if needed to we have both sets of attributes.
    if (empty($mainAttributes))
      $mainAttributes=array();
    if (empty($boundaryAttributes))
      $boundaryAttributes=array(); 
    $attributes = array_merge($mainAttributes,$boundaryAttributes);
    
    return $attributes;
  }
  

  
  /**
   * Retrieve the additional HTML to appear at the top of the first
   * tab or form section. This is a set of hidden inputs containing the website ID and
   * survey ID as well as an existing location's ID.
   * @param type $args 
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $r = $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    if (isset(data_entry_helper::$entity_to_load['location:id'])) {
      $r .= '<input type="hidden" id="location:id" name="location:id" value="' . data_entry_helper::$entity_to_load['location:id'] . '" />' . PHP_EOL;    
    }
    $r .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['location:id']), $auth['read']);
    return $r;
  }
 
  /** 
   * Get the map control.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      $options
    );
    // If a drawing tool is on the map we can support boundaries.
    $boundaries = false;
    foreach ($options['standardControls'] as $ctrl) {
      if (substr($ctrl, 0, 4)==='draw') {
        $boundaries = true;
        break;
      }
    }
    if (isset(data_entry_helper::$entity_to_load['location:centroid_geom'])) 
      $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['location:centroid_geom'];
    if ($boundaries && isset(data_entry_helper::$entity_to_load['location:boundary_geom'])) 
      $options['initialBoundaryWkt'] = data_entry_helper::$entity_to_load['location:boundary_geom'];
    if ($args['interface']!=='one_page')
      $options['tabDiv'] = $tabalias;
    $olOptions = iform_map_get_ol_options($args);
    if (!isset($options['standardControls']))
      $options['standardControls']=array('layerSwitcher','panZoom');
    $r = '';
    $r .= data_entry_helper::map_panel($options, $olOptions);
    // Add a geometry hidden field for boundary support
    if ($boundaries) 
      $r .= '<input type="hidden" name="location:boundary_geom" id="imp-boundary-geom" value="' .
          data_entry_helper::$entity_to_load['location:boundary_geom'] . '"/>';
    return $r;
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
  
  protected static function get_control_locationcreatedon($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Created_On'),
      'fieldname' => 'location:created_on',
      'class' => 'control-width-5'
    ), $options));
  }

  /*
   * Control allows a user to select which count unit boundary version they intend to be the preferred one upon saving.
   * In order for control to operate correctly, the parent count unit must be loaded into the location_id parameter in the URL.
   */
  protected static function get_control_boundaryversions($auth, $args, $tabalias, $options) {
    //When adding a new record, don't show the control at all
    if (!empty($_GET['location_id'])) {
      global $user;
      if ($args['administrator_mode']==1) 
        $admin_mode=1;
      else
        $admin_mode=0;
      iform_load_helpers(array('report_helper')); 
      //When the preferred count unit changes, put the value into the text box of the field that holds the preferred count unit location attribute.
      //Also default the preferred count unit drop-down to the existing preferred count unit.
      data_entry_helper::$javascript = "$('#set-preferred').click( function() {
                                          $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val($('#boundary_versions').val());
                                          alert('The preferred boundary has been set');
                                        });
                                        $(\"#boundary_versions option[value=\"+$('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val()+\"]\").attr('selected', 'selected');\n
                                        ";   
      $options = array(
        'dataSource'=>'reports_for_prebuilt_forms/CUDI/get_count_unit_boundaries_for_user_role',
        'readAuth'=>$auth['read'],
        'extraParams' =>array('preferred_boundary_attribute_id'=>$args['preferred_boundary_attribute_id'],
                              'current_user_id'=>$user->uid,
                              'count_unit_id'=>$_GET['location_id'],
                              'admin_role'=>$admin_mode)
      );
      //Get the report options such as the Preset Parameters on the Edit Tab
      $options = array_merge(
        iform_report_get_report_options($args, $readAuth),
      $options);    
      //Collect the boundaries from a report.
      $boundaryVersions = report_helper::get_report_data($options);
      $r = '<label for="boundary_versions">Boundary Versions:</label> ';
      //Put the count unit boundaries into a drop-down
      $r .= '<select id = "boundary_versions">';
      $r .= '<option>Please Select...</option>';
      foreach ($boundaryVersions as $boundaryVersionData) {
        $r .= '<option value="'.$boundaryVersionData['id'].'">'.$boundaryVersionData['id'].' - '.$boundaryVersionData['created_on'].' -> '.$boundaryVersionData['updated_on'].'</option>';
      }
      
      $r .= "</select>\n";
      if ($admin_mode)
        $r .= "<input type='button' id='set-preferred' class='indicia-button'value='Set Preferred'>";
      $r .= "<br>";
      return $r;
    }
  }
  /*
  protected static function get_control_createnewboundary($auth, $args, $tabalias, $options) {
    if (!empty($_GET['location_id'])) {
      $r = 'Correct Existing Boundary Rather Than Create New One?: <input type="checkbox" name="update-existing-boundary" value="update-existing-boundary"><br>';
      return $r;
    }
  }
  */
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
   * Get the location photo control
   */
  protected static function get_control_locationphoto($auth, $args, $tabalias, $options) {
    return data_entry_helper::file_box(array_merge(array(
      'table'=>'location_image',
      'caption'=>lang::get('File upload')
    ), $options));
  }
  
  /*
   * When we a location is a Count Unit, most of the data is saved into the Count Unit Boundary Location.
   * So we need to remove the data going into the Count Unit Boundary from the original parent.
   */
  protected static function unsetParentValues($valuesForParent, $args) {
    global $fieldsToHoldInCountUnitBoundary;
    foreach($fieldsToHoldInCountUnitBoundary as $fieldToHoldInCountUnitBoundary) {
      unset($valuesForParent['location:'.$fieldToHoldInCountUnitBoundary]);
      unset($valuesForParent['location:'.$fieldToHoldInCountUnitBoundary]);
      unset($valuesForParent['location:'.$fieldToHoldInCountUnitBoundary]);
    }
    //Remove all attributes from the parent location apart from the attribute pointing to the boundary itself.
    foreach ($valuesForParent as $key=>$valueForParent) {
      $keyParts = explode(':',$key);    
      if ($keyParts[0] === 'locAttr' && $keyParts[1]!==$args['preferred_boundary_attribute_id']) {
        unset($valuesForParent[$key]);
      }
    }
    return $valuesForParent;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $s = self::prepare_locations_to_save_for_submission($values, $args);
    return $s;
  }
  
  /*
   * When we save a Count Unit location, we save most of the information into a seperate Count Unit Boundary location.
   * We need to prepare the submission for this.
   */
  protected static function prepare_locations_to_save_for_submission($values, $args) {   
    $values['location:location_type_id']=$args['count_unit_location_type_id'];
    //Remove the values we are saving into the boundary location from the parent.
    $valuesForParent = self::unsetParentValues($values,$args);
    $s = self::create_submission($valuesForParent, $args);
    $values['location:location_type_id']=$args['count_unit_boundary_location_type_id'];
    $s['subModels'][1]['fkId'] = 'parent_id';      
    //The location attribute that points to the boundary should only be saved for the parent, so remove
    //it from the child boundary
    foreach ($values as $key=>$value) {
      $keyParts = explode(':',$key);    
      if ($keyParts[0] === 'locAttr' && $keyParts[1]===$args['preferred_boundary_attribute_id']) {
        unset($values[$key]);
      }
    }
    // TODO ->at this point, if not creating a new boundary we need to get the boundary id from somewhere
    unset($values['location:id']);
    //Write to id 1, as we don't want to overwrite the locations_website submission which is in submodel 0
    $s['subModels'][1]['model'] = self::create_submission($values, $args);
    return $s;
  }
  
  public static function create_submission($values, $args) {
    $structure = array(
        'model' => 'location',
    );
    // Either an uploadable file, or a link to a Flickr external detail means include the submodel
    // (Copied from data_entry_helper::build_sample_occurrence_submission. If file_box control is used
    // then build_submission calls wrap_with_images instead)
    if ((array_key_exists('location:image', $values) && $values['location:image'])
        || array_key_exists('location_image:external_details', $values) && $values['location_image:external_details']) {
      $structure['submodel'] = array(
          'model' => 'location_image',
          'fk' => 'location_id'
      );
    }
    $s = submission_builder::build_submission($values, $structure);
   
    // On first save of a new location, link it to the website.
    // Be careful not to over-write other subModels (e.g. images)
    if (empty($values['location:id']))
      $s['subModels'][] = array(
          'fkId' => 'location_id', 
          'model' => array(
            'id' => 'locations_website',
            'fields' => array(
              'website_id' => $args['website_id']
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
  protected static function getLocationListGrid($args, $node, $auth) {
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
    
    $extraParams = array(
      'website_id' => $args['website_id'], 
      'iUserID' => $iUserId,
    );
    if (!$args['list_all_locations']) {
      // The option to list all locations is denied so enforce selection of own data.
      $extraParams['ownData'] = '1';
    }
    $r .= data_entry_helper::report_grid(array(
      'id' => 'locations-grid',
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(self::$called_class, 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => $extraParams,
      'paramDefaults' => array('ownData' => '1')
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
                                                  'url' => '{currentUrl}', 
                                                  'urlParams' => array('location_id'=>'{id}'),
                                                  'visibility_field' => 'editable'))
    ));
  }
  
}

