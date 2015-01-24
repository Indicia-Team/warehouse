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

// TODO
// Proper data drive of events.
// picture of species in corner.
// Sort colour of control table.
//
// Sort Year control
//   onChange invoke self.
//
// Next stage: 2 maps side by side
// Then add compare different years.
// zoom to dataset on change of species/event


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
    return array_merge(
      iform_map_get_map_parameters(),
      iform_report_get_minimal_report_parameters(),
      array(array(
            'name'=>'first_year',
            'caption'=>'First Year of Data',
            'description'=>'Used to generate the year control.',
            'type'=>'int',
      		'group'=>'Controls'
          ),
          array(
            'name'=>'caching',
            'caption'=>'Use Cache',
            'description'=>'Select to cache the report results. This can lead to performance improvements for high volume data sets.',
            'type'=>'boolean',
            'required'=>false,
            'default'=>false,
            'group'=>'Report Settings'
          ),
          array(
            'name'=>'dotSize',
            'caption'=>'Dot Size',
            'description'=>'Size in pixels of observation dots on map. This will be the default if a user control is created.',
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
            'name'=>'dynamicEndPoint',
            'caption'=>'Dynamic End Point',
            'description'=>'Calculate the end point displayed from the report data. If not selected, it will use either today or the last day in the year, whichever is latest.',
            'type'=>'boolean',
            'required'=>false,
            'default'=>false,
            'group'=>'Report Settings'
          ),
          array(
            'name'=>'numberOfDates',
            'caption'=>'Number of Dates',
            'description'=>'The maximum number of dates displayed on the X-axis. Used to prevent crowding. The minimum spacing is one date displayed per week.',
            'type'=>'int',
            'default'=>11,
            'group'=>'Controls'
          )
      )
    );
  }

  private static $advanced_UI = true;

  protected static function getReloadPath () {
  	$reload = data_entry_helper::get_reload_link_parts();
  	unset($reload['params']['year']);
  	$reloadPath = $reload['path'];
  	if(count($reload['params'])) {
  		// decode params prior to encoding to prevent double encoding.
  		foreach ($reload['params'] as $key => $param) {
  			$reload['params'][$key] = urldecode($param);
  		}
  		$reloadPath .= '?'.http_build_query($reload['params']);
  	}
  	return $reloadPath;
  }
  
  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    //  This form designed in the knowledge that a second map may be added later.
    $r = "";
    data_entry_helper::add_resource('jquery_ui');
    if(self::$advanced_UI)
