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
 * 
 * On Installation:
 * Need to set attributeValidation required for locAttrs for Village, site type, site follow up, and smpAttrs Visit, human freq, microclimate (including min, max) 
 * Need to manually set the term list sort order on non-default language tems.
 * Need to set the control of Visit to a select, and for the cavity entrance to a checkbox group.
 */
require_once('mnhnl_dynamic_1.php');
require_once('includes/mnhnl_common.php');

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
    $retVal=array();
    $parentVal = array_merge(
      parent::get_parameters(),
      iform_mnhnl_getParameters(),
      array(
        array(
          'name' => 'siteTypeOtherTermID',
          'caption' => 'Site Type Attribute, Other Term ID',
          'description' => 'The site type has an Other choice which when selected allows an additional text field to be filled in. This field holds the Indicia term meaning id for the radiobutton.',
          'type' => 'int',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'siteTypeOtherAttrID',
          'caption' => 'Site Type Other Attribute ID',
          'description' => 'The site type has an Other choice which when selected allows an additional text field to be filled in. This field holds the text field Indicia attribute id',
          'type' => 'int',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'entranceDefectiveTermID',
          'caption' => 'Entrance hole Attribute, Defective Term ID',
          'description' => 'The Entrance hole attribute has a Defective choice which when selected allows an additional text field to be filled in. This field holds the Indicia term meaning id for the checkbox.',
          'type' => 'int',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'entranceDefectiveCommentAttrID',
          'caption' => 'Defective Entrance Comment Attribute ID',
          'description' => 'The Entrance hole attribute has a Defective choice which when selected allows an additional text field to be filled in. This field holds the text field Indicia attribute id',
          'type' => 'int',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'disturbanceOtherTermID',
          'caption' => 'Disturbance Attribute, Other Term ID',
          'description' => 'The Disturbances attribute has an Other choice which when selected allows an additional text field to be filled in. This field holds the Indicia term meaning id for the checkbox.',
          'type' => 'int',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'disturbanceCommentAttrID',
          'caption' => 'Disturbance Other Comment Attribute ID',
          'description' => 'The Disturbances attribute has an Other choice which when selected allows an additional text field to be filled in. This field holds the text field Indicia attribute id',
          'type' => 'int',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'removeBreakIDs',
          'caption' => 'Attributes to remove the break after',
          'description' => 'The Attributes to remove the break after. This text field holds a colon separated list of Indicia attribute ids',
          'type' => 'string',
          'group' => 'User Interface'
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
          'name'=>'communeLayerLookup',
          'caption'=>'WFS Layer specification for Commune Lookup',
          'description'=>'Comma separated: proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames',
          'type'=>'string',
          'required' => false,
          'group'=>'Georeferencing',
        ),
        array(
          'name'=>'locationWMSLayerLookup',
          'caption'=>'WMS Layer specification for Location Layer',
          'description'=>'Comma separated: proxiedurl,layer',
          'type'=>'string',
          'group'=>'Locations',
        )
      )
    );
  	
    foreach($parentVal as $param){
      if($param['name'] == 'structure'){
        $param['default'] =
             "=Site=\r\n".
              "[custom JS]\r\n".
              "[location module]\r\n".
              "[location attributes]\r\n".
              "@lookUpListCtrl=radio_group\r\n".
              "@lookUpKey=meaning_id\r\n".
              "@sep= \r\n".
              "@tabNameFilter=Site\r\n".
              "@class=wide\r\n".
              "[location spatial reference]\r\n".
              "[location attributes]\r\n".
              "@tabNameFilter=SpatialRef\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "@layers=['SiteAreaLayer','SitePathLayer','SitePointLayer','SiteLabelLayer']\r\n".
              "@scroll_wheel_zoom=false\r\n".
              "@searchUpdatesSref=true\r\n".
              "@maxZoom=13\r\n".
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
              "@sep= \r\n".
              "@lookUpKey=meaning_id\r\n".
              "[*]\r\n".
              "[lateJS]\r\n";
      }
      if($param['name'] == 'attribute_termlist_language_filter')
        $param['default'] = true;
      if($param['name'] == 'grid_report')
        $param['default'] = 'reports_for_prebuilt_forms/MNHNL/mnhnl_bats_grid';
        
      if($param['name'] != 'species_include_taxon_group' &&
          $param['name'] != 'link_species_popups' &&
          $param['name'] != 'species_include_both_names' &&
          $param['name'] != 'includeLocTools' &&
          $param['name'] != 'loctoolsLocTypeID')
        $retVal[] = $param;
    }
    return $retVal;
  }

  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    $isAdmin = user_access('IForm n'.self::$node->nid.' admin');
  	if(!$isAdmin) return('');
    if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'), '#locations' => lang::get('LANG_Locations'));
    $confirmedLocationTypeID = iform_mnhnl_getTermID(self::$auth, $args['locationTypeTermListExtKey'],$args['SecondaryLocationTypeTerm']);
    $submittedLocationTypeID = iform_mnhnl_getTermID(self::$auth, $args['locationTypeTermListExtKey'],$args['LocationTypeTerm']);
    
    $r = '<div id="downloads" >
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_bats_sites_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=batsitesreport">
      <p>'.lang::get('LANG_Sites_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"website_id":'.$args['website_id'].', "survey_id":'.$args['survey_id'].', "orig_location_type_id":'.$confirmedLocationTypeID.', "new_location_type_id":'.$submittedLocationTypeID.'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_bats_conditions_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=batconditionsreport">
      <p>'.lang::get('LANG_Conditions_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
        <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_bats_species_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=batspeciesreport">
      <p>'.lang::get('LANG_Species_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
  </div>'.iform_mnhnl_locModTool(self::$auth, $args, self::$node);
    self::communeJS(self::$auth, $args);
    return $r;
  }
  
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    if ($user->uid===0)
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    $userIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS User ID');
    if (!$userIdAttr) return lang::get('This form must be used with a survey that has the CMS User ID sample attribute associated with it so records can be tagged against their creator.');
    $usernameAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS Username');
    if (!$usernameAttr) return lang::get('This form must be used with a survey that has the CMS Username sampleattribute associated with it so records can be tagged against their creator.');
    $villageAttr=iform_mnhnl_getAttrID($auth, $args, 'location', 'Village');
    if (!$villageAttr) return lang::get('This form must be used with a survey that has the Village location attribute associated with it.');
    $communeAttr=iform_mnhnl_getAttrID($auth, $args, 'location', 'Commune');
    if (!$communeAttr) return lang::get('This form must be used with a survey that has the Commune location attribute associated with it.');
    $reportName = $args['grid_report'];
    if(method_exists(get_called_class(), 'getSampleListGridPreamble'))
      $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    else
      $r = '';
    $isAdmin = user_access('IForm n'.$node->nid.' admin');
    $extraparams = array('survey_id'=>$args['survey_id'],
        'userID_attr_id'=>$userIdAttr,
        'username_attr_id'=>$usernameAttr,
        'village_attr_id'=>$villageAttr,
        'commune_attr_id'=>$communeAttr);
    if($isAdmin) {
      $extraparams['userID'] = -1;
    } else {
      $extraparams['userID'] = $user->uid;
    }
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' => $args['grid_num_rows'],
      'autoParamsForm' => true,
      'extraParams' => $extraparams
    ));    
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= "</form>
<div style=\"display:none\" />
    <form id=\"form-delete-survey\" action=\"".iform_mnhnl_getReloadPath()."\" method=\"POST\">".self::$auth['write']."
       <input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />
       <input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />
       <input type=\"hidden\" name=\"sample:id\" value=\"\" />
       <input type=\"hidden\" name=\"sample:deleted\" value=\"t\" />
    </form>
