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
 * TODO documentation
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
      'description'=>'MNHNL Summer Bats form. Inherits from Winter Bats (and hence Dynamic 1).'
    );
  }

  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Summer Bats (2)';  
  }

  public static function get_parameters() {
    $retVal=array();
    $parentVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name' => 'addBreaks',
          'caption' => 'Add Breaks',
          'description' => 'Add line breaks before these items',
          'type' => 'string',
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name' => 'language',
          'caption' => 'Language Override',
          'description' => '',
          'type' => 'string',
          'required' => true,
          'default' => 'en',
          'group' => 'User Interface'
        )));
    
    foreach($parentVal as $param){
      if($param['name'] == 'structure'){
        $param['default'] =
             "=Site=\r\n".
              "[custom JS]\r\n".
              "[location module]\r\n".
              "[location attributes]\r\n".
              "@tabNameFilter=Site\r\n".
              "@class=wide\r\n".
              "@numValues=10000\r\n".
              "@lookUpKey=meaning_id\r\n".
              "[location attributes]\r\n".
              "@lookUpKey=meaning_id\r\n".
              "@sep=&#32;\r\n".
              "@tabNameFilter=SiteExtras\r\n".
              "[location spatial reference]\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "@layers=[\"SiteAreaLayer\",\"SitePathLayer\",\"SitePointLayer\",\"SiteLabelLayer\"]\r\n".
              "@editLayer=false\r\n".
              "@searchLayer=true\r\n".
              "@searchUpdatesSref=false\r\n".
              "[point grid]\r\n".
              "@srefs=2169,LUREF (m),X,Y,;4326,Lat/Long Deg,Lat,Long,D;4326,Lat/Long Deg:Min,Lat,Long,DM;4326,Lat/Long Deg:Min:Sec,Lat,Long,DMS\r\n".
              "[location comment]\r\n".
              "[*]\r\n".
             "=Conditions=\r\n".
              "[date]\r\n".
              "[recorder names]\r\n".
              "[survey method grid]\r\n".
              "@surveyMethodTermList=bats2:surveymethod\r\n".
              "@tableHeadings=Survey method,Start time,End time,Visit\r\n".
              "@defaultAttrs=<TBD>\r\n".
              "@removeOptions=<TBD>\r\n".
              "@removeAttr=<TBD>\r\n".
              "?The Visit is of the format 'Visit number' of 'Number of visits during the season'. Please enter times in 24 Hour clock format, omitting any AM or PM: for example for 4:15PM, please enter 16:15. When entering times before 10:00 in the morning, you must include a leading zero: e.g. 09:00.?\r\n".
              "[*]\r\n".
              "@sep=&#32;\r\n".
              "@lookUpKey=meaning_id\r\n".
              "[sample comment]\r\n".
             "=Species=\r\n".
              "[species]\r\n".
              "@surveyMethodTermList=bats2:surveymethod\r\n".
              "@view=detail\r\n".
              "@rowInclusionCheck=alwaysRemovable\r\n".
              "@sep=&#32;\r\n".
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
          $param['name'] != 'entranceDefectiveTermID' &&
          $param['name'] != 'entranceDefectiveCommentAttrID' &&
          $param['name'] != 'disturbanceOtherTermID' &&
          $param['name'] != 'disturbanceCommentAttrID' &&
          $param['name'] != 'removeBreakIDs' &&
          $param['name'] != 'locationWMSLayerLookup')
        $retVal[] = $param;
    }
    return $retVal;
  }

  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    return call_user_func(array(get_called_class(), 'getExtraGridModeTabsSub'),
      $retTabs, $readAuth, $args, $attributes,
        'mnhnl_bats_sites_download_report.xml',
        'mnhnl_bats2_conditions_download_report.xml',
        'mnhnl_bats2_species_download_report.xml');
  }
  
  // getSampleListGrid is now identical to main bats form

  // because of validation issues (ie the validation is trying to validate the hidden cloneable table)
  // we put the cloneable table outside the form.
  private static $cloneableTable = "";
  
  protected static function getTrailerHTML($args) {
    $r = self::$cloneableTable;
    $r .= (isset($args['headerAndFooter']) && $args['headerAndFooter'] ?
      '<p id="iform-trailer">'.lang::get('LANG_Trailer_Text').'</p>' : '');
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
    iform_mnhnl_addCancelButton($args['interface']);
    
    $r .= self::getSiteTypeJS(parent::$auth, $args);
    data_entry_helper::$javascript .= "
if($.browser.msie && $.browser.version < 9)
  $('input[type=radio],[type=checkbox]').live('click', function(){
    this.blur();
    this.focus();
});\n";
    // Move the date after the Institution
    $institutionAttrID=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Institution');
    if($institutionAttrID) {
      data_entry_helper::$javascript .= "
var institutionAttr = jQuery('[name=smpAttr\\:".$institutionAttrID."],[name^=smpAttr\\:".$institutionAttrID."\\:],[name^=smpAttr\\:".$institutionAttrID."\\[\\]]').not(':hidden').eq(0).closest('.control-box').next();
var recorderField = jQuery('#sample\\\\:recorder_names');
var recorderLabel = recorderField.prev().filter('label');
var recorderRequired = recorderField.next();
recorderRequired.next().filter('br').remove();
var recorderText = recorderRequired.next();
recorderText.next().filter('br').remove();
recorderText.next().filter('br').remove();
institutionAttr.after('<br/>');
institutionAttr.after('<br/>');
institutionAttr.after(recorderText);
institutionAttr.after('<br/>');
institutionAttr.after(recorderRequired);
institutionAttr.after(recorderField);
institutionAttr.after(recorderLabel);
var dateField = jQuery('#sample\\\\:date');
var dateLabel = dateField.prev().filter('label');
var dateRequired = dateField.next();
dateRequired.next().filter('br').remove();
institutionAttr.after('<br/>');
institutionAttr.after(dateRequired);
institutionAttr.after(dateField);
institutionAttr.after(dateLabel);
";
    }
    
    // Break up the Disturbances: makes assumptions on format, and assumes that we are doing a checkbox list
    if (!empty($args['addBreaks'])) {
      $addBreakSpecs = explode(';', $args['addBreaks']);
      foreach($addBreakSpecs as $addBreakSpec){
        $addBreakDetail = explode(',', $addBreakSpec);
        $addBreakDetail[0] = str_replace(':', '\\:', $addBreakDetail[0]);
        data_entry_helper::$javascript .= "jQuery('[name^=".$addBreakDetail[0]."\\:],[name^=".$addBreakDetail[0]."\\[\\]]').filter('[value=".$addBreakDetail[1]."],[value^=".$addBreakDetail[1]."\\:]').parent().before('<br/>');\n";
      }
    }
      
    $disturbOtherAttrID=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Disturbances other comment');
    if (!$disturbOtherAttrID) return lang::get('This form must be used with a survey that has the Disturbances other comment attribute associated with it.');
    $disturb2AttrID=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Disturbances2');
    if (!$disturb2AttrID) return lang::get('This form must be used with a survey that has the Disturbances2 attribute associated with it.');
    $disturb2OtherTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Other'));
    $disturb2PlannedTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Planned renovations'));
    $disturb2InProgTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Renovations in progress'));
    $disturb2RecentTerm = helper_base::get_termlist_terms($auth, 'bats2:disturbances', array('Renovations recently completed'));
    data_entry_helper::$javascript .= "
jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2PlannedTerm[0]['meaning_id']."],[value^=".$disturb2PlannedTerm[0]['meaning_id']."\\:]').change(function(){
  if(this.checked)
    jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2InProgTerm[0]['meaning_id']."],[value^=".$disturb2InProgTerm[0]['meaning_id']."\\:],[value=".$disturb2RecentTerm[0]['meaning_id']."],[value^=".$disturb2RecentTerm[0]['meaning_id']."\\:]').removeAttr('checked');
});
jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2InProgTerm[0]['meaning_id']."],[value^=".$disturb2InProgTerm[0]['meaning_id']."\\:]').change(function(){
  if(this.checked)
    jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2PlannedTerm[0]['meaning_id']."],[value^=".$disturb2PlannedTerm[0]['meaning_id']."\\:],[value=".$disturb2RecentTerm[0]['meaning_id']."],[value^=".$disturb2RecentTerm[0]['meaning_id']."\\:]').removeAttr('checked');
});
jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2RecentTerm[0]['meaning_id']."],[value^=".$disturb2RecentTerm[0]['meaning_id']."\\:]').change(function(){
  if(this.checked)
    jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2InProgTerm[0]['meaning_id']."],[value^=".$disturb2InProgTerm[0]['meaning_id']."\\:],[value=".$disturb2PlannedTerm[0]['meaning_id']."],[value^=".$disturb2PlannedTerm[0]['meaning_id']."\\:]').removeAttr('checked');
});
var myTerm = jQuery('[name=smpAttr\\:".$disturb2AttrID."\\[\\]],[name^=smpAttr\\:".$disturb2AttrID."\\:]').filter('[value=".$disturb2OtherTerm[0]['meaning_id']."],[value^=".$disturb2OtherTerm[0]['meaning_id']."\\:]');
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
jQuery('span').filter('.control-box').each(function(idex, elem){
  if(jQuery(elem).find(':checkbox').length){
    jQuery(elem).prev().filter('label').addClass('auto-width');
    jQuery(elem).prev().after('<br/>');
  }
});\n";
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
          }  else if($rule[$i]=='no_record'){
            data_entry_helper::$late_javascript .= "
noRecCheckbox = jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').filter(':checkbox');
numRows = jQuery('.scPresence').filter('[value=1]').length;
if(numRows>0)
  noRecCheckbox.addClass('no_record').removeAttr('checked').attr('disabled','disabled');
else
  noRecCheckbox.addClass('no_record').removeAttr('disabled');
";
          } else if(substr($rule[0], 3, 4)!= 'Attr'){ // have to add for non attribute case.
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').addClass('".$rule[$i]."');";
          }
    }
    data_entry_helper::$late_javascript .= "// JS for survey methods grid control.
