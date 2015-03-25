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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

require_once('includes/map.php');
require_once('includes/report.php');

// TODO DEV
// picture of species in corner.
// Add speed control
// Sort colour of control table.
// preload values in controls from optional URL params: Year, Species, Event, Compare
// Add function to allow user to copy URL.

/**
 * Prebuilt Indicia data form that lists the output of any report on a map.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_tree_map_2 {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_tree_map_2_definition() {
    return array(
      'title'=>'Overview 2',
      'category' => 'Custom Forms',
      'description'=>'Outputs data from a report onto a map. To work, the report must include a column containing spatial data.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $paramArray = array_merge(
      iform_map_get_map_parameters(),
      iform_report_get_minimal_report_parameters(),
      array(array(
            'name'=>'first_year',
            'caption'=>'First Year of Data',
            'description'=>'Used to determine first year displayed in the year control. Final Year will be current year.',
            'type'=>'int',
      		'group'=>'Controls'
          ),
          array(
            'name'=>'twinMaps',
            'caption'=>'Twin Maps',
            'description'=>'Display a second map, for data comparison.',
            'type'=>'boolean',
            'required'=>false,
            'default'=>false,
            'group'=>'Controls'
          ),
          array(
            'name'=>'advancedUI',
            'caption'=>'Advanced UI',
            'description'=>'Advanced User Interface: use a slider for date and dot size controls, and a graphical button. Relies on jQuery_ui.',
            'type'=>'boolean',
            'required'=>false,
            'default'=>false,
            'group'=>'Controls'
          ),
          array(
            'name'=>'dotSize',
            'caption'=>'Dot Size',
            'description'=>'Initial size in pixels of observation dots on map. Can be overriden by a control.',
            'type'=>'select',
            'options' => array(
              '2' => '2',
              '3' => '3',
              '4' => '4',
              '5' => '5'
            ),
            'default' => '3',
            'group' => 'Controls'
          ),
          array(
            'name'=>'numberOfDates',
            'caption'=>'Number of Dates',
            'description'=>'The maximum number of dates displayed on the X-axis. Used to prevent crowding. The minimum spacing is one date displayed per week. Date range is determined by the data.',
            'type'=>'int',
            'default'=>11,
            'group'=>'Controls'
          ),
		  array(
      		'name'=>'frameRate',
      		'caption'=>'Animation Frame Rate',
      		'description'=>'Number of frames displayed per second.',
      		'type'=>'int',
            'default'=>4,
      		'group'=>'Controls'
      	  ),
      	  array(
      		'name'=>'triggerEvents',
      		'caption'=>'Event Definition',
      		'description'=>'JSON encode event definition: an array, one per event type, each with a "name" element, a "type" (either...), an "attr", and a "values"',
      		'type'=>'textarea',
      		'group'=>'Events'
      	  )
      )
    );
    $retVal = array();
    foreach($paramArray as $param){
    	if(!in_array($param['name'],
    			array('map_width', 'remember_pos', 'location_boundary_id', 'items_per_page', 'param_ignores', 'param_defaults'/*, 'message_after_save', 'redirect_on_success' */)))
    		$retVal[] = $param;
    }
    return $retVal;
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    $r = "";
    data_entry_helper::add_resource('jquery_ui');
    if(isset($args['advancedUI']) && $args['advancedUI']) {
    	// TODO Sort out
//    	data_entry_helper::$resource_list['jquery_ui_slider'] =
//    		array('deps' => array('jquery_ui'), 'javascript' => array('/misc/ui/jquery.ui.slider.js'));
//    	data_entry_helper::add_resource('jquery_ui_slider');
//      drupal_add_js(drupal_get_path('module', 'jquery_update') .'/replace/ui/ui/jquery.ui.slider.js');
//      drupal_add_js('/misc/ui/jquery.ui.slider.min.js');
		drupal_add_js('/misc/ui/jquery.ui.slider.js');
//      drupal_add_js('/misc/ui/jquery.ui.button.min.js');
    }
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    
    $now = new DateTime('now');
    $year = (isset($_REQUEST['year']) ? $_REQUEST['year'] : $year = $now->format('Y'));

    iform_load_helpers(array('report_helper','map_helper'));
    $options = iform_report_get_report_options($args, $readAuth);
    
    $currentParamValues = array();
    if (isset($options['extraParams'])) {
    	foreach ($options['extraParams'] as $key=>$value) {
    		// trim data to ensure blank lines are not handled.
    		$key = trim($key);
    		$value = trim($value);
    		// We have found a parameter, so put it in the request to the report service
    		if (!empty($key))
    			$currentParamValues[$key]=$value;
    	}
    }
    $extras = '&wantColumns=1&wantParameters=1&'.report_helper::array_to_query_string($currentParamValues, true);
    $canIDuser = false;
    
    // Report record should have location_id, sample_id, occurrence_id, sample_date, species ttl_id, attributes, geometry. created_by_id is optional
    // Event definition: Name|attribute_id|attribute_values
    // Loop through event definitions
