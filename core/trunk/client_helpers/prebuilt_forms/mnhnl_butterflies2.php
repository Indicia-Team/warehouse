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
 * NB has Drupal specific code.
 * 
 * @package	Client
 * @subpackage PrebuiltForms
 * 
 * Should be set up in "wizard" buttons mode
 */

require_once('mnhnl_dynamic_1.php');

class iform_mnhnl_butterflies2 extends iform_mnhnl_dynamic_1 {
  protected static $locations;
  protected static $svcUrl;
  protected static $locationsInGrid;
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_butterflies2_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Butterflies de Jour form. Inherits from Dynamic 1.'
    );
  }
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Butterflies de Jour';  
  }

  public static function get_parameters() {    
    $retVal=array();
    $parentVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'max_species_ids',
          'caption'=>'max number of species to be returned by a search',
          'description'=>'The maximum number of species to be returned by the drop downs at any one time.',
          'default'=>25,
          'type'=>'int'
        ),
        array(
          'name'=>'attributeValidation',
          'caption'=>'Attribute Validation Rules',
          'description'=>'Client Validation rules to be enforced on attributes: allows more options than allowed by straight class led validation.',
          'type'=>'textarea',
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'LocationTypeID',
          'caption'=>'Site Location Type ID filter',
          'description'=>'Location Type ID for locations which are Sites ',
          'type'=>'int',
          'required' => true,
          'group' => 'User Interface'
        )
      )
    );
    foreach($parentVal as $param){
      if($param['name'] == 'structure'){
        $param['default'] =
             "=General=\r\n".
              "[lux5kgrid2]\r\n".
              "[recorder names]\r\n".
              "[year]\r\n".
              "[*]\r\n".
              "@sep= \r\n".
              "@lookUpKey=meaning_id\r\n".
              "[location buttons]\r\n".
              "[map]\r\n".
              "@layers=[\"ParentLocationLayer\",\"SiteListLayer\"]\r\n".
              "@editLayer=false\r\n".
              "@scroll_wheel_zoom=false\r\n".
              "@searchUpdatesSref=true\r\n".
              "[sample comment]\r\n".
             "=Conditions=\r\n".
              "[conditions grid]\r\n".
              "@sep= \r\n".
              "@lookUpKey=meaning_id\r\n".
              "@tabNameFilter=Conditions\r\n".
             "=Species=\r\n".
              "[species grid]\r\n".
              "[*]\r\n".
              "[late JS]";
      }
      if($param['name'] == 'attribute_termlist_language_filter')
        $param['default'] = true;
      if($param['name'] == 'grid_report')
        $param['default'] = 'reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies2';
        
      if($param['name'] != 'species_include_taxon_group' &&
          $param['name'] != 'link_species_popups' &&
          $param['name'] != 'species_include_both_names')
        $retVal[] = $param;
    }
    
    return $retVal;
  }
  
  public static function get_css() {
    return array('mnhnl_butterflies.css');
  }

  public static function get_perms($nid) {
    return array('IForm n'.$nid.' admin', 'IForm n'.$nid.' user');
  }
  
  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    global $indicia_templates;
  	$isAdmin = user_access('IForm n'.$node->nid.' admin');
  	if(!$isAdmin) return('');
  	if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'), '#locations' => lang::get('LANG_Locations'));
    $retVal = '<div id="downloads" >
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies2_conditions_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=downloadconditions">
      <p>'.lang::get('LANG_Conditions_Report_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
	<form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies2_species_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=downloadoccurrences">
      <p>'.lang::get('LANG_Occurrence_Report_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
  </div><div id="locations" ><p>'.lang::get("LANG_LocationModuleInstructions3").'</p>';
    $retVal .= "<form method=\"post\" id=\"entry_form\">".
          self::$auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />".
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"".$args['survey_id']."\" />";
    // at this point we assume that this admin user has access to all locations.
    $locOptions = array('validation' => array('required'),
    					'label'=>lang::get('LANG_Lux5kgrid'),
    					'id'=>'parent_id',
    					'table'=>'location',
    					'fieldname'=>'location:parent_id',
    					'valueField'=>'id',
    					'captionField'=>'name',
    					'template' => 'select',
    					'itemTemplate' => 'select_item',
    					'extraParams'=>array_merge($readAuth,
    						array('parent_id'=>'NULL',
    								'view'=>'detail',
    								'orderby'=>'name',
    								'location_type_id'=>$args['loctoolsLocTypeID'],
    								'deleted'=>'f')));
    $response = data_entry_helper::get_population_data($locOptions);
    if (isset($response['error'])) return $response['error'];
    $opts .= str_replace(array('{value}', '{caption}', '{selected}'),
                         array('', htmlentities(lang::get('LANG_Lux5kgrid_blank')), ''),
                         $indicia_templates[$locOptions['itemTemplate']]);
    if (!array_key_exists('error', $response)) {
      foreach ($response as $record) {
        $caption = htmlspecialchars($record[$locOptions['captionField']]);
        $opts .= str_replace(array('{value}', '{caption}', '{selected}'),
                               array($record[$locOptions['valueField']],
                                     htmlentities($record[$locOptions['captionField']]),
                                     isset(data_entry_helper::$entity_to_load['sample:location_id']) ? (data_entry_helper::$entity_to_load['sample:location_id'] == $record[$locOptions['valueField']] ? 'selected=selected' : '') : ''),
                               $indicia_templates[$locOptions['itemTemplate']]);
      }
    }
    $locOptions['items'] = $opts;
    $retVal .= data_entry_helper::apply_template($locOptions['template'], $locOptions).'<p>'.lang::get("LANG_LocationModuleInstructions4")."</p>
  <label for=\"location-id\">ID : </label><select id=\"location-id\" name=\"location:id\"><option >".lang::get("LANG_EmptyLocationID")."</option></select><br />
  <label for=\"location-name\">Name : </label><input id=\"location-name\" name=\"location:name\">
  <input type=\"hidden\" id=\"centroid_sref\" name=\"location:centroid_sref\" readonly=\"readonly\" >
  <input type=\"hidden\" id=\"centroid_sref_system\" name=\"location:centroid_sref_system\" readonly=\"readonly\" >
  <input type=\"hidden\" id=\"centroid_geom\" name=\"location:centroid_geom\" readonly=\"readonly\" >
  <input type=\"hidden\" id=\"boundary_geom\" name=\"location:boundary_geom\" readonly=\"readonly\" ><br />
  <input type=\"button\" value=\"".lang::get('Zoom to square (5x5 km)" ')."\" onclick=\"ZoomToDataExtent(ParentLocationLayer);\">
  <input type=\"button\" value=\"".lang::get('View All Luxembourg')."\" onclick=\"ViewAllLuxembourg(".$args['map_centroid_lat'].','.$args['map_centroid_long'].','.((int) $args['map_zoom']).");\">
  ";
    /* TBD Poss convert this to AJAX */
    // location ID is fixed and correct: don't include in form
    $options = iform_map_get_map_options($args,$readAuth);
    // For main page we force to Tabs
    $args['interface']='Tabs';
    $options['tabDiv'] = 'locations';
    $olOptions = iform_map_get_ol_options($args);
    $options['standardControls']=array('layerSwitcher','panZoom','modFeature');
    $options['layers']=array("ParentLocationLayer","SiteListLayer");
    $options['editLayer']=false;
    $retVal .= data_entry_helper::map_panel($options, $olOptions);
    $retVal .= '<input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Submit').'">
    </form></div>';
    data_entry_helper::$javascript .= "