$.validator.addMethod('method-presence', function(value, element){
    var valid = jQuery('.method-presence').filter('[checked]').length > 0;
	if(valid){
	  jQuery('.method-presence').removeClass('ui-state-error').next('p.inline-error').remove();
	}
	return valid;
},
  \"".lang::get('validation_method-presence')."\");
$.validator.addMethod('scNumDead', function(value, element){
  var assocNumAlive = jQuery(element).closest('tr').find('.scNumAlive');
  var valid = true;
  if(jQuery(element).val()!='' || assocNumAlive.val()!='') {
    valid = ((jQuery(element).val()=='' ? 0 : jQuery(element).val()) + (assocNumAlive.val()=='' ? 0 : assocNumAlive.val()) > 0);
  }
  if(valid){
    assocNumAlive.removeClass('ui-state-error')
  } else {
    assocNumAlive.addClass('ui-state-error')
  }
  return valid;
},
  \"".lang::get('validation_scNumDead')."\");
jQuery('.scNumAlive').live('change', function(){
  var assocNumDead = jQuery(this).closest('tr').find('.scNumDead');
  assocNumDead.valid();
});
$.validator.addMethod('no_record', function(value, element){
  var numRows = jQuery('.scPresence').filter('[value=1]').length;
  var isChecked = jQuery(element).filter('[checked]').length>0;
  if(isChecked) return(numRows==0)
  else  return(numRows>0);
},
  \"".lang::get('validation_no_record')."\");
$.validator.addMethod('scCheckTaxon', function(value, element){
  var retVal = false;
  var row = jQuery(element).closest('tr');
  var classList = row.attr('class').split(/\s+/);
  $.each( classList, function(index, item){
    if (item.split(/-/)[0] === 'scMeaning') {
      if(jQuery('.'+item).find(':checkbox').filter('[checked]').length>0) retVal=true;
      if(jQuery('.'+item).find(':text').not('[value=]').length>0) retVal=true;
    }});
    // this is called at two points: when a value is entered and when the save button is called.
  // If this fails then fine, there is no data entered for this species.
  // If it passes then look up all the error paragraphs.
  if(retVal){
    $.each( classList, function(index, item){
      if (item.split(/-/)[0] === 'scMeaning') {
        jQuery('.'+item).find('p.inline-error').each(function(index, item){
          if(item.innerHTML == \"".lang::get('validation_taxon_data')."\")
            jQuery(item).prev('.ui-state-error').removeClass('ui-state-error');
            jQuery(item).remove();
        });
      }});
  }
  var inputs = row.find('input');
  if(inputs.eq(inputs.length-1)[0] != element) return true; // nb jQuery 1.3, we are only interested in displaying the error for the last entry in the row.
  return retVal;
},
  \"".lang::get('validation_taxon_data')."\");
jQuery('.scCheckTaxon:checkbox').live('change', function(value, element){
  if(jQuery(this).filter('[checked]').length > 0){
    var row = jQuery(this).closest('tr');
    var classList = row.attr('class').split(/\s+/);
    $.each( classList, function(index, item){
      if (item.split(/-/)[0] === 'scMeaning') {
        jQuery('.'+item).find('p.inline-error').each(function(index, item){
          if(item.innerHTML == \"".lang::get('validation_taxon_data')."\")
            jQuery(item).prev('.ui-state-error').removeClass('ui-state-error');
            jQuery(item).remove();
        });
      }});
  }
});\n";
  
    
    return '';
  }
  
  /**
   * The location module control is the same as the original Bats one, including Code processing.
   */

  protected static function get_control_surveymethodgrid($auth, $args, $tabalias, $options) {
    $surveyMethodIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Bats2SurveyMethod');
    if (!$surveyMethodIdAttr) return 'get_control_surveymethodgrid : The Survey Method Grid control must be used with a survey that has the Bats2SurveyMethod attribute associated with it.';
    // the survey methods grid is based on a grouping of samples determined by the
    // 1) the termlist id of the list of survey methods: external_key provided by argument surveyMethodTermList
    // 2) a default set of attributes to be loaded: Start and end times, visit, Unsuitablity
    // 3) Overrides for specific survey methods: Common wall disabled second survey
    $list = data_entry_helper::get_population_data(array('table' => 'termlist',
        'extraParams' => $auth['read'] + array('external_key' => $options['surveyMethodTermList'])));
    if (count($list)==0) return "get_control_surveymethodgrid : Termlist ".$options['surveyMethodTermList']." not available on the Warehouse";
    if (count($list)>1) return "get_control_surveymethodgrid : Multiple termlists identified by ".$options['surveyMethodTermList']." found on the Warehouse";
    $termlist = $list[0]['id'];
    $extraParams = $auth['read'] + array('termlist_id' => $termlist, 'view'=>'detail');
    $surveyMethods = data_entry_helper::get_population_data(array('table' => 'termlists_term', 'extraParams' => $extraParams));
    data_entry_helper::$javascript .= "// JS for survey method grid control.
jQuery('.method-presence').change(function(){
  var myTR = jQuery(this).closest('tr');
  if(jQuery(this).filter('[checked]').length>0) {
    myTR.find('.method-grid-cell').find('input,select').removeAttr('disabled').addClass('required').after('<span class=\"deh-required\">*</span>');
    // when you select a survey method: enable all the rows
    jQuery('.sg-tr-'+this.name.split(':')[2]).find('input').removeAttr('disabled');
  } else {
    if(jQuery('.mnhnl-species-grid').find('.sg-tr-'+this.name.split(':')[2]).length>0)
      if(!confirm(\"".lang::get('LANG_Confirm_Survey_Method_Removal')."\")){
        jQuery(this).attr('checked',true);
        return;
      };
    myTR.find('input,select').not('.method-presence').attr('disabled','disabled').val('').removeClass('required');
    myTR.find('.deh-required,.inline-error').remove();
    myTR.find('.required').removeClass('ui-state-error required');
    jQuery('.sg-tr-'+this.name.split(':')[2]).find('input').not('.scPresence').attr('disabled','disabled').removeAttr('checked').val('');
    myTR.find('.deh-required,.inline-error').remove();
  }
  jQuery('.mnhnl-species-grid').find('*').removeClass('ui-state-error').filter('.inline-error').remove();
});
";
    if(isset($options['removeOptions'])){
      $removeList = explode(';', $options['removeOptions']);
      foreach($removeList as $removeSpec){
        $removeDetails = explode(',', $removeSpec);
        for($i=1; $i<count($removeDetails); $i++){
          data_entry_helper::$javascript .= "
jQuery('.survey-method-grid').find('[name*=\\:".$removeDetails[0]."\\:smpAttr\\:]').find('option').filter('[value=".$removeDetails[$i]."]').remove();
";
        }
        data_entry_helper::$javascript .= "
if(jQuery('.survey-method-grid').find('[name*=\\:".$removeDetails[0]."\\:smpAttr\\:]').find('option').not('[value=]').length == 1)
  jQuery('.survey-method-grid').find('[name*=\\:".$removeDetails[0]."\\:smpAttr\\:]').find('option').filter('[value=]').remove();
";
      }
    }    
    if(isset($options['removeAttr'])){
      $removeList = explode(';', $options['removeAttr']);
      foreach($removeList as $removeSpec){
        $removeDetails = explode(',', $removeSpec);
        for($i=1; $i<count($removeDetails); $i++){
          data_entry_helper::$javascript .= "
jQuery('.survey-method-grid').find('[name$=\\:".$removeDetails[0]."\\:smpAttr\\:".$removeDetails[$i]."]').closest('td').empty();
";
        }
      }
    }    
    $smpAttributes = data_entry_helper::getAttributes(array(
       'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'{MyPrefix}:smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    ), true);
    $retval = '<br /><table class="survey-method-grid"><thead><tr>';
    $headingList = explode(',', $options['tableHeadings']);
    foreach($headingList as $idx=>$heading){
      $retval .= '<th>'.t($heading).(!$idx?'<span class="deh-required">*</span>':'').'</th>'; //because the text is a configuration item we use the drupal translation rather than hardcoded iform translations.
    }
    $retval .= '</tr></thead><tbody>';
    $subSamples = array();
    $subSamplesAttrs = array();
    if(isset(data_entry_helper::$entity_to_load['sample:id'])){
      $smpOptions = array(
        'table'=>'sample',
        'nocache'=>true,
        'extraParams'=> $auth['read']+ array('view'=>'detail', 'parent_id' => data_entry_helper::$entity_to_load['sample:id']));
      $subSamples = data_entry_helper::get_population_data($smpOptions);
      foreach($subSamples as $sample) {
        $subSamplesAttrs[$sample['id']] = data_entry_helper::getAttributes(array(
             'attrtable'=>'sample_attribute'
            ,'valuetable'=>'sample_attribute_value'
            ,'id'=>$sample['id']
            ,'key'=>'sample_id'
            ,'fieldprefix'=>'{MyPrefix}:smpAttr'
            ,'extraParams'=>$auth['read']
            ,'survey_id'=>$args['survey_id']), true);
        $subSamplesAttrs[$sample['id']][$visitAttr]['validation_rules']='required';
      }
    }
    // method:<sampleID>:<termlist_meaning_id>:[presence|smpAttr]:<attrdetails>].
    foreach($surveyMethods as $method){
      $smpID=false;
      $fieldname = '{MyPrefix}:presence:'.$surveyMethodIdAttr;
      $present='';
      $attrOpts = array('lookUpKey'=>'meaning_id',
                        'extraParams' => $auth['read'],
                        'language' => iform_lang_iso_639_2($args['language']),
                        'disabled'=>'disabled');
      foreach($subSamples as $subSample){
        foreach($subSamplesAttrs[$subSample['id']] as $attr) {
          if($attr['attributeId'] == $surveyMethodIdAttr && $attr['default'] == $method['meaning_id']) {
            $smpID=$subSample['id'];
            $fieldname = str_replace('smpAttr','presence',$attr["fieldname"]);
            $present=" checked=\"checked\" ";
            unset($attrOpts['disabled']);
          }
        }
      }
      // any unselected survey methods must have equivalent rows in species grid and cloneable grid disabled. 
      if($present=='')
        data_entry_helper::$javascript .= "\njQuery('.sg-tr-".$method['meaning_id']."').find('input').attr('disabled','disabled');\n";
      
      $fieldprefix='method:'.($smpID ? $smpID : '-').':'.$method['meaning_id'];
      $retval .= str_replace('{MyPrefix}',$fieldprefix,'<tr><td><input type="hidden" name="'.$fieldname.'" class="method-presence" value=0><input type="checkbox" class="method-presence method-'.$method['meaning_id'].'" name="'.$fieldname.'" id="'.$fieldname.'" value=1 '.$present.'><label for="'.$fieldname.'">'.$method['term'].'</label></td>');
      $attrList = explode(',', $options['defaultAttrs']);
      foreach($attrList as $attrID){
        $myAttr = $smpID ? $subSamplesAttrs[$smpID][$attrID] : $smpAttributes[$attrID];
        unset($myAttr['caption']);
        $attrOpts['class']= "smg-".preg_replace('/[^a-zA-Z0-9]/', '', strtolower($myAttr['untranslatedCaption']));
        if($smpID) $attrOpts['validation'] = array('required');
        $retval .= str_replace('{MyPrefix}',$fieldprefix, 
              '<td class="method-grid-cell">'.data_entry_helper::outputAttribute($myAttr, $attrOpts).'</td>');
      }
      $retval .= '</tr>';
    }
    $retval .= '</tbody></table><br />';
    $sketchAttrID=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Sketch provided');
    if($sketchAttrID) {
      data_entry_helper::$javascript .= "
var sketchAttr = jQuery('[name=smpAttr\\:".$sketchAttrID."],[name^=smpAttr\\:".$sketchAttrID."\\:]').not(':hidden').next();
var smgrid = jQuery('.survey-method-grid');
smgrid.next().filter('br').remove();
var smtext = smgrid.next().filter('div');
sketchAttr.after('<br/>');
if(smtext.length) sketchAttr.after(smtext);
sketchAttr.after(smgrid);
sketchAttr.after('<br/>');
";
    }
    
    return $retval;
  }
  
  /**
   * Get the custom species control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    $surveyMethodIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Bats2SurveyMethod');
    if (!$surveyMethodIdAttr) return 'get_control_species : This Species control must be used with a survey that has the Bats2SurveyMethod attribute associated with it.';
    $list = data_entry_helper::get_population_data(array('table' => 'termlist',
        'extraParams' => $auth['read'] + array('external_key' => $options['surveyMethodTermList'])));
    if (count($list)==0) return "get_control_species : Termlist ".$options['surveyMethodTermList']." not available on the Warehouse";
    if (count($list)>1) return "get_control_species : Multiple termlists identified by ".$options['surveyMethodTermList']." found on the Warehouse";
    $termlist = $list[0]['id'];
    $extraParams = $auth['read'] + array('termlist_id' => $termlist, 'view'=>'detail');
    $surveyMethods = data_entry_helper::get_population_data(array('table' => 'termlists_term', 'extraParams' => $extraParams));
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
          'PHPtaxonLabel' => true,
          'language' => iform_lang_iso_639_2($user->lang) // used for termlists in attributes
         ,'max_species_ids'=>$args['max_species_ids']
         ,'surveyMethodAttrId'=>$surveyMethodIdAttr
         ,'surveyMethods'=>$surveyMethods
    ), $options);
    if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
    call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args, array());
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
    // Load any existing sample's occurrence data into $entity_to_load: first have to load the survey method subsamples.
    if (isset(data_entry_helper::$entity_to_load['sample:id'])){
      $subSamplesAttrs = array();
      $smpOptions = array(
        'table'=>'sample',
        'nocache'=>true,
        'extraParams'=> $options['readAuth']+ array('view'=>'detail', 'parent_id' => data_entry_helper::$entity_to_load['sample:id']));
      $subSamples = data_entry_helper::get_population_data($smpOptions);
      foreach($subSamples as $sample) {
        $subSamplesAttrs[$sample['id']] = data_entry_helper::getAttributes(array(
             'attrtable'=>'sample_attribute'
            ,'valuetable'=>'sample_attribute_value'
            ,'id'=>$sample['id']
            ,'key'=>'sample_id'
            ,'fieldprefix'=>'{MyPrefix}:smpAttr'
            ,'extraParams'=>$options['readAuth']
            ,'survey_id'=>$args['survey_id']), true);
        $subSamplesAttrs[$sample['id']][$visitAttr]['validation_rules']='required';
      }
      foreach($options['surveyMethods'] as $i=>$method){
        $smpID=false;
        foreach($subSamples as $subSample){
          foreach($subSamplesAttrs[$subSample['id']] as $attr) {
            if($attr['attributeId'] == $options['surveyMethodAttrId'] && $attr['default'] == $method['meaning_id']) {
              $smpID=$subSample['id'];
              $options['surveyMethods'][$i]['smpID']=$smpID;
            }
          }
        }
        if($smpID) self::preload_species_checklist_occurrences($smpID, $method['meaning_id'], $options['readAuth']);
      }
    }
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
      foreach($attributes as $idx=>$attr) $attributes[$idx]['class'] = "scCheckTaxon"; // this allows extra validation
      // Get the attribute and control information required to build the custom occurrence attribute columns
      self::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $retVal = "<p>".lang::get('LANG_SpeciesInstructions')."</p>\n";
      if (isset($options['lookupListId'])) {
         self::$cloneableTable = self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $retVal .= '<table class="ui-widget ui-widget-content mnhnl-species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $retVal .= self::get_species_checklist_header($options, $attributes).'<tbody>';
      $attrsPerRow=array();
      foreach ($attributes as $attrId=>$attr) {
        $row=substr ( $attr['inner_structure_block'], 3);
        if(!isset($attrsPerRow[$row])) {
          $attrsPerRow[$row]=array();
        }
        $attrsPerRow[$row][] = $attr["attributeId"];
      }
      $rows = array();
      $rowIdx = 0;
      // each row grouping is driven by the ttlid, not the occurrence, as there is a different occurrence for each survey method.
      // that said we want it to be in general occurrence order so it matches the order in which the occurrences are created
      // i.e. in order of first occurrence in ttl group
      $ttlidList=array();
      $ttlList=array();
      foreach ($occList as $occ) {
        $ttlid = $occ['taxon']['id'];
        if(!in_array($ttlid,$ttlidList)){
          $ttlidList[] = $ttlid;
          $ttlList[$ttlid] = $occ['taxon'];
        }
      }
      foreach ($ttlidList as $ttlid) {
        $id=1;
        foreach($options['surveyMethods'] as $method){
          $existing_record_id='';
          foreach ($occList as $occIt) { // get the occurrence for this method/ttl combination
            if($occIt['taxon']['id']==$ttlid && $occIt['method'] == $method['meaning_id']){
              $occ=$occIt;
              $existing_record_id = $occ['id'];
            }
          }
          $retVal .= '<tr class="scMeaning-'.$ttlList[$ttlid]['taxon_meaning_id'].' scDataRow sg-tr-'.$method['meaning_id'].' '.($id=='1'?'scFirstRow':'').'">';
          if($id=='1'){
          	$firstCell = data_entry_helper::mergeParamsIntoTemplate($ttlList[$ttlid], 'taxon_label', false, true);
            if ($options['PHPtaxonLabel']) $firstCell=eval($firstCell);
            // assume always removeable and scPresence is hidden.
            $retVal .= '<td class="ui-state-default remove-row" style="width: 1%" rowspan="'.(count($attrsPerRow)).'">X</td>';
            $retVal .= str_replace('{content}', $firstCell, str_replace('{colspan}', 'rowspan="'.(count($attrsPerRow)).'"', $indicia_templates['taxon_label_cell']));
          }
          $ctrlId = "sc:".$method['meaning_id'].":$ttlid:".($existing_record_id ? $existing_record_id : "x".$rowIdx).":present";
          $retVal .= '<td>'.$method['term'].':</td><td class="scPresenceCell" style="display:none"><input type="hidden" class="scPresence" name="'.$ctrlId.'" value="1" /></td><td><span>';
          foreach ($attrsPerRow[$id] as $attrId) {
            // no complex values in checkboxes as the controls are vanilla
          	if ($existing_record_id) {
              $search = preg_grep("/^sc:".$method['meaning_id'].":$ttlid:$existing_record_id:occAttr:$attrId".'[:[0-9]*]?$/', array_keys(data_entry_helper::$entity_to_load));
              $ctrlId = (count($search)===1) ? implode('', $search) : "sc:".$method['meaning_id'].":$ttlid:$existing_record_id:occAttr:$attrId";
            } else {
              $ctrlId = "sc:".$method['meaning_id'].":$ttlid:x$rowIdx:occAttr:$attrId";
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
            $retVal .= '<span class="scKeepTogether">'.$oc.'</span> ';
          }
          $retVal .= '</span></td></tr>';
          $id++;
          $rowIdx++;
        }
      }
      if ($rowIdx==0) $retVal .= "<tr style=\"display: none\"><td></td></tr>\n";
      $retVal .= "</tbody></table>\n";
      data_entry_helper::$javascript .= "\njQuery('#species,.scClonableRow').find(':checkbox').addClass('sgCheckbox');\n";
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
              taxaList += (taxaList == '' ? '' : '<br/>')+data[i].taxon;
            else
              taxaList = '<em>'+data[i].taxon+'</em>'+(taxaList == '' ? '' : '<br/>'+taxaList);
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
      return $retVal;
    } else {
      return $occList['error'];
    }
  }

  /**
   * Build a JavaScript function  to format the autocomplete item list according to the form parameters
   * autocomplete_include_both_names and autocomplete_include_taxon_group.
   */
  protected static function build_grid_taxon_label_function($args, $options) {
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
        '    "auth_token"=>"'.parent::$auth['read']['auth_token'].'",'."\n".
        '    "nonce"=>"'.parent::$auth['read']['nonce'].'",'."\n".
        '    "taxon_list_id"=>'.$args['extra_list_id'].'),'."\n".
        '  "table"=>"taxa_taxon_list");'."\n".
        '$responseRecords = data_entry_helper::get_population_data($taxa_list_args);'."\n".
        '$taxaList = "";'."\n".
        '$taxaMeaning = -1;'."\n".
        'foreach ($responseRecords as $record)'."\n".
        '  if($record["id"] == {id}) $taxaMeaning=$record["taxon_meaning_id"];'."\n".
        'foreach ($responseRecords as $record){'."\n".
        '  if($record["id"] != {id} && $taxaMeaning==$record["taxon_meaning_id"]){'."\n".
        '    if($record["preferred"] == "f")'."\n".
        '      $taxaList .= ($taxaList == "" ? "" : "<br/>").$record["taxon"];'."\n".
        '    else'."\n".
        '      $taxaList = "<em>".$record["taxon"]."</em>".($taxaList == "" ? "" : "<br/>".$taxaList);'."\n".
        '}}'."\n".
        '$r .= "<br/>".$taxaList;'."\n".
        'return $r;'."\n";
    // Set it into the indicia templates
    $indicia_templates['taxon_label'] = $php;
  }
  
  public static function preload_species_checklist_occurrences($sampleId, $method, $readAuth) {
    $occurrenceIds = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the
    // data we need.
    if (is_null(data_entry_helper::$validation_errors)) {
      $occurrences = data_entry_helper::get_population_data(array(
        'table' => 'occurrence',
        'extraParams' => $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f'),
        'nocache' => true
      ));
      foreach($occurrences as $occurrence){
        data_entry_helper::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];
        data_entry_helper::$entity_to_load['sc::'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':present'] = true;
        data_entry_helper::$entity_to_load['sc:'.$method.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
        data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
        data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
        // Keep a list of all Ids
        $occurrenceIds[$occurrence['id']] = $occurrence['taxa_taxon_list_id'];
      }
      // load the attribute values into the entity to load as well
      $attrValues = data_entry_helper::get_population_data(array(
        'table' => 'occurrence_attribute_value',
        'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
        'nocache' => true
      ));
      foreach($attrValues as $attrValue) {
        data_entry_helper::$entity_to_load['sc:'.$method.':'.$occurrenceIds[$attrValue['occurrence_id']].':'.$attrValue['occurrence_id'].':occAttr:'.$attrValue['occurrence_attribute_id'].(isset($attrValue['id'])?':'.$attrValue['id']:'')]
            = $attrValue['raw_value'];
      }
    }
    return $occurrenceIds;
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
    // first row has X to remove row, plus species
    $rows=array();
    foreach ($attributes as $attrId=>$attr) {
    	$row=substr ( $attr['inner_structure_block'], 3);
    	if(!isset($rows[$row])) {
    		$rows[$row]=array();
    	}
    	$rows[$row][] = $attr["attributeId"];
    }
    $id=1;
//    $r = '<table border=3 id="'.$options['id'].'-scClonable"><tbody>';
    $r = '<table style="display: none" id="'.$options['id'].'-scClonable"><tbody>';
    foreach($options['surveyMethods'] as $method){
      $r .= '<tr class="scClonableRow sg-tr-'.$method['meaning_id'].' '.($id=='1'?'scFirstRow':'').'" id="'.$options['id'].'-scClonableRow'.$id.'">';
      if($id=='1'){
        $r .= '<td class="ui-state-default remove-row" style="width: 1%" rowspan="'.(count($rows)).'">X</td>';
        $r .= str_replace('{colspan}', 'rowspan="'.(count($rows)).'"', $indicia_templates['taxon_label_cell']);
      }
      $r .= '<td>'.$method['term'].':</td><td class="scPresenceCell" style="display:none"><input type="hidden" class="scPresence" name="sc:'.$method['meaning_id'].':-ttlId-::present"" value="0" /></td><td>';
      foreach ($rows[$id] as $attrId) {
        $oc = $occAttrControls[$attrId];
        // as we are using meaning_ids, we can't use standard default value method.
        if (isset($attributes[$attrId]['default']) && !empty($attributes[$attrId]['default'])) {
          $existing_value=$attributes[$attrId]['default'];
          // For select controls, specify which option is selected from the existing value. checkbox controls are vanilla so no complex values
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
        $r .= str_replace('{fieldname}', "sc:".$method['meaning_id'].":-ttlId-::occAttr:".$attrId, $oc);
      }
      $r .= '</td></tr>';
      $id++;
    }
    $r .= '</tbody></table>';
    return $r;
  }
  /**
   * Retrieve the grid header row for the species checklist grid control.
   * @param array $options Control options array.
   * @param array $occAttrs Array of custom attributes included in the grid.
   * @return string Html for the <thead> element.
   */
  private static function get_species_checklist_header($options, $attributes) {
    $r = '';
    if ($options['header']) {
      $r .= "<thead class=\"ui-widget-header\"><tr>";
      // assume always removeable
      $r .= '<th colspan=2>'.lang::get('species_checklist.species').'</th>';
      $r .= '<th colspan=2>'.lang::get('species_checklist.observations').'</th>';
      $r .= '</tr></thead>';
    }
    return $r;
  }

  private static function gscol_cmp($k1, $k2){
    return intval($k1)-intval($k2);
  }
  public static function get_species_checklist_occ_list($options) {
    // at this point the data_entry_helper::$entity_to_load has been preloaded with the occurrence data.
    // copy the options array so we can modify it
    $extraTaxonOptions = array_merge(array(), $options);
    // We don't want to filter the taxa to be added, because if they are in the sample, then they must be included whatever.
    $ids = array();
    unset($extraTaxonOptions['extraParams']['taxon_list_id']);
    unset($extraTaxonOptions['extraParams']['preferred']);
    unset($extraTaxonOptions['extraParams']['language_iso']);
    foreach(data_entry_helper::$entity_to_load as $key => $value) {
      // 'sc:<method>:<taxa_taxon_list_id>:<occID>:occAttr:<attrID>[:<attrValID>]'
      $parts = explode(':', $key,4);
      // Is this taxon attribute data?
      if (count($parts) == 4 && $parts[0] == 'sc'&& $parts[1]!='' && $parts[2]!='-ttlId-' && $parts[3]!='' && !in_array($parts[2], $ids))
        $ids[] = $parts[2];
    }
    if(count($ids)==0) return $ids;
    $extraTaxonOptions['extraParams']['id'] = $ids;
    // append the taxa to the list to load into the grid
    $fullTaxalist = data_entry_helper::get_population_data($extraTaxonOptions);
    $occList = array();
    foreach(data_entry_helper::$entity_to_load as $key => $value) {
      // 'sc:<method>:<taxa_taxon_list_id>:<occID>:occAttr:<attrID>[:<attrValID>]'
      $parts = explode(':', $key,5);
      // Is this taxon attribute data?
      if (count($parts) > 2 && $parts[0] == 'sc' && $parts[1]!='' && $parts[2]!='-ttlId-') {
        if($parts[3]=='') $occList['error'] = 'ERROR PROCESSING entity_to_load: found name '.$key.' with no sequence/id number in part 3';
        else if(!isset($occList[$parts[3]])){
          $occ['id'] = $parts[3];
          $occ['method'] = $parts[1];
          foreach($fullTaxalist as $taxon){
            if($parts[2] == $taxon['id']) $occ['taxon'] = $taxon;
          }
          $occList[$parts[3]] = $occ;
        }
      }
    }
    uksort($occList, "self::gscol_cmp");
    return $occList;
  }
  

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    foreach($values as $key => $value){
      $parts = explode(':', $key, 5);
      if(count($parts)==3 && $parts[0]=='locAttr' && $parts[2]=='term')
        unset($values[$key]);
    }
    if (isset($values['source'])){ // comes from main Sites tab, Admins may create so need to check for locations_website entry
      $locModel = submission_builder::wrap_with_images($values, 'location');
      if(isset($values['locations_website:website_id'])) // assume no other submodels
        $locModel['subModels'] = array(array('fkId' => 'location_id',
                                             'model' => array('id' => 'locations_website',
                                                              'fields' => array('website_id' =>array('value' => $values['locations_website:website_id'])))));
      return $locModel;
    }
    if (isset($values['sample:location_id']) && $values['sample:location_id']=='') unset($values['sample:location_id']);
    if (isset($values['sample:recorder_names'])){
      if(is_array($values['sample:recorder_names'])){
        $values['sample:recorder_names'] = implode("\r\n", $values['sample:recorder_names']);
      }
    } // else just load the string
    if (isset($values['location:name'])) $values['sample:location_name'] = $values['location:name'];
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    $subModels=array();
    if(!isset($values['sample:deleted'])) {
      // loop through all survey methods subsamples
      foreach($values as $key => $value){
        $parts = explode(':', $key, 5);
        if ($parts[0] == 'method' && $parts[3] == 'presence'){
          $smp = array('fkId' => 'parent_id', 'model' => array('id' => 'sample', 'fields' => array()));
          $smp['model']['fields']['survey_id'] = array('value' => $values['survey_id']);
          $smp['model']['fields']['website_id'] = array('value' => $values['website_id']);
          $smp['model']['fields']['date'] = array('value' => $values['sample:date']);
          $smp['model']['fields']['smpAttr:'.$parts[4]] = array('value' => $parts[2]);
          if(isset($values['sample:location_id']))
            $smp['model']['fields']['location_id'] = array('value' => $values['sample:location_id']);
          else
            $smp['model']['fields']['location_id'] = array('value' => $values['location:parent_id']);
          if($value != '1') $smp['model']['fields']['deleted'] = array('value' => 't');
          if($parts[1] != '-') $smp['model']['fields']['id'] = array('value' => $parts[1]);
          foreach($values as $key1 => $value1){
            $moreParts = explode(':', $key1, 5);
            if ($moreParts[0] == 'method' && $moreParts[1] == $parts[1] && $moreParts[2] == $parts[2] && $moreParts[3]== 'smpAttr'){
              $smp['model']['fields']['smpAttr:'.$moreParts[4]] = array('value' => $value1);
            }
          }
          if(isset($values['sample:location_id'])) $smp['model']['fields']['location_id'] = array('value' => $values['sample:location_id']);
          else {
            $smp['model']['fields']['centroid_sref'] = array('value' => $values['sample:entered_sref']);
            $smp['model']['fields']['centroid_sref_system'] = array('value' => $values['sample:entered_sref_system']);
            $smp['model']['fields']['centroid_geom'] = array('value' => $values['sample:geom']);
          }
          if($value == '1' || $parts[1] != '-'){
            $occurrences = self::wrap_species_checklist($values, $parts[2]);
            if(count($occurrences)>0) 
              $smp['model']['subModels'] = $occurrences;
            $subModels[]=$smp;
          }
        }
      }
      $sampleMod['subModels']=$subModels;
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
  * function to allow multiple rows for the same species, plus linking to survey method
  */
  private static function wrap_species_checklist($arr, $method){
    if (array_key_exists('website_id', $arr))
      $website_id = $arr['website_id'];
    else throw new Exception('Cannot find website id in POST array!');
    // occurrences are included dependant on the present field... If present is 
    // Species checklist entries take the following format
    // sc:<method-meaning-id>:<taxa_taxon_list_id>:[<occurrence_id>|<sequence(negative)>]:occAttr:<occurrence_attribute_id>[:<occurrence_attribute_value_id>]
    // sc:<method-meaning-id>:<taxa_taxon_list_id>:[<occurrence_id>|<sequence(negative)>]:present
    // not doing occurrence images at this point - TBD
    $records = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      if (substr($key, 0, 3)=='sc:'){
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 5);
        if($a[3] && $a[1] == $method){
          if(!array_key_exists($a[3],$records)){
            $records[$a[3]]['taxa_taxon_list_id'] = $a[2];
            if(is_numeric($a[3]) && $a[3]>0) $records[$a[3]]['id'] = $a[3];
          }
          $records[$a[3]][$a[4]] = $value; // does attrs and present field
        }
      }
    }
    foreach ($records as $id => $record) {
    	$present = self::wrap_species_checklist_record_present($record);
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
  private static function wrap_species_checklist_record_present($record) {
    // if a present record is there and = 1, and some data has been filled in, return true
    // else return false.
    // have to accept a hit on any checkboxes, as can't differentiate between unchecked and text zero.
    if(!array_key_exists('present', $record) || $record['present']=='0') return false;
  	unset($record['taxa_taxon_list_id']); // discard ttlid, as no bearing on entered data.
    unset($record['id']);
    unset($record['present']);
    $recordData=implode('',$record);// this implodes the values only, not the keys
    return ($recordData!='');
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
  protected static function getArgDefaults($args) {
    $args['includeLocTools'] == false; 
    return $args;      
  }
} 