//      drupal_add_js(drupal_get_path('module', 'jquery_update') .'/replace/ui/ui/jquery.ui.slider.js');
      drupal_add_js('/misc/ui/jquery.ui.slider.min.js');
    iform_load_helpers(array('map_helper'));
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);

    $now = new DateTime('now');
    if(isset($_GET['year'])) {
      $year = $_REQUEST['year'];
    } else {
      $year = $now->format('Y');
    }
    $yearStart = new DateTime($year.'-01-01 00:00:00');
    $firstJan = new DateTime($year.'-01-01 12:00:00'); // put midday as allows easier day calculations.
    // end of data either now for this year, or 31st December if a previous year.
    if($year != $now->format('Y'))
      $endDateTime = new DateTime($year.'-12-31 12:00:00');
    else $endDateTime = new DateTime('now');
    $endDayIndex = ceil(($endDateTime->format('U') - $yearStart->format('U'))/(24*60*60));
    $firstJanIndex = ceil(($firstJan->format('U') - $yearStart->format('U'))/(24*60*60));
    $first = array('r'=>0, 'g'=>0, 'b'=>255); // colour of first date displayed.
    $last = array('r'=>255, 'g'=>0, 'b'=>0);  // colour of last date displayed.

    iform_load_helpers(array('report_helper','map_helper'));
    $options = array_merge(
     array(
      'mode' => 'report',
      'id' => 'report-output', // this needs to be set explicitly when more than one report on a page
      'itemsPerPage' => 20,
      'class' => 'ui-widget ui-widget-content report-grid',
      'thClass' => 'ui-widget-header',
      'altRowClass' => 'odd',
      'columns' => array(),
      'galleryColCount' => 1,
      'headers' => true,
      'includeAllColumns' => true,
      'autoParamsForm' => true,
      'paramsOnly' => false,
      'extraParams' => array(),
      'completeParamsForm' => true,
      'callback' => '',
      'paramsFormButtonCaption' => 'Run Report',
      'paramsInMapToolbar' => false,
      'view' => 'list',
      'caching' => isset($args['caching']) && $args['caching'],
      'sendOutputToMap' => false,
      'zoomMapToOutput' => true,
      'ajax' => false,
      'autoloadAjax' => true,
      'linkFilterToMap' => true,
      'pager' => true
    ),
    		     iform_report_get_report_options($args, $readAuth)
    );
    // use the current report as the params form by default
    if (empty($options['reportGroup'])) $options['reportGroup'] = $options['id'];
    if (empty($options['fieldNamePrefix'])) $options['fieldNamePrefix'] = $options['reportGroup'];
    
    $extras = '&wantColumns=1&wantParameters=1';
    if (array_key_exists('extraParams', $options) && array_key_exists('ignoreParams', $options))
      $options['paramsToExclude'] = array_merge($options['ignoreParams'], array_keys($options['extraParams']));
    elseif (array_key_exists('extraParams', $options))
      $options['paramsToExclude'] = array_keys($options['extraParams']);
    elseif (array_key_exists('ignoreParams', $options))
      $options['paramsToExclude'] = array_merge($options['ignoreParams']);
    if (array_key_exists('paramsToExclude', $options))
      $extras .= '&paramsFormExcludes='.json_encode($options['paramsToExclude']);
    $currentParamValues = array();
    if (isset($options['paramDefaults'])) {
    	foreach ($options['paramDefaults'] as $key=>$value) {
    		// trim data to ensure blank lines are not handled.
    		$key = trim($key);
    		$value = trim($value);
    		// We have found a parameter, so put it in the request to the report service
    		if (!empty($key))
    			$currentParamValues[$key]=$value;
    	}
    }
    $currentParamValues["year"]=$year;
    $currentParamValues["date_from"]=$year.'-01-01';
    $currentParamValues["date_to"]=$year.'-12-31';
    $extras .= '&'.report_helper::array_to_query_string($currentParamValues, true);
//    $r .= '<span style="display:none;">'."\n".print_r($options, true)."\n".print_r($extras, true).'</span>';
    $response = report_helper::get_report_data($options, $extras);

    if (isset($response['error'])) return $response['error'];
//    $r .= self::params_form_if_required($response, $options, $currentParamValues);
//    $r .= print_r($response, true);
    $records = $response['records'];
    // find the geom column
    $canIDuser = false;
    foreach($response['columns'] as $col=>$cfg) {
      if($col == 'created_by_id')
        $canIDuser = true;
      if (isset($cfg['mappable']) && $cfg['mappable']=='true' && !isset($wktCol)) {
        $wktCol = $col;
      }
    }
    if (!isset($wktCol))
      $r .= "<p>".lang::get("The report's configuration does not output any mappable data")."</p>";
    data_entry_helper::$javascript .= "\nvar mySiteFeature = false;
