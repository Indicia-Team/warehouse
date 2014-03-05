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
require_once('includes/mnhnl_common.php');

class iform_mnhnl_reptiles extends iform_mnhnl_dynamic_1 {
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_reptiles_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'Luxembourg Reptile Biomonitoring form. Inherits from Dynamic 1.'
    );
  }
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Luxembourg Reptile Biomonitoring';  
  }

  public static function get_parameters() {    
    $retVal = array();
    $parentVal = array_merge(
      parent::get_parameters(),
      iform_mnhnl_getParameters(),
      array(
        array(
          'name'=>'attributeValidation',
          'caption'=>'Attribute Validation Rules',
          'description'=>'Client Validation rules to be enforced on attributes: allows more options than allowed by straight class led validation.',
          'type'=>'textarea',
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name'=>'targetSpeciesAttr',
          'caption'=>'Target Species Attribute',
          'description'=>'Name of lookup type Sample Attribute used to hold the target species. This is used in the control and the reporting.',
          'type'=>'text_input',
          'default' => 'ReptileTargetSpecies',
          'group' => 'User Interface'
        ),
        array(
          'name'=>'targetSpeciesAttrList',
          'caption'=>'Target Species Attribute List',
          'description'=>'Comma separated list of sample attribute IDs used in the target species grid. This is used in the control and the reporting.',
          'type'=>'text_input',
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
        ),
        array(
          'name' => 'reportFilenamePrefix',
          'caption' => 'Report Filename Prefix',
          'description' => 'Prefix to be used at the start of the download report filenames.',
          'type' => 'string',
          'default' => 'reptiles',
          'group' => 'Reporting'
        ),
        array(
          'name' => 'sites_download_report',
          'caption' => 'Sites download report',
          'description' => 'Name of the sites download report.',
          'type'=>'string',
          'group' => 'Reporting',
          'required' => false
        ),
        array(
          'name' => 'conditions_download_report',
          'caption' => 'Conditions download report',
          'description' => 'Name of the conditions download report.',
          'type'=>'string',
          'group' => 'Reporting',
          'required' => false
        ),
        array(
          'name' => 'species_download_report',
          'caption' => 'Species download report',
          'description' => 'Name of the species download report.',
          'type'=>'string',
          'group' => 'Reporting',
          'default' => 'reports_for_prebuilt_forms/MNHNL/mnhnl_reptile_download_report'
        )
      )
    );
    foreach($parentVal as $param){
      if($param['name'] == 'structure'){
        $param['default'] =
             "=Site=\r\n".
              "[custom JS]\r\n".
              "@attrRestrictionsProcessOrder=<TBD>\r\n".
              "@attrRestrictions=<TBD>\r\n".
              "[lux5k grid]\r\n".
              "[location attributes]\r\n".
              "[location spatial reference]\r\n".
              "[map]\r\n".
              "@layers=[\"ParentWMSLayer\",\"ParentLocationLayer\",\"SitePointLayer\",\"SitePathLayer\",\"SiteAreaLayer\",\"SiteLabelLayer\"]\r\n".
              "@editLayer=false\r\n".
              "@clickableLayers=[\"ParentWMSLayer\"]\r\n".
              "@clickableLayersOutputMode=custom\r\n".
              "@clickableLayersOutputDiv=clickableLayersOutputDiv\r\n".
              "@clickableLayersOutputFn=setClickedParent\r\n".
              "[point grid]\r\n".
              "@srefs=2169,LUREF (m),X,Y,;4326,Lat/Long Deg,Lat,Long,D;4326,Lat/Long Deg:Min,Lat,Long,DM;4326,Lat/Long Deg:Min:Sec,Lat,Long,DMS\r\n".
              "[location comment]\r\n".
             "=Conditions=\r\n".
              "[target species grid]\r\n".
              "@targetSpeciesTermList=reptile:targetSpecies\r\n".
              "@disableOptions=<TBD>\r\n".
              "[date]\r\n".
              "[recorder names]\r\n".
              "[*]\r\n".
              "@sep=&#32;\r\n".
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
      if($param['name'] == 'extendLocationNameTemplate')
        $param['default'] = '{name} : created by {Creator}';
        
      if($param['name'] != 'species_include_taxon_group' &&
          $param['name'] != 'link_species_popups' &&
          $param['name'] != 'species_include_both_names')
        $retVal[] = $param;
    }
    return $retVal;
  }

  public static function get_css() {
    return array('mnhnl_reptiles.css');
  }

  protected static function enforcePermissions(){
  	return true;
  }
  
  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    if(!user_access($args['edit_permission'])) return('');
    $targetSpeciesAttr=iform_mnhnl_getAttr(parent::$auth, $args, 'sample', $args['targetSpeciesAttr']);
    if(!$targetSpeciesAttr) return lang::get('This form must be used with a survey that has the '.$args['targetSpeciesAttr'].' attribute associated with it.');
    if(!$retTabs) return array('#downloads' => lang::get('Reports'), '#locations' => lang::get('LANG_Locations'));
    if($args['LocationTypeTerm']=='' && isset($args['loctoolsLocTypeID'])) $args['LocationTypeTerm']=$args['loctoolsLocTypeID'];
    $primary = iform_mnhnl_getTermID(array('read'=>$readAuth), 'indicia:location_types',$args['LocationTypeTerm']);
    data_entry_helper::$javascript .= "
jQuery('.downloadreportparams').val('{\"survey_id\":".$args['survey_id'].", \"location_type_id\":".$primary.", \"taxon_list_id\":".$args['extra_list_id'].", \"target_species_attr\":".$targetSpeciesAttr['attributeId'].", \"target_species_termlist\":".$targetSpeciesAttr['termlist_id'].(isset($args['targetSpeciesAttrList']) ? ", \"target_species_attr_list\":\"".$args['targetSpeciesAttrList']."\"":"")."}');
";
    return  '<div id="downloads" >
  <p>'.lang::get('LANG_Data_Download').'</p>'.($args['sites_download_report']!=''?'
  <form id="sitesReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['sites_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Sites">
    <input type="hidden" name="params" class="downloadreportparams" value="" />
    <label>'.lang::get('Sites report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
  </form>':'').($args['conditions_download_report']!=''?'
  <form id="conditionsReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['conditions_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Conditions">
    <input type="hidden" name="params" class="downloadreportparams" value="" />
    <label>'.lang::get('Conditions report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
  </form>':'').'
  <form id="speciesReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['species_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Species">
    <input type="hidden" name="params" class="downloadreportparams" value="" />
    <label>'.lang::get('Species report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
  </form>
</div>'.iform_mnhnl_locModTool(parent::$auth, $args, parent::$node);
	
  }
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
  	global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    $userIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS User ID');
    if (!$userIdAttr) return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can be tagged against their creator.');
    $userNameAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS Username');
    if (!$userNameAttr) return lang::get('This form must be used with a survey that has the CMS User Name attribute associated with it so records can be tagged against their creator.');
    $targetSpeciesIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', $args['targetSpeciesAttr']);
    if (!$targetSpeciesIdAttr) return lang::get('This form must be used with a survey that has the '.$args['targetSpeciesAttr'].' attribute associated with it.');
    
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/MNHNL/mnhnl_reptiles';
    $extraParams = array(
        'survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>(iform_loctools_checkaccess($node,'superuser') ? -1 :  $user->uid), // use -1 if superuser - non logged in will not get this far.
        'userName_attr_id'=>$userNameAttr,
        'userName'=>($user->name),
        'target_species_attr_id'=>$targetSpeciesIdAttr);
    if(isset($args['filterAttrs']) && $args['filterAttrs']!=''){
      global $custom_terms;
      $filterAttrs = explode(',',$args['filterAttrs']);
      $idxN=1;
      foreach($filterAttrs as $idx=>$filterAttr){
        $filterAttr=explode(':',$filterAttr);
        switch($filterAttr[0]){
        	case 'Display': break;
        	case 'Parent':
              $extraParams['attr_id_'.$idxN]=iform_mnhnl_getAttrID($auth, $args, 'location', $filterAttr[1]);
              $custom_terms['attr_'.$idxN]=lang::get($filterAttr[1]);
              $idxN++;
              break;
        	default:
              $extraParams['attr_id_'.$idxN]=iform_mnhnl_getAttrID($auth, $args, 'location', $filterAttr[0]);
              $custom_terms['attr_'.$idxN]=lang::get($filterAttr[0]);
              $idxN++;
              break;
        }
      }
    }
    $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 25),
      'autoParamsForm' => true,
      'extraParams' => $extraParams));	
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new')).'\'">';    
    }
    $r .= '</form>