// Create vector layers: one to display the Parent Square onto, and another for the site locations list
ParentLocStyleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({strokeColor: \"Yellow\",fillOpacity: 0,strokeWidth: 4})});
ParentLocationLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_ParentLocationLayer")."\",{styleMap: ParentLocStyleMap,displayInLayerSwitcher: false});
SiteLocStyleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({pointRadius: 3,fillColor: \"Red\",fillOpacity: 0.3,strokeColor: \"Red\",strokeWidth: 1})});
SiteListLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_SiteLocationLayer")."\",{styleMap: SiteLocStyleMap,displayInLayerSwitcher: false});
function onFeatureSelected(evt) {
  feature = evt.feature;
  SiteListLayer.map.zoomToExtent(feature.geometry.getBounds());
  jQuery('#location-id').val(feature.attributes.id);
  jQuery('#location-name').val(feature.attributes.name);
  jQuery('#centroid_sref').val(feature.attributes.centroid_sref);
  jQuery('#centroid_sref_system').val(feature.attributes.centroid_sref_system);
  jQuery('#centroid_geom').val(feature.attributes.centroid_geom);
  jQuery('#boundary_geom').val(feature.attributes.boundary_geom);
}
function onFeatureAdded(evt) {
  if(typeof modFeature == 'undefined'){
    modFeature = new OpenLayers.Control.ModifyFeature(SiteListLayer);
    SiteListLayer.map.addControl(modFeature);
    modFeature.activate();
  }
}
function onFeatureModified(evt) {
  feature = evt.feature;
  wkt = '';
  points = feature.geometry.components[0].getVertices();
  for(var i = 0; i< points.length; i++)
    wkt = wkt+(i==0? '' : ', ')+points[i].x+' '+points[i].y;
  wkt = wkt+', '+points[0].x+' '+points[0].y;
  jQuery('#boundary_geom').val(\"POLYGON((\" + wkt + \"))\");
  centre = feature.geometry.getCentroid();
  jQuery('#centroid_geom').val(\"POINT(\" + centre.x + \"  \" + centre.y + \")\");
  jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/spatial/wkt_to_sref?wkt=POINT(\" + centre.x + \"  \" + centre.y + \")&system=2169&precision=8&callback=?\",
    function(data){
      if(typeof data.error != 'undefined')
        alert(data.error);
      else
        jQuery('#centroid_sref').val(data.sref);});
}
SiteListLayer.events.on({
    'featureadded': onFeatureAdded,
    'featureselected': onFeatureSelected,
    'featuremodified': onFeatureModified
  });

loadFeatures = function(parent_id){
  ParentLocationLayer.destroyFeatures();
  SiteListLayer.destroyFeatures();
  jQuery('#location-id').val('');
  jQuery('#location-name').val('');
  jQuery('#centroid_sref').val('');
  jQuery('#centroid_geom').val('');
  jQuery('#boundary_geom').val('');
  jQuery('#location-id').find('option').not(':eq(0)').remove();
  if(parent_id != ''){
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/location/\"+parent_id+\"?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."&callback=?\",
      function(data) {
       if (data.length>0) {
         var parser = new OpenLayers.Format.WKT();
         if(data[0].boundary_geom){ // only one location if any
           feature = parser.read(data[0].boundary_geom)
           ParentLocationLayer.addFeatures([feature]);
           ParentLocationLayer.map.zoomToExtent(ParentLocationLayer.getDataExtent());
         }
       }});
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/location?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"]."&callback=?&orderby=id&location_type_id=".$args['LocationTypeID']."&parent_id=\"+parent_id,
      function(data) {
        if (data.length>0) {
          var parser = new OpenLayers.Format.WKT();
          for (var i=0;i<data.length;i++){
            if(data[i].boundary_geom){
              jQuery('<option value=\"'+data[i].id+'\">'+data[i].name+'</option>').appendTo('#location-id');
              feature = parser.read(data[i].boundary_geom); // assume map projection=900913
              feature.attributes = data[i];
              if(data[i].centroid_geom){
                centrefeature = parser.read(data[i].centroid_geom); // assume map projection=900913
                centrefeature.style = {label: data[i].name};
              } else {
                centre = feature.geometry.getCentroid();
                centrefeature = new OpenLayers.Feature.Vector(centre, {}, {label: data[i].name});
              }
              SiteListLayer.addFeatures([feature, centrefeature]);
            }}}});
}}
jQuery('#location-id').change(function() {
  for(var i=0; i<SiteListLayer.features.length; i++){
    if(SiteListLayer.features[i].attributes.id == $(this).val()){
      modFeature.selectControl.select(SiteListLayer.features[i]);
      break;
    }
  }
});
";
     data_entry_helper::$javascript .= "
jQuery(\"#parent_id\").change(function(){
  loadFeatures(this.value);
});";
    return $retVal;
  }
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
  	global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    if ($user->uid===0) return lang::get('LANG_Please_Login').'<a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">'.lang::get('LANG_Login').'</a>';
  	foreach($attributes as $attrId => $attr)
      if (strcasecmp($attr['untranslatedCaption'],'CMS User ID')==0) {
        $userIdAttr = $attr['attributeId'];
        break;
      }
    if (!isset($userIdAttr)) return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can be tagged against their creator.');
    foreach($attributes as $attrId => $attr)
      if (strcasecmp($attr['untranslatedCaption'],'CMS Username')==0) {
        $userNameAttr = $attr['attributeId'];
        break;
      }
    if (!isset($userNameAttr)) return lang::get('This form must be used with a survey that has the CMS User Name attribute associated with it so records can be tagged against their creator.');
    foreach($attributes as $attrId => $attr)
      if (strcasecmp($attr['untranslatedCaption'],'MNHNL Butterfly de Jour Passage')==0) {
        $passageAttr = $attr['attributeId'];
        break;
      }
    if (!isset($passageAttr)) return lang::get('This form must be used with a survey that has the MNHNL Butterfly de Jour Passage attribute associated with it.');    
      
    if (isset($args['grid_report'])) $reportName = $args['grid_report'];
    else // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies';
    $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>10,
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>(iform_loctools_checkaccess($node,'superuser') ? -1 :  $user->uid), // use -1 if superuser - non logged in will not get this far.
        'userName_attr_id'=>$userNameAttr
       ,'passage_attr_id'=>$passageAttr
        )));	
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= "</form>
<div style=\"display:none\" />
    <form id=\"form-delete-survey\" method=\"POST\">".self::$auth['write']."
       <input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />
       <input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />
       <input type=\"hidden\" name=\"sample:id\" value=\"\" />
       <input type=\"hidden\" name=\"sample:deleted\" value=\"t\" />
    </form>
</div>";
    data_entry_helper::$javascript .= "
deleteSurvey = function(sampleID){
  if(confirm(\"".lang::get('LANG_ConfirmSurveyDelete')." \"+sampleID)){
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/sample/\"+sampleID +
            \"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."\" +
            \"&callback=?\", function(data) {
        if (data.length>0) {
          jQuery('#form-delete-survey').find('[name=sample\\:id]').val(data[0].id);
          jQuery('#form-delete-survey').submit();
  }});
  };
};";
    
    return $r;
  }
  
  protected static function get_control_lux5kgrid2($auth, $args, $tabalias, $options) {
    // can only change the location for a new sample: fixed afterwards. *
    global $indicia_templates;
    $occAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'occurrence_attribute_value'
       ,'attrtable'=>'occurrence_attribute'
       ,'key'=>'occurrence_id'
       ,'fieldprefix'=>'occAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ), false);
    foreach($occAttributes as $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'Count')==0) {
        $countAttr = $attr['attributeId'];
        break;
      }
    }
    if (!isset($countAttr)) {
      return lang::get('This form must be used with a survey that has the Count Occurrence attribute associated with it.');
    }
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['newSample']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.http_build_query($reload['params']);
    data_entry_helper::$javascript .= "
