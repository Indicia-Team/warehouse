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

/* Development Stream: TBD
 * 
 * Future possibles:
 * add map to main grid, Populate with positions of samples?
 * add close locations to map in site tab: geoserver view. Could hover -> WMS feature request, Click event to select item under it, or if there is no item, as if clicking edit Layer.
 * checks on species list re adding existing taxon
 * 
 * On Installation:
 * Need to set attributeValidation required for locAttrs for Village, site type, site follow up, and smpAttrs Visit, human freq, microclimate (including min, max) 
 * Need to manually set the term list sort order on non-default language tems.
 * Need to set the control of Visit to a select, and for the cavity entrance to a checkbox group.
 */
require_once('mnhnl_dynamic_1.php');

class iform_mnhnl_bats extends iform_mnhnl_dynamic_1 {
  
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_bats_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Winter Bats form. Inherits from Dynamic 1.'
    );
  }

  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Bats';  
  }

  public static function get_perms($nid) {
    return array('IForm n'.$nid.' admin', 'IForm n'.$nid.' user');
  }
  
  public static function get_parameters() {   
    $parentVal = parent::get_parameters();
    $retVal = array();
    foreach($parentVal as $param){
      if($param['name'] == 'structure'){
        $param['default'] =
             "=Site=\r\n".
              "[custom JS]\r\n".
              "[location module]\r\n".
              "@extendNameAttribute=<TBD>\r\n".
              "[location attributes]\r\n".
              "@lookUpListCtrl=radio_group\r\n".
              "@lookUpKey=meaning_id\r\n".
              "@sep= \r\n".
              "[spatial reference]\r\n".
              "@fieldname=location:centroid_sref\r\n".
              "@srefField=location:centroid_sref\r\n".
              "@splitLatLong=true\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "@scroll_wheel_zoom=false\r\n".
              "@searchUpdatesSref=true\r\n".
              "[location comment]\r\n".
              "[*]\r\n".
             "=Other Information=\r\n".
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
              "[species attributes]\r\n".
              "[*]\r\n";
      }
      if($param['name'] == 'attribute_termlist_language_filter')
        $param['default'] = true;
      if($param['name'] == 'grid_report')
        $param['default'] = 'reports_for_prebuilt_forms/mnhnl_bats_grid';
        
      if($param['name'] != 'species_include_taxon_group' &&
          $param['name'] != 'link_species_popups' &&
          $param['name'] != 'species_include_both_names')
        $retVal[] = $param;
    }
    $retVal[] = array(
          'name' => 'siteTypeOtherTermID',
          'caption' => 'Site Type Attribute, Other Term ID',
          'description' => 'The site type has an Other choice which when selected allows an additional text field to be filled in. This field holds the Indicia term meaning id for the radiobutton.',
          'type' => 'int',
          'group' => 'User Interface'
        );
    $retVal[] = array(
          'name' => 'siteTypeOtherAttrID',
          'caption' => 'Site Type Other Attribute ID',
          'description' => 'The site type has an Other choice which when selected allows an additional text field to be filled in. This field holds the text field Indicia attribute id',
          'type' => 'int',
          'group' => 'User Interface'
        );
    $retVal[] = array(
          'name' => 'entranceDefectiveTermID',
          'caption' => 'Entrance hole Attribute, Defective Term ID',
          'description' => 'The Entrance hole attribute has a Defective choice which when selected allows an additional text field to be filled in. This field holds the Indicia term meaning id for the checkbox.',
          'type' => 'int',
          'group' => 'User Interface'
        );
    $retVal[] = array(
          'name' => 'entranceDefectiveCommentAttrID',
          'caption' => 'Defective Entrance Comment Attribute ID',
          'description' => 'The Entrance hole attribute has a Defective choice which when selected allows an additional text field to be filled in. This field holds the text field Indicia attribute id',
          'type' => 'int',
          'group' => 'User Interface'
        );
    $retVal[] = array(
          'name' => 'disturbanceOtherTermID',
          'caption' => 'Entrance hole Attribute, Defective Term ID',
          'description' => 'The Disturbances attribute has an Other choice which when selected allows an additional text field to be filled in. This field holds the Indicia term meaning id for the checkbox.',
          'type' => 'int',
          'group' => 'User Interface'
        );
    $retVal[] = array(
          'name' => 'disturbanceCommentAttrID',
          'caption' => 'Disturbance Other Comment Attribute ID',
          'description' => 'The Disturbances attribute has an Other choice which when selected allows an additional text field to be filled in. This field holds the text field Indicia attribute id',
          'type' => 'int',
          'group' => 'User Interface'
        );
    $retVal[] = array(
          'name' => 'removeBreakIDs',
          'caption' => 'Attributes to remove the break after',
          'description' => 'The Attributes to remove the break after. This text field holds a colon separated list of Indicia attribute ids',
          'type' => 'string',
          'group' => 'User Interface'
        );
    $retVal[] = array(
          'name'=>'attributeValidation',
          'caption'=>'Attribute Validation Rules',
          'description'=>'Client Validation rules to be enforced on attributes: allows more options than allowed by straight class led validation.',
          'type'=>'textarea',
          'required' => false,
          'group' => 'User Interface'
        );
        
        
    return $retVal;
  }

  /**
   * Insert any custom JS for this form: this may be related to attributes, which are included
   * as part of inherited generic code.
   * Does not include any HTML.
   */
  protected static function get_control_customJS($auth, $args, $tabalias, $options) {
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
  	if(lang::get('validation_required')!='validation_required'){
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
    }
    $numRows=3;
    $numCols=2;
    $startPos=2;
    data_entry_helper::$javascript .= "
jQuery('<div class=\"ui-widget-content ui-state-default ui-corner-all indicia-button tab-cancel\"><span><a href=\"".$reloadPath."\">".lang::get('LANG_Cancel')."</a></span></div>').appendTo('.buttons');
checkRadioStatus = function(){
  jQuery('[name^=locAttr]').filter(':radio').filter('[value=".$args['siteTypeOtherTermID']."]').each(function(){
    if(this.checked)
      jQuery('[name=locAttr\\:".$args['siteTypeOtherAttrID']."],[name^=locAttr\\:".$args['siteTypeOtherAttrID']."\\:]').addClass('required').removeAttr('readonly');
    else
      jQuery('[name=locAttr\\:".$args['siteTypeOtherAttrID']."],[name^=locAttr\\:".$args['siteTypeOtherAttrID']."\\:]').removeClass('required').val('').attr('readonly',true);
  });
};
jQuery('[name^=locAttr]').filter(':radio').change(checkRadioStatus);
checkRadioStatus();

var other = jQuery('[name=locAttr\\:".$args['siteTypeOtherAttrID']."],[name^=locAttr\\:".$args['siteTypeOtherAttrID']."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name^=locAttr]').filter(':radio').filter('[value=".$args['siteTypeOtherTermID']."]').parent().append(other);

var other = jQuery('[name=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."],[name^=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name^=smpAttr]').filter(':checkbox').filter('[value=".$args['entranceDefectiveTermID']."]').parent().append(other);

var other = jQuery('[name=smpAttr\\:".$args['disturbanceCommentAttrID']."],[name^=smpAttr\\:".$args['disturbanceCommentAttrID']."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name^=smpAttr]').filter(':checkbox').filter('[value=".$args['disturbanceOtherTermID']."]').parent().append(other);

checkCheckStatus = function(){
  jQuery('[name^=smpAttr]').filter(':checkbox').filter('[value=".$args['entranceDefectiveTermID']."]').each(function(){
    if(this.checked) // note not setting the required flag.
      jQuery('[name=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."],[name^=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."\\:]').removeAttr('readonly');
    else
      jQuery('[name=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."],[name^=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."\\:]').val('').attr('readonly',true);
  });
  jQuery('[name^=smpAttr]').filter(':checkbox').filter('[value=".$args['disturbanceOtherTermID']."]').each(function(){
    if(this.checked)
      jQuery('[name=smpAttr\\:".$args['disturbanceCommentAttrID']."],[name^=smpAttr\\:".$args['disturbanceCommentAttrID']."\\:]').addClass('required').removeAttr('readonly');
    else
      jQuery('[name=smpAttr\\:".$args['disturbanceCommentAttrID']."],[name^=smpAttr\\:".$args['disturbanceCommentAttrID']."\\:]').removeClass('required').val('').attr('readonly',true);
  });
  };
jQuery('[name^=smpAttr]').filter(':checkbox').change(checkCheckStatus);
checkCheckStatus();

// put lat and long side by side, and set labels to auto width so they fit in.
jQuery('#imp-sref-lat').next().remove();
jQuery('#imp-sref-lat').prev().css('width', 'auto');
jQuery('#imp-sref-long').prev().css('width', 'auto');
checkSystemStatus = function(){
  var srefSystem = jQuery('#imp-sref-system').val();
  if(srefSystem == 4326){
    jQuery('#imp-sref-lat').prev().empty().text('".lang::get('Latitude').":');
    jQuery('#imp-sref-long').prev().empty().text('".lang::get('Longitude').":');
  } else {
    jQuery('#imp-sref-lat').prev().empty().text('".lang::get('X').":');
    jQuery('#imp-sref-long').prev().empty().text('".lang::get('Y').":');
  }
};
jQuery('#imp-sref-system').change(function(){
  checkSystemStatus();
  jQuery('#imp-sref').val('');
  jQuery('#imp-sref-lat').val('');
  jQuery('#imp-sref-long').val('');
  jQuery('#imp-geom').val('');
  if(jQuery('#map')[0].map) jQuery('#map')[0].map.editLayer.destroyFeatures();
});
checkSystemStatus();
// Two aspects: need to find scClonableRow, and then do existing rows in grid, and clear row being added.
var occAttrs = jQuery('.scClonableRow').find('.scOccAttrCell');
var newTable = jQuery('<table class=\"fullWidth\">');
var newElem = jQuery('<td class=\"noPadding\" >').append(newTable).insertBefore(occAttrs.filter(':first'));
for (var i=0;i<$numRows;i++){
	switch(i){";
    for($i=0; $i<$numRows; $i++){
    	data_entry_helper::$javascript .= "
		case($i): jQuery(\"<tr><td class='scOccAttrCell ui-widget-content'>".lang::get('SCLabel_Row'.($i+1))."</td></tr>\").appendTo(newTable); break;";
    }
    data_entry_helper::$javascript .= "
	}
}
for (var i=0;i<occAttrs.length;i++){
	jQuery(occAttrs[i]).find('input').addClass('number').attr('min',0);
	if(i%$numRows == 0){
		newTable = jQuery('<table class=\"fullWidth\">');
		newElem = jQuery('<td class=\"noPadding\">').append(newTable).insertAfter(newElem);
	}
	jQuery('<tr>').append(occAttrs[i]).appendTo(newTable);
}
var CRgroup = jQuery('.scClonableRow').find('table').find('td');
// Do main table header
occAttrs = jQuery('.species-grid > thead').find('th');
var newElem = jQuery('<th>').insertBefore(occAttrs.filter(':eq($startPos)'));
for (var i=0;i<$numRows*$numCols;i++){
	jQuery(occAttrs[i+$startPos]).remove();
}
for (var i=0;i<$numCols;i++){
	switch(i){";
    for($i=0; $i<$numCols; $i++){
    	data_entry_helper::$javascript .= "
		case($i): newElem = jQuery(\"<th>".lang::get('SCLabel_Col'.($i+1))."</th>\").insertAfter(newElem); break;";
    }
    data_entry_helper::$javascript .= "
	}
}
// Main table existing entries
speciesRows = jQuery('.species-grid > tbody').find('tr');
for(var j=0; j<speciesRows.length; j++){
	occAttrs = jQuery(speciesRows[j]).find('.scOccAttrCell');
	newTable = jQuery('<table class=\"fullWidth\">');
	newElem = jQuery('<td class=\"noPadding\" >').append(newTable).insertBefore(occAttrs.filter(':first'));
	for (var i=0;i<$numRows;i++){
		switch(i){";
    for($i=0; $i<$numRows; $i++){
    	data_entry_helper::$javascript .= "
			case($i): jQuery(\"<tr><td class='scOccAttrCell ui-widget-content'>".lang::get('SCLabel_Row'.($i+1))."</td></tr>\").appendTo(newTable); break;";
    }
    data_entry_helper::$javascript .= "
		}
	}
	for (var i=0;i<occAttrs.length;i++){
		jQuery(occAttrs[i]).find('input').addClass('number').attr('min',0);
		if(i%$numRows == 0){
			newTable = jQuery('<table class=\"fullWidth\">');
			newElem = jQuery('<td class=\"noPadding\" >').append(newTable).insertAfter(newElem);
		}
		jQuery('<tr>').append(occAttrs[i]).appendTo(newTable);
	}
	var group = jQuery(speciesRows[j]).find('table').find('td');
	var tallest = 0;
	group.each(function(){ tallest = Math.max($(this).outerHeight(), tallest); });
	group.each(function(){ 
		$(this).height(tallest); });
	CRgroup.each(function(){ 
		$(this).height(tallest); });
}
";
    if (isset($args['col_widths']) && $args['col_widths']){
       $colWidths=explode(',', $args['col_widths']);
       for($i=0; $i<count($colWidths); $i++){
       		data_entry_helper::$javascript .= "
jQuery('.species-grid > thead').find('th').filter(':eq(".$i.")').width('";
       	if($colWidths[$i]==''){
       		data_entry_helper::$javascript .= "auto');";
       	} else {
       		data_entry_helper::$javascript .= $colWidths[$i]."%');";
       	}
       }
    }
    
    // Move the Temperature and Humidity fields side by side.
    $removeBreakIDs = explode(':', $args['removeBreakIDs']);
    foreach($removeBreakIDs as $removeBreakID){
      data_entry_helper::$javascript .= "
jQuery('[name=smpAttr\\:".$removeBreakID."],[name^=smpAttr\\:".$removeBreakID."\\:]').css('margin-right', '20px').nextAll('br').eq(0).remove();";
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
jQuery('[name=".$rule[0]."],[name^=".$rule[0]."\\:]').attr('".$details[0]."',".$details[1].");";
          } else if($rule[$i]=='no_observation'){
               data_entry_helper::$late_javascript .= "
jQuery('[name=".$rule[0]."],[name^=".$rule[0]."\\:]').filter(':checkbox').rules('add', {no_observation: true});
hook_species_checklist_delete_row=function() {
  var rows=jQuery('.species-grid > tbody > tr').not(':hidden').not('.scClonableRow').length;
  if(rows==0)
    jQuery('[name=".$rule[0]."],[name^=".$rule[0]."\\:]').removeAttr('disabled');
  else
    jQuery('[name=".$rule[0]."],[name^=".$rule[0]."\\:]').attr('disabled','disabled').removeAttr('checked');
};
// possible clash with link_species_popups, so latter disabled.
hook_species_checklist_new_row=function(rowData) {
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list/' + rowData.id +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(mdata) {
    if(mdata instanceof Array && mdata.length>0){
      jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list' +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&taxon_meaning_id='+mdata[0].taxon_meaning_id+'&callback=?', function(data) {
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
            if(jQuery('[name^=sc\\:'+data[i].id+'\\:]').length > 0)
              duplicate=true;
          }
          if(duplicate){
            alert(\"".lang::get('LANG_Duplicate_Taxon')."\");
            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').closest('tr').remove();
          } else
            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').append(' - '+taxaList).removeClass('extraCommonNames');
        }
      });
    }})
    hook_species_checklist_delete_row();;
}
hook_species_checklist_delete_row();
$.validator.addMethod('no_observation', function(arg1, arg2){
var numChecked = jQuery('[name^=sc]').not(':hidden').not('[name^=sc\\:-ttlId-]').filter(':radio').filter('[checked=true]').length;
var numFilledIn = jQuery('[name^=sc]').not(':hidden').not('[name^=sc\\:-ttlId-]').not(':radio').filter('[value!=]').length;
if(jQuery('[name='+jQuery(arg2).attr('name')+']').not(':hidden').filter('[checked=true]').length>0)
 // is checked.
 return(numChecked==0&&numFilledIn==0)
else if(numChecked>0||numFilledIn>0)
 return true;
// there are no rows filled in, in which case ensure no obs can be filled in.
jQuery('[name=".$rule[0]."],[name^=".$rule[0]."\\:]').removeAttr('disabled','disabled');
return false;
},
  \"".lang::get('validation_no_observation')."\");
  ";
          }
    }
    return '';
  }
  
  /**
   * Get the block of custom attributes at the location level
   */
  protected static function get_control_locationattributes($auth, $args, $tabalias, $options) {
    $attrArgs = array(
       'valuetable'=>'location_attribute_value',
       'attrtable'=>'location_attribute',
       'key'=>'location_id',
       'fieldprefix'=>'locAttr',
       'extraParams'=>$auth['read'],
       'survey_id'=>$args['survey_id']
      );
    if (array_key_exists('location:id', data_entry_helper::$entity_to_load) && data_entry_helper::$entity_to_load['location:id']!="") {
      // if we have location Id to load, use it to get attribute values
      $attrArgs['id'] = data_entry_helper::$entity_to_load['location:id'];
    }
    $locationAttributes = data_entry_helper::getAttributes($attrArgs);
    $defAttrOptions = array_merge(
        array('extraParams' => array_merge($auth['read'], array('view'=>'detail')),
              'language' => iform_lang_iso_639_2($args['language'])),$options);
    $r = get_attribute_html($locationAttributes, $args, $defAttrOptions);
    return $r;
  }

  /**
   * Get the location comment control
   */
  protected static function get_control_locationcomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'location:comment',
      'label'=>lang::get('Location Comment')
    ), $options)); 
  }
  
  /**
   * Get the location module control
   */
  protected static function get_control_locationmodule($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    // have to override the name of the imp-geom to point to the location centroid_geometry
    $indicia_templates['sref_textbox'] = '<input type="text" id="{id}" name="{fieldname}" {class} {disabled} value="{default}" />' .
        '<input type="hidden" id="imp-geom" name="{table}:centroid_geom" value="{defaultGeom}" />';
    
    // at this point the entity to load either holds location data if there has been an error, or needs
    // to be populated with it.
    // we are assuming no loctools.
    if (array_key_exists('sample:location_id', data_entry_helper::$entity_to_load)&&
        !array_key_exists('location:id', data_entry_helper::$entity_to_load)){
      data_entry_helper::load_existing_record($auth['read'], 'location', data_entry_helper::$entity_to_load['sample:location_id']);
      // next two are a bit of a bodge to get map control to display initial feature from location table
      // the sample:wkt and sample:geom should not be passed through to POST.
      data_entry_helper::$entity_to_load['location:geom'] = data_entry_helper::$entity_to_load['location:centroid_geom'];
      data_entry_helper::$entity_to_load['sample:geom'] = data_entry_helper::$entity_to_load['location:centroid_geom'];
      data_entry_helper::$entity_to_load['sample:wkt'] = data_entry_helper::$entity_to_load['location:centroid_geom'];
    };
    // self::add_resource('json');
    $location_list_args=array_merge(array(
        'nocache'=>true,
        'includeCodeSearch'=>false,
        'includeCodeField'=>true,
        'CodeLabel'=>lang::get('LANG_Location_Code_Label'),
        'CodeBlankText'=>lang::get('LANG_Location_Code_Blank_Text'),
        'CodeFieldName'=>'dummy:location_code_DD',
        'CodeID'=>'imp-location-code',
        'NameLabel'=>lang::get('LANG_Location_Name_Label'),
        'NameBlankText'=>lang::get('LANG_Location_Name_Blank_Text'),
        'NameFieldName'=>'dummy:location_name_DD',
        'NameID'=>'imp-location-name',
        'view'=>'detail',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $auth['read']),
        'table'=>'location',
        'template' => 'select',
        'itemTemplate' => 'select_item',
        'filterField'=>'parent_id',
        'size'=>3
    ), $options);
    if (array_key_exists('location_type_id', $options)) {
      $location_list_args['extraParams'] += array('location_type_id' => $options['location_type_id']);
    }
    // Idea here is to get a list of all locations in order to build drop downs.
    // control used can be configured on Indicia
    $responseRecords = data_entry_helper::get_population_data($location_list_args);
    // The way this will work: have 2 drop downs: code and name. Doesn't matter what they are set to
    // investigate self::init_linked_lists($options);
    if (isset($responseRecords['error']))
      return $responseRecords['error'];
    $attributeRecords = array();
    if (isset($options['extendNameAttribute'])){
      $attribute_list_args=array_merge(array(
        'nocache'=>true,
        'view'=>'list',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id'], 'location_attribute_id'=>$options['extendNameAttribute']), $auth['read']),
        'table'=>'location_attribute_value'
      ), $options);
      $attributeResponse = data_entry_helper::get_population_data($attribute_list_args);
      foreach ($attributeResponse as $record){
        $attributeRecords[$record['location_id']] = $record;
      }
    }
    $CodeOpts = '';
    $NameOpts = '';
    foreach ($responseRecords as $record){
      if($record['code']!='' && $location_list_args['includeCodeSearch']){
         $item = array('selected' => ((array_key_exists('location:id', data_entry_helper::$entity_to_load) &&
                                      intval(data_entry_helper::$entity_to_load['location:id'])==intval($record['id'])) ? 'selected' : ''),
                      'value' => $record['id'],
                      'caption' => $record['code']);
        $CodeOpts .= data_entry_helper::mergeParamsIntoTemplate($item, $location_list_args['itemTemplate']);
      }
      if($record['name']!=''){
        $item = array('selected' => ((array_key_exists('location:id', data_entry_helper::$entity_to_load) &&
                                      data_entry_helper::$entity_to_load['location:id']==$record['id']) ? 'selected' : ''),
                      'value' => $record['id'],
                      'caption' => $record['name'].(isset($options['extendNameAttribute']) ? ' ('.$attributeRecords[$record['id']]['value'].')' : ''));
        $NameOpts .= data_entry_helper::mergeParamsIntoTemplate($item, $location_list_args['itemTemplate']);
      }
    }
    $r = '<fieldset><legend>'.lang::get('Existing Locations').'</legend><input type="hidden" id="imp-location" name="location:id" value="'.(array_key_exists('location:id', data_entry_helper::$entity_to_load) ? data_entry_helper::$entity_to_load['location:id'] : ''). '" >';
    if($CodeOpts != ''){
      $location_list_args['label']=$location_list_args['CodeLabel'];
      $location_list_args['fieldname']=$location_list_args['CodeFieldName'];
      $location_list_args['id']=$location_list_args['CodeID'];
      $location_list_args['items'] = str_replace(array('{value}', '{caption}', '{selected}'),
          array('', htmlentities($location_list_args['CodeBlankText'])),
          $indicia_templates[$location_list_args['itemTemplate']]).$CodeOpts;
      $r .= data_entry_helper::apply_template($location_list_args['template'], $location_list_args);
    }
    if($NameOpts != ''){
      $location_list_args['label']=$location_list_args['NameLabel'];
      $location_list_args['fieldname']=$location_list_args['NameFieldName'];
      $location_list_args['id']=$location_list_args['NameID'];
      $location_list_args['items'] = str_replace(array('{value}', '{caption}', '{selected}'),
          array('', htmlentities($location_list_args['NameBlankText'])),
          $indicia_templates[$location_list_args['itemTemplate']]).$NameOpts;
      $r .= data_entry_helper::apply_template($location_list_args['template'], $location_list_args);
    }
    $isAdmin = user_access('IForm n'.$node->nid.' admin');
    data_entry_helper::$javascript .= "
