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
 * Introduce read only fields on section list for transect and date, copied over from front page.
 * Improve deletion: need to maintain the attachment to correct_sample, to enable undeleting if needed.
 * Transect restrictions?
 * Streamline attributes in submission - use new method?
 * 
 * Should be set up in "wizard" buttons mode
 */

require_once('mnhnl_dynamic_1.php');
require_once('includes/mnhnl_common.php');

class iform_mnhnl_butterflies extends iform_mnhnl_dynamic_1 {
  protected static $locations;
  protected static $svcUrl;

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_butterflies_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Butterflies form. Inherits from Dynamic 1.'
    );
  }
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Butterflies';  
  }

  public static function get_parameters() {    
    $parentVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'qual_dist_term_id',
          'caption'=>'Qualitive Distribution Termlist ID',
          'description'=>'The Indicia ID of the termlist for the Qualitive distribution flag.',
          'type'=>'int'
        ),
        array(
          'name'=>'qual_dist_attr_id',
          'caption'=>'Qualitive Distribution Occurrence Attribute ID',
          'description'=>'The Indicia ID of the occurrence Attribute for the Qualitive distribution flag.',
          'type'=>'int'
        ),
        array(
          'name'=>'ignore_qual_dist_id',
          'caption'=>'Qualitive Distribution Termlist Term Ignore ID',
          'description'=>'The Indicia ID of the termlist term for which a occurrence is not generated.',
          'type'=>'int'
        ),
        array(
          'name'=>'quant_dist_attr_id',
          'caption'=>'Quantative Distribution Occurrence Count Attribute ID',
          'description'=>'The Indicia ID of the Occurrence Attribute for the Quantative Distribution Count.',
          'type'=>'int'
        ),
        array(
          'name'=>'observer_attr_id',
          'caption'=>'Observer Sample Attribute ID',
          'description'=>'The Indicia ID of the Sample Attribute for the Observer.',
          'type'=>'int'
        ),
        array(
          'name'=>'month_attr_id',
          'caption'=>'Month Sample Attribute ID',
          'description'=>'The Indicia ID of the Sample Month drop down Attribute.',
          'type'=>'int'
        ),
        array(
          'name'=>'aucune_attr_id',
          'caption'=>'No observation Sample Attribute ID',
          'description'=>'The Indicia ID of the Sample Attribute for No observation.',
          'type'=>'int'
        ),
        array(
          'name'=>'init_species_ids',
          'caption'=>'List of default species to be included in Quantative Distribution list',
          'description'=>'Comma separated list of the Indicia IDs of those species to be included by default in the Quantative Distribution list.',
          'type'=>'string',
          'required'=>false
        ),
        array(
          'name'=>'max_species_ids',
          'caption'=>'max number of species to be returned by a search',
          'description'=>'The maximum number of species to be returned by the drop downs at any one time.',
          'default'=>25,
          'type'=>'int'
        ),
        array(
          'name'=>'max_number_sections',
          'caption'=>'Maximum Number of Sections',
          'description'=>'The Maximum Number of Sections in the Section List.',
          'default'=>8,
          'type'=>'int'
        )		
      )
    );
    $retVal=array();
    foreach($parentVal as $param){
      if($param['name'] == 'structure'){
        $param['default'] =
             "=General Information=\r\n".
              "[transect]\r\n".
              "[date]\r\n".
              "?Setting this date field automatically fills in the Month field below. This field must be between April and September, otherwise the Month field will not be filled in, and you will not be able to proceed.?\r\n".
              "[sectionnumber]\r\n".
              "[*]\r\n".
              "[sample comment]\r\n".
             "=Grid-based species records=\r\n".
              "[display transect and date]\r\n".
              "[transectgrid]\r\n".
             "=Section-based species records=\r\n".
              "[display transect and date]\r\n".
              "?The number of sections in this list is selected on the first page?\r\n".
              "[sectionlist]\r\n".
              "@smpAttr=[TBD]\r\n";
      }
      $retVal[] = $param;
    }
    
    return $retVal;
  }
  
  public static function get_form($args, $node, $response=null) {
    global $indicia_templates;
    global $user;
    $indicia_templates['select_item'] = '<option value="{value}" {selected} >{caption}&nbsp;</option>';
    if ($user->uid===0)
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    // we don't use the map, but a lot of the inherited code assumes the map is present.
    self::$svcUrl = data_entry_helper::$base_url.'/index.php/services';
    data_entry_helper::add_resource('openlayers');
    $indicia_templates['label'] = '<label for="{id}"{labelClass}>{label}:</label>'; // can't have the CR on the end
    $indicia_templates['zilch'] = ''; // can't have the CR on the end
    self::$locations = iform_loctools_listlocations($node);
    $retVal = parent::get_form($args, $node, $response);
    if(parent::$mode != self::MODE_GRID){
      iform_mnhnl_addCancelButton($args['interface']);
      data_entry_helper::$javascript .= "
$.validator.messages.required = \"".lang::get('validation_required')."\";";
      if(!iform_loctools_checkaccess($node,'superuser')){
        data_entry_helper::$javascript .= "
jQuery('[name=smpAttr\\:".$args['observer_attr_id']."],[name^=smpAttr\\:".$args['observer_attr_id']."\\:]').attr('readonly',true)";
        if(parent::$mode == self::MODE_NEW){
          data_entry_helper::$javascript .= ".val(\"".$user->name."\");";
        } else {
          data_entry_helper::$javascript .= ";";
        }
      } else {
        $userlist = iform_loctools_listusers($node);
        data_entry_helper::$javascript .= "
existing = jQuery('[name=smpAttr\\:".$args['observer_attr_id']."],[name^=smpAttr\\:".$args['observer_attr_id']."\\:]');
replacement = '<select name=\"'+existing.attr('name')+'\" >";
        foreach($userlist as $uid => $a_user){
          data_entry_helper::$javascript .= "<option value=\"".$a_user->name."\">".$a_user->name."&nbsp;</option>";
        }
        data_entry_helper::$javascript .= "</select>';
jQuery(replacement).insertBefore(existing).val(existing.val());
existing.remove();
";
      }
      data_entry_helper::$javascript .= "
// jQuery('#sample\\\\:date').datepicker( \"option\", \"minDate\", new Date(2010, 4 - 1, 1) );
Date.prototype.getMonthName = function() {
var m = ['".lang::get('January')."','".lang::get('February')."','".lang::get('March')."',
'".lang::get('April')."','".lang::get('May')."','".lang::get('June')."',
'".lang::get('July')."','".lang::get('August')."','".lang::get('September')."',
'".lang::get('October')."','".lang::get('November')."','".lang::get('December')."'];
return m[this.getMonth()];
} 
var monthAttr = jQuery('[name=smpAttr\\\\:".$args['month_attr_id']."],[name^=smpAttr\\\\:".$args['month_attr_id']."\\\\:]').attr('disabled', true);
monthAttr.before('<input type=\"hidden\" id=\"storedMonth\" name=\"'+monthAttr.attr('name')+'\">');
updateSampleDate = function(context, doAlert){
  jQuery('.displayDateDetails').empty().append('<span>'+jQuery('[name=sample\\:date]').val()+'</span>');
  var myDate = jQuery(context).datepicker(\"getDate\");
  var monthAttr = jQuery('[name=smpAttr\\\\:".$args['month_attr_id']."],[name^=smpAttr\\\\:".$args['month_attr_id']."\\\\:]').filter('select').val('');
  if(myDate != null){
    myDate = myDate.getMonthName();
    monthAttr.find(\"option:contains('\"+myDate+\"')\").attr('selected',true) ; 
    jQuery('#storedMonth').val(monthAttr.val()); // doing in this order converts the text to a number and stores that number in the storedMonth
  } else
    jQuery('#storedMonth').val('');
  if(doAlert && monthAttr.val() == \"\")
  	alert('Given date is outside valid month range (April to September).');
};
jQuery('#sample\\\\:date').change(function(){updateSampleDate(this, true);});
updateSampleDate('#sample\\\\:date', false);
jQuery('.tab-submit').unbind('click');
jQuery('.tab-submit').click(function() {
  var current=jQuery('#controls').tabs('option', 'selected');
  var tabinputs = jQuery('#entry_form div > .ui-tabs-panel:eq('+current+')').find('input,select');
  var secList = '';
  if (!tabinputs.valid()) { return; }
  var rows = jQuery('.sectionlist').find('tr');
  for(var i=1; i<= ".$args['max_number_sections']."; i++){
    if(jQuery('.sectionlist').find('[section='+i+']').length > 0) {
      var aucuneControl = jQuery(':checkbox[name^=\"SLA\\:'+i+'\\:\"]').filter('[name$=\"\\:".$args['aucune_attr_id']."\"]');
      var foundEntry = false;
      for(var j = 1; j < (rows.length-(numAttrs+1)); j++){
        foundEntry = foundEntry || (jQuery(rows[j]).find('td').filter(':eq('+i+')').find('[value!=\"\"]').length > 0);
      }
      if(!foundEntry && !aucuneControl.attr('checked')){
          secList = secList + (secList=='' ? '' : ', ') + i;
      }
    }
  }
  if (secList != ''){
    alert('The following sections have no species recorded against them: Section(s) '+secList+'. In these circumstances, the \"No observation\" checkbox must be checked for the relevant section.'); 
    return;
  }
  var form = jQuery(this).parents('form:first');
  form.submit();
});
";
      
    } else {
    $retVal .= "<div style=\"display:none\" />
    <form id=\"form-delete-survey\" action=\"".iform_mnhnl_getReloadPath()."\" method=\"POST\">".parent::$auth['write']."
       <input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />
       <input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />
       <input type=\"hidden\" name=\"sample:id\" value=\"\" />
       <input type=\"hidden\" name=\"sample:date\" value=\"2010-01-01\"/>
       <input type=\"hidden\" name=\"sample:location_id\" value=\"\" />
       <input type=\"hidden\" name=\"sample:deleted\" value=\"t\" />
    </form>
</div>";
    data_entry_helper::$javascript .= "
deleteSurvey = function(sampleID){
  if(confirm(\"Are you sure you wish to delete survey \"+sampleID)){
    jQuery.getJSON(\"".self::$svcUrl."/data/sample/\"+sampleID +
            \"?mode=json&view=detail&auth_token=".parent::$auth['read']['auth_token']."&nonce=".parent::$auth['read']["nonce"]."\" +
            \"&callback=?\", function(data) {
        if (data.length>0) {
          jQuery('#form-delete-survey').find('[name=sample\\:id]').val(data[0].id);
          jQuery('#form-delete-survey').find('[name=sample\\:date]').val(data[0].date_start);
          jQuery('#form-delete-survey').find('[name=sample\\:location_id]').val(data[0].location_id);
          jQuery('#form-delete-survey').submit();
  }});
  };
};
";
	
    }
    return $retVal;
  }
    
  public static function get_css() {
    return array('mnhnl_butterflies.css');
  }

  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    $isAdmin = user_access($args['edit_permission']);
    $auth = array('read'=>$readAuth);
    if(!$isAdmin) return('');
    if(!$retTabs) return array('#downloads' => lang::get('Reports'));
    $userNameAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS Username');
    $ObserverIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Observer');
    if (!$ObserverIdAttr) return lang::get('This form must be used with a survey that has the Observer sample attribute associated with it.');
    $MonthIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'MNHNL Month');
    if (!$MonthIdAttr) return lang::get('This form must be used with a survey that has the MNHNL Month sample attribute associated with it.');
    $NumInMonthIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Number In Month');
    if (!$NumInMonthIdAttr) return lang::get('This form must be used with a survey that has the Number In Month sample attribute associated with it.');
    $StartTimeIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Start time');
    if (!$StartTimeIdAttr) return lang::get('This form must be used with a survey that has the Start time sample attribute associated with it.');
    $EndTimeIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'End time');
    if (!$EndTimeIdAttr) return lang::get('This form must be used with a survey that has the End time sample attribute associated with it.');
    $TempIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Temperature');
    if (!$TempIdAttr) return lang::get('This form must be used with a survey that has the Temperature sample attribute associated with it.');
    $WindIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Wind force');
    if (!$WindIdAttr) return lang::get('This form must be used with a survey that has the Wind force sample attribute associated with it.');
    $CloudIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Cloud cover');
    if (!$CloudIdAttr) return lang::get('This form must be used with a survey that has the Cloud cover sample attribute associated with it.');
    $HabitatIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Habitat type');
    if (!$HabitatIdAttr) return lang::get('This form must be used with a survey that has the Habitat type sample attribute associated with it.');
    $NoObsIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'No observation');
    if (!$NoObsIdAttr) return lang::get('This form must be used with a survey that has the No observation sample attribute associated with it.');
    $ReliabilityIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Survey reliability');
    if (!$ReliabilityIdAttr) return lang::get('This form must be used with a survey that has the Survey reliability sample attribute associated with it.');

    return  '<div id="downloads" >
    <p>'.lang::get('LANG_Data_Download').'</p>
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies_grid.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=downloadgrid">
      <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "username_attr_id":'.$userNameAttr.', "observer_attr_id":'.$ObserverIdAttr.', "month_attr_id":'.$MonthIdAttr.', "numberinmonth_attr_id":'.$NumInMonthIdAttr.', "starttime_attr_id":'.$StartTimeIdAttr.', "endtime_attr_id":'.$EndTimeIdAttr.', "temperature_attr_id":'.$TempIdAttr.', "wind_attr_id":'.$WindIdAttr.', "cloud_attr_id":'.$CloudIdAttr.'}\' />
      <label>'.lang::get('Grid report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
    </form>
	<form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies_section.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=downloadsection">
      <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "username_attr_id":'.$userNameAttr.', "observer_attr_id":'.$ObserverIdAttr.', "month_attr_id":'.$MonthIdAttr.', "numberinmonth_attr_id":'.$NumInMonthIdAttr.', "starttime_attr_id":'.$StartTimeIdAttr.', "endtime_attr_id":'.$EndTimeIdAttr.', "temperature_attr_id":'.$TempIdAttr.', "wind_attr_id":'.$WindIdAttr.', "cloud_attr_id":'.$CloudIdAttr.', "habitat_attr_id":'.$HabitatIdAttr.', "no_obs_attr_id":'.$NoObsIdAttr.', "reliability_attr_id":'.$ReliabilityIdAttr.'}\' />
      <label>'.lang::get('Section report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
    </form>
  </div>';
	
  }
  
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
  	global $user;
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    $userIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS User ID');
    if (!$userIdAttr) return lang::get('This form must be used with a survey that has the CMS User ID sample attribute associated with it.');
    $userNameAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS Username');
    if (!$userNameAttr) return lang::get('This form must be used with a survey that has the CMS Username sample attribute associated with it.');
    $observerAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Observer');
    if (!$observerAttr) return lang::get('This form must be used with a survey that has the Observer sample attribute associated with it.');
        
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies';
    $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>(iform_loctools_checkaccess($node,'superuser') ? -1 :  $user->uid), // use -1 if superuser - non logged in will not get this far.
        'userName_attr_id'=>$userNameAttr,
        'userName'=>($user->name),
        'observer_attr_id'=>$observerAttr
    )
    ));	
    $r .= '<form>';    
    $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new')).'\'">';
    $r .= '</form>';
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
  protected static function get_control_transect($auth, $args, $tabalias, $options) {
    $defAttrOptions = array('extraParams'=>$auth['read'], 'validation' => array('required'));
    if(self::$locations == 'all'){
      $locOptions = array_merge(array('label'=>lang::get('LANG_Transect'), 'id'=>'sample:location_id'), $defAttrOptions);
      $locOptions['extraParams'] = array_merge(array('parent_id'=>'NULL', 'view'=>'detail', 'orderby'=>'name'), $locOptions['extraParams']);
      $ret = data_entry_helper::location_select($locOptions);
    } else {
      // can't use location select due to location filtering.
      $ret = "<label for=\"sample:location_id\">".lang::get('LANG_Transect').":</label>\n<select id=\"sample:location_id\" name=\"sample:location_id\" ".$disabled_text." class=\" \"  >";
      $url = self::$svcUrl.'/data/location?mode=json&view=detail&parent_id=NULL&orderby=name&auth_token='.$auth['read']['auth_token'].'&nonce='.$auth['read']["nonce"]; // could do new multiple fetch query
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entities = json_decode(curl_exec($session), true);
      if(!empty($entities)){
        foreach($entities as $entity){
          if(in_array($entity["id"], self::$locations)) {
            if($entity["id"] == data_entry_helper::$entity_to_load['sample:location_id']) {
              $selected = 'selected="selected"';
            } else {
              $selected = '';
            }
            $ret .= "<option value=\"".$entity["id"]."\" ".$selected.">".$entity["name"]."&nbsp;</option>";
          }
        }
      }
      $ret .= "</select>";
    }
    $ret .= "<input type=hidden name=\"sample:location_name\" value=\"\" /><br />";
    data_entry_helper::$javascript .= "
jQuery(\"#sample\\\\:location_id\").change(function(){
  jQuery('[name=sample\\:location_name]').val(jQuery(this).find(':selected')[0].text);
  jQuery('.displayTransectDetails').empty().append('<span>'+jQuery('[name=sample\\:location_name]').val()+'</span>');
});
jQuery(\"#sample\\\\:location_id\").change();
";
    return $ret;
  }
  
  protected static function get_control_transectgrid($auth, $args, $tabalias, $options) {
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
          'label'=>lang::get('transectgrid:taxa_taxon_list_id'),
          'fieldname'=>'transectgrid_taxa_taxon_list_id',
          'id'=>'transectgrid_taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'taxon_meaning_id',
          'columns'=>2,          
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'numValues'=>$args['max_species_ids']
    ), $options);
    // do not allow tree browser
    if ($args['species_ctrl']=='tree_browser')
      return '<p>Can not use tree browser in this context</p>';
    // this termlist is language independant so ignore language
    $detail_args = array(
        'label'=>'{LABEL}',
        'fieldname'=>'{FIELDNAME}',
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'extraParams'=>$auth['read'] + array('termlist_id' => $args['qual_dist_term_id']),
        'suffixTemplate' => 'zilch',
        'optionSeparator' => '',
        'labelClass' => 'narrow',
        'size'=>4 // for listboxes
    );
    data_entry_helper::$javascript .= "
