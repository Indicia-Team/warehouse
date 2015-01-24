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
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_sample_occurrence.php');

class iform_tree_sample_occurrence extends iform_dynamic_sample_occurrence {

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_tree_sample_occurrence_definition() {
    return array(
      'title'=>'Track a Tree : Visit data entry form',
      'category' => 'Custom Forms',
      'description'=>'Track a Tree specific visit (sample and occurrences) entry form based on the generic dynamic_sample_occurrence form. ' .
        'The attributes on the form are dynamically generated from the survey setup on the Indicia Warehouse.'
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
          'name' => 'reg_sample_method_id',
          'caption' => 'Registration Sample Method',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:sample_methods'),
          'required' => true,
          'helpText' => 'The sample method used to register the tree species in the Location form.'
        )
      )
    );
    return $retVal;
  }
  
  // TODO setup required understorey species list, and Tree species list.
  // TODO check errors on all data returned, php and JS
  // TODO Custom location control with display of pictures.

  // get_submission should be OK.

  static $treeOccurrenceRecord;
  
  /**
   * Preparing to display an existing sample with occurrences.
   * When displaying a grid of occurrences, just load the sample and data_entry_helper::species_checklist
   * will load the occurrences.
   * When displaying just one occurrence we must load the sample and the occurrence
   */
  protected static function getEntity($args, $auth) {
  	data_entry_helper::$entity_to_load = array();
  
  	// If we know the occurrence ID but not the sample, we must look it up
  	if ( self::$loadedOccurrenceId && !self::$loadedSampleId  ) {
  		$response = data_entry_helper::get_population_data(array(
  				'table' => 'occurrence',
  				'extraParams' => $auth['read'] + array('id' => self::$loadedOccurrenceId, 'view' => 'detail')
  		));
  		if (count($response) != 0) {
  			//we found an occurrence
  			self::$loadedSampleId = $response[0]['sample_id'];
  		}
  	}
  
  	// Load the sample record
  	if (self::$loadedSampleId) {
      data_entry_helper::load_existing_record($auth['read'], 'sample', self::$loadedSampleId, 'detail', false, true);
  	}
  	// Ensure that if we are used to load a different survey's data, then we get the correct survey attributes. We can change args
  	// because the caller passes by reference.
    if($args['survey_id']!=data_entry_helper::$entity_to_load['sample:survey_id'])
      throw new exception(lang::get('Attempt to access a record on a different survey.'));
    if($args['sample_method_id']!=data_entry_helper::$entity_to_load['sample:sample_method_id'])
      throw new exception(lang::get('Attempt to access a record with the wrong sample_method_id.'));
    // enforce that people only access their own data, unless explicitly have permissions
  	$editor = !empty($args['edit_permission']) && function_exists('user_access') && user_access($args['edit_permission']);
  	if (!$editor && function_exists('hostsite_get_user_field') &&
  			data_entry_helper::$entity_to_load['sample:created_by_id'] != 1 && // created_by_id can come out as string...
  			data_entry_helper::$entity_to_load['sample:created_by_id'] !== hostsite_get_user_field('indicia_user_id'))
  		throw new exception(lang::get('Attempt to access a record you did not create.'));
  }

  protected static function get_control_treedate($auth, $args, $tabAlias, $options) {
    if(!isset(data_entry_helper::$entity_to_load['sample:date']) &&
        isset($_GET['date']))
      data_entry_helper::$entity_to_load['sample:date'] = $_GET['date'];
    if (self::$loadedSampleId && !(empty($args['edit_permission']) || !function_exists('user_access') || user_access($args['edit_permission']))){
      if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
        // Date has 4 digit year first (ISO style) - convert date to expected output format
        // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
        $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
        data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
      }
      return data_entry_helper::text_input(array('label' => lang::get('Date'), 'fieldname' => 'sample:date', 'disabled'=>'readonly="readonly"', 'class'=>'tree-details-readonly' ));
    } else {
      return self::get_control_date($auth, $args, $tabAlias, $options);
    }
  }
  
  /**
   * Get the location control as a select dropdown.
   * Default control ordering is by name.
   * reportProvidesOrderBy option should be set to true if the control is populated by a report that
   * provides its own Order By statement, if the reportProvidesOrderBy option is not set in this situation, then the report
   * will have two Order By statements and will fail.
   */
  protected static function get_control_treelocationselect($auth, $args, $tabAlias, $options) {
    global $indicia_templates;
    global $user;
    
    data_entry_helper::$helpTextPos = 'before';
    $indicia_templates['two-col-50'] = '<div class="two_columns"><div id="leftcol" class="column">{col-1}</div><div id="rightcol" class="column">{col-2}</div></div>';
    $r="";
    if (!array_key_exists('location_type_id', $options))
      return "Control [tree location select] must be provided with a location_type_id.<br />";
    if (!array_key_exists('tree_location_type_id', $options))
      return "Control [tree location select] must be provided with a tree_location_type_id.<br />";
$r .= '<span style="display:none;">'.print_r($_SERVER,true).'</span>';
    if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='' && ($referer = self::getRefererPath()) !== false)
      $r .= '<input name="referer" type="hidden" value="'.$referer.'" />';

    $attrArgs = array(
    		'valuetable'=>'sample_attribute_value',
    		'attrtable'=>'sample_attribute',
    		'key'=>'sample_id',
    		'extraParams'=>$auth['read'],
    		'survey_id'=>$args['survey_id'],
    		'fieldprefix'=>'smpAttr'
    );
    $attrArgs['extraParams'] += array('query'=>json_encode(array('in'=>array('caption'=>array('No Understorey Observed')))));
    $smpAttrs = data_entry_helper::getAttributes($attrArgs, false);
    if(!count($smpAttrs))
      throw new exception(lang::get('This form must be used with an "No Understorey Observed" sample attribute.'));
    
    data_entry_helper::$javascript .= "