<div style="display:none" />
  <form id="form-delete-survey" action="'.iform_mnhnl_getReloadPath().'" method="POST">'.$auth['write'].'
    <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
    <input type="hidden" name="survey_id" value="'.$args['survey_id'].'" />
    <input type="hidden" name="sample:id" value="" />
    <input type="hidden" name="sample:deleted" value="t" />
  </form>
</div>
<div style="display:none" />
  <form id="form-delete-survey-location" action="'.iform_mnhnl_getReloadPath().'" method="POST">'.$auth['write'].'
     <input type="hidden" name="website_id" value="'.$args['website_id'].'" />
     <input type="hidden" name="survey_id" value="'.$args['survey_id'].'" />
     <input type="hidden" name="sample:id" value="" />
     <input type="hidden" name="sample:deleted" value="t" />
     <input type="hidden" name="location:id" value="" />
     <input type="hidden" name="location:deleted" value="t" />
  </form>
</div>';
    data_entry_helper::$javascript .= "
deleteSurvey = function(sampleID){
  jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/sample/\"+sampleID +
          \"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."\" +
          \"&callback=?\", function(data) {
      if (data.length>0) {
        jQuery('#form-delete-survey').find('[name=sample\\:id]').val(data[0].id);
        jQuery('#form-delete-survey-location').find('[name=sample\\:id]').val(data[0].id);
        jQuery('#form-delete-survey-location').find('[name=location\\:id]').val(data[0].location_id);
        // next get the location ID from sample, count the samples that are attached to that location
        jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/sample?location_id=\"+data[0].location_id +
                \"&parent_id=NULL&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."\" +
                \"&callback=?\", function(sdata) {
            if (sdata.length==1) {
              var dialog = $('<p>".lang::get('The site only has this survey associated with it. Do you wish to delete the site as well?')."</p>').
                  dialog({ title: 'Delete Location Data?',
                    width: 400,
                    buttons: {
                      'Cancel': function() { dialog.dialog('close'); },
                      'Survey Only': function() {
                          dialog.dialog('close');
                          jQuery('#form-delete-survey').submit();
                        },
                      'Site and Survey':  function() {
                          dialog.dialog('close');
                          jQuery('#form-delete-survey-location').submit();
                        }}});
            } else if (sdata.length > 1) {
              if(confirm(\"".lang::get('Are you sure you wish to delete survey ')."\"+sampleID)){
                jQuery('#form-delete-survey').submit();
              }
            }
        });
      }
  });
};\n";
    return $r;
  }

  /* data_entry_helper::$entity_to_load holds the data to store, but comes in three flavours:
   * empty: brand new, no data
   * sample_id specified: editing existing record, only holds the top level sample data.
   * Submission failed: holds the POST array.
   */
  protected static function get_control_lux5kgrid($auth, $args, $tabalias, $options) {
    $ret = iform_mnhnl_lux5kgridControl($auth, $args, parent::$node, $options);
    return $ret;
  }
  protected static function get_control_locationspatialreference($auth, $args, $tabalias, $options) {
    return iform_mnhnl_SrefFields($auth, $args);
  }
  protected static function get_control_locationattributes($auth, $args, $tabalias, $options) {
    return iform_mnhnl_locationattributes($auth, $args, $tabalias, $options);
  }
  protected static function get_control_pointgrid($auth, $args, $tabalias, $options) {
    return iform_mnhnl_PointGrid($auth, $args, $options); 
  }
  
  /**
   * Get the recorder names control
   */
  protected static function get_control_recordernames($auth, $args, $tabalias, $options) {
    return iform_mnhnl_recordernamesControl(parent::$node, $auth, $args, $tabalias, $options);
  }
  
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
  	// possible clash with link_species_popups, so latter disabled.
    iform_mnhnl_addCancelButton($args['interface']);
    data_entry_helper::$javascript .= "