build_empty_transectgrid = function(speciesID){
  // at this point the species ID should be the preferred one.
  // first check if already set up. If yes do nothing.
  if(jQuery('.transectgrid').find('[taxonID='+speciesID+']').length > 0) return;
  var container = jQuery('<div class=\"trSpeciesContainer\" ></div>').prependTo('.transectgrid');
  jQuery('<span class=\"right\"><img src=\"/misc/watchdog-error.png\" alt=\"Delete\"/></span>').attr('taxonID',speciesID).appendTo(container).click(function(){
    if(confirm(\"".lang::get('transectgrid:confirmremove')."\"+jQuery(this).parent().find('.trgridspecname')[0].textContent+\"?\")){
     jQuery(this).parent().find('select').each(function(){
      var parts = jQuery(this).attr('name').split(':');
      if(parts[5] != '-'){
        var delList = jQuery('#TGDEL').val();
        jQuery('#TGDEL').val((delList == '' ? '' : delList+',')+parts[5]);
      }
     });
     jQuery(this).parent().remove();
    }
  });
  jQuery('<span class=\"trgridspecname\"></span>').attr('taxonID',speciesID).appendTo(container);
  jQuery('<br /><span class=\"trgridothernames\"></span>').attr('taxonID',speciesID).appendTo(container);
  var table = jQuery('<table border=\"1\"></table>').appendTo(container);
  var sel = '".data_entry_helper::select($detail_args)."';
  for(var i=0; i<5; i++) {
    var row = jQuery('<tr></tr>').appendTo(table);
    // Fieldname is TG:speciesID:gridX:gridY:GridsampleID:OccID:AttrID
    for(var j=0; j<5; j++)
      jQuery('<td><span>'+((sel.replace(/{LABEL}/g, (j*2).toString()+((4-i)*2).toString())).replace(/{FIELDNAME}/g, 'TG:'+speciesID+':'+(j*2)+':'+(4-i)*2+':-:-:-'))+'</td>').appendTo(row);
  }
  jQuery.getJSON(\"".self::$svcUrl."/data/taxa_taxon_list/\"+speciesID +
		\"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."\" +
			\"&callback=?\", function(data) {
        if (data.length>0) {
          jQuery('.trgridspecname').filter('[taxonID='+data[0].id+']').empty().attr('meaningID', data[0].taxon_meaning_id).append('<b>'+data[0].taxon+'</b>');
          jQuery('.trgridothernames').filter('[taxonID='+data[0].id+']').empty().attr('meaningID', data[0].taxon_meaning_id);
          jQuery.getJSON(\"".self::$svcUrl."/data/taxa_taxon_list/\"+
              \"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&preferred=false&taxon_meaning_id=\" + data[0].taxon_meaning_id +
              \"&callback=?\", function(data) {
            if (data.length>0) {
              for(var i = 0; i<data.length; i++)
                jQuery('.trgridothernames').filter('[meaningID='+data[i].taxon_meaning_id+']').append(data[i].taxon+', ');
            }});
        }});
};