var features = [];
var parser = new OpenLayers.Format.WKT();\n";
    
    // Report record should have location_id, sample_id, occurrence_id, sample_date, species ttl_id, attributes, geometry. created_by_id is optional
    
    if ($canIDuser && function_exists('hostsite_get_user_field')){
      $me = hostsite_get_user_field('indicia_user_id');
      $mySites = array();
      foreach ($records as $record) {
        if($record['created_by_id'] == $me){
          $wkt=preg_replace('/POINT\(/', '', $record[$wktCol]); // remove point stuff
          $wkt=preg_replace('/\)/', '', $wkt); // remove point stuff
          $wkt=preg_replace('/\.(\d+)/', '', $wkt); // make integers
          $mySites[$record["location_id"]] = $wkt;
        }
      }
      if(count($mySites)>0)
        data_entry_helper::$javascript .= "mySiteFeature=parser.read('".(count($mySites) > 1 ? "MULTIPOINT" : "POINT")."(".implode($mySites, ',').")');
mySiteFeature.style = {fillColor: 0, fillOpacity: 0, strokeWidth: 2, strokeColor: 'Yellow', graphicName: 'square'};\n";
    }
    
    // Report record should have location_id, sample_id, occurrence_id, sample_date, species ttl_id, attributes, geometry.
    // Event definition: Name|attribute_id|attribute_values
    // Loop through event definitions
    $events = array(array('name'=>'Budburst', 'attr'=>'289', 'values'=>array(3904,3961,3905,3906,3962,3907), 'clearAfter'=>true),
                    array('name'=>'Leaf', 'attr'=>'289', 'values'=>array(3906,3962,3907), 'clearAfter'=>true),
                    array('name'=>'Flowering', 'attr'=>'291', 'values'=>array(3912,3913,3914,3916,3917,3918), 'clearAfter'=>true));

    foreach ($records as $i=>$record) {
      // convert dates.
      $parts = explode("/",$record["date"]);
      $records[$i]["converted_date"] = $parts[2]."/".$parts[1]."/".$parts[0];
    }
    $SpeciesEventSelections = array();
    $minDayIndex = $endDayIndex; // min and max are calculated across all events.
    $maxDayIndex = $firstJanIndex;
    foreach($events as $evt)
    {
      data_entry_helper::$javascript .= "features['".$evt["name"]."']=[];\n";
      $thisEvent = array();
      foreach ($records as $record) {
        if(in_array($record['attr_occurrence_'.$evt["attr"]],$evt["values"]))
        {
          if(!isset($thisEvent[$record["species_id"]])){
          	$SpeciesEventSelections[$record["species_id"].':'.$evt["name"]] = array('id'=>$record["species_id"], 'taxon'=>isset($record["taxon"])?$record["taxon"]:$record["species_id"], 'event'=>$evt["name"]);
            $thisEvent[$record["species_id"]] = array();
            for($i = $firstJanIndex; $i <= $endDayIndex; $i++) {
              $thisEvent[$record["species_id"]][$i] = array("mine"=>array(), "others"=>array());
            }
          }
          $datetime1 = new DateTime($record["converted_date"]);
          $recordDayIndex = ceil(($datetime1->format('U') - $yearStart->format('U'))/(24*60*60));
          $record[$wktCol]=preg_replace('/POINT\(/', '', $record[$wktCol]); // remove point stuff
          $record[$wktCol]=preg_replace('/\)/', '', $record[$wktCol]); // remove point stuff
          $record[$wktCol]=preg_replace('/\.(\d+)/', '', $record[$wktCol]); // make integers
          $thisEvent[$record["species_id"]][$recordDayIndex][$canIDuser && $record['created_by_id'] == $me ? "mine" : "others"][$record["location_id"]] = $record[$wktCol];
          if($minDayIndex >= $recordDayIndex) $minDayIndex = $recordDayIndex-1; // mindayindex will be day before first record (nothing displayed), only events.
        }
      }
      foreach($thisEvent as $speciesID=>$dates) { // only show first of any given event at a given location
        for($i = $firstJanIndex; $i <= $endDayIndex; $i++) {
          if(count($thisEvent[$speciesID][$i]["others"])>0) {
            if($maxDayIndex < $i)  $maxDayIndex = $i; // maxdayindex will be day of last event - all displayed.
            foreach($thisEvent[$speciesID][$i]["others"] as $location => $geom)
              for($j = $i+1; $j <= $endDayIndex; $j++)
                unset($thisEvent[$speciesID][$j]["others"][$location]);
          }
          if(count($thisEvent[$speciesID][$i]["mine"])>0) {
            if($maxDayIndex < $i)  $maxDayIndex = $i; // maxdayindex will be day of last event - all displayed.
            foreach($thisEvent[$speciesID][$i]["mine"] as $location => $geom)
              for($j = $i+1; $j <= $endDayIndex; $j++)
                unset($thisEvent[$speciesID][$j]["mine"][$location]);
          }
        }
      } 
      foreach($thisEvent as $speciesID=>$dates) {
        data_entry_helper::$javascript .= "features['".$evt["name"]."'][".$speciesID."]=[];\n";
        for($i = $firstJanIndex; $i <= $endDayIndex; $i++) {
          $innerstruct = array();
          if (count($dates[$i]["others"])>0)
            $innerstruct[] = "others : parser.read('".(count($dates[$i]["others"]) > 1 ? "MULTIPOINT" : "POINT")."(".implode($dates[$i]["others"], ',').")')";
          if (count($dates[$i]["mine"])>0)
            $innerstruct[] = "mine : parser.read('".(count($dates[$i]["mine"]) > 1 ? "MULTIPOINT" : "POINT")."(".implode($dates[$i]["mine"], ',').")')";
          if (count($innerstruct)>0)
            data_entry_helper::$javascript .= "features['".$evt["name"]."'][".$speciesID."][".$i."]={".implode($innerstruct, ',')."};\n";
        }
      }
    }
    if($minDayIndex == $endDayIndex || $minDayIndex < $firstJanIndex) $minDayIndex = $firstJanIndex;
    if($maxDayIndex == $firstJanIndex || $maxDayIndex > $endDayIndex || !isset($args['dynamicEndPoint'])  || !$args['dynamicEndPoint']) $maxDayIndex = $endDayIndex;
    // extract the combined taxon/event select.
    ksort($SpeciesEventSelections);
    $r .= '<div id="leftMap" class="ui-helper-clearfix"><table class="ui-widget ui-widget-content ui-corner-all controls-table" id="controls-table"><thead class="ui-widget-header"><tr>';

    if(count($SpeciesEventSelections) > 0) {
      $r .= "\n".'<th><label for="eventControl1">'.lang::get("Event").' : </label><select id="eventControl1">';
      $r .= '<option value="">'.lang::get("Please select species/event combination").'</option>';
      foreach($SpeciesEventSelections as $selection){
        $r .= '<option value="'.$selection['id'].":".$selection['event'].'">'.$selection['taxon']." ".$selection['event'].'</option>';
      }
      $r .= '</select></th>';
    } else {
    	$r .= "\n<th>".lang::get("There are no mapable events yet for this year").'</th>';
    }
    $reloadPath = self::getReloadPath ();
    $r .= '<th><form id="yearControlForm" method = "GET" action="'.$reloadPath.'"><label for="yearControl1">'.lang::get("Year").' : </label><select id="yearControl1" name="year">';
    for($i = $now->format('Y'); $i >= $args['first_year']; $i--){
      $r .= '<option value="'.$i.'">'.$i.'</option>';
    }
    $r .= '</select></form></th><th>'.lang::get("Date Currently displayed").' : <span id="displayDate" ></span></th></tr></thead></table>'."\n";
    
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    $options['editLayer'] = false;
    $options['clickForSpatialRef'] = false;
    $options['scroll_wheel_zoom'] = false;
    //        unset($options['standardControls'][array_search('layerSwitcher', $options['standardControls'])]);
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>';
    // RH map to be inserted here.
    if(count($SpeciesEventSelections) > 0) {
      $r .= '<div id="timeControls" class="ui-helper-clearfix">';
      if(self::$advanced_UI)
      	$r .= '<div id="timeSlider"></div>';
      $r .= '  <div id="toolbar">
    <button id="beginning">go to beginning</button>
    <button id="playMap">play</button>
    <button id="end">go to end</button>'.(self::$advanced_UI ? '<span id="dotSliderLabel">'.lang::get('Dot Size').' :</span><div id="dotSlider"></div>' : '').'    </div>';
    }

