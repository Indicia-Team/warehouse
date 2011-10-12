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
 * TBD:
 * Clarify what the Observers field holds.
 * Add LANG entries
 * entitiy_to_load initialisation
 * Add attributes.
 *
 * Drive WFS layer attributes off form parameters
 */

require_once('mnhnl_dynamic_1.php');

class iform_mnhnl_reptiles extends iform_mnhnl_dynamic_1 {
  protected static $locations;
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_reptiles_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Reptiles form. Inherits from Dynamic 1.'
    );
  }
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Reptiles';  
  }

  public static function get_parameters() {    
    $retVal = array();
    $parentVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'attributeValidation',
          'caption'=>'Attribute Validation Rules',
          'description'=>'Client Validation rules to be enforced on attributes: allows more options than allowed by straight class led validation.',
          'type'=>'textarea',
          'required' => false,
          'group' => 'User Interface'
        )
      )
    );
    foreach($parentVal as $param){
      if($param['name'] == 'structure'){
        $param['default'] =
             "=Site=\r\n".
              "[custom JS]\r\n".
              "@attrRestrictions=<TBD>\r\n".
              "[lux5k grid]\r\n".
              "@ParentLocationTypeID=<TBD>\r\n".
              "@LocationTypeID=<TBD>\r\n".
              "[location buttons]\r\n".
              "[map]\r\n".
              "@layers=[\"ParentLocationLayer\",\"SiteListLayer\",\"PolygonLayer\"]\r\n".
              "@scroll_wheel_zoom=false\r\n".
              "@searchUpdatesSref=true\r\n".
             "=Conditions=\r\n".
              "[date]\r\n".
              "[recorder names]\r\n".
              "[*]\r\n".
              "@sep= \r\n".
              "@lookUpKey=meaning_id\r\n".
              "[sample comment]\r\n".
             "=Species=\r\n".
              "[species]\r\n". 
              "@view=detail\r\n".
              "@rowInclusionCheck=alwaysRemovable\r\n".
              "@lookUpKey=meaning_id\r\n".
              "[*]\r\n".
              "@lookUpKey=meaning_id\r\n".
              "[late JS]";
      }
      if($param['name'] == 'attribute_termlist_language_filter')
        $param['default'] = true;
      if($param['name'] == 'grid_report')
        $param['default'] = 'reports_for_prebuilt_forms/MNHNL/mnhnl_reptiles';
        
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
    if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'));

    return  '<div id="downloads" >
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_reptile_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=reptilereport">
      <p>'.lang::get('LANG_Data_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].', "taxon_list_id":'.$args['extra_list_id'].'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
  </div>';
	
  }
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
  	global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    foreach($attributes as $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'CMS User ID')==0) {
        $userIdAttr = $attr['attributeId'];
        break;
      }
    }
    foreach($attributes as $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'CMS Username')==0) {
        $userNameAttr = $attr['attributeId'];
        break;
      }
    }

    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    if (!isset($userIdAttr)) {
      return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can '.
          'be tagged against their creator.');
    }
    if (!isset($userNameAttr)) {
      return lang::get('This form must be used with a survey that has the CMS User Name attribute associated with it so records can '.
          'be tagged against their creator.');
    }
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/MNHNL/mnhnl_reptiles';
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
        'userName_attr_id'=>$userNameAttr,
        'userName'=>($user->name)
    )
    ));	
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= '</form>
<div style="display:none" />
    <form id="form-delete-survey" action="'.$reloadPath.'" method="POST">'.$auth['write'].'
       <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
       <input type="hidden" name="survey_id" value="'.$args['survey_id'].'" />
       <input type="hidden" name="sample:id" value="" />
       <input type="hidden" name="sample:deleted" value="t" />
    </form>
</div>';
    data_entry_helper::$javascript .= "