build_transectgrid = function(speciesID, X, Y, gridSampleID, occurrenceID, attributeID, value){
  // at this point the species ID should be the preferred one.
  build_empty_transectgrid(speciesID);
  var sel=jQuery('[name^=TG\\:'+speciesID+'\\:'+X+'\\:'+Y+'\\:]').attr('name','TG:'+speciesID+':'+X+':'+Y+':'+gridSampleID+':'+occurrenceID+':'+attributeID).val(value);
};

jQuery('#transectgrid_taxa_taxon_list_id').change(function(){
  // when this is called the value stored is the taxon_meaning_id: .
  // Initially check that row does not already exist:
  var existRows = jQuery('.trgridspecname').filter('[meaningID='+jQuery('#transectgrid_taxa_taxon_list_id').val()+']');
  if(existRows.length>0)
    alert(\"".lang::get('transectgrid:rowexists')."\"+existRows[0].textContent);
  else
    // Next convert to the preferred ID
    jQuery.getJSON(\"".self::$svcUrl."/data/taxa_taxon_list\" +
		\"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&preferred=true&taxon_meaning_id=\" + jQuery('#transectgrid_taxa_taxon_list_id').val() +
			\"&callback=?\", function(data) {
        if (data.length>0) {
  			build_empty_transectgrid(data[0].id);
        }});
  jQuery('#transectgrid_taxa_taxon_list_id\\\\:taxon').val('');
});