/*    $events = array(array('type'=>'arrayVal', 'name'=>'Budburst', 'attr'=>'289', 'values'=>array(3904,3961,3905,3906,3962,3907)),
                    array('type'=>'arrayVal', 'name'=>'Leaf', 'attr'=>'289', 'values'=>array(3906,3962,3907)),
                    array('type'=>'arrayVal', 'name'=>'Flowering', 'attr'=>'291', 'values'=>array(3912,3913,3914,3916,3917,3918)),
                    array('type'=>'presence', 'name'=>'Presence')); */
    $events = str_replace("\r\n", "\n", $args['triggerEvents']);
    $events = str_replace("\r", "\n", $events);
    $events = explode("\n", trim($events));
	foreach($events as $idx => $event) $events[$idx] = explode(':',$event);
	$triggerEvents = array();
	
    $SpeciesEventSelections = array();
    $Species = array();
    $r .= '<div id="errorMsg"></div>'.
    	  '<table class="ui-widget ui-widget-content ui-corner-all controls-table" id="controls-table">'.
          '<thead class="ui-widget-header">'.
    	  	'<tr><th><label for="yearControl">'.lang::get("Year").' : </label><select id="yearControl" name="year">';
    for($i = $now->format('Y'); $i >= $args['first_year']; $i--){
    	$r .= '<option value="'.$i.'">'.$i.'</option>';
    }
    $r .= '</select></th>'.
		  '<th><label for="speciesControl">'.lang::get("Species").' : </label><select id="speciesControl"><option value="">'.lang::get("Please select species").'</option></select></th>'.
    	  '<th><label for="eventControl">'.lang::get("Event").' : </label><select id="eventControl"><option value="">'.lang::get("Please select event").'</option>';
    foreach($events as $index => $event){
    	$r .= '<option value="'.$index.'">'.$event[0].'</option>';
    	$triggerEvents[] = '{"name":"'.$event[0].'","type":"'.$event[1].'"'.
      			(count($event) > 2 ? ',"attr":'.$event[2].',"values":['.$event[3].']' : '').
      			'}';
    }
    $r .= "</select></th>\n";

    if(isset($args['twinMaps']) && $args['twinMaps'])
    	$r .= '<th><label for="rhsCtrl">'.lang::get("Compare").' : </label><select id="rhsCtrl" name="rhsCtrl"><option value="">'.lang::get("Please select.").'</option><option value="Test">'.lang::get("Test.")."</option></select></th>\n";
    $r .= '</tr></thead></table>'."\n";
    
    $args['map_width']="auto";
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    $options['editLayer'] = false;
    $options['clickForSpatialRef'] = false;
    $options['scroll_wheel_zoom'] = false;
    $r .= '<div class="leftMap mapContainers leftMapOnly">'.map_helper::map_panel($options, $olOptions).'</div>';
    $options['divId']='map2';
    if(isset($args['twinMaps']) && $args['twinMaps'])
    	$r .= '<div class="rightMap mapContainers leftMapOnly">'.map_helper::map_panel($options, $olOptions).'</div>';
    
    $r .= '<div class="ui-helper-clearfix"></div><div id="timeControls">'.
    		(isset($args['advancedUI']) && $args['advancedUI'] ? '<div id="timeSlider"></div>' : '').
    		'<div id="toolbar">'.
    		'<span id="dotControlLabel">'.lang::get('Dot Size').' :</span>'.((isset($args['advancedUI']) && $args['advancedUI']) ? '<div id="dotSlider"></div>' : '<select id="dotSelect"><option>2</option><option>3</option><option>4</option><option>5</option></select>').
    		'<button id="beginning">go to beginning</button><button id="playMap">play</button><button id="end">go to end</button>'.
    		'<span id="dateControlLabel">'.lang::get("Date Currently displayed").' : '.(isset($args['advancedUI']) && $args['advancedUI'] ? '<span id="displayDate" ></span>' : '<select id="timeSelect"><option value="">'.lang::get("Please select date").'</option></select>').'</span>'.
    		'</div>';
    
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path()."../media/images/" : data_entry_helper::$images_path;    
    data_entry_helper::$javascript .= "
initTreeMap2({
  advanced_UI: ".(isset($args['advancedUI']) && $args['advancedUI'] ? "true" : "false").",
  dotSize: ".$args['dotSize'].",
  lat: ".$args['map_centroid_lat'].",
  long: ".$args['map_centroid_long'].",
  zoom: ".$args['map_zoom'].",
  triggerEvents: [".implode(',',$triggerEvents)."],
  base_url: '".data_entry_helper::$base_url."',
  report_name: '".$args['report_name']."',
  auth_token: '".$readAuth['auth_token']."',
  nonce: '".$readAuth['nonce']."',
  reportExtraParams: '".$extras."',
  indicia_user_id: ".(hostsite_get_user_field('indicia_user_id') ? hostsite_get_user_field('indicia_user_id') : 'false').",
  timeControlSelector: '".(isset($args['advancedUI']) && $args['advancedUI'] ? '#timeSlider' : '#timeSelect')."',
  dotControlSelector: '".(isset($args['advancedUI']) && $args['advancedUI'] ? '#dotSlider' : '#dotSelect')."',
  timerDelay: ".((int)1000/$args['frameRate']).",
  twinMaps: ".(isset($args['twinMaps']) && $args['twinMaps'] ? 'true' : 'false').",
  imgPath: '".$imgPath."'
});
";
    $r .= '</div>';
    return $r;
  }
}