</div>";
    data_entry_helper::$javascript .= "
deleteSurvey = function(sampleID){
  if(confirm(\"".lang::get('Are you sure you wish to delete survey')." \"+sampleID)){
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

  /**
   * Insert any custom JS for this form: this may be related to attributes, which are included
   * as part of inherited generic code.
   * Does not include any HTML.
   */
  protected static function get_control_customJS($auth, $args, $tabalias, $options) {
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
    if(lang::get('validation_integer') != 'validation_integer')
      data_entry_helper::$late_javascript .= "
$.validator.messages.integer = $.validator.format(\"".lang::get('validation_integer')."\");";
      data_entry_helper::$late_javascript .= "
$.validator.addMethod('fillgroup', function(value, element){
	return jQuery(element).closest('table').find('input').not('[value=]').length > 0 ||
	       jQuery(element).closest('table').parent().next().find(':checkbox').filter('[checked]').length > 0;
},
  \"".lang::get('validation_fillgroup')."\");";
      
    $numRows=2;
    $numCols=1;
    $startPos=2;
    iform_mnhnl_addCancelButton();
    data_entry_helper::$javascript .= "
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
other.removeClass('wide').remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name^=locAttr]').filter(':radio').filter('[value=".$args['siteTypeOtherTermID']."]').parent().append(other);

var other = jQuery('[name=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."],[name^=smpAttr\\:".$args['entranceDefectiveCommentAttrID']."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.removeClass('wide').remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name^=smpAttr]').filter(':checkbox').filter('[value=".$args['entranceDefectiveTermID']."]').parent().append(other);

var other = jQuery('[name=smpAttr\\:".$args['disturbanceCommentAttrID']."],[name^=smpAttr\\:".$args['disturbanceCommentAttrID']."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.removeClass('wide').remove(); // remove Other field, then bolt in after the other radio button.
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

// Two aspects: need to find scClonableRow, and then do existing rows in grid, and clear row being added.
var occAttrs = jQuery('.scClonableRow').find('.scOccAttrCell');
var newTable = jQuery('<table class=\"fullWidth\">');
var newElem = jQuery('<td class=\"noPadding\" colspan=".($numCols+1).">').append(newTable).insertBefore(occAttrs.filter(':first'));
for (var i=0;i<$numRows;i++){
	switch(i){";
    for($i=0; $i<$numRows; $i++){
    	data_entry_helper::$javascript .= "
		case($i): newRow = jQuery(\"<tr><td class='ui-widget-content' width='".(100/($numCols+1))."%'>".lang::get('SCLabel_Row'.($i+1))."</td></tr>\").appendTo(newTable); break;";
    }
    data_entry_helper::$javascript .= "
	}
	for (var j=0;j<$numCols;j++){
		newRow.append(occAttrs[i+(j*$numRows)]);
	}
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
	occAttrs.find('input').eq(0).addClass('fillgroup');
	newTable = jQuery('<table class=\"fullWidth\">');
	newElem = jQuery('<td class=\"noPadding\" colspan=".($numCols+1).">').append(newTable).insertBefore(occAttrs.filter(':first'));
	for (var i=0;i<$numRows;i++){
		switch(i){";
    for($i=0; $i<$numRows; $i++){
    	data_entry_helper::$javascript .= "
			case($i): newRow = jQuery(\"<tr><td class='ui-widget-content' width='".(100/($numCols+1))."%'>".lang::get('SCLabel_Row'.($i+1))."</td></tr>\").appendTo(newTable); break;";
    }
    data_entry_helper::$javascript .= "
		}
		for (var k=0;k<$numCols;k++){
			newRow.append(occAttrs[i+(k*$numRows)]);
		}
	}
	occAttrs.find('select').addClass('required').width('85%').after('<span class=\"deh-required\">*</span>');
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
jQuery('.species-grid > thead').find('th').not(':hidden').filter(':eq(".$i.")').width('";
       	if($colWidths[$i]==''){
       		data_entry_helper::$javascript .= "auto');";
       	} else {
       		data_entry_helper::$javascript .= $colWidths[$i]."%');";
       	}
       }
    }
    
    // Move the Temperature and Humidity fields side by side.
    $removeBreakIDs = explode(';', $args['removeBreakIDs']);
    foreach($removeBreakIDs as $removeBreakID){
      $removeBreakID = str_replace(':', '\\:', $removeBreakID);
      data_entry_helper::$javascript .= "
jQuery('[name=".$removeBreakID."],[name^=".$removeBreakID."\\:]').css('margin-right', '20px').nextAll('br').eq(0).remove();";
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
hook_species_checklist_pre_delete_row=function(e) {
  if(!confirm(\"".lang::get('Are you sure you want to delete this row?')."\")) return false;
  var row = $(e.target.parentNode);
  row.find('*').removeClass('ui-state-error');
  row.find('.inline-error').remove();
  return true;
};
// possible clash with link_species_popups, so latter disabled. First get the meaning id for the taxon, then all taxa with that meaning.
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
              // look for a checked presence checkbox that starts with this taxon ID
              if(jQuery('.scPresence').filter(':checkbox').filter('[checked]').filter('[name^=sc\\:'+data[i].id+'\\:]').length>0)
                duplicate=true;
            } else
              if(jQuery('.scPresence').filter(':checkbox').filter('[checked]').filter('[name^=sc\\:'+data[i].id+'\\:]').length>1)
                duplicate=true;
          }
          if(duplicate){
            alert(\"".lang::get('LANG_Duplicate_Taxon')."\");
            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').closest('tr').remove();
          } else {
            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').closest('tr').find('.scOccAttrCell').find('input').eq(0).addClass('fillgroup');
            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').closest('tr').find('.scOccAttrCell').find('select').addClass('required').width('85%').after('<span class=\"deh-required\">*</span>');
            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').append(' - '+taxaList).removeClass('extraCommonNames');
          }
        }
      });
    }})
    hook_species_checklist_delete_row();
}
hook_species_checklist_delete_row();
$.validator.addMethod('no_observation', function(arg1, arg2){
  var numRows = jQuery('.scPresence').filter(':checkbox').filter('[checked]').length;
  var isChecked = jQuery('[name='+jQuery(arg2).attr('name')+']').not(':hidden').filter('[checked]').length>0;
  if(isChecked) return(numRows==0)
  else if(numRows>0) return true;
  // Not checked, no rows: ensure no obs can be filled in, and flag failure.
  jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').removeAttr('disabled');
  // this is being used against a boolean checkbox, which has a hidden zero field before. Have to tag on to later field explicitly.
  return false;
},
  \"".lang::get('validation_no_observation')."\");
  ";
          } else if(substr($rule[0], 3, 4)!= 'Attr'){ // have to add for non attribute case.
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').addClass('".$rule[$i]."');";
          }
    }
    if (array_key_exists('sample:id', data_entry_helper::$entity_to_load))
      data_entry_helper::$late_javascript .= "
setupButtons($('#controls'), 1);
setupButtons($('#controls'), 2);
setupButtons($('#controls'), 0);";
    $smpAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ));
    foreach($smpAttributes as $attrId => $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'Bat visit')==0) {
    data_entry_helper::$late_javascript .= "
jQuery('#smpAttr\\\\:$attrId').next().after(\"<span class='extra-text'>".lang::get('LANG_Site_Extra')."</span>\");";
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
    $tabName = (isset($options['tabNameFilter']) ? $options['tabNameFilter'] : null);
    if (array_key_exists('location:id', data_entry_helper::$entity_to_load) && data_entry_helper::$entity_to_load['location:id']!="") {
      // if we have location Id to load, use it to get attribute values
      $attrArgs['id'] = data_entry_helper::$entity_to_load['location:id'];
    }
    $locationAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $defAttrOptions = array_merge(
        array('extraParams' => array_merge($auth['read'], array('view'=>'detail')),
              'language' => iform_lang_iso_639_2($args['language'])),$options);
    $r = self::bats_get_attribute_html($locationAttributes, $args, $defAttrOptions, $tabName);
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
    $retVal = iform_mnhnl_lux5kgridControl($auth, $args, self::$node, array_merge(
      array('initLoadArgs' => '{initial: true}',
       'canCreate'=>true
       ), $options));
    $isAdmin = user_access('IForm n'.self::$node->nid.' admin');
    if(!$isAdmin)
      data_entry_helper::$javascript .= "
jQuery('#location-code').attr('readonly','readonly');
";
    data_entry_helper::$javascript .= "
// this is called after the location is cleared, including the code. If when we come to set the code
// we find it is filled in, it must have been set by fetch from DB so leave...
hook_set_defaults=function(){
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/location' +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(data) {
    // store value in saved field?
    if (data instanceof Array && data.length>0) {
      var maxCode = 0;
      for(var i = 0; i< data.length; i++){
        if(parseInt(data[i].code) > maxCode)
          maxCode = parseInt(data[i].code)
      }
      if(jQuery('[name=location\\:code]').val() == '')
        jQuery('[name=location\\:code]').val(maxCode+1);
    }});
};";
    return $retVal;