setLocationEditable = function(enableFields){
  var enableItems;
  var disableItems;
  disableItems = '[name=location\\:id],[name=location\\:code]'; //clearing the location so no ID, so disable 
  enableItems = '[name=locations_website\\:website_id]'; // but have to activate website record 
  if(!enableFields){
    if(jQuery('#map')[0].map != undefined) jQuery('#map')[0].map.editLayer.clickControl.deactivate()
    disableItems = disableItems + ',[name=location\\:name],[name=location\\:comment],[name^=locAttr\\:],#imp-sref-lat,#imp-sref-long,#imp-sref-system,#imp-geom';
  } else {
    if(jQuery('#map')[0].map != undefined) jQuery('#map')[0].map.editLayer.clickControl.activate()
    enableItems = enableItems + ',[name=location\\:name],[name=location\\:comment],[name^=locAttr\\:],#imp-sref-lat,#imp-sref-long,#imp-sref-system,#imp-geom';
  }
  jQuery(enableItems).removeAttr('disabled');
  jQuery(disableItems).attr('disabled',true);
};
clearLocation = function(){
  jQuery('[name=location\\:id],[name=location\\:code],[name=location\\:name],[name=location\\:comment],#imp-sref,#imp-sref-lat,#imp-sref-long,#imp-geom').val('');
  // first need to remove any hidden multiselect checkbox unclick fields
  jQuery('[name^=locAttr\\:]').filter('.multiselect').remove();
  // rename, to be safe, removing any [] at the end or any attribute value id
  jQuery('[name^=locAttr\\:]').each(function(){
    var name = jQuery(this).attr('name').split(':');
    if(name[1].indexOf('[]') > 0) name[1] = name[1].substr(0, name[1].indexOf('[]'));
    jQuery(this).attr('name', name[0]+':'+name[1]);
  });
  // Then add [] to multiple choice checkboxes.
  jQuery('[name^=locAttr\\:]').filter(':checkbox').removeAttr('checked').each(function(){
    var myName = jQuery(this).attr('name').split(':');
    var similar = jQuery('[name='+myName[0]+'\\:'+myName[1]+'],[name='+myName[0]+'\\:'+myName[1]+'\\[\\]]').filter(':checkbox');
    if(similar.length > 1)
      jQuery(this).attr('name', myName[0]+':'+myName[1]+'[]');
  });
  // radio buttons all share the same name, only one checked.
  jQuery('[name^=locAttr\\:]').filter(':radio').removeAttr('checked');
  // checkboxes are all unchecked
  // boolean checkboxes have extra field to force zero if unselected, but there are no attributes of that type in use for this form at the moment, so leave uncoded.
  jQuery('[name^=locAttr\\:]').filter(':checkbox').removeAttr('checked');
  jQuery('[name^=locAttr\\:]').filter(':text').val('');
  checkSystemStatus();
  checkRadioStatus();
};
loadLocation = function(myValue){
  if (myValue!=='') {
    // Change the location control requests the location's geometry to place on the map.
    jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/sample?location_id='+myValue +
            '&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(sdata) {
      ".($isAdmin ? "": "
      // Count returns: if only one, then normal bods can edit
      if (sdata instanceof Array && sdata.length==1) {
        setLocationEditable(true);
      }")."
      jQuery('[name=location\\:id]').val(myValue).removeAttr('disabled');
      jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/location/'+myValue +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(data) {
       // store value in saved field?
       if (data instanceof Array && data.length>0) {
        jQuery('[name=location\\:code]').val(data[0].code);
        jQuery('[name=location\\:name]').val(data[0].name);
        jQuery('[name=location\\:comment]').val(data[0].comment);
        jQuery('#imp-sref-system').val(data[0].centroid_sref_system);
        checkSystemStatus();
        jQuery('#imp-geom').val(data[0].centroid_geom);
        jQuery('#imp-sref').val(data[0].centroid_sref);
        var refxy = data[0].centroid_sref.split(' ');
        var refx = refxy[0].split(',');
        jQuery('#imp-sref-lat').val(refx[0]);
        jQuery('#imp-sref-long').val(refxy[1]).change();
        jQuery('[name=locations_website\\:website_id]').attr('disabled','disabled');
       }
      });
      jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/location_attribute_value' +
            '?mode=json&view=list&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&location_id='+myValue+'&callback=?', function(data) {
       if(data instanceof Array && data.length>0){
        for (var i=0;i<data.length;i++){
          if (data[i].id) { // && (data[i].iso == null || data[i].iso == '' || data[i].iso == '".$language."')
            var radiobuttons = jQuery('[name=locAttr\\:'+data[i]['location_attribute_id']+'],[name^=locAttr\\:'+data[i]['location_attribute_id']+'\\:]').filter(':radio');
            var multicheckboxes = jQuery('[name=locAttr\\:'+data[i]['location_attribute_id']+'\\[\\]],[name^=locAttr\\:'+data[i]['location_attribute_id']+':]').filter(':checkbox');
            // at the moment there are no boolean checkboxes so don't code for them
            if(radiobuttons.length > 0){ // radio buttons all share the same name, only one checked.
              radiobuttons.attr('name', 'locAttr:'+data[i]['location_attribute_id']+':'+data[i].id)
                  .filter('[value='+data[i].raw_value+']').attr('checked', 'checked');
            } else if(multicheckboxes.length > 0){ // individually named
              multicheckboxes = multicheckboxes.filter('[value='+data[i].raw_value+']')
                        .attr('name', 'locAttr:'+data[i]['location_attribute_id']+':'+data[i].id).attr('checked', 'checked');
              multicheckboxes.each(function(){
                jQuery('<input type=\"hidden\" value=\"0\" class=\"multiselect\" >').attr('name', jQuery(this).attr('name')).insertBefore(this);
              });
            } // at the moment there are no boolean checkboxes so don't code for them
            else {
              jQuery('[name=locAttr\\:'+data[i]['location_attribute_id']+']')
                      .attr('name', 'locAttr:'+data[i]['location_attribute_id']+':'+data[i].id).val(data[i].raw_value);
            }
          }
        }
       }
       checkRadioStatus();
      });
    });
  } else
    setLocationEditable(false);
};
jQuery('#imp-location-name').change(function(){
  var myValue = jQuery('#imp-location-name').val();
  jQuery('#imp-location').val(myValue).change();
  jQuery('#imp-location-code').val(myValue);
  clearLocation();
  setLocationEditable(".($isAdmin ? "true" : "false").");
  loadLocation(myValue);
  });
