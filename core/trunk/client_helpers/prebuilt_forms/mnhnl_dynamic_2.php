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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 */

/* Development Stream: TBD
 * 
 * Future possibles:
 * add map to main grid, Populate with positions of supersamples?
 * There are various language strings which setting up properly.
 * 
 * Testing:
 * confirm that if using location records it actually works.
 */
require_once('mnhnl_dynamic_1.php');

class iform_mnhnl_dynamic_2 extends iform_mnhnl_dynamic_1 {

  protected static $svcUrl;
  protected static $currentUrl;
  protected static $gridmode;
  protected static $node;
  
   /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_mnhnl_dynamic_2_definition() {
    return array(
      'title'=>'MNHNL Dynamic 2 - dynamically generated form for entry of a series of ad-hoc occurrences',
      'category' => 'General Purpose Data Entry Forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description'=>'A data entry form that is dynamically generated from the survey\'s attributes. The form lets the user create '.
          'a series of occurrences by clicking on the map to set the location of each one then entering details. Data entered in a '.
          'single session in this way is joined using a simple sample hierarchy (so the top level sample encapsulates all data for the '.
          'session.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    $parentVal = parent::get_parameters();
    $retVal = array();
    foreach($parentVal as $param){
      if($param['name'] == 'grid_report'){
        $param['description'] = 'Name of the report to use to populate the grid for selecting existing data from. The report must return a sample_id '.
              'field for linking to the data entry form.';
        $param['default'] = 'reports_for_prebuilt_forms/mnhnl_dynamic_2_supersamples';
      }
      if($param['name'] != 'structure' && $param['name'] != 'no_grid')
        $retVal[] = $param;
      // Note the includeLocTools is left in in case any child forms use it 
    }
    $retVal = array_merge(
      $retVal,
      array(
        array(
          'name'=>'grid_rows',
          'caption'=>'Number of Rows display in supersample grid',
          'description'=>'Number of Rows display in supersample grid at one time.',
          'type'=>'int',
          'default' => 12,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'supersample_structure',
          'caption'=>'Form Structure for supersample',
          'description'=>'Define the structure of the supersample form. Each component goes on a new line and is nested inside the previous component where appropriate. The following types of '.
            "component can be specified. <br/>".
            "<strong>=tab name=</strong> is used to specify the name of a tab. (Alpha-numeric characters only)<br/>".
            "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
				"&nbsp;&nbsp;<strong>[date]</strong><br/>".
				"&nbsp;&nbsp;<strong>[map]</strong> - if you wish to see the subsamples associated with this supersample, add the option @layers=[\"SSLayer\"] to the following line<br/>".
				"&nbsp;&nbsp;<strong>[spatial reference]</strong><br/>".
				"&nbsp;&nbsp;<strong>[location name]</strong><br/>".
				"&nbsp;&nbsp;<strong>[location autocomplete]</strong><br/>".
				"&nbsp;&nbsp;<strong>[location select]</strong><br/>".
				"&nbsp;&nbsp;<strong>[place search]</strong><br/>".
				"&nbsp;&nbsp;<strong>[recorder names]</strong><br/>".
				"&nbsp;&nbsp;<strong>[sample comment]</strong>. <br/>".
				"&nbsp;&nbsp;<strong>[sub sample grid]</strong>. This requires the following options (see below): (1) grid_report - Name of the report to use to populate the grid for selecting existing data from; and (2) grid_rows - the number of rows to be displayed in a single page of the grid. The report must take a parent_id, and return a sample id field for linking to the data entry form.<br/>".
              "<strong>@option=value</strong> on the line(s) following any control allows you to override (or set) one of the options passed to the control. The options ".
        "available depend on the control. For example @label=Abundance would set the untranslated label of a control to Abundance. Where the ".
        "option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @occAttrClasses=[\"class1\",\"class2\"]. ".
        "Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
        "classes to the control such as control-width-3). <br/>".
        "<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab.<br/>".
            "<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of the site.?<br/><br/>".
              "The <strong>=*=</strong> placeholder is not valid for this form.",
          'type'=>'textarea',
          'default' => "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map.?\r\n".
              "[place search]\r\n".
              "[spatial reference]\r\n".
              "[map]\r\n".
              "@layers=[\"SSLayer\"]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[date]\r\n".
              "[sample comment]\r\n".
              "[*]\r\n".
              "=Occurrences=\r\n".
              "?Please enter subsample and occurrence data. If you have made any changes to the supersample record, save those first, as they will be lost otherwise when you either add a new or edit an existing subsample.?\r\n".
              "[sub sample grid]\r\n".
              "@grid_report=reports_for_prebuilt_forms/mnhnl_dynamic_2_subsamples\r\n".
              "@grid_rows=15\r\n".
              "[*]\r\n",
          'group' => 'User Interface'
        ),
        array(
          'name'=>'occurrence_structure',
          'caption'=>'Form Structure for sample/occurrence pair',
          'description'=>'Define the structure of the sample/occurrence form. Each component goes on a new line and is nested inside the previous component where appropriate. The following types of '.
            "component can be specified, in addition to those given under the Form Structure for supersample. <br/>".
            "<strong>[control name]</strong>: the following additional controls may be used: <br/>".
				"&nbsp;&nbsp;<strong>[map]</strong> - if you wish to see the supersample associated with this subsample, add the option @layers=[\"SSLayer\"] to the following line<br/>".
                "&nbsp;&nbsp;<strong>[species]</strong> - a species grid or input control<br/>".
				"&nbsp;&nbsp;<strong>[species_attributes]</strong> - any custom attributes for the occurrence, if not using the grid<br/>".
				"&nbsp;&nbsp;<strong>[record status]</strong><br/>".
        		"Some controls don't make sense being used in this context of this part of the form, though they are available: these include <strong>[location name]</strong>, <strong>[location autocomplete]</strong>, <strong>[location select]</strong> as the expectation is that the concept of a named location is based within the supersample, and the positioning of the individual occurrences would be based on selecting a point on the map associated with the supersample location.<br/>".
        		"Note as well that the date will be copied down from the parent supersample, and that care should be taken 1) when using any tab called Other Information, as this the default tab for any unassigned attributes, and 2) to avoid using tab names to hold attributes which are on both super and subsample records, as the attributes will then appear on both.",
          'type'=>'textarea',
          'default' => "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or click on the map.?\r\n".
              "[spatial reference]\r\n".
              "[map]\r\n".
              "@layers=[\"SSLayer\"]\r\n".
              "[*]\r\n".
              "=Species=\r\n".
              "?Please enter the species you saw and any other information about them.?\r\n".
              "[species]\r\n".
              "[species attributes]\r\n".
              "[*]\r\n",
          'group' => 'User Interface'
        ),
        array(
          'name'=>'occurrence_interface',
          'caption'=>'Interface Style Option for sample/occurrence pair',
          'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
              'wizard pages or having all controls on a single page.',
          'type'=>'select',
          'options' => array(
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page'
          ),
          'group' => 'User Interface'
        )
        
      )
    );
    return $retVal;
  }
  
/**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
    // Navigation Rules: URL contents:
    // 1) GET						: Views Grid. From there we can EDIT an existing supersample (2) or add a new one (3)
    // 2) GET ?supersample_id=<x>	: Views supersample with id=<x>: From here can cancel (1), POST changes to the supersample (4), view an existing occurrence (5) ar create a new occurrence (6)
    // 3) GET ?newsupersample		: Views a form which allows the creation of a new supersample. No occurrences can be added until saved: occurrence grid not displayed.
    //								  From here can save the supersample details (4), or cancel - back to (1).
    // 4) POST						: Supersample POST. sample:parent_id is not included in the POST. POST includes only sample or possibly location based information. No occurrence details. sample:parent_id is forced to null.
    //								  Navigation option: 1) Displays the same as (2), but has to fetch the most recent supersample ID for the user for a new one.
    //													 2) Displays the same as (1)
  	// 5) GET ?sample_id=<x>		: Views the sample with id=<x>. From here can cancel (2), POST changes to the sample (7).
    // 6) POST [newsample]			: Views a form which allows the creation of a new sample and its associated occurrence(s). The POST must contain the newsample - parent_id.
    //								  Displays a blank (5) -> From here can POST changes to the sample/occurrence details (7), or cancel - back to (2).
    // 7) POST						: Sample POST. sample:parent_id is included in the POST. POST includes samples and occurrence information. No location.
    //								  Displays the same as (5), but has to fetch the most recent sample ID for the user for a new one.
    //								  Navigation option: 1) Displays the same as (5), but has to fetch the most recent sample ID for the user for a new one.
    //													 2) Displays the same as (2), determined by posted sample:parent_id
  	define ("MODE_GRID", 1);
    define ("MODE_EXISTING_SUPERSAMPLE", 2);
  	define ("MODE_NEW_SUPERSAMPLE", 3);
  	define ("MODE_POST_SUPERSAMPLE", 4);
    define ("MODE_EXISTING_OCCURRENCE", 5); // Occurrences are actually sample/occurrence pair
  	define ("MODE_NEW_OCCURRENCE", 6); 
  	define ("MODE_POST_OCCURRENCE", 7); 
  	self::parse_defaults($args);
    self::getArgDefaults($args);
    self::$gridmode = false;
    self::$node = $node;
    global $user;
    $logged_in = $user->uid>0;
    $r = '';
    if (!$logged_in) {
      // Return a login link that takes you back to this form when done.
      // TBD this language string needs sorting
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    
    // Get authorisation tokens to update and read from the Warehouse.
    self::$auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    self::$svcUrl = data_entry_helper::$base_url.'/index.php/services';
    $mode = MODE_GRID; // default mode
    if ($_POST) {
      if(!array_key_exists('website_id', $_POST)) { // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrupt the data by accident
        if(function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin') && array_key_exists('mnhnld2', $_POST)){
          iform_loctools_deletelocations($node);
          foreach($_POST as $key => $value){
            $parts = explode(':', $key);
            iform_loctools_insertlocation($node, $parts[2], $parts[1]);
          }
        }
      } else if(array_key_exists('sample:parent_id', $_POST))
        $mode = MODE_POST_OCCURRENCE;
      else if(array_key_exists('newsample_parent_id', $_POST))
        $mode = MODE_NEW_OCCURRENCE;
      else
        $mode = MODE_POST_SUPERSAMPLE;
      if(isset($_POST['gridmode']))
        self::$gridmode=true;
    } else if (array_key_exists('newSuperSample', $_GET)){
      $mode = MODE_NEW_SUPERSAMPLE;
    } else if (array_key_exists('sample_id', $_GET)){
      $mode = MODE_EXISTING_OCCURRENCE;
      $loadedSampleId = $_GET['sample_id'];
    } else if (array_key_exists('supersample_id', $_GET)){ // if done this way around because the report grid adds the supersample id to url when picking the subsample
      $mode = MODE_EXISTING_SUPERSAMPLE;
    }
    if(($args['multiple_occurrence_mode']) == 'multi')
        self::$gridmode=true;
    
//    if ($mode!=MODE_EXISTING && array_key_exists('newSample', $_GET)){
//      $mode = MODE_NEW_SAMPLE;
//      data_entry_helper::$entity_to_load = array();
//    } // else default to mode MODE_GRID
    self::$mode = $mode;
    self::$currentUrl = url('node/'.($node->nid));
    // default mode MODE_GRID : display grid of the samples to add a new one 
    // or edit an existing one.
    return call_user_func(array(get_called_class(), 'get_form_mode_'.$mode),$args, $node);
  }
  
  protected static function get_form_mode_1($args, $node){ // MODE_GRID
    // TDB
    global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>self::$auth['read']
       ,'survey_id'=>$args['survey_id']
    ));
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attrId;
        break;
      }
    }
    if (!isset($userIdAttr)) {
      return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can '.
          'be tagged against the user.');
    }
    $r = '';
    if (method_exists(get_called_class(), 'getHeaderHTML')) $r .= call_user_func(array(get_called_class(), 'getHeaderHTML'), true, $args);
    $tabs = array('#sampleList'=>lang::get('LANG_Main_Samples_Tab'));
    if($args['includeLocTools'] && function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin')){
      $tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
    }
    if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
      $extraTabs = call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), false, self::$auth['read'], $args, $attributes);
      if(is_array($extraTabs)) $tabs = $tabs + $extraTabs;
    }
    if(count($tabs) > 1){
      $r .= "<div id=\"controls\">".(data_entry_helper::enable_tabs(array('divId'=>'controls','active'=>'#sampleList')))."<div id=\"temp\"></div>";
      $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
    }
    $r .= "<div id=\"sampleList\">";
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => self::$auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getSupersampleReportActions')),
      'itemsPerPage' =>$args['grid_rows'],
      'autoParamsForm' => true,
      'extraParams' => array('survey_id'=>$args['survey_id'])
    ));	
    $r .= '<form><input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSuperSample')).'\'"></form></div>';
    if($args['includeLocTools'] && function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess($node,'admin')){
      $r .= '
  <div id="setLocations">
    <form method="post">
      <input type="hidden" id="mnhnld2" name="mnhnld2" value="mnhnld2" /><table border="1"><tr><td></td>';
      $url = self::$svcUrl.'/data/location?mode=json&view=detail&auth_token='.self::$auth['read']['auth_token']."&nonce=".self::$auth['read']["nonce"]."&parent_id=NULL&orderby=name".(isset($args['loctoolsLocTypeID'])&&$args['loctoolsLocTypeID']<>''?'&location_type_id='.$args['loctoolsLocTypeID']:'');
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entities = json_decode(curl_exec($session), true);
      $userlist = iform_loctools_listusers($node);
      foreach($userlist as $uid => $a_user){ $r .= '<td>'.$a_user->name.'</td>'; }
      $r .= "</tr>";
      if(!empty($entities)){
        foreach($entities as $entity){
          if(!$entity["parent_id"]){ // only assign parent locations.
            $r .= "<tr><td>".$entity["name"]."</td>";
            $defaultuserids = iform_loctools_getusers($node, $entity["id"]);
            foreach($userlist as $uid => $a_user){
                $r .= '<td><input type="checkbox" name="location:'.$entity["id"].':'.$uid.(in_array($uid, $defaultuserids) ? '" checked="checked"' : '"').'></td>';
            }
            $r .= "</tr>";
      }}}
      $r .= "</table>
      <input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Location_Allocations')."\" />
    </form>
  </div>";
    }
    if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
      $r .= call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), true, self::$auth['read'], $args, $attributes);
    }
    if(count($tabs)>1){ // close tabs div if present
      $r .= "</div>";
    }
    if (method_exists(get_called_class(), 'getTrailerHTML')) $r .= call_user_func(array(get_called_class(), 'getTrailerHTML'), true, $args);
    return $r;
  }
  
  protected static function get_form_mode_2($args, $node){ // MODE_EXISTING_SUPERSAMPLE
    $loadedSampleId = $_GET['supersample_id'];
    data_entry_helper::load_existing_record(self::$auth['read'], 'sample', $loadedSampleId);
    return call_user_func(array(get_called_class(), 'get_form_supersample'),$args, $node);
  }
  
  protected static function get_form_mode_3($args, $node){ // MODE_NEW_SUPERSAMPLE
    data_entry_helper::$entity_to_load=array();
    return call_user_func(array(get_called_class(), 'get_form_supersample'),$args, $node);
  }
  
  protected static function get_form_mode_4($args, $node){ // MODE_POST_SUPERSAMPLE
  	if(!is_null(data_entry_helper::$entity_to_load))
      // errors with new super sample sample, entity poulated with post, so display this data.
      return call_user_func(array(get_called_class(), 'get_form_supersample'),$args, $node);
    // else valid save
    if(array_key_exists('navigate:grid', $_POST))
      return call_user_func(array(get_called_class(), 'get_form_mode_1'),$args, $node);
    // Otherwise redisplay current sample
    if(array_key_exists('sample:id', $_POST)){ // modified an existing sample
      $loadedSampleId = $_POST['sample:id'];
      data_entry_helper::load_existing_record(self::$auth['read'], 'sample', $loadedSampleId);
      return call_user_func(array(get_called_class(), 'get_form_supersample'),$args, $node);
    } // else created a new one so need most recent one created for this user.
    // first work out the most recent CMS user sample attribute modified by this user.
    $url =  self::$svcUrl."/data/sample_attribute_value";
    $url .= "?mode=json&view=$view&auth_token=".self::$auth['read']['auth_token']."&nonce=".self::$auth['read']['nonce'];
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $entity = json_decode(curl_exec($session), true);    
    if (isset($entity['error'])) throw new Exception($entity['error']);
    $loadedSampleId = $entity['sample_id'];
    data_entry_helper::load_existing_record(self::$auth['read'], 'sample', $loadedSampleId);
    return call_user_func(array(get_called_class(), 'get_form_supersample'),$args, $node);
  }

  protected static function get_form_mode_5($args, $node){ // MODE_EXISTING_OCCURRENCE
    $loadedSampleId = $_GET['sample_id'];
    data_entry_helper::load_existing_record(self::$auth['read'], 'sample', $loadedSampleId);
    return call_user_func(array(get_called_class(), 'get_form_sampleoccurrence'),$args, $node);
  }
  
  protected static function get_form_mode_6($args, $node){ // MODE_NEW_OCCURRENCE
    data_entry_helper::$entity_to_load=array('sample:parent_id' => $_POST['newsample_parent_id'],
    										 'sample:date' => $_POST['newsample_date']);
    return call_user_func(array(get_called_class(), 'get_form_sampleoccurrence'),$args, $node);
      }
  
  protected static function get_form_mode_7($args, $node){ // MODE_POST_OCCURRENCE
    if(!is_null(data_entry_helper::$entity_to_load))
      // errors with new sample+occurrence combination, entity poulated with post, so display this data.
      return call_user_func(array(get_called_class(), 'get_form_sampleoccurrence'),$args, $node);
    // else valid save
    if(array_key_exists('navigate:newoccurrence', $_POST)){ // display a new sample/occurrence combination, with the same parent & date
      data_entry_helper::$entity_to_load=array('sample:parent_id' => $_POST['sample:parent_id'],
      										   'sample:date' => $_POST['sample:date']);
      return call_user_func(array(get_called_class(), 'get_form_sampleoccurrence'),$args, $node);
    }
    $loadedSampleId = $_POST['sample:parent_id'];
    data_entry_helper::load_existing_record(self::$auth['read'], 'sample', $loadedSampleId);
    return call_user_func(array(get_called_class(), 'get_form_supersample'),$args, $node);
  }
  
  
  protected static function get_form_supersample($args, $node){  // attributes must be fetched after the entity to load is filled in - this is because the id gets filled in then!
    global $user;
    $logged_in = $user->uid>0;
    $attributes = data_entry_helper::getAttributes(array(
       'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>self::$auth['read']
       ,'survey_id'=>$args['survey_id']
    ));
    
    $r = "<form method=\"post\" id=\"entry_form\" action=\"".self::$currentUrl."\">\n";
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $hiddens = self::$auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $hiddens .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";	
    }
    $arr = explode("\r\n", $args['supersample_structure']);
    
    // request automatic JS validation
    if (!isset($args['clientSideValidation']) || $args['clientSideValidation'])
      data_entry_helper::enable_validation('entry_form');
    // If logged in, output some hidden data about the user
    foreach($attributes as &$attribute) {
      if (strcasecmp($attribute['caption'], 'cms user id')==0) {
        if ($logged_in) $attribute['value'] = $user->uid;
        $attribute['handled']=true; // user id attribute is never displayed
      }
      elseif (strcasecmp($attribute['caption'], 'cms username')==0) {
        if ($logged_in) $attribute['value'] = $user->name;
        $attribute['handled']=true; // username attribute is never displayed
      }
      elseif (strcasecmp($attribute['caption'], 'email')==0) {
        if ($logged_in) {
          if (!isset($args['emailShow']) || $args['emailShow'] != true)
          {// email attribute is not displayed
            $attribute['value'] = $user->mail;
            $attribute['handled']=true; 
          }
          else
            $attribute['default'] = $user->mail;
        }
      }
      elseif ((strcasecmp($attribute['caption'], 'first name')==0 ||
          strcasecmp($attribute['caption'], 'last name')==0 ||
          strcasecmp($attribute['caption'], 'surname')==0) && $logged_in) {
        if ($args['nameShow'] != true) {  
          // name attributes are not displayed because we have the users login
          $attribute['handled']=true;
        }
      }
      // If we have a value for one of the user login attributes then we need to output this value. BUT, for existing data
      // we must not overwrite the user who created the record.
      if (isset($attribute['value']) && self::$mode == MODE_NEW_SUPERSAMPLE) {
        $hiddens .= '<input type="hidden" name="'.$attribute['fieldname'].'" value="'.$attribute['value'].'" />'."\n";
      }
    }
    self::set_attribute_default_block($attributes);
    $tabs = self::get_all_tabs($args['supersample_structure'], array());
    $r .= "<div id=\"controls\">\n";
    // Build a list of the tabs that actually have content
    $tabHtml = self::get_tab_html($tabs, self::$auth, $args, $attributes, $hiddens);
    // Output the dynamic tab headers
    $headerOptions = array('tabs'=>array());
    foreach ($tabHtml as $tab=>$tabContent) {
      $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $tabtitle = lang::get("LANG_Tab_$alias");
      if ($tabtitle=="LANG_Tab_$alias") {
        // if no translation provided, we'll just use the standard heading
        $tabtitle = $tab;
      }
      $headerOptions['tabs']['#'.$alias] = $tabtitle; 
    }
    if ($args['interface']!='one_page') {
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    
    // Output the dynamic tab content
    $pageIdx = 0;
    foreach ($tabHtml as $tab=>$tabContent) {
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $r .= '<div id="'.$tabalias.'">'."\n";
      // For wizard include the tab title as a header.
      if ($args['interface']!='tabs') {
        $r .= '<h1>'.$headerOptions['tabs']['#'.$tabalias].'</h1>';        
      }
      $r .= $tabContent;    
      // Add any buttons required at the bottom of the tab
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'classRedisplay' => 'ui-widget-content ui-state-default ui-corner-all indicia-button tab-submit-redisplay',
          'captionSaveRedisplay' => 'save and redisplay',
          'divId'=>'controls',
          'page'=>$pageIdx===0 ? 'first' : (($pageIdx==count($tabs)-1) ? 'last' : 'middle')
        ));        
      } elseif ($pageIdx==count($tabs)-1 && !($args['interface']=='tabs' && $args['save_button_below_all_pages'])) {
        // last part of a non wizard interface must insert a save button, unless it is tabbed interface with save button beneath all pages 
        $r .= "<input type=\"submit\" name=\"navigate:grid\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" />\n";      
        $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Redisplay')."\" />\n";      
        $r .= "<input type=\"button\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Cancel')."\" onclick=\"window.location.href='".self::$currentUrl."'\" >\n";
      }
      $pageIdx++;
      $r .= "</div>\n";      
    }
    $r .= "</div>\n";
    if ($args['interface']=='tabs' && $args['save_button_below_all_pages']) {
      $r .= "<input type=\"submit\" name=\"navigate:grid\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" />\n";
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Redisplay')."\" />\n";      
      $r .= "<input type=\"button\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Cancel')."\" onclick=\"window.location.href='".self::$currentUrl."'\" >\n";
    }
    if(!empty(data_entry_helper::$validation_errors)){
      $r .= data_entry_helper::dump_remaining_errors();
    }   
    $r .= "</form>";
    // Because the SSLayer may be defined at map creation (as defined by the user interface argument, we have to define the
    // layer even if it is not used - ie no subsamples yet (eg when creating a new supersample)
    data_entry_helper::$javascript .= "
// Create a vector layer to display the subsample locations
// the default edit layer is used for the supersample
SSStyleMap = new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    fillColor: \"Green\",
                    strokeColor: \"Black\",
                    fillOpacity: 0.2,
                    strokeWidth: 1
                  })});
SSLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_Subsample_Layer")."\", {styleMap: SSStyleMap});
SSparser = new OpenLayers.Format.WKT();
";
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $url = self::$svcUrl."/data/sample?parent_id=".data_entry_helper::$entity_to_load['sample:id'];
      $url .= "&mode=json&view=detail&auth_token=".self::$auth['read']['auth_token']."&nonce=".self::$auth['read']['nonce'];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entity = json_decode(curl_exec($session), true);    
      if (isset($entity['error'])) throw new Exception($entity['error']);
      foreach($entity as $SS){
        data_entry_helper::$javascript .= "
SSfeature = SSparser.read('".$SS['wkt']."');
SSLayer.addFeatures([SSfeature]);
";
      }
    }
    return $r;
  }

  protected static function get_form_sampleoccurrence($args, $node){  // attributes must be fetched after the entity to load is filled in - this is because the id gets filled in then!
    $cancelUrl = self::$currentUrl;
    $cancelUrl .= (strpos($cancelUrl, '?')===false) ? '?' : '&';
    $cancelUrl .= "supersample_id=".data_entry_helper::$entity_to_load['sample:parent_id'];
    $args['interface'] = $args['occurrence_interface'];
    
    $attributes = data_entry_helper::getAttributes(array(
       'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>self::$auth['read']
       ,'survey_id'=>$args['survey_id']
    ));
    if (self::$mode == MODE_EXISTING_OCCURRENCE && self::$gridmode == false && isset(data_entry_helper::$entity_to_load['sample:id'])){
    	$cloneEntity = data_entry_helper::$entity_to_load;
        $occList = data_entry_helper::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'], self::$auth['read'], $args['occurrence_images']);
        foreach($occList as $id => $taxon)
          self::$occurrenceIds[] = $id;
        if(count(self::$occurrenceIds)>1){
          self::$gridmode = true;
          data_entry_helper::$entity_to_load = $cloneEntity;
        }
    }
    // Make sure the form action points back to this page
    $r = "<form method=\"post\" id=\"entry_form\" action=\"".self::$currentUrl."\">\n"; 
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $hiddens = self::$auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n".
          "<input type=\"hidden\" id=\"parent_id\" name=\"sample:parent_id\" value=\"".data_entry_helper::$entity_to_load['sample:parent_id']."\" />\n".
          "<input type=\"hidden\" id=\"date\" name=\"sample:date\" value=\"".data_entry_helper::$entity_to_load['sample:date']."\" />\n";
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $hiddens .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";	
    }
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = explode("\r\n", $args['structure']);
    if (!in_array('[record status]', $arr)) {
      $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C'; 
      $hiddens .= "<input type=\"hidden\" id=\"occurrence:record_status\" name=\"occurrence:record_status\" value=\"$value\" />\n";	
    }
    
    // request automatic JS validation
    if (!isset($args['clientSideValidation']) || $args['clientSideValidation'])
      data_entry_helper::enable_validation('entry_form');
    self::set_attribute_default_block($attributes);
    $tabs = self::get_all_tabs($args['occurrence_structure'], array());
    $r .= "<div id=\"controls\">\n";
    // Build a list of the tabs that actually have content
    $tabHtml = self::get_tab_html($tabs, self::$auth, $args, $attributes, $hiddens);
    // Output the dynamic tab headers
    $headerOptions = array('tabs'=>array());
    foreach ($tabHtml as $tab=>$tabContent) {
      $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $tabtitle = lang::get("LANG_Tab_$alias");
      if ($tabtitle=="LANG_Tab_$alias") {
        // if no translation provided, we'll just use the standard heading
        $tabtitle = $tab;
      }
      $headerOptions['tabs']['#'.$alias] = $tabtitle;        
    }
    if ($args['occurrence_interface']!='one_page') {
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['occurrence_interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    
    // Output the dynamic tab content
    $pageIdx = 0;
    foreach ($tabHtml as $tab=>$tabContent) {
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $r .= '<div id="'.$tabalias.'">'."\n";
      // For wizard include the tab title as a header.
      if ($args['occurrence_interface']!='tabs') {
        $r .= '<h1>'.$headerOptions['tabs']['#'.$tabalias].'</h1>';        
      }
      $r .= $tabContent;    
      // Add any buttons required at the bottom of the tab
      if ($args['occurrence_interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'classRedisplay' => 'ui-widget-content ui-state-default ui-corner-all indicia-button tab-submit-redisplay',
          'captionSaveRedisplay' => 'save and redisplay',
          'divId'=>'controls',
          'page'=>$pageIdx===0 ? 'first' : (($pageIdx==count($tabs)-1) ? 'last' : 'middle')
        ));        
      } elseif ($pageIdx==count($tabs)-1 && !($args['occurrence_interface']=='tabs' && $args['save_button_below_all_pages'])) {
        // last part of a non wizard interface must insert a save button, unless it is tabbed interface with save button beneath all pages 
        $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" />\n";      
        $r .= "<input type=\"submit\" name=\"navigate:newoccurrence\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_and_New')."\" />\n";
        $r .= "<input type=\"button\" class=\"ui-state-default ui-corner-all\"value=\"".lang::get('LANG_Cancel')."\" onclick=\"window.location.href='".$cancelUrl."'\" >\n";
      }
      $pageIdx++;
      $r .= "</div>\n";      
    }
    $r .= "</div>\n";
    if ($args['occurrence_interface']=='tabs' && $args['save_button_below_all_pages']) {
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" />\n";
      $r .= "<input type=\"submit\" name=\"navigate:newoccurrence\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_and_New')."\" />\n";
      $r .= "<input type=\"button\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Cancel')."\" onclick=\"window.location.href='".$cancelUrl."'\" >\n";
    }
    if(!empty(data_entry_helper::$validation_errors)){
      $r .= data_entry_helper::dump_remaining_errors();
    }   
    $r .= "</form>";
    $url = self::$svcUrl."/data/sample/".data_entry_helper::$entity_to_load['sample:parent_id'];
    $url .= "?mode=json&view=detail&auth_token=".self::$auth['read']['auth_token']."&nonce=".self::$auth['read']['nonce'];
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $entity = json_decode(curl_exec($session), true);
    if (isset($entity['error'])) throw new Exception($entity['error']);
    if(empty($entity[0]['location_id'])){
      $wkt = $entity[0]['wkt'];
    } else {
      $url = self::$svcUrl."/data/location/".$entity[0]['location_id']."?mode=json&view=detail&auth_token=".self::$auth['read']['auth_token']."&nonce=".self::$auth['read']['nonce'];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entity = json_decode(curl_exec($session), true);
      if (isset($entity['error'])) throw new Exception($entity['error']);
      $wkt = $entity[0]['boundary_geom'];
    }
    data_entry_helper::$javascript .= "
// Create a vector layer to display the supersample location
// the default edit layer is used for the subsamples
SSStyleMap = new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    fillColor: \"Green\",
                    strokeColor: \"Black\",
                    fillOpacity: 0.2,
                    strokeWidth: 1,
                    pointRadius:6
                  })
  });
ZoomToFeature = function(feature){
  var div = jQuery('#map')[0];
  var bounds=feature.geometry.bounds.clone();
  // extend the boundary to include a buffer, so the map does not zoom too tight.
  var dy = (bounds.top-bounds.bottom) * div.settings.maxZoomBuffer;
  var dx = (bounds.right-bounds.left) * div.settings.maxZoomBuffer;
  bounds.top = bounds.top + dy;
  bounds.bottom = bounds.bottom - dy;
  bounds.right = bounds.right + dx;
  bounds.left = bounds.left - dx;
  if (div.map.getZoomForExtent(bounds) > div.settings.maxZoom) {
    // if showing something small, don't zoom in too far
    div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
  } else {
    // Set the default view to show something triple the size of the grid square
    div.map.zoomToExtent(bounds);
  }
};
SSLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_Supersample_Layer")."\",
                                    {styleMap: SSStyleMap});
SSparser = new OpenLayers.Format.WKT();
SSfeature = SSparser.read('".$wkt."');
SSLayer.addFeatures([SSfeature]);
mapInitialisationHooks.push(function(mapdiv) {
  ZoomToFeature(SSfeature);
});
";
    return $r;
  }
  
  // get_attribute_tabs is not used here: instead we have set_attribute_default_block
  // We can't create dummy tabs for attributes. This is because we could conceivably have attributes attached to both super and sub sample records.
  /**
   * Sets the default tab for custom attributes.
   */
  protected static function set_attribute_default_block(&$attributes) {
    $r = array();
    foreach($attributes as &$attribute) {
      // Assign any ungrouped attributes to a block called Other Information 
      if (empty($attribute['outer_structure_block'])) 
        $attribute['outer_structure_block']='Other Information';
    }
    return $r;
  }
  
  /**
   * Finds the list of all tab names that are going to be required by the form structure.
   * custom attributes are not used, as noted above. Need the dummy argument to match prototype in dynamic 1
   */
  protected static function get_all_tabs($structure, $attrTabs) {
    $structureArr = explode("\r\n", $structure);
    $structureTabs = array();
    foreach ($structureArr as $component) {
      if (preg_match('/^=[A-Za-z0-9 \-]+=$/', trim($component), $matches)===1) {
        $currentTab = substr($matches[0], 1, -1);
        $structureTabs[$currentTab] = array();
      } else {
        if (!isset($currentTab)) 
          throw new Exception('The form structure parameter must start with a tab title, e.g. =Species=');
        $structureTabs[$currentTab][] = $component;
      }
    }
    // We don't use the dummy =*= tab - see comments above
    return $structureTabs;
  }
  
  /**
   * Returns true if this form should be displaying a multipled occurrence entry grid.
   */
  protected static function getGridMode($args) {
    return self::$gridmode;
  }
  
// link_species_popups defined in Dynamic_1
// get_tab_html defined in Dynamic_1

// get_control_map defined in Dynamic_1
// get_control_species defined in Dynamic_1
// get_control_samplecomment defined in Dynamic_1 textarea sample:comment
// get_control_speciesattributes defined in Dynamic_1 
// get_control_date defined in Dynamic_1 date_picker sample:date
// get_control_spatialreference defined in Dynamic_1 sref_and_system
// get_control_locationautocomplete defined in Dynamic_1 location_autocomplete
// get_control_locationselect defined in Dynamic_1 location_select
// get_control_locationname defined in Dynamic_1 textinput sample:location_name
// get_control_placesearch defined in Dynamic_1 georeference
// get_control_recordernames defined in Dynamic_1 textarea sample:recorder_names
// get_control_recordstatus defined in Dynamic_1 select occurrence:record_status
  
  /** 
   * Get the grid of subsamples.
   */
  protected static function get_control_subsamplegrid($auth, $args, $tabalias, $options) {
    if(!array_key_exists('sample:id', data_entry_helper::$entity_to_load) || !data_entry_helper::$entity_to_load['sample:id'])
      return('You have to save a new supersample before you can access aubsamples/occurrences for it.<br/>');
  	$r = "<div id=\"subSampleList\">";
    $r .= data_entry_helper::report_grid(array(
      'id' => 'subsample-grid',
      'dataSource' => $options['grid_report'],
      'mode' => 'report',
      'readAuth' => self::$auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getSubsampleReportActions')),
      'itemsPerPage' =>$options['grid_rows'],
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'],
        'parent_id'=>data_entry_helper::$entity_to_load['sample:id']
      )
    ));	
    if ($args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_SubSample_Single').'" id="new-subsample-button">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_SubSample_Grid').'" id="new-subsample-button-grid">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_SubSample').'" id="new-subsample-button">';    
    }
    
    $r .= "</div>\n";
    data_entry_helper::$onload_javascript .= "
jQuery('#new-subsample-button').click(function(e) {
    var form = jQuery('<form>');
    form.attr('action', '".self::$currentUrl."');
    form.attr('method', 'POST');
    var addParam = function(paramName, paramValue){
         var input = $('<input type=\"hidden\">');
         input.attr({ 'id':     paramName,
                      'name':   paramName,
                      'value':  paramValue });
         form.append(input);
    };
    addParam('newsample_parent_id', ".data_entry_helper::$entity_to_load['sample:id'].");
    addParam('newsample_date', jQuery('[name=sample:date]').val());
    // Submit the form, then remove it from the page
    form.appendTo(document.body);
    form.submit();
    form.remove(); 
});
jQuery('#new-subsample-button-grid').click(function(e) {
    var form = jQuery('<form>');
    form.attr('action', '".self::$currentUrl."');
    form.attr('method', 'POST');
    var addParam = function(paramName, paramValue){
         var input = $('<input type=\"hidden\">');
         input.attr({ 'id':     paramName,
                      'name':   paramName,
                      'value':  paramValue });
         form.append(input);
    };
    addParam('newsample_parent_id', ".data_entry_helper::$entity_to_load['sample:id'].");
    addParam('newsample_date', jQuery('[name=sample:date]').val());
    addParam('gridmode', 'YES');
    // Submit the form, then remove it from the page
    form.appendTo(document.body);
    form.submit();
    form.remove(); 
});";
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
  	if(array_key_exists('newsample_parent_id', $_POST)){
      // $mode = MODE_NEW_OCCURRENCE
      return null;
    }
    if(array_key_exists('sample:parent_id', $_POST)) {
      //  $mode = MODE_POST_OCCURRENCE;
      // Can't call getGridMode in this context as we might not have the $_GET value to indicate grid
      if (isset($values['gridmode']))
        return data_entry_helper::build_sample_occurrences_list_submission($values);
      else
        return data_entry_helper::build_sample_occurrence_submission($values);
    } else {
      //  $mode = MODE_POST_SUPERSAMPLE;
      return submission_builder::build_submission($values, $structure = array('model' => 'sample'));
    }
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_dynamic_2.css');
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected function getArgDefaults(&$args) {
    if (!isset($args['includeLocTools']))
      $args['includeLocTools'] == false; 
  }
  
  protected function getSupersampleReportActions() {
    return array(array('display' => '', 'actions' => 
            array(array('caption' => 'Edit', 'url'=>self::$currentUrl, 'urlParams'=>array('supersample_id'=>'{id}')))));
  }

  protected function getSubsampleReportActions() {
  	return array(array('display' => '', 'actions' => 
            array(array('caption' => 'Edit', 'url'=>self::$currentUrl, 'urlParams'=>array('sample_id'=>'{id}')))));
  }
  
} 