/*
    if (!isset($response['parameterRequest']) || count(array_intersect_key($currentParamValues, $response['parameterRequest']))==count($response['parameterRequest'])) {
      $geoms = array();
      foreach ($records as $record) {
        if (!empty($record[$wktCol])) {
          $record[$wktCol]=preg_replace('/\.(\d+)/', '', $record[$wktCol]); // make integers
          // rather than output every geom separately, do a list of distinct geoms to minify the JS
          if (!$geomIdx = array_search('"'.$record[$wktCol].'"', $geoms)) {
            $geoms[] = '"'.$record[$wktCol].'"';
            $geomIdx = count($geoms)-1;
          }
        }
      }
      data_entry_helper::$javascript .= 'indiciaData.geoms=['.implode(',',$geoms)."];\n";
    } */
    
    data_entry_helper::$javascript .= "
var advanced_UI = ".(self::$advanced_UI ? "true" : "false").";
var dummySliderValue = $minDayIndex;
var global_timer_function = false;
var last_displayed = -1;
var displayArray1 = false;
var dotSize = ".$args['dotSize'].";

$('#yearControl1').val('$year');

var eventsLayer;
var sitesLayer;

$('#eventControl1').change(function(){
  if(global_timer_function) {
    clearInterval(global_timer_function);
    global_timer_function = false;
  }
  if($(this).val() != '') {
    var value = $(this).val().split(':');
    displayArray1 = features[value[1]][value[0]];
//    $( '#playMap' ).removeAttr('disabled');
  } else {
    displayArray1 = false;
//    $( '#playMap' ).attr('disabled',true);
  }
  var toRemove = [];
  for(var i = 0; i < eventsLayer.features.length; i++)
    toRemove.push(eventsLayer.features[i]);
  if(toRemove.length>0)
    eventsLayer.removeFeatures(toRemove);
  last_displayed = -1;
  setToDate($maxDayIndex);
  if(advanced_UI) {
    $( '#timeSlider' ).slider( 'option', 'value', $maxDayIndex );
    $( '#playMap' ).button( 'option', { label: 'play', icons: { primary: 'ui-icon-play' }} );
  } else {
    $( '#playMap' ).text( 'play' );
    dummySliderValue = $maxDayIndex;
  }
});