resetChildValue = function(child){
  var options = child.find('option').not('[value=]').not('[disabled]');
  if (options.length==1)
    child.val(options.val());
  else child.val('');
};
set_up_relationships = function(startAttr, parent, setval){
  start=false; // final field is treated differently, as it enforces no duplicates.
  myParentRow = jQuery(parent).closest('tr');
  for(var i=0; i < attrRestrictionsProcessOrder.length-1; i++){
    if(start || startAttr==attrRestrictionsProcessOrder[i]){
      start=true;
      var child = myParentRow.find('[name$=occAttr\\:'+attrRestrictionsProcessOrder[i]+'],[name*=occAttr\\:'+attrRestrictionsProcessOrder[i]+'\\:]');
      var childOptions = child.find('option').not('[value=]');
      resetChild=false;
      if(parent.val() == '') {
        childOptions.attr('disabled','disabled');
        if(setval) resetChild=true;
      } else {
        childOptions.attr('disabled','');
        for(var j=0; j < relationships.length; j++){
          if(relationships[j].child == attrRestrictionsProcessOrder[i]){
            var relParentVal = jQuery(parent).closest('tr').find('[name$=occAttr\\:'+relationships[j].parent+'],[name*=occAttr\\:'+relationships[j].parent+'\\:]').val();
            for(var k=0; k < relationships[j].values.length; k++){
              if(relParentVal == relationships[j].values[k].value) {
                childOptions.each(function(index, Element){
                  for(var m=0; m < relationships[j].values[k].list.length; m++){
                    if(relationships[j].values[k].list[m] == $(this).val()){
                      $(this).attr('disabled','disabled');
                      if($(this).val() == child.val() && setval) resetChild=true;
                    }}
                  });
              }}}}
       }
       if(child.val()=='' && setval) resetChild=true;
       if(resetChild) resetChildValue(child);
    }
  }
  // something has changed: now need to go through ALL rows final field, not just ours, and eliminate options which would cause a duplicate.
  // but some of those may have been re-added by the change so have to reset all options!
  i= attrRestrictionsProcessOrder.length-1;
  var tableRows = jQuery(parent).closest('table').find('.scDataRow');
  tableRows.each(function(index, Row){
    var child = jQuery(Row).find('[name$=occAttr\\:'+attrRestrictionsProcessOrder[i]+'],[name*=occAttr\\:'+attrRestrictionsProcessOrder[i]+'\\:]');
    var parent = jQuery(Row).find('[name$=occAttr\\:'+attrRestrictionsProcessOrder[i-1]+'],[name*=occAttr\\:'+attrRestrictionsProcessOrder[i-1]+'\\:]');
    var childOptions = child.find('option').not('[value=]');
    resetChild=false;
    if(parent.val() == '') {
      childOptions.attr('disabled','disabled'); // all disabled.
      if(setval && myParentRow[0]==Row) resetChild=true;
    } else {
      childOptions.attr('disabled','');
      for(var j=0; j < relationships.length; j++){
        if(relationships[j].child == attrRestrictionsProcessOrder[i]){
          var relParentVal = jQuery(Row).find('[name$=occAttr\\:'+relationships[j].parent+'],[name*=occAttr\\:'+relationships[j].parent+'\\:]').val();
          for(var k=0; k < relationships[j].values.length; k++){
            if(relParentVal == relationships[j].values[k].value) {
              childOptions.each(function(index, Element){
                for(var m=0; m < relationships[j].values[k].list.length; m++){
                  if(relationships[j].values[k].list[m] == $(Element).val()){
                    $(Element).attr('disabled','disabled');
                    if($(Element).val() == child.val() && setval && myParentRow[0]==Row) resetChild=true;
                }}
              });
    }}}}}
    // now check duplicates
    var classList = jQuery(Row).attr('class').split(/\s+/);
    jQuery.each( classList, function(index, item){ 
      var parts= item.split(/-/);
      if(parts[0]=='scMeaning'){
        sameSpeciesRows=jQuery('.'+item).not(Row);
        sameSpeciesRows.each(function(index, sameSpeciesRow){
          var same=true;
          for(var j=0; j < attrRestrictionsProcessOrder.length-1; j++){
            otherVal = jQuery(sameSpeciesRow).find('[name$=occAttr\\:'+attrRestrictionsProcessOrder[j]+'],[name*=occAttr\\:'+attrRestrictionsProcessOrder[j]+'\\:]').val();
            myVal = jQuery(Row).find('[name$=occAttr\\:'+attrRestrictionsProcessOrder[j]+'],[name*=occAttr\\:'+attrRestrictionsProcessOrder[j]+'\\:]').val();
            if(myVal == '' || otherVal == '' || myVal != otherVal) same=false;
          }
          myVal = jQuery(Row).find('[name$=occAttr\\:'+attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1]+'],[name*=occAttr\\:'+attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1]+'\\:]').val();
          otherVal = jQuery(sameSpeciesRow).find('[name$=occAttr\\:'+attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1]+'],[name*=occAttr\\:'+attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1]+'\\:]').val();
          // where all the other parents in the relationships are the same on this row, and the value is not empty
          // and we have changed a value in a row (ie myParentRow), then that row is the one that gets reset if a duplicate row is created.
          // ie myParentRow is one that will have option removed, not Row
          if(same && (myVal!=otherVal || myParentRow[0]!=sameSpeciesRow)){
            if(otherVal!='')
              childOptions.filter('[value='+otherVal+']').attr('disabled','disabled');
            if(setval && otherVal == child.val())
              resetChild=true;
          }
        });
      }
    });
    if(child.val()=='' && setval && myParentRow[0]==Row) resetChild=true;
    if(resetChild) resetChildValue(child);
  });
};

   /*
     * @attrRestrictions=
24:25:231,235:232,235,236,237:234,235,236,237;
25:26:235,239,240,241:236,241:238,239,240,241;
24:27:231,243,244,245,246,247,248,249,250,251,252,253;
25:27:235,243,244,245,246,247,248,249,250,251,252,253:236,244,250,251;
26:27:240,251:239,244:242,244,251
     */
