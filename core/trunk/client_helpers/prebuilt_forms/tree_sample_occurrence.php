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
  
  // TODO setup required understory species list, and Tree species list.
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
  
  /**
   * Get the location control as a select dropdown.
   * Default control ordering is by name.
   * reportProvidesOrderBy option should be set to true if the control is populated by a report that
   * provides its own Order By statement, if the reportProvidesOrderBy option is not set in this situation, then the report
   * will have two Order By statements and will fail.
   */
  protected static function get_control_treelocationselect($auth, $args, $tabAlias, $options) {
    global $indicia_templates;
    $indicia_templates['two-col-50'] = '<div class="two_columns"><div id="leftcol" class="column">{col-1}</div><div id="rightcol" class="column">{col-2}</div></div>';
    $r="";
    if (!array_key_exists('location_type_id', $options))
      return "Control [tree location select] must be provided with a location_type_id.<br />";
    if (!array_key_exists('tree_location_type_id', $options))
      return "Control [tree location select] must be provided with a tree_location_type_id.<br />";

    if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='')
      $r .= '<input name="referer" type="hidden" value="'.self::getRefererPath().'" />';

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
  }
});
$('.scPresence:checkbox').change();
indiciaData.searchUpdatesSref=false;\n
\n";
    self::$treeOccurrenceRecord = false;
    
    if (self::$loadedSampleId) {
      $location_id = data_entry_helper::$entity_to_load['sample:location_id'];
    } else if(isset($_GET['location_id'])) {
      $location_id = $_GET['location_id'];
    } else $location_id = false;
    if($location_id){
      $r .= '<div class="page-notice ui-state-highlight ui-corner-all">This visit is already registered against a tree. All fields followed by a red asterisk (<span class="deh-required">*</span>) must be filled in. You can not modify any field that is greyed out.</div>';
      $locationRecord = data_entry_helper::get_population_data(array(
            'table' => 'location',
            'extraParams' => $auth['read'] + array('id' => $location_id, "view"=>"detail"),
            'nocache' => true
      ));
      if(count($locationRecord)!=1)
        throw new exception(lang::get('Could not identify tree location : ID ').$location_id);

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
      if(count($registrationOccurrenceRecord)!=1)
        throw new exception(lang::get('Could not identify registration occurrence for tree location ID ').$locationRecord[0]['id']);

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
            'label' => lang::get('Tree Grid Ref.'),
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
$('[name=sample\\:date]').change(function(){ // sample exists so just check that no sample already taken on that date.
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/sample?location_id='+".$locationRecord[0]['id']." +
      '&sample_method_id=".$args['sample_method_id']."&mode=json&view=detail' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    var date = $('[name=sample\\:date]').val();
    date = date.split('/');
    for(var i = 0; i < data.length; i++){
      if(data[i][\"id\"] != ".self::$loadedSampleId." && data[i][\"date_start\"] == date[2]+'-'+date[1]+'-'+date[0]){
        alert(\"Warning: there is already a visit recorded for this tree on \"+data[i][\"date_start\"]);
        return;
    }}
  });\n});\n";
      else
        data_entry_helper::$javascript .= "
$('[name=sample\\:date]').change(function(){
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/sample?location_id='+".$locationRecord[0]['id']." +
  		'&sample_method_id=".$args['sample_method_id']."&mode=json&view=detail&orderby=date_start' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    var date = $('[name=sample\\:date]').val();
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
        if(confirm(\"There was a previously recorded visit for this tree prior to this date, on \"+data[i][\"date_start\"]+'. Do you wish to use the data from this earlier visit as a starting point? (This will overwrite any data you have entered up to this point, apart from the Field Diary).')){
          // this is an initial save: all attributes have vanilla names.
          $('.scPresence').removeAttr('checked');
          var cells = $('.scOccAttrCell');
          cells.find('select').removeClass('required').val('').attr('disabled',true);
          cells.find('.deh-required').remove();
          $('[name^=sc\\:tree\\:\\:occAttr\\:]').val('');
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
                        $('.scPresence[value='+odata[j].taxa_taxon_list_id+']').closest('tr').find('[name$=\\:occAttr\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
                        $('[name=sc\\:tree\\:\\:occAttr\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
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
    $r .= data_entry_helper::location_select(array_merge(array(
        'label'=>lang::get('Site'),
        'view'=>'detail',
        'fieldname'=>'location:parent_id',
        'id'=>'imp-site-location',
        'blankText'=>lang::get('Please select a site.'),
        'validation' => 'required'
      ), $options));
    $location_list_args = array_merge(array(
        'label'=>lang::get('Tree'),
        'view'=>'detail',
        'blankText'=>lang::get('Please select a tree.'),
        'validation' => 'required'
    ), $options, array('location_type_id' => $options['tree_location_type_id']));
    $r .= data_entry_helper::location_select($location_list_args);
    $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Tree Species'),
        'fieldname' => 'dummy:tree_species',
        'class' => 'control-width-4 tree-details-readonly',
        'disabled' => ' readonly="readonly"',
        'validation' => 'required',
        'helpText' => lang::get('This is filled in automatically when you select a tree.')
    ));
    $r .= '<input name="sc:tree::present" type="hidden" value="" />';
    $systems = array('4326' => lang::get("sref:4326")); // this will be overwriten when the tree is loaded.
    $r .= data_entry_helper::sref_and_system(array_merge(array(
        'label' => lang::get('Tree Grid Ref.'),
        'systems' => $systems,
        'disabled' => ' readonly="readonly"',
        'validation' => '',
        'class' => 'tree-details-readonly',
        'helpText' => lang::get('This is filled in automatically when you select a tree.')
    ), $options));
    data_entry_helper::$javascript .= "
$('#imp-site-location').change(function() {
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
              $('[name=dummy\\:tree_species]').val('');
            }
            TreeListLayer.addFeatures(features);
            _zoomToExtent(bounds, ParentLocationLayer);
        }});
    }});
}});
mapLocationSelectedHooks.push(function(div, data){
  if($('#imp-site-location').val() != data[0].parent_id) {
    $('#imp-site-location').val(data[0].parent_id).change();
    $('#imp-site-location option[value=]').attr('disabled',true);
  }
  if(data[0].parent_id != ''){
    $('#imp-location option[value=]').attr('disabled',true);
  }
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
          $('[name=dummy\\:tree_species]').val(data[0].taxon);
          $('[name=sc\\:tree\\:\\:present]').val(data[0].taxa_taxon_list_id);
      }});
  }});
});
$('[name=sample\\:date]').change(function(){
  $.getJSON(ParentLocationLayer.map.div.settings.indiciaSvc + 'index.php/services/data/sample?location_id='+$('#imp-location').val() +
  		'&sample_method_id=".$args['sample_method_id']."&mode=json&view=detail&orderby=date_start' + ParentLocationLayer.map.div.settings.readAuth + '&callback=?', function(data) {
    if (typeof data.error!=='undefined') {
      alert(data.error);
      return;
    }
    var date = $('[name=sample\\:date]').val();
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
        if(confirm(\"There was a previously recorded visit for this tree prior to this date, on \"+data[i][\"date_start\"]+'. Do you wish to use the data from this earlier visit as a starting point? (This will overwrite any data you have entered up to this point, apart from the Field Diary).')){
          // this is an initial save: all attributes have vanilla names.
          $('.scPresence').removeAttr('checked');
          var cells = $('.scOccAttrCell');
          cells.find('select').removeClass('required').val('').attr('disabled',true);
          cells.find('.deh-required').remove();
          $('[name^=sc\\:tree\\:\\:occAttr\\:]').val('');
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
                        $('.scPresence[value='+odata[j].taxa_taxon_list_id+']').closest('tr').find('[name$=\\:occAttr\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
                        $('[name=sc\\:tree\\:\\:occAttr\\:'+adata[k]['occurrence_attribute_id']+']').val(adata[k]['raw_value']);
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
  	$r .= get_attribute_html($occAttrs, $args, $ctrlOptions, '', $attrSpecificOptions);
    return $r;
  }

  protected static function get_control_sampleattributes($auth, $args, $tabAlias, $options) {
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
    if (!empty($options['attributeIds'])) {
      $attrArgs['extraParams'] += array('query'=>json_encode(array('in'=>array('id'=>$options['attributeIds']))));
    }
    $smpAttrs = data_entry_helper::getAttributes($attrArgs, false);
    $ctrlOptions = array('extraParams'=>$auth['read']);
  	$attrSpecificOptions = array();
  	self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
  	foreach($attrSpecificOptions as $attr => $opts)
      if(isset($attrSpecificOptions[$attr]['default']))
  		$attrSpecificOptions[$attr]['default'] = apply_user_replacements($attrSpecificOptions[$attr]['default']);
  	$r .= get_attribute_html($smpAttrs, $args, $ctrlOptions, '', $attrSpecificOptions);
    return $r;
  }

  /**
   * Override the default submit buttons to add a cancel button.
   */
  protected static function getSubmitButtons($args) {
    $r = '<input type="submit" class="indicia-button" id="save-button" value="'.lang::get('Submit')."\" />";
    $r .= '<a href="'.self::getRefererPath().'"><input type="button" class="indicia-button" name="cancel" value="'.lang::get('Cancel').'" /></a>';
    return $r;
  }

  protected static function getRefererPath () {
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
  	}
  }
  

}
  