jQuery('<div class=\"ui-widget-content ui-state-default ui-corner-all indicia-button tab-cancel\"><span><a href=\"".$reloadPath."\">".lang::get('LANG_Cancel')."</a></span></div>').appendTo('.buttons');
// Create vector layers: one to display the Parent Square onto, and another for the site locations list
ParentLocStyleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({strokeColor: \"Yellow\",fillOpacity: 0,strokeWidth: 4})});
ParentLocationLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_ParentLocationLayer")."\",{styleMap: ParentLocStyleMap,displayInLayerSwitcher: false});
SiteLocStyleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({pointRadius: 3,fillColor: \"Red\",fillOpacity: 0.3,strokeColor: \"Red\",strokeWidth: 1})});
SiteListLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_SiteLocationLayer")."\",{styleMap: SiteLocStyleMap,displayInLayerSwitcher: false});
loadFeatures = function(parent_id, addGridRows){
  ParentLocationLayer.destroyFeatures();
  SiteListLayer.destroyFeatures();
  if(parent_id != ''){
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/location/\"+parent_id+\"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?\",
      function(data) {
       if (data.length>0) {
         var parser = new OpenLayers.Format.WKT();
         if(data[0].boundary_geom){ // only one location if any
           feature = parser.read(data[0].boundary_geom)
           ParentLocationLayer.addFeatures([feature]);
           ParentLocationLayer.map.zoomToExtent(ParentLocationLayer.getDataExtent());
         }
         jQuery('#smp-loc-name').val(data[0].name);
       }});
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/location?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?&orderby=id&location_type_id=".$args['LocationTypeID']."&parent_id=\"+parent_id,
      function(data) {
        if (data.length>0) {
          var parser = new OpenLayers.Format.WKT();
          for (var i=0;i<data.length;i++){
            if(data[i].boundary_geom){
              feature = parser.read(data[i].boundary_geom); // assume map projection=900913
              feature.attributes.id = data[i].id;
              feature.attributes.boundary = data[i].boundary_geom;
              feature.attributes.centroid = data[i].centroid_geom;
              feature.attributes.sref = data[i].centroid_sref;
              feature.attributes.name = data[i].name;
              if(data[i].centroid_geom){
                centrefeature = parser.read(data[i].centroid_geom); // assume map projection=900913
                centrefeature.style = {label: data[i].name};
              } else {
                centre = feature.geometry.getCentroid();
                centrefeature = new OpenLayers.Feature.Vector(centre, {}, {label: data[i].name});
              }
              SiteListLayer.addFeatures([feature, centrefeature]);
            }
            if(addGridRows){
              cgRownum++;
              newCGrow = jQuery('.cgCloneableRow').clone().removeClass('cgCloneableRow').addClass('cgRow').data('rowNum', cgRownum);
              newCGrow.find('td:not(.cggrid-datecell,.cggrid-namecell,.remove-cgnewrow)').css('opacity',0.25);
              newCGrow.find('*:not(.cggrid-date,.cggrid-datecell,.cggrid-name,.cggrid-namecell,.remove-cgnewrow)').attr('disabled','disabled');
              jQuery.each(newCGrow.children(), function(i, cell) {
                cell.innerHTML = cell.innerHTML.replace(/--rownnum--/g, cgRownum);
              });
              newCGrow.find('.cggrid-name').val(data[i].name);
              newCGrow.find('.remove-cgnewrow').removeClass('remove-cgnewrow').addClass('clear-cgrow');
              newCGrow.find('.cggrid-centroid_sref,.cggrid-centroid_geom,.cggrid-boundary_geom,.cggrid-location_type_id').remove();
              newCGrow.find('.cggrid-namecell').append('<input type=\"hidden\" name=\"CG:'+cgRownum+':--sampleid--:location_id\" value=\"'+data[i].id+'\" >');
              newCGrow.appendTo('#conditions-grid');
              newCGrow.find('.cggrid-date').datepicker({dateFormat : 'dd/mm/yy', changeMonth: true, changeYear: true, constrainInput: false, maxDate: '0', onClose: function() { $(this).valid(); }});
              // Species grid 1) add to header, 2) add to cloneable row, 3) assume no species existing rows
              jQuery('<th class=\"smp-'+cgRownum+'\">'+data[i].name+'</th>').css('opacity',0.25).appendTo('#species-grid-header');
              jQuery('<td class=\"smp-'+cgRownum+'\"><input class=\"digits narrow\" name=\"SG:'+cgRownum+':--sampleid--:--ttlid--:--occid--:occAttr:".$countAttr."\" disabled=\"disabled\" ></td>').css('opacity',0.25).appendTo('.sgCloneableRow');
            }
  }}});
  }
}";
    $retVal = '<input type="hidden" id="imp-sref-system" name="location:centroid_sref_system" value="2169" >';// TBD value configurable
    if(isset(data_entry_helper::$entity_to_load["sample:updated_by_id"])){ // only set if data loaded from db, not error condition
      data_entry_helper::load_existing_record($auth['read'], 'location', data_entry_helper::$entity_to_load["sample:location_id"]);
      data_entry_helper::$javascript .= "
loadFeatures(".data_entry_helper::$entity_to_load["sample:location_id"].", false);";
      return $retVal.'
<input type="hidden" name ="sample:location_id" value="'.data_entry_helper::$entity_to_load["sample:location_id"].'" >
<p>'.lang::get('LANG_Lux5kgrid').' : '.data_entry_helper::$entity_to_load["location:name"].'</p>
<p>'.lang::get("LANG_LocationModuleInstructions2").'</p>
<p>'.lang::get('LANG_NumSites').' : <span id="num-sites"></span></p>
';
    }
    $retVal .= '<p>'.lang::get("LANG_LocationModuleInstructions1").'</p><input type="hidden" id="smp-loc-name" name="sample:location_name" value="" >';
    self::$locations = iform_loctools_listlocations(self::$node);
    $locOptions = array('validation' => array('required'),
    					'label'=>lang::get('LANG_Lux5kgrid'),
    					'id'=>'location_id',
    					'table'=>'location',
    					'fieldname'=>'sample:location_id',
    					'valueField'=>'id',
    					'captionField'=>'name',
    					'template' => 'select',
    					'itemTemplate' => 'select_item',
    					'extraParams'=>array_merge($auth['read'],
    						array('parent_id'=>'NULL',
    								'view'=>'detail',
    								'orderby'=>'name',
    								'location_type_id'=>$args['loctoolsLocTypeID'],
    								'deleted'=>'f')));
    $response = data_entry_helper::get_population_data($locOptions);
    if (isset($response['error'])) return $response['error'];
    $opts .= str_replace(array('{value}', '{caption}', '{selected}'),
                         array('', htmlentities(lang::get('LANG_Lux5kgrid_blank')), ''),
                         $indicia_templates[$locOptions['itemTemplate']]);
    if (!array_key_exists('error', $response)) {
      foreach ($response as $record) {
        $include=false;
        if(self::$locations == 'all') $include = true;
        else if(in_array($record["id"], self::$locations)) $include = true;
        if($include == true){
          $caption = htmlspecialchars($record[$locOptions['captionField']]);
          $opts .= str_replace(array('{value}', '{caption}', '{selected}'),
                               array($record[$locOptions['valueField']],
                                     htmlentities($record[$locOptions['captionField']]),
                                     isset(data_entry_helper::$entity_to_load['sample:location_id']) ? (data_entry_helper::$entity_to_load['sample:location_id'] == $record[$locOptions['valueField']] ? 'selected=selected' : '') : ''),
                               $indicia_templates[$locOptions['itemTemplate']]);
        }
      }
    }
    $locOptions['items'] = $opts;
    $retVal .= data_entry_helper::apply_template($locOptions['template'], $locOptions).'<p>'.lang::get("LANG_LocationModuleInstructions2").'</p>';
    data_entry_helper::$javascript .= "
jQuery(\"#location_id\").change(function(){
  drawControl.activate();
  jQuery('#conditions-grid').find('tr').not(':eq(0)').remove();
  jQuery('#species-grid').find('tr').not(':eq(0)').remove();
  jQuery('#species-grid').find('th').not(':eq(0)').remove();
  jQuery('.sgCloneableRow').find('td:gt(1)').remove();
  cgRownum=0;
  loadFeatures(this.value, true);
});";
    return $retVal.'<p>'.lang::get('LANG_NumSites').' : <span id="num-sites"></span></p>';
  }

  
  protected static function get_control_locationbuttons($auth, $args, $tabalias, $options) {
    return '<input type="button" value="'.lang::get('Zoom To Grid').'" onclick="ZoomToDataExtent(ParentLocationLayer);">
<input type="button" value="'.lang::get('View All Luxembourg').'" onclick="ViewAllLuxembourg('.$args['map_centroid_lat'].','.$args['map_centroid_long'].','.((int) $args['map_zoom']).');">';
  }

  /**
   * Get the recorder names control
   */
  protected static function get_control_recordernames($auth, $args, $tabalias, $options) {
    $values = array();
  	$userlist = array();
    $results = db_query('SELECT uid, name FROM {users}');
    while($result = db_fetch_object($results)){
    	$account = user_load($result->uid);
    	if($account->uid != 1 && user_access('IForm n'.self::$node->nid.' user', $account)){
			$userlist[$result->name] = $result->name;
		}
    }
    if (isset(data_entry_helper::$entity_to_load['sample:recorder_names'])){
      if(!is_array(data_entry_helper::$entity_to_load['sample:recorder_names']))
        $values = explode("\r\n", data_entry_helper::$entity_to_load['sample:recorder_names']);
      else
        $values = data_entry_helper::$entity_to_load['sample:recorder_names'];
    }
    foreach($values as $value){
      $userlist[$value] = $value;
    }
    $r = data_entry_helper::listbox(array_merge(array(
      'id'=>'sample:recorder_names',
      'fieldname'=>'sample:recorder_names[]',
      'label'=>lang::get('Recorder names'),
      'size'=>6,
      'multiselect'=>true,
      'default'=>$values,
      'lookupValues'=>$userlist
      ,'validation'=>array('required')
    ), $options));
    return $r."<span>".lang::get('LANG_RecorderInstructions')."</span><br />";
  }
  
  /**
   * Get the recorder names control
   */
  protected static function get_control_year($auth, $args, $tabalias, $options) {
    if($args['language'] != 'en')
      data_entry_helper::add_resource('jquery_ui_'.$args['language']); // this will autoload the jquery_ui resource. The date_picker does not have access to the args.
    if (isset(data_entry_helper::$entity_to_load['sample:date']))
      $year=substr(data_entry_helper::$entity_to_load['sample:date'],0,4);
//    else if (isset(data_entry_helper::$entity_to_load['sample:date'])){
//      $year=explode('/',data_entry_helper::$entity_to_load['sample:date']);
//      $year = $year[2];
//    }
    else $year = "";
    return "<label for=\"sample:date\">".lang::get('Year').":</label><input type=\"text\" size=\"4\" class=\" required digits\" min=\"2000\" id=\"sample:date\" name=\"sample:date\" value=\"".$year."\" /><span class=\"deh-required\">*</span><br/>";
  }

  private static function getLocationsInGrid($auth)
  {
    if(isset(self::$locationsInGrid)) return self::$locationsInGrid;
    if(isset(data_entry_helper::$entity_to_load["sample:updated_by_id"])){ // only set if data loaded from db, not error condition
      $url = data_entry_helper::$base_url."/index.php/services/data/sample?parent_id=".data_entry_helper::$entity_to_load['sample:id']."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $sampleEntities = json_decode(curl_exec($session), true);
      $url = data_entry_helper::$base_url."/index.php/services/data/location?parent_id=".data_entry_helper::$entity_to_load['sample:location_id']."&mode=json&view=detail&orderby=id&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $locationEntities = json_decode(curl_exec($session), true);
      self::$locationsInGrid=array();
      // merge the locations and existing subsamples lists
      if (isset($locationEntities))
        foreach($locationEntities as $entity){
          self::$locationsInGrid[intval($entity['id'])] = array('location_id'=>$entity['id'], 'name'=>$entity['name']);
        }
      if (isset($sampleEntities))
        foreach($sampleEntities as $entity){
          $id=intval($entity['location_id']);
          if(!isset(self::$locationsInGrid[$id]))
            self::$locationsInGrid[$id] = array('location_id'=>$entity['location_id']);
          self::$locationsInGrid[$id]['name'] = $entity['location_name'];
          self::$locationsInGrid[$id]['date'] = $entity['date_start'];
          self::$locationsInGrid[$id]['comment'] = $entity['comment'];
          self::$locationsInGrid[$id]['sample_id'] = $entity['id'];
        }
      self::$locationsInGrid = array_values(self::$locationsInGrid);
    }
    return self::$locationsInGrid;
  }
  
  protected static function get_control_speciesgrid($auth, $args, $tabalias, $options) {
  	/* We will make the assumption that only one of these will be put onto a form.
  	 * A lot of this is copied from the species control and has the same features. */
  	$occAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'occurrence_attribute_value'
       ,'attrtable'=>'occurrence_attribute'
       ,'key'=>'occurrence_id'
       ,'fieldprefix'=>'occAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ), false);
    foreach($occAttributes as $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'Count')==0) {
        $countAttr = $attr['attributeId'];
        break;
      }
    }
    if (!isset($countAttr)) {
      return lang::get('This form must be used with a survey that has the Count Occurrence attribute associated with it.');
    }
    $subsamples = self::getLocationsInGrid($auth);
    $ret = '<p>'.lang::get("LANG_SpeciesGridInstructions").'</p><table style="display:none">';
    $cloneprefix='SG:--rownnum--:--sampleid--:--ttlid--:--occid--:';
    $ret .= "<tr class=\"sgCloneableRow\">
