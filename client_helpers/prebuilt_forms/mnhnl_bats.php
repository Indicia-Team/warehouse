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

  protected static function enforcePermissions(){
  	return true;
  }

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

  public static function get_parameters() {
    $retVal=array();
    $parentVal = array_merge(
      parent::get_parameters(),
      iform_mnhnl_getParameters(),
      array(
        array(
          'name' => 'reportFilenamePrefix',
          'caption' => 'Report Filename Prefix',
          'description' => 'Prefix to be used at the start of the download report filenames.',
          'type' => 'string',
          'default' => 'bathibernation',
          'group' => 'Reporting'
        ),
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
          'name'=>'max_species_ids',
          'caption'=>'max number of species to be returned by a search',
          'description'=>'The maximum number of species to be returned by the drop downs at any one time.',
          'default'=>25,
          'type'=>'int',
          'group'=>'Species'
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
              "@layers=[\"SiteAreaLayer\",\"SitePathLayer\",\"SitePointLayer\",\"SiteLabelLayer\"]\r\n".
              "@scroll_wheel_zoom=false\r\n".
              "@searchUpdatesSref=true\r\n".
              "@maxZoom=17\r\n".
              "[point grid]\r\n".
              "@srefs=2169,LUREF (m),X,Y,;4326,Lat/Long Deg,Lat,Long,D;4326,Lat/Long Deg:Min,Lat,Long,DM;4326,Lat/Long Deg:Min:Sec,Lat,Long,DMS\r\n".
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
    return call_user_func(array(get_called_class(), 'getExtraGridModeTabsSub'),
      $retTabs, $readAuth, $args, $attributes,
        'mnhnl_bats_sites_download_report.xml',
        'mnhnl_bats_conditions_download_report.xml',
        'mnhnl_bats_species_download_report.xml');
  }
  
  protected static function getExtraGridModeTabsSub($retTabs, $readAuth, $args, $attributes, $rep1, $rep2, $rep3) {
  	$isAdmin = user_access('IForm n'.self::$node->nid.' admin');
  	if(!$isAdmin) return('');
    if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'), '#locations' => lang::get('LANG_Locations'));
    $confirmedLocationTypeID = iform_mnhnl_getTermID(self::$auth, $args['locationTypeTermListExtKey'],$args['SecondaryLocationTypeTerm']);
    $submittedLocationTypeID = iform_mnhnl_getTermID(self::$auth, $args['locationTypeTermListExtKey'],$args['LocationTypeTerm']);
    
    $r = '<div id="downloads" >
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/'.$rep1.'&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'sitesreport">
      <p>'.lang::get('LANG_Sites_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"website_id":'.$args['website_id'].', "survey_id":'.$args['survey_id'].', "orig_location_type_id":'.$confirmedLocationTypeID.', "new_location_type_id":'.$submittedLocationTypeID.'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/'.$rep2.'&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'conditionsreport">
      <p>'.lang::get('LANG_Conditions_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/'.$rep3.'&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'speciesreport">
      <p>'.lang::get('LANG_Species_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
  </div>'.iform_mnhnl_locModTool(self::$auth, $args, self::$node);
    iform_mnhnl_addCancelButton();
    data_entry_helper::$javascript .= "
var other = jQuery('[name=locAttr\\:".$args['siteTypeOtherAttrID']."],[name^=locAttr\\:".$args['siteTypeOtherAttrID']."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.removeClass('wide').remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name^=locAttr]').filter(':radio').filter('[value=".$args['siteTypeOtherTermID']."]').parent().append(other);
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
// we arent going to move the 'if others' field.
";
    self::communeJS(self::$auth, $args);
    return $r;
  }

  protected static function getHeaderHTML($args) {
    $base = base_path();
    if(substr($base, -1)!='/') $base.='/';
    $r = '<div id="iform-header">
    <div id="iform-logo-left"><a href="http://www.environnement.public.lu" target="_blank"><img border="0" class="government-logo" alt="'.lang::get('Gouvernement').'" src="'.$base.'sites/all/files/gouv.png"></a></div>
    <div id="iform-logo-right"><a href="http://www.crpgl.lu" target="_blank"><img border="0" class="gabriel-lippmann-logo" alt="'.lang::get('Gabriel Lippmann').'" src="'.$base.drupal_get_path('module', 'iform').'/client_helpers/prebuilt_forms/images/mnhnl-gabriel-lippmann-logo.jpg"></a></div>
    </div>';
    return $r;
  }
  protected static function getTrailerHTML($args) {
    $r = '<p id="iform-trailer">'.lang::get('LANG_Trailer_Text').'</p>';
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
	return jQuery(element).closest('tr').find(':text').not('[value=]').length > 0 ||
	       jQuery(element).closest('tr').find(':checkbox').filter('[checked]').length > 0;
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
            jQuery('.extraCommonNames').filter('[tID='+mdata[0].id+']').closest('tr').find('.scOccAttrCell').find('select').addClass('required').after('<span class=\"deh-required\">*</span>');
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
    return iform_mnhnl_locationattributes($auth, $args, $tabalias, $options);
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

  protected static function get_control_pointgrid($auth, $args, $tabalias, $options) {
    return iform_mnhnl_PointGrid($auth, $args, $options); 
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
    if($args['LocationTypeTerm']=='' && isset($args['loctoolsLocTypeID'])) $args['LocationTypeTerm']=$args['loctoolsLocTypeID'];
    $primary = iform_mnhnl_getTermID($auth, $args['locationTypeTermListExtKey'],$args['LocationTypeTerm']);
    if($args['SecondaryLocationTypeTerm'] != ''){
      $secondary = iform_mnhnl_getTermID($auth, $args['locationTypeTermListExtKey'],$args['SecondaryLocationTypeTerm']);
      $loctypequery="&query='+escape(JSON.stringify({'in': ['location_type_id', [$primary, $secondary]]}))+'";
    } else {
      $loctypequery="&location_type_id=".$primary;
    }
    data_entry_helper::$javascript .= "
// this is called after the location is cleared, including the code. If when we come to set the code
// we find it is filled in, it must have been set by fetch from DB so leave...
hook_set_defaults=function(){
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/location' +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"].$loctypequery."&callback=?', function(data) {
      // store value in saved field?
      var maxCode = 0;
      if (data instanceof Array && data.length>0) {
        for(var i = 0; i< data.length; i++){
          if(parseInt(data[i].code) > maxCode)
            maxCode = parseInt(data[i].code)
        }
      }
      if(jQuery('[name=location\\:code]').val() == '')
        jQuery('[name=location\\:code]').val(maxCode+1);
    });
};";
    return $retVal;
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
  if(a1.error && (typeof a1.error.success == 'undefined' || a1.error.success == false)){
    alert(\"".lang::get('LANG_CommuneLookUpFailed')."\");
    return;
  }
  if(a1.features.length > 0)
    jQuery('[name=locAttr\\:$communeAttr],[name^=locAttr\\:$communeAttr\\:]').val(a1.features[0].attributes[\"".$parts[6]."\"]).attr('readonly','readonly');
  else {
    alert(\"".lang::get('LANG_PositionOutsideCommune')."\");
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
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    data_entry_helper::$javascript .= "
// Main table existing entries
jQuery('.scCommentLabelCell').each(function(idx,elem){
  jQuery(this).css('width',jQuery(this).find('label').css('width'));
});
speciesRows = jQuery('.species-grid > tbody').find('tr');
for(var j=0; j<speciesRows.length; j++){
	occAttrs = jQuery(speciesRows[j]).find('.scOccAttrCell');
	occAttrs.find('input').not(':hidden').addClass('fillgroup');
	occAttrs.find('select').addClass('required').after('<span class=\"deh-required\">*</span>');
}
hook_species_checklist_pre_delete_row=function(e) {
    return confirm(\"".lang::get('Are you sure you want to delete this row?')."\");
};
";
  	$extraParams = $auth['read'];
    // we want all languages, so dont filter
    // multiple species being input via a grid
    $myLanguage = iform_lang_iso_639_2($args['language']);
    if($myLanguage!='fra') $myLanguage=  'eng'; // forced, used for termlists in attributes
    $species_ctrl_opts=array_merge(array(
          "extra_list_id"=>$args["extra_list_id"],
          'listId'=>$args['list_id'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'occurrenceComment'=>$args['occurrence_comment'],
          'occurrenceConfidential'=>(isset($args['occurrence_confidential']) ? $args['occurrence_confidential'] : false),
          'occurrenceImages'=>$args['occurrence_images'],
          'PHPtaxonLabel' => true,
          'language' => $myLanguage,
          "max_species_ids"=>$args["max_species_ids"]
    ), $options);
    if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
    call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args);
    // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
    // then output the grid control
    return '<input type="hidden" value="true" name="gridmode" />'.
          self::mnhnl_bats_species_checklist($species_ctrl_opts);
  }
  

  public static function mnhnl_bats_species_checklist()
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
    $options['extraParams']['view'] = 'detail';
    $occList = self::get_species_checklist_occ_list($options);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $occList)) {
      $attributes = data_entry_helper::getAttributes(array(
          'id' => null
           ,'valuetable'=>'occurrence_attribute_value'
           ,'attrtable'=>'occurrence_attribute'
           ,'key'=>'occurrence_id'
           ,'fieldprefix'=>"{fieldname}"
           ,'extraParams'=>$options['readAuth']
           ,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null
      ));
      // Get the attribute and control information required to build the custom occurrence attribute columns
      self::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $grid = "<p>".lang::get('LANG_SpeciesInstructions')."</p>\n";
      if (isset($options['lookupListId'])) {
        $grid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $grid .= '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'">';
      // No header for this one.
      $rows = array();
      $rowIdx = 0;
      foreach ($occList as $occ) {
        $ttlid = $occ['taxon']['id'];
        $firstCell = data_entry_helper::mergeParamsIntoTemplate($occ['taxon'], 'taxon_label');
        if ($options['PHPtaxonLabel']) $firstCell=eval($firstCell);
        $colspan = ' colspan="'.count($attributes).'"';
        // assume always removeable and presence is hidden.
        $firstrow = '<td class="ui-state-default remove-row" style="width: 1%" rowspan="'.($options['occurrenceComment']?"3":"2").'" >X</td>';
        $firstrow .= str_replace('{content}', $firstCell, str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']));
        $existing_record_id = $occ['id'];
        $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
        if ($options['rowInclusionCheck']=='alwaysFixed' || $options['rowInclusionCheck']=='alwaysRemovable' ||
            (data_entry_helper::$entity_to_load!=null && array_key_exists("sc:$ttlid:$existing_record_id:present", data_entry_helper::$entity_to_load))) {
          $checked = ' checked="checked"';
        } else {
          $checked='';
        }
        $firstrow .= "<td class=\"scPresenceCell\"$hidden>".($options['rowInclusionCheck']!='hasData' ? "<input type=\"hidden\" class=\"scPresence\" name=\"sc:$ttlid:$existing_record_id:present\" value=\"0\"/><input type=\"checkbox\" class=\"scPresence\" name=\"sc:$ttlid:$existing_record_id:present\" $checked />" : '')."</td>";
        $secondrow = "";
        foreach ($occAttrControls as $attrId => $control) {
          if ($existing_record_id) {
            $search = preg_grep("/^sc:$ttlid:$existing_record_id:occAttr:$attrId".'[:[0-9]*]?$/', array_keys(data_entry_helper::$entity_to_load));
            $ctrlId = (count($search)===1) ? implode('', $search) : "sc:$ttlid:$existing_record_id:occAttr:$attrId";
          } else {
            $ctrlId = "sc:$ttlid::occAttr:$attrId";
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
            // May have a label in front of it
            if (strpos($oc, '<select') !== false) {
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
          $secondrow .= str_replace(array('{label}', '{content}'), array(lang::get($attributes[$attrId]['caption']), $oc), $indicia_templates[$options['attrCellTemplate']]);
        }
        // no confidential checkbox.
        $rows[]='<tr class="scMeaning-'.$occ['taxon']['taxon_meaning_id'].'">'.$firstrow.'</tr>';
        $rows[]='<tr class="scMeaning-'.$occ['taxon']['taxon_meaning_id'].' scDataRow">'.$secondrow.'</tr>'; // no images.
        if ($options['occurrenceComment']) {
          $rows[]='<tr class="scMeaning-'.$occ['taxon']['taxon_meaning_id'].' scDataRow">'.
"<td class=\"ui-widget-content scCommentCell\" $colspan>
  <table class=\"scCommentTable\">
    <tbody class=\"scCommentTableBody\" ><tr>
      <td class=\"scCommentLabelCell\">
        <label for=\"sc:$ttlid:$existing_record_id:occurrence:comment\" class=\"auto-width\">".lang::get("Comment").":</label>
      </td>
      <td>
        <input type=\"text\" class=\"scComment\" name=\"sc:$ttlid:$existing_record_id:occurrence:comment\" id=\"sc:$ttlid:$existing_record_id:occurrence:comment\" value=\"".htmlspecialchars(data_entry_helper::$entity_to_load["sc:$ttlid:$existing_record_id:occurrence:comment"])."\">
      </td>
    </tr></tbody>
  </table>
</td></tr>";
        }
        $rowIdx++;
      }
      $grid .= "\n<tbody>\n";
      if (count($rows)>0) $grid .= implode("\n", $rows)."\n";
      else $grid .= "<tr style=\"display: none\"><td></td></tr>\n";
      $grid .= "</tbody>\n</table>\n";
      if ($options['rowInclusionCheck']=='hasData') $grid .= '<input name="rowInclusionCheck" value="hasData" type="hidden" />';
      // If the lookupListId parameter is specified then the user is able to add extra rows to the grid,
      // selecting the species from this list. Add the required controls for this.
      if (isset($options['lookupListId'])) {
        $grid .= "<label for=\"taxonLookupControl\" class=\"auto-width\">".lang::get('Add species to list').":</label> <input id=\"taxonLookupControl\" name=\"taxonLookupControl\" >";
        // Javascript to add further rows to the grid
        data_entry_helper::$javascript .= "var formatter = function(rowData,taxonCell) {
  taxonCell.html(\"".lang::get('loading')."\");
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list/' + rowData.id +
            '?mode=json&view=detail&auth_token=".$options['readAuth']['auth_token']."&nonce=".$options['readAuth']["nonce"]."&callback=?', function(mdata) {
    if(mdata instanceof Array && mdata.length>0){
      jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list' +
            '?mode=json&view=detail&auth_token=".$options['readAuth']['auth_token']."&nonce=".$options['readAuth']["nonce"]."&taxon_meaning_id='+mdata[0].taxon_meaning_id+'&taxon_list_id=".$options["extra_list_id"]."&callback=?', function(data) {
        var taxaList = '';
         if(data instanceof Array && data.length>0){
          for (var i=0;i<data.length;i++){
            if(data[i].preferred == 'f')
              taxaList += (taxaList == '' ? '' : ', ')+data[i].taxon;
            else
              taxaList = '<em>'+data[i].taxon+'</em>'+(taxaList == '' ? '' : ', '+taxaList);
            }
          }
          taxonCell.html(taxaList).removeClass('extraCommonNames');
        });
    }})
}  
bindSpeciesAutocomplete(\"taxonLookupControl\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$options['id']."\", \"".$options['lookupListId']."\", {\"auth_token\" : \"".
            $options['readAuth']['auth_token']."\", \"nonce\" : \"".$options['readAuth']['nonce']."\"}, formatter, \"".lang::get('LANG_Duplicate_Taxon')."\", ".$options['max_species_ids'].");
";
      }
      // No help text
      return $grid;
    } else {
      return $taxalist['error'];
    }
  }

  public static function species_checklist_prepare_attributes($options, $attributes, &$occAttrControls, &$occAttrs) {
    $idx=0;
    if (array_key_exists('occAttrs', $options))
      $attrs = $options['occAttrs'];
    else
      // There is no specified list of occurrence attributes, so use all available for the survey
      $attrs = array_keys($attributes);
    foreach ($attrs as $occAttrId) {
      // test that this occurrence attribute is linked to the survey
      if (!isset($attributes[$occAttrId]))
        throw new Exception("The occurrence attribute $occAttrId requested for the grid is not linked with the survey.");
      $attrDef = array_merge($attributes[$occAttrId]);
      $occAttrs[$occAttrId] = $attrDef['caption'];
      // Get the control class if available. If the class array is too short, the last entry gets reused for all remaining.
      $ctrlOptions = array(
        'class'=>self::species_checklist_occ_attr_class($options, $idx, $attrDef['untranslatedCaption']) .
            (isset($attrDef['class']) ? ' '.$attrDef['class'] : ''),
        'extraParams' => $options['readAuth'],
        'suffixTemplate' => 'nosuffix',
        'language' => $options['language'] // required for lists eg radio boxes: kept separate from options extra params as that is used to indicate filtering of species list by language
      );
      if(isset($options['lookUpKey'])) $ctrlOptions['lookUpKey']=$options['lookUpKey'];
      if(isset($options['blankText'])) $ctrlOptions['blankText']=$options['blankText'];
      $attrDef['fieldname'] = '{fieldname}';
      $attrDef['id'] = '{fieldname}';
      $occAttrControls[$occAttrId] = data_entry_helper::outputAttribute($attrDef, $ctrlOptions);
      $idx++;
    }
  }
  
  /**
   * When the species checklist grid has a lookup list associated with it, this is a
   * secondary checklist which you can pick species from to add to the grid. As this happens,
   * a hidden table is used to store a clonable row which provides the template for new rows
   * to be added to the grid.
   */
  private static function get_species_checklist_clonable_row($options, $occAttrControls, $attributes) {
    global $indicia_templates;
    // assume always removeable and presence is hidden.
//    $r = '<table border=3 id="'.$options['id'].'-scClonable">';
    $r = '<table style="display: none" id="'.$options['id'].'-scClonable">';
    $r .= '<tbody><tr class="scClonableRow" id="'.$options['id'].'-scClonableRow1"><td class="ui-state-default remove-row" style="width: 1%" rowspan="'.($options['occurrenceComment']?"3":"2").'">X</td>';
    $colspan = ' colspan="'.count($attributes).'"';
    $r .= str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']).
        '<td class="scPresenceCell" style="display:none"><input type="checkbox" class="scPresence" name="" value="" /></td>'.
        '</tr><tr class="scClonableRow scDataRow" id="'.$options['id'].'-scClonableRow2">';
    $idx = 0;
    foreach ($occAttrControls as $attrId=>$oc) {
      $class = self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['caption']);
      if (isset($attributes[$attrId]['default']) && !empty($attributes[$attrId]['default'])) {
        $existing_value=$attributes[$attrId]['default'];
        // For select controls, specify which option is selected from the existing value
        if (substr($oc, 0, 7)=='<select') {
          $oc = str_replace('value="'.$existing_value.'"',
              'value="'.$existing_value.'" selected="selected"', $oc);
        } else {
          $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
        }
      }
      $r .= str_replace(array('{content}', '{class}'),
          array(str_replace('{fieldname}', "sc:-ttlId-::occAttr:$attrId", $oc), $class.'Cell'),
          $indicia_templates['attribute_cell']
      );
      $idx++;
    }
    if ($options['occurrenceComment']) {
      $r .= "</tr><tr class=\"scClonableRow scDataRow\" id=\"".$options['id']."-scClonableRow3\">
<td class=\"ui-widget-content scCommentCell\" ".$colspan.">
  <table class=\"scCommentTable\">
    <tbody class=\"scCommentTableBody\" ><tr>
      <td class=\"scCommentLabelCell\">
        <label for=\"sc:-ttlId-::occurrence:comment\" class=\"auto-width\">".lang::get("Comment").":</label>
      </td>
      <td>
        <input type=\"text\" class=\"scComment\" name=\"sc:-ttlId-::occurrence:comment\" id=\"sc:-ttlId-::occurrence:comment\" value=\"\">
      </td>
    </tr></tbody>
  </table>
</td>";
    }
    $r .= "</tr></tbody></table>\n";
    return $r;
  }
  /**
   * Returns the class to apply to a control for an occurrence attribute, identified by an index.
   * @access private
   */
  private static function species_checklist_occ_attr_class($options, $idx, $caption) {
    return (array_key_exists('occAttrClasses', $options) && $idx<count($options['occAttrClasses'])) ?
          $options['occAttrClasses'][$idx] :
          'sc'.str_replace(' ', '', ucWords($caption)); // provide a default class based on the control caption
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
                  array(array('caption' => lang::get('Delete'), 'javascript'=>'deleteSurvey({sample_id})'))));
  }
  
} 