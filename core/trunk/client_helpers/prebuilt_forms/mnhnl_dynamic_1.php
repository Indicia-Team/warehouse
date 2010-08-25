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

class iform_mnhnl_dynamic_1 {

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
          'name'=>'spatial_systems',
          'caption'=>'Allowed Spatial Ref Systems',      
          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
          'type'=>'string',
          'group'=>'Map'
        ),
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
          'name'=>'place_search_control',
          'caption'=>'Place Search Control',
          'description'=>'Should a control for searching for place names on the map be added to the form?',
          'type'=>'boolean',
          'group' => 'User Interface'
        ),
        array(
          'name'=>'location_ctrl',
          'caption'=>'Location Control Type',
          'description'=>'The type of control that will be available to select a location.',
          'type'=>'select',
          'options' => array(
		    'none' => 'None',
            'location_autocomplete' => 'Autocomplete',
            'location_select' => 'Select'
          ),
          'group'=>'User Interface'
        ),
        array(
          'name'=>'survey_id',
          'caption'=>'Survey ID',
          'description'=>'The Indicia ID of the survey that data will be posted into.',
          'type'=>'int'
        ),
        array(
          'name'=>'list_id',
          'caption'=>'Initial Species List ID',
          'description'=>'The Indicia ID for the species list that species can be selected from. This list is pre-populated '.
              'into the grid.',
          'type'=>'int'
        ),
        array(
          'name'=>'extra_list_id',
          'caption'=>'Extra Species List ID',
          'description'=>'The Indicia ID for the species list that species can be selected from. This list is available for additional '.
              'taxa being added to the grid.',
          'type'=>'int',
          'required'=>false
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
    global $user;
    $logged_in = $user->uid>0;
    $r = '';

    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $svcUrl = data_entry_helper::$base_url.'/index.php/services';

    $mode = 0; // default mode : display survey selector
    			// mode 1: display new sample
    			// mode 2: display existing sample
    $loadID = null;
    $displayThisOcc = true; // when populating from the DB rather than POST we have to be
    						            // careful with selection object, as geom in wrong format.
    if ($_POST) {
    	if(!is_null(data_entry_helper::$entity_to_load)){
			  $mode = 2; // errors with new sample, entity poulated with post, so display this data.
    	} // else valid save, so go back to gridview: default mode 0
    } else {
  		if (array_key_exists('sample_id', $_GET)){
		    $mode = 2;
		    $loadID = $_GET['sample_id'];
      } else if (array_key_exists('newSample', $_GET)){
        $mode = 1;
        data_entry_helper::$entity_to_load = array();
      } // else default to mode 0
    }
    
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
      // get the CMS User ID attribute so we can filter the grid to this user
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
      $r .= data_entry_helper::report_grid(array(
        'id' => 'samples-grid',
        'dataSource' => 'reports_for_prebuilt_forms/simple_sample_list_1',
        'mode' => 'report',
        'readAuth' => $auth['read'],
        'columns' => array(
          array('fieldname' => 'location_name', 'display' => lang::get('location_name')),
          array('fieldname' => 'entered_sref', 'display' => lang::get('entered_sref')),
          array('fieldname' => 'num_occurrences', 'display' => lang::get('num_occurrences')),
          array('fieldname' => 'completed', 'display' => lang::get('completed')),
          array('display' => 'Actions', 'actions' => array(
            array('caption' => 'Edit', 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{id}')),
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
      $r .= '<form><input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'"></form>';
      return $r;
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
      // Can't cache these as data may have just changed
      data_entry_helper::$entity_to_load['occurrence:record_status']='I';
      $url = $svcUrl.'/data/sample/'.$loadID;
      $url .= "?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entity = json_decode(curl_exec($session), true);
      // Attributes should be loaded by get_attributes.
      data_entry_helper::$entity_to_load = array();
      foreach($entity[0] as $key => $value){
        data_entry_helper::$entity_to_load['sample:'.$key] = $value;
      }
      $url = $svcUrl.'/data/occurrence';
      $url .= "?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&sample_id=".$loadID."&deleted=FALSE";
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entities = json_decode(curl_exec($session), true);
      foreach($entities as $entity){
        data_entry_helper::$entity_to_load['occurrence:record_status']=$entity['record_status'];
        data_entry_helper::$entity_to_load['sc:'.$entity['taxa_taxon_list_id'].':'.$entity['id'].':present'] = true;
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
    $attributeHeadings = self::get_attribute_headings($attributes);
    if (!in_array('place', $attributeHeadings)) {
      array_splice($attributeHeadings, 0, 0, array('place'));
    }
    if (!in_array('species', $attributeHeadings)) {
      array_splice($attributeHeadings, 0, 0, array('species'));
    }
    $r .= "<div id=\"controls\">\n";
    // Output the dynamic tab headers
    if ($args['interface']!='one_page') {
      $r .= "<ul>\n";
      foreach ($attributeHeadings as $heading) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($heading));
        $tabtitle = lang::get("LANG_Tab_$alias");
        if ($tabtitle=="LANG_Tab_$alias") {
          // if no translation provided, we'll just use the standard heading
          $tabtitle = $heading;
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
    foreach ($attributeHeadings as $heading) {
      // get a machine readable alias for the heading
      $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($heading));
      $r .= '<div id="'.$alias.'">'."\n";
      if ($pageIdx==0)
        // output the hidden inputs on the first tab
        $r .= $hiddens;
      // Check if there is a translation for the instructions for this tab. If so, put it on the tab.
      $instruct = lang::get("LANG_Tab_Instructions_$alias");
      if ($instruct!="LANG_Tab_Instructions_$alias") 
        $r .= '<p class="page-notice ui-state-highlight ui-corner-all">'.$instruct."</p>";
      if (method_exists('iform_mnhnl_dynamic_1', "get_fixed_controls_$alias")) {
        // dynamically call a method to add controls to this page, if there are non dynamic ones.
        $method = "get_fixed_controls_$alias";
        $r .= self::$method($auth, $args);
      }
      $r .= self::get_attribute_html($attributes, $defAttrOptions, $heading);
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>($pageIdx==0 ? 'first' : ($pageIdx==count($heading)-1) ? 'last' : 'middle')
        ));
      } elseif ($pageIdx==count($attributeHeadings)-1)
        // last part of a non wizard interface must insert a save button
        $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" />\n";

      $pageIdx++;
      $r .= "</div>\n";
    }
        
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
    //  TODO image upload - not sure how to do this as images are attached to occurrences, and occurrences
//  are embedded in the species list.
//    $r .= "<label for='occurrence:image'>".lang::get('LANG_Image_Label')."</label>\n".
//        data_entry_helper::image_upload('occurrence:image');
    
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page'=>'last'
      ));
    }
    $r .= "</div>\n"; 
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
            	for (var i=0;i<data.length;i++)
				{
	      			if(data[i].centroid_geom){
						feature = parser.read(data[i].centroid_geom);
						centre = feature.geometry.getCentroid();
						centrefeature = new OpenLayers.Feature.Vector(centre, {}, {label: data[i].name});
						locationLayer.addFeatures([feature, centrefeature]); 
					}
					if(data[i].boundary_geom){
						feature = parser.read(data[i].boundary_geom);
						feature.style = {strokeColor: \"Blue\",
    	                	strokeWidth: 2,
  							label: (data[i].centroid_geom ? \"\" : data[i].name)};
						locationLayer.addFeatures([feature]);
 					}
    				locationLayer.map.zoomToExtent(locationLayer.getDataExtent());
  				}
			}
		});
  }
};
jQuery('#imp-location').unbind('change');
jQuery('#imp-location').change(function(){
	locationChange(this);
});
// upload location & sref initial values into map.
jQuery('#imp-location').change();
jQuery('#imp-sref').change();