relationships = [";
    if(isset($options["attrRestrictions"]) && $options["attrRestrictions"]!=""){
      $restrictionRules = explode(';', $options["attrRestrictions"]);
      foreach($restrictionRules as $restrictionRule){
        $parts = explode(':', $restrictionRule);
        data_entry_helper::$javascript .= "{parent : ".$parts[0].",
  child : ".$parts[1].",
  values: [";
        for($i = 2; $i < count($parts); $i++){
          $values = explode(',', trim($parts[$i]));
          data_entry_helper::$javascript .= "{value : ".$values[0].", list: [\"";
          unset($values[0]);
          data_entry_helper::$javascript .= (implode("\",\"", $values))."\"]},
          ";
        }
        data_entry_helper::$javascript .= "]},";
      }
    }
    data_entry_helper::$javascript .= "
];";
    if(isset($options["attrRestrictionsProcessOrder"]) && $options["attrRestrictionsProcessOrder"]!=""){
      $attrOrder = explode(':', $options["attrRestrictionsProcessOrder"]);
      data_entry_helper::$javascript .= "
attrRestrictionsProcessOrder = [".(implode(',', $attrOrder))."];
// set up pre-existing ones.
jQuery('[name$=occAttr\\:".$attrOrder[0]."],[name*=occAttr\\:".$attrOrder[0]."\\:]').each(function(){
    set_up_relationships(".$attrOrder[1].", $(this), false);
});";
      // need to check all but last
      for($i = 0; $i < count($attrOrder)-1; $i++){
        data_entry_helper::$javascript .= "
jQuery('[name$=occAttr\\:".$attrOrder[$i]."],[name*=occAttr\\:".$attrOrder[$i]."\\:]').live('change',
  function(){
    set_up_relationships(".$attrOrder[$i+1].", $(this), true);
  });";
      }
      // last is special - only updates similar on other rows.
      data_entry_helper::$javascript .= "
jQuery('[name$=occAttr\\:".$attrOrder[count($attrOrder)-1]."],[name*=occAttr\\:".$attrOrder[count($attrOrder)-1]."\\:]').live('change',
  function(){
    var parent = $(this).closest('tr').find('[name$=occAttr\\:".$attrOrder[count($attrOrder)-2]."],[name*=occAttr\\:".$attrOrder[count($attrOrder)-2]."\\:]');
    set_up_relationships(".$attrOrder[count($attrOrder)-1].", parent, true);
  });";
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
hook_check_no_obs=function() {
  if(jQuery('.scPresence').filter(':checkbox').filter('[checked]').length==0)
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').removeAttr('disabled');
  else
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').filter(':checkbox').attr('disabled','disabled').removeAttr('checked');
};
hook_check_no_obs();
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
          } else if($rule[$i]=='no_record'){
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').rules('add', {no_record: true});
$.validator.addMethod('no_record', function(arg1, elem){
  // validation for radio buttons only called for selected one.
  var numRows = jQuery('.scPresence').filter(':checkbox').filter('[checked]').length;
  var rbutton = jQuery('[name='+jQuery(elem).attr('name')+']').eq(0).filter('[checked]').length>0;
  if((numRows>0 && rbutton)||(numRows==0 && !rbutton)) return true;
  return false;
},
  \"".lang::get('validation_no_record')."\");
";
          } else if(substr($rule[0], 3, 4)!= 'Attr'){ // have to add for non attribute case.
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').addClass('".$rule[$i]."');";
          }
    }
    
    return '';
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

  protected static function get_control_lateJS($auth, $args, $tabalias, $options) {
   return iform_mnhnl_locationmodule_lateJS($auth, $args, $tabalias, $options);
  }
  
  protected static function getSampleListGridPreamble() {
    global $user;
    $r = '<p>'.lang::get('LANG_SampleListGrid_Preamble').(iform_loctools_checkaccess(parent::$node,'superuser') ? lang::get('LANG_All_Users') : $user->name).'</p>';
    return $r;
  }
  
  protected static function get_control_targetspeciesgrid($auth, $args, $tabalias, $options) {
    $targetSpeciesAttr=iform_mnhnl_getAttr($auth, $args, 'sample', $args['targetSpeciesAttr']);
    if (!$targetSpeciesAttr) return lang::get('The Target Species Grid control must be used with a survey that has the '.$args['targetSpeciesAttr'].' attribute associated with it.');
    // the target species grid is based on a grouping of samples determined by the
    // 1) the termlist id of the list of target species: argument targetSpeciesTermList
    // 2) a default set of attributes to be loaded: visit, Unsuitablity
    // 3) Overrides for specific target species: Common wall disabled second survey
    $termlist = $targetSpeciesAttr["termlist_id"];
    $extraParams = $auth['read'] + array('termlist_id' => $termlist, 'view'=>'detail');
    $targetSpecies = data_entry_helper::get_population_data(array('table' => 'termlists_term', 'extraParams' => $extraParams));
    $smpAttributes = data_entry_helper::getAttributes(array(
       'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'{MyPrefix}:smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
    ), true);
    $retval = '<br /><table class="target-species-grid"><tr><th colspan=2>'.lang::get('Target Species').'</th>';
    $attrList = explode(',', $args['targetSpeciesAttrList']);
    $attrIDs = array();
    foreach($attrList as $attr){
      $cell = "";
      // $retval .= '<th></th>'; // blank headings: will put captions in table itself.
      if(is_numeric($attr)) {
        $cell = $smpAttributes[intval($attr)]['caption'];
        $attrIDs[] = intval($attr);
      } else {
        foreach($smpAttributes as $id=>$sattr){
          if($attr == $sattr['untranslatedCaption']){
            $cell = $sattr['caption'];
            $attrIDs[] = $id;
          }
        }
        if($cell=="")
          $retval = lang::get('The configuration of the Target Species Grid includes a '.$attr.' samples attribute, which is not associated with this survey.').'<br/>'.$retval;
      }
      if(!isset($options['useCaptionsInHeader'])) $cell="";
      $retval .= '<th class="targ-grid-cell">'.$cell.'</th>';
    }
    $retval .= '</tr>';
    if(isset($options['useCaptionsInHeader']))
      foreach($smpAttributes as $id=>$sattr)
        unset($smpAttributes[$id]['caption']);
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
      }
    }
    // targ:sampleID:termlist_meaning_id:presence|smpAttr:attrdetails.
    foreach($targetSpecies as $target){
      $smpID=false;
      $fieldname = '{MyPrefix}:presence:'.$targetSpeciesAttr["attributeId"];
      $present='';
      $attrOpts = array('lookUpKey'=>'meaning_id',
                        'extraParams' => $auth['read'],
                        'language' => iform_lang_iso_639_2($args['language']),
                        'disabled'=>'disabled');
      foreach($subSamples as $subSample){
        foreach($subSamplesAttrs[$subSample['id']] as $id=>$attr) {
          if(isset($options['useCaptionsInHeader']))
            unset($subSamplesAttrs[$subSample['id']][$id]['caption']);
          if($attr['attributeId'] == $targetSpeciesAttr["attributeId"] && $attr['default'] == $target['meaning_id']) {
            $smpID=$subSample['id'];
            $fieldname = str_replace('smpAttr','presence',$attr["fieldname"]);
            $present=" checked=\"checked\" ";
            unset($attrOpts['disabled']);
          }
        }
      }
      $fieldprefix='targ:'.($smpID ? $smpID : '-').':'.$target['meaning_id'];
      $retval .= str_replace('{MyPrefix}',$fieldprefix,'<tr><td>'.$target['term'].'</td><td><input type="hidden" name="'.$fieldname.'" class="targ-presence" value=0><input type="checkbox" class="targ-presence" name="'.$fieldname.'" value=1 '.$present.'></td>');
      foreach($attrIDs as $attrID){
        $retval .= str_replace('{MyPrefix}',$fieldprefix, 
              '<td class="targ-grid-cell">'.data_entry_helper::outputAttribute(($smpID ? $subSamplesAttrs[$smpID][$attrID] : $smpAttributes[$attrID]),
                $attrOpts).'</td>');
      }
      $retval .= '</tr>';
    }
    $retval .= '</table><br />';
    data_entry_helper::$javascript .= "// JS for target species grid control.