";
    $myHidden = '';
    // here put in a load of JS calls to build the grids
    if(isset(data_entry_helper::$entity_to_load['auth_token'])) // post failed
      foreach(data_entry_helper::$entity_to_load as $key => $value){
        $parts = explode(':', $key);
        if ($parts[0] == 'TG' && $value != $args['ignore_qual_dist_id']){
          data_entry_helper::$javascript .= "build_transectgrid(".$parts[1].",".$parts[2].",".$parts[3].",\"".$parts[4]."\",\"".$parts[5]."\",\"".$parts[6]."\",".$value.");
";
        } else if ($parts[0] == 'TGS' || $parts[0] == 'TGDEL'){
          $myHidden .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }
      }
    else {
     $myHidden = '<input type="hidden" id="TGDEL" name="TGDEL" value="" >';
     data_entry_helper::$javascript .= "jQuery('#TGDEL').val('');";
     if(isset(data_entry_helper::$entity_to_load['sample:id'])){ //sample specified
      $url = self::$svcUrl.'/data/sample?parent_id='.data_entry_helper::$entity_to_load['sample:id'];
      $url .= "&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entities = json_decode(curl_exec($session), true);
      foreach($entities as $entity){
        if (substr($entity['location_name'], 0, 3) == 'GR '){
          $X = substr($entity['location_name'], -2, 1);
          $Y = substr($entity['location_name'], -1);
          $myHidden .= '<input type="hidden" name="TGS:'.$X.':'.$Y.'" value="'.($entity['id']).'">';
          $url = self::$svcUrl.'/data/occurrence?sample_id='.($entity['id']);
          $url .= "&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $OCCentities = json_decode(curl_exec($session), true);
          foreach($OCCentities as $OCCentity){
          	$url = self::$svcUrl.'/data/occurrence_attribute_value?mode=json&view=list&nonce='.$auth['read']["nonce"].'&auth_token='.$auth['read']['auth_token'].'&deleted=f&occurrence_id='.$OCCentity['id'].'&occurrence_attribute_id='.$args['qual_dist_attr_id'];
            $session = curl_init($url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            $ATTRentities = json_decode(curl_exec($session), true);
            foreach($ATTRentities as $ATTRentity){
              data_entry_helper::$javascript .= "
build_transectgrid(".($OCCentity['taxa_taxon_list_id']).",".$X.",".$Y.",".($OCCentity['sample_id']).",".($ATTRentity['occurrence_id']).",".($ATTRentity['id']).",".($ATTRentity['raw_value']).");";
            }
          }
        }
      }
     }
    }
    $retVal = $myHidden.'<div>'.call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args).'</div><p>'.lang::get('transectgrid:bumpf1').'</p><p>'.lang::get('transectgrid:bumpf2').'</p><div class="transectgrid"></div>';
    data_entry_helper::$javascript .= "
