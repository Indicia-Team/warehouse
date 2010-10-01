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

require_once('includes/map.php');
require_once('includes/language_utils.php');

class iform_mnhnl_dynamic_1 {

  // A list of the taxon ids we are loading
  private static $occurrenceIds = array();

	/* TODO
	 *  Photo upload: not sure how to do this as images are attached to occurrences, and occurrences
	 *  	are embedded in the species list.
	 * 	Survey List
	 * 		Put in "loading" message functionality.
	 *  	Add a map and put samples on it, clickable
	 *  
	 *  Sort out {common}.
	 * 
	 * The report paging will not be converted to use LIMIT & OFFSET because we want the full list returned so 
	 * we can display all the occurrences on the map.
	 * When displaying transects, we should display children locations as well as parent.
	 */
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {    
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      array(
        array(
          'name'=>'interface',
          'caption'=>'Interface Style Option',
          'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
              'wizard pages or having all controls on a single page.',
          'type'=>'select',
          'options' => array(
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page'
          ),
          'group' => 'User Interface'
        ),
        array(
          'name'=>'structure',
          'caption'=>'Form Structure',
          'description'=>'Define the structure of the form. Each component goes on a new line and is nested inside the previous component where appropriate. The following types of '.
            "component can be specified. <br/>".
            "<strong>=tab name=</strong> is used to specify the name of a tab. <br/>".
            "<strong>=*=</strong> indicates a placeholder for putting any custom attribute tabs not defined in this form structure. <br/>".
            "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
                "&nbsp;&nbsp;<strong>[species]</strong> - a species grid or input control<br/>".
				"&nbsp;&nbsp;<strong>[species_attributes]</strong> - any custom attributes for the occurrence, if not using the grid<br/>".
				"&nbsp;&nbsp;<strong>[date]</strong><br/>".
				"&nbsp;&nbsp;<strong>[map]</strong><br/>".
				"&nbsp;&nbsp;<strong>[spatial reference]</strong><br/>".
				"&nbsp;&nbsp;<strong>[location name]</strong><br/>".
				"&nbsp;&nbsp;<strong>[location autocomplete</strong>br/>".
				"&nbsp;&nbsp;<strong>[location select]</strong><br/>".
				"&nbsp;&nbsp;<strong>[place search]</strong><br/>".
				"&nbsp;&nbsp;<strong>[record status]</strong><br/>".
				"&nbsp;&nbsp;<strong>[sample comment]</strong>. <br/>".
            "<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to the control. The options ".
			"available depend on the control. For example @label=Abundance would set the untranslated label of a control to Abundance. ".
			"Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ".
			"classes to the control such as control-width-3). <br/>".
			"<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab.<br/>".
            "<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of the site.?",
          'type'=>'textarea',
          'default' => "=Species=\r\n".
              "?Please enter the species you saw and any other information about them.?\r\n".
              "[species]\r\n".
              "[species attributes]\r\n".
              "[*]\r\n".
              "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map.?\r\n".
              "[place search]\r\n".
              "[spatial reference]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[date]\r\n".
              "[sample comment]\r\n".
              "[*]\r\n".
              "=*=",
          'group' => 'User Interface'
        ),
        array(
          'name'=>'no_grid',
          'caption'=>'Skip initial grid of data',
          'description'=>'If checked, then when initially loading the form the data entry form is immediately displayed, as opposed to '.
              'the default of displaying a grid of the user\'s data which they can add to.',
          'type'=>'boolean',
          'default' => false,
          'group' => 'User Interface'
        ),    
        array(
          'name' => 'grid_report',
          'caption' => 'Grid Report',
          'description' => 'Name of the report to use to populate the grid for selecting existing data from. The report must return a sample_id '.
              'field for linking to the data entry form. As a starting point, try reports_for_prebuilt_forms/simple_occurrence_list_1 or '.
              'reports_for_prebuilt_forms/simple_sample_list_1 for a list of occurrences or samples respectively.',
          'type'=>'string',
          'group' => 'User Interface',
          'default' => 'reports_for_prebuilt_forms/simple_sample_list_1'
        ),
        array(
          'name'=>'save_button_below_all_pages',
          'caption'=>'Save button below all pages?',
          'description'=>'Should the save button be present below all the pages (checked), or should it be only on the last page (unchecked)? '.
              'Only applies to the Tabs interface style.',
          'type'=>'boolean',
          'default' => false,
          'group' => 'User Interface'
        ),        
        array(
          'name'=>'multiple_occurrence_mode',
          'caption'=>'Single or multiple occurrences per sample',
          'description'=>'Method of data entry, via a grid of occurrences, one occurrence at a time, or allow the user to choose.',
          'type'=>'select',
          'options' => array(
            'single' => 'Only allow entry of one occurrence at a time',
            'multi' => 'Only allow entry of multiple occurrences using a grid',
            'either' => 'Allow the user to choose single or multiple occurrence data entry.'
          ),
          'default' => 'multi',
          'group' => 'Species'
        ),
        array(
          'name'=>'species_ctrl',
          'caption'=>'Single Species Selection Control Type',
          'description'=>'The type of control that will be available to select a single species.',
          'type'=>'select',
          'options' => array(
            'autocomplete' => 'Autocomplete',
            'select' => 'Select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group',
            'treeview' => 'Treeview',
            'tree_browser' => 'Tree browser'
          ),
          'default' => 'autocomplete',
          'group'=>'Species'
        ),
		array(
          'name'=>'occurrence_comment',
          'caption'=>'Occurrence Comment',
          'description'=>'Should an input box be present for a comment against each occurrence?',
          'type'=>'boolean',
		  'default'=>false,
		  'group'=>'Species'
		),
		array(
          'name'=>'occurrence_images',
          'caption'=>'Occurrence Images',
          'description'=>'Should occurrences allow images to be uploaded?',
          'type'=>'boolean',
		  'default'=>false,
		  'group'=>'Species'
		),
        array(
          'name'=>'list_id',
          'caption'=>'Initial Species List ID',
          'description'=>'The Indicia ID for the species list that species can be selected from. This list is pre-populated '.
              'into the grid when doing grid based data entry.',
          'type'=>'int',          
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'extra_list_id',
          'caption'=>'Extra Species List ID',
          'description'=>'The Indicia ID for the second species list that species can be selected from. This list is available for additional '.
              'taxa being added to the grid when doing grid based data entry.',
          'type'=>'int',
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'species_names_filter',
          'caption'=>'Species Names Filter',
          'description'=>'Select the filter to apply to the species names which are available to choose from.',
          'type'=>'select',          
          'options' => array(
            'all' => 'All names are available',
            'language' => 'Only allow selection of species using common names in the user\'s language',
            'preferred' => 'Only allow selection of species using names which are flagged as preferred'            
          ),
          'default' => 'autocomplete',
          'group'=>'Species'
        ),
        array(
          'name'=>'spatial_systems',
          'caption'=>'Allowed Spatial Ref Systems',      
          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
          'type'=>'string',
          'group'=>'Map'
        ),
        array(
          'name'=>'survey_id',
          'caption'=>'Survey ID',
          'description'=>'The Indicia ID of the survey that data will be posted into.',
          'type'=>'int'
        ),        
		    array(
          'name'=>'defaults',
          'caption'=>'Default Values',
          'description'=>'Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, '.
              'the fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. occurrence.record_status. '.
              'For date fields, use today to dynamically default to today\'s date. NOTE, currently only supports occurrence:record_status and '.
              'sample:date but will be extended in future.',
          'type'=>'textarea',
          'default'=>'occurrence:record_status=C'
        )		
      )
    );
    return $retVal;
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Dynamic 1 - form that dynamically generates a species checklist card from the attributes '.
        'defined for the selected survey';  
  }
  