// TODO - pass event through?
$('#yearControl1').change(function(){
  $('#yearControlForm').submit();
});

mapInitialisationHooks.push(function(mapdiv) {
  eventsLayer = new OpenLayers.Layer.Vector('Events Layer', {displayInLayerSwitcher: false});
  sitesLayer = new OpenLayers.Layer.Vector('My Sites', {displayInLayerSwitcher: true});
  mapdiv.map.addLayer(sitesLayer);
  mapdiv.map.addLayer(eventsLayer);
  if(mySiteFeature){
    mySiteFeature.style.pointRadius = dotSize+1;
    sitesLayer.addFeatures([mySiteFeature]);
  }
  $('#eventControl1').change();
  // switch off the mouse drag pan.
  for(var i = 0; i < mapdiv.map.controls.length; i++){
    if(mapdiv.map.controls[i].CLASS_NAME == 'OpenLayers.Control.Navigation')
      mapdiv.map.controls[i].deactivate();
  }
});

var rgbvalue = function(dateidx) {
  var r = parseInt(".$last["r"]."*(dateidx-$minDayIndex)/($maxDayIndex-$minDayIndex) + ".$first["r"]."*($maxDayIndex-dateidx)/($maxDayIndex-$minDayIndex));
  r = (r<16 ? '0' : '') + r.toString(16);
  var g = parseInt(".$last["g"]."*(dateidx-$minDayIndex)/($maxDayIndex-$minDayIndex) + ".$first["g"]."*($maxDayIndex-dateidx)/($maxDayIndex-$minDayIndex));
  g = (g<16 ? '0' : '') + g.toString(16);
  var b = parseInt(".$last["b"]."*(dateidx-$minDayIndex)/($maxDayIndex-$minDayIndex) + ".$first["b"]."*($maxDayIndex-dateidx)/($maxDayIndex-$minDayIndex));
  b = (b<16 ? '0' : '') + b.toString(16);
  return '#'+r+g+b;
};