deleteSurvey = function(sampleID){
  if(confirm(\"Are you sure you wish to delete survey \"+sampleID)){
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

  /* data_entry_helper::$entity_to_load holds the data to store, but comes in three flavours:
   * empty: brand new, no data
   * sample_id specified: editing existing record, only holds the top level sample data.
   * Submission failed: holds the POST array.
   */
  /**
   * Get the transect control
   */
  protected static function get_control_lux5kgrid($auth, $args, $tabalias, $options) {
    /*
     * TBD put in check to enforce ParentLocationType and LocationType in options
     * The location centroid srref will contain the first point of the geom.
     */
  	// if the 
    global $indicia_templates;
    if(isset(data_entry_helper::$entity_to_load["sample:updated_by_id"])) // only set if data loaded from db, not error condition
      data_entry_helper::load_existing_record($auth['read'], 'location', data_entry_helper::$entity_to_load["sample:location_id"]);
    $dummy=array('','');
    if(isset(data_entry_helper::$entity_to_load["location:centroid_sref"]))
      $dummy = explode(',',data_entry_helper::$entity_to_load["location:centroid_sref"]);
    self::$locations = iform_loctools_listlocations(self::$node);
    $locOptions = array('validation' => array('required'),
    					'label'=>lang::get('LANG_Lux5kgrid'),
    					'id'=>'location_parent_id',
    					'table'=>'location',
    					'fieldname'=>'location:parent_id',
    					'valueField'=>'id',
    					'captionField'=>'name',
    					'template' => 'select',
    					'itemTemplate' => 'select_item',
    					'extraParams'=>array_merge($auth['read'],
    						array('parent_id'=>'NULL',
    								'view'=>'detail',
    								'orderby'=>'name',
    								'location_type_id'=>$options['ParentLocationTypeID'],
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
                                     isset(data_entry_helper::$entity_to_load['location:parent_id']) ? (data_entry_helper::$entity_to_load['location:parent_id'] == $record[$locOptions['valueField']] ? 'selected=selected' : '') : ''),
                               $indicia_templates[$locOptions['itemTemplate']]);
        }
      }
    }
    $locOptions['items'] = $opts;
    $ret = data_entry_helper::apply_template($locOptions['template'], $locOptions);
    $ret .= "<input type=hidden name=\"location:id\" value=\"".data_entry_helper::$entity_to_load['location:id']."\" />
<label for='location_name'>".lang::get('LANG_Location_Name_Label').":</label><input type='text' id='location_name' name='location:name' class='required' value='".data_entry_helper::$entity_to_load['location:name']."' /><span class='deh-required'>*</span><br/>
<input type=hidden id='sample_location_id' name=\"sample:location_id\" value=\"".data_entry_helper::$entity_to_load['location:id']."\" />
<input type=hidden id=\"imp-sref\" name=\"location:centroid_sref\" value=\"".data_entry_helper::$entity_to_load['location:centroid_sref']."\" />
<input type=hidden id=\"imp-sref-system\" name=\"location:centroid_sref_system\" value=\"2169\" />
<input type=hidden id=\"imp-geom\" name=\"location:centroid_geom\" value=\"".data_entry_helper::$entity_to_load['location:centroid_geom']."\" />
<input type=hidden id=\"imp-boundary-geom\" name=\"location:boundary_geom\" value=\"".data_entry_helper::$entity_to_load['location:boundary_geom']."\" />
<input type=hidden id=\"locWebsite\" name=\"locations_website:website_id\" value=\"".$args['website_id']."\" />
<input type=hidden name=\"location:location_type_id\" value=\"".$options['LocationTypeID']."\" />
<label for=\"imp-srefX\">".lang::get('LANG_Location_X_Label').":</label><input type=\"text\" id=\"imp-srefX\" name=\"dummy:srefX\" value=\"".trim($dummy[0])."\" disabled=\"disabled\"/>
<label for=\"imp-srefX\">".lang::get('LANG_Location_Y_Label').":</label><input type=\"text\" id=\"imp-srefY\" name=\"dummy:srefY\" value=\"".trim($dummy[1])."\" disabled=\"disabled\"/><br />
";
    data_entry_helper::$javascript .= "
// Create vector layers: one to display the Parent Square onto, and another for the site locations list
// the default edit layer is used for this sample
ParentLocStyleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({fillColor: \"Green\",strokeColor: \"Black\",fillOpacity: 0.2,strokeWidth: 1})});
ParentLocationLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_Parent_Location_Layer")."\",{styleMap: ParentLocStyleMap,displayInLayerSwitcher: false});
SiteLocStyleMap = new OpenLayers.StyleMap({\"default\": new OpenLayers.Style({pointRadius: 3,fillColor: \"Red\",fillOpacity: 0.3,strokeColor: \"Red\",strokeWidth: 1})});
SiteListLayer = new OpenLayers.Layer.Vector(\"".lang::get("LANG_Site_Location_Layer")."\",{styleMap: SiteLocStyleMap,displayInLayerSwitcher: false});
    // when the parent_id is changed: 
     // clear the location currently pointed to, set it up to be entered anew.
PolygonLayer = new OpenLayers.Layer.Vector('Polygon Layer', {
			styleMap: new OpenLayers.StyleMap({
                		\"default\": new OpenLayers.Style({
                    		fillColor: \"Blue\",
        		            strokeColor: \"Blue\",
		                    fillOpacity: 0.2,
                		    strokeWidth: 2
        		          })
			})
			,displayInLayerSwitcher: false
		});
loadFeatures = function(parent_id, child_id){
  ParentLocationLayer.destroyFeatures();
  SiteListLayer.destroyFeatures();
  PolygonLayer.destroyFeatures();
  if(parent_id != ''){
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/location/\"+parent_id+\"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?\",
      function(data) {
       if (data.length>0) {
         var parser = new OpenLayers.Format.WKT();
         if(data[0].boundary_geom){ // only one location if any
           ".self::readGeomJs('data[0].boundary_geom', $args['map_projection'])."
           ParentLocationLayer.addFeatures([feature]);
           if(child_id == '') ParentLocationLayer.map.zoomToExtent(ParentLocationLayer.getDataExtent());
         }
       }});
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/location?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?&location_type_id=".$options['LocationTypeID']."&parent_id=\"+parent_id,
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
              if(child_id != '' && data[i].id == child_id){
                var polygonFeature = feature.clone();
                PolygonLayer.addFeatures([polygonFeature]);
                PolygonLayer.map.zoomToExtent(PolygonLayer.getDataExtent());
              }
            }}}});
  }
}
jQuery(\"#location_parent_id\").change(function(){
  jQuery(\"#imp-geom\").val('');
  jQuery(\"#imp-boundary-geom\").val('');
  jQuery(\"#imp-sref\").val('');
  jQuery(\"#imp-srefX\").val('');
  jQuery(\"#imp-srefY\").val('');
  jQuery(\"#location_name\").val('');
  loadFeatures(this.value, '');
});";
    return $ret;
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
        $values[] = data_entry_helper::$entity_to_load['sample:recorder_names'];
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
      'lookupValues'=>$userlist,
      'validation'=>array('required')
    ), $options));
    return $r;
  }
  
  protected static function get_control_customJS($auth, $args, $tabalias, $options) {
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
    if(lang::get('validation_required') != 'validation_required')
      data_entry_helper::$late_javascript .= "
$.validator.messages.required = \"".lang::get('validation_required')."\";";
    if(lang::get('validation_max') != 'validation_max')
      data_entry_helper::$late_javascript .= "
$.validator.messages.max = $.validator.format(\"".lang::get('validation_max')."\");";
    if(lang::get('validation_min') != 'validation_min')
      data_entry_helper::$late_javascript .= "
$.validator.messages.min = $.validator.format(\"".lang::get('validation_min')."\");";
    if(lang::get('validation_number') != 'validation_number')
      data_entry_helper::$late_javascript .= "
$.validator.messages.number = $.validator.format(\"".lang::get('validation_number')."\");";
    if(lang::get('validation_digits') != 'validation_digits')
      data_entry_helper::$late_javascript .= "
$.validator.messages.digits = $.validator.format(\"".lang::get('validation_digits')."\");";
  	// possible clash with link_species_popups, so latter disabled.
    data_entry_helper::$javascript .= "
jQuery('<div class=\"ui-widget-content ui-state-default ui-corner-all indicia-button tab-cancel\"><span><a href=\"".$reloadPath."\">".lang::get('LANG_Cancel')."</a></span></div>').appendTo('.buttons');
// Main table existing entries
speciesRows = jQuery('.species-grid > tbody').find('tr');
for(var j=0; j<speciesRows.length; j++){
	occAttrs = jQuery(speciesRows[j]).find('.scOccAttrCell');
	occAttrs.find('select').addClass('required').width('85%').after('<span class=\"deh-required\">*</span>');
}
hook_species_checklist_new_row=function(rowData) {
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list/' + rowData.id +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(mdata) {
    if(mdata instanceof Array && mdata.length>0){
      jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list' +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&taxon_meaning_id='+mdata[0].taxon_meaning_id+'&taxon_list_id=".$args["extra_list_id"]."&callback=?', function(data) {
        var taxaList = '';
        var duplicate=false;
        if(data instanceof Array && data.length>0){
          for (var i=0;i<data.length;i++){
            if(data[i].id != mdata[0].id){
              if(data[i].preferred == 'f')
                taxaList += (taxaList == '' ? '' : ', ')+data[i].taxon;
              else
                taxaList = '<em>'+data[i].taxon+'</em>'+(taxaList == '' ? '' : ', '+taxaList);
            }
          }
//            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').closest('tr').find('.scOccAttrCell').find('input').filter(':text').addClass('required').width('85%').attr('min',1).after('<span class=\"deh-required\">*</span>');
          jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').closest('tr').find('.scOccAttrCell').find('select').addClass('required').width('85%').after('<span class=\"deh-required\">*</span>');
          jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').append(' - '+taxaList).removeClass('extraCommonNames');
        }
      });
    }})
  hook_species_checklist_delete_row();
}
hook_species_checklist_pre_delete_row=function(e) {
    return confirm(\"".lang::get('Are you sure you want to delete this row?')."\");
};

set_up_child_select = function(parent, relationship){
  var childOptions = jQuery(parent).closest('tr').find('[name$='+relationship.childAttr+'],[name*='+relationship.childAttr+'\\:]').find('option');
  if(parent.val() == '') {
    childOptions.attr('disabled','disabled');
    return;
  }
  var count=0;
  var elem;
  for(var i=0; i < relationship.values.length; i++){
    if(parent.val() == relationship.values[i].value) {
      childOptions.each(function(index, Element){
        var found = false;
        for(var j=0; j < relationship.values[i].list.length; j++){
          if(relationship.values[i].list[j] == $(this).val()) found = true;
        }
        if(found || relationship.values[i].list[0]=='*') $(this).removeAttr('disabled');
        else $(this).attr('disabled','disabled');
        if(found) {
        	count++;
        	elem = $(this);
        }
      });
    }
  }
  return (count==1 ? elem.val() : '');
};
// integer is similar to digit but allows negative
$.validator.addMethod('integer', function(value, element){
	return this.optional(element) || /^-?\d+$/.test(value);
},
  \"".lang::get('validation_integer')."\");
";
    if(isset($options["attrRestrictions"])){
      $restrictionRules = explode(';', $options["attrRestrictions"]);
      foreach($restrictionRules as $restrictionRule){
        $parts = explode(':', $restrictionRule);
        data_entry_helper::$javascript .= "
rule_".$parts[0]."_".$parts[1]." = {
  childAttr: 'occAttr\\:".$parts[1]."',
  values: [";
        for($i = 2; $i < count($parts); $i++){
          $values = explode(',', $parts[$i]);
          data_entry_helper::$javascript .= "{value : ".$values[0].", list: [";
          for($j = 1; $j < count($values); $j++)
            data_entry_helper::$javascript .= "\"".$values[$j]."\", ";
          data_entry_helper::$javascript .= "]},";
        }
        data_entry_helper::$javascript .= "
  ]
};
jQuery('[name$=occAttr\\:".$parts[0]."],[name*=occAttr\\:".$parts[0]."\\:]').live('change',
  function(){
    var setval = set_up_child_select($(this),rule_".$parts[0]."_".$parts[1].");
    jQuery(this).closest('tr').find('[name$=occAttr\\:".$parts[1]."],[name*=occAttr\\:".$parts[1]."\\:]').val(setval).change();
  });
jQuery('[name$=occAttr\\:".$parts[0]."],[name*=occAttr\\:".$parts[0]."\\:]').each(function(){
    set_up_child_select($(this),rule_".$parts[0]."_".$parts[1].");
});
";
      }
    }
    if (!empty($args['attributeValidation'])) {
      $rules = array();
      $argRules = explode(';', $args['attributeValidation']);
      foreach($argRules as $rule){
        $rules[] = explode(',', $rule);
      }
      foreach($rules as $rule)
      // But only do if a parameter given as rule:param - eg min:-40
        for($i=1; $i<count($rule); $i++)
          if(strpos($rule[$i], ':') !== false){
            $details = explode(':', $rule[$i]);
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').attr('".$details[0]."',".$details[1].");";
          } else if($rule[$i]=='no_observation'){
               data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').filter(':checkbox').rules('add', {no_observation: true});
hook_species_checklist_delete_row=function() {
  if(jQuery('.scPresence').filter(':checkbox').filter('[checked]').length==0)
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').removeAttr('disabled');
  else
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').filter(':checkbox').attr('disabled','disabled').removeAttr('checked');
};
hook_species_checklist_delete_row();
$.validator.addMethod('no_observation', function(arg1, arg2){
  var numRows = jQuery('.scPresence').filter(':checkbox').filter('[checked]').length;
  var isChecked = jQuery('[name='+jQuery(arg2).attr('name')+']').not(':hidden').filter('[checked]').length>0;
  if(isChecked) return(numRows==0)
  else if(numRows>0) return true;
  // Not checked, no rows: ensure no obs can be filled in, and flag failure.
  jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').removeAttr('disabled','disabled');
  // this is being used against a boolean checkbox, which has a hidden zero field before. Have to tag on to later field explicitly.
  return false;
},
  \"".lang::get('validation_no_observation')."\");
";
          } else if($rule[$i]=='integer'){
               data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').rules('add', {integer: true});";
          } else if(substr($rule[0], 3, 4)!= 'Attr'){ // have to add for non attribute case.
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').addClass('".$rule[$i]."');";
          }
    }
    
    return '';
  }
  protected static function get_control_lateJS($auth, $args, $tabalias, $options) {
  data_entry_helper::$onload_javascript .= "setSref = function(sref){
  $('#imp-sref').val(sref);
  if (sref.indexOf(' ')!==-1) {
    var parts=sref.split(' ');
    parts[0]=parts[0].split(',')[0]; // part 1 may have a comma at the end, so remove
    $('#imp-srefX').val(parts[0]);
    $('#imp-srefY').val(parts[1]);
}};
setDrawnGeom = function() {
  // need to leave the location parent id enabled. Don't need to set geometries as we are using an existing location.
  jQuery(\"#locWebsite,#imp-geom,#imp-boundary-geom,#imp-sref,#imp-srefX,#imp-srefY\").removeAttr('disabled');
  jQuery(\"#sample_location_id\").attr('disabled','disabled');
  jQuery(\"#location_name\").attr('name','location:name').removeAttr('readonly');;
};
addDrawnGeomToSelection = function(geometry) {
  // Create the polygon as drawn, only 1 polygon at a time
  var feature = new OpenLayers.Feature.Vector(geometry, {});
  PolygonLayer.destroyFeatures();
  PolygonLayer.addFeatures([feature]);
  wkt = '';
  points = geometry.components[0].getVertices();
  setDrawnGeom();
  jQuery(\"#location_name\").val('');
  for(var i = 0; i< points.length; i++)
    wkt = wkt+(i==0? '' : ', ')+points[i].x+' '+points[i].y;
  wkt = wkt+', '+points[0].x+' '+points[0].y;
  jQuery(\"#imp-boundary-geom\").val(\"POLYGON((\" + wkt + \"))\");
  centre = geometry.getCentroid();
  jQuery(\"#imp-geom\").val(\"POINT(\" + centre.x + \"  \" + centre.y + \")\");
  jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/spatial/wkt_to_sref?wkt=POINT(\" + centre.x + \"  \" + centre.y + \")&system=2169&precision=8&callback=?\",
    function(data){
      if(typeof data.error != 'undefined')
        alert(data.error);
      else
        setSref(data.sref);});
};
var SiteSelector = new OpenLayers.Control.SelectFeature(SiteListLayer);
SiteListLayer.map.addControl(SiteSelector);
// onSelect {Function} Optional function to be called when a feature is selected. 
// callbacks {Object} The functions that are sent to the handlers.feature for callback
var drawControl=new OpenLayers.Control.DrawFeature(PolygonLayer,OpenLayers.Handler.Polygon,{'displayClass':'olControlDrawFeaturePolygon', drawFeature: addDrawnGeomToSelection});
PolygonLayer.map.addControl(drawControl);
function setSpecifiedLocation() {
  // need to leave the location parent id enabled. Don't need to set geometries as we are using an existing location.
  jQuery(\"#locWebsite,#imp-geom,#imp-boundary-geom,#imp-sref,#imp-srefX,#imp-srefY\").attr('disabled','disabled');
  jQuery(\"#sample_location_id\").removeAttr('disabled');
  jQuery(\"#location_name\").attr('name','sample:location_name').attr('readonly','readonly');
}
function onFeatureSelect(evt) {
  feature = evt.feature;
  newFeature=feature.clone();
  PolygonLayer.destroyFeatures();
  PolygonLayer.addFeatures([newFeature]);
  SiteSelector.unselect(feature);
  setSpecifiedLocation();
  jQuery(\"#sample_location_id\").val(feature.attributes.id);
  jQuery(\"#location_name\").val(feature.attributes.name);
  if(feature.attributes.sref=='TBC'){
    centre = feature.geometry.getCentroid();
    jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/spatial/wkt_to_sref?wkt=POINT(\" + centre.x + \"  \" + centre.y + \")&system=2169&precision=8&callback=?\",
      function(data){
        if(typeof data.error != 'undefined')
          alert(data.error);
        else
          setSref(data.sref);
       });
  } else {
    setSref(feature.attributes.sref);
  }
}
SiteListLayer.events.on({
    'featureselected': onFeatureSelect
});
PolygonLayer.map.editLayer.clickControl.deactivate();
PolygonLayer.map.editLayer.destroyFeatures();
drawControl.activate();
SiteSelector.activate();
";
    if(isset(data_entry_helper::$entity_to_load['location:id']))
      data_entry_helper::$onload_javascript .= "setSpecifiedLocation();
loadFeatures(".data_entry_helper::$entity_to_load['location:parent_id'].",".data_entry_helper::$entity_to_load['location:id'].");
";
    if (array_key_exists('sample:id', data_entry_helper::$entity_to_load))
      data_entry_helper::$late_javascript .= "
setupButtons($('#controls'), 1);
setupButtons($('#controls'), 2);
setupButtons($('#controls'), 0);";
      
    return '';
  }
  
  protected static function get_control_locationbuttons($auth, $args, $tabalias, $options) {
    return '<input type="button" value="'.lang::get('Zoom to Grid').'" onclick="ZoomToDataExtent(ParentLocationLayer);">
<input type="button" value="'.lang::get('Zoom to Location').'" onclick="ZoomToDataExtent(PolygonLayer);">
<input type="button" value="'.lang::get('View All Luxembourg').'" onclick="ViewAllLuxembourg('.$args['map_centroid_lat'].','.$args['map_centroid_long'].','.((int) $args['map_zoom']).');">';
//	var center = new OpenLayers.LonLat(".$args['map_centroid_long'].", ".$args['map_centroid_lat'].");
//	center.transform(div.map.displayProjection, div.map.projection);
//	div.map.setCenter(center, ".((int) $args['map_zoom']).");
    //<input type="button" value="'.lang::get('View All Luxembourg').'" onclick="ViewAllLuxembourg();">';
  }
  protected static function getSampleListGridPreamble() {
    global $user;
    $r = '<p>'.lang::get('LANG_SampleListGrid_Preamble').(iform_loctools_checkaccess(self::$node,'superuser') ? lang::get('LANG_All_Users') : $user->name).'</p>';
    return $r;
  }
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    $extraParams = $auth['read'];
    if ($args['species_names_filter']=='preferred') {
      $extraParams += array('preferred' => 't');
    }
    if ($args['species_names_filter']=='language') {
      $extraParams += array('language' => iform_lang_iso_639_2($user->lang));
    }  
    // multiple species being input via a grid      
    $species_ctrl_opts=array_merge(array(
          'listId'=>$args['list_id'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'occurrenceComment'=>$args['occurrence_comment'],
          'occurrenceConfidential'=>(isset($args['occurrence_confidential']) ? $args['occurrence_confidential'] : false),
          'occurrenceImages'=>$args['occurrence_images'],
          'PHPtaxonLabel' => true,
          'language' => iform_lang_iso_639_2($user->lang) // used for termlists in attributes
    ), $options);
    if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
      
    call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args);
    call_user_func(array(get_called_class(), 'build_grid_autocomplete_function'), $args);
      
    // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
    // then output the grid control
    return '<input type="hidden" value="true" name="gridmode" />'.
          self::species_checklist($species_ctrl_opts);
  }
  

  public static function species_checklist()
  {
  	global $indicia_templates;
    $options = data_entry_helper::check_arguments(func_get_args(), array('listId', 'occAttrs', 'readAuth', 'extraParams', 'lookupListId'));
    $options = data_entry_helper::get_species_checklist_options($options);
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    $occAttrControls = array();
    $occAttrs = array();
    // Load any existing sample's occurrence data into $entity_to_load
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      data_entry_helper::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'], $options['readAuth'], false);
    // load the full list of species for the grid, including the main checklist plus any additional species in the reloaded occurrences.
    $occList = self::get_species_checklist_occ_list($options);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $occList)) {
      $attributes = data_entry_helper::getAttributes(array(
          'id' => null
           ,'valuetable'=>'occurrence_attribute_value'
           ,'attrtable'=>'occurrence_attribute'
           ,'key'=>'occurrence_id'
           ,'fieldprefix'=>"{fieldname}"
//           ,'fieldprefix'=>"sc:-ttlId-::occAttr"
           ,'extraParams'=>$options['readAuth']
           ,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null
      ));
      // Get the attribute and control information required to build the custom occurrence attribute columns
      data_entry_helper::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $grid = "\n";
      if (isset($options['lookupListId'])) {
        $grid .= data_entry_helper::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $grid .= '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $grid .= data_entry_helper::get_species_checklist_header($options, $occAttrs);
      $rows = array();
      $rowIdx = 0;
      foreach ($occList as $occ) {
        $ttlid = $occ['taxon']['id'];
        $firstCell = data_entry_helper::mergeParamsIntoTemplate($occ['taxon'], 'taxon_label');
        if ($options['PHPtaxonLabel']) $firstCell=eval($firstCell);
        $colspan = isset($options['lookupListId']) && $options['rowInclusionCheck']!='alwaysRemovable' ? ' colspan="2"' : '';
        $row = '';
        // Add a X button if the user can remove rows
        if ($options['rowInclusionCheck']=='alwaysRemovable') $row .= '<td class="ui-state-default remove-row" style="width: 1%">X</td>';
        $row .= str_replace('{content}', $firstCell, str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']));

        $existing_record_id = $occ['id'];
        $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
        // AlwaysFixed mode means all rows in the default checklist are included as occurrences. Same for
        // AlwayeRemovable except that the rows can be removed.
        if ($options['rowInclusionCheck']=='alwaysFixed' || $options['rowInclusionCheck']=='alwaysRemovable' ||
            (data_entry_helper::$entity_to_load!=null && array_key_exists("sc:$ttlid:$existing_record_id:present", data_entry_helper::$entity_to_load))) {
          $checked = ' checked="checked"';
        } else {
          $checked='';
        }
        $row .= "<td class=\"scPresenceCell\"$hidden>".($options['rowInclusionCheck']!='hasData' ? "<input type=\"hidden\" class=\"scPresence\" name=\"sc:$ttlid:$existing_record_id:present\" value=\"0\"/><input type=\"checkbox\" class=\"scPresence\" name=\"sc:$ttlid:$existing_record_id:present\" $checked />" : '')."</td>";
        foreach ($occAttrControls as $attrId => $control) {
          if ($existing_record_id) {
            $search = preg_grep("/^sc:$ttlid:$existing_record_id:occAttr:$attrId".'[:[0-9]*]?$/', array_keys(data_entry_helper::$entity_to_load));
            $ctrlId = (count($search)===1) ? implode('', $search) : "sc:$ttlid:$existing_record_id:occAttr:$attrId";
          } else {
            $ctrlId = "sc:$ttlid:x$rowIdx:occAttr:$attrId";
          }
          if (isset(data_entry_helper::$entity_to_load[$ctrlId])) {
            $existing_value = data_entry_helper::$entity_to_load[$ctrlId];
          } elseif (array_key_exists('default', $attributes[$attrId])) {
            $existing_value = $attributes[$attrId]['default'];
          } else
            $existing_value = '';
          $oc = str_replace('{fieldname}', $ctrlId, $control);
          if (!empty($existing_value)) {
            // For select controls, specify which option is selected from the existing value
            if (substr($oc, 0, 7)=='<select') {
              $oc = str_replace('value="'.$existing_value.'"',
                  'value="'.$existing_value.'" selected="selected"', $oc);
            } else if(strpos($oc, 'checkbox') !== false) {
              if($existing_value=="1")
                $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
            } else {
              $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
            }
            // assume all error handling/validation done client side
          }
          $row .= str_replace(array('{label}', '{content}'), array(lang::get($attributes[$attrId]['caption']), $oc), $indicia_templates[$options['attrCellTemplate']]);
        }
        if ($options['occurrenceComment']) {
          $row .= "\n<td class=\"ui-widget-content scCommentCell\"><input class=\"scComment\" type=\"text\" name=\"sc:$ttlid:$existing_record_id:occurrence:comment\" ".
          "id=\"sc:$ttlid:$existing_record_id:occurrence:comment\" value=\"".data_entry_helper::$entity_to_load["sc:$ttlid:$existing_record_id:occurrence:comment"]."\" /></td>";
        }
        // no confidential checkbox.
        $rows[$rowIdx]=$row; // only one column, no images.
        $rowIdx++;
      }
      $grid .= "\n<tbody>\n";
      if (count($rows)>0) $grid .= "<tr>".implode("</tr>\n<tr>", $rows)."</tr>\n";
      else $grid .= "<tr style=\"display: none\"><td></td></tr>\n";
      $grid .= "</tbody>\n</table>\n";
      if ($options['rowInclusionCheck']=='hasData') $grid .= '<input name="rowInclusionCheck" value="hasData" type="hidden" />';
      // If the lookupListId parameter is specified then the user is able to add extra rows to the grid,
      // selecting the species from this list. Add the required controls for this.
      if (isset($options['lookupListId'])) {
        // Javascript to add further rows to the grid
        if (isset($indicia_templates['format_species_autocomplete_fn'])) {
          data_entry_helper::$javascript .= 'var formatter = '.$indicia_templates['format_species_autocomplete_fn'];
        } else {
          data_entry_helper::$javascript .= "var formatter = '".$indicia_templates['taxon_label']."';\n";
        }
        data_entry_helper::$javascript .= "addRowToGrid('".data_entry_helper::$base_url."index.php/services/data"."', '".
            $options['id']."', '".$options['lookupListId']."', {'auth_token' : '".
            $options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'},".
            " formatter);\r\n";
      }
      // No help text
      return $grid;
    } else {
      return $taxalist['error'];
    }
  }

  public static function get_species_checklist_occ_list($options) {
    // at this point the data_entry_helper::$entity_to_load has been preloaded with the occurrence data.
  	// Get the list of species that are always added to the grid
    if (isset($options['listId']) && !empty($options['listId'])) {
      $taxalist = data_entry_helper::get_population_data($options);
    } else
      $taxalist = array();
    // copy the options array so we can modify it
    $extraTaxonOptions = array_merge(array(), $options);
    // We don't want to filter the taxa to be added, because if they are in the sample, then they must be included whatever.
    unset($extraTaxonOptions['extraParams']['taxon_list_id']);
    unset($extraTaxonOptions['extraParams']['preferred']);
    unset($extraTaxonOptions['extraParams']['language_iso']);
     // append the taxa to the list to load into the grid
    $fullTaxalist = data_entry_helper::get_population_data($extraTaxonOptions);
    $taxaLoaded = array();
    $occList = array();
    $maxgensequence = 0;
    foreach(data_entry_helper::$entity_to_load as $key => $value) {
      $parts = explode(':', $key,4);
      // Is this taxon attribute data?
      if (count($parts) > 2 && $parts[0] == 'sc' && $parts[1]!='-ttlId-') {
        if($parts[2]=='') $occList['error'] = 'ERROR PROCESSING entity_to_load: found name '.$key.' with no sequence/id number in part 2';
        else if(!isset($occList[$parts[2]])){
          $occ['id'] = $parts[2];
          foreach($fullTaxalist as $taxon){
            if($parts[1] == $taxon['id']) $occ['taxon'] = $taxon;
            $taxaLoaded[] = $parts[1];
          }
          $occList[$parts[2]] = $occ;
          if(!is_numeric($parts[2])) $maxgensequence = intval(max(substr($parts[2],1),$maxgensequence));
        }
      }
    }
    if (!isset(data_entry_helper::$entity_to_load['sample:id']))
      foreach ($taxalist as $taxon){
        if(!in_array($taxon['id'], $taxaLoaded)) {
          $maxgensequence++;
          $occ['id'] = 'x'.$maxgensequence;
          $occ['taxon'] = $taxon;
          $occList[] = $occ;
        }
      }
  	return $occList;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    if (isset($values['sample:recorder_names'])){
      if(is_array($values['sample:recorder_names'])){
        $values['sample:recorder_names'] = implode("\r\n", $values['sample:recorder_names']);
      }
    } // else just load the string
    if (isset($values['location:name'])) $values['sample:location_name'] = $values['location:name'];
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    if(!isset($values['sample:deleted'])) {
      if (isset($values['gridmode']))
        $occurrences = self::wrap_species_checklist($values);
      else
        $occurrences = submission_builder::wrap_with_images($values, 'occurrence');
      if(count($occurrences)>0) 
        $sampleMod['subModels'] = $occurrences;
      if (isset($values['location:name'])){
        $locationMod = submission_builder::wrap_with_images($values, 'location');
        $locationMod['subModels'] = array(array('fkId' => 'location_id', 'model' => $sampleMod));
        if(array_key_exists('locations_website:website_id', $_POST)){
          $lw = submission_builder::wrap_with_images($values, 'locations_website');
          $locationMod['subModels'][] = array('fkId' => 'location_id', 'model' => $lw);
        }
        return $locationMod;
      }
    }
    return $sampleMod;
  }
  
  /**
  * Wraps data from a species checklist grid: modified from original data_entry_helper
  * function to allow multiple rows for the same species.
  */
  private static function wrap_species_checklist($arr, $include_if_any_data=false){
    if (array_key_exists('website_id', $arr))
      $website_id = $arr['website_id'];
    else throw new Exception('Cannot find website id in POST array!');
    // Set the default method of looking for rows to include - either using data, or the checkbox (which could be hidden)
    $include_if_any_data = $include_if_any_data || (isset($arr['rowInclusionCheck']) && $arr['rowInclusionCheck']=='hasData');
    // Species checklist entries take the following format
    // sc:<taxa_taxon_list_id>:[<occurrence_id>|<sequence(negative)>]:occAttr:<occurrence_attribute_id>[:<occurrence_attribute_value_id>]
    // sc:<taxa_taxon_list_id>:[<occurrence_id>]:occurrence:comment
    // sc:<taxa_taxon_list_id>:[<occurrence_id>]:present
    // not doing occurrence images at this point - TBD
    $records = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      if (substr($key, 0, 3)=='sc:'){
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 4);
        if($a[2]){
          $records[$a[2]]['taxa_taxon_list_id'] = $a[1];
          $records[$a[2]][$a[3]] = $value;
          // store any id so update existing record
          if(is_numeric($a[2])) $records[$a[2]]['id'] = $a[2];
        }
      }
    }
    foreach ($records as $id => $record) {
      $present = self::wrap_species_checklist_record_present($record, $include_if_any_data);
      if (array_key_exists('id', $record) || $present) { // must always handle row if already present in the db
        if (!$present) $record['deleted'] = 't';
        $record['website_id'] = $website_id;
        if (array_key_exists('occurrence:determiner_id', $arr)) $record['determiner_id'] = $arr['occurrence:determiner_id'];
        if (array_key_exists('occurrence:record_status', $arr)) $record['record_status'] = $arr['occurrence:record_status'];
        $occ = data_entry_helper::wrap($record, 'occurrence');
        $subModels[] = array('fkId' => 'sample_id', 'model' => $occ);
      }
    }
    return $subModels;
  }

  /**
   * Test whether the data extracted from the $_POST for a species_checklist grid row refers to an occurrence record.
   * @access Private
   */
  private static function wrap_species_checklist_record_present($record, $include_if_any_data) {
    unset($record['taxa_taxon_list_id']); // discard ttlid, as no bearing on entered data.
  	// as we are working on a copy of the record, discard the ID so it is easy to check if there is any other data for the row.
    unset($record['id']);
    $recordData=implode('',$record);
    return ($include_if_any_data && $recordData!='' && !preg_match("/^[0]*$/", $recordData)) ||       // inclusion of record is detected from having a non-zero value in any cell
      (!$include_if_any_data && array_key_exists('present', $record) && $record['present']!='0'); // inclusion of record detected from the presence checkbox
  }
  
  protected function getReportActions() {
    return array(array('display' => '', 'actions' => 
            array(array('caption' => 'Edit', 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))),
        array('display' => '', 'actions' => 
            array(array('caption' => 'Delete', 'javascript'=>'deleteSurvey({sample_id})'))));
  }
  
  /**
   * Construct JavaScript to read and transform a geometry from the supplied
   * object name.
   * @param string $name Name of the existing geometry object to read the feature from.
   * @param string $proj EPSG code for the projection we want the feature in.
   */
  private static function readGeomJs($name, $proj) {
    $r = "feature = parser.read($name);";
	if ($proj!='900913') {
	  $r .= "\n    feature.geometry.transform(new OpenLayers.Projection('EPSG:900913'), new OpenLayers.Projection('EPSG:" . $proj . "'));";
    }
    return $r;
  }

  /**
   * Build a PHP function  to format the species added to the grid according to the form parameters
   * autocomplete_include_both_names and autocomplete_include_taxon_group.
   * We have an issue with Common names, as the view only gives one, but we have 3 non latin languages.
   */
  protected static function build_grid_autocomplete_function($args) {
    global $indicia_templates;  
    // always include the searched name
    $fn = "function(item) { \n".
        "  var r;\n".
        "  if (item.preferred=='t') {\n".
        "    r = '<em>'+item.taxon+'</em>';\n".
        "  } else {\n".
        "    r = item.taxon;\n".
        "  }\n".
        "  r += '<span class=\"extraCommonNames\" tID=\"'+item.id+'\"></span>';\n".
        " return r;\n".
        "}\n";
    // Set it into the indicia templates
    $indicia_templates['format_species_autocomplete_fn'] = $fn;
  }
  
  /**
   * Build a JavaScript function  to format the autocomplete item list according to the form parameters
   * autocomplete_include_both_names and autocomplete_include_taxon_group.
   */
  protected static function build_grid_taxon_label_function($args) {
    global $indicia_templates;
    // always include the searched name
    $php = '$r="";'."\n".
        'if ("{preferred}"=="t") {'."\n".
        '  $r .= "<em>{taxon}</em>";'."\n".
        '} else {'."\n".
        '  $r .= "{taxon}";'."\n".
        '}'."\n".
        '$taxa_list_args=array('."\n".
        '  "extraParams"=>array("website_id"=>'.$args['website_id'].','."\n".
        '    "view"=>"detail",'."\n".
        '    "auth_token"=>"'.self::$auth['read']['auth_token'].'",'."\n".
        '    "nonce"=>"'.self::$auth['read']['nonce'].'",'."\n".
        '    "taxon_list_id"=>"'.$args["extra_list_id"].'"),'."\n".
        '  "table"=>"taxa_taxon_list");'."\n".
        '$responseRecords = data_entry_helper::get_population_data($taxa_list_args);'."\n".
        '$taxa_list_args["extraParams"]["taxon_list_id"] = "'.$args["list_id"].'";'."\n".
        '$initResponseRecords = data_entry_helper::get_population_data($taxa_list_args);'."\n".
        '$initResponseRecords = array_merge($initResponseRecords, $responseRecords);'."\n".
        '$taxaList = "";'."\n".
        '$taxaMeaning = -1;'."\n".
        'foreach ($initResponseRecords as $record)'."\n".
        '  if($record["id"] == {id}) $taxaMeaning=$record["taxon_meaning_id"];'."\n".
        'foreach ($responseRecords as $record){'."\n".
        '  if($taxaMeaning==$record["taxon_meaning_id"]){'."\n".
        '    if($record["preferred"] == "f")'."\n".
        '      $taxaList .= ($taxaList == "" ? "" : ", ").$record["taxon"];'."\n".
        '    else'."\n".
        '      $r = "<em>".$record["taxon"]."</em>";'."\n".
        '}}'."\n".
        'if($taxaList != "") $r .= " - ".$taxaList;'."\n".
        'return $r;'."\n";
    // Set it into the indicia templates
    $indicia_templates['taxon_label'] = $php;
  }
  
}