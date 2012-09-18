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
 * Need to set attributeValidation required for locAttrs for site type, site follow up, and smpAttrs Visit, human freq, microclimate (including min, max) 
 * Need to manually set the term list sort order on non-default language tems.
 * Need to set the control of Visit to a select, and for the cavity entrance to a checkbox group.
 */
require_once('mnhnl_bats.php');
class iform_mnhnl_bats2 extends iform_mnhnl_bats {
  
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_bats2_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Summer Bats form. Inherits from Summer Bats, Dynamic 1.'
    );
  }

  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Bats2';  
  }

  public static function get_parameters() {
    $retVal=array();
    $parentVal = parent::get_parameters();
  	
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
              "[location attributes]\r\n".
              "@lookUpListCtrl=radio_group\r\n".
              "@lookUpKey=meaning_id\r\n".
              "@sep= \r\n".
              "@tabNameFilter=SiteExtras\r\n".
              "[location spatial reference]\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "@layers=[\"SiteAreaLayer\",\"SitePathLayer\",\"SitePointLayer\",\"SiteLabelLayer\"]\r\n".
              "@scroll_wheel_zoom=false\r\n".
              "@searchUpdatesSref=true\r\n".
              "@maxZoom=13\r\n".
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
        $param['default'] = 'reports_for_prebuilt_forms/MNHNL/mnhnl_bats2_grid';
        
      if($param['name'] != 'species_include_taxon_group' &&
          $param['name'] != 'link_species_popups' &&
          $param['name'] != 'species_include_both_names' &&
          $param['name'] != 'includeLocTools' &&
          $param['name'] != 'loctoolsLocTypeID' &&
          $param['name'] != 'siteTypeOtherTermID' &&
          $param['name'] != 'siteTypeOtherAttrID' &&
          $param['name'] != 'entranceDefectiveTermID' &&
          $param['name'] != 'entranceDefectiveCommentAttrID' &&
          $param['name'] != 'disturbanceOtherTermID' &&
          $param['name'] != 'disturbanceCommentAttrID' &&
          $param['name'] != 'removeBreakIDs' &&
          $param['name'] != 'attributeValidation' &&
          $param['name'] != 'locationWMSLayerLookup')
        $retVal[] = $param;
    }
    return $retVal;
  }

  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    $isAdmin = user_access('IForm n'.self::$node->nid.' admin');
  	if(!$isAdmin) return('');
    if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'), '#locations' => lang::get('LANG_Locations'));
    $LocationTypeID = iform_mnhnl_getTermID(self::$auth, $args['locationTypeTermListExtKey'],$args['LocationTypeTerm']);
    
    $r = '<div id="downloads" >
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_bats2_sites_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=bat2sitesreport">
      <p>'.lang::get('LANG_Sites_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"website_id":'.$args['website_id'].', "survey_id":'.$args['survey_id'].', "location_type_id":'.$LocationTypeID.'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_bats_conditions_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=bat2conditionsreport">
      <p>'.lang::get('LANG_Conditions_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class=\"ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
        <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_bats_species_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=bat2speciesreport">
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
$.validator.addMethod('observation_type', function(value, element){
	return jQuery(element).closest('table').find('input').not('[value=]').length > 0 ||
	       jQuery(element).closest('table').parent().next().find(':checkbox').filter('[checked]').length > 0;
},
  \"".lang::get('validation_observation_type')."\");\n";
    $siteTypeOtherAttrID=iform_mnhnl_getAttrID($auth, $args, 'location', 'site type other');
    if (!$siteTypeOtherAttrID) return lang::get('This form must be used with a survey that has the site type other attribute associated with it.');
    $siteType2AttrID=iform_mnhnl_getAttrID($auth, $args, 'location', 'Site type2');
    if (!$siteType2AttrID) return lang::get('This form must be used with a survey that has the Site type2 attribute associated with it.');
    $siteType2TermList = helper_base::get_termlist_terms($auth, 'bats2:sitetype', array('Other'));
    data_entry_helper::$javascript .= "
var myTerms = jQuery('[name=locAttr\\:".$siteType2AttrID."],[name^=locAttr\\:".$siteType2AttrID."\\:]');
myTerms.change(function(){
    // for a radio button the change is fired on the newly checked button
    if(this.value==".$siteType2TermList[0]['meaning_id'].")
      jQuery('[name=locAttr\\:".$siteTypeOtherAttrID."],[name^=locAttr\\:".$siteTypeOtherAttrID."\\:]').addClass('required').removeAttr('readonly');
    else
      jQuery('[name=locAttr\\:".$siteTypeOtherAttrID."],[name^=locAttr\\:".$siteTypeOtherAttrID."\\:]').removeClass('required').val('').attr('readonly',true);
  });
var other = jQuery('[name=locAttr\\:".$siteTypeOtherAttrID."],[name^=locAttr\\:".$siteTypeOtherAttrID."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.removeClass('wide').remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name^=locAttr]').filter(':radio').filter('[value=".$siteType2TermList[0]['meaning_id']."]').parent().append(other);
if(myTerms.filter('[checked]').filter('[value=".$siteType2TermList[0]['meaning_id']."]').length)
  jQuery('[name=locAttr\\:".$siteTypeOtherAttrID."],[name^=locAttr\\:".$siteTypeOtherAttrID."\\:]').addClass('required').removeAttr('readonly');
else
  jQuery('[name=locAttr\\:".$siteTypeOtherAttrID."],[name^=locAttr\\:".$siteTypeOtherAttrID."\\:]').removeClass('required').val('').attr('readonly',true);
";
    if (array_key_exists('sample:id', data_entry_helper::$entity_to_load))
      data_entry_helper::$javascript .= "jQuery('[name=locAttr\\:".$siteType2AttrID."],[name^=locAttr\\:".$siteType2AttrID."\\:]').filter('[checked]').change();\n";
    else
      data_entry_helper::$javascript .= "jQuery('[name=locAttr\\:".$siteTypeOtherAttrID."],[name^=locAttr\\:".$siteTypeOtherAttrID."\\:]').removeClass('required').val('').attr('readonly',true);\n";

    $disturbOtherAttrID=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Disturbances other comment');
    if (!$disturbOtherAttrID) return lang::get('This form must be used with a survey that has the Disturbances other comment attribute associated with it.');
    $disturb2AttrID=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Disturbances2');
    if (!$disturb2AttrID) return lang::get('This form must be used with a survey that has the Disturbances2 attribute associated with it.');
    $disturb2OtherTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Other'));
    $disturb2PlannedTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Planned renovations'));
    $disturb2InProgTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Renovations in progress'));
    $disturb2RecentTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Renovations recently completed'));
    data_entry_helper::$javascript .= "
jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2PlannedTerm[0]['meaning_id']."]').change(function(){
  if(this.checked)
    jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2InProgTerm[0]['meaning_id']."],[value=".$disturb2RecentTerm[0]['meaning_id']."]').removeAttr('checked');
});
jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2InProgTerm[0]['meaning_id']."]').change(function(){
  if(this.checked)
    jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2PlannedTerm[0]['meaning_id']."],[value=".$disturb2RecentTerm[0]['meaning_id']."]').removeAttr('checked');
});
jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2RecentTerm[0]['meaning_id']."]').change(function(){
  if(this.checked)
    jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2InProgTerm[0]['meaning_id']."],[value=".$disturb2PlannedTerm[0]['meaning_id']."]').removeAttr('checked');
});
var myTerm = jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2OtherTerm[0]['meaning_id']."]');
myTerm.change(function(){
    if(this.checked)
      jQuery('[name=smpAttr\\:".$disturbOtherAttrID."],[name^=smpAttr\\:".$disturbOtherAttrID."\\:]').addClass('required').removeAttr('readonly');
    else
      jQuery('[name=smpAttr\\:".$disturbOtherAttrID."],[name^=smpAttr\\:".$disturbOtherAttrID."\\:]').removeClass('required').val('').attr('readonly',true);
  });