// assume map projection is same as indicia internal
// Create vector layers: one to display the Parent Site onto, and another for the tree locations list
// the default edit layer is used for this sample
var labelRule = new OpenLayers.Rule({
        symbolizer: {
          label : \"\${name}\",
          fontSize: \"16px\",
          fontFamily: \"Verdana, Arial, Helvetica,sans-serif\",
          fontWeight: \"bold\",
          fontColor: \"#FF0000\",
          labelAlign: \"cm\",
          labelYOffset: \"-15\"
        }
      });
var defaultStyle = new OpenLayers.Style({pointRadius: 6,fillColor: \"Red\",fillOpacity: 0.3,strokeColor: \"Yellow\",strokeWidth: 1});
defaultStyle.addRules([labelRule]);

ParentLocStyleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({strokeColor: \"Yellow\",fillOpacity: 0,strokeWidth: 4})});
ParentLocationLayer = new OpenLayers.Layer.Vector('Parents',{styleMap: ParentLocStyleMap,displayInLayerSwitcher: false});

TreeStyleMap = new OpenLayers.StyleMap({\"default\": defaultStyle});
TreeListLayer = new OpenLayers.Layer.Vector('Trees',{styleMap: TreeStyleMap, displayInLayerSwitcher: false});
var _zoomToExtent = function(bounds, layer){
  var dy = Math.max(10, (bounds.top-bounds.bottom) * layer.map.div.settings.maxZoomBuffer);
  var dx = Math.max(10, (bounds.right-bounds.left) * layer.map.div.settings.maxZoomBuffer);
  bounds.top = bounds.top + dy;
  bounds.bottom = bounds.bottom - dy;
  bounds.right = bounds.right + dx;
  bounds.left = bounds.left - dx;
  if (layer.map.getZoomForExtent(bounds) > layer.map.div.settings.maxZoom) {
    // if showing something small, don't zoom in too far
    layer.map.setCenter(bounds.getCenterLonLat(), layer.map.div.settings.maxZoom);
  } else {
    // Set the default view to show something a bit larger than the size of the grid square
    layer.map.zoomToExtent(bounds);
  }
};
$('.scPresence:checkbox').change(function(){
  var cells = $(this).closest('tr').find('.scOccAttrCell');
  if($(this).attr('checked')){
    cells.find('select').addClass('required').removeAttr('disabled');
    cells.append('<span class=\"deh-required\">*</span>');
  } else {
    cells.find('select').removeClass('required').val('').attr('disabled',true);
    cells.find('.deh-required').remove();
    cells.find('label.error').remove();
    cells.find('.error').removeClass('error');
    cells.find('.inline-error').remove();
    cells.find('.ui-state-error').removeClass('ui-state-error');
  }
});
$('.scPresence:checkbox').change();
indiciaData.searchUpdatesSref=false;\n";
    data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\\\:',$smpAttrs[0]['id'])."],[name^=".str_replace(':','\\\\:',$smpAttrs[0]['id'])."\\\\:]').filter(':checkbox').rules('add', {no_observation: true});
$.validator.addMethod('no_observation', function(arg1, arg2){
  // ignore the hidden zeroing field asscoiated with the boolean
  if(!$(arg2).filter(':checkbox').length) return true;
  var numRows = jQuery('.scPresence').filter(':checkbox').filter('[checked]').length;
  var isChecked = jQuery(arg2).attr('checked') != false;
  if(isChecked) return(numRows==0)
  else if(numRows>0) return true;
  return false;
},
  \"The <strong>".lang::get('No Understorey Observed')."</strong> checkbox must be selected if there are no species entered in the Flowering plant phenology grid, otherwise it must be unchecked.\");
\n";
    self::$treeOccurrenceRecord = false;
    
    if (self::$loadedSampleId) {
      $location_id = data_entry_helper::$entity_to_load['sample:location_id'];
    } else if(isset($_GET['location_id'])) {
      $location_id = $_GET['location_id'];
    } else $location_id = false;
    
    $attrArgs = array(
    		'valuetable'=>'location_attribute_value',
    		'attrtable'=>'location_attribute',
    		'key'=>'location_id',
    		'extraParams'=>$auth['read'],
    		'survey_id'=>$args['survey_id'],
    		'fieldprefix'=>'locAttr',
            'location_type_id' => $options['tree_location_type_id']
    );
    
    if($location_id) $attrArgs['id'] = $location_id;
    $attrArgs['extraParams'] += array('query'=>json_encode(array('in'=>array('caption'=>array('Recorder Name')))));
    $locAttrs = data_entry_helper::getAttributes($attrArgs, false);
    
    $attrArgs = array(
    		'valuetable'=>'sample_attribute_value',
    		'attrtable'=>'sample_attribute',
    		'key'=>'sample_id',
    		'extraParams'=>$auth['read'],
    		'survey_id'=>$args['survey_id'],
    		'fieldprefix'=>'smpAttr',
    		'sample_method_id'=>$args['sample_method_id']
    );
    $attrArgs['extraParams'] += array('query'=>json_encode(array('in'=>array('caption'=>array('Recorder Name')))));
    $smpAttrs = data_entry_helper::getAttributes($attrArgs, false);
    
    if(!count($locAttrs))
      throw new exception(lang::get('This form must be used with a "Recorder Name" tree location attribute.'));
    if(!count($smpAttrs))
    	throw new exception(lang::get('This form must be used with a "Recorder Name" sample attribute.'));
    
    data_entry_helper::$javascript .= "indiciaData.assignedRecorderAttrID='".$locAttrs[0]['attributeId']."';\n";
    data_entry_helper::$javascript .= "indiciaData.recorderNameID='".$smpAttrs[0]['id']."';\n";
    
    if($location_id){
      $r .= '<div class="page-notice ui-state-highlight ui-corner-all">This visit is registered against the tree highlighted on the map. All fields followed by a red asterisk (<span class="deh-required">*</span>) must be filled in. You can not modify any field that is greyed out.</div>';
      $locationRecord = data_entry_helper::get_population_data(array(
            'table' => 'location',
            'extraParams' => $auth['read'] + array('id' => $location_id, "view"=>"detail"),
            'nocache' => true
      ));
      if(count($locationRecord)!=1)
        throw new exception(lang::get('Could not identify tree location : ID ').$location_id);
      
      if(count($locAttrs) && count($smpAttrs))
        data_entry_helper::$entity_to_load[$smpAttrs[0]['fieldname']] = $locAttrs[0]['default'];

      $parentLocationRecord = data_entry_helper::get_population_data(array(
            'table' => 'location',
            'extraParams' => $auth['read'] + array('id' => $locationRecord[0]['parent_id'], "view"=>"detail"),
            'nocache' => true
      ));
      if(count($parentLocationRecord)!=1)
        throw new exception(lang::get('Could not identify site location : ID ').$locationRecord[0]['parent_id']);

      // TODO make sure user is allocated this tree, or if sample already exists, that they created this sample, or they are admin

      $registrationSampleRecord = data_entry_helper::get_population_data(array(
            'table' => 'sample',
            'extraParams' => $auth['read'] + array('location_id' => $locationRecord[0]['id'], "view"=>"detail", "sample_method_id"=>$args['reg_sample_method_id']),
            'nocache' => true
      ));
      if(count($registrationSampleRecord)!=1)
        throw new exception(lang::get('Could not identify registration sample for tree location ID ').$locationRecord[0]['id']);

      $registrationOccurrenceRecord = data_entry_helper::get_population_data(array(
            'table' => 'occurrence',
            'extraParams' => $auth['read'] + array('sample_id' => $registrationSampleRecord[0]['id'], "view"=>"detail"),
            'nocache' => true
      ));
      if(count($registrationOccurrenceRecord)!=1){
        throw new exception(lang::get('Could not identify registration occurrence for tree location ID ').$locationRecord[0]['id']);
      }

      if(self::$loadedSampleId)
        self::$treeOccurrenceRecord = data_entry_helper::get_population_data(array(
            'table' => 'occurrence',
            'extraParams' => $auth['read'] + array('sample_id' => self::$loadedSampleId, "view"=>"detail", 'taxa_taxon_list_id'=>$registrationOccurrenceRecord[0]['taxa_taxon_list_id']),
            'nocache' => true
        )); // can handle this so no error check.
      else // need to set sref & system to match tree.
        data_entry_helper::$javascript .= "\n$('#imp-sref').val('".$locationRecord[0]['centroid_sref']."');\n";

      $r .= data_entry_helper::text_input(array(
            'label' => lang::get('Site'),
            'fieldname' => 'dummy:site_name',
            'class' => 'control-width-4 tree-details-readonly',
            'default' => $parentLocationRecord[0]["name"],
            'disabled' => ' readonly="readonly"'
      )); // don't need a hidden control.
      $r .= data_entry_helper::text_input(array(
            'label' => lang::get('Tree'),
            'fieldname' => 'dummy:tree_name',
            'class' => 'control-width-4 tree-details-readonly',
            'default' => $locationRecord[0]["name"],
            'disabled' => ' readonly="readonly"'
      ));
      $r .= '<input name="sample:location_id" type="hidden" value="'.$location_id.'" />';
      $r .= data_entry_helper::text_input(array(
            'label' => lang::get('Tree Species'),
            'fieldname' => 'dummy:tree_species',
            'class' => 'control-width-4 tree-details-readonly',
            'default' => $registrationOccurrenceRecord[0]["taxon"],
      		'disabled' => ' readonly="readonly"'
      ));
      if(self::$treeOccurrenceRecord && count(self::$treeOccurrenceRecord))
        $r .= '<input name="sc:tree:'.self::$treeOccurrenceRecord[0]['id'].':present" type="hidden" value="'.self::$treeOccurrenceRecord[0]['taxa_taxon_list_id'].'" />';
      else
        $r .= '<input name="sc:tree::present" type="hidden" value="'.$registrationOccurrenceRecord[0]['taxa_taxon_list_id'].'" />';
      if(self::$loadedSampleId)
        $systems = array(data_entry_helper::$entity_to_load['sample:entered_sref_system'] => data_entry_helper::$entity_to_load['sample:entered_sref_system']);
      else
        $systems = array($locationRecord[0]['centroid_sref_system'] => $locationRecord[0]['centroid_sref_system']);
      $r .= data_entry_helper::sref_and_system(array_merge(array(
            'label' => lang::get('Tree Grid Ref'),
            'systems' => $systems,
            'disabled' => ' readonly="readonly"',
            'validation' => '',
            'class' => 'control-width-4 tree-details-readonly'
          ), $options));
      data_entry_helper::$javascript .= "
mapInitialisationHooks.push(function (div) {
  'use strict';
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/location/'+".$locationRecord[0]['parent_id']." +
            '?mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    if (data.length>0) {
      var geomwkt = data[0].boundary_geom || data[0].centroid_geom;
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(geomwkt);
      ParentLocationLayer.addFeatures([feature]);
      // no zoom, but put in other trees at site - will add our name.
      $.getJSON(TreeListLayer.map.div.settings.indiciaSvc + 'index.php/services/data/location?parent_id='+data[0].id +
            '&mode=json&view=detail' + TreeListLayer.map.div.settings.readAuth + '&callback=?', function(tdata) {
        if (typeof tdata.error!=='undefined') {
          alert(tdata.error);
          return;
        }
        if (tdata.length>0) {
          for(var i=0; i<tdata.length; i++){
            var geomwkt = tdata[i].centroid_geom, feature;
            var parser = new OpenLayers.Format.WKT();
            var feature = parser.read(geomwkt);
            feature.attributes.name = tdata[i].name;
            TreeListLayer.addFeatures(feature);
      }}});
  }});
  if(div.map.editLayer.features.length>0) {
    var highlighter = new OpenLayers.Control.SelectFeature(div.map.editLayer, {highlightOnly: true});
    div.map.addControl(highlighter);
    highlighter.activate();
    highlighter.highlight(div.map.editLayer.features[0]);
  }
});";
      if(self::$loadedSampleId)
        data_entry_helper::$javascript .= "
$('[name=sample\\\\:date]').change(function(){ // sample exists so just check that no sample already taken on that date.
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/sample?location_id='+".$locationRecord[0]['id']." +
      '&sample_method_id=".$args['sample_method_id']."&mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    var date = $('[name=sample\\\\:date]').val();
    date = date.split('/');
    for(var i = 0; i < data.length; i++){
      if(data[i][\"id\"] != ".self::$loadedSampleId." && data[i][\"date_start\"] == date[2]+'-'+date[1]+'-'+date[0]){
        alert(\"Warning: there is already a visit recorded for this tree on \"+data[i][\"date_start\"]);
        return;
    }}
  });\n});\n";
      else
        data_entry_helper::$javascript .= "
$('[name=sample\\\\:date]').change(function(){
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/sample?location_id='+".$locationRecord[0]['id']." +
  		'&sample_method_id=".$args['sample_method_id']."&mode=json&view=detail&orderby=date_start' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    var date = $('[name=sample\\\\:date]').val();
    date = date.split('/');
    for(var i = 0; i < data.length; i++){
      if(data[i][\"date_start\"] == date[2]+'-'+date[1]+'-'+date[0]) {
        alert(\"Warning: there is already a visit recorded for this tree on \"+data[i][\"date_start\"]);
        return;
      }
    }
    for(var i = 0; i < data.length; i++){
      sdate = data[i][\"date_start\"].split('-');
      if(sdate[0] > date[2] || (sdate[0] == date[2] && (sdate[1] > date[1] || (sdate[1] == date[1] && sdate[2] >= date[0])))) {
        alert(\"Warning: there is already a visit recorded for this tree after this date, on \"+data[i][\"date_start\"]);
        break;
      }
    }
    for(var i = data.length-1; i >=0; i--){
      sdate = data[i][\"date_start\"].split('-');
      if(sdate[0] < date[2] || (sdate[0] == date[2] && (sdate[1] < date[1] || (sdate[1] == date[1] && sdate[2] <= date[0])))) {
        if(confirm(\"There was a previously recorded visit for this tree prior to this date, on \"+data[i][\"date_start\"]+'. Do you wish to use the data from this earlier visit as a starting point? (This will not affect any previously recorded observations - it will just fill in this form with the data from your previous visit, excluding the field diary. You can then update the data with any changes for this visit.)')){
          // this is an initial save: all attributes have vanilla names.
          $('.scPresence').removeAttr('checked');
          var cells = $('.scOccAttrCell');
          $('#".data_entry_helper::$validated_form_id."').find('label.error').remove();
          $('#".data_entry_helper::$validated_form_id."').find('.error').removeClass('error');
          $('#".data_entry_helper::$validated_form_id."').find('.inline-error').remove();
          $('#".data_entry_helper::$validated_form_id."').find('.ui-state-error').removeClass('ui-state-error');
          cells.find('select').removeClass('required').val('').attr('disabled',true);
          cells.find('.deh-required').remove();
          $('[name^=sc\\\\:tree\\\\:\\\\:occAttr\\\\:]').val('');
          $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/occurrence?sample_id='+data[i]['id'] +
              '&mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(odata) {
            if (typeof odata.error!=='undefined') {
              alert(odata.error);
              return;
            }
            var occIDs = [];
            if(odata.length>0){
              for(var j = 0; j < odata.length; j++) {
                occIDs.push(odata[j].id);
                $('.scPresence[value='+odata[j].taxa_taxon_list_id+']').each(function(idx, elem){
                  var cells = $(elem).attr('checked',true).closest('tr').find('.scOccAttrCell');
                  cells.find('select').addClass('required').removeAttr('disabled');
                  cells.append('<span class=\"deh-required\">*</span>');
                });
              }
              $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/occurrence_attribute_value' +
                  '?query={\"in\":{\"occurrence_id\":'+JSON.stringify(occIDs) +'}}' +
                  '&mode=json&view=list' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(adata) {
                if (typeof adata.error!=='undefined') {
                  alert(adata.error);
                  return;
                }
                var x = adata;
                for(var k = 0; k < adata.length; k++){
                  if(adata[k].id != null){
                    for(var j = 0; j < odata.length; j++) {
                      if(odata[j].id == adata[k].occurrence_id){
                        $('.scPresence[value='+odata[j].taxa_taxon_list_id+']').closest('tr').find('[name$=\\\\:occAttr\\\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
                        $('[name=sc\\\\:tree\\\\:\\\\:occAttr\\\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
                        break;
              }}}}});
          }});
        }
        break;
  }}});
});
\n";
      return $r;
    }
    $r .= '<div class="page-notice ui-state-highlight ui-corner-all">All fields followed by a red asterisk (<span class="deh-required">*</span>) must be filled in. You can not modify any field that is greyed out.</div>';
    self::$treeOccurrenceRecord = false;
    if (isset($options['extraParams'])) {
      foreach ($options['extraParams'] as $key => &$value)
        $value = apply_user_replacements($value);
        $options['extraParams'] = array_merge($auth['read'], $options['extraParams']);
    } else
      $options['extraParams'] = array_merge($auth['read']);
    if (empty($options['reportProvidesOrderBy'])||$options['reportProvidesOrderBy']==0) {
      $options['extraParams']['orderby'] = 'name';
    }
    // Get list of sites alocated to me, using CMS User ID.
    $site_attributes = data_entry_helper::getAttributes(array(
    		'valuetable'=>'location_attribute_value',
    		'attrtable'=>'location_attribute',
    		'key'=>'location_id',
    		'extraParams'=>$auth['read'],
    		'survey_id'=>$args['survey_id'],
    		'location_type_id' => $options['location_type_id'],
    		'fieldprefix'=>'locAttr'
    ));
    if (false==($cmsUserAttr = extract_cms_user_attr($site_attributes)))
      return 'This form is designed to be used with the "CMS User ID" attribute setup for Site locations in the survey.';
    $response = data_entry_helper::get_population_data(array(
      'table' => 'location_attribute_value',
      'extraParams' => $auth['read'] + array('view' => 'list', 'location_attribute_id'=>$cmsUserAttr['attributeId'], 'raw_value'=>$user->uid),
      'nocache' => true
    ));
    if(count($response)==0)
      throw new exception(lang::get('You have no sites, so you can not enter any phenology observations. Please create a site and some trees first.'));
    $siteIDs = array();
    foreach($response as $loc) {
      $siteIDs[] = $loc['location_id'];
    }
    
    $location_list_args = array_merge(array(
        'label'=>lang::get('Site'),
        'view'=>'detail',
        'fieldname'=>'location:parent_id',
        'id'=>'imp-site-location',
        'blankText'=>lang::get('Please select a site.'),
        'validation' => 'required',
        'nocache' => true
      ), $options);
    // $options already has the location_type_id set
    $location_list_args['extraParams']['id']=$siteIDs;
    $r .= data_entry_helper::location_select($location_list_args);

    $location_list_args = array_merge(array(
        'label'=>lang::get('Tree'),
        'view'=>'detail',
        'blankText'=>lang::get('Please select a tree.'),
        'validation' => 'required',
        'nocache' => true
    ), $options);
    $location_list_args['extraParams']['id']=$siteIDs;
    $location_list_args['location_type_id'] = $options['tree_location_type_id'];
    $r .= data_entry_helper::location_select($location_list_args);

    $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Tree Species'),
        'fieldname' => 'dummy:tree_species',
        'class' => 'control-width-4 tree-details-readonly',
        'disabled' => ' readonly="readonly"',
        'validation' => 'required',
        'helpText' => lang::get('The following field is filled in automatically when you select a tree.')
    ));
    $r .= '<input name="sc:tree::present" type="hidden" value="" />';
    $systems = array('4326' => lang::get("sref:4326")); // this will be overwriten when the tree is loaded.
    $r .= data_entry_helper::sref_and_system(array_merge(array(
        'label' => lang::get('Tree Grid Ref'),
        'systems' => $systems,
        'disabled' => ' readonly="readonly"',
        'validation' => '',
        'class' => 'tree-details-readonly',
        'helpText' => lang::get('The following field is filled in automatically when you select a tree.')
    ), $options));
    data_entry_helper::$javascript .= "
$('#imp-site-location').change(function() {
  $('#".data_entry_helper::$validated_form_id."').find('label.error').remove();
  $('#".data_entry_helper::$validated_form_id."').find('.error').removeClass('error');
  $('#".data_entry_helper::$validated_form_id."').find('.inline-error').remove();
  $('#".data_entry_helper::$validated_form_id."').find('.ui-state-error').removeClass('ui-state-error');
  ParentLocationLayer.destroyFeatures();
  TreeListLayer.destroyFeatures();
  var intValue = parseInt($(this).val());
  if (!isNaN(intValue)) {
    $(this).find('option[value=]').attr('disabled',true);
    // site list has been populated with correct location_type_ids.
    $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/location/'+$(this).val() +
              '?mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
      if (typeof data.error!=='undefined') {
        alert(data.error);
        return;
      }
      if (data.length>0) {
        var geomwkt = data[0].boundary_geom || data[0].centroid_geom;
        // assume single geometry - not a collection, and default style
        var parser = new OpenLayers.Format.WKT();
        var feature = parser.read(geomwkt);
        ParentLocationLayer.addFeatures([feature]);
        _zoomToExtent(feature.geometry.getBounds(), ParentLocationLayer);
        $('#imp-location option[value!=]').not(':selected').remove();
        $.getJSON(TreeListLayer.map.div.settings.indiciaSvc + 'index.php/services/data/location?parent_id='+data[0].id +
              '&mode=json&view=detail' + TreeListLayer.map.div.settings.readAuth + '&callback=?', function(tdata) {
          if (typeof tdata.error!=='undefined') {
            alert(tdata.error);
            return;
          }
          if (tdata.length>0) {
            var features = [], found=false;
            var bounds = ParentLocationLayer.features[0].geometry.getBounds();
            for(var i=0; i<tdata.length; i++){
              var geomwkt = tdata[i].centroid_geom, feature;
              var parser = new OpenLayers.Format.WKT();
              var feature = parser.read(geomwkt);
              feature.attributes.name = tdata[i].name;
              features.push(feature);
              bounds.extend(feature.geometry.getBounds());
              if($('#imp-location option[value='+tdata[i].id+']').length == 0)
                $('#imp-location').append('<option value=\"'+tdata[i].id+'\">'+tdata[i].name+'</option>');
              else found=true;
            }
            if(!found){ // currently selected tree no longer on this site. Deselect it.
              $(this).find('option[value=]').removeAttr('disabled');
              $('#imp-location option[value!=]:selected').remove();
              $('#imp-location,#imp-sref').val('');
              TreeListLayer.map.editLayer.destroyFeatures();
              $('[name=dummy\\\\:tree_species]').val('');
            }
            TreeListLayer.addFeatures(features);
            _zoomToExtent(bounds, ParentLocationLayer);
        }});
    }});
}});
mapLocationSelectedHooks.push(function(div, data){
  $('#".data_entry_helper::$validated_form_id."').find('label.error').remove();
  $('#".data_entry_helper::$validated_form_id."').find('.error').removeClass('error');
  $('#".data_entry_helper::$validated_form_id."').find('.inline-error').remove();
  $('#".data_entry_helper::$validated_form_id."').find('.ui-state-error').removeClass('ui-state-error');
  if($('#imp-site-location').val() != data[0].parent_id) {
    $('#imp-site-location').val(data[0].parent_id).change();
    $('#imp-site-location option[value=]').attr('disabled',true);
  }
  if(data[0].parent_id != ''){
    $('#imp-location option[value=]').attr('disabled',true);
  }
  $('#'+indiciaData.recorderNameID.replace(/:/g,'\\\\:')).val('');
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/location_attribute_value?location_id='+data[0]['id'] +
      '&mode=json&view=list' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if(data instanceof Array && data.length>0){
      for (var i=0;i<data.length;i++){
        if (data[i]['location_attribute_id'] == indiciaData.assignedRecorderAttrID)
          $('#'+indiciaData.recorderNameID.replace(/:/g,'\\\\:')).val(data[i].raw_value);
      }
  }});
  // get registration sample and occurrence and fill in species.
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/sample?location_id='+data[0]['id'] +
      '&sample_method_id=".$args['reg_sample_method_id']."&mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    if(data.length){
      $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/occurrence?sample_id='+data[0]['id'] +
          '&mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
        if (typeof data.error!=='undefined') {
          alert(data.error);
          return;
        }
        if(data.length){
          $('[name=dummy\\\\:tree_species]').val(data[0].taxon);
          $('[name=sc\\\\:tree\\\\:\\\\:present]').val(data[0].taxa_taxon_list_id);
      }});
  }});
});
$('[name=sample\\\\:date]').change(function(){
  if($('#imp-location').val()=='') return;
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/sample?location_id='+$('#imp-location').val() +
  		'&sample_method_id=".$args['sample_method_id']."&mode=json&view=detail&orderby=date_start' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    var date = $('[name=sample\\\\:date]').val();
    date = date.split('/');
    for(var i = 0; i < data.length; i++){
      if(data[i][\"date_start\"] == date[2]+'-'+date[1]+'-'+date[0]) {
        alert(\"Warning: there is already a visit recorded for this tree on \"+data[i][\"date_start\"]);
        return;
      }
    }
    for(var i = 0; i < data.length; i++){
      sdate = data[i][\"date_start\"].split('-');
      if(sdate[0] > date[2] || (sdate[0] == date[2] && (sdate[1] > date[1] || (sdate[1] == date[1] && sdate[2] >= date[0])))) {
        alert(\"Warning: there is already a visit recorded for this tree after this date, on \"+data[i][\"date_start\"]);
        break;
      }
    }
    for(var i = data.length-1; i >=0; i--){
      sdate = data[i][\"date_start\"].split('-');
      if(sdate[0] < date[2] || (sdate[0] == date[2] && (sdate[1] < date[1] || (sdate[1] == date[1] && sdate[2] <= date[0])))) {
        if(confirm(\"There was a previously recorded visit for this tree prior to this date, on \"+data[i][\"date_start\"]+'. Do you wish to use the data from this earlier visit as a starting point? (This will not affect any previously recorded observations - it will just fill in this form with the data from your previous visit, excluding the field diary. You can then update the data with any changes for this visit.)')){
          // this is an initial save: all attributes have vanilla names.
          // TODO sample attributes
          $('.scPresence').removeAttr('checked');
          var cells = $('.scOccAttrCell');
          cells.find('select').removeClass('required').val('').attr('disabled',true);
          cells.find('.deh-required').remove();
          $('#".data_entry_helper::$validated_form_id."').find('label.error').remove();
          $('#".data_entry_helper::$validated_form_id."').find('.error').removeClass('error');
          $('#".data_entry_helper::$validated_form_id."').find('.inline-error').remove();
          $('#".data_entry_helper::$validated_form_id."').find('.ui-state-error').removeClass('ui-state-error');
          $('[name^=sc\\\\:tree\\\\:\\\\:occAttr\\\\:]').val('');
          $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/occurrence?sample_id='+data[i]['id'] +
              '&mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(odata) {
            if (typeof odata.error!=='undefined') {
              alert(odata.error);
              return;
            }
            var occIDs = [];
            if(odata.length>0){
              for(var j = 0; j < odata.length; j++) {
                occIDs.push(odata[j].id);
                $('.scPresence[value='+odata[j].taxa_taxon_list_id+']').each(function(idx, elem){
                  var cells = $(elem).attr('checked',true).closest('tr').find('.scOccAttrCell');
                  cells.find('select').addClass('required').removeAttr('disabled');
                  cells.append('<span class=\"deh-required\">*</span>');
                });
              }
              $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/occurrence_attribute_value' +
                  '?query={\"in\":{\"occurrence_id\":'+JSON.stringify(occIDs) +'}}' +
                  '&mode=json&view=list' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(adata) {
                if (typeof adata.error!=='undefined') {
                  alert(adata.error);
                  return;
                }
                var x = adata;
                for(var k = 0; k < adata.length; k++){
                  if(adata[k].id != null){
                    for(var j = 0; j < odata.length; j++) {
                      if(odata[j].id == adata[k].occurrence_id){
                        $('.scPresence[value='+odata[j].taxa_taxon_list_id+']').closest('tr').find('[name$=\\\\:occAttr\\\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
                        $('[name=sc\\\\:tree\\\\:\\\\:occAttr\\\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
                        break;
              }}}}});
          }});
        }
        break;
  }}});
});
\n";
    return $r;
  }

  protected static function get_control_occurrenceattributes($auth, $args, $tabAlias, $options) {
    $attrArgs = array(
         'valuetable'=>'occurrence_attribute_value',
         'attrtable'=>'occurrence_attribute',
         'key'=>'occurrence_id',
         'extraParams'=>$auth['read'],
         'survey_id'=>$args['survey_id']
    );
    if (self::$treeOccurrenceRecord) {
      // if we have a single occurrence Id to load, use it to get attribute values
      $attrArgs['id'] = self::$treeOccurrenceRecord[0]['id'];
      $attrArgs['fieldprefix']='sc:tree:'.$attrArgs['id'].':occAttr';
    } else {
      $attrArgs['fieldprefix']='sc:tree::occAttr';
    }
    if (!empty($options['attributeIds'])) {
      $attrArgs['extraParams'] += array('query'=>json_encode(array('in'=>array('id'=>$options['attributeIds']))));
    }
    $occAttrs = data_entry_helper::getAttributes($attrArgs, false);
    $ctrlOptions = array('extraParams'=>$auth['read']);
    $attrSpecificOptions = array();
    self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
    return get_attribute_html($occAttrs, $args, $ctrlOptions, '', $attrSpecificOptions);
  }

  protected static function get_control_sampleattributes($auth, $args, $tabAlias, $options) {
    $r = '';
  	$tab = (isset($options['tab']) ? $options['tab'] : null);
    $attrArgs = array(
         'valuetable'=>'sample_attribute_value',
         'attrtable'=>'sample_attribute',
         'key'=>'sample_id',
         'fieldprefix'=>'smpAttr',
         'extraParams'=>$auth['read'],
         'survey_id'=>$args['survey_id']
    );
    if (self::$loadedSampleId) {
      // if we have a single occurrence Id to load, use it to get attribute values
      $attrArgs['id'] = self::$loadedSampleId;
    }
    if(isset($args['sample_method_id']) && !empty($args['sample_method_id']))
      $attrArgs['sample_method_id'] = $args['sample_method_id'];
    if (!empty($options['attributeIds'])) {
      $attrArgs['extraParams']['query']=json_encode(array('in'=>array('id'=>$options['attributeIds'])));
    }
    $smpAttrs = data_entry_helper::getAttributes($attrArgs, false);
    $ctrlOptions = array('extraParams'=>$auth['read']);
    $attrSpecificOptions = array();
    self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
    foreach($attrSpecificOptions as $attr => $opts)
      if(isset($attrSpecificOptions[$attr]['default']))
        $attrSpecificOptions[$attr]['default'] = apply_user_replacements($attrSpecificOptions[$attr]['default']);
    foreach ($smpAttrs as &$attribute) {
      if (in_array($attribute['id'],data_entry_helper::$handled_attributes))
        $attribute['handled']=1;
      // Hide controls that have already been handled.
      if (($tab===null || strcasecmp($tab, $attribute['inner_structure_block'])==0) && !isset($attribute['handled'])) {
        $options = $ctrlOptions + get_attr_validation($attribute, $args);
        // when getting the options, only use the first 2 parts of the fieldname as any further imply an existing record ID so would differ.
        $fieldNameParts=explode(':',$attribute['fieldname']);
        if (preg_match('/[a-z][a-z][a-z]Attr/', $fieldNameParts[count($fieldNameParts)-2]))
          $optionFieldName = $fieldNameParts[count($fieldNameParts)-2] . ':' . $fieldNameParts[count($fieldNameParts)-1];
        elseif (preg_match('/[a-za-za-z]Attr/', $fieldNameParts[count($fieldNameParts)-3]))
          $optionFieldName = $fieldNameParts[count($fieldNameParts)-3] . ':' . $fieldNameParts[count($fieldNameParts)-2];
        else
          throw new exception('Option fieldname not found');
        $dummy=null;
        if (isset($attrSpecificOptions[$optionFieldName])) {
          $options = array_merge($options, $attrSpecificOptions[$optionFieldName]);
        }
        $r .= data_entry_helper::outputAttribute($attribute, $options);
        $attribute['handled']=true;
      }
    }
    return $r;
  }

  protected static function get_control_title($auth, $args, $tabAlias, $options) {
  	$r = 'No Valid Header';
  	if(is_array($options) && count($options)>0){
      foreach($options as $key=>$value){
        // only do first
        switch($key){
          case 'h4': $r = "<h4>".$value."</h4>";
                     break;
          case 'h3': $r = "<h3>".$value."</h3>";
                     break;
          case 'h2': $r = "<h2>".$value."</h2>";
                     break;
        }
        break;
      }
  	}
  	return $r;
  }
  
  /**
   * Override the default submit buttons to add a cancel button.
   */
  protected static function getSubmitButtons($args) {
    $r = '<input type="submit" class="indicia-button" id="save-button" value="'.lang::get('Submit')."\" />";
    if($referer = self::getRefererPath())
      $r .= '<a href="'.$referer.'"><input type="button" class="indicia-button" name="cancel" value="'.lang::get('Cancel').'" /></a>';
    else
      $r .= '<a href="JavaScript:window.close()"><input type="button" class="indicia-button" name="cancel" value="'.lang::get('Cancel').'" /></a>';
    if (!empty(self::$loadedSampleId)) {
      $r .= '<input type="submit" class="indicia-button" id="delete-button" name="delete-button" value="'.lang::get('Delete')."\" />\n";
      data_entry_helper::$javascript .= "$('#delete-button').click(function(e) {
  if (!confirm('".lang::get('Are you sure you want to delete this record?')."')) {
    e.preventDefault();
    return false;
  }
});\n";
    }
    
    return $r;
  }

  protected static function getRefererPath () {
    if(isset($_REQUEST['no_referer'])) return false;
    $split = strpos($_SERVER['HTTP_REFERER'], '?');
    // convert the query parameters into an array
    $gets = ($split!==false && strlen($_SERVER['HTTP_REFERER']) > $split+1) ?
        explode('&', substr($_SERVER['HTTP_REFERER'], $split+1)) : array();
    $getsAssoc = array();
    foreach ($gets as $get) {
      $tokens = explode('=', $get);
      // ensure a key without value in the URL gets an empty value
      if (count($tokens)===1) $tokens[] = '';
      $getsAssoc[$tokens[0]] = $tokens[1];
    }
  	$path = $split!==false ? substr($_SERVER['HTTP_REFERER'], 0, $split) : $_SERVER['HTTP_REFERER'];
  	unset($getsAssoc['sample_id']);
  	unset($getsAssoc['occurrence_id']);
  	unset($getsAssoc['location_id']);
  	unset($getsAssoc['table']);
  	unset($getsAssoc['id']);
  	unset($getsAssoc['new']);
  	unset($getsAssoc['newLocation']);
  	unset($getsAssoc['no_referer']);
  	if(count($getsAssoc)) {
  		// decode params prior to encoding to prevent double encoding.
  		foreach ($getsAssoc as $key => $param) {
  			$getsAssoc[$key] = urldecode($param);
  		}
  		$path .= '?'.http_build_query($getsAssoc);
  	}
  	return $path;
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   *
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('tree_sample_occurrence.css');
  }

  protected static function getReportActions() {
    return array(array('display' => 'Actions', 'actions' =>
        array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))));
  }
  
  public static function get_redirect_on_success($values, $args) {
  	if (isset($values['referer'])) {
  		return $values['referer'];
  	} else return false;
  }

  public static function get_form($args, $node, $response=null) {
    if(is_array($response)) {
      // we have got here via a post that has not been redirected.
      data_entry_helper::$javascript .= "\njQuery('div.field-name-body').remove();\n";
      return '<p>'.lang::get('Your phenology observation has been saved.').'<p>'.
             '<p>'.lang::get('The creation of this observation was instigated after adding or modifying a tree, which was done in another tab or window - you may now close this browser tab, should you wish.').'</p>'.
             '<a href="JavaScript:window.close()"><input type="button" class="indicia-button" name="close" value="'.lang::get('Close Tab').'" /></a>';
    }
    return parent::get_form($args, $node, $response);
  }

}
  