var jitterRadius = 15000; // TODO datadrive

var applyJitter = function(feature){
  var X = 1000000;
  // multipoints, only do for individual points
  if(feature.geometry.CLASS_NAME == 'OpenLayers.Geometry.MultiPoint') {
    for(var j = 0; j < feature.geometry.components.length; j++){
      for(var i = 0; i < eventsLayer.features.length; i++)
        X = Math.min(X, feature.geometry.components[j].distanceTo(eventsLayer.features[i].geometry));
      if(X<jitterRadius){
        feature.attributes.jittered = true;
        var angle = Math.random()*Math.PI*2;
        feature.geometry.components[j].move(jitterRadius*Math.cos(angle),jitterRadius*Math.sin(angle));
      }
    }
  } else { 
    for(var i = 0; i < eventsLayer.features.length; i++)
      X = Math.min(X, feature.geometry.distanceTo(eventsLayer.features[i].geometry));
    if(X<jitterRadius){
      feature.attributes.jittered = true;
      var angle = Math.random()*Math.PI*2;
      feature.geometry.move(jitterRadius*Math.cos(angle),jitterRadius*Math.sin(angle));
    }
  }
}

var displayDay = function(day, idx){
  if(typeof day != 'undefined' && day !== false) {
    if(typeof day.others != 'undefined') {
      if(typeof day.others.attributes.dayIndex == 'undefined') {
        day.others.attributes.dayIndex = idx;
        day.others.attributes.others = true;
        day.others.attributes.jittered = false;
        day.others.style = {fillColor: rgbvalue(idx), fillOpacity: 0.8, strokeWidth: 0};
        applyJitter(day.others);
      }
      day.others.style.pointRadius = dotSize;
      eventsLayer.addFeatures([day.others]);
    }
    if(typeof day.mine != 'undefined') {
      if(typeof day.mine.attributes.dayIndex == 'undefined') {
        day.mine.attributes.dayIndex = idx;
        day.mine.attributes.others = false;
        day.mine.attributes.jittered = false;
        day.mine.style = {strokeWidth: 3, strokeColor: 'Yellow', graphicName: 'square', fillColor: rgbvalue(idx), fillOpacity: 1};
//        applyJitter(day.mine); Dont apply jitter to own data as this may 
      }
      day.mine.style.pointRadius = dotSize+2;
      eventsLayer.addFeatures([day.mine]);
    }
  }
};

var setToDate = function(idx){
  var displayYear = false;
  var myDate=new Date();
  myDate.setFullYear($year,0,1);
  if(idx>1)
    myDate.setDate(myDate.getDate()+(idx-1));
  $('#displayDate').html(myDate.getDate()+'/'+(myDate.getMonth()+1)+(displayYear ? '/'+myDate.getFullYear() : ''));
    
  if(displayArray1 !== false && idx !== last_displayed) {
    if(idx == last_displayed+1) 
      displayDay(displayArray1[idx],idx);
    else if(last_displayed > idx){
      var toRemove = [];
      for(var i = 0; i < eventsLayer.features.length; i++)
        if(eventsLayer.features[i].attributes.dayIndex > idx)
          toRemove.push(eventsLayer.features[i]);
      if(toRemove.length>0)
        eventsLayer.removeFeatures(toRemove);
    } else {
      for(var i = last_displayed+1; i < idx; i++)
        displayDay(displayArray1[i],i);
    }
  }
  last_displayed = idx;
};