var other = jQuery('[name=smpAttr\\:".$disturbOtherAttrID."],[name^=smpAttr\\:".$disturbOtherAttrID."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.removeClass('wide').remove(); // remove Other field, then bolt in after the other radio button.
myTerm.parent().append(other);
myTerm.change();
";
    if (array_key_exists('sample:id', data_entry_helper::$entity_to_load))
      data_entry_helper::$javascript .= "jQuery('[name=locAttr\\:".$siteType2AttrID."],[name^=locAttr\\:".$siteType2AttrID."\\:]').filter('[checked]').change();\n";
    else
      data_entry_helper::$javascript .= "jQuery('[name=locAttr\\:".$siteTypeOtherAttrID."],[name^=locAttr\\:".$siteTypeOtherAttrID."\\:]').removeClass('required').val('').attr('readonly',true);\n";
    $liveCountNotDoneTerm = helper_base::get_termlist_terms($auth, 'bats2:livecounttype', array('Not done'));
    data_entry_helper::$javascript .= "
jQuery('.scPresence-absenceRecording').live('change',function(){
  // we don't want the present attribute there if this is unchecked. Have to take into account
  // existing data, so can't just set to disabled, as this would leave any existing data as
  // unchanged.
  var myRow=jQuery(this).closest('tr');
  var presentName = myRow.find('.scPresent').attr('name');
  if(this.checked){
    var next=myRow.next();
    next.find('.scLiveCount').find('[value=".$liveCountNotDoneTerm[0]['meaning_id']."]').attr('checked','checked').change();
    next.next().find('.scDeadCountCell').find(':checkbox').removeAttr('checked').change();
    myRow.find('.scPresent').removeAttr('disabled');
    jQuery('[name='+presentName+']').filter('[value=]').val(0);
  } else {
    myRow.find('.scPresent').removeAttr('checked').attr('disabled','disabled');
    jQuery('[name='+presentName+']').filter('[value=0]').val('');
  }
});
// TBD: set default value for this; then setup 
jQuery('.scLiveCount :radio').live('change',function(){
  // has been checked
  if(this.value == ".$liveCountNotDoneTerm[0]['meaning_id']."){
    jQuery(this).closest('tr').find('.scNumAlive').val('').attr('readonly','readonly').removeClass('required ui-state-error');
    jQuery(this).closest('tr').find('.deh-required,.inline-error').remove();
  } else {
    var prev=jQuery(this).closest('tr').prev();
    prev.find('.scPresence-absenceRecording').removeAttr('checked').change();
    if(jQuery(this).closest('tr').find('.required').length==0)//previously ticked, so value input is setup
      jQuery(this).closest('tr').find('.scNumAlive').removeAttr('readonly').addClass('required').after('<span class=\"deh-required\">*</span>');
  }
  // next row - Dead Count - is left alone.
});
jQuery('.scDeadCount').live('change',function(){
  if(this.checked){
    // prev row - Live Count - is left alone.
    var prev=jQuery(this).closest('tr').prev().prev();
    prev.find('.scPresence-absenceRecording').removeAttr('checked').change();
    jQuery(this).closest('tr').find('.scNumDead').removeAttr('readonly').addClass('required').after('<span class=\"deh-required\">*</span>');
  }else{
    jQuery(this).closest('tr').find('.scNumDead').val('').attr('readonly','readonly').removeClass('required ui-state-error')
    jQuery(this).closest('tr').find('.deh-required,.inline-error').remove();
  }
});
jQuery('.scPresence-absenceRecording,.scDeadCount').change();
jQuery('.scLiveCount :radio').filter(':checked').change();
  ";
    $visitAttrID=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Bat visit');
    if ($visitAttrID) {
      data_entry_helper::$late_javascript .= "
jQuery('#smpAttr\\\\:$visitAttrID').after(\"<span class='extra-text'>".lang::get('LANG_Site_Extra')."</span>\");";
    }
    return '';

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

          } else if(substr($rule[0], 3, 4)!= 'Attr'){ // have to add for non attribute case.
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').addClass('".$rule[$i]."');";
          }
    }
      
    return '';
  }
  
  /**
   * Get the location module control
   */
  protected static function get_control_locationmodule($auth, $args, $tabalias, $options) {
    return iform_mnhnl_lux5kgridControl($auth, $args, self::$node, array_merge(
      array('initLoadArgs' => '{initial: true}',
       'canCreate'=>true
       ), $options));
  }

  /**
   * Get the custom species control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    data_entry_helper::$javascript .= "
hook_species_checklist_pre_delete_row=function(e) {
    return confirm(\"".lang::get('Are you sure you want to delete this row?')."\");
};
";
  	$extraParams = $auth['read'];
    if ($args['species_names_filter']=='preferred') {
      $extraParams += array('preferred' => 't');
    }
    if ($args['species_names_filter']=='language') {
      $extraParams += array('language' => iform_lang_iso_639_2($user->lang));
    }  
    // multiple species being input via a grid      
    $species_ctrl_opts=array_merge(array(
          "extra_list_id"=>$args["extra_list_id"],
          'listId'=>$args['list_id'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,
          'readAuth'=>$auth['read'],
          'extraParams'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'occurrenceComment'=>$args['occurrence_comment'],
          'PHPtaxonLabel' => true,
          'language' => iform_lang_iso_639_2($user->lang) // used for termlists in attributes
    ), $options);
    if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
    call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args);
    // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
    // then output the grid control
    return self::mnhnl_bats2_species_checklist($args, $species_ctrl_opts);
  }
  

  public static function mnhnl_bats2_species_checklist($args, $options)
  {
  	global $indicia_templates;
//    $options = data_entry_helper::check_arguments($options, array('listId', 'occAttrs', 'readAuth', 'extraParams', 'lookupListId'));
    $options = data_entry_helper::get_species_checklist_options($options);
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    $occAttrControls = array();
    $occAttrs = array();
    $retVal='';
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
      $retVal = "<p>".lang::get('LANG_SpeciesInstructions')."</p>\n";
      if (isset($options['lookupListId'])) {
        $retVal .= self::get_species_checklist_clonable_row($args, $options, $occAttrControls, $attributes);
      }
      $retVal .= '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $retVal .= self::get_species_checklist_header($options, $attributes).'<tbody>';
      $attrsPerRow=array();
      foreach ($attributes as $attrId=>$attr) {
        $row=substr ( $attr['inner_structure_block'], 3);
        if(!isset($attrsPerRow[$row])) {
          $attrsPerRow[$row]=array();
        }
        $attrsPerRow[$row][] = $attr["attributeId"];
      }
      $maxCount=0;
      foreach ($attrsPerRow as $row) {
        if(count($row) > $maxCount) $maxCount=count($row);
      }
      $rows = array();
      $rowIdx = 0;
      foreach ($occList as $occ) {
        $ttlid = $occ['taxon']['id'];
        $existing_record_id = $occ['id'];
        foreach ($attrsPerRow as $id=>$row) {
          $retVal .= '<tr class="scMeaning-'.$occ['taxon']['taxon_meaning_id'].' scDataRow">';
          if($id=='1'){
            $firstCell = data_entry_helper::mergeParamsIntoTemplate($occ['taxon'], 'taxon_label');
            if ($options['PHPtaxonLabel']) $firstCell=eval($firstCell);
            // assume always removeable and scPresence is hidden.
            $retVal .= '<td class="ui-state-default remove-row" style="width: 1%" rowspan="'.(count($attrsPerRow) + ($options['occurrenceComment'] ? 1 : 0)).'">X</td>';
            $retVal .= str_replace('{content}', $firstCell, str_replace('{colspan}', 'rowspan="'.(count($attrsPerRow) + ($options['occurrenceComment'] ? 1 : 0)).'"', $indicia_templates['taxon_label_cell']));
            $ctrlId = "sc:$ttlid:$existing_record_id:present";
            $retVal .= '<td class="scPresenceCell" style="display:none"><input type="checkbox" class="scPresence" checked="checked" name="'.$ctrlId.'" value="" /></td>';
          }
          foreach ($row as $attrId) {
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
            } else $existing_value = '';
            $control = $occAttrControls[$attrId];
            $oc = str_replace('{fieldname}', $ctrlId, $control);
            if (!empty($existing_value)) { // TBD selects
              if(strpos($oc, 'checkbox') !== false) {
                if($existing_value=="1")
                  $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
              } else {
                $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
              }
            // assume all error handling/validation done client side
            }
            $class = self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['untranslatedCaption']);
            $retVal .= str_replace(array('{content}', '{class}'),
              array(str_replace('{fieldname}', "sc:-ttlId-::occAttr:$attrId", $oc), $class.'Cell'),
              $indicia_templates['attribute_cell']
            );
          }
          $idx++;
          $retVal .= '</tr>';
        }
        if ($options['occurrenceComment']) {
          $commentID = "sc:".$ttlid.":".($existing_record_id?$existing_record_id:"x".$rowIdx).":occurrence:comment";
          $retVal .= '<tr class="scMeaning-'.$occ['taxon']['taxon_meaning_id'].' scDataRow">'.
                '<td class="ui-widget-content scCommentCell" colspan="'.$maxCount.'">'.
                '<label for="'.$commentID.'" class="auto-width" >'.lang::get("Comment").' : </label>'.
                '<input class="scComment" type="text" id="'.$commentID.'" name="'.$commentID.'" value="'.data_entry_helper::$entity_to_load[$commentID].'" />'.
                "</td></tr>\n";
        }
        $rowIdx++;
      }
      if ($rowIdx==0) $retVal .= "<tr style=\"display: none\"><td></td></tr>\n";
      $retVal .= "</tbody></table>\n";
      // no confidential checkbox.
      // resize has to be done after tabs set up
      data_entry_helper::$javascript .= "
setCommentWidth = function(){
  jQuery('.scComment').width('10px');
  jQuery('.species-grid').find('.scCommentCell').each(function(index){
    if(index==0){
      myWidth=jQuery(this).width();
      labelWidth=jQuery(this).find('label').width();
    }
    jQuery(this).find('input').width(myWidth-labelWidth-1-6);
  });
}
speciesTabHandler = function(e, ui){
  if (ui.panel.id=='species') {
    setCommentWidth();
    jQuery(jQuery('#species').parent()).unbind('tabsshow', speciesTabHandler);
  }
}
jQuery(jQuery('#species').parent()).bind('tabsshow', speciesTabHandler);
";
      if ($options['rowInclusionCheck']=='hasData') $r .= '<input name="rowInclusionCheck" value="hasData" type="hidden" />';
      // If the lookupListId parameter is specified then the user is able to add extra rows to the grid,
      // selecting the species from this list. Add the required controls for this.
      if (isset($options['lookupListId'])) {
        $retVal .= "<label for=\"taxonLookupControl\" class=\"auto-width\">".lang::get('Add species to list')." : </label><input id=\"taxonLookupControl\" name=\"taxonLookupControl\" >";
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
          setCommentWidth();
        });
    }})
}  
bindSpeciesAutocomplete(\"taxonLookupControl\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$options['id']."\", \"".$options['lookupListId']."\", {\"auth_token\" : \"".
            $options['readAuth']['auth_token']."\", \"nonce\" : \"".$options['readAuth']['nonce']."\"}, formatter, \"".lang::get('LANG_Duplicate_Taxon')."\");
";
      }
      // No help text
      return $retVal;
    } else {
      return $taxalist['error'];
    }
  }

  /**
   * When the species checklist grid has a lookup list associated with it, this is a
   * secondary checklist which you can pick species from to add to the grid. As this happens,
   * a hidden table is used to store a clonable row which provides the template for new rows
   * to be added to the grid.
   */
  private static function get_species_checklist_clonable_row($args, $options, $occAttrControls, $attributes) {
    global $indicia_templates;
    // assume always removeable and presence is hidden.
    // first row has X to remove row, plus species
    $liveTypeAttrID=iform_mnhnl_getAttrID(self::$auth, $args, 'occurrence', 'Live Count');
    if (!$liveTypeAttrID) return lang::get('This form must be used with a survey that has the Live Count attribute associated with it.');
    $notDoneTerm = helper_base::get_termlist_terms(self::$auth, 'bats2:livecounttype', array('Not done'));
    
    $rows=array();
    foreach ($attributes as $attrId=>$attr) {
    	$row=substr ( $attr['inner_structure_block'], 3);
    	if(!isset($rows[$row])) {
    		$rows[$row]=array();
    	}
    	$rows[$row][] = $attr["attributeId"];
    }
    $maxCount=0;
    foreach ($rows as $row) {
      if(count($row) > $maxCount) $maxCount=count($row);
    }
    $idx=0;
//    $r = '<table border=3 id="'.$options['id'].'-scClonable"><tbody>';
    $r = '<table style="display: none" id="'.$options['id'].'-scClonable"><tbody>';
    foreach ($rows as $id=>$row) {
      $r .= '<tr class="scClonableRow" id="'.$options['id'].'-scClonableRow'.$id.'">';
      if($id=='1'){
        $r .= '<td class="ui-state-default remove-row" style="width: 1%" rowspan="'.(count($rows) + ($options['occurrenceComment'] ? 1 : 0)).'">X</td>';
        $r .= str_replace('{colspan}', 'rowspan="'.(count($rows) + ($options['occurrenceComment'] ? 1 : 0)).'"', $indicia_templates['taxon_label_cell']);
        // assume always removeable: scPresence is hidden.
        $r .= '<td class="scPresenceCell" style="display:none"><input type="checkbox" class="scPresence" name="sc:-ttlId-::present" value="" /></td>';
      }
      foreach ($row as $attrId) {
        $class = self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['untranslatedCaption']);
        $oc = $occAttrControls[$attrId];
        // as we are using meaning_ids, we can't use standard default value method.
        if($liveTypeAttrID==$attrId){
          $attributes[$attrId]['default']=$notDoneTerm[0]['meaning_id'];
        }
        if (isset($attributes[$attrId]['default']) && !empty($attributes[$attrId]['default'])) {
          $existing_value=$attributes[$attrId]['default'];
          // For select controls, specify which option is selected from the existing value
          if (substr($oc, 0, 7)=='<select') {
            $oc = str_replace('value="'.$existing_value.'"', 'value="'.$existing_value.'" selected="selected"', $oc);
          } else if(strpos($oc, 'radio') !== false) {
              $oc = str_replace('value="'.$existing_value.'"','value="'.$existing_value.'" checked="checked"', $oc);
          } else if(strpos($oc, 'checkbox') !== false) {
            if($existing_value=="1")
              $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
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
      $r .= '</tr>';
    }
    if ($options['occurrenceComment']) {
      $r .= '<tr class="scClonableRow" id="'.$options['id'].'-scClonableRowComment"><td class="ui-widget-content scCommentCell" colspan="'.$maxCount.'"><label for="sc:-ttlId-::occurrence:comment" class="auto-width" >'.lang::get("Comment").' : </label><input class="scComment" type="text" id="sc:-ttlId-::occurrence:comment" name="sc:-ttlId-::occurrence:comment" value="" /></td></tr>';
    }
    $r .= '</tbody></table>';
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
  
  /**
   * Retrieve the grid header row for the species checklist grid control.
   * @param array $options Control options array.
   * @param array $occAttrs Array of custom attributes included in the grid.
   * @return string Html for the <thead> element.
   */
  private static function get_species_checklist_header($options, $attributes) {
    $r = '';
    $visibleColIdx = 0;
    if ($options['header']) {
      $idx = 0;
      $rows=array();
      foreach ($attributes as $attrId=>$attr) {
    	$row=substr ( $attr['inner_structure_block'], 3);
    	if(!isset($rows[$row])) {
    		$rows[$row]=array();
    		$trs[$row]="";
    	}
    	$rows[$row][] = $attr["attributeId"];
      }
      $maxCount=0;
      foreach ($rows as $row) {
      	if(count($row) > $maxCount) $maxCount=count($row);
      }
      $r .= "<thead class=\"ui-widget-header\"><tr>";
      // assume always removeable
      $r .= '<th></th><th>'.lang::get('species_checklist.species').'</th>';
      for ($i=0; $i<$maxCount; $i++){
        $r .= '<th>'.lang::get('species_checklist.column'.($i+1)).'</th>';
      }
      $r .= '</tr></thead>';
    }
    return $r;
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
    if (isset($values['sample:location_id']) && $values['sample:location_id']=='') unset($values['sample:location_id']);
    if (isset($values['sample:recorder_names'])){
      if(is_array($values['sample:recorder_names'])){
        $values['sample:recorder_names'] = implode("\r\n", $values['sample:recorder_names']);
      }
    } // else just load the string
    if (isset($values['location:name'])) $values['sample:location_name'] = $values['location:name'];
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    if(!isset($values['sample:deleted'])) {
      $occurrences = self::wrap_species_checklist($values);
      if(count($occurrences)>0) 
        $sampleMod['subModels'] = $occurrences;
      if (isset($values['location:location_type_id'])){
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
  
  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_bats2.css');
  }
  
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected function getArgDefaults(&$args) {
    $args['includeLocTools'] == false; 
  }
} 