/**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
    self::parse_defaults($args);
    self::getArgDefaults($args);
    global $user;
    $logged_in = $user->uid>0;
    $r = '';

    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $svcUrl = data_entry_helper::$base_url.'/index.php/services';

    $mode = 0; // default mode : display grid of existing data
    			// mode 1: display new sample
    			// mode 2: display existing sample
    $loadID = null;
    if ($_POST) {
    	if(!is_null(data_entry_helper::$entity_to_load)){
	      $mode = 2; // errors with new sample, entity populated with post, so display this data.
    	} // else valid save, so go back to gridview: default mode 0
    } elseif (array_key_exists('sample_id', $_GET)){
      $mode = 2;
      $loadID = $_GET['sample_id'];
    } else if (array_key_exists('newSample', $_GET) || (isset($args['no_grid']) && $args['no_grid'])){
      $mode = 1;
      data_entry_helper::$entity_to_load = array();
	} // else default to mode 0
    
    $attributes = data_entry_helper::getAttributes(array(
    	'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    ));

    ///////////////////////////////////////////////////////////////////
    // default mode 0 : display grid of the samples to add a new one 
    // or edit an existing one.
    ///////////////////////////////////////////////////////////////////
    if($mode == 0) {      
      return self::getSampleListGrid($args, $node, $auth, $attributes);
    }
    ///////////////////////////////////////////////////////////////////
    
        data_entry_helper::$javascript .= "
// Create vector layers: one to display the location onto, and another for the occurrence list
// the default edit layer is used for the occurrences themselves
locStyleMap = new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    fillColor: \"Green\",
                    strokeColor: \"Black\",
                    fillOpacity: 0.3,
                    strokeWidth: 1
                  })
  });
locationLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_Location_Layer")."\",
                                    {styleMap: locStyleMap});
";
    if($loadID){      
      $url = $svcUrl.'/data/sample/'.$loadID;
      $url .= "?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entity = json_decode(curl_exec($session), true);
      // Build a list of the sample data.
      data_entry_helper::$entity_to_load = array();
      foreach($entity[0] as $key => $value){
        data_entry_helper::$entity_to_load['sample:'.$key] = $value;
      }
	  data_entry_helper::$entity_to_load['sample:geom'] = ''; // value received from db is not WKT, which is assumed by all the code.
      data_entry_helper::$entity_to_load['sample:date'] = data_entry_helper::$entity_to_load['sample:date_start']; // bit of a bodge to get around vague dates.
    }	
    $defAttrOptions = array('extraParams'=>$auth['read']);
        
//    $r .= "<h1>MODE = ".$mode."</h1>";
//    $r .= "<h2>readOnly = ".$readOnly."</h2>";
    
    $r = "<form method=\"post\" id=\"entry_form\">\n";
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $hiddens = $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n".
          "<input type=\"hidden\" id=\"sample:survey_id\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\" />\n";
    if(array_key_exists('sample:id', data_entry_helper::$entity_to_load)){
      $hiddens .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";	
    }
    if(array_key_exists('occurrence:id', data_entry_helper::$entity_to_load)){
      $hiddens .= "<input type=\"hidden\" id=\"occurrence:id\" name=\"occurrence:id\" value=\"".data_entry_helper::$entity_to_load['occurrence:id']."\" />\n";	
    }
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = explode("\r\n", $args['structure']);
    if (!in_array('[record status]', $arr)) {
      $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C'; 
      $hiddens .= "<input type=\"hidden\" id=\"occurrence:record_status\" name=\"occurrence:record_status\" value=\"$value\" />\n";	
    }
    
    // request automatic JS validation
    data_entry_helper::enable_validation('entry_form');
    $attributes = data_entry_helper::getAttributes(array(
    	'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    ));
    // If logged in, output some hidden data about the user
    foreach($attributes as &$attribute) {
      if (strcasecmp($attribute['caption'], 'cms user id')==0) {
        if ($logged_in) $attribute['value'] = $user->uid;
        $attribute['handled']=true; // user id attribute is never displayed
      }
      elseif (strcasecmp($attribute['caption'], 'cms username')==0) {
        if ($logged_in) $attribute['value'] = $user->name;
        $attribute['handled']=true; // username attribute is never displayed
      } elseif (strcasecmp($attribute['caption'], 'email')==0) {
        if ($logged_in) {
          $attribute['value'] = $user->mail;
          $attribute['handled']=true; // email attribute is displayed unless logged in
        }
      } elseif ((strcasecmp($attribute['caption'], 'first name')==0 || 
          strcasecmp($attribute['caption'], 'last name')==0 || 
          strcasecmp($attribute['caption'], 'surname')==0) && $logged_in)
        $attribute['handled']=true; // name attributes are displayed unless logged in
      
      if (isset($attribute['value'])) {
        $hiddens .= '<input type="hidden" name="'.$attribute['fieldname'].'" value="'.$attribute['value'].'" />'."\n";
      }
    }
    $customAttributeTabs = self::get_attribute_tabs($attributes);
    $tabs = self::get_all_tabs($args['structure'], $customAttributeTabs);
    $r .= "<div id=\"controls\">\n";
    // Output the dynamic tab headers
    if ($args['interface']!='one_page') {
      $r .= "<ul>\n";
      foreach ($tabs as $tab=>$tabContent) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $tabtitle = lang::get("LANG_Tab_$alias");
        if ($tabtitle=="LANG_Tab_$alias") {
          // if no translation provided, we'll just use the standard heading
          $tabtitle = $tab;
        }
        $r .= '  <li><a href="#'.$alias.'"><span>'.$tabtitle."</span></a></li>\n";
      }
      $r .= "</ul>\n";
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface']
      ));
    }
    // Output the dynamic tab content
    $pageIdx = 0;
    foreach ($tabs as $tab=>$tabContent) {
      // get a machine readable alias for the heading
      $tabalias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $r .= '<div id="'.$tabalias.'">'."\n";
      if ($pageIdx==0)
        // output the hidden inputs on the first tab
        $r .= $hiddens;
      // Now output the content of the tab. Use a for loop, not each, so we can treat several rows as one object
      for ($i = 0; $i < count($tabContent); $i++) {
        $component = $tabContent[$i];
        if (preg_match('/\A\?[^¬]*\?\z/', trim($component))===1) {
          // Component surrounded by ? so represents a help text
          $helpText = substr(trim($component), 1, -1);
          $r .= '<p class="page-notice ui-state-highlight ui-corner-all">'.lang::get($helpText)."</p>";
        } elseif (preg_match('/\A\[[^¬]*\]\z/', trim($component))===1) {
          // Component surrounded by [] so represents a control
          $method = 'get_control_'.preg_replace('/[^a-zA-Z0-9]/', '', strtolower($component));
          // Anything following the component that starts with @ is an option to pass to the control
          $options = array();
          while ($i < count($tabContent)-1 && substr($tabContent[$i+1],0,1)=='@') {
            $i++;
            $option = explode('=',substr($tabContent[$i],1));
            $options[$option[0]]=$option[1];
          }
          if (method_exists('iform_mnhnl_dynamic_1', $method)) 
            $r .= self::$method($auth, $args, $tabalias, $options);
          elseif (trim($component)==='[*]')
            $r .= self::get_attribute_html($attributes, $defAttrOptions, $tab);
          else          
            $r .= "The form structure includes a control called $component which is not recognised.<br/>";
        }      
      }
      // Add any buttons required at the bottom of the tab
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>$pageIdx===0 ? 'first' : (($pageIdx==count($tabs)-1) ? 'last' : 'middle')
        ));        
      } elseif ($pageIdx==count($tabs)-1 && !($args['interface']=='tabs' && $args['save_button_below_all_pages']))
        // last part of a non wizard interface must insert a save button, unless it is tabbed interface with save button beneath all pages 
        $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" />\n";      
      $pageIdx++;
      $r .= "</div>\n";      
    }
    $r .= "</div>\n";
    if ($args['interface']=='tabs' && $args['save_button_below_all_pages']) {
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" />\n";
    }
    if(!empty(data_entry_helper::$validation_errors)){
		$r .= data_entry_helper::dump_remaining_errors();
    }   
    $r .= "</form>";
        
    // may need to keep following code for location change functionality
	data_entry_helper::$onload_javascript .= "
    
locationChange = function(obj){
	locationLayer.destroyFeatures();
	if(obj.value != ''){
		jQuery.getJSON(\"".$svcUrl."\" + \"/data/location/\"+obj.value +
			\"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."\" +
			\"&callback=?\", function(data) {
        if (data.length>0) {
          var parser = new OpenLayers.Format.WKT();
          for (var i=0;i<data.length;i++) {
            if(data[i].centroid_geom){
              feature = parser.read(data[i].centroid_geom);
              centre = feature.geometry.getCentroid();
              centrefeature = new OpenLayers.Feature.Vector(centre, {}, {label: data[i].name});
              locationLayer.addFeatures([feature, centrefeature]); 
            }
            if (data[i].boundary_geom){
              feature = parser.read(data[i].boundary_geom);
              feature.style = {strokeColor: \"Blue\",
                  strokeWidth: 2,
                  label: (data[i].centroid_geom ? \"\" : data[i].name)};
                  locationLayer.addFeatures([feature]);
            }
          }
          var extent=locationLayer.getDataExtent();
          if (extent!==null) {
            locationLayer.map.zoomToExtent(extent);
          }
        }
		  }
    );
  }
};
jQuery('#imp-location').unbind('change');
jQuery('#imp-location').change(function(){
	locationChange(this);
});

var updatePlaceTabHandler = function(event, ui) { 
  if (ui.panel.id=='place') {
    // upload location & sref initial values into map.
    jQuery('#imp-location').change();
    jQuery('#imp-sref').change();
    jQuery('#controls').unbind('tabsshow', updatePlaceTabHandler);
  }
}
jQuery('#controls').bind('tabsshow', updatePlaceTabHandler);

";
	return $r;

  }
  
  /**
   * Finds the list of tab names that are going to be required by the custom attributes.
   */
  private static function get_attribute_tabs(&$attributes) {
    $r = array();
    foreach($attributes as &$attribute) {
      // Assign any ungrouped attributes to a block called Other Information 
      if (empty($attribute['outer_structure_block'])) 
        $attribute['outer_structure_block']='Other Information';
      if (!array_key_exists($attribute['outer_structure_block'], $r))
        // Create a tab for this structure block and mark it with [*] so the content goes in
        $r[$attribute['outer_structure_block']] = array("[*]");
    }
    return $r;
  }
  
  /**
   * Finds the list of all tab names that are going to be required, either by the form
   * structure, or by custom attributes.
   */
  private static function get_all_tabs($structure, $attrTabs) {
    $structureArr = explode("\r\n", $structure);
    $structureTabs = array();
    foreach ($structureArr as $component) {
      if (preg_match('/^=[A-Za-z0-9 \*]+=$/', trim($component), $matches)===1) {
        $currentTab = substr($matches[0], 1, -1);
        $structureTabs[$currentTab] = array();
      } else {
        if (!isset($currentTab)) 
          throw new Exception('The form structure parameter must start with a tab title, e.g. =Species=');
        $structureTabs[$currentTab][] = $component;
      }
    }
    // If any additional tabs are required by attributes, add them to the position marked by a dummy tab named [*].
    // First get rid of any tabs already in the structure
    foreach ($attrTabs as $tab => $tabContent) {
      // case -insensitive check if attribute tab already in form structure
      if (in_array(strtolower($tab), array_map('strtolower', array_keys($structureTabs))))
        unset($attrTabs[$tab]);
    }
    // Now we have a list of form structure tabs, with the position of the $attrTabs marked by *. So join it all together.
    // Maybe there is a better way to do this?
    $allTabs = array();
    foreach($structureTabs as $tab => $tabContent) {
      if ($tab=='*') 
        $allTabs += $attrTabs;
      else
        $allTabs[$tab] = $tabContent;
    }
    return $allTabs;
  }
  
  /** 
   * Get the map control.
   */
  private static function get_control_map($auth, $args, $tabalias, $options) {
    $options = array_merge(
	  iform_map_get_map_options($args, $auth['read']),
	  $options
	);
    $options['layers'][] = 'locationLayer';
    $options['tabDiv'] = $tabalias;
    $olOptions = iform_map_get_ol_options($args);
    return data_entry_helper::map_panel($options, $olOptions);
  }
  
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  private static function get_control_species($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    
    $extraParams = $auth['read'] + array('view' => 'detail');
    if ($args['species_names_filter']=='preferred') {
      $extraParams += array('preferred' => 't');
    }
    if ($args['species_names_filter']=='language') {
      $extraParams += array('language_iso' => iform_lang_iso_639_2($user->lang));
    }  
    if (self::getGridMode($args)) {      
      // multiple species being input via a grid
      $indicia_templates ['taxon_label'] = '<div class="biota"><span class="nobreak sci binomial"><em>{taxon}</em></span> {authority}</div>';
      $species_list_args=array_merge(array(
          'listId'=>$args['list_id'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'survey_id'=>$args['survey_id'],
		  'occurrenceComment'=>$args['occurrence_comment'],
		  'occurrenceImages'=>$args['occurrence_images']
      ), $options);
      if ($args['extra_list_id']) $species_list_args['lookupListId']=$args['extra_list_id'];
      // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
      // then output the grid control
      return '<input type="hidden" value="true" name="gridmode" />'.
          data_entry_helper::species_checklist($species_list_args);
    }
    else {      
      // A single species entry control of some kind
      if (count(self::$occurrenceIds)==1)
        // output a hidden input to contain the occurrence id
        $r .= '<input type="hidden" name="occurrence:id" value="'.self::$occurrenceIds[0].'" />'."\n";
      if ($args['extra_list_id']=='')
        $extraParams['taxon_list_id'] = $args['list_id'];
      // @todo At the moment the autocomplete control does not support 2 lists. So use just the extra list. Should 
      // update to support 2 lists.
      elseif ($args['species_ctrl']=='autocomplete')
        $extraParams['taxon_list_id'] = $args['extra_list_id'];
      else
        $extraParams['taxon_list_id'] = array($args['list_id'], $args['extra_list_id']);
      $species_list_args=array_merge(array(
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'fieldname'=>'occurrence:taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,          
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams
      ), $options);
      if ($args['species_ctrl']=='tree_browser') {
        // change the node template to include images
        global $indicia_templates;
        $indicia_templates['tree_browser_node']='<div>'.
            '<img src="'.data_entry_helper::$base_url.'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
            '<span>{caption}</span>';
      }
      // Dynamically generate the species selection control required.
     return call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args);
    }
  }
  
    
  /**
   * Get the sample comment control
   */
  private static function get_control_samplecomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Overall Comment')
    ), $options)); 
  }
  
  /**
   * Get the block of custom attributes at the species (occurrence) level
   */
  private static function get_control_speciesattributes($auth, $args, $tabalias, $options) {
    if (!self::getGridMode($args)) {  
      // Add any dynamically generated controls
      $attrArgs = array(
         'valuetable'=>'occurrence_attribute_value',
         'attrtable'=>'occurrence_attribute',
         'key'=>'occurrence_id',
         'fieldprefix'=>'occAttr',
         'extraParams'=>$auth['read'],
         'survey_id'=>$args['survey_id']
      );
      if (count(self::$occurrenceIds)==1) {
        // if we have a single occurrence Id to load, use it to get attribute values
        $attrArgs['id'] = self::$occurrenceIds[0];
      }
      $attributes = data_entry_helper::getAttributes($attrArgs);
      $defAttrOptions = array('extraParams'=>$auth['read']);
      $r = self::get_attribute_html($attributes, $defAttrOptions);
	  if ($args['occurrence_comment'])
        $r .= data_entry_helper::textarea(array(
          'fieldname'=>'occurrence:comment',
          'label'=>lang::get('Record Comment')
        )); 
      if ($args['occurrence_images'])
        $r .= data_entry_helper::file_box(array(
          'table'=>'occurrence_image',
          'label'=>lang::get('Upload your photos'),
		      'tabDiv'=>$tabalias
        ));
	  return $r;
    } else 
      // in grid mode the attributes are embedded in the grid.
      return '';
  }
  
  /** 
   * Get the date control.
   */
  private static function get_control_date($auth, $args, $tabalias, $options) {
    return data_entry_helper::date_picker(array_merge(array(
      'label'=>lang::get('LANG_Date'),
      'fieldname'=>'sample:date',
		  'default' => isset($args['defaults']['sample:date']) ? $args['defaults']['sample:date'] : ''
    ), $options));
  }
  
  /** 
   * Get the spatial reference control.
   */
  private static function get_control_spatialreference($auth, $args, $tabalias, $options) {
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }    
    return data_entry_helper::sref_and_system(array_merge(array(
      'label' => lang::get('LANG_SRef_Label'),
      'systems' => $systems
    ), $options));
  }
  
  /** 
   * Get the location control as an autocomplete.
   */
  private static function get_control_locationautocomplete($auth, $args, $tabalias, $options) {
    $location_list_args=array_merge(array(
        'label'=>lang::get('LANG_Location_Label'),
        'view'=>'detail',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $auth['read'])
    ), $options);
    return data_entry_helper::location_autocomplete($location_list_args);
  }
  
  /** 
   * Get the location control as a select dropdown.
   */
  private static function get_control_locationselect($auth, $args, $tabalias, $options) {
    $location_list_args=array_merge(array(
        'label'=>lang::get('LANG_Location_Label'),
        'view'=>'detail',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $auth['read'])
    ), $options);
    return data_entry_helper::location_select($location_list_args);
  }
  
  /** 
   * Get the location name control.
   */
  private static function get_control_locationname($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'sample:location_name',
      'class' => 'control-width-5'
    ), $options));
  }
  
  /** 
   * Get the location search control.
   */
  private static function get_control_placesearch($auth, $args, $tabalias, $options) {
    return data_entry_helper::georeference_lookup(array_merge(
	  iform_map_get_georef_options($args),
	  $options
	));
  }
  
  /**
   * Get the control for the record status.
   */
  private static function get_control_recordstatus($auth, $args) {    
    $default = isset(data_entry_helper::$entity_to_load['occurrence:record_status']) ? 
        data_entry_helper::$entity_to_load['occurrence:record_status'] :
        isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C';
    $values = array('I', 'C'); // not initially doing V=Verified
    $r = '<label for="occurrence:record_status">'.lang::get('LANG_Record_Status_Label')."</label>\n";
    $r .= '<select id="occurrence:record_status" name="occurrence:record_status">';
    foreach($values as $value){
      $r .= '<option value="'.$value.'"';
      if ($value == $default){
        $r .= ' selected="selected"';
      }
      $r .= '>'.lang::get('LANG_Record_Status_'.$value).'</option>';
    }
    $r .= "</select><br/>\n";
  	return $r;
  }
  
  private static function get_attribute_html($attributes, $defAttrOptions, $outerFilter=null) {
  	$lastOuterBlock='';
    $lastInnerBlock='';
    $r = '';
    foreach ($attributes as $attribute) {
      // Apply filter to only output 1 block at a time. Also hide controls that have already been handled.
      if (($outerFilter===null || strcasecmp($outerFilter,$attribute['outer_structure_block'])==0) && !isset($attribute['handled'])) {
        
        if (empty($outerFilter) && $lastOuterBlock!=$attribute['outer_structure_block']) {
          if (!empty($lastInnerBlock)) {
            $r .= '</fieldset>';
          }
          if (!empty($lastOuterBlock)) {
            $r .= '</fieldset>';
          }
          if (!empty($attribute['outer_structure_block']))
            $r .= '<fieldset><legend>'.$attribute['outer_structure_block'].'</legend>';
          if (!empty($attribute['inner_structure_block']))
            $r .= '<fieldset><legend>'.$attribute['inner_structure_block'].'</legend>';
        }
        elseif ($lastInnerBlock!=$attribute['inner_structure_block']) {
          if (!empty($lastInnerBlock)) {
            $r .= '</fieldset>';
          }
          if (!empty($attribute['inner_structure_block']))
            $r .= '<fieldset><legend>'.$attribute['inner_structure_block'].'</legend>';
        }
        $lastInnerBlock=$attribute['inner_structure_block'];
        $lastOuterBlock=$attribute['outer_structure_block'];
        $r .= data_entry_helper::outputAttribute($attribute, $defAttrOptions);
        $attribute['handled']=true;
      }
    }
    if (!empty($lastInnerBlock)) {
      $r .= '</fieldset>';
    }
    if (!empty($lastOuterBlock) && strcasecmp($outerFilter,$lastOuterBlock)!==0) {
      $r .= '</fieldset>';
    }
    return $r;
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
    if (isset($values['gridmode']))
      $sampleMod = data_entry_helper::build_sample_occurrences_list_submission($values);
    else
      $sampleMod = data_entry_helper::build_sample_occurrence_submission($values);
    return($sampleMod);
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_collaborators_1.css');
  }
  
  /**
   * Convert the unstructured textarea of default values into a structured array.
   */
  private static function parse_defaults(&$args) {
    $result=array();
    if (isset($args['defaults'])) {
      $defaults = explode("\n", $args['defaults']);
      foreach($defaults as $default) {
        $tokens = explode('=', $default);
        $result[trim($tokens[0])] = trim($tokens[1]);
      }	  
    }  
    $args['defaults']=$result;
  }
  
  /**
   * Returns true if this form should be displaying a multipled occurrence entry grid.
   */
  private static function getGridMode($args) {
    // if loading an existing sample and we are allowed to display a grid or single species selector
    if ($args['multiple_occurrence_mode']=='either') {
      // Either we are in grid mode because we were instructed to externally, or because the form is reloading
      // after a validation failure with a hidden input indicating grid mode.
      return isset($_GET['gridmode']) || 
          isset(data_entry_helper::$entity_to_load['gridmode']) ||
          count(self::$occurrenceIds)>1;
    } else
      return 
          // a form saved using a previous version might not have this setting, so default to grid mode=true
          (!isset($args['multiple_occurrence_mode'])) ||
          // Are we fixed in grid mode?
          $args['multiple_occurrence_mode']=='multi';
  }
  
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  private static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attrId;
        break;
      }
    }
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    if (!isset($userIdAttr)) {
      return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can '.
          'be tagged against the user.');
    }
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/simple_sample_list_1';
    $r = data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => array(
        array('display' => 'Actions', 'actions' => array(
          array('caption' => 'Edit', 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')),
        ))
      ),
      'itemsPerPage' =>10,
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>$user->uid
      )
    ));	
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= '</form>';
    return $r;
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  private function getArgDefaults(&$args) {
     if (!isset($args['structure']) || empty($args['structure']))
      $args['structure'] = "=Species=\r\n".
              "?Please enter the species you saw and any other information about them.?\r\n".
              "[species]\r\n".
              "[species attributes]\r\n".
              "[*]\r\n".
              "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map.?\r\n".
              "[place search]\r\n".
              "[spatial reference]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[date]\r\n".
              "[sample comment]\r\n".
              "[*]\r\n".
              "=*=";
    if (!isset($args['occurrence_comment']))
	  $args['occurrence_comment'] == false; 
	if (!isset($args['occurrence_images']))
	  $args['occurrence_images'] == false; 
  }

}