$(function() {
  if(advanced_UI) {
    $( '#timeSlider' ).slider();
    $( '#timeSlider' ).slider( 'option', 'min', $minDayIndex );
    $( '#timeSlider' ).slider( 'option', 'max', $maxDayIndex );
    $( '#timeSlider' ).slider({change : function( event, ui ) { setToDate($( '#timeSlider' ).slider( 'value' )); } });
    $( '#beginning' ).button({ text: false, icons: { primary: 'ui-icon-seek-start' } });
    $( '#playMap' ).button({ text: false, icons: { primary: 'ui-icon-play' }});
    $( '#end' ).button({ text: false, icons: { primary: 'ui-icon-seek-end' }});
    var slider =  $('#timeSlider');
    var spacing =  100 / ($maxDayIndex-$minDayIndex);
    slider.find('.ui-slider-tick-mark').remove();
    var maxLabels = ".(isset($args['numberOfDates']) && $args['numberOfDates'] > 1 ? $args['numberOfDates'] : 11).";
    var maxTicks = 100;
    var daySpacing = $maxDayIndex-$minDayIndex == 0 ? 1 : Math.ceil(($maxDayIndex-$minDayIndex)/maxTicks);
    var provisionalLabelSpacing = Math.max(7, Math.ceil(($maxDayIndex-$minDayIndex)/maxLabels));
    var actualLabelSpacing = daySpacing*Math.ceil(provisionalLabelSpacing/daySpacing);
    for (var i = 0; i <= $maxDayIndex-$minDayIndex ; i+=daySpacing) {
      var monthNames = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
      var myDate=new Date();
      myDate.setFullYear($year,0,1);
      if($minDayIndex+i>1)
        myDate.setDate(myDate.getDate()+($minDayIndex+i-1));
      if(i>0 && i<$maxDayIndex-$minDayIndex)
        $('<span class=\"ui-slider-tick-mark'+(!(i % actualLabelSpacing) ? ' long' : '')+'\"></span>').css('left', Math.round(spacing * i * 10)/10 +  '%').appendTo(slider);
      if(!(i % actualLabelSpacing) && spacing*i < 95)
        $('<span class=\"ui-slider-label\"><span>'+myDate.getDate()+' '+monthNames[myDate.getMonth()]+'</span></span>').css('left', Math.round(spacing * i * 10)/10 +  '%').appendTo(slider); 
    }
    $( '#dotSlider' ).slider();
    $( '#dotSlider' ).slider( 'option', 'min', 2 );
    $( '#dotSlider' ).slider( 'option', 'max', 5 );
    $( '#dotSlider' ).slider( 'option', 'value', dotSize );
    $( '#dotSlider' ).slider({change : function( event, ui ) {
      dotSize = $( '#dotSlider' ).slider( 'value' );
      if(mySiteFeature){
        sitesLayer.removeFeatures[mySiteFeature];
        mySiteFeature.style.pointRadius = dotSize+2;
        sitesLayer.addFeatures([mySiteFeature]);
      }
      setToDate(-1); // clear old dots
      setToDate($( '#timeSlider' ).slider( 'value' ));
    } });
  }
  $( '#beginning' ).click(function() {
    if(global_timer_function) {
      clearInterval(global_timer_function);
      global_timer_function = false;
    }
    if(advanced_UI) {
      $( '#timeSlider' ).slider( 'option', 'value', $minDayIndex );
      $( '#playMap' ).button( 'option', { label: 'play', icons: { primary: 'ui-icon-play' }} );
    } else $( '#playMap' ).text('play');
    dummySliderValue = $minDayIndex;
    setToDate($minDayIndex);
  });
  $( '#playMap' ).click(function() {
    if(displayArray1 === false){
      alert('Please select an Event type before playing');
      return;
    }
    var caller = function() {
      var value = advanced_UI ? $( '#timeSlider' ).slider( 'value' ) : dummySliderValue;
      if(value < $maxDayIndex) {
        if(advanced_UI) $( '#timeSlider' ).slider( 'value', value+1 );
        dummySliderValue = value+1;
        setToDate(value+1);
      } else {
        clearInterval(global_timer_function);
        global_timer_function = false;
        if(advanced_UI) $( '#playMap' ).button( 'option', { label: 'play', icons: { primary: 'ui-icon-play' }} );
        else $( '#playMap' ).text('play');
      }
    };
    var options;
    if ( $( this ).text() === 'play' ) {
      var value = advanced_UI ? $( '#timeSlider' ).slider( 'value' ) : dummySliderValue;
      if(value >= $maxDayIndex) {
        if(advanced_UI) {
          $( '#timeSlider' ).slider( 'option', 'value', $minDayIndex );
          $( '#playMap' ).button( 'option', { label: 'play', icons: { primary: 'ui-icon-play' }} );
        } else $( '#playMap' ).text('play');
        dummySliderValue = $minDayIndex;
      }
      options = { label: 'pause', icons: { primary: 'ui-icon-pause' }};
      global_timer_function = setInterval(caller,250);
    } else {
      if(global_timer_function) {
        clearInterval(global_timer_function);
        global_timer_function = false;
      }
      options = { label: 'play', icons: { primary: 'ui-icon-play' }};
    }
    if(advanced_UI) $( this ).button( 'option', options );
    else $( this ).text(options.label);
  });
  $( '#end' ).click(function() {
    if(global_timer_function) {
      clearInterval(global_timer_function);
      global_timer_function = false;
    }
    if(advanced_UI) {
      $( '#timeSlider' ).slider( 'option', 'value', $maxDayIndex);
      $( '#playMap' ).button( 'option', { label: 'play', icons: { primary: 'ui-icon-play' }} );
    } else $( '#playMap' ).text('play');
    dummySliderValue = $maxDayIndex;
    setToDate($maxDayIndex);
  });
  setToDate($minDayIndex);
});\n";
    $r .= '</div>';
    
    // Define report: needs tree location id, sample id, species id, date, updated_on date, attributes, position
    // Run report for start of year til today, updated_on_date since the last run.
    // form arg to specify whether to use a location_id
    // set results array empty.
    // loop through all results:
    //   loop through event definitions
    //     if matches event description
    //       load catched events into results array for this species/event id.
    //       identify date index.
    //       if location_id being used
    //         loop through results array up to but excluding date
    //           if location_id found with same sample id -> have moved the sample, unset the results entry for the location_id, tag this location as a rerun: jump to next result.
    //           if location_id found => has previously met event requirements: jump to next result.
    //         if location_id already exists on this date: jump to next result.
    //         set results array[species/event id][date] to include this location_id - position, sample_id
    //         loop through results array from this date+1 to end
    //           if location_id found => unset the results array data.
    //       else
    //         set results array[species/event id][date] to include position
    // Rerun report for tagged locations, 
    // loop through results arrays, save data into file cache.
    // 
    // load date:time last cache run ran.
    // if date:time more than n hours ago (Arg)
    //   check if someone else running:
    //     if not, or that has timed out, run report run - see above.
    // load in cached data. if none: run report.
    // loop through each event type:
    //   loop through species.
    //     build JS function to create an array of vector features, point or multipoint, one per day, or false if not present
    //
    // Output controls above map: year, combined species/event.
    // --- Output map.
    // Output extra controls on map: 'Date represented'
    // --- Output extra controls under map: 'Beginning':'Step back':'Play'/'Pause':'Step forward':'End':Slider.
    // --- Normal controls: navigation pan and scrollwheel, zoombar and world:
    //         
    // possible form arg to specify month to stop at.
    // Form argument for first year of data: this year is last.
    // Add second map: own year control and species control. Tie the two together.
    // Move the layer switcher to the second map only, and only add if there is more than 1 layer.
/*
    $samples = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => array_merge($readAuth, array('view' => 'detail')),  // TODO add sample_method_id
        'nocache' => true
    )); */
    
    return $r;
  }

}
