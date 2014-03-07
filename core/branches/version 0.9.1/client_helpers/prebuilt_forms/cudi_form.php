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
          'description'=>'Supply the ID of the Preferred Boundary Location Attribute in the database.',
          'type'=>'textarea',
          'required' => false,
        ),
        array(
          'name'=>'preferred_boundary_attribute_id',
          'caption'=>'Preferred Boundary Attribute Id',
          'description'=>'The location type id of the preferred boundary attribute type.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'count_unit_location_type_id',
          'caption'=>'Count Unit Location Type Id',
          'description'=>'The location type id of the Count Unit location type.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'count_unit_boundary_location_type_id',
          'caption'=>'Count Unit Boundary Location Type Id',
          'description'=>'The location type id of the Count Unit Boundary location type.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'attribute_ids_to_store_on_count_unit_boundary',
          'caption'=>'Location Attributes to be stored in the Count Unit Boundary',
          'description'=>'Comma seperated list of Location Attribute Ids that are to be stored as part of the Count Unit Boundary.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'verified_attribute_id',
          'caption'=>'Verification Attribute Id',
          'description'=>'Id of the "Verified" location attribute.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),  
        array(
          'name'=>'annotation_location_type_id',
          'caption'=>'Annotation Location Type Id',
          'description'=>'Id of the annotation location type.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),  
        array(
          'name'=>'boundary_start_date_id',
          'caption'=>'Boundary Start Date Id',
          'description'=>'Id of the start date attribute for a count unit boundary.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ), 
        array(
          'name'=>'boundary_end_date_id',
          'caption'=>'Boundary End Date Id',
          'description'=>'Id of the end date attribute for a count unit boundary.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'official_reason_for_change_attribute_id',
          'caption'=>'Official Reason For Change Id',
          'description'=>'Id of the Official Reason For Change location attribute.',
          'type'=>'string',
          'required' => true,
          'group'=>'Configurable Ids'
        ),
        array(
          'name'=>'surveys_attribute_id',
          'caption'=>'Surveys Attribute Id',
          'description'=>'Id of the Surveys location attribute.',
          'type'=>'string',
          'required' => true,
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
    } else if (array_key_exists('location_id', $_GET)||array_key_exists('parent_id', $_GET)||array_key_exists('zoom_id', $_GET)){
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
    //If a zoom_id is supplied, it means we are moving into add mode but for a specific region
    //So we want an empty page but the map should be zoomed in to the location boundary.
    //This boundary acts as a ghost, so it isn't actually submitted, it is purely visual.
    if (!empty($_GET['zoom_id']))
      self::zoom_map($auth, $_GET['zoom_id'],$args);
    else {     
      //Note:
      //When the user selects a boundary, the page is reloaded with an additional parent_id parameter in the url.
      //In this case, we use the parent_id as the main record id, the system then uses the location_id to load boundary information
      //for the main record.
      if (!empty($_GET['parent_id']))
        $recordId = $_GET['parent_id'];
      elseif (!empty($_GET['location_id']))
        $recordId = $_GET['location_id'];    
      if (!empty($recordId))
        self::load_existing_record($args, $auth, 'location', $recordId);
    }
  }
  
  /*
   * Return the id of the preferred count unit boundary location (or latest one if preferred isn't specified) when loading an existing count unit location.
   * If a preferred boundary is not found then return null. The preferred_boundary_location_attribute_id holds the preferred boundary id.
   */
  protected static function getIdForCountUnitPreferredBoundaryIfApplicable($args, $auth) { 
    $preferredBoundaryValueReportData = data_entry_helper::get_report_data(array(
      'dataSource'=>'reports_for_prebuilt_forms/cudi/get_preferred_boundary_id',
      'readAuth'=>$auth['read'],
      'extraParams'=>array('count_unit_id' => $_GET['location_id'], 'preferred_boundary_location_attribute_id' => $args['preferred_boundary_attribute_id'],
                           'count_unit_boundary_location_type_id'=>$args['count_unit_boundary_location_type_id'])
    ));
    $preferredBoundaryValue = $preferredBoundaryValueReportData[0]['preferred_boundary'];
    if (empty($preferredBoundaryValue)) //{
      $preferredBoundaryValue = null;
    return $preferredBoundaryValue;
  }
  
  /*
   * This function is used when an add site/count unit screen is in Add Mode 
   * and we just want to automatically zoom the map to a region/site we are adding a location to.
   * This boundary is purely visual and isn't submitted.
   */
  private static function zoom_map($auth, $id,$args) {
    //In add mode hide the official reason for change
    data_entry_helper::$javascript .= "$('[for=\"locAttr\\\\:".$args['official_reason_for_change_attribute_id']."\"]').remove();\n 
                                       $('#locAttr\\\\:".$args['official_reason_for_change_attribute_id']."').remove();\n";
    //In add mode hide the verified checkbox and label
    data_entry_helper::$javascript .= "$('[for=\"locAttr\\\\:".$args['verified_attribute_id']."\"]').remove();\n 
                                       $('#locAttr\\\\:".$args['verified_attribute_id']."').remove();\n";
    $loc = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('id' => $id, 'view' => 'detail'),
      'nocache' => true
    ));
    
    if (isset($loc['error'])) throw new Exception($loc['error']);
    $loc=$loc[0];
    //Just put the feature onto the map, set the feature type to zoomToBoundary so it isn't used for anything
    //other than being a visual cue to zoom to.
    data_entry_helper::$javascript .= "
mapInitialisationHooks.push(function(mapdiv) {
  var feature, geom=OpenLayers.Geometry.fromWKT('{$loc[boundary_geom]}');

  if (indiciaData.mapdiv.map.projection.getCode() != indiciaData.mapdiv.indiciaProjection.getCode()) {
      geom.transform(indiciaData.mapdiv.indiciaProjection, indiciaData.mapdiv.map.projection);
  }
  feature = new OpenLayers.Feature.Vector(geom);
  feature.attributes.type = 'zoomToBoundary';
  indiciaData.mapdiv.map.editLayer.addFeatures([feature]);
  mapdiv.map.zoomToExtent(feature.geometry.bounds);
});
    ";
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
    //If we are loading a parent count unit rather than a boundary, then a none admin user
    //will always have the latest boundary loaded (even if preferred is set). So we need to 
    //get a list of boundary versions they will see so we can load the latest one
    if (empty($_GET['parent_id'])) {
      $boundaryVersions = self::getBoundaryVersionsList($args,$_GET['location_id'],$auth);
    }
    $parentRecord = data_entry_helper::get_population_data(array(
      'table' => $entity,
      'extraParams' => $auth['read'] + array('id' => $id, 'view' => $view),
      'nocache' => true,
      'sharing' => $sharing
    ));
    if (!empty($_GET['parent_id'])) 
      $boundaryId = $_GET['location_id'];
    else {
      //If looking at a Count Unit as an administrator, the boundary info we view comes from the preferred boundary
      if ($args['administrator_mode']==1) {
        $boundaryId = self::getIdForCountUnitPreferredBoundaryIfApplicable($args, $auth);
      } else {
        //If looking at a Count Unit as an normal, the boundary info we view comes from the latest boundary.
        //As the report returns data as "order by id desc" it means we can just get the first id.
        $boundaryId = $boundaryVersions[0]['id'];
      }
    }
    if (!empty($boundaryId)) {
      $preferredBoundaryRecord = data_entry_helper::get_population_data(array(
        'table' => $entity,
        'extraParams' => $auth['read'] + array('id' => $boundaryId, 'view' => $view),
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
    data_entry_helper::$javascript .= "
      $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').hide();
      $('[for=\"locAttr\\\\:".$args['preferred_boundary_attribute_id']."\"]').hide();\n
    ";
    //Get the preferred boundary id for use if we are viewing a count unit
    $preferredBoundaryLocationIdCalculatedFromCountUnit =  self::getIdForCountUnitPreferredBoundaryIfApplicable($args, $auth);   
    if (!empty($_GET['parent_id'])) {
      //If we are looking at a count unit boundary, then we have both the parent and boundary ids available to us
      $parentId=$_GET['parent_id'];
      $boundaryId=$_GET['location_id'];
    } else {
      //If we are looking at a count unit instead of a boundary, then we need to get the preferred boundary id
      $parentId=$_GET['location_id'];
      $boundaryId=$preferredBoundaryLocationIdCalculatedFromCountUnit;
    }
    //Get attributes associated with the parent
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $attrOpts = array(
      'id' =>$parentId  
      ,'valuetable'=>'location_attribute_value'
      ,'attrtable'=>'location_attribute'
      ,'key'=>'location_id'
      ,'fieldprefix'=>'locAttr'
      ,'extraParams'=>$auth['read']
      ,'survey_id'=>$args['survey_id']
      ,'location_type_id'=>$args['count_unit_location_type_id']      
    );
    $mainAttributes = data_entry_helper::getAttributes($attrOpts, false);
    //Get attributes associated with the boundary
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $attrOpts = array(
      'id' =>$boundaryId
      ,'valuetable'=>'location_attribute_value'
      ,'attrtable'=>'location_attribute'
      ,'key'=>'location_id'
      ,'fieldprefix'=>'locAttr'
      ,'extraParams'=>$auth['read']
      ,'survey_id'=>$args['survey_id']
      ,'location_type_id'=>$args['count_unit_boundary_location_type_id']      
    );
    $boundaryAttributes = data_entry_helper::getAttributes($attrOpts, false);
    //Merge the parent and child boundary attributes if needed so we have both sets of attributes.
    if (empty($mainAttributes))
      $mainAttributes=array();
    if (empty($boundaryAttributes))
      $boundaryAttributes=array(); 
    $attributes = array_merge($mainAttributes,$boundaryAttributes);
    //Need to format any dates from the database manually.
    foreach ($attributes as $attributeNum=>&$attributeData) {
      if ($attributeData['data_type']==='D' && !empty($attributeData['displayValue'])) {
        $d = new DateTime($attributeData['displayValue']);
        $attributeData['displayValue'] = $d->format('d/m/Y');
        $attributeData['default'] = $attributeData['displayValue'];
      }
    }
    //Disable Verified checkbox for normal users, note that we use a selector for a field whose name starts with locAttr:<Verified Attribute ID>
    //as there is a hidden field with the same name but no id and we want to disable both so they are not submitted in the post.
    if ($args['administrator_mode']==0) {
      data_entry_helper::$javascript .= "$(\"input[name^='locAttr\\\\:".$args['verified_attribute_id']."']\").attr('disabled','true');";
    }
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
 
  /*
   * Not Now button appears if the calling page is the data entry screen and there is no boundary.
   * The data entry screen must provide its URL name in a parameter called calling_page. The button then
   * returns no_boundary_warning=true in the URL back to the data entry page, this page can then look for this
   * in the URL so it stops warning the user about the missing boundary.
   * 
   */
  protected static function get_control_notnow($auth, $args, $tabalias, $options) {
    //We only ever need to worry about the location_id in this case as parent_id only appears if there is a boundary,
    //in which case the Not Now button doesn't appear anyway.
    $parentCountUnitId=$_GET['location_id'];  
    //Only display Not Now if user have come from data-entry page
    if ($_GET['calling_page']==$options['data_entry_page_name']) {
      //Return the count unit id and a parameter to say stop showing the no boundary warning.
      $urlQuery='no_boundary_warning=true&'.$options['data_entry_page_param'].'='.$parentCountUnitId;
      //Get drupal to generate the URL for us
      $notNowUrl = url($options['data_entry_page_name'], array('query'=>"$urlQuery"));
      $r = "<div id='notnow-button'><input type='button' value='Not Now' ONCLICK='window.location.href=\"".$notNowUrl."\"'></div>";
      //Only show the Not Now button when there isn't a boundary
      data_entry_helper::$javascript .= "
        $('#notnow-button').hide();
        if (!$('#imp-boundary-geom').val()) {
         $('#notnow-button').show();
        }
      ";     
      return $r;
    }
  }
  
  /** 
   * Survey control that supports multiple surveys each with a date
   */
  protected static function get_control_surveys($auth, $args, $tabalias, $options) {
    //Get the Delete icon for the Surveys Grid
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path()."../media/images/" : data_entry_helper::$images_path;
    $deleteIcon = $imgPath."delete.png";
    //Pass the delete icon to javascript
    data_entry_helper::$javascript .= "indiciaData.deleteImagePath='".$deleteIcon."';\n";
    
    if (!empty($_GET['parent_id']))
      $countUnitId = $_GET['parent_id'];
    else 
      $countUnitId = $_GET['location_id'];  
    //Get the data for all surveys that are relevant
    $surveysData = data_entry_helper::get_population_data(array(
      'table' => 'survey',
      'extraParams' => $auth['read'],
      'nocache' => true,
      'sharing' => $sharing
    ));
    //If we are in edit mode, then collect the data relating to the previously selected surveys, the ID and Date are
    //held as JSON, so we need to decode it.
    if ($countUnitId) {
      $selectedSurveysData = data_entry_helper::get_population_data(array(
        'table' => 'location_attribute_value',
        'extraParams' => $auth['read'] + array('location_attribute_id'=>$args['surveys_attribute_id'], 'location_id'=>$countUnitId),
        'nocache' => true,
        'sharing' => $sharing
      ));
      if (!empty($selectedSurveysData)) {       
        foreach ($selectedSurveysData as $idx=>$theSurveyData) {
          $decodedSavedSurvey[$idx] = json_decode($theSurveyData['raw_value']);
          $decodedSavedSurveyIds[$idx] = $decodedSavedSurvey[$idx][0];
        }
      }
    }
    //We need to populate the surveys drop-down but not include items that are already on the grid.
    $r = '<div id="surveys-control">';
    $r .='<h3>Surveys</h3>';
    $r .= '<label>Surveys: </label><select id = "survey-select">';
    $r .= '<option id="please-select-surveys-item">Please Select</option>';
    if (!empty($surveysData)) {
      foreach ($surveysData as $surveyData) {
        if (empty($decodedSavedSurveyIds) || !in_array($surveyData['id'],$decodedSavedSurveyIds)) {
          $r .= '<option id="survey-select-'.$surveyData['id'].'" value="'.$surveyData['id'].'">'.$surveyData['title'].'</option>';
        }
        //Get the names of the survey items in the grid and save them in an array where the ids are the keys - for use in a minute
        if (!empty($decodedSavedSurveyIds)) {
          if (in_array($surveyData['id'],$decodedSavedSurveyIds)) {
            $surveyNameInGrid[$surveyData['id']]['title']=$surveyData['title'];
          }
        }
      }
    }
    $r .= '</select><br>';
    $r .= data_entry_helper::date_picker(array_merge(array(
      'label'=>lang::get('LANG_Location_Surveys_Date'),
      'fieldname'=>'survey:date',
    ), $options));
    
    $r .= '</br>';
    $r .= '<input type="button" id="select-surveys-add" value="Add" onclick="select_survey_and_date();"><br>';
    $r .= 
    '<table id="surveys-table" id="surveys-table" border="3">'.
      '<tr>'.
        '<th>Survey Id</th>'.
        '<th>Survey Name</th>'.
        '<th>Date</th>'.
        '<th>Remove</th>'.
        '<th style="display:none;">Existing Attribute Id</th>'.
        '<th style="display:none;" type="hidden">Deleted</th></tr>'.
        '<tr>'.
      '</tr>';   
    if ($countUnitId) {
      if (!empty($selectedSurveysData)) {
        foreach ($selectedSurveysData as $idx => $theSurveyData) {
        //Add a row to the grid of Selected Suveys and Dates for each existing survey that has been saved against the Count Unit
        //Note we use the Survey Id on the end of the various html ids. The fields that will be used in submission also have a 
        //"name" otherwise they won't show in the submission $values variable
        $r .= "
        <tr id='"."selected-survey-row-".$decodedSavedSurvey[$idx][0]."'>
          <td>
            <input style='border: none;' id='"."selected-survey-id-".$decodedSavedSurvey[$idx][0]."' name='"."selected-survey-id-".$decodedSavedSurvey[$idx][0]."' value='".$decodedSavedSurvey[$idx][0]."' readonly>
          </td>
          <td>
            <input style='border: none;' id='"."selected-survey-name-".$decodedSavedSurvey[$idx][0]."' value='".$surveyNameInGrid[$decodedSavedSurvey[$idx][0]]['title']."' readonly>
          </td>
          <td>
            <input id='"."selected-survey-date-".$decodedSavedSurvey[$idx][0]."' name='"."selected-survey-date-".$decodedSavedSurvey[$idx][0]."' value='".$decodedSavedSurvey[$idx][1]."'>
          </td>
          <td>
            <img class=\"action-button\" src=\"$deleteIcon\" onclick=\"remove_survey_selection(".$decodedSavedSurvey[$idx][0].",'".$surveyNameInGrid[$decodedSavedSurvey[$idx][0]]['title']."');\" title=\"Delete Survey Selection\">
          </td>
          <td style='display:none;'>
            <input id='"."selected-survey-existing-".$decodedSavedSurvey[$idx][0]."' name='"."selected-survey-existing-".$decodedSavedSurvey[$idx][0]."' value='".$theSurveyData['id']."'>
          </td>
          <td style='display:none;'>
            <input id='"."selected-survey-deleted-".$decodedSavedSurvey[$idx][0]."' name='"."selected-survey-deleted-".$decodedSavedSurvey[$idx][0]."' value='false'>
          </td>
        </tr>";
        }
      }          
    }   
    $r .= '</table>'; 
    $r .= '</div>';
    return $r;
  }
  
  /*
   * Control warns normal users when a boundary is not filled in at all.
   */
  protected static function get_control_noboundarywarning($auth, $args, $tabalias, $options) {
    //Only show warning in edit mode
    if ($_GET['location_id']) {
      data_entry_helper::$javascript .= "
      if (!$('#imp-boundary-geom').val()) {
        $('#no-boundary-warning').text('Please provide boundary details for this count unit.');
      }
      ";
      return '<div><h3><font color="red" id="no-boundary-warning"></font></h3></div>';
    }
  } 
  
  /*
   * Control button to move into adding/editing annotations mode. Code behind button is handled by javascript
   */ 
  protected static function get_control_addeditannotations($auth, $args, $tabalias, $options) {
    $r = '<div id="add-edit-annotations-button-div"><input id="annotations-mode-on" type="hidden" name="annotations-mode-on" value="">';
    $r .= '<input type="button" id="toggle-annotations" value="Add & Edit Annotations" onclick="hide_boundary_annotations_panels();"><div>';
    return $r;
  }
  
  /*
   * Draw the panel for handling the adding and editing of annotations
   */
  protected static function get_control_annotations($auth, $args, $tabalias, $options) {
    $r='';
    $r .= "<div id='annotation-details' style='display: none;'>";  
    $r .= "<input type='hidden' id='boundary-geom-temp' value=''><br>";
    $r .= "<input type='hidden' id='other-layer-boundary-geom-holder' name='other-layer-boundary-geom-holder' value=''><br>";
    $r .= "<label>".lang::get('LANG_Annotation_Name').":</label> <input id='annotation:name' name='annotation:name' value=''><br>";
    $r .= self::existingannotations($auth, $args, $tabalias, $options);
    $r .= self::control_annotationtype($auth, $args, $tabalias, $options);
    //Hidden id field used during submission when editing existing annotations, populated by javascript
    $r .= "<input type='hidden' id='annotation:id' name='annotation:id' value=''><br>";
    $r .= "</div>";
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
    //Get the zoom_id from the map to allow us to zoom to a specific region in add mode
    data_entry_helper::$javascript .= "indiciaData.zoomid='".$_GET['zoom_id']."';\n";
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

  /*
   * Control displays Count Unit name. 
   * If existing Count Unit is being displayed then a hidden field is used.
   * If a boundary is displayed, then the boundary name is set to <count Unit name> + ' - Boundary'
   * by using a hidden field.
   */
  protected static function get_control_locationname($auth, $args, $tabalias, $options) {
    //Where we get the Count Unit name from varies depending on whether we are 
    //viewing a Count Unit or Boundary. 
    //Note: Boundaries don't have names, but the field is mandatory in the database, so we
    //fill in the boundary name field with the <count Unit name> + ' - Boundary'
    //The field is not displayed on the boundary page, but it is hidden so it is still submitted.
    if (!empty($_GET['parent_id']))
      $countUnitId = $_GET['parent_id'];
    else 
      $countUnitId = $_GET['location_id'];
    //Get the Count Unit Name for use in read only label
    if (!empty($countUnitId)) {
      $locationNameData = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('id' => $countUnitId),
        'nocache' => true,
        'sharing' => $sharing
      ));
    }
    $locationName = $locationNameData[0]['name'];
    //If not adding a new Count Unit, we need to put the name in a read only label.
    if (!empty($countUnitId)) {
      return "<label>".lang::get('LANG_Location_Name').':</label> <input id="location:name" name="location:name" value="'.$locationName.'" readonly><br>';
    } else {      
      return data_entry_helper::text_input(array_merge(array(
        'label' => lang::get('LANG_Location_Name'),
        'fieldname' => 'location:name',
        'class' => 'control-width-5'
      ), $options));
    }
  }

  protected static function get_control_locationcode($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Code'),
      'fieldname' => 'location:code',
      'class' => 'control-width-5'
    ), $options));
  }
  
  /*
   * Display the person who created the Count Unit.
   */
  protected static function get_control_locationcreatedby($auth, $args, $tabalias, $options) {
    //The URL parameter varies depending on whether we are viewing a count unit record or have selected a boundary to views
    if (!empty($_GET['parent_id']))
      $countUnitId = $_GET['parent_id'];
    if ($_GET['location_id']) 
      $countUnitId = $_GET['location_id'];
    //Get the created by id from view before putting it into the report. This might not be the quickest way
    //of doing this, but is perhaps more elegant as we don't write another report that is specific only to a very small part of a single project.
    //Only show in add mode
    if (!empty($countUnitId)) {
      $locationCreatedByData = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('id' => $countUnitId, 'view' => 'detail'),
        'nocache' => true,
        'sharing' => $sharing
      )); 
      $reportOptions = array(
        'dataSource'=>'library/users/get_people_details_for_website_or_user',
        'readAuth'=>$auth['read'],
        'mode'=>'report',
        'extraParams' => array('user_id'=>$locationCreatedByData[0]['created_by_id'])
      );
      $userData = data_entry_helper::get_report_data($reportOptions);
      return "<label>".lang::get('LANG_Location_Created_By').":</label> <label>".$userData[0]['fullname_firstname_first']."</label><br>";
    }
  }
  
  /*
   * Display the Count Unit created date.
   */
  protected static function get_control_locationcreatedon($auth, $args, $tabalias, $options) {
    //The URL parameter varies depending on whether we are viewing a count unit record or have selected a boundary to view
    if (!empty($_GET['parent_id']))
      $countUnitId = $_GET['parent_id'];
    if ($_GET['location_id']) 
      $countUnitId = $_GET['location_id'];
    //Only show in add mode
    if (!empty($countUnitId)) {
      $locationCreatedByData = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('id' => $countUnitId, 'view' => 'detail'),
        'nocache' => true,
        'sharing' => $sharing
      ));
       
      $createdDataUnformatted = new DateTime($locationCreatedByData[0]['created_on']);
      $createdDateToShow = $createdDataUnformatted->format('d/m/Y');
      return "<label>".lang::get('LANG_Location_Created_On').":</label> <label>".$createdDateToShow."</label><br>";
    }
  }

  /*
   * Get a list of boundary versions
   */
  protected static function getBoundaryVersionsList($args,$locationId,$auth) {
    global $user;   
    if (!isset($user->profile_indicia_user_id) && function_exists('profile_load_profile'))
      profile_load_profile($user);
    iform_load_helpers(array('report_helper')); 
    if (!empty($user->profile_indicia_user_id))
      $extraParams=array('preferred_boundary_attribute_id'=>$args['preferred_boundary_attribute_id'],
                              'current_user_id'=>$user->profile_indicia_user_id,
                              'count_unit_id'=>$locationId,
                              'count_unit_boundary_location_type_id'=>$args['count_unit_boundary_location_type_id'],
                              'admin_role'=>$args['administrator_mode'],
                              'boundary_start_date_attribute_id'=>$args['boundary_start_date_id'],
                              'boundary_end_date_attribute_id'=>$args['boundary_end_date_id']);
    else 
      $extraParams=array('preferred_boundary_attribute_id'=>$args['preferred_boundary_attribute_id'],
                          'current_user_id'=>$user->uid,
                          'count_unit_id'=>$locationId,
                          'count_unit_boundary_location_type_id'=>$args['count_unit_boundary_location_type_id'],
                          'admin_role'=>$args['administrator_mode'],
                          'boundary_start_date_attribute_id'=>$args['boundary_start_date_id'],
                          'boundary_end_date_attribute_id'=>$args['boundary_end_date_id']);
      $optionsForBoundaryVersionsReport = array(
      'dataSource'=>'reports_for_prebuilt_forms/CUDI/get_count_unit_boundaries_for_user_role',
      'readAuth'=>$auth['read'],
      'extraParams' =>$extraParams
    );
    //Get the report options such as the Preset Parameters on the Edit Tab
    $optionsForBoundaryVersionsReport = array_merge(
      iform_report_get_report_options($args, $readAuth),
    $optionsForBoundaryVersionsReport);    
    //Collect the boundaries from a report.
    $boundaryVersions = report_helper::get_report_data($optionsForBoundaryVersionsReport);
    return $boundaryVersions;
  }
  
  /*
   * Get a list of existing annotations for a count unit
   */
  protected static function getAnnotationsList($args,$locationId,$auth) {
    iform_load_helpers(array('report_helper')); 
    $extraParams=array('count_unit_id'=>$locationId,
                       'count_unit_boundary_location_type_id'=>$args['count_unit_boundary_location_type_id']);
    $optionsForAnnotationsReport = array(
      'dataSource'=>'reports_for_prebuilt_forms/CUDI/get_count_unit_annotations',
      'readAuth'=>$auth['read'],
      'extraParams' =>$extraParams
    );
    //Get the report options such as the Preset Parameters on the Edit Tab
    $optionsForAnnotationsReport = array_merge(
      iform_report_get_report_options($args, $readAuth),
    $optionsForAnnotationsReport);    
    //Collect the annotations from a report.
    $annotations = report_helper::get_report_data($optionsForAnnotationsReport);
    return $annotations;
  }
  
  /*
   * Control allows user to select from an existing list of annotations related to a Count Unit.
   * Used to allow users to edit existing annotations
   */
  protected static function existingannotations($auth, $args, $tabalias, $options) {
    //Don't show control when adding new count units.
    //The user can still annotations, but there won't be any existing ones to choose from.
    if (!empty($_GET['location_id'])) {
      $parentCountUnitId=$_GET['location_id'];       
      global $user;
      iform_load_helpers(array('report_helper')); 
      //Collect the annotations from a report.
      $existingAnnotations = self::getAnnotationsList($args,$parentCountUnitId,$auth);
      //Need to pass the list of existing annotations to javascript as that is where the processing is 
      //handled when user makes a selection.
      map_helper::$javascript .= "indiciaData.existingannotations=".json_encode($existingAnnotations).";\n";
      //Only display control if there is something to list in it.
      if (!empty($existingAnnotations)) {
        $r = '<label for="existing_annotations">'.lang::get('LANG_Existing_Annotations').':</label> ';
        //Put the count unit annotatons into a drop-down and setup reloading of the page when an item is selected.
        $r .= '<select id = "existing_annotations" name="existing_annotations" onchange="load_annotation();">';
        $r .= '<option>(New Annotation)</option>';
        foreach ($existingAnnotations as $existingAnnotationData) { 
          $linkToAnnotationPage =
                url($_GET['q'], array('absolute' => true)).(variable_get('clean_url', 0) ? '?' : '&').
                'location_id='.$existingAnnotationData['id'].'&annotation_count_unit_id='.$parentCountUnitId; 
          //Annotation options setup as we go around the foreach loop.
          $r .= '<option value="'.$existingAnnotationData['id'].'" id="'.$linkToAnnotationPage.'">'.$existingAnnotationData['name'].'</option>';         
        }

        $r .= "</select></br>";
      }    
      return $r;
    }
  }        
  /*
   * Control allows a user to select which count unit boundary version they intend to be the preferred one upon saving.
   * In order for control to operate correctly, the parent count unit must be loaded into the location_id parameter in the URL.
   */
  protected static function get_control_boundaryversions($auth, $args, $tabalias, $options) {
    //If we are looking at a count unit boundary we need to show boundary versions for the parent.
    //If we are looking at a count unit we need to show boundary versions for the current location.
    if (!empty($_GET['parent_id']))
      $parentCountUnitId=$_GET['parent_id'];
    else
      $parentCountUnitId=$_GET['location_id'];  
    //When adding a new record, don't show the control at all
    if (!empty($_GET['location_id'])) {
      global $user;
      if ($args['administrator_mode']==1) 
        $admin_mode=1;
      else
        $admin_mode=0;
      iform_load_helpers(array('report_helper')); 
      //When the "Over-write Boundary and Save" is clicked, put the drop-down value into the textbox of the preferred count unit location attribute.
      //Also automatically select the checkbox that indicates we are updating rather creating a boundary. Then hide the checkbox to avoid it being tampered with as save occurs.
      data_entry_helper::$javascript .= "var preferredWhenScreenLoads = $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val(); 
                                         $('#set-preferred').click( function() {
                                           $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val($('#boundary_versions').val());
                                           $('#update-existing-boundary').attr('checked','checked'); 
                                           $('#update-existing-boundary').hide();
                                           $('#update-existing-boundary-label').hide();
                                         });";
      //As the Update Existing Boundary and Set Preferred on Save checkboxes are selected and deselected by the user, we need
      //to manipulate the Preferred Boundary Id textbox. This needs to occur whenever either checkbox is changed.
      data_entry_helper::$javascript .= "$('#update-existing-boundary').click( function() {
                                           set_preferred_on_save_checks();
                                         });";
      
      data_entry_helper::$javascript .= "$('#set-preferred-on-save').click( function() {
                                           set_preferred_on_save_checks();
                                         });";
      //User elects to set preferred on save, user elects to update existing boundary and a boundary is selected->Preferred Boundary is set to selected boundary
      //User elects to set preferred on save, user elects to update existing boundary and a boundary is NOT selected (viewing count unit)->Preferred Boundary remains the same
      //User elects to set preferred on save, user elects to create new boundary->Preferred Boundary set to empty (latest is assumed to be preferred)
      //User elects NOT to set preferred on save->Preferred Boundary left as it was
      data_entry_helper::$javascript .= "function set_preferred_on_save_checks() {
                                           if ($('#set-preferred-on-save').is(':checked') && $(\"#boundary_versions\").val()) {
                                             if ($('#update-existing-boundary').is(':checked')) {
                                               if ($(\"#boundary_versions\").val()) {
                                                 $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val($(\"#boundary_versions\").val());
                                               } else {
                                                 $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val(preferredWhenScreenLoads);
                                               }
                                             } else {
                                               $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val('');
                                             }
                                           } else {
                                             $('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val(preferredWhenScreenLoads);
                                           }
                                         }
                                         ";  
      //Collect the boundaries from a report.
      $boundaryVersions = self::getBoundaryVersionsList($args,$parentCountUnitId,$auth);
      if (!empty($boundaryVersions)) {
        $r = '<label for="boundary_versions">Boundary Versions:</label> ';
        //Put the count unit boundaries into a drop-down and setup reloading of the page when an item is selected.
        $r .= '<select id = "boundary_versions" name="boundary_versions" onchange="location = location = this.options[this.selectedIndex].id;">';
        foreach ($boundaryVersions as $boundaryVersionData) {
          //Need to find the biggest id of the boundaries, as if there isn't a preferred one set, then latest is assumed to be preferred.
          if (empty($maxBoundaryId)||($boundaryVersionData['id'] > $maxBoundaryId))
            $maxBoundaryId = $boundaryVersionData['id'];   
          //Link to boundary page when user clicks on a boundary.
          $linkToBoundaryPage =
                url($_GET['q'], array('absolute' => true)).(variable_get('clean_url', 0) ? '?' : '&').
                'location_id='.$boundaryVersionData['id'].'&parent_id=';
          //We get the parent count unit id in a different way depending on whether we are already viewing a boundary,
          //or whether we are looking at the count unit
          if (!empty($_GET['parent_id'])) {
            $linkToBoundaryPage = $linkToBoundaryPage.$_GET['parent_id'];
          } else {
            $linkToBoundaryPage = $linkToBoundaryPage.$_GET['location_id'];
          }
          //Boundary version options setup as we go around the foreach loop.
          if ($boundaryVersionData['start_date']) {
            $dateStartUnformatted = new DateTime($boundaryVersionData['start_date']);
            $boundaryStartDateToShow = $dateStartUnformatted->format('d/m/Y');
          } else {
            $boundaryStartDateToShow = 'N/K';
          }
          
          if ($boundaryVersionData['end_date']) {
            $dateEndUnformatted = new DateTime($boundaryVersionData['end_date']);
            $boundaryEndDateToShow = $dateEndUnformatted->format('d/m/Y');
          } else {
            $boundaryEndDateToShow = 'N/K';
          }
          
          $r .= '<option value="'.$boundaryVersionData['id'].'" id="'.$linkToBoundaryPage.'">'.$boundaryVersionData['id'].
                ' - Start Date = '.$boundaryStartDateToShow.
                ', End Date = '.$boundaryEndDateToShow.
                '</option>';         
        }
        
        //If preferred boundary is setup, then set the label on screen and put it in a hidden textbox which can be accessed during submission.
        //If preferred boundary is not setup, then we assume the latest is preferred.
        data_entry_helper::$javascript .= "if ($('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val()) {
                                            $(\"#preferred_boundary\").html($('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val());
                                            $(\"#preferred_boundary_hidden\").val($('#locAttr\\\\:".$args['preferred_boundary_attribute_id']."').val());
                                          } else {
                                            $(\"#preferred_boundary\").html($maxBoundaryId);
                                            $(\"#preferred_boundary_hidden\").val($maxBoundaryId);
                                          }\n";

        $r .= "</select>\n";
        /*
        //This button is not required on the form anymore, but just comment out the button code in case it is required again.
        if ($admin_mode)
          $r .= '<form type="post">'.
                  "<input type='submit' id='set-preferred' class='indicia-button'value='Over-write Boundary and Save'>".
                "</form>";
        
        */
        $r .= "<br>";
        $r .= '<label for="preferred_boundary">'.lang::get('LANG_Location_Preferred_Boundary').'</label> ';
        $r .= '<label id="preferred_boundary" name="preferred_boundary"></label>';
        $r .= '<input id="preferred_boundary_hidden" name="preferred_boundary_hidden" type="hidden"/>';  
        $r .= "<br>";

        //If we are viewing a boundary, then default it in the drop-down else default to preferred item if admin or latest item
        //if normal user
        data_entry_helper::$javascript .= "var parentId = '".$_GET['parent_id']."';
                                           if (parentId!=='') {
                                             $(\"#boundary_versions option[value='".$_GET['location_id']."']\").attr('selected', 'selected');
                                           } else {
                                             if (".$admin_mode."==1) {
                                               $(\"#boundary_versions option[value=\"+$('#preferred_boundary_hidden').val()+\"]\").attr('selected', 'selected');
                                             } else {
                                               $(\"#boundary_versions option[value=\"+$maxBoundaryId+\"]\").attr('selected', 'selected');
                                             }
                                           }\n";
        return $r;
      }
    }
  }
  
  /*
   * Control that lets the user overwrite an existing count unit boundary instead of creating a new one
   */
  protected static function get_control_createnewboundary($auth, $args, $tabalias, $options) {
    if (!empty($_GET['location_id'])) {
      $r = '<label id="update-existing-boundary-label" for="update-existing-boundary">Correct Existing Boundary Rather Than Create New One?:</label><input type="checkbox" id="update-existing-boundary" name="update-existing-boundary"><br>';
      $r .= '<label id="set-preferred-on-save-label" for="set-preferred-on-save">Set as preferred on save?:</label><input type="checkbox" id="set-preferred-on-save" name="set-preferred-on-save"><br>';
      return $r;
    }
  }
  
  /* 
   * On the count unit cudi form, the handling of location types is automatic (as it is always a count unit or boundary),
   * but the user selects a annotation type manually. So use the code from the the usual get_control_locationtype in a
   * control_annotationtype control.
   */ 
  protected static function control_annotationtype($auth, $args, $tabalias, $options) {
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
        'label' => lang::get('LANG_Annotation_Type'),
        'fieldname' => 'annotation:location_type_id',
        'lookupValues' => $lookup,
        'blankText' => lang::get('LANG_Blank_Text'),
      ), $options));
    }
  }

  protected static function get_control_locationcomment($auth, $args, $tabalias, $options) {
    if (!empty($_GET['location_id'])) {
      return data_entry_helper::textarea(array_merge(array(
        'fieldname'=>'location:comment',
        'label'=>lang::get('LANG_Comment')
      ), $options)); 
    }
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
      'readAuth' => $auth['read'],
      'caption'=>lang::get('File upload')
    ), $options));
  }
  
  /*
   * When we a location is a Count Unit, some of the data is saved into the Count Unit Boundary Location.
   * So we need to remove the data going into the parent from the boundary.
   */
  protected static function unsetBoundaryValues($values, $args) {
    //For flexibility, the names of the location table fields to store in the boundary are 
    //supplied in a variable. 
    global $fieldsToHoldInCountUnitBoundary;
    //The location attributes to associate with a boundary are supplied on the edit tab for flexibility
    $attributesForBoundary = explode(',',$args['attribute_ids_to_store_on_count_unit_boundary']);
    //Cycle through all the values from the page
    foreach($values as $key => $value) {
      //The key of the values is in the form of "locAttr:5" or "locAttr:5:239" or "location:name" etc,
      //So this means the part of the key that relates to the field is the second part if we explode on :
      $keyElements = explode(':',$key);
      $keyElementToTest = $keyElements[1];
      //Unset the field from the boundary if it doesn't appear in the fields or attributes we
      //have specified to save in the boundary.
      //Name is a special case as it is saved for both parent and boundary
      if (!in_array($keyElementToTest,$fieldsToHoldInCountUnitBoundary)&&
          !in_array($keyElementToTest,$attributesForBoundary)&&
          $key!=='location:name') {    
        unset($values[$key]);
      }
    }
    return $values;
  }
  /*
   * When we a location is a Count Unit, some of the data is saved into the Count Unit Boundary Location.
   * So we need to remove the data going into the Count Unit Boundary from the original parent.
   */
  protected static function unsetParentValues($values, $args) {
    global $fieldsToHoldInCountUnitBoundary;
    foreach($fieldsToHoldInCountUnitBoundary as $fieldToHoldInCountUnitBoundary) {
      //The values keys are the form <table>:<field>, so need to match with location: before the field we specified
      //have specified to store on boundary. If we find a match, then unset it from parent.
      foreach ($values as $field=>$value) {
        if (preg_match('/^location:'.$fieldToHoldInCountUnitBoundary.'/',  $field)) {
          unset($values[$field]);
        }
      }
    }
    $attributesToUnset = explode(',',$args['attribute_ids_to_store_on_count_unit_boundary']);
    foreach($attributesToUnset as $attributeToUnset) {
      foreach ($values as $field=>$value) {
        if (preg_match('/^locAttr:'.$attributeToUnset.'/',  $field)) {
            unset($values[$field]);
          }
      }
    }
    return $values;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    //The Surveys control uses a multiple selection of Surveys along with their dates, so these need preparing for submission sperately.
    self::prepare_multi_survey_field($values, $args);
    $s=self::get_count_unit_and_boundary_submission($values, $args);
    if ($values['annotation:name'])
      $s['subModels'][]=self::get_annotation_submission($values, $args);
    return $s;
  }
  
  /*
   * The Surveys control uses a multiple selection of Surveys along with their dates, so these need preparing for submission sperately.
   */
  protected static function prepare_multi_survey_field(&$values, $args) {
    $existingIdsHolder = array();
    //We need to find any Survey/Date selections which are already saved in the database.
    foreach ($values as $fieldName => $theAttributeId) {
      if (0 === strpos($fieldName, 'selected-survey-existing-')) { 
        if (!empty($values[$fieldName])) {
          $fieldNameParts = explode('-',$fieldName);
          $surveyId = array_pop($fieldNameParts);
          //Create an array where the Survey Id in is the key and the id of the location attribute value this is saved in
          $existingIdsHolder[$surveyId] = $theAttributeId;
        }
      }
    }
    
    $jsonResultsHolder = array();
    foreach ($values as $fieldName => $theValue) {
      if (0 === strpos($fieldName, 'selected-survey-id-')) {
        $fieldNameParts = explode('-',$fieldName);
        $surveyId = array_pop($fieldNameParts);
        //If the user is removing the Surveys/Data Selection item
        if ($values['selected-survey-deleted-'.$theValue]==='true') {          
          //If the removal didn't previously exist in the database then we don't need to submit it at all
          if (empty($existingIdsHolder[$surveyId])) {
            unset($values['selected-survey-deleted-'.$theValue]);
          } else {
            //If we are removing an item that previously existed in the database, then submit it with an empty value to remove it
            $values['locAttr:'.$args['surveys_attribute_id'].':'.$existingIdsHolder[$surveyId]]='';
          }
        //If the user is adding or changing an existing selection
        } else {
          //If the item already exists in the database, and the user is not removing it, then submit it with values from the grid and the location_attribute_value id appended to the $values key
          if (array_key_exists($surveyId,$existingIdsHolder)) {
            $values['locAttr:'.$args['surveys_attribute_id'].':'.$existingIdsHolder[$surveyId]] = json_encode(array($values['selected-survey-id-'.$surveyId],$values['selected-survey-date-'.$surveyId]));
          } else {
            //If the user is adding a new Surveys selection the we don't use the location_attribute_value id
            //As we might be submitting several new items, we use an array which is store in a multi-value attribute setup on the wrehouse
            array_push($jsonResultsHolder, json_encode(array($theValue,$values['selected-survey-date-'.$theValue])));
          }
        }
      }
    }
    //This is used for adding new entries to the surveys grid, we need to use an array here as there might be several new items
    foreach ($jsonResultsHolder as $idx => $theJson) {
      $values['locAttr:'.$args['surveys_attribute_id']][$idx] = $theJson;
    }
  }
  
  /*
   * Setup submission of a versioned count unit boundary
   */ 
  protected static function get_count_unit_and_boundary_submission($values, $args) {
    //When the user clicks the submit button, the screen can be in either annotations mode or boundary mode.
    //The geometry for the item in the current mode is always held in location:boundary_geom.
    //The geometry for the item in the inactive mode is always held in other-layer-boundary-geom-holder.
    //These two are swapped over as the user switches between the two modes.
    //e.g if the user submits in annotation mode, then the annotation geom is held in location:boundary_geom
    //and the boundary geom is held in other-layer-boundary-geom-holder. So in this case when we are submitting the
    //boundary geom, we know it needs to come from $values['other-layer-boundary-geom-holder']
    if ($values['annotations-mode-on']==='yes') {
      $tempBoundaryHolder = $values['location:boundary_geom'];
      $values['location:boundary_geom']=$values['other-layer-boundary-geom-holder'];
    }
    //Get to the Count Unit id to store in the parent submission
    if (!empty($_GET['parent_id']))
      $values['location:id']=$_GET['parent_id'];
    $values['location:location_type_id']=$args['count_unit_location_type_id'];
    //Remove the values we are saving into the boundary location from the parent.
    $valuesForParent = self::unsetParentValues($values,$args);
    //Create the submission for the parent count unit.
    $s = self::create_submission($valuesForParent, $args);  
    //Remove the values we are saving into the parent location from the boundary.
    $values = self::unsetBoundaryValues($values, $args);    
    //If we are updating an existing boundary, then the id to update is in the boundary drop-down
    if ($valuesForParent['update-existing-boundary']==='on') {
      $values['location:id']=$valuesForParent['boundary_versions'];
    } else {
      unset($values['location:id']);
      //We need to create new location_attribute_values associated with the new boundary.
      //Existing attribute values have a key of the form,
      //locAttr:<location_attribute_id>:<location_attribute_value_id>.
      //So we need to remove the location_attribute_value_id from the end of the value key
      //in order to force the system create new attribute values.
      foreach ($values as $key=>$value) {
        $keyParts = explode(':',$key);
        if (!empty($keyParts[2])) {
          $values[$keyParts[0].':'.$keyParts[1]] = $values[$key];
          unset($values[$key]);
        }  
      }
    }
    
    $values['location:location_type_id']=$args['count_unit_boundary_location_type_id'];
    //Write to id 1, as we don't want to overwrite the locations_website submission which is in submodel 0
    if (!empty($values['location:boundary_geom'])) {
      //The Count Unit Id goes into the Count Unit Boundary parent_id field
      $s['subModels'][1]['fkId'] = 'parent_id';    
      $s['subModels'][1]['model'] = self::create_submission($values, $args);
    }
    $values['location:boundary_geom'] = $tempBoundaryHolder;
    return $s;
  }
  
  /*
   * Setup the submission of an annotation.
   */
  protected static function get_annotation_submission($values, $args) {
    //See coding comment next to equivalent code in get_count_unit_and_boundary_submission
    if ($values['annotations-mode-on']!=='yes') {
      $tempBoundaryHolder = $values['location:boundary_geom'];
      $values['location:boundary_geom']=$values['other-layer-boundary-geom-holder'];
    }
    //Don't submit fields that aren't relevant to annotations when submitting the annotation sub-model
    foreach ($values as $field=>$value) {
      if (substr($field, 0, 10 ) !== "annotation" && 
        'boundary_geom' !== substr($field, -strlen('boundary_geom')))
        unset($values[$field]);
    }
    //Submit the annotation fields with the location prefix so we can take advantage of existing code.
    if (!empty($values['annotation:id'])){
      $values['location:id']=$values['annotation:id'];
      unset($values['annotation:id']);
    }
    $values['location:name']=$values['annotation:name'];
    unset($values['annotation:name']);
    $values['location:location_type_id']=$values['annotation:location_type_id'];
    unset($values['annotation:location_type_id']);
    //The parent field of the annotation always comes from the count unit (which is submitted as the parent of the 
    //annotation sub-model
    $s['fkId'] = 'parent_id';    
    $s['model'] = self::create_submission($values, $args); 
    $values['location:boundary_geom'] = $tempBoundaryHolder;
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