/*
//    $parts=explode(',',$args['locationWMSLayerLookup']);
//    data_entry_helper::$onload_javascript .= "
//locationListLayer = new OpenLayers.Layer.WMS('Sites',
//        '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $parts[0])."',
//        {TRANSPARENT: 'true',
//          LAYERS: '".$parts[1]."'}, {
//            isBaseLayer: false, singleTile: true, sphericalMercator: true, displayInLayerSwitcher: false});
//clickedSite = function(features, div){
//  if(features.length>0){
//    var myValue = features[0].attributes.id;
//    jQuery('#imp-location-name').val(myValue);
//    loadLocation(myValue);
//  }
//};
//";
*/
  }

  protected static function communeJS($auth, $args) {
    // proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames
    // http://localhost/geoserver/wfs,indiciaCommune,Communes,the_geom,indicia,EPSG:2169,COMMUNE
  	if(isset($args['communeLayerLookup']) && $args['communeLayerLookup']!=''){
      $communeAttr=iform_mnhnl_getAttrID($auth, $args, 'location', 'Commune');
      if (!$communeAttr) return lang::get('The lateJS control form must be used with a survey that has the Commune attribute associated with it.');
      $parts=explode(',',$args['communeLayerLookup']);
      data_entry_helper::$onload_javascript .= "communeProtocol = new OpenLayers.Protocol.WFS({
              url:  '".str_replace("{HOST}", $_SERVER['HTTP_HOST'], $parts[0])."',
              featurePrefix: '".$parts[1]."',
              featureType: '".$parts[2]."',
              geometryName:'".$parts[3]."',
              featureNS: '".$parts[4]."',
              srsName: '".$parts[5]."',
              version: '1.1.0'                  
      		  ,propertyNames: [\"".$parts[6]."\"]
});
fillCommune = function(a1){
  if(typeof a1.error.success == 'undefined' || a1.error.success == false){
    alert('".lang::get('LANG_CommuneLookUpFailed')."');
    return;
  }
  if(a1.features.length > 0)
    jQuery('[name=locAttr\\:$communeAttr],[name^=locAttr\\:$communeAttr\\:]').val(a1.features[0].attributes[\"".$parts[6]."\"]).attr('readonly','readonly');
  else {
    alert('".lang::get('LANG_PositionOutsideCommune')."');
  }
}
hook_setSref = function(geom){
  jQuery('[name=locAttr\\:$communeAttr],[name^=locAttr\\:$communeAttr\\:]').val('').attr('readonly','readonly');
  var filter = new OpenLayers.Filter.Spatial({
  		type: OpenLayers.Filter.Spatial.CONTAINS ,
    	property: '".$parts[3]."',
    	value: geom
  });
  communeProtocol.read({filter: filter, callback: fillCommune});
};";
    }
}

  protected static function get_control_lateJS($auth, $args, $tabalias, $options) {
    self::communeJS($auth, $args);
  	return iform_mnhnl_locationmodule_lateJS($auth, $args, $tabalias, $options);
}

  protected static function get_control_locationspatialreference($auth, $args, $tabalias, $options) {
    return iform_mnhnl_SrefFields($auth, $args);
  }
  
  /**
   * Get the recorder names control
   */
  protected static function get_control_recordernames($auth, $args, $tabalias, $options) {
    return iform_mnhnl_recordernamesControl(self::$node, $auth, $args, $tabalias, $options);
  }


  // This function pays no attention to the outer block. This is needed when the there is no outer/inner block pair, 
  // if the attribute is put in a single block level, then that block appears in the inner, rather than the outer .
  private function bats_get_attribute_html($attributes, $args, $defAttrOptions, $blockFilter=null, $blockOptions=null) {
   $r = '';
   foreach ($attributes as $attribute) {
    // Apply filter to only output 1 block at a time. Also hide controls that have already been handled.
    if (($blockFilter===null || strcasecmp($blockFilter,$attribute['inner_structure_block'])==0) && !isset($attribute['handled'])) {
      $options = $defAttrOptions + get_attr_validation($attribute, $args);
      if (isset($blockOptions[$attribute['fieldname']])) {
        $options = array_merge($options, $blockOptions[$attribute['fieldname']]);
      }
      $r .= data_entry_helper::outputAttribute($attribute, $options);
      $attribute['handled']=true;
    }
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
    if (isset($values['source']))
      return submission_builder::wrap_with_images($values, 'location');
    if (isset($values['sample:recorder_names'])){
      if(is_array($values['sample:recorder_names'])){
        $values['sample:recorder_names'] = implode("\r\n", $values['sample:recorder_names']);
      }
    } // else just load the string
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    if(!isset($values['sample:deleted'])) {
      if (isset($values['gridmode']))
        $occurrences = data_entry_helper::wrap_species_checklist($values);
      else
        $occurrences = submission_builder::wrap_with_images($values, 'occurrence');
      // when a non admin selects an existing location they can not modify it or its attributes and the location record does not form part of the submission
      if (isset($values['location:location_type_id'])){
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
      if(count($occurrences)>0) 
            $sampleMod['subModels'] = $occurrences;
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
    return
      array(array('display' => lang::get('Actions'),
                  'actions' => array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))),
            array('display' => '', 'actions' => 
                  array(array('caption' => 'Delete', 'javascript'=>'deleteSurvey({sample_id})'))));
  }
  
} 