// override the default results function - doesn't seem to use value field.
jQuery('input#transectgrid_taxa_taxon_list_id\\\\:taxon').unbind(\"result\");
jQuery('input#transectgrid_taxa_taxon_list_id\\\\:taxon').result(function(event, data, value) {
      jQuery('input#transectgrid_taxa_taxon_list_id').attr('value', value);
      jQuery('input#transectgrid_taxa_taxon_list_id').change();
});
";
    return $retVal;
  }


  protected static function get_control_sectionlist($auth, $args, $tabalias, $options) {
    $numAttrs=count($options['smpAttr']);
    $attributes = data_entry_helper::getAttributes(array(
       'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    ));
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
          'fieldname'=>'sectionlist_taxa_taxon_list_id',
          'id'=>'sectionlist_taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'taxon_meaning_id',
          'columns'=>2,          
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'numValues'=>$args['max_species_ids']
    ), $options);
    $defNRAttrOptions = array('extraParams'=>$auth['read']+array('orderby'=>'id'),
    				'lookUpKey' => 'meaning_id',
//    				'language' => iform_lang_iso_639_2($args['language']),
    				'suffixTemplate'=>'nosuffix');
    $defAttrOptions=$defNRAttrOptions;
    $defAttrOptions ['validation'] = array('required');
    
    // do not allow tree browser
    if ($args['species_ctrl']=='tree_browser')
      return '<p>Can not use tree browser in this context</p>';
    data_entry_helper::$javascript .= "
var numAttrs = ".$numAttrs.";
delete_section_species_row = function(row){
  if(confirm(\"".lang::get('sectionlist:confirmremove')."\"+row.find('.seclistspecname')[0].textContent+\"?\")){
    row.find('input').each(function(){
      // Fieldname is SL:speciesID:section:SectionsampleID:OccID:AttrValID
      var parts = jQuery(this).attr('name').split(':');
      if(parts[4] != '-'){
        var delList = jQuery('#SLDEL').val();
        jQuery('#SLDEL').val((delList == '' ? '' : delList+',')+parts[4]);
      }
    });
    row.remove();
    // recalc the aucune checkboxes.
    for(var i=1; i<= ".$args['max_number_sections']."; i++){
      if(jQuery('.sectionlist').find('[section='+i+']').length > 0) {
        section_column_changed(i);
      }
    }
  }
};
remove_section_columns = function(from, to){
  var rows=jQuery('.sectionlist').find('tr');
  for(var i=0; i< rows.length; i++){
    for(var j=to; j>= from; j--){
      var cell = jQuery(rows[i]).children(':eq('+j+')');
      cell.find('input').each(function(){
        // Fieldname is SL:speciesID:section:SectionsampleID:OccID:AttrValID
        var parts = jQuery(this).attr('name').split(':');
        if(parts[4] != '-'){
          var delList = jQuery('#SLDEL').val();
          jQuery('#SLDEL').val((delList == '' ? '' : delList+',')+parts[4]);
        }
      });
      cell.remove();
    }
  }
};
// at this point, the species ID should hold the preferred taxon id.
add_section_species_row = function(speciesID){
  // first check if already set up. If yes do nothing.
  if(jQuery('.sectionlist').find('[taxonID='+speciesID+']').length > 0) return;
  var remButton = jQuery('<img src=\"/misc/watchdog-error.png\" alt=\"Delete\"/>').click(function(){
    delete_section_species_row(jQuery(this).parent().parent()); // image, td, tr
  });
  var name = jQuery('<span class=\"seclistspecname\"></span>').attr('taxonID',speciesID);
  var cell = jQuery('<td></td>').append(remButton).append(name);
  var row = jQuery('<tr></tr>').data('taxonID',speciesID).insertBefore('.seclistspecrow').append(cell);
  for(var i=1; i<= ".$args['max_number_sections']."; i++){
    if(jQuery('.sectionlist').find('[section='+i+']').length > 0) {
      var sampleID = jQuery('.sectionlist').find('tr:eq(0)').find('th:eq('+i+')').data('sampleID');
      add_section_value(row, i, sampleID);  // these are all empty, so aucune value stays unchanged.
    }
  }
  jQuery.getJSON(\"".self::$svcUrl."/data/taxa_taxon_list/\"+speciesID +
		\"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."\" +
			\"&callback=?\", function(data) {
        if (data.length>0) {
          // we are assuming that this is the preferred ID
          jQuery('.seclistspecname').filter('[taxonID='+data[0].id+']').empty().attr('meaningID', data[0].taxon_meaning_id).append('<b>'+data[0].taxon+'</b>');
        }}
  );
};
section_column_changed = function(column){
  var aucuneControl = jQuery(':checkbox[name^=\"SLA\\:'+column+'\\:\"]').filter('[name$=\"\\:".$args['aucune_attr_id']."\"]').attr('disabled', false);
  var rows = jQuery('.sectionlist').find('tr');
  for(var j = 1; j < (rows.length-".($numAttrs+1)."); j++){
    if(jQuery(rows[j]).find('td').filter(':eq('+column+')').find('[value!=\"\"]').length > 0)
      aucuneControl.attr('checked', false).attr('disabled', true);
  };
};
section_data_changed = function(){
  section_column_changed(jQuery(this).attr('name').split(':')[2]);
};

add_section_value = function(row, column, sampleID){
  // Fieldname is SL:speciesID:section:SectionsampleID:OccID:AttrID
  var taxonID = jQuery(row).find('.seclistspecname').attr('taxonID');
  var ctrl = jQuery('<input type=\"text\" name=\"SL:'+taxonID+':'+column+':'+sampleID+':-:-\" class=\"sl-input digits\" value=\"\" />').change(section_data_changed);
  jQuery('<td></td>').append(ctrl).appendTo(row);
};

add_section_column = function(column, sampleID){
  var rows=jQuery('.sectionlist').find('tr');
  for(var i=1; i<= column; i++){
    if(jQuery('.sectionlist').find('[section='+i+']').length == 0) {
    	for(var j = 0; j < rows.length; j++){
    		if(j==0){ //header
    		  var header = jQuery('<span class=\"sectionlist-column-header\">".lang::get('sectionlist:section')." '+i+'</span>').attr('section',i);
    		  jQuery('<th></th>').data('sampleID', i==column ? sampleID : '-').append(header).appendTo(rows[j]);
    		} else if(j == (rows.length-".(1+$numAttrs).")) {// species selection row has no data.
    		  jQuery('<td></td>').appendTo(rows[j]);";
    global $indicia_templates;
    $tempLabel = $indicia_templates['label'];
    $indicia_templates['label'] = ''; // we don't want labels in the cell
      // Fieldname is SLA:section:SectionsampleID:AttrValID:AttrID
    for($i=0; $i<$numAttrs; $i++){	
      data_entry_helper::$javascript .= "
    		} else if(j == (rows.length-".($numAttrs-$i).")) { // section sample attribute rows.
    		  var newName = 'SLA:'+i+':'+(i==column ? sampleID : '-')+':-'; //this will replace the smpAttr, so the AttrID is left alone at the end.
    		  var attr = '".str_replace("\n", "",
      				data_entry_helper::outputAttribute($attributes[$options['smpAttr'][$i]],
      					($attributes[$options['smpAttr'][$i]]['data_type']=='Boolean' ||
      							$attributes[$options['smpAttr'][$i]]['data_type']=='B' ?
      						$defNRAttrOptions :
      						$defAttrOptions)))."';
    		  jQuery('<td>'+attr.replace(/smpAttr/g, newName)+'</td>').appendTo(rows[j]);";
    }
    $indicia_templates['label'] = $tempLabel;
    data_entry_helper::$javascript .= "
            } else
              add_section_value(rows[j], i, (i==column ? sampleID : '-'));
    	}
    } else if (i==column && jQuery(rows[0]).find('th:eq('+i+')').data('sampleID') == '-' && sampleID != '-'){
      jQuery(rows[0]).find('th:eq('+i+')').data('sampleID', sampleID);
      for(var j = 1; j < rows.length; j++){
        var input = jQuery(rows[j]).find('td:eq('+i+')').find('input,select');
        if(input.length > 0) {
          var parts = input.attr('name').split(':');
          if(parts[0]=='SL')
            input.attr('name','SL:'+parts[1]+':'+i+':'+sampleID+':'+parts[4]+':'+parts[5]);
          else if(parts[0]=='SLA')
            input.attr('name','SLA:'+i+':'+sampleID+':'+parts[3]+':'+parts[4]);
        }
      }
    }
  }
  if(jQuery('#sectionlist_number').val() < column) jQuery('#sectionlist_number').val(column);
  section_column_changed(column);
};
jQuery('#sectionlist_number').change(function(){
  // initially we put in the restriction that it is only possible to increase the number of sections.
  var currentCols = jQuery('.sectionlist-column-header').length;
  if(currentCols > jQuery('#sectionlist_number').val()){
    if(!confirm(\"".lang::get('sectionlist:confirmremovecolumns')."\")) return;
    remove_section_columns(parseInt(jQuery('#sectionlist_number').val())+1, currentCols);
  }
  add_section_column(jQuery('#sectionlist_number').val(),'-');
});
add_section_species = function(speciesID, section, sectionSampleID, occurrenceID, attributeID, value){
  add_section_column(section, sectionSampleID);
  add_section_species_row(speciesID);
  jQuery('[name^=\"SL\\:'+speciesID+'\\:'+section+'\\:\"]').attr('name','SL:'+speciesID+':'+section+':'+sectionSampleID+':'+occurrenceID+':'+attributeID).val(value).change()
  section_column_changed(section);
};
add_section_attribute = function(section, sectionSampleID, attrValID, attributeID, value){
  // Fieldname is SLA:section:SectionsampleID:AttrValID:AttrID
  add_section_column(section, sectionSampleID);
  jQuery('[name^=\"SLA\\:'+section+'\\:\"]').each(function(){
    var parts = jQuery(this).attr('name').split(':');
    if(attributeID == parts[4]) {
      var myName = jQuery(this).attr('name');
      var checkboxes = jQuery('[name=\"'+myName+'\"]:checkbox');
      if(checkboxes.length > 0){
        checkboxes.attr('checked', value == '1' ? true : false);
      } else {
        jQuery(this).val(value);
      }
      jQuery(this).attr('name','SLA:'+section+':'+sectionSampleID+':'+attrValID+':'+attributeID);
    }
  });
};
jQuery('#sectionlist_taxa_taxon_list_id').change(function(){
  // when this is called the value stored is the taxon_meaning_id: .
  // Initially check that row does not already exist:
  var existRows = jQuery('.seclistspecname').filter('[meaningID='+jQuery('#sectionlist_taxa_taxon_list_id').val()+']');
  if(existRows.length>0)
    alert(\"".lang::get('sectionlist:rowexists')."\"+existRows[0].textContent);
  else
    // Next convert to the preferred ID
    jQuery.getJSON(\"".self::$svcUrl."/data/taxa_taxon_list/\" +
		\"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&preferred=true&taxon_meaning_id=\" + jQuery('#sectionlist_taxa_taxon_list_id').val() +
			\"&callback=?\", function(data) {
        if (data.length>0) {
  			add_section_species_row(data[0].id);
        }});
  jQuery('#sectionlist_taxa_taxon_list_id\\\\:taxon').val('');
});
jQuery('#sectionlist_number').change();
";
    if($args['init_species_ids'] != '') {
      $init_species = explode(',', $args['init_species_ids']);
      foreach($init_species as $toAdd)
        data_entry_helper::$javascript .= "add_section_species_row(".$toAdd.");
";
    }
    $myHidden = '';
    // here put in a load of JS calls to build the grids
    if(isset(data_entry_helper::$entity_to_load['auth_token'])) // post failed
      foreach(data_entry_helper::$entity_to_load as $key => $value){
        $parts = explode(':', $key);
        if ($parts[0] == 'SL' && $value != ''){
          data_entry_helper::$javascript .= "add_section_species(".$parts[1].",".$parts[2].",\"".$parts[3]."\",\"".$parts[4]."\",\"".$parts[5]."\",\"".$value."\");
";
        } else if ($parts[0] == 'SLS' || $parts[0] == 'SLDEL'){
          $myHidden .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        } else if ($parts[0] == 'SLA' && $value != ''){
          data_entry_helper::$javascript .= "add_section_attribute(".$parts[1].",".$parts[2].",\"".$parts[3]."\",\"".$parts[4]."\",\"".$value."\");
";
        }
      }
    else if(isset(data_entry_helper::$entity_to_load['sample:id'])){ //sample specified
      $myHidden = '<input type="hidden" id="SLDEL" name="SLDEL" value="" >';
      data_entry_helper::$javascript .= "jQuery('#SLDEL').val('');";
      $url = self::$svcUrl.'/data/sample?parent_id='.data_entry_helper::$entity_to_load['sample:id'];
      $url .= "&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $entities = json_decode(curl_exec($session), true);
      foreach($entities as $entity){
        if (substr($entity['location_name'], 0, 3) == 'SL '){
          $section = explode(' ', $entity['location_name']);
          $section = $section[2];
          $myHidden .= '<input type="hidden" name="SLS:'.$section.'" value="'.($entity['id']).'">';
          $url = self::$svcUrl.'/data/occurrence?sample_id='.($entity['id']);
          $url .= "&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $OCCentities = json_decode(curl_exec($session), true);
          foreach($OCCentities as $OCCentity){
          	$url = self::$svcUrl.'/data/occurrence_attribute_value?mode=json&view=list&nonce='.$auth['read']["nonce"].'&auth_token='.$auth['read']['auth_token'].'&deleted=f&occurrence_id='.$OCCentity['id'].'&occurrence_attribute_id='.$args['quant_dist_attr_id'];
            $session = curl_init($url);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            $ATTRentities = json_decode(curl_exec($session), true);
            foreach($ATTRentities as $ATTRentity){
              	data_entry_helper::$javascript .= "
add_section_species(".($OCCentity['taxa_taxon_list_id']).",".$section.",".($OCCentity['sample_id']).",".($ATTRentity['occurrence_id']).",".(isset($ATTRentity['id']) ? $ATTRentity['id'] : "\"-\"").",".($ATTRentity['raw_value']==''?"\"\"":$ATTRentity['raw_value']).");";
            }
          } // TBS SLA
          $url = self::$svcUrl.'/data/sample_attribute_value?mode=json&view=list&nonce='.$auth['read']["nonce"].'&auth_token='.$auth['read']['auth_token'].'&deleted=f&sample_id='.($entity['id']);
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $ATTRentities = json_decode(curl_exec($session), true);
          foreach($ATTRentities as $ATTRentity){
      // Fieldname is SLA:section:SectionsampleID:AttrValID:AttrID
          	if($ATTRentity['id']!='')
          	  data_entry_helper::$javascript .= "
add_section_attribute(".$section.",".($entity['id']).",".($ATTRentity['id']).",".($ATTRentity['sample_attribute_id']).",".($ATTRentity['raw_value']).");";
          }
        }
      }
    }
    $retVal = $myHidden.'<div class="sectionlist"><table border="1" ><tr><th>'.lang::get('sectionlist:species').'</th></tr><tr class="seclistspecrow" ><td>'.call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args).'</td></tr>';
    for($i=0; $i<$numAttrs; $i++){
      $retVal .= '<tr><td>'.$attributes[$options['smpAttr'][$i]]['caption'].'</td></tr>';
    }
    $retVal .= '</table></div>';
    data_entry_helper::$javascript .= "
// override the default results function - doesn't seem to use value field.
jQuery('input#sectionlist_taxa_taxon_list_id\\\\:taxon').unbind(\"result\");
jQuery('input#sectionlist_taxa_taxon_list_id\\\\:taxon').result(function(event, data, value) {
      jQuery('input#sectionlist_taxa_taxon_list_id').attr('value', value);
      jQuery('input#sectionlist_taxa_taxon_list_id').change();
});
";
    
    return $retVal;
  }

  protected static function get_control_sectionnumber($auth, $args, $tabalias, $options) {
    $r = '<label for="sectionlist_number">'.lang::get('sectionlist:numberlabel').':</label><select id="sectionlist_number" name="sectionlist_number">';
    for($i=1; $i<=$args['max_number_sections']; $i++)
    	$r .= '<option value="'.$i.'">'.$i.'&nbsp;</option>';
    $r .= '</select>';
    return $r;
  }
  
  protected static function get_control_displaytransectanddate($auth, $args, $tabalias, $options) {
    $r = '<div><div class="displayTransDateContainer"><b>'.lang::get('Transect').'</b>:<span class="displayTransectDetails"></span></div>
<div class="displayTransDateContainer"><b>'.lang::get('Date').'</b>:<span class="displayDateDetails"></span></div></div>';
    return $r;
  }

  protected static function getSampleListGridPreamble() {
    global $user;
    $r = '<p>'.lang::get('LANG_SampleListGrid_Preamble').(iform_loctools_checkaccess(parent::$node,'superuser') ? lang::get('LANG_All_Users') : $user->name).'</p>';
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $sampleMod = data_entry_helper::wrap_with_attrs($values, 'sample');
    if(isset($values['sample:deleted'])) return($sampleMod);
    $subsamples = array();
    // first do transect grid
  	for($i=0; $i<5; $i++) {
      // Fieldname is TG:speciesID:gridX:gridY:GridsampleID:OccID:AttrID
      for($j=0; $j<5; $j++){
		$sa = array(
	      'fkId' => 'parent_id',
		  'model' => array(	
            'id' => 'sample',
            'fields' => array()));
        if(isset($values['TGS:'.($j*2).':'.($i*2)]))
          $sa['model']['fields']['id'] = array('value' => $values['TGS:'.($j*2).':'.($i*2)]);
        $sa['model']['fields']['date'] = array('value' => $values['sample:date']);
        $sa['model']['fields']['location_id'] = array('value' => $values['sample:location_id']);
        $sa['model']['fields']['location_name'] = array('value' => 'GR '.$values['sample:location_name'].' '.($j*2).($i*2));
        $sa['model']['fields']['website_id'] = array('value' => $values['website_id']);
        $sa['model']['fields']['survey_id'] = array('value' => $values['survey_id']);
        $suboccs = array();
        foreach($values as $key => $value){
        	$parts = explode(':', $key);
        	if ($parts[0] == 'TG' && $parts[2] == (string)($j*2) && $parts[3] == (string)($i*2)){
        		$occ = array('fkId' => 'sample_id',
                             'model' => array('id' => 'occurrence', 'fields' => array()));
                $occ['model']['fields']['taxa_taxon_list_id'] = array('value' => $parts[1]);
                $occ['model']['fields']['website_id'] = array('value' => $values['website_id']);
                if($parts[5] != '-'){
                  $occ['model']['fields']['id'] = array('value' => $parts[5]);
                }
                $attrFields = array('occurrence_attribute_id' => $args['qual_dist_attr_id'], 'value' => $value);
                if($parts[6] != '-'){
                  $attrFields['id'] = $parts[6];
                }
                $occ['model']['metaFields'] = array(
                    'occAttributes' => array('value' => array(array('id'=>'occurrence', 'fields' => $attrFields))));
                if($parts[5] != '-' || $value != $args['ignore_qual_dist_id'])
                  $suboccs[] = $occ;
            }
        }
        $sa['model']['subModels'] = $suboccs;
        if(isset($sa['model']['fields']['id']) || count($suboccs)>0)
          $subsamples[] = $sa;
      }
  	}
    // next do section list
  	for($i=1; $i<=$args['max_number_sections']; $i++) {
      // Fieldname is SL:speciesID:section:SectionsampleID:OccID:AttrID
      $sa = array(
	      'fkId' => 'parent_id',
	      'model' => array(	
          'id' => 'sample',
          'fields' => array()));
      if(isset($values['SLS:'.$i]))
          $sa['model']['fields']['id'] = array('value' => $values['SLS:'.$i]);
      $sa['model']['fields']['date'] = array('value' => $values['sample:date']);
      $sa['model']['fields']['location_id'] = array('value' => $values['sample:location_id']);
      $sa['model']['fields']['location_name'] = array('value' => 'SL '.$values['sample:location_name'].' '.$i);
      $sa['model']['fields']['website_id'] = array('value' => $values['website_id']);
      $sa['model']['fields']['survey_id'] = array('value' => $values['survey_id']);
      $saattrs = array();
      foreach($values as $key => $value){
        $parts = explode(':', $key);
        if ($parts[0] == 'SLA' && $parts[1] == (string)$i){
          // Fieldname is SLA:section:SectionsampleID:AttrValID:AttrID
          $attr = array("id" => "sample", "fields" => array());
          $attr['fields']['sample_attribute_id'] = $parts[4];
          if($parts[3] != '-') $attr['fields']['id'] = $parts[3];
          $attr['fields']['value'] = $value;
          if($parts[3] != '-' || $value != '') $saattrs[] = $attr;
        }
      }
      if(count($saattrs)>0)
          $sa['model']['metaFields'] = array('smpAttributes' => array('value' => $saattrs));
      $suboccs = array();
      foreach($values as $key => $value){
        $parts = explode(':', $key);
        if ($parts[0] == 'SL' && $parts[2] == (string)$i){
          // Fieldname is SL:speciesID:section:SectionsampleID:OccID:AttrValID
          $occ = array('fkId' => 'sample_id',
                             'model' => array('id' => 'occurrence',
                                              'fields' => array('taxa_taxon_list_id' => array('value' => $parts[1]),
                                                                'website_id' => array('value' => $values['website_id']))));
          if($parts[4] != '-') $occ['model']['fields']['id'] = array('value' => $parts[4]);
          if($parts[5] != '-' || $value != ''){
            $occ['model']['fields']['occAttr:'.$args['quant_dist_attr_id'].($parts[5] != '-' ? ':'.$parts[5] : '')] = array('value' => $value);
            $suboccs[] = $occ;
          }
        }
      }
      $sa['model']['subModels'] = $suboccs;
      if(isset($sa['model']['fields']['id']) || count($suboccs)>0 || count($saattrs)>0) $subsamples[] = $sa;
  	}
    if(isset($values['TGDEL'])){
      if($values['TGDEL'] != ''){
        $delList = explode(',', $values['TGDEL']);
        foreach($delList as $occID){
          $occ = array('fkId' => 'sample_id',
                             'model' => array('id' => 'occurrence', 'fields' => array()));
          $occ['model']['fields']['website_id'] = array('value' => $values['website_id']);
          $occ['model']['fields']['id'] = array('value' => $occID);
          $occ['model']['fields']['deleted'] = array('value' => 't');
          $subsamples[] = $occ;
        }
      }
    }
    if(isset($values['SLDEL'])){
      if($values['SLDEL'] != ''){
        $delList = explode(',', $values['SLDEL']);
        foreach($delList as $occID){
          $occ = array('fkId' => 'sample_id',
                             'model' => array('id' => 'occurrence', 'fields' => array()));
          $occ['model']['fields']['website_id'] = array('value' => $values['website_id']);
          $occ['model']['fields']['id'] = array('value' => $occID);
          $occ['model']['fields']['deleted'] = array('value' => 't');
          $subsamples[] = $occ;
        }
      }
    }
    if(count($subsamples)>0)
      $sampleMod['subModels'] = $subsamples;
    return($sampleMod);
  }
  
  protected static function getReportActions() {
    return array(array('display' => '', 'actions' => 
            array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))),
        array('display' => '', 'actions' => 
            array(array('caption' => lang::get('Delete'), 'javascript'=>'deleteSurvey({sample_id})'))));
  }
  

}