<td class=\"ui-state-default remove-sgnewrow\" style=\"width: 1%\">X</td><td class=\"sggrid-namecell\"></td>";
    $taxonList = array();
    if (isset($subsamples))
      foreach($subsamples as $key => $entity){
        $ret .= str_replace(array('--rownnum--', '--sampleid--'),
                         array($key+1, $entity['sample_id']),
                         '<td class="smp---rownnum--" '.(isset($entity['date']) ? '' : 'style="opacity: 0.25"').'><input class="digits narrow" name="'.$cloneprefix.'occAttr:'.$countAttr.'" '.(isset($entity['date']) ? '' : 'disabled="disabled"').' ></td>');
        if(isset($entity['sample_id'])){
          $url = data_entry_helper::$base_url."/index.php/services/data/occurrence?sample_id=".$entity['sample_id']."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $occs = json_decode(curl_exec($session), true);
          $occList = array();
          $subsampleOccs = array();
          foreach($occs as $an_occ) {
            $occList[] = $an_occ['id'];
            $taxonList[$an_occ['taxa_taxon_list_id']]=true;
            $subsampleOccs[$an_occ['taxa_taxon_list_id']]=$an_occ; // this indexes the occurrences for the subsample by taxa_taxon_list_id
          }
          $subsamples[$key]['occurrences'] = $subsampleOccs;
          $url = data_entry_helper::$base_url."/index.php/services/data/occurrence_attribute_value?query=".json_encode(array('in' => array('occurrence_id', $occList)))."&mode=json&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $occAttributes = json_decode(curl_exec($session), true);
          $subsampleOccAttrs = array();
          foreach($occAttributes as $an_occAttr) {
            if($an_occAttr['occurrence_attribute_id'] == $countAttr)
              $subsampleOccAttrs[$an_occAttr['occurrence_id']]=$an_occAttr;
          }
          $subsamples[$key]['occattrs'] = $subsampleOccAttrs;
        } else $subsamples[$key]['occurrences'] = array();
      }
    $ret .= '</table>';
    /* We will make the assumption that only one of these will be put onto a form.
     * A lot of this is copied from the species control and has the same features. */
    $extraParams = $auth['read'] + array('view' => 'detail', 'reset_timeout' => 'true');
    if ($args['species_names_filter']=='preferred') {
      $extraParams += array('preferred' => 't');
    }
    if ($args['species_names_filter']=='language') {
      $extraParams += array('language_iso' => iform_lang_iso_639_2($user->lang));
    }  
    // A single species entry control of some kind
    if ($args['extra_list_id']=='')
      $extraParams['taxon_list_id'] = $args['list_id'];
    elseif ($args['species_ctrl']=='autocomplete')
      $extraParams['taxon_list_id'] = $args['extra_list_id'];
    else
      $extraParams['taxon_list_id'] = array($args['list_id'], $args['extra_list_id']);
    $species_list_args=array_merge(array(
          'label'=>lang::get('speciesgrid:taxa_taxon_list_id'),
          'fieldname'=>'speciesgrid_taxa_taxon_list_id',
          'id'=>'speciesgrid_taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,          
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'numValues'=>$args['max_species_ids']
    ), $options);
    // do not allow tree browser
    if ($args['species_ctrl']=='tree_browser')
      return '<p>Can not use tree browser in this context</p>';
    $ret .= '<div>'.call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args).'</div>';
    $ret .= '<table id="species-grid"><tr id="species-grid-header"><th colSpan=2>'.lang::get('Species').'</th>';
    if (isset($subsamples))
      foreach($subsamples as $key => $entity){
        $ret .= '<th class="smp-'.($key+1).'" '.(isset($entity['date']) ? '' : 'style="opacity: 0.25"').'>'.$entity['name'].'</th>';
      }
    $ret .= '</tr>';
    $taxonRow=0;
    foreach($taxonList as $ttlid=>$disgard){
      $url = data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list?id=".$ttlid."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $taxon = json_decode(curl_exec($session), true);
      $url = data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list?taxon_meaning_id=".$taxon[0]['taxon_meaning_id']."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $allnames = json_decode(curl_exec($session), true);
      $name = '';
      foreach($allnames as $aname){
        if($aname['preferred'] == 't')
          $name = '<em>'.$aname['taxon'].'</em>'.($name!=''?', ':'').$name;
        else
          $name = $name.($name!=''?', ':'').$aname['taxon'];
      }
      $taxonRow++;
      data_entry_helper::$javascript .= "
jQuery('#species-grid').find('tr:eq(".$taxonRow.")').data('ttlid', ".$ttlid.").data('meaning_id', ".$taxon[0]['taxon_meaning_id'].");";
      $ret .= '
<tr class="sgOrigRow"><td class="ui-state-default clear-sgrow" style="width: 1%">X</td><td class="sggrid-namecell">'.$name.'</td>';
      foreach($subsamples as $key => $entity){
        $template = '<td class="smp---rownnum--" '.(isset($entity['date']) ? '' : 'style="opacity: 0.25"').'><input class="digits narrow" name="'.$cloneprefix.'occAttr:'.$countAttr.'--attrid--" '.(isset($entity['date']) ? '' : 'disabled="disabled"').' value="--value--" ></td>';
        if(isset($entity['occurrences'][$ttlid])){
          $occid=$entity['occurrences'][$ttlid]['id'];
          $ret .= str_replace(array('--rownnum--', '--sampleid--','--ttlid--','--occid--','--attrid--','--value--'),
                         array($key+1, $entity['sample_id'], $ttlid, $occid, isset($entity['occattrs'][$occid]) ? ':'.$entity['occattrs'][$occid]['id'] : '', isset($entity['occattrs'][$occid]) ? $entity['occattrs'][$occid]['value'] : ''),
                         $template);
        } else
          $ret .= str_replace(array('--rownnum--', '--sampleid--','--ttlid--','--attrid--','--value--'),
                         array($key+1, isset($entity['sample_id']) ? $entity['sample_id'] : '', $ttlid, '',''),
                         $template);
      }
      $ret .= '</tr>';
    }
    $ret .= '<tr class="sgNoObRow" ><td colspan=2>'.lang::get('No Observation').'</td>';
    if (isset($subsamples))
      foreach($subsamples as $key => $entity){
      // pretend that the no observations are actually part of the conditions grid.
        $attrArgs = array(
         'valuetable'=>'sample_attribute_value',
         'attrtable'=>'sample_attribute',
         'key'=>'sample_id',
         'fieldprefix'=>'smpAttr',
         'extraParams'=>$auth['read'],
         'survey_id'=>$args['survey_id']
        );
        $defAttrOptions = array_merge(
              array('cellClass' => 'smp-'.($key+1),
                'class' => 'cgAttr',
                'extraParams' => array_merge($auth['read'], array('view'=>'detail')),
                'language' => iform_lang_iso_639_2($args['language'])),$options);
        if(isset($entity['sample_id'])) $attrArgs['id'] = $entity['sample_id'];
        $attrArgs['fieldprefix']='CG:'.($key+1).':'.(isset($entity['sample_id']) ? $entity['sample_id'] : '').':smpAttr';
        $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
        $ret .= self::get_sample_attribute_html($sampleAttributes, $args, $defAttrOptions, 'Species');
        // validation has to be put on hidden element in the checkbox group/
       data_entry_helper::$late_javascript .= "
jQuery('.sgNoObRow').find(':checkbox:eq(".$key.")').rules('add', {no_observation: ".($key+1)."});";
      }
    $ret .= '</tr></table>';
    // remembering that validation for checkbox is actually called on the hidden element, not the checkbox itself.
    data_entry_helper::$late_javascript .= "
