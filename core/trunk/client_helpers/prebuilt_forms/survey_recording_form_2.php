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

class iform_survey_recording_form_2 {

	/* TODO LIST
	 * Features to be fixed before delivery 1.
     * 
     * TODO Force custom attributes to be required: do through indicia front end and then check data in DB:
     *      update scripts to match.
     * TODO Implement map processing
     *  Modify main survey entry so if mod existing location it is displayed on startup as well as on change.
     *    This should display centroid, buffered centroid and boundary geoms. Start of centroid: label 'A', end 'B'
     *  Force custom attributes to be required: do through indicia front end and then check data in DB:
     *      update scripts to match.
     *  Data import of location SHP files.
     *  Sort out which layers are available on which tabs.
     *  If a feature on the occurrence list layer is clicked/hovered, the taxon is displayed.
     *  Add functionality to highlight a feature when a row selected in occurrence list.
     *  Add prompt to confirm when closing a survey.
     * 
     * When a location is chosen, its geometry is displayed and zoomed to.
     * When the survey page is displayed, the selection and occurrence list layers are not displayed.
     * When an existing occurrence is chosen (existing position), its geometry is displayed
     * When the occurence tab is displayed, the location layer and selection layers are displayed, but not the occurrence list layer.
     * When the occurence list tab is displayed, the location layer and occurrence list layers are displayed, but not the selection layer.
     * TODO Internationalise
     * TODO Check error handling in form.
	 *
	 * TODO ENHANCEMENT1: Sort out initialisation of geom so can have ?occurrence=<id>.
	 * 
	 * Phase 2:
     *  Create indicia report for checking of survey walk directions.
     *  Add survey download as CSV: may need to expand main sample to include a survey. 
     *  sort out disabling of complex fields in readonly mode.
	 * 
	 * Possible future phases:
	 *  when displaying the transects in the surveys list map, could display their name.
	 *  improve outputAttributes to handle restrict to survey correctly.
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
    return array(
      array(
      	'name'=>'survey_id',
        'caption'=>'Survey ID',
        'description'=>'The Indicia ID of the survey that data will be posted into.',
        'type'=>'int'
      ),
      array(
      	'name'=>'not_logged_in',
        'caption'=>'Not Logged In Text',
        'description'=>'The text to be displayed when the user is not logged in.',
        'type'=>'string',
        'maxlength'=>200
      ),
      array(
      	'name'=>'layer1',
        'caption'=>'Layer 1 Definition',
        'description'=>'Comma separated list of option definitions for the first layer',
        'type'=>'string',
        'maxlength'=>200
      ),
      array(
      	'name'=>'layer2',
        'caption'=>'Layer 2 Definition',
        'description'=>'Comma separated list of option definitions for the first layer',
        'type'=>'string',
        'maxlength'=>200
      ),
      
      array(
      	'name'=>'sample_walk_direction_id',
        'caption'=>'Sample Walk Direction Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Walk Direction.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_reliability_id',
        'caption'=>'Sample Data Reliability Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Data Reliability.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_visit_number_id',
        'caption'=>'Sample Visit Number Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Visit Number.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_wind_id',
        'caption'=>'Sample Wind Force Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Wind Force.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_precipitation_id',
        'caption'=>'Sample Precipitation Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Precipitation.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_temperature_id',
        'caption'=>'Sample Temperature Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Temperature.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_cloud_id',
        'caption'=>'Sample Cloud Cover Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Cloud Cover.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_start_time_id',
        'caption'=>'Sample Start Time Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Start Time.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_end_time_id',
        'caption'=>'Sample End Time Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the End Time.',
        'type'=>'int'
      ),
      array(
      	'name'=>'sample_closure_id',
        'caption'=>'Sample Closed Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for Closure: this is used to determine whether the sample is editable.',
        'type'=>'int'
      ),
      array(
      	'name'=>'list_id',
        'caption'=>'Species List ID',
        'description'=>'The Indicia ID for the species list that species can be selected from.',
        'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_confidence_id',
        'caption'=>'Occurrence Confidence Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for the Data Confidence.',
        'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_count_id',
        'caption'=>'Occurrence Count Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for the Count of the particular species.',
        'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_approximation_id',
        'caption'=>'Occurrence Approximation Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for whether the count is approximate.',
        'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_territorial_id',
        'caption'=>'Occurrence Territorial Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for [TODO].',
        'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_atlas_code_id',
        'caption'=>'Occurrence Atlas Code Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for [TODO].',
        'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_overflying_id',
        'caption'=>'Occurrence Overflying Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for [TODO].',
        'type'=>'int'
      )
    );
  }
  
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Site Recording Form 2';  
  }

  public static function get_perms($nid) {
  	return array('IForm node '.$nid.' admin');
  }
  
/**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args, $node) {
  	global $user;
    $logged_in = $user->uid>0;
  	$r = '';
   	
    // Get authorisation tokens to update and read from the Warehouse.
    $writeAuth = data_entry_helper::get_auth($args['website_id'], $args['password']);
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
	$svcUrl = data_entry_helper::$base_url.'/index.php/services';

    // When invoked by GET there are the following modes:
    // Not logged in: Display an information message.
    // No additional arguments: display the survey selector.
    // Additional argument - newSample: display the main page, no occurrence or occurrence list tabs. Survey tab active.
    // Additional argument - sample_id=<id>: display the main page, fill in the main sample details, "Add Occurrence" tab present, survey tab active.
    // Additional argument - occurrence_id=<id>: display the main page, fill in the main sample details, "Add Occurrence" tab active.
    $mode = 0; // default mode : display survey selector
    			// mode 1: display new sample: no occurrence list or add occurrence tabs. Survey tab active
    			// mode 2: display existing sample. Survey tab active. No occurence details filled in.
    			// mode 3: display existing occurrence. Add Occurrence tab active. Occurence details filled in.
    			// mode 4: display Occurrence List. Occurrence List tab active. No occurence details filled in.
    $readOnly = false; // On top of this, things can be flagged as readonly. RO mode 2+4 means no Add Occurrence tab.
    if (!$logged_in){
    	return $args['not_logged_in'];
    }
    global $indicia_errors;
    $parentSample = array();
    $parentErrors = null;
    $childSample = array();
    $childErrors = null;
    $saveErrors = $indicia_errors;
    if ($_POST) {
      if(array_key_exists('website_id', $_POST)) { // Indicia POST, already handled.
    	if (array_key_exists('newSample', $_GET)){
    		if(!is_null(data_entry_helper::$entity_to_load)){
				$mode = 1; // errors with new sample, entity poulated with post, so display this data.
				$parentSample = data_entry_helper::$entity_to_load;
				$parentErrors = $indicia_errors;
    		} // else new sample, we don't have the id, so need to go back to gridview: default mode 0
		} else {
			// could have saved parent sample or child sample/occurrence pair.
			if (array_key_exists('sample:parent_id', $_POST)){ // have saved child sample/occurrence pair
				$childSample = data_entry_helper::$entity_to_load;
				$childErrors = $indicia_errors;
				if(isset(data_entry_helper::$entity_to_load)){
					$mode = 3; // errors so display Add Occurrence page.
    			} else {
					$mode = 4; //display occurrence list
    			}
			} else { // 
				$parentSample = data_entry_helper::$entity_to_load;
				$parentErrors = $indicia_errors;
				$mode=2; // display parent sample details, whether errors or not.
			}
		}
      } else { // non Indicia POST, in this case must be the location allocations.
      	if(iform_loctools_checkaccess($node,'admin')){
      		iform_loctools_deletelocations($node);
	      	foreach($_POST as $key => $value){
    	  		$parts = explode(':', $key);
      			if($parts[0] == 'location' && $value){
      				iform_loctools_insertlocation($node, $value, $parts[1]);
	      		}
    	  	}
      	}
      }
    } else {
  		if (array_key_exists('sample_id', $_GET)){
		    $mode = 2;
		// ENHANCEMENT1
		// } else if (array_key_exists('occurrence_id', $_GET)){
		//	$mode = 3;
		} else if (array_key_exists('newSample', $_GET)){
			$mode = 1;
		} // else default to mode 0
    }
    // define layers for all maps.
	// each argument is a comma separated list eg:
    // "Name:Lux Outline,URL:http://localhost/geoserver/wms,LAYERS:indicia:nation2,SRS:EPSG:2169,FORMAT:image/png,minScale:0,maxScale:1000000,units:m";
    //$Layer1WMSoptionsstring="Name:Lux Outline,URL:http://localhost/geoserver/wms,LAYERS:indicia:nation2,SRS:EPSG:2169,FORMAT:image/png,minScale:0,maxScale:1000000,units:m";
    //$Layer2WMSoptionsstring="Name:Lux Outline 2,URL:http://localhost/geoserver/wms,LAYERS:indicia:nation2,SRS:EPSG:2169,FORMAT:image/png,minScale:0,maxScale:1000000,units:m";
    $optionArray_1 = array();
    $optionArray_2 = array();
    $options = explode(',', $args['layer1']);
    foreach($options as $option){
    	$parts = explode(':', $option);
    	$optionName = $parts[0];
    	unset($parts[0]);
    	$optionsArray_1[$optionName] = implode(':', $parts);
    }
    $options = explode(',', $args['layer2']);
    foreach($options as $option){
    	$parts = explode(':', $option);
    	$optionName = $parts[0];
    	unset($parts[0]);
    	$optionsArray_2[$optionName] = implode(':', $parts);
    }
    data_entry_helper::$javascript .= "
// Create Layers.
// Base Layers first.
var WMSoptions = {          
          LAYERS: '".$optionsArray_1['LAYERS']."',
          SERVICE: 'WMS',
          VERSION: '1.1.0',
          STYLES: '',
          SRS: '".$optionsArray_1['SRS']."',
          FORMAT: '".$optionsArray_1['FORMAT']."'
    };
baseLayer_1 = new OpenLayers.Layer.WMS('".$optionsArray_1['Name']."',
        '".iform_proxy_url($optionsArray_1['URL'])."',
        WMSoptions, {        	
         	  minScale: ".$optionsArray_1['minScale'].",
	          maxScale: ".$optionsArray_1['maxScale'].",          
	          units: '".$optionsArray_1['units']."',
	          isBaseLayer: true,
        });
WMSoptions = {          
          LAYERS: '".$optionsArray_2['LAYERS']."',
          SERVICE: 'WMS',
          VERSION: '1.1.0',
          STYLES: '',
          SRS: '".$optionsArray_2['SRS']."',
          FORMAT: '".$optionsArray_2['FORMAT']."'
    };
baseLayer_2 = new OpenLayers.Layer.WMS('".$optionsArray_2['Name']."',
        '".iform_proxy_url($optionsArray_2['URL'])."',
        WMSoptions, {        	
         	  minScale: ".$optionsArray_2['minScale'].",
	          maxScale: ".$optionsArray_2['maxScale'].",          
	          units: '".$optionsArray_2['units']."',
	          isBaseLayer: true,
        });
// Create vector layers: one to display the location onto, and another for the occurrence list
// the default edit layer is used for the occurrences themselves
locationLayer = new OpenLayers.Layer.Vector(\"Location Layer\");
occListLayer = new OpenLayers.Layer.Vector(\"Occurrence List Layer\");
addListFeature = function(div, record) {
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(record.geom);
      occListLayer.addFeatures([feature]);
};
//featureHover = function(feature) {
//      alert(\"1\");
//};
//var occListSelector = new OpenLayers.Control.SelectFeature( 
//                     occListLayer, 
//                     {clickout: true, toggle: true, multiple: false, hover: true,
//  					  onSelect: featureHover} );
";
    // default mode 0 : display survey selector
	if($mode == 0){
			if(iform_loctools_checkaccess($node,'admin')){
	    		$r .= "<div id=\"controls\">\n";
				$r .= data_entry_helper::enable_tabs(array(
    		    	'divId'=>'controls',
    				'active'=>'#surveyList'
			    ));
    			$r .= "<div id=\"temp\"></div>";
    			$r .= data_entry_helper::tab_header(array('tabs'=>array(
    				'#surveyList'=>'Surveys'
    				,'#setLocations'=>'Allocate Locations'
		    	)));
			}
			$locations = iform_loctools_listlocations($node);
		if($locations == 'all'){
			$reportName = srf2_samples_list;
			$loclist = '';
		} else {
			$reportName = srf2_samples_list_restricted;
			// an empty list will cause an sql error, lids must be > 0, so push a -1 to prevent the error.
			if(empty($locations)) $locations[] = -1;
			$loclist = ", locations : \"".implode(',', $locations)."\"";
		}
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/hasharray.js', 'module');
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.datagrid.js', 'module');
		drupal_add_js("jQuery(document).ready(function(){
  $('div#smp_grid').indiciaDataGrid('rpt:".$reportName."', {
    indiciaSvc: '".$svcUrl."',
    dataColumns: ['location_name', 'date', 'num_visit', 'num_occurrences', 'num_taxa'],
    reportColumnTitles: {location_name : 'Transect', date : 'Date', num_visit : 'Visit No', num_occurrences : '# Occurrences', num_taxa : '# Species'},
    actionColumns: {show : \"?sample_id=£id£\"},
    auth : { nonce : '".$readAuth['nonce']."', auth_token : '".$readAuth['auth_token']."'},
    parameters : { survey : '".$args['website_id']."', visit_id : '".$args['sample_visit_number_id']."', closed_id : '".$args['sample_closure_id']."' ".$loclist."},
    itemsPerPage : 17,
    condCss : {field : 'closed', value : '0', css: 'srf2-highlight'},
    cssOdd : '' 
  });
});", 'inline');
		$r .= '<div id="surveyList"><div class="srf2-datapanel"><div id="smp_grid"></div>';
		$r .= '<FORM><INPUT TYPE="BUTTON" VALUE="Add a new Survey" ONCLICK="window.location.href=\'?newSample\'"></FORM></div>';
		$r .= "<div class=\"srf2-mappanel\">\n";
        $r .= data_entry_helper::map_panel(array('presetLayers' => array(),
//						      'presetLayers' => array('google_physical','google_satellite'),
    						  'layers'=>array('baseLayer_1', 'baseLayer_2', 'locationLayer')
    						, 'initialFeatureWkt' => null
    						, 'width'=>'auto'));
    	if($locations != 'all'){
    		data_entry_helper::$javascript .= "locationList = [".implode(',', $locations)."];\n";
    	}
		data_entry_helper::$javascript .= "
// upload locations into map.
// Change the location control requests the location's geometry to place on the map.
$.getJSON(\"$svcUrl\" + \"index.php/services/data/location\" +
          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          \"&callback=?\", function(data) {
    // store value in saved field?
    if (data.length>0) {
    	var feature;
    	var parser = new OpenLayers.Format.WKT();
		for (var i=0;i<data.length;i++)
		{\n";
    	if($locations != 'all'){
    		data_entry_helper::$javascript .= "
    		for(var j=0; j<locationList.length; j++) {
    		  if(locationList[j] == data[i].id || locationList[j] == data[i].parent_id) {";
    	}
		data_entry_helper::$javascript .= "
    			if(data[i].centroid_geom){
					feature = parser.read(data[i].centroid_geom);
					locationLayer.addFeatures([feature]);
				}
				if(data[i].boundary_geom){
					feature = parser.read(data[i].boundary_geom);
					locationLayer.addFeatures([feature]);
				}\n";
    	if($locations != 'all'){
    		data_entry_helper::$javascript .= "
    		  }
    		}\n";
    	}
		data_entry_helper::$javascript .= "		}
		locationLayer.map.zoomToExtent(locationLayer.getDataExtent());
    }
});
";
    						
        $r .= "</div></div>\n";
		
		if(iform_loctools_checkaccess($node,'admin')){
			$r .= '<div id="setLocations"><div class="srf2-datapanel"><FORM method="post">';
			$url = 'http://localhost/indicia/index.php/services/data/location';
	    	$url .= "?mode=json&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"].'&cachetimeout=0';
	    	$session = curl_init($url);
	    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    	$entities = json_decode(curl_exec($session), true);
	    	$userlist = iform_loctools_listusers($node);
	    	if(!empty($entities)){
	    		foreach($entities as $entity){
	    		  if(!$entity["parent_id"]){ // only assign parent locations.
	    			$r .= "\n<label for=\"location:".$entity["id"]."\">".$entity["name"].":</label><select id=\"location:".$entity["id"]."\" name=\"location:".$entity["id"]."\">";
	    				$r .= "<option value=\"\" >&lt;Not Allocated&gt;</option>";
	    				$defaultuserid = iform_loctools_getuser($node, $entity["id"]);
	    				foreach($userlist as $uid => $a_user){
	    					if($uid == $defaultuserid) {
	    						$selected = 'selected="selected"';
	    					} else {
	    						$selected = '';
	    					}
	    					$r .= "<option value=\"".$uid."\" ".$selected.">".$a_user->name."</option>";
	    				}
	    			$r .= "</select>";
	    		  }
	    		}
	    	}
	    	 $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"Save Location Allocations\" />\n";
			$r .= "</FORM></div></div></div>";
		}
		return $r;
    }
    
// ENHANCEMENT1
//    if($mode == 3 && empty($childSample)){
//	    $url = 'http://localhost/indicia/index.php/services/data/occurrence/'.$_GET['occurrence_id'];
//	    $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"];
//	    $session = curl_init($url);
//	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
//	    $entity = json_decode(curl_exec($session), true);
//	    $childSample = array();
//	    foreach($entity[0] as $key => $value){
//	    	$childSample['occurrence:'.$key] = $value;
//	    }
//    	$url = 'http://localhost/indicia/index.php/services/data/sample/'.$childSample['occurrence:sample_id'];
//	    $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"];
//	    $session = curl_init($url);
//	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
//	    $entity = json_decode(curl_exec($session), true);
//	    foreach($entity[0] as $key => $value){
//	    	$childSample['sample:'.$key] = $value;
//	    }
//	    $url = 'http://localhost/indicia/index.php/services/data/occurrence_attribute_value';
//	    $url .= "?mode=json&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"].'&occurrence_id='.$_GET['occurrence_id'];
//	    $session = curl_init($url);
//	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
//	    $entities = json_decode(curl_exec($session), true);
//	    if(!empty($entities)){
//	    	foreach($entities as $entity){
//	    		if(!is_null($entity['raw_value'])){
//		    		$childSample['occAttr:'.$entity['occurrence_attribute_id']] = $entity['raw_value'];
//		    	}
//	    	}
//	    }
//    }
    if($mode >= 2 && empty($parentSample)){
    	// have to force cachetimeout so we get most recent data (we could have just updated it!)
    	if($mode == 3){
    		$sampleID = $childSample['sample:parent_id'];
    	} else {
    		$sampleID = $_GET['sample_id'];
    	}
	    $url = 'http://localhost/indicia/index.php/services/data/sample/'.$sampleID;
	    $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."&cachetimeout=0";
	    $session = curl_init($url);
	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    $entity = json_decode(curl_exec($session), true);
	    $parentSample = array();
	    foreach($entity[0] as $key => $value){
	    	$parentSample['sample:'.$key] = $value;
	    }
	    $parentSample['sample:date'] = $parentSample['sample:date_start']; // bit of a bodge
	    $url = 'http://localhost/indicia/index.php/services/data/sample_attribute_value';
	    $url .= "?mode=json&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"].'&sample_id='.$sampleID."&cachetimeout=0";
	    $session = curl_init($url);
	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    $entities = json_decode(curl_exec($session), true);
	    if(!empty($entities)){
	    	foreach($entities as $entity){
	    		if(!is_null($entity['raw_value'])){
		    		$parentSample['smpAttr:'.$entity['sample_attribute_id']] = $entity['raw_value'];
	    		}
	    	}
	    }
    }
	$childSample['sample:date'] = $parentSample['sample:date']; // enforce a match between child and parent sample dates
    data_entry_helper::$entity_to_load=$parentSample;
    $indicia_errors = $parentErrors;				
    $closedFieldName = "smpAttr:".$args['sample_closure_id'];
    $closedFieldValue = data_entry_helper::check_default_value($closedFieldName, '0'); // default is not closed
	$adminPerm = 'IForm node '.$node->nid.' admin';
    if($closedFieldValue == '1' && !user_access($adminPerm)){
    	// sample has been closed, no admin perms. Everything now set to read only.
    	$readOnly= true;
    	$disabledText = "disabled=\"disabled\"";
    	$defAttrOptions = array('extraParams'=>$readAuth,
    							'disabled'=>$disabledText);
    } else {
    	// sample open.
    	$disabledText="";
    	$defAttrOptions = array('extraParams'=>$readAuth);
    }

    $r .= "<h1>MODE = ".$mode."</h1>";
    $r .= "<h2>readOnly = ".$readOnly."</h2>";
    
    $r .= "<div id=\"controls\">\n";
    $activeTab = 'survey';
    if($mode == 3){
    	$activeTab = 'occurrence';
    }


      	
    // Set Up form tabs.
    if($mode == 4)
    	$activeTab = 'occurrenceList';
    $r .= data_entry_helper::enable_tabs(array(
        'divId'=>'controls',
    	'active'=>$activeTab
    ));
    $r .= "<div id=\"temp\"></div>";
    $r .= data_entry_helper::tab_header(array('tabs'=>array(
    		'#survey'=>'Survey'
    		,'#occurrence'=>(isset($childSample['sample:id']) ? 'Edit Occurrence' : 'Add Occurrence')
    		,'#occurrenceList'=>'Occurrence List'
    		)));
    		
    // Set up main Survey Form.
    $r .= "<div id=\"survey\" class=\"srf2-datapanel\">\n";
	if($readOnly){
	    $r .= "<strong>This survey has been closed and is now read only.</strong>";
	}
    $r .= "<form id=\"SurveyForm\" method=\"post\">\n";
    $r .= $writeAuth;
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"sample:survey_id\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\" />\n";
    if(array_key_exists('sample:id', data_entry_helper::$entity_to_load)){
    	$r .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";	
    }
    $attributes = data_entry_helper::getAttributes(array(
        'table'=>'sample_attribute'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$readAuth + array('deleted' => 'f', 'website_deleted' => 'f')
    ));
    $r .= data_entry_helper::location_select(array_merge($defAttrOptions,
    					array('suffixTemplate'=>'requiredsuffix',
    							'label' => 'Transect')));
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_walk_direction_id']], array_merge($defAttrOptions, array('suffixTemplate'=>'requiredsuffix')));
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_reliability_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_visit_number_id']], array_merge($defAttrOptions, array('default'=>1)));
    if($readOnly){
	    $r .= data_entry_helper::text_input(array_merge($defAttrOptions,
					    array('label' => 'Date',
							'fieldname' => 'sample:date',
    						'suffixTemplate'=>'requiredsuffix',
						    'disabled'=>$disabledText
					    	)));
    } else {
	    $r .= data_entry_helper::date_picker(array('label' => 'Date',
							'fieldname' => 'sample:date',
    						'class' => 'vague-date-picker',
    						'suffixTemplate'=>'requiredsuffix'));
    }
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_wind_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_precipitation_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_temperature_id']], array_merge($defAttrOptions, array('suffixTemplate'=>'nosuffix')));
    $r .= " degC<br />";
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_cloud_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_start_time_id']], array_merge($defAttrOptions, array('suffixTemplate'=>'nosuffix')));
    $r .= " hh:mm<br />";
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_end_time_id']], array_merge($defAttrOptions, array('suffixTemplate'=>'nosuffix')));
    $r .= " hh:mm<br />";
    if(user_access($adminPerm)) { //  users with admin permissions can override the closing of the 
    	// sample by unchecking the checkbox.
    	// Because this is attached to the sample, we have to include the sample required fields in the
    	// the post. This means they can't be disabled, so we enable all fields in this case.
    	// Normal users can only set this to closed, and they do this using a button/hidden field.
    	$r .= data_entry_helper::outputAttribute($attributes[$args['sample_closure_id']], $defAttrOptions);
    } else {
	    // hidden closed
    	$r .= "<input type=\"hidden\" id=\"".$closedFieldName."\" name=\"".$closedFieldName."\" value=\"".$closedFieldValue."\" />\n";
    }

    if(!empty($indicia_errors)){
		$r .= data_entry_helper::dump_remaining_errors();
    }
    $escaped_id=str_replace(':','\\\\:',$closedFieldName);
    data_entry_helper::$javascript .= "
jQuery('#close').click(function() {
var inputlist =   jQuery(\"input#".$escaped_id."\");
jQuery(\"#".$escaped_id."\").val('1');
  jQuery('#SurveyForm').submit();  
});
";
    if(!$readOnly){
	    $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"Save Survey Details\" />\n";
	    if(!user_access($adminPerm)) {
	    	$r .= "<INPUT TYPE=BUTTON id=\"close\" class=\"ui-state-default ui-corner-all\" VALUE=\"Save Survey and Close\">";
	    }
    }
    $r .= "</form>";    
    $r .= "</div>\n";

    // Set up Occurrence List tab: don't include when creating a new sample as it will have no occurrences
    if($mode != 1){
	    $r .= "<div id=\"occurrenceList\" class=\"srf2-datapanel\">\n";
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/hasharray.js', 'module');
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.datagrid.js', 'module');
		data_entry_helper::$javascript .= "
$('div#occ_grid').indiciaDataGrid('rpt:srf2_occurrences_list', {
    indiciaSvc: '".$svcUrl."',
    dataColumns: ['taxon', 'territorial', 'count'],
    reportColumnTitles: {taxon : 'Species', territorial : 'Territorial', count : 'Count'},
// ENHANCEMENT 1
//    actionColumns: {show : \"?occurrence_id=£id£\"},
    auth : { nonce : '".$readAuth['nonce']."', auth_token : '".$readAuth['auth_token']."'},
    parameters : { survey : '".$args['website_id']."',
    				parent_id : '".$parentSample['sample:id']."',
    				territorial_id : '".$args['occurrence_territorial_id']."',
    				count_id : '".$args['occurrence_count_id']."'},
    itemsPerPage : 17,
    callback : addListFeature ,
    cssOdd : '' 
  });
";
		$r .= '<div id="occ_grid"></div></div>';
    }
    
    // Set up Occurrence tab: don't allow entry of a new occurrence until after top level sample is saved.
    if($mode != 1 && (($mode != 2 && $mode !=4) || $readOnly == false)){
    	$r .= "<div id=\"occurrence\" class=\"srf2-datapanel\">\n";
    	data_entry_helper::$entity_to_load=$childSample;
    	$indicia_errors = $childErrors;				
    	$attributes = data_entry_helper::getAttributes(array(
       		 'table'=>'occurrence_attribute'
   			,'fieldprefix'=>'occAttr'
			,'extraParams'=>$readAuth + array('deleted' => 'f', 'website_deleted' => 'f')));
    	$r .= "<form method=\"post\">\n";
    	$r .= $writeAuth;
    	$r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    	$r .= "<input type=\"hidden\" id=\"sample:survey_id\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\" />\n";
    	$r .= "<input type=\"hidden\" id=\"sample:parent_id\" name=\"sample:parent_id\" value=\"".$parentSample['sample:id']."\" />\n";	
    	$r .= "<input type=\"hidden\" id=\"sample:date\" name=\"sample:date\" value=\"".data_entry_helper::$entity_to_load['sample:date']."\" />\n";	
    	if(array_key_exists('sample:id', data_entry_helper::$entity_to_load)){
    		$r .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";	
    	}
    	$extraParams = $readAuth + array('taxon_list_id' => $args['list_id']);
	    $species_ctrl_args=array(
    	    'label'=>'Species',
        	'fieldname'=>'occurrence:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
    	    'extraParams'=>$extraParams,
	    	'suffixTemplate'=>'requiredsuffix'
	    );
	    $r .= data_entry_helper::autocomplete($species_ctrl_args);
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_confidence_id']], $defAttrOptions);
	    $r .= data_entry_helper::sref_and_system(array('label'=>'Spatial ref',
	    			'systems'=>array('2169'=>'Luref (Gauss Luxembourg)'),
	    			'suffixTemplate'=>'requiredsuffix'));
    	$r .= "<p>Click on the map to set the spatial reference</p>";
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_count_id']], array_merge($defAttrOptions, array('default'=>1, 'suffixTemplate'=>'requiredsuffix')));
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_approximation_id']], $defAttrOptions);
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_territorial_id']], array_merge($defAttrOptions, array('default'=>1)));
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_atlas_code_id']], $defAttrOptions);
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_overflying_id']], $defAttrOptions);
    	$r .= data_entry_helper::textarea(array(
	        'label'=>'Comment',
    	    'fieldname'=>'occurrence:comment',
			'disabled'=>$disabledText
	    ));	
		if(!empty($indicia_errors)){
			$r .= data_entry_helper::dump_remaining_errors();
    	}
    	if(!$readOnly){
   		 	$r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"Save Occurrence Details\" />\n";    
       	}
	    $r .= "</form></div>\n";
	    data_entry_helper::$javascript .= "
setAltasStatus = function() {
	if (jQuery(\"input[name='occAttr\\\\:".$args['occurrence_territorial_id']."']:checked\").val() == '0') {
    	jQuery('#occAttr\\\\:".$args['occurrence_atlas_code_id']."').val('');
	    jQuery('#occAttr\\\\:".$args['occurrence_atlas_code_id']."').attr('disabled','disabled');
	} else {
    	if(jQuery('#occAttr\\\\:".$args['occurrence_atlas_code_id']."').val() == ''){
    		jQuery('#occAttr\\\\:".$args['occurrence_atlas_code_id']."').val('BB02');
	    }
    	jQuery('#occAttr\\\\:".$args['occurrence_atlas_code_id']."').attr('disabled','');
	}
};
setAltasStatus();
jQuery(\"input[name='occAttr\\\\:".$args['occurrence_territorial_id']."']\").change(setAltasStatus);\n";
	    
    }
    
    // add map panel.
    $r .= "<div class=\"srf2-mappanel\">\n";
    $r .= data_entry_helper::map_panel(array('presetLayers' => array(),
//						      'presetLayers' => array('google_physical','google_satellite'),
    						  'layers'=>array('baseLayer_1', 'baseLayer_2', 'locationLayer', 'occListLayer')
    						, 'initialFeatureWkt' => null
    						, 'width'=>'auto'));
	    data_entry_helper::$onload_javascript .= "
// upload location initial value into map. wrong JS position
//jQuery('#imp-location').each(function()
//    {
//        // Change the location control requests the location's geometry to place on the map.
//        $.getJSON(div.settings.indiciaSvc + \"index.php/services/data/location/\"+this.value +
//          \"?mode=json&view=detail\" + div.settings.readAuth + \"&callback=?\", function(data) {
//            // store value in saved field?
//            if (data.length>0) {
//              _showWktFeature(div, data[0].centroid_geom);
//            }
//          }
//        );
//      });
//
//test=jQuery(\"#survey-tab\");
//jQuery(\"#survey-tab\").click(function(){
//	alert(\"TEST\");
//});

";
    						
    $r .= "</div></div>\n";
        
    return $r;
  }
  
    /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
  	if(isset($values['sample:parent_id'])){
    	$sampleMod = data_entry_helper::build_sample_occurrence_submission($values);
    } else {
    	$sampleMod = data_entry_helper::wrap_with_attrs($values, 'sample');
    }
    return $sampleMod;   
  } 

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('survey_recording_form_2.css');
  }  
}