jQuery('#imp-location-code').change(function(){
  var myValue = jQuery('#imp-location-code').val();
  jQuery('#imp-location').val(myValue).change();
  jQuery('#imp-location-name').val(myValue);
  clearLocation();
  setLocationEditable(".($isAdmin ? "true" : "false").");
  loadLocation(myValue);
});
newLocation = function(){
  jQuery('#imp-location').val('').change();
  jQuery('#imp-location').attr('disabled');
  jQuery('#imp-location-name').val('');
  jQuery('#imp-location-code').val('');
  clearLocation();
  setLocationEditable(true);
};
jQuery('#imp-location').change(function(){
  jQuery('#imp-location').removeAttr('disabled');
});
";
    if(array_key_exists('sample:id', data_entry_helper::$entity_to_load)) {
      if($isAdmin || !array_key_exists('sample:location_id', data_entry_helper::$entity_to_load))
      // existing sample, either admin or not existing location (validation error situation)
        data_entry_helper::$onload_javascript .= "
setLocationEditable(true);";
      else 
            data_entry_helper::$onload_javascript .= "
setLocationEditable(false);
jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/sample?location_id=".data_entry_helper::$entity_to_load['sample:location_id']."' +
            '&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(sdata) {
      // Count returns: if only one, then normal bods can edit
      if (sdata instanceof Array && sdata.length==1) {
        setLocationEditable(true);
      }});";
  } else { // newSample
    if(self::$mode == 1 )// newSample mode rather than error situation
        data_entry_helper::$onload_javascript .= "
clearLocation();
setLocationEditable(false);";
    else if($isAdmin || !array_key_exists('sample:location_id', data_entry_helper::$entity_to_load))
        data_entry_helper::$onload_javascript .= "
setLocationEditable(true);";
      else 
            data_entry_helper::$onload_javascript .= "
setLocationEditable(false);
jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/sample?location_id=".data_entry_helper::$entity_to_load['sample:location_id']."' +
            '&mode=json&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(sdata) {
      // Count returns: if only one, then normal bods can edit
      if (sdata instanceof Array && sdata.length==1) {
        setLocationEditable(true);
      }});";
  }
    $r .= '<input type="button" value="'.lang::get('Create New Location').'" onclick="newLocation();">'.
      '<input type="hidden" id="locations_website:website_id" name="locations_website:website_id" value="'.$args['website_id'].'" disabled="'.(array_key_exists('location:id', data_entry_helper::$entity_to_load) ? 'disabled' : ''). '" />'.
      '</fieldset>'.
      '<label for="location:name">'.lang::get('LANG_Location_Name_Label').':</label>'.
      '<input type="text" id="location:name" name="location:name" class="required" value="'.data_entry_helper::$entity_to_load['location:name'].'" /><span class="deh-required">*</span><br/>';
    if($location_list_args['includeCodeField'])
      $r .= '<label for="location:code">'.lang::get('LANG_Location_Code_Label').':</label><input type="text" id="location:code" name="location:code" disabled="disabled" value="'.data_entry_helper::$entity_to_load['location:code'].'" /><br/>';
    
    return $r;
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
        '    "nonce"=>"'.self::$auth['read']['nonce'].'"),'."\n".
        '  "table"=>"taxa_taxon_list");'."\n".
        '$responseRecords = data_entry_helper::get_population_data($taxa_list_args);'."\n".
        '$taxaList = "";'."\n".
        '$taxaMeaning = -1;'."\n".
        'foreach ($responseRecords as $record)'."\n".
        '  if($record["id"] == {id}) $taxaMeaning=$record["taxon_meaning_id"];'."\n".
        'foreach ($responseRecords as $record){'."\n".
        '  if($record["id"] != {id} && $taxaMeaning==$record["taxon_meaning_id"]){'."\n".
        '    if($record["preferred"] == "f")'."\n".
        '      $taxaList .= ($taxaList == "" ? "" : ", ").$record["taxon"];'."\n".
        '    else'."\n".
        '      $taxaList = "<em>".$record["taxon"]."</em>".($taxaList == "" ? "" : ", ".$taxaList);'."\n".
        '}}'."\n".
        '$r .= " - ".$taxaList;'."\n".
        'return $r;'."\n";
    // Set it into the indicia templates
    $indicia_templates['taxon_label'] = $php;
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
    if (isset($values['gridmode']))
      $occurrences = data_entry_helper::wrap_species_checklist($values);
    else
      $occurrences = submission_builder::wrap_with_images($values, 'occurrence');
    // when a non admin selects an existing location they can not modify it or its attributes and the location record does not form part of the submission
    if (isset($values['location:name'])){
      $sampleMod = submission_builder::wrap_with_images($values, 'sample');
      if(count($occurrences)>0) 
          $sampleMod['subModels'] = $occurrences;
      $locationMod = submission_builder::wrap_with_images($values, 'location');
      $locationMod['subModels'] = array(array('fkId' => 'location_id', 'model' => $sampleMod));
      if(array_key_exists('locations_website:website_id', $_POST)){
        $lw = submission_builder::wrap_with_images($values, 'locations_website');
        $locationMod['subModels'][] = array('fkId' => 'location_id', 'model' => $lw);
      }
      return $locationMod;
    }
    $values['sample:location_id'] = $values['location:id'];
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    if(count($occurrences)>0) 
          $sampleMod['subModels'] = $occurrences;
    return $sampleMod;
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_bats.css');
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected function getArgDefaults(&$args) {
    $args['includeLocTools'] == false; 
  }

  protected function getReportActions() {
    return array(array('display' => lang::get('Actions'), 'actions' => 
        array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))));
  }
  
} 