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

class iform_mnhnl_bird_transect_walks {

	/* TODO
	 * Future Enhancements
	 * 	General
	 * 		Rename superuser to manager permission
	 *      Separate the loading of the OCCList grid view from the population of the map.
	 *      Change onShow for tabs to zoom into relevant area: eg location for survey, occlist extent for occlist
	 * 	Survey List
	 * 		Put in "loading" message functionality
	 * 		Add filter by location
	 * 		Alter location to WFS layer.
	 * 	Location Allocation
	 * 		Zoom map into location on request.
	 * 		Tidy up list - goes beyond pane boundary
	 *  Indicia Core
	 *  	improve outputAttributes to handle restrict to survey correctly.
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
      	'name'=>'layer1',
        'caption'=>'Layer 1 Definition',
        'description'=>'Comma separated list of option definitions for the first layer',
        'type'=>'string',
        'group'=>'Maps',
      'maxlength'=>200
      ),
      array(
      	'name'=>'layer2',
        'caption'=>'Layer 2 Definition',
        'description'=>'Comma separated list of option definitions for the first layer',
        'type'=>'string',
        'group'=>'Maps',
      	'maxlength'=>200
      ),
      array(
        'name'=>'preset_layers',
        'caption'=>'Preset Base Layers',
        'description'=>'Select the preset base layers that are available for the map.',
        'type'=>'list',
        'options' => array(
          'google_physical' => 'Google Physical',
          'google_streets' => 'Google Streets',
          'google_hybrid' => 'Google Hybrid',
          'google_satellite' => 'Google Satellite',
          'virtual_earth' => 'Microsoft Virtual Earth'
          // NB Multimap is UK only.
        ),
        'group'=>'Maps',
        'required'=>false
      ),
      array(
        'name'=>'map_centroid_lat',
        'caption'=>'Centre of Map Latitude',
        'description'=>'WGS84 Latitude of the initial map centre point, in decimal form.',
        'type'=>'string',
        'group'=>'Maps'
      ),
      array(
        'name'=>'map_centroid_long',
        'caption'=>'Centre of Map Longitude',
        'description'=>'WGS84 Longitude of the initial map centre point, in decimal form.',
        'type'=>'string',
        'group'=>'Maps'
      ),
      array(
        'name'=>'map_zoom',
        'caption'=>'Map Zoom Level',
        'description'=>'Zoom level of the initially displayed map.',
        'type'=>'int',
        'group'=>'Maps'
      ),

      array(
      	'name'=>'sample_walk_direction_id',
        'caption'=>'Sample Walk Direction Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Walk Direction.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_reliability_id',
        'caption'=>'Sample Data Reliability Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Data Reliability.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_visit_number_id',
        'caption'=>'Sample Visit Number Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Visit Number.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_wind_id',
        'caption'=>'Sample Wind Force Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Wind Force.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_precipitation_id',
        'caption'=>'Sample Precipitation Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Precipitation.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_temperature_id',
        'caption'=>'Sample Temperature Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Temperature.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_cloud_id',
        'caption'=>'Sample Cloud Cover Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Cloud Cover.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_start_time_id',
        'caption'=>'Sample Start Time Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the Start Time.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_end_time_id',
        'caption'=>'Sample End Time Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for the End Time.',
        'group'=>'Sample Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'sample_closure_id',
        'caption'=>'Sample Closed Custom Attribute ID',
        'description'=>'The Indicia ID for the Sample Custom Attribute for Closure: this is used to determine whether the sample is editable.',
        'group'=>'Sample Attribute IDs',
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
        'group'=>'Occurrence Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_count_id',
        'caption'=>'Occurrence Count Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for the Count of the particular species.',
        'group'=>'Occurrence Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_approximation_id',
        'caption'=>'Occurrence Approximation Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for whether the count is approximate.',
        'group'=>'Occurrence Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_territorial_id',
        'caption'=>'Occurrence Territorial Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for [TODO].',
        'group'=>'Occurrence Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_atlas_code_id',
        'caption'=>'Occurrence Atlas Code Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for [TODO].',
        'group'=>'Occurrence Attribute IDs',
      	'type'=>'int'
      ),
      array(
      	'name'=>'occurrence_overflying_id',
        'caption'=>'Occurrence Overflying Custom Attribute ID',
        'description'=>'The Indicia ID for the Occurrence Custom Attribute for [TODO].',
        'group'=>'Occurrence Attribute IDs',
      	'type'=>'int'
      )
    );
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Bird Transect Walks';
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

	$presetLayers = array();
    // read out the activated preset layers
    if(isset($args['preset_layers'])) {
	    foreach($args['preset_layers'] as $layer => $active) {
    	  if ($active!==0) {
        	$presetLayers[] = $layer;
    	  }
    	}
    }

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
    			// mode 4: NO LONGER USED. display Occurrence List. Occurrence List tab active. No occurence details filled in. 
    $readOnly = false; // On top of this, things can be flagged as readonly. RO mode 2+4 means no Add Occurrence tab.
    if (!$logged_in){
    	return lang::get('LANG_not_logged_in');
    }
    $parentSample = array();
    $parentErrors = null;
    $parentLoadID = null;
    $childSample = array();
    $childErrors = null;
    $childLoadID = null;
    $saveErrors = data_entry_helper::$validation_errors;
    $thisOccID=-1; // IDs have to be >0, so this is outside the valid range
    $displayThisOcc = true; // when populating from the DB rather than POST we have to be
    						// careful with selection object, as geom in wrong format.
    if ($_POST) {
      if(array_key_exists('website_id', $_POST)) { // Indicia POST, already handled.
    	if (array_key_exists('newSample', $_GET)){
    		if(!is_null(data_entry_helper::$entity_to_load)){
				$mode = 1; // errors with new sample, entity poulated with post, so display this data.
				$parentSample = data_entry_helper::$entity_to_load;
				$parentErrors = $saveErrors;
    		} // else new sample, we don't have the id, so need to go back to gridview: default mode 0
		} else {
			// could have saved parent sample or child sample/occurrence pair.
			if (array_key_exists('sample:parent_id', $_POST)){ // have saved child sample/occurrence pair
				$parentLoadID = $_POST['sample:parent_id']; // load the parent sample.
				$mode = 3;
				if(isset(data_entry_helper::$entity_to_load)){ // errors so display Edit Occurrence page.
					$childSample = data_entry_helper::$entity_to_load;
					$childErrors = $saveErrors;
					$displayThisOcc = false;
					if($childSample['occurrence:id']){
						$thisOccID=$childSample['occurrence:id'];
					}
//    			} else {
//					$mode = 4; //display occurrence list
    			}
			} else { // saved parent record. display updated parent, no child.
				$mode=2; // display parent sample details, whether errors or not.
				if(isset(data_entry_helper::$entity_to_load)){ // errors so use posted data.
					$parentSample = data_entry_helper::$entity_to_load;
					$parentErrors = $saveErrors;
				} else {
					$parentLoadID = $_POST['sample:id']; // load the parent sample.
				}
			}
		}
      } else { // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrept the data by accident
      	if(iform_loctools_checkaccess($node,'admin') && array_key_exists('mnhnlbtw', $_POST)){
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
		    $parentLoadID = $_GET['sample_id'];
		} else if (array_key_exists('occurrence_id', $_GET)){
			$mode = 3;
		    $childLoadID = $_GET['occurrence_id'];
		    $thisOccID = $childLoadID;
		} else if (array_key_exists('newSample', $_GET)){
			$mode = 1;
		} // else default to mode 0
    }

    // define layers for all maps.
	// each argument is a comma separated list eg:
    // "Name:Lux Outline,URL:http://localhost/geoserver/wms,LAYERS:indicia:nation2,SRS:EPSG:2169,FORMAT:image/png,minScale:0,maxScale:1000000,units:m";
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
occStyleMap = new OpenLayers.StyleMap({
                \"default\": new OpenLayers.Style({
                    pointRadius: 3,
                    fillColor: \"Red\",
                    fillOpacity: 0.3,
                    strokeColor: \"Red\",
                    strokeWidth: 1
  				}) });
occListLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_Occurrence_List_Layer")."\",
                                    {styleMap: occStyleMap});
";
	// Work out list of locations this user can see.
	$locations = iform_loctools_listlocations($node);
    ///////////////////////////////////////////////////////////////////
    // default mode 0 : display survey selector and locations allocator
    ///////////////////////////////////////////////////////////////////
    if($mode == 0){

		// If the user has permissions, add tabs so can choose to see
		// locations allocator
		$tabs = array('#surveyList'=>lang::get('LANG_Surveys'));
		if(iform_loctools_checkaccess($node,'admin')){
			$tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
		}
		if(iform_loctools_checkaccess($node,'superuser')){
			$tabs['#downloads'] = lang::get('LANG_Download');
		}
		if(count($tabs) > 1){
    		$r .= "<div id=\"controls\">\n";
			$r .= data_entry_helper::enable_tabs(array(
    		    	'divId'=>'controls',
    				'active'=>'#surveyList'
		    ));
   			$r .= "<div id=\"temp\"></div>";
   			$r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
		}


		if($locations == 'all'){
			$useloclist = 'NO';
			$loclist = '-1';
		} else {
			// an empty list will cause an sql error, lids must be > 0, so push a -1 to prevent the error.
			if(empty($locations)) $locations[] = -1;
			$useloclist = 'YES';
			$loclist = implode(',', $locations);
		}

		// Create the Survey list datagrid for this user.
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/hasharray.js', 'module');
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.datagrid.js', 'module');
		drupal_add_js("jQuery(document).ready(function(){
  $('div#smp_grid').indiciaDataGrid('rpt:mnhnl_btw_list_samples', {
    indiciaSvc: '".$svcUrl."',
    dataColumns: ['location_name', 'date', 'num_visit', 'num_occurrences', 'num_taxa'],
    reportColumnTitles: {location_name : '".lang::get('LANG_Transect')."', date : '".lang::get('LANG_Date')."', num_visit : '".lang::get('LANG_Visit_No')."', num_occurrences : '".lang::get('LANG_Num_Occurrences')."', num_taxa : '".lang::get('LANG_Num_Species')."'},
    actionColumns: {".lang::get('LANG_Show')." : \"".url('node/'.($node->nid), array('query' => 'sample_id=£id£'))."\"},
    auth : { nonce : '".$readAuth['nonce']."', auth_token : '".$readAuth['auth_token']."'},
    parameters : { survey_id : '".$args['survey_id']."', visit_attr_id : '".$args['sample_visit_number_id']."', closed_attr_id : '".$args['sample_closure_id']."', use_location_list : '".$useloclist."', locations : '".$loclist."'},
    itemsPerPage : 12,
    condCss : {field : 'closed', value : '0', css: 'mnhnl-btw-highlight'},
    cssOdd : ''
  });
});

", 'inline');
		$r .= '<div id="surveyList" class="mnhnl-btw-datapanel"><div id="smp_grid"></div>';
		$r .= '<form><input type="button" value="'.lang::get('LANG_Add_Survey').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'"></form></div>';

        // Add the locations allocator if user has admin rights.
		if(iform_loctools_checkaccess($node,'admin')){
			$r .= '<div id="setLocations" class="mnhnl-btw-datapanel"><form method="post">';
    		$r .= "<input type=\"hidden\" id=\"mnhnlbtw\" name=\"mnhnlbtw\" value=\"mnhnlbtw\" />\n";
			$url = $svcUrl.'/data/location';
	    	$url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."&parent_id=NULL&orderby=name";
	    	$session = curl_init($url);
	    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    	$entities = json_decode(curl_exec($session), true);
	    	$userlist = iform_loctools_listusers($node);
	    	if(!empty($entities)){
	    		foreach($entities as $entity){
	    		  if(!$entity["parent_id"]){ // only assign parent locations.
	    			$r .= "\n<label for=\"location:".$entity["id"]."\">".$entity["name"].":</label><select id=\"location:".$entity["id"]."\" name=\"location:".$entity["id"]."\">";
	    				$r .= "<option value=\"\" >&lt;".lang::get('LANG_Not_Allocated')."&gt;</option>";
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
	    	 $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Location_Allocations')."\" />\n";
			$r .= "</form></div>";
		}
        // Add the downloader if user has manager (superuser) rights.
		if(iform_loctools_checkaccess($node,'superuser')){
			$r .= '<div id="downloads" class="mnhnl-btw-datapanel">';
			$r .= "<form method=\"post\" action=\"".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=mnhnl_btw_transect_direction_report.xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv\">";
			$r .= '<p>'.lang::get('LANG_Direction_Report').'</p>';
			$r .= "<input type=\"hidden\" id=\"params\" name=\"params\" value='{\"survey_id\":".$args['survey_id'].", \"direction_attr_id\":".$args['sample_walk_direction_id'].", \"closed_attr_id\":".$args['sample_closure_id']."}' />";
    		$r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Direction_Report_Button')."\">";
	   		$r .= "</form>";
			$r .= "<form method=\"post\" action=\"".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=mnhnl_btw_download_report.xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv\">";
			$r .= '<p>'.lang::get('LANG_Initial_Download').'</p>';
			$r .= "<input type=\"hidden\" id=\"params\" name=\"params\" value='{\"survey_id\":".$args['survey_id'].", \"closed_attr_id\":".$args['sample_closure_id'].", \"download\": \"INITIAL\"}' />";
    		$r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Initial_Download_Button')."\">";
	   		$r .= "</form>";
	   		$r .= "<form method=\"post\" action=\"".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=mnhnl_btw_download_report.xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv\">";
			$r .= '<p>'.lang::get('LANG_Confirm_Download').'</p>';
	   		$r .= "<input type=\"hidden\" id=\"params\" name=\"params\" value='{\"survey_id\":".$args['survey_id'].", \"closed_attr_id\":".$args['sample_closure_id'].", \"download\": \"CONFIRM\"}' />";
    		$r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Confirm_Download_Button')."\">";
	   		$r .= "</form>";
			$r .= "<form method=\"post\" action=\"".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=mnhnl_btw_download_report.xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv\">";
			$r .= '<p>'.lang::get('LANG_Final_Download').'</p>';
			$r .= "<input type=\"hidden\" id=\"params\" name=\"params\" value='{\"survey_id\":".$args['survey_id'].", \"closed_attr_id\":".$args['sample_closure_id'].", \"download\": \"FINAL\"}' />";
    		$r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Final_Download_Button')."\">";
	   		$r .= "</form></div>";
		}
				// Create Map
		$r .= "<div class=\"mnhnl-btw-mappanel\">\n";
        $r .= data_entry_helper::map_panel(array('presetLayers' => $presetLayers
    						, 'layers'=>array('baseLayer_1', 'baseLayer_2', 'locationLayer')
    						, 'initialFeatureWkt' => null
    						, 'width'=>'auto'
    						, 'height'=>490
    						, 'editLayer'=> false
    						, 'initial_lat'=>$args['map_centroid_lat']
      						, 'initial_long'=>$args['map_centroid_long']
      						, 'initial_zoom'=>(int) $args['map_zoom']
    						));

    	// Add locations to the map on the locations layer.
    	// Zoom in to area which contains the users locations.
    	if($locations != 'all'){
    		data_entry_helper::$javascript .= "locationList = [".implode(',', $locations)."];\n";
    	}
		data_entry_helper::$javascript .= "
// upload locations into map.
// Change the location control requests the location's geometry to place on the map.
$.getJSON(\"$svcUrl\" + \"/data/location\" +
          \"?mode=json&view=detail&nonce=".$readAuth['nonce']."&auth_token=".$readAuth['auth_token']."\" +
          \"&parent_id=NULL&callback=?\", function(data) {
    // store value in saved field?
	locationLayer.destroyFeatures();
    if (data.length>0) {
    	var newFeatures = [];
    	var feature;
    	var parser = new OpenLayers.Format.WKT();
		for (var i=0;i<data.length;i++)
		{\n";
    	if($locations != 'all'){ // include restriction on locations if user does not have full access.
    		data_entry_helper::$javascript .= "
    		for(var j=0; j<locationList.length; j++) {
    		  if(locationList[j] == data[i].id || locationList[j] == data[i].parent_id) {";
    	}
		data_entry_helper::$javascript .= "
				if(data[i].boundary_geom){
					feature = parser.read(data[i].boundary_geom);
					feature.style = {label: data[i].name,
                        strokeColor: \"Blue\",
                    	strokeWidth: 2};
					newFeatures.push(feature);
				}\n";
    	if($locations != 'all'){
    		data_entry_helper::$javascript .= "
    		  }
    		}\n";
    	}
		data_entry_helper::$javascript .= "
		}
		locationLayer.addFeatures(newFeatures);
		locationLayer.map.zoomToExtent(locationLayer.getDataExtent());
    }
});
";
        $r .= "</div>\n";
		if(count($tabs)>1){
			$r .= "</div>";
		}
		return $r;
    }
    ///////////////////////////////////////////////////////////////////

	$occReadOnly = false;
    if($childLoadID){
	    $url = $svcUrl.'/data/occurrence/'.$childLoadID;
	    $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"];
	    $session = curl_init($url);
	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    $entity = json_decode(curl_exec($session), true);
	    $childSample = array();
	    foreach($entity[0] as $key => $value){
	    	$childSample['occurrence:'.$key] = $value;
	    }
	    if($entity[0]['downloaded_flag'] == 'F') { // Final download complete, now readonly
			$occReadOnly = true;
	    }
	    $url = $svcUrl.'/data/sample/'.$childSample['occurrence:sample_id'];
	    $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"];
	    $session = curl_init($url);
	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    $entity = json_decode(curl_exec($session), true);
	    foreach($entity[0] as $key => $value){
	    	$childSample['sample:'.$key] = $value;
	    }
	    $childSample['sample:geom'] = ''; // value received from db is not WKT, which is assumed by all the code.
	    $thisOccID = $childLoadID; // this will be used to load the occurrence into the editlayer.
		$childSample['taxon']=$childSample['occurrence:taxon'];
		$parentLoadID=$childSample['sample:parent_id'];
    }
    if($parentLoadID){
	    $url = $svcUrl.'/data/sample/'.$parentLoadID;
	    $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"];
	    $session = curl_init($url);
	    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
	    $entity = json_decode(curl_exec($session), true);
	    $parentSample = array();
	    foreach($entity[0] as $key => $value){
	    	$parentSample['sample:'.$key] = $value;
	    }
	    if(is_array($locations) && !in_array($entity[0]["location_id"], $locations)){
			return '<p>'.lang::get('LANG_No_Access_To_Location').'</p>';
		}
	    if($entity[0]["parent_id"]){
			return '<p>'.lang::get('LANG_No_Access_To_Sample').'</p>';
		}
		$parentSample['sample:date'] = $parentSample['sample:date_start']; // bit of a bodge
	    // default values for attributes from DB are picked up automatically.
    }
    $childSample['sample:date'] = $parentSample['sample:date']; // enforce a match between child and parent sample dates
    data_entry_helper::$entity_to_load=$parentSample;
    data_entry_helper::$validation_errors = $parentErrors;
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

//    $r .= "<h1>MODE = ".$mode."</h1>";
//    $r .= "<h2>readOnly = ".$readOnly."</h2>";

	data_entry_helper::enable_validation('SurveyForm');
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
    		'#survey'=>lang::get('LANG_Survey')
    		,'#occurrence'=>lang::get(($readOnly || $occReadOnly) ? 'LANG_Show_Occurrence' : (isset($childSample['sample:id']) ?  'LANG_Edit_Occurrence' : 'LANG_Add_Occurrence'))
    		,'#occurrenceList'=>lang::get('LANG_Occurrence_List')
    		)));

    // Set up main Survey Form.
    $r .= "<div id=\"survey\" class=\"mnhnl-btw-datapanel\">\n";
	if($readOnly){
	    $r .= "<strong>".lang::get('LANG_Read_Only_Survey')."</strong>";
	}
    $r .= "<form id=\"SurveyForm\" method=\"post\">\n";
    $r .= $writeAuth;
    $r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" id=\"sample:survey_id\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\" />\n";
    if(array_key_exists('sample:id', data_entry_helper::$entity_to_load)){
    	$r .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";
    }
	$defAttrOptions['validation'] = array('required');
    $defAttrOptions['suffixTemplate']='requiredsuffix';
    $attributes = data_entry_helper::getAttributes(array(
    	'id' => data_entry_helper::$entity_to_load['sample:id']
       ,'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$readAuth
    ));
    if($locations == 'all'){
    	$locOptions = array_merge(array('label'=>lang::get('LANG_Transect')), $defAttrOptions);
    	$locOptions['extraParams'] = array_merge(array('parent_id'=>'NULL', 'view'=>'detail', 'orderby'=>'name'), $locOptions['extraParams']);
    	$r .= data_entry_helper::location_select($locOptions);
    } else {
	    // can't use location select due to location filtering.
    	$r .= "<label for=\"imp-location\">".lang::get('LANG_Transect').":</label>\n<select id=\"imp-location\" name=\"sample:location_id\" ".$disabled_text." class=\" \"  >";
		$url = $svcUrl.'/data/location';
		$url .= "?mode=json&view=detail&parent_id=NULL&orderby=name&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"];
		$session = curl_init($url);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$entities = json_decode(curl_exec($session), true);
		if(!empty($entities)){
	   		foreach($entities as $entity){
				if(in_array($entity["id"], $locations)){
   					if($entity["id"] == data_entry_helper::$entity_to_load['sample:location_id']) {
						$selected = 'selected="selected"';
   					} else {
						$selected = '';
					}
   					$r .= "<option value=\"".$entity["id"]."\" ".$selected.">".$entity["name"]."</option>";
				}
		    }
	    }
		$r .= "</select><span class=\"deh-required\">*</span><br />";
    }
	$r .= data_entry_helper::outputAttribute($attributes[$args['sample_walk_direction_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_reliability_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_visit_number_id']], array_merge($defAttrOptions, array('default'=>1)));
    if($readOnly){
	    $r .= data_entry_helper::text_input(array_merge($defAttrOptions,
					    array('label' => lang::get('LANG_Date'),
							'fieldname' => 'sample:date',
						    'disabled'=>$disabledText
					    	)));
    } else {
	    $r .= data_entry_helper::date_picker(array('label' => lang::get('LANG_Date'),
							'fieldname' => 'sample:date',
    						'class' => 'vague-date-picker',
    						'suffixTemplate'=>'requiredsuffix'));
    }
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_wind_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_precipitation_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_temperature_id']], array_merge($defAttrOptions, array('suffixTemplate'=>'nosuffix')));
    $r .= " degC<span class=\"deh-required\">*</span><br />";
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_cloud_id']], $defAttrOptions);
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_start_time_id']], array_merge($defAttrOptions, array('suffixTemplate'=>'nosuffix')));
    $r .= " hh:mm<span class=\"deh-required\">*</span><br />";
    $r .= data_entry_helper::outputAttribute($attributes[$args['sample_end_time_id']], array_merge($defAttrOptions, array('suffixTemplate'=>'nosuffix')));
    $r .= " hh:mm<span class=\"deh-required\">*</span><br />";
    unset($defAttrOptions['suffixTemplate']);
    unset($defAttrOptions['validation']);
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


    if(!empty(data_entry_helper::$validation_errors)){
		$r .= data_entry_helper::dump_remaining_errors();
    }
    $escaped_id=str_replace(':','\\\\:',$closedFieldName);
    if(!$readOnly){
	    $r .= "<input type=button id=\"close1\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Survey_Details')."\";
				onclick=\"var result = $('#SurveyForm input').valid();
					var result2 = $('#SurveyForm select').valid();
					if (!result || !result2) {
    					return;
    				}
  					jQuery('#SurveyForm').submit();\">\n";
	    if(!user_access($adminPerm) && $mode !=1) {
			$r .= "<input type=button id=\"close2\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Survey_And_Close')."\"
				onclick=\"if(confirm('".lang::get('LANG_Close_Survey_Confirm')."')){
					var result = $('#SurveyForm input').valid();
					var result2 = $('#SurveyForm select').valid();
					if (!result || !result2) {
    					return;
    				}
					jQuery('#".$escaped_id."').val('1');
  					jQuery('#SurveyForm').submit();
  				};\">\n";
	    }
    }
    $r .= "</form>";
    $r .= "</div>\n";

    // Set up Occurrence List tab: don't include when creating a new sample as it will have no occurrences
    // Grid populated at a later point
	$r .= "<div id=\"occurrenceList\" class=\"mnhnl-btw-datapanel\">\n";
    if($mode != 1){
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/hasharray.js', 'module');
		drupal_add_js(drupal_get_path('module', 'iform') .'/media/js/jquery.datagrid.js', 'module');
		$r .= '<div id="occ_grid"></div>';
	   	$r .= "<form method=\"post\" action=\"".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=mnhnl_btw_occurrences_report.xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv\">";
    	$r .= "<input type=\"hidden\" id=\"params\" name=\"params\" value='{\"survey_id\":".$args['survey_id'].", \"sample_id\":".data_entry_helper::$entity_to_load['sample:id']."}' />";
    	$r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Download_Occurrences')."\">";
	   	$r .= "</FORM>";
    } else {
    	$r .= '<p>'.lang::get('LANG_Page_Not_Available').'</p>';
    }
	$r .= '</div>';
    // Set up Occurrence tab: don't allow entry of a new occurrence until after top level sample is saved.
    $r .= "<div id=\"occurrence\" class=\"mnhnl-btw-datapanel\">\n";
	if($mode != 1 && (($mode != 2 && $mode !=4) || $readOnly == false)){
    	data_entry_helper::$entity_to_load=$childSample;
    	data_entry_helper::$validation_errors = $childErrors;
    	$attributes = data_entry_helper::getAttributes(array(
	    	'id' => data_entry_helper::$entity_to_load['occurrence:id']
    	   ,'valuetable'=>'occurrence_attribute_value'
    	   ,'attrtable'=>'occurrence_attribute'
    	   ,'key'=>'occurrence_id'
    	   ,'fieldprefix'=>'occAttr'
    	   ,'extraParams'=>$readAuth
	    ));
    	if($occReadOnly){
	    	$r .= "<strong>".lang::get('LANG_Read_Only_Occurrence')."</strong>";
			$disabledText = "disabled=\"disabled\"";
    		$defAttrOptions['disabled'] = $disabledText;
		} else if($readOnly){
	    	$r .= "<strong>".lang::get('LANG_Read_Only_Survey')."</strong>";
		}
		$r .= "<form method=\"post\">\n";
    	$r .= $writeAuth;
    	$r .= "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    	$r .= "<input type=\"hidden\" id=\"sample:survey_id\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\" />\n";
    	$r .= "<input type=\"hidden\" id=\"sample:parent_id\" name=\"sample:parent_id\" value=\"".$parentSample['sample:id']."\" />\n";
    	$r .= "<input type=\"hidden\" id=\"sample:date\" name=\"sample:date\" value=\"".data_entry_helper::$entity_to_load['sample:date']."\" />\n";
    	if(array_key_exists('sample:id', data_entry_helper::$entity_to_load)){
    		$r .= "<input type=\"hidden\" id=\"sample:id\" name=\"sample:id\" value=\"".data_entry_helper::$entity_to_load['sample:id']."\" />\n";
    	}
    	if(array_key_exists('occurrence:id', data_entry_helper::$entity_to_load)){
    		$r .= "<input type=\"hidden\" id=\"occurrence:id\" name=\"occurrence:id\" value=\"".data_entry_helper::$entity_to_load['occurrence:id']."\" />\n";
    	}
    	$r .= "<input type=\"hidden\" id=\"occurrence:record_status\" name=\"occurrence:record_status\" value=\"C\" />\n";
    	$r .= "<input type=\"hidden\" id=\"occurrence:downloaded_flag\" name=\"occurrence:downloaded_flag\" value=\"N\" />\n";
    	$extraParams = $readAuth + array('taxon_list_id' => $args['list_id']);
	    $species_ctrl_args=array(
    	    'label'=>lang::get('LANG_Species'),
        	'fieldname'=>'occurrence:taxa_taxon_list_id',
	        'table'=>'taxa_taxon_list',
    	    'captionField'=>'taxon',
        	'valueField'=>'id',
	        'columns'=>2,
    	    'extraParams'=>$extraParams,
	    	'suffixTemplate'=>'requiredsuffix',
			'disabled'=>$disabledText
	    );
	    $r .= data_entry_helper::autocomplete($species_ctrl_args);
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_confidence_id']], array_merge($defAttrOptions, array('noBlankText'=>'')));
	    $r .= data_entry_helper::sref_and_system(array('label'=>lang::get('LANG_Spatial_ref'),
	    			'systems'=>array('2169'=>'Luref (Gauss Luxembourg)'),
	    			'suffixTemplate'=>'requiredsuffix'));
    	$r .= "<p>".lang::get('LANG_Click_on_map')."</p>";
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_count_id']], array_merge($defAttrOptions, array('default'=>1, 'suffixTemplate'=>'requiredsuffix')));
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_approximation_id']], $defAttrOptions);
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_territorial_id']], array_merge($defAttrOptions, array('default'=>1)));
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_atlas_code_id']], $defAttrOptions);
    	$r .= data_entry_helper::outputAttribute($attributes[$args['occurrence_overflying_id']], $defAttrOptions);
    	$r .= data_entry_helper::textarea(array(
	        'label'=>lang::get('LANG_Comment'),
    	    'fieldname'=>'occurrence:comment',
			'disabled'=>$disabledText
	    ));
		if(!empty(data_entry_helper::$validation_errors)){
			$r .= data_entry_helper::dump_remaining_errors();
    	}
    	if(!$readOnly && !$occReadOnly){
   		 	$r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Occurrence_Details')."\" />\n";
       	}
	    $r .= "</form>\n";
	    $escaped_terr_id = str_replace(':','\\\\:',$attributes[$args['occurrence_territorial_id']]['fieldname']);
	    $escaped_atlas_id = str_replace(':','\\\\:',$attributes[$args['occurrence_atlas_code_id']]['fieldname']);

	    data_entry_helper::$javascript .= "
setAltasStatus = function() {
	if (jQuery(\"input[name='".$escaped_terr_id."']:checked\").val() == '0') {
    	jQuery('#".$escaped_atlas_id."').val('');
	    jQuery('#".$escaped_atlas_id."').attr('disabled','disabled');
	} else {
    	if(jQuery('#".$escaped_atlas_id."').val() == ''){
    		jQuery('#".$escaped_atlas_id."').val('BB02');
	    }
    	jQuery('#".$escaped_atlas_id."').attr('disabled','');
	}
};
setAltasStatus();
jQuery(\"input[name='".$escaped_terr_id."']\").change(setAltasStatus);\n";
    } else {
    	$r .= '<p>'.lang::get('LANG_Page_Not_Available').'</p>';
    }
	$r .= '</div>';

    // add map panel.
    $r .= "<div class=\"mnhnl-btw-mappanel\">\n";
    $r .= data_entry_helper::map_panel(array('presetLayers' => $presetLayers
    						, 'layers'=>array('baseLayer_1', 'baseLayer_2', 'locationLayer', 'occListLayer')
    						, 'initialFeatureWkt' => null
    						, 'width'=>'auto'
    						, 'height'=>490
    						, 'initial_lat'=>$args['map_centroid_lat']
      						, 'initial_long'=>$args['map_centroid_long']
      						, 'initial_zoom'=>(int) $args['map_zoom']
    						));
    // for timing reasons, all the following has to be done after the map is loaded.
    // 1) feature selector for occurrence list must have the map present to attach the control
    // 2) location placer must have the location layer populated and the map present in
    //    order to zoom the map into the location.
    // 3) occurrence list feature adder must have map present in order to zoom into any
    //    current selection.
	data_entry_helper::$onload_javascript .= "
var control = new OpenLayers.Control.SelectFeature(occListLayer);
occListLayer.map.addControl(control);
function onPopupClose(evt) {
    // 'this' is the popup.
    control.unselect(this.feature);
}
function onFeatureSelect(evt) {
    feature = evt.feature;
    popup = new OpenLayers.Popup.FramedCloud(\"featurePopup\",
							 feature.geometry.getBounds().getCenterLonLat(),
                             new OpenLayers.Size(100,100),
                             feature.attributes.taxon + \" (\" + feature.attributes.count + \")\",
                             null, true, onPopupClose);
    feature.popup = popup;
    popup.feature = feature;
    feature.layer.map.addPopup(popup);
}
function onFeatureUnselect(evt) {
    feature = evt.feature;
    if (feature.popup) {
        popup.feature = null;
        feature.layer.map.removePopup(feature.popup);
        feature.popup.destroy();
        feature.popup = null;
    }
}

occListLayer.events.on({
    'featureselected': onFeatureSelect,
    'featureunselected': onFeatureUnselect
});

control.activate();

locationChange = function(obj){
	locationLayer.destroyFeatures();
	if(obj.value != ''){
		jQuery.getJSON(\"".$svcUrl."\" + \"/data/location/\"+obj.value +
			\"?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."\" +
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
    	                	strokeWidth: 2};
						locationLayer.addFeatures([feature]);
 					}
    				locationLayer.map.zoomToExtent(locationLayer.getDataExtent());
  				}
			}
		});
 		jQuery.getJSON(\"".$svcUrl."\" + \"/data/location\" +
			\"?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."&callback=?&parent_id=\"+obj.value, function(data) {
            if (data.length>0) {
            	var parser = new OpenLayers.Format.WKT();
            	for (var i=0;i<data.length;i++)
				{
	      			if(data[i].centroid_geom){
						feature = parser.read(data[i].centroid_geom);
						locationLayer.addFeatures([feature]);
					}
					if(data[i].boundary_geom){
						feature = parser.read(data[i].boundary_geom);
						feature.style = {label: data[i].name,
							labelAlign: \"cb\",
							strokeColor: \"Blue\",
    	                	strokeWidth: 2};
						locationLayer.addFeatures([feature]);
 					}
 				}
			}
        });
  }
};
// upload location initial value into map.
jQuery('#imp-location').each(function(){
	locationChange(this);
});
jQuery('#imp-location').unbind('change');
jQuery('#imp-location').change(function(){
	locationChange(this);
});
var selected = $('#controls').tabs('option', 'selected');

// Only leave the click control activated for edit/add occurrence tab.
if(selected != 1){
    locationLayer.map.editLayer.clickControl.deactivate(); 
}
$('#controls').bind('tabsshow', function(event, ui) {
        if(ui.index == 1) 
        { 
         locationLayer.map.editLayer.clickControl.activate(); 
        } 
        else  
        { 
         locationLayer.map.editLayer.clickControl.deactivate(); 
        } 
    }
);
";
    if($mode != 1){
		data_entry_helper::$onload_javascript .= "
activateAddList = 1;

addListFeature = function(div, r, record, count) {
	if(activateAddList == 0)
		return;
	if(r == count)
		activateAddList = 0;
    var parser = new OpenLayers.Format.WKT();
    var feature = parser.read(record.geom);
    if(record.id != ".$thisOccID." || 1==".($readOnly ? 1 : 0)." || 1==".($occReadOnly ? 1 : 0)."){
	    feature.attributes.id = record.id;
    	feature.attributes.taxon = record.taxon;
    	feature.attributes.count = record.count;
    	occListLayer.addFeatures([feature]);
    	if(record.id == ".$thisOccID."){
			var bounds=feature.geometry.getBounds();
			locationLayer.map.setCenter(bounds.getCenterLonLat());
    	}
    } else {
    	if(".($displayThisOcc ? 1 : 0)."){
    		locationLayer.map.editLayer.destroyFeatures();
			locationLayer.map.editLayer.addFeatures([feature]);
			var bounds=feature.geometry.getBounds()
			var centre=bounds.getCenterLonLat();
			locationLayer.map.setCenter(centre);
		}
    }
};
highlight = function(id){
	if(id == ".$thisOccID."){
		if(occListLayer.map.editLayer.features.length > 0){
			var bounds=occListLayer.map.editLayer.features[0].geometry.getBounds()
			var centre=bounds.getCenterLonLat();
			occListLayer.map.setCenter(centre);
			return;
		}
	}
	for(var i = 0; i < occListLayer.features.length; i++){
		if(occListLayer.features[i].attributes.id == id){
			control.unselectAll();
			var bounds=occListLayer.features[i].geometry.getBounds()
			var centre=bounds.getCenterLonLat();
			occListLayer.map.setCenter(centre);
			control.select(occListLayer.features[i]);
			return;
		}
	}
}
$('div#occ_grid').indiciaDataGrid('rpt:mnhnl_btw_list_occurrences', {
    indiciaSvc: '".$svcUrl."',
    dataColumns: ['taxon', 'territorial', 'count'],
    reportColumnTitles: {taxon : '".lang::get('LANG_Species')."', territorial : '".lang::get('LANG_Territorial')."', count : '".lang::get('LANG_Count')."'},
    actionColumns: {".lang::get('LANG_Show')." : \"".url('node/'.($node->nid), array('query' => 'occurrence_id=£id£'))."\",
    				".lang::get('LANG_Highlight')." : \"script:highlight(£id£);\"},
    auth : { nonce : '".$readAuth['nonce']."', auth_token : '".$readAuth['auth_token']."'},
    parameters : { survey_id : '".$args['survey_id']."',
    				parent_id : '".$parentSample['sample:id']."',
    				territorial_attr_id : '".$args['occurrence_territorial_id']."',
    				count_attr_id : '".$args['occurrence_count_id']."'},
    itemsPerPage : 12,
    callback : addListFeature ,
    cssOdd : ''
  });

// activateAddList = 0;

";
    };
    $r .= "</div><div><form><input type=\"button\" value=\"".lang::get('LANG_Return')."\" onclick=\"window.location.href='".url('node/'.($node->nid), array('query' => 'Main'))."'\"></form></div></div>\n";

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
    return($sampleMod);
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   *
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_bird_transect_walks.css');
  }
}