";
	return $r;

  }
  
  private static function get_attribute_headings(&$attributes) {
    $r = array();
    foreach($attributes as &$attribute) {
      // Assign any ungrouped attributes to a block called Other Information 
      if (empty($attribute['outer_structure_block'])) 
        $attribute['outer_structure_block']='Other Information';
      if (!in_array($attribute['outer_structure_block'], $r))
        $r[] = $attribute['outer_structure_block'];
    }
    return $r;
  }
  
  private static function get_fixed_controls_species($auth, $args) {
    global $indicia_templates;
    $indicia_templates ['taxon_label'] = '<div class="biota"><span class="nobreak sci binomial"><em>{taxon}</em></span> {authority}</div>';
    $r = '';
    $species_list_args=array(
        'listId'=>$args['list_id'],
        'label'=>lang::get('occurrence:taxa_taxon_list_id'),
        'columns'=>1,
        'view'=>'detail',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id']
    );
    if ($args['extra_list_id']) $species_list_args['lookupListId']=$args['extra_list_id'];
    $r .= data_entry_helper::species_checklist($species_list_args);
    $r .= data_entry_helper::textarea(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('comment')
    ));
    return $r; 
  }
  
  private static function get_fixed_controls_place($auth, $args) {
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }    
    $r .= data_entry_helper::sref_and_system(array(
      'label' => lang::get('LANG_SRef_Label'),
      'systems' => $systems
    ));
    $location_list_args=array(
        'label'=>lang::get('LANG_Location_Label'),
        'view'=>'detail',
        'extraParams'=>array_merge(array('view'=>'detail', 'orderby'=>'name'), $auth['read'])
    );
    if ($args['location_ctrl']!='none')
      $r .= call_user_func(array('data_entry_helper', $args['location_ctrl']), $location_list_args);
    if ($args['place_search_control'])
      $r .= data_entry_helper::georeference_lookup(iform_map_get_georef_options($args));
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['layers'][] = 'locationLayer';
    $olOptions = iform_map_get_ol_options($args);
    $r .= data_entry_helper::map_panel($options, $olOptions);
    return $r;
  }
  
  private static function get_fixed_controls_otherinformation($auth, $args) {
    $r .= data_entry_helper::date_picker(array(
        'label'=>lang::get('LANG_Date'),
        'fieldname'=>'sample:date',
		'default' => isset($args['defaults']['sample:date']) ? $args['defaults']['sample:date'] : ''
    ));
    if (!isset($args['defaults']['occurrence:record_status'])) {
      $values = array('I', 'C'); // not initially doing V=Verified
      $r .= '<label for="occurrence:record_status">'.lang::get('LANG_Record_Status_Label')."</label>\n";
      $r .= '<select id="occurrence:record_status" name="occurrence:record_status">';
      foreach($values as $value){
        $r .= '<option value="'.$value.'"';
        if(isset(data_entry_helper::$entity_to_load['occurrence:record_status'])){
        if(data_entry_helper::$entity_to_load['occurrence:record_status'] == $value){
          $r .= ' selected="selected"';
        }
        }
        $r .= '>'.lang::get('LANG_Record_Status_'.$value).'</option>';
      }
      $r .= "</select><br/>\n";
    } else  {
      $r .= '<input type="hidden" name="occurrence:record_status" value="'.$args['defaults']['occurrence:record_status'].'"/>';
    }
  	return $r;
  }
  
  private static function get_attribute_html($attributes, $defAttrOptions, $outerFilter=null) {
  	$lastOuterBlock='';
    $lastInnerBlock='';
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
    $sampleMod = data_entry_helper::build_sample_occurrences_list_submission($values);
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
}