$.validator.addMethod('no_observation', function(value, element, params){
  if(jQuery('[name='+jQuery(element).attr('name')+']').not(':hidden').filter('[disabled=true]').length>0) return true;
  var numFilledIn = jQuery('.smp-'+params).find('.digits').filter('[value!=]').length;
  if(jQuery('[name='+jQuery(element).attr('name')+']').not(':hidden').filter('[checked=true]').length>0)
    return(numFilledIn==0);
  else
    return(numFilledIn>0);
}, \"".lang::get('validation_no_observation')."\");
";

  data_entry_helper::$javascript .= "
jQuery('#speciesgrid_taxa_taxon_list_id').change(function(){
  jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list/\" +jQuery('#speciesgrid_taxa_taxon_list_id').val()+
    \"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?\",
    function(tdata) {
      jQuery('#speciesgrid_taxa_taxon_list_id\\\\:taxon').val('');
      if (tdata.length>0) {
        found=false;
        jQuery('#species-grid').find('tr').each(function(i, row){
          if(tdata[0].taxon_meaning_id == jQuery(row).data('meaning_id'))
            found=true;
        });
        if(found){
          alert(\"".lang::get('speciesgrid:rowexists')."\");
          return;
        }
        newSGrow = jQuery('.sgCloneableRow').clone().removeClass('sgCloneableRow').addClass('sgAddedRow');
        jQuery.each(newSGrow.children(), function(i, cell) {
          cell.innerHTML = cell.innerHTML.replace(/--ttlid--/g, tdata[0].id);
        });
        newSGrow.find('.sggrid-namecell').append(tdata[0].taxon);
        newSGrow.data('ttlid',tdata[0].id).data('meaning_id',tdata[0].taxon_meaning_id).insertBefore('.sgNoObRow');
        jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list\" +
            \"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&taxon_meaning_id=\" + tdata[0].taxon_meaning_id + \"&callback=?\",
          function(ldata) {
            if (ldata.length>0) {
              var name = '';
              for(var i=0; i< ldata.length; i++){
                if(ldata[i].preferred == 't')
                  name = '<em>'+ldata[i].taxon+'</em>'+(i>0?', ':'')+name;
                else
                  name = name+(i>0?', ':'')+ldata[i].taxon;
              }
              jQuery('#species-grid').find('tr').each(function(i, row){
                if(ldata[0].taxon_meaning_id == jQuery(row).data('meaning_id'))
                  jQuery(row).find('.sggrid-namecell').empty().append(name);});
            }});
        }});
});
jQuery('.clear-sgrow').live('click', function() {
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_speciesgrid:clearconfirm')."\")) return;
  thisRow.find('*').removeClass('ui-state-error');
  thisRow.find(':text').val('');
  thisRow.find('.inline-error').remove();
});
jQuery('.remove-sgnewrow').live('click', function() {
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_speciesgrid:removeconfirm')."\")) return;
  thisRow.remove();
});";
    return $ret;
  }
  
  protected static function get_control_conditionsgrid($auth, $args, $tabalias, $options) {
  	/* We will make the assumption that only one of these will be put onto a form.
  	 * A lot of this is copied from the species control and has the same features. */
    $extraParams = $auth['read'] + array('view' => 'detail', 'reset_timeout' => 'true');
    // A single species entry control of some kind
    $attrArgs = array(
       'valuetable'=>'sample_attribute_value',
       'attrtable'=>'sample_attribute',
       'key'=>'sample_id',
       'fieldprefix'=>'smpAttr',
       'extraParams'=>$auth['read'],
       'survey_id'=>$args['survey_id']
      );
    $defAttrOptions = array_merge(
              array('class' => 'cgAttr',
                'extraParams' => array_merge($auth['read'], array('view'=>'detail')),
                'language' => iform_lang_iso_639_2($args['language'])),$options);
    $tabName = (isset($options['tabNameFilter']) ? $options['tabNameFilter'] : null);
    $ret = '<p>'.lang::get("LANG_ConditionsGridInstructions").'</p><table style="display:none">';
    $cloneprefix='CG:--rownnum--:--sampleid--:';
    $ret .= "<tr class=\"cgCloneableRow\">
<td class=\"ui-state-default remove-cgnewrow\" style=\"width: 1%\">X</td>
<td class=\"cggrid-namecell\"><input name=\"".$cloneprefix."name\" class=\"cggrid-name\" ><input type=\"hidden\" name=\"".$cloneprefix."location:centroid_sref\" class=\"cggrid-centroid_sref\" ><input type=\"hidden\" name=\"".$cloneprefix."location:centroid_geom\" class=\"cggrid-centroid_geom\" ><input type=\"hidden\" name=\"".$cloneprefix."location:boundary_geom\" class=\"cggrid-boundary_geom\" ><input type=\"hidden\" name=\"".$cloneprefix."location:location_type_id\" class=\"cggrid-location_type_id\" value=\"".$args['LocationTypeID']."\"></td>
<td class=\"cggrid-datecell\"><input name=\"".$cloneprefix."date\" class=\"cggrid-date customDate\" value=\"\" ></td>";
    unset($attrArgs['id']);
    $attrArgs['fieldprefix']=$cloneprefix.'smpAttr';
    $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $ret .= self::get_sample_attribute_html($sampleAttributes, $args, $defAttrOptions, $tabName)."<td><input name=\"".$cloneprefix."comment\" class=\"cggrid-comment\" ></td></tr>";
//    throw(1);
    $ret .= '</table><table id ="conditions-grid"><tr><th colSpan=2></th><th>'.lang::get('Date').'</th>';
    foreach($sampleAttributes as $attr){
      if ((!isset($options['tabNameFilter']) || strcasecmp($options['tabNameFilter'],$attr['inner_structure_block'])==0))
        $ret .= '<th>'.$attr['caption'].'</th>';
    }
    $ret .= '<th>'.lang::get('Comment').'</th></tr>';
    $rowNum=0;
    data_entry_helper::$javascript .= "cgRownum=0;";
    if(isset(data_entry_helper::$entity_to_load["sample:updated_by_id"])){ // only set if data loaded from db, not error condition
      $subsamples = self::getLocationsInGrid($auth);
      if (isset($subsamples))
        foreach($subsamples as $entity){
          $rowNum++;
          data_entry_helper::$javascript .= "
jQuery('#conditions-grid').find('tr:eq(".$rowNum.")').data('rowNum', ".$rowNum.");
jQuery('#conditions-grid').find('tr:eq(".$rowNum.")').find('.cggrid-date').datepicker({dateFormat : 'dd/mm/yy', changeMonth: true, changeYear: true, constrainInput: false, maxDate: '0', onClose: function() { $(this).valid(); }});
";
          if (isset($entity['sample_id'])){
            $fieldprefix='CG:'.$rowNum.':'.$entity['sample_id'].':';
            $attrArgs['id'] = $entity['sample_id'];
          } else {
            $fieldprefix='CG:'.$rowNum.':--sampleid--:';
            unset($attrArgs['id']);
          }
          if (isset($entity['date']) && preg_match('/^(\d{4})/', $entity['date'])) {
            // Date has 4 digit year first (ISO style) - convert date to expected output format
            // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
            $d = new DateTime($entity['date']);
            $entity['date'] = $d->format('d/m/Y');
          }
          $ret .= "<tr class=\"cggrid-row\"><td class=\"ui-state-default clear-cgrow\" style=\"width: 1%\">X</td><td class=\"cggrid-namecell\"><input name=\"".$fieldprefix."name\" class=\"cggrid-name\" value=\"".$entity['name']."\" readonly=\"readonly\" ></td><td class=\"cggrid-datecell\"><input type=\"hidden\" name=\"".$fieldprefix."location_id\" value=\"".$entity['location_id']."\" class=\"cggrid-location_id\" ><input name=\"".$fieldprefix."date\" class=\"cggrid-date customDate\" value=\"".$entity['date']."\" ></td>";
          $attrArgs['fieldprefix']=$fieldprefix.'smpAttr';
          $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
          $ret .= self::get_sample_attribute_html($sampleAttributes, $args, $defAttrOptions, $tabName);
          $ret .= "<td><input name=\"".$fieldprefix."comment\" class=\"cggrid-comment\" value=\"".$entity['comment']."\" ></td></tr>";
        }
    }
    $ret .= '</table>';
    data_entry_helper::$onload_javascript .= "
cgRownum=$rowNum;";
    data_entry_helper::$javascript .= "
recalcNumSites = function(){
 jQuery('#num-sites').empty().append(jQuery('.cggrid-row,.cgAddedRow').length);};
recalcNumSites();
if (typeof jQuery.validator !== \"undefined\") {
  jQuery.validator.addMethod('customDate',
    function(value, element) {
      // parseDate throws exception if the value is invalid
      try{jQuery.datepicker.parseDate( 'dd/mm/yy', value);return true;}
      catch(e){return false;}
    }, '".lang::get('Please enter a valid date')."'
  );
}
jQuery('.cggrid-row').each(function(index, Element) {
  if(jQuery(this).find('.cggrid-date').val()==\"\"){
    jQuery(this).find('td:not(.cggrid-datecell,.cggrid-namecell)').css('opacity',0.25);
    // disable all  active controls from the row apart from the date.
    // Do NOT disable the date or the container td, otherwise it is not submitted.
    jQuery(this).find('*:not(.cggrid-date,.cggrid-datecell,.cggrid-name,.cggrid-namecell)').attr('disabled','disabled');
  }
});
jQuery('.cggrid-name').live('change', function() {
  rowNum = jQuery(this).closest('tr').data('rowNum');
  jQuery('#species-grid-header').find('.smp-'+rowNum).empty().append(jQuery(this).val());
  for(var i=0; i<SiteListLayer.features.length; i++){
    if(SiteListLayer.features[i].attributes.row == rowNum){
      newFeature = SiteListLayer.features[i].clone();
      newFeature.style.label = jQuery(this).val();
      SiteListLayer.destroyFeatures([SiteListLayer.features[i]]);
      SiteListLayer.addFeatures([newFeature]);
      break;
    }
  }
});
jQuery('.cggrid-date').live('change', function() {
  jQuery(this).closest('tr').find('td:not(.cggrid-datecell,.cggrid-namecell)').css('opacity','');
  jQuery(this).addClass('required');
  jQuery(this).closest('tr').find('*').removeAttr('disabled');
  rowNum = jQuery(this).closest('tr').data('rowNum');
  jQuery('.smp-'+rowNum).css('opacity','').find('input').removeAttr('disabled');
});
jQuery('.clear-cgrow').live('click', function() {
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_conditionsgrid:clearconfirm')."\")) return;
  thisRow.find('td:not(.cggrid-datecell,.cggrid-namecell)').css('opacity',0.25);
  thisRow.find(':checkbox').attr('checked',false);
  thisRow.find('*').removeClass('required').removeClass('ui-state-error');
  thisRow.find(':text').filter('*:not(.cggrid-name)').val('');
  thisRow.find('select').val('');
  thisRow.find('.inline-error').remove();
  thisRow.find('*:not(.cggrid-date,.cggrid-datecell,.cggrid-name,.cggrid-namecell)').attr('disabled','disabled');
  rowNum = thisRow.data('rowNum');
  jQuery('.smp-'+rowNum).css('opacity',0.25).find(':text').attr('disabled','disabled').val('');
  jQuery('.smp-'+rowNum).css('opacity',0.25).find(':checkbox').attr('disabled','disabled').attr('checked','');
});
jQuery('.remove-cgnewrow').live('click', function() {
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_conditionsgrid:removeconfirm')."\")) return;
  rowNum = thisRow.data('rowNum');
  jQuery('.smp-'+rowNum).remove();
  thisRow.remove();
  recalcNumSites();
  for(var i=0; i<SiteListLayer.features.length; i++){
    if(SiteListLayer.features[i].attributes.row == rowNum){
      SiteListLayer.destroyFeatures([SiteListLayer.features[i]]);
      break;
    }
  }
  cgRownum=0;
  jQuery('.cggrid-row,.cgAddedRow').each(function(i,thisRow){
    if(jQuery(thisRow).data('rowNum') > cgRownum)
      cgRownum=jQuery(thisRow).data('rowNum');
  });
});";
    return $ret;
  }

  // This function pays no attention to the outer block. This is needed when the there is no outer/inner block pair, 
  // if the attribute is put in a single block level, then that block appears in the inner, rather than the outer .
  private function get_sample_attribute_html($attributes, $args, $defAttrOptions, $blockFilter=null, $blockOptions=null) {
   $r = '';
   if(!isset($attributes)) return $r;
   foreach ($attributes as $attribute) {
    // Apply filter to only output 1 block at a time. Also hide controls that have already been handled.
    if (($blockFilter===null || strcasecmp($blockFilter,$attribute['inner_structure_block'])==0) && !isset($attribute['handled'])) {
      $options = $defAttrOptions + get_attr_validation($attribute, $args);
      if (isset($blockOptions[$attribute['fieldname']])) {
        $options = array_merge($options, $blockOptions[$attribute['fieldname']]);
      }
      unset($attribute['caption']);
      $r .= '<td '.(isset($defAttrOptions['cellClass'])? 'class="'.$defAttrOptions['cellClass'].'"' : '').'>'.data_entry_helper::outputAttribute($attribute, $options).'</td>';
      $attribute['handled']=true;
    }
   }
   return $r;
  }

  protected static function get_control_lateJS($auth, $args, $tabalias, $options) {
    $occAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'occurrence_attribute_value'
       ,'attrtable'=>'occurrence_attribute'
       ,'key'=>'occurrence_id'
       ,'fieldprefix'=>'occAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ), false);
    foreach($occAttributes as $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'Count')==0) {
        $countAttr = $attr['attributeId'];
        break;
      }
    }
    if (!isset($countAttr)) {
      return lang::get('This form must be used with a survey that has the Count Occurrence attribute associated with it.');
    }
  	$smpAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ), false);
    foreach($smpAttributes as $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'No observation')==0) {
        $noObAttr = $attr['attributeId'];
        break;
      }
    }
    if (!isset($noObAttr)) {
      return lang::get('This form must be used with a survey that has the No observation Sample attribute associated with it.');
    }
  	data_entry_helper::$onload_javascript .= "addDrawnGeomToSelection = function(geometry) {
  cgRownum++;
  newCGrow = jQuery('.cgCloneableRow').clone().removeClass('cgCloneableRow').addClass('cgAddedRow').data('rowNum', cgRownum);
  newCGrow.find('td:not(.cggrid-datecell,.cggrid-namecell,.remove-cgnewrow)').css('opacity',0.25);
  newCGrow.find('*:not(.cggrid-date,.cggrid-datecell,.cggrid-name,.cggrid-namecell,.remove-cgnewrow)').attr('disabled','disabled');
  jQuery.each(newCGrow.children(), function(i, cell) {
    cell.innerHTML = cell.innerHTML.replace(/--rownnum--/g, cgRownum);
  });
  newCGrow.find('.cggrid-name').val(cgRownum);
  newCGrow.appendTo('#conditions-grid');
  newCGrow.find('.cggrid-date').datepicker({dateFormat : 'dd/mm/yy', changeMonth: true, changeYear: true, constrainInput: false, maxDate: '0', onClose: function() { $(this).valid(); }});
  recalcNumSites();
  // Species grid 1) add to header, 2) add to cloneable row, 3) add to existing rows
  jQuery('<th class=\"smp-'+cgRownum+'\">'+cgRownum+'</th>').css('opacity',0.25).appendTo('#species-grid-header');
  jQuery('<td class=\"smp-'+cgRownum+'\"><input class=\"digits narrow\" name=\"SG:'+cgRownum+':--sampleid--:--ttlid--:--occid--:occAttr:".$countAttr."\" disabled=\"disabled\" ></td>').css('opacity',0.25).appendTo('.sgCloneableRow');
  jQuery('.sgAddedRow,.sgOrigRow').each(function(i, Row) {
    jQuery('<td class=\"smp-'+cgRownum+'\"><input class=\"digits narrow\" name=\"SG:'+cgRownum+':--sampleid--:'+jQuery(Row).data('ttlid')+':--occid--:occAttr:".$countAttr."\" disabled=\"disabled\" ></td>').css('opacity',0.25).appendTo(Row);
  });
  jQuery('.sgNoObRow').each(function(i, Row) {
    jQuery('<td class=\"smp-'+cgRownum+'\">'+
      '<input type=\"hidden\" name=\"CG:'+cgRownum+':--sampleid--:smpAttr:".$noObAttr."\" value=\"0\" \"/>'+
      '<input type=\"checkbox\" name=\"CG:'+cgRownum+':--sampleid--:smpAttr:".$noObAttr."\" value=\"1\" class=\"cgAttr\" disabled=\"disabled\" />'+
      '</td>').css('opacity',0.25).appendTo(Row);
    jQuery(Row).find('.smp-'+cgRownum).find(':checkbox').rules('add', {no_observation: cgRownum});
  });
  // Create the polygon as drawn, only 1 polygon at a time
  var feature = new OpenLayers.Feature.Vector(geometry, {row: cgRownum}, {label: cgRownum.toString(), pointRadius: 3,fillColor: \"Blue\",fillOpacity: 0.3,strokeColor: \"Red\",strokeWidth: 1});
  SiteListLayer.addFeatures([feature]);
  wkt = '';
  points = geometry.components[0].getVertices();
  jQuery(\"#location_name\").val('');
  for(var i = 0; i< points.length; i++)
    wkt = wkt+(i==0? '' : ', ')+points[i].x+' '+points[i].y;
  wkt = wkt+', '+points[0].x+' '+points[0].y;
  newCGrow.find('.cggrid-boundary_geom').val(\"POLYGON((\" + wkt + \"))\");
  centre = geometry.getCentroid();
  newCGrow.find('.cggrid-centroid_geom').val(\"POINT(\" + centre.x + \"  \" + centre.y + \")\");
  jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/spatial/wkt_to_sref?wkt=POINT(\" + centre.x + \"  \" + centre.y + \")&system=2169&precision=8&callback=?\",
    function(data){
      if(typeof data.error != 'undefined')
        alert(data.error);
      else
        newCGrow.find('.cggrid-centroid_sref').val(data.sref);});
};
drawControl=new OpenLayers.Control.DrawFeature(SiteListLayer,OpenLayers.Handler.Polygon,{'displayClass':'olControlDrawFeaturePolygon', drawFeature: addDrawnGeomToSelection});
SiteListLayer.map.addControl(drawControl);
SiteListLayer.map.editLayer.clickControl.deactivate();
SiteListLayer.map.editLayer.destroyFeatures();
";
    if (array_key_exists('sample:id', data_entry_helper::$entity_to_load)){
    	data_entry_helper::$onload_javascript .= "
drawControl.activate();
";
      data_entry_helper::$late_javascript .= "
setupButtons($('#controls'), 1);
setupButtons($('#controls'), 2);
setupButtons($('#controls'), 0);";
    } else {
    	data_entry_helper::$onload_javascript .= "
drawControl.deactivate();
";
    }
    return '';
  }
  
  
  protected static function getSampleListGridPreamble() {
    global $user;
    $r = '<p>'.lang::get('LANG_SampleListGrid_Preamble').(iform_loctools_checkaccess($node,'superuser') ? lang::get('LANG_All_Users') : $user->name).'</p>';
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    if (isset($values['location:name']))
      return submission_builder::wrap_with_images($values, 'location');
    if (isset($values['sample:recorder_names'])){
      if(is_array($values['sample:recorder_names'])){
        $values['sample:recorder_names'] = implode("\r\n", $values['sample:recorder_names']);
      }
    } // else just load the string
    $sampleMod = data_entry_helper::wrap_with_attrs($values, 'sample');
    if(isset($values['sample:deleted'])) return($sampleMod);
    $subsamples = array();
    $locations = array();
    foreach($values as $key => $value){
      $parts = explode(':', $key, 4);
      //     $cloneprefix='CG:--rownnum--:--sampleid--:smpAttr:--attr_id--[:--attr_value_id--]
      if($parts[0]=='CG' && count($parts)>1 && $parts[1] != '--rownnum--' && $parts[1] != ''){
        $field = explode(':',$parts[3]);
        if($field[0]=='location'){
          $locations[$parts[1]][$field[1]]=array('value' => $value);
          $locations[$parts[1]]['website_id']=array('value' => $values['website_id']);
          $locations[$parts[1]]['survey_id']=array('value' => $values['survey_id']);
        }else {
          if($parts[2] != "--sampleid--" && $parts[2] != "")
            $subsamples[$parts[1]]['id']=array('value' => $parts[2]);
          $subsamples[$parts[1]][$parts[3]]=array('value' => $value);
          $subsamples[$parts[1]]['website_id']=array('value' => $values['website_id']);
          $subsamples[$parts[1]]['survey_id']=array('value' => $values['survey_id']);
        }
      }
    }
    $subsamples2 = array();
    foreach($subsamples as $sampleIndex => $subsampleFields) {
      if(isset($subsampleFields['date']) && $subsampleFields['date']['value']!=""){
        $subsampleFields['location_name']=$subsampleFields['name'];
        $subsample = array('fkId' => 'parent_id','model' => array('id' => 'sample','fields' => $subsampleFields));
        if(isset($locations[$sampleIndex])) {
          $locweb = array('fkId' => 'location_id','model' => array('id' => 'locations_website','fields' => array('website_id' =>array('value' => $values['website_id']))));
          $locations[$sampleIndex]['name']=$subsampleFields['name'];
          $locations[$sampleIndex]['parent_id']=array('value' => $values['sample:location_id']);
          $locations[$sampleIndex]['centroid_sref_system']=array('value' => $values['location:centroid_sref_system']);
          $subsample['model']['superModels'] = array(array('fkId' => 'location_id','model' => array('id' => 'location','fields' => $locations[$sampleIndex], 'subModels'=>array($locweb))));
        }
        $occs=array();
        foreach($values as $key => $value){
          $parts = explode(':', $key, 6);
          // SG:--rownnum--:--sampleid--:--ttlid--:--occid--:occAttr:--attr_id--[:--attr_value_id--]
          if($parts[0]=='SG' && count($parts)>1 && $parts[3] != '--ttlid--' && $parts[3] != '' && $parts[1] == $sampleIndex && (($parts[4] != "--occid--" && $parts[4] != "")||$value!="")){
            $occ = array('fkId' => 'sample_id',
                         'model' => array('id' => 'occurrence',
                             'fields' => array('taxa_taxon_list_id' => array('value' => $parts[3]),
                                               'website_id'=>array('value' => $values['website_id']),
                                               'survey_id'=>array('value' => $values['survey_id']),
                                               $parts[5]=>array('value' => $value))));
            if($parts[4] != "--occid--" && $parts[4] != "")
              $occ['model']['fields']['id']=array('value' => $parts[4]);
            $occs[] = $occ;
          }
        }
        if(count($occs)>0) $subsample['model']['subModels'] = $occs;
        $subsamples2[] = $subsample;
      } else if(isset($subsampleFields['id']) && $subsampleFields['id']['value']!=""){
        $subsample = array('fkId' => 'parent_id',
          'model' => array('id' => 'sample','fields' => array(
            'id'=>$subsampleFields['id'],
            'deleted'=>array('value' => 't'),
            'website_id'=>$subsampleFields['website_id'],
            'survey_id'=>$subsampleFields['survey_id'])));
        $subsamples2[] = $subsample;
      }
    }
    if(count($subsamples2)>0)
      $sampleMod['subModels'] = $subsamples2;
    return($sampleMod);
  }
  
  protected function getReportActions() {
    return array(array('display' => '', 'actions' => 
            array(array('caption' => 'Edit', 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))),
        array('display' => '', 'actions' => 
            array(array('caption' => 'Delete', 'javascript'=>'deleteSurvey({sample_id})'))));
  } 
}