jQuery('.targ-presence').change(function(){
  var myTR = jQuery(this).closest('tr');
  if(jQuery(this).filter('[checked]').length>0) {
    myTR.find('input').filter('[name*=\\:smpAttr\\:]').removeAttr('disabled');
    myTR.find('select').removeAttr('disabled').addClass('required').after('<span class=\"deh-required\">*</span>');
  } else {
    myTR.find('.deh-required,.inline-error').remove();
    myTR.find('.required').removeClass('ui-state-error required');
    myTR.find('input').filter('[name*=\\:smpAttr\\:]').attr('disabled','disabled').removeAttr('checked');
    myTR.find('select').attr('disabled','disabled').val('');
  }
});";
    if(isset($options['disableOptions'])){
      $disableControls = explode(';', $options['disableOptions']);
      foreach($disableControls as $disableControl){
        $disableList = explode(',', $disableControl);
        data_entry_helper::$javascript .= "\njQuery('.target-species-grid').find('[name*=\\:".$disableList[0]."\\:smpAttr\\:]').find('option').filter('";
        for($i=1; $i<count($disableList); $i++)
          data_entry_helper::$javascript .= ($i>1?',':'')."[value=".$disableList[$i]."]";
        data_entry_helper::$javascript .= "').attr('disabled','disabled');\n";
      }
    }    
    data_entry_helper::$late_javascript .= "// JS for target species grid control.
$.validator.addMethod('targ-presence', function(value, element){
	return jQuery('.targ-presence').filter('[checked]').length > 0;
},
  \"".lang::get('validation_targ-presence')."\");
";
    return $retval;
  }
  
  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    data_entry_helper::$javascript .= "
// Main table existing entries
speciesRows = jQuery('.mnhnl-species-grid > tbody').find('tr');
for(var j=0; j<speciesRows.length; j++){
	occAttrs = jQuery(speciesRows[j]).find('.scOccAttrCell');
	occAttrs.find('.scCount').addClass('required').attr('min',1).after('<span class=\"deh-required\">*</span>');
	occAttrs.find('select').not('.scUnits').addClass('required').width('auto').after('<span class=\"deh-required\">*</span>');
	occAttrs.find('.scUnits').width('auto');
}
hook_species_checklist_pre_delete_row=function(e) {
    return confirm(\"".lang::get('Are you sure you want to delete this row?')."\");
};
";
  	$extraParams = $auth['read'];
    // we want all languages, so dont filter
    // multiple species being input via a grid      
    $species_ctrl_opts=array_merge(array(
          "extra_list_id"=>$args["extra_list_id"],
          'listId'=>$args['list_id'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'occurrenceComment'=>$args['occurrence_comment'],
          'occurrenceImages'=>$args['occurrence_images'],
          'PHPtaxonLabel' => true
    ), $options);
    if ($args['extra_list_id']) $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths']=explode(',', $args['col_widths']);
    call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args);
    // Start by outputting a hidden value that tells us we are using a grid when the data is posted,
    // then output the grid control
    return '<input type="hidden" value="true" name="gridmode" />'.
          self::mnhnl_reptiles_species_checklist($species_ctrl_opts);
  }
  

  public static function mnhnl_reptiles_species_checklist()
  {
  	global $indicia_templates;
    $options = data_entry_helper::check_arguments(func_get_args(), array('listId', 'occAttrs', 'readAuth', 'extraParams', 'lookupListId'));
    $options = data_entry_helper::get_species_checklist_options($options);
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    $occAttrControls = array();
    $occAttrs = array();
    // Load any existing sample's occurrence data into $entity_to_load
    $subSamples = array();
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      data_entry_helper::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'], $options['readAuth'], false, array(), $subSamples, false);
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
      data_entry_helper::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $grid = "<p>".lang::get('LANG_SpeciesInstructions')."</p>\n";
      if (isset($options['lookupListId'])) {
        $grid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $grid .= '<table class="ui-widget ui-widget-content mnhnl-species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $grid .= self::get_species_checklist_header($options, $occAttrs);
      $rows = array();
      $rowIdx = 0;
      foreach ($occList as $occ) {
        $ttlid = $occ['taxon']['id'];
        $firstCell = data_entry_helper::mergeParamsIntoTemplate($occ['taxon'], 'taxon_label', false, true);
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
        $secondrow = "<td class=\"scPresenceCell\"$hidden>".($options['rowInclusionCheck']!='hasData' ? "<input type=\"hidden\" class=\"scPresence\" name=\"sc:$ttlid:$existing_record_id:present\" value=\"0\"/><input type=\"checkbox\" class=\"scPresence\" name=\"sc:$ttlid:$existing_record_id:present\" $checked />" : '')."</td>";
        foreach ($occAttrControls as $attrId => $control) {
          if ($existing_record_id) {
            $search = preg_grep("/^sc:".$ttlid."[_[0-9]*]?:$existing_record_id:occAttr:$attrId".'[:[0-9]*]?$/', array_keys(data_entry_helper::$entity_to_load));
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
          $secondrow .= str_replace(array('{label}', '{content}'), array(lang::get($attributes[$attrId]['caption']), $oc), $indicia_templates[$options['attrCellTemplate']]);
        }
        $thirdrow = "";
        if ($options['occurrenceComment']) {
          $thirdrow .= "\n<td class=\"ui-widget-content scCommentCell\" $colspan><label for=\"sc:$ttlid:$existing_record_id:occurrence:comment\" class=\"auto-width\" >".lang::get("Comment")." : </label><input class=\"scComment\" type=\"text\" name=\"sc:$ttlid:$existing_record_id:occurrence:comment\" ".
          "id=\"sc:$ttlid:$existing_record_id:occurrence:comment\" value=\"".htmlspecialchars(data_entry_helper::$entity_to_load["sc:$ttlid:$existing_record_id:occurrence:comment"])."\" /></td>";
        }
        $rows[]='<tr>'.$firstrow.'</tr>';
        $rows[]='<tr class="scMeaning-'.$occ['taxon']['taxon_meaning_id'].' scDataRow">'.$secondrow.'</tr>'; // no images.
        if($thirdrow != "") 
          $rows[]='<tr class="scMeaning-'.$occ['taxon']['taxon_meaning_id'].' scDataRow">'.$thirdrow.'</tr>'; // no images.
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
        $grid .= "<label for=\"taxonLookupControl\" class=\"auto-width\">".lang::get('Add species to list')." : </label> <input id=\"taxonLookupControl\" name=\"taxonLookupControl\" >";
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
            $options['readAuth']['auth_token']."\", \"nonce\" : \"".$options['readAuth']['nonce']."\"}, formatter);
";
      }
      // No help text
      return $grid;
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
  private static function get_species_checklist_clonable_row($options, $occAttrControls, $attributes) {
    global $indicia_templates;
    // assume always removeable and presence is hidden.
    $r = '<table style="display: none"><tbody><tr class="scClonableRow" id="'.$options['id'].'-scClonableRow1"><td class="ui-state-default remove-row" style="width: 1%" rowspan="'.($options['occurrenceComment']?"3":"2").'">X</td>';
    $colspan = ' colspan="'.count($attributes).'"';
    $r .= str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']).'</tr><tr class="scClonableRow scDataRow" id="'.$options['id'].'-scClonableRow2">';
    $r .= '<td class="scPresenceCell" style="display:none"><input type="checkbox" class="scPresence" name="" value="" /></td>';
    $idx = 0;
    foreach ($occAttrControls as $attrId=>$oc) {
      $class = self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['untranslatedCaption']);
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
      $r .= "</tr>
  <tr class=\"scClonableRow scDataRow\" id=\"".$options['id']."-scClonableRow3\">
    <td class=\"ui-widget-content scCommentCell\" ".$colspan.">
      <label for=\"sc:-ttlId-::occurrence:comment\" class=\"auto-width\" >".lang::get("Comment")." : </label>
      <input class=\"scComment\" type=\"text\" id=\"sc:-ttlId-::occurrence:comment\" name=\"sc:-ttlId-::occurrence:comment\" />
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
  
  /**
   * Retrieve the grid header row for the species checklist grid control.
   * @param array $options Control options array.
   * @param array $occAttrs Array of custom attributes included in the grid.
   * @return string Html for the <thead> element.
   */
  private static function get_species_checklist_header($options, $occAttrs) {
    $r = '';
    $visibleColIdx = 0;
    if ($options['header']) {
      $r .= "<thead class=\"ui-widget-header\"><tr>";
      for ($i=0; $i<$options['columns']; $i++) {
      	// assume always removeable
        $r .= '<th></th>';
        $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
        $r .= self::get_species_checklist_col_header(lang::get('species_checklist.present'), $visibleColIdx, $options['colWidths'], $hidden);
        foreach ($occAttrs as $a) {
          $r .= self::get_species_checklist_col_header($a, $visibleColIdx, $options['colWidths']) ;
        }
        // Comment on its own line
      }
      $r .= '</tr></thead>';
      return $r;
    }
  }

  private static function get_species_checklist_col_header($caption, &$colIdx, $colWidths, $attrs='') {
    $width = count($colWidths)>$colIdx && $colWidths[$colIdx] ? ' style="width: '.$colWidths[$colIdx].'%;"' : '';
    if (!strpos($attrs, 'display:none')) $colIdx++;
    return "<th$attrs$width>".$caption."</th>";
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
          $pos = strpos($parts[1], '_');
          $txID = ($pos === false) ? $parts[1] : substr($parts[1], 0, $pos); 
          foreach($fullTaxalist as $taxon){
            if($txID == $taxon['id']){
              $occ['taxon'] = $taxon;
              $taxaLoaded[] = $txID;
            }
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
    if(!isset($values['sample:deleted'])) {
      if (isset($values['gridmode']))
        $subModels = self::wrap_species_checklist($values);
      else
        $subModels = submission_builder::wrap_with_images($values, 'occurrence');
      foreach($values as $key => $value){
        $parts = explode(':', $key, 5);
        if ($parts[0] == 'targ' && $parts[3] == 'presence'){
          $smp = array('fkId' => 'parent_id', 'model' => array('id' => 'sample', 'fields' => array()));
          $smp['model']['fields']['survey_id'] = array('value' => $values['survey_id']);
          $smp['model']['fields']['website_id'] = array('value' => $values['website_id']);
          $smp['model']['fields']['date'] = array('value' => $values['sample:date']);
          $smp['model']['fields']['smpAttr:'.$parts[4]] = array('value' => $parts[2]);
          if(isset($values['sample:location_id']))
            $smp['model']['fields']['location_id'] = array('value' => $values['sample:location_id']);
          else if(isset($values['location:parent_id']))
            $smp['model']['fields']['location_id'] = array('value' => $values['location:parent_id']);
          else {
            $smp['model']['fields']['geom'] = array('value' => $values['location:centroid_geom']);
            $smp['model']['fields']['entered_sref'] = array('value' => $values['location:centroid_sref']);
            $smp['model']['fields']['entered_sref_system'] = array('value' => $values['location:centroid_sref_system']);
          }
          if($value != '1') $smp['model']['fields']['deleted'] = array('value' => 't');
          if($parts[1] != '-') $smp['model']['fields']['id'] = array('value' => $parts[1]);
          foreach($values as $key1 => $value1){
            $moreParts = explode(':', $key1, 5);
            if ($moreParts[0] == 'targ' && $moreParts[1] == $parts[1] && $moreParts[2] == $parts[2] && $moreParts[3]== 'smpAttr'){
              $smp['model']['fields']['smpAttr:'.$moreParts[4]] = array('value' => $value1);
            }
          }
          if(isset($values['sample:location_id'])) $smp['model']['fields']['location_id'] = array('value' => $values['sample:location_id']);
          else {
            $smp['model']['fields']['centroid_sref'] = array('value' => $values['sample:entered_sref']);
            $smp['model']['fields']['centroid_sref_system'] = array('value' => $values['sample:entered_sref_system']);
            $smp['model']['fields']['centroid_geom'] = array('value' => $values['sample:geom']);
          }
          if($value == '1' || $parts[1] != '-') $subModels[]=$smp;
        }
      }
      if(count($subModels)>0)
        $sampleMod['subModels'] = $subModels;
      if (isset($values['location:location_type_id'])){
        $locationMod = submission_builder::wrap_with_images($values, 'location');
        $locationMod['subModels'] = array(array('fkId' => 'location_id', 'model' => $sampleMod));
        if(array_key_exists('locations_website:website_id', $_POST)){
          $lw = submission_builder::wrap_with_images($values, 'locations_website');
          $locationMod['subModels'][] = array('fkId' => 'location_id', 'model' => $lw);
        }
        return $locationMod;
      }
    } else if (isset($values['location:deleted'])){
      $locationMod = submission_builder::wrap_with_images($values, 'location');
      $locationMod['subModels'] = array(array('fkId' => 'location_id', 'model' => $sampleMod));
      return $locationMod;
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
          $pos = strpos($a[1], '_');
          $records[$a[2]]['taxa_taxon_list_id'] = ($pos === false) ? $a[1] : substr($a[1], 0, $pos);
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
  
  protected static function getReportActions() {
    return array(array('display' => '', 'actions' => 
            array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))),
        array('display' => '', 'actions' => 
            array(array('caption' => lang::get('Delete'), 'javascript'=>'deleteSurvey({sample_id})'))));
  }
  
  /**
   * Build a PHP function  to format the species added to the grid according to the form parameters
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
        '    "nonce"=>"'.parent::$auth['read']['nonce'].'"),'."\n".
        '  "table"=>"taxa_taxon_list");'."\n".
        '$responseRecords = data_entry_helper::get_population_data($taxa_list_args);'."\n".
        '$taxaList = "";'."\n".
        '$taxaMeaning = -1;'."\n".
        'foreach ($responseRecords as $record)'."\n".
        '  if($record["id"] == {id}) $taxaMeaning=$record["taxon_meaning_id"];'."\n".
        'foreach ($responseRecords as $record){'."\n".
        '  if($record["id"] != {id} && $taxaMeaning==$record["taxon_meaning_id"] && $record["taxon_list_id"]=="'.$args['extra_list_id'].'"){'."\n".
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
  
}