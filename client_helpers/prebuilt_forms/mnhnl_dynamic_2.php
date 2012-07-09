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
 * Stage 2 get subsamples going.
 * In tabs mode, Prevent display of species tab until location provided for main sample.
 * default coords to parent
 * Zoom to parent if no child at the moment, when selecting a row in the species grid.
 * Optionally add listlayer label: taxon and count.
 * add possiblity that parent has a location.
 * Add ability to show previously store samples/occurrences
 * 
 * Known Bugs
 * The count on the species grid is flagged required but isn't
 * 
 * Nice to haves
 * Buttons in Params Form to configure the form for Reptiles, Amphibiansx2, Dormice.
 * Extend Species grid - optional date: sort datepicker & ID
 * Sample attributes in species grid.
 */
require_once('mnhnl_dynamic_1.php');
require_once('includes/mnhnl_common.php');

class iform_mnhnl_dynamic_2 extends iform_mnhnl_dynamic_1 {

  protected static $svcUrl;
  protected static $currentUrl;
  protected static $gridmode;
  protected static $node;
  
  /*
   * First all the API stuff for bolting into the IForm Module.
   */
  
   /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_mnhnl_dynamic_2_definition() {
    return array(
      'title'=>'MNHNL Dynamic 2 - dynamically generated form for entry of a series of ad-hoc occurrences',
      'category' => 'General Purpose Data Entry Forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description'=>'A data entry form that is dynamically generated from the survey\'s attributes. The form lets the user create '.
          'a series of occurrences by clicking on the map to set the location of each one then entering details. Data entered in a '.
          'single session in this way is joined using a simple sample hierarchy (so the top level sample encapsulates all data for the '.
          'session).'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
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
          'description'=>'Name of lookup type Sample Attribute used to hold the target species. This is used in the reporting and control.',
          'type'=>'text_input',
          'group' => 'User Interface',
          'required' => false
        ),
        array(
          'name' => 'language',
          'caption' => 'Language Override',
          'description' => 'Two digit language override.',
          'type' => 'string',
          'required' => true,
          'default' => 'en',
          'group' => 'User Interface'
        ),
        array(
          'name'=>'communeLayerLookup',
          'caption'=>'WFS Layer specification for Commune Lookup',
          'description'=>'Comma separated: proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames',
          'type'=>'string',
          'required' => false,
          'group'=>'Locations',
        ),
        array(
          'name'=>'locationLayerLookup',
          'caption'=>'WFS Layer specification for Locations Lookup',
          'description'=>'Comma separated: proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames',
          'type'=>'string',
          'required' => false,
          'group'=>'Locations',
        ),
        
        array(
          'name' => 'reportFilenamePrefix',
          'caption' => 'Report Filename Prefix',
          'description' => 'Prefix to be used at the start of the download report filenames.',
          'type' => 'string',
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
        )
      )
    );
    $retVal=array();
    foreach($parentVal as $param){
      if($param['name'] == 'grid_report'){
        $param['description'] = 'Name of the report to use to populate the grid for selecting existing data. The report must return a sample_id '.
              'field for linking to the data entry form.';
        $param['default'] = 'reports_for_prebuilt_forms/mnhnl_dynamic_2_supersamples';
      }
      if($param['name'] == 'interface')
        $param['options'] = array('tabs' => 'Tabs', 'wizard' => 'Wizard'); // No one_page
      if($param['name'] != 'no_grid' &&
          $param['name'] != 'occurrence_comment' &&
          $param['name'] != 'occurrence_confidential' &&
          $param['name'] != 'occurrence_images' &&
          $param['name'] != 'multiple_occurrence_mode' &&
          $param['fieldname'] != 'list_id' &&
          $param['fieldname'] != 'extra_list_id' &&
          $param['fieldname'] != 'cache_lookup' &&
          $param['fieldname'] != 'user_controls_taxon_filter')
        $retVal[] = $param;
      // Note the includeLocTools is left in in case any child forms use it 
    }
    return $retVal;
  }
  
  
  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   * 
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mnhnl_dynamic_2.css');
  }

  /*
   * Then the functions that bolt into any Dynamic 1 functionality
   */
  protected static function enforcePermissions(){
  	return true;
  }
  
  /*
   * Next the functions which relate to the main front page.
   */
  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    if(!user_access('IForm n'.self::$node->nid.' admin')) return('');
    if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'), '#locations' => lang::get('LANG_Locations'));
    if($args['LocationTypeTerm']=='' && isset($args['loctoolsLocTypeID'])) $args['LocationTypeTerm']=$args['loctoolsLocTypeID'];
    $primary = iform_mnhnl_getTermID(array('read'=>$readAuth), $args['locationTypeTermListExtKey'],$args['LocationTypeTerm']);
    $r= '<div id="downloads" >';
    if(isset($args['targetSpeciesAttr']) && $args['targetSpeciesAttr']!="") {
      $targetSpeciesAttr=iform_mnhnl_getAttr(self::$auth, $args, 'sample', $args['targetSpeciesAttr']);
      if(!$targetSpeciesAttr) return lang::get('This form must be used with a survey that has the '.$args['targetSpeciesAttr'].' sample attribute associated with it.');
      data_entry_helper::$javascript .= "
jQuery('[name=targetSpecies]').change(function(){
  jQuery('[name=params]').val('{\"survey_id\":".$args['survey_id'].", \"location_type_id\":".$primary.", \"target_species\":'+jQuery(this).val()+'}');
  var filename=jQuery(this).find('[selected]')[0].text.replace(/ /g, \"\");
  jQuery('#sitesReportRequestForm').attr('action',
    '".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=".$args['sites_download_report'].".xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv&filename=".$args['reportFilenamePrefix']."Sites');
  jQuery('#conditionsReportRequestForm').attr('action',
    '".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=".$args['conditions_download_report'].".xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv&filename=".$args['reportFilenamePrefix']."Conditions'+filename);
  jQuery('#speciesReportRequestForm').attr('action',
    '".data_entry_helper::$base_url."/index.php/services/report/requestReport?report=".$args['species_download_report'].".xml&reportSource=local&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce']."&mode=csv&filename=".$args['reportFilenamePrefix']."Species'+filename);
});
jQuery('[name=targetSpecies]').change();
";
      $r .= '<p>'.lang::get('LANG_Data_Download_TS').'</p>'.data_entry_helper::select(array(
          'label'=>lang::get("LANG_TargetSpecies"),
          'fieldname'=>'targetSpecies',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'meaning_id',
          'extraParams' => $readAuth + array('view'=>'detail', 'termlist_id'=>$targetSpeciesAttr['termlist_id'], 'orderby'=>'id')
        ));
    } else
      $r .= '<p>'.lang::get('LANG_Data_Download').'</p>';
    return $r.($args['sites_download_report']!=''?'
  <form id="sitesReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['sites_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Sites">
    <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "location_type_id":'.$primary.'}\' />
    <label>'.lang::get('Sites report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
  </form>':'').($args['conditions_download_report']!=''?'
  <form id="conditionsReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['conditions_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Conditions">
    <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "location_type_id":'.$primary.'}\' />
    <label>'.lang::get('Conditions report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
  </form>':'').'
  <form id="speciesReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['species_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Species">
    <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "location_type_id":'.$primary.'}\' />
    <label>'.lang::get('Species report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
  </form>
</div>'.iform_mnhnl_locModTool(self::$auth, $args, self::$node);
  }
  /*
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
  	global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    $userIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS User ID');
    if (!$userIdAttr) return lang::get('This form must be used with a survey that has the CMS User ID sample attribute associated with it, so records can be tagged against their creator.');
    $extraParams = array('survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>(user_access('IForm n'.self::$node->nid.' admin') ? -1 :  $user->uid)); // use -1 if admin - non logged in will not get this far.
    $userNameAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS Username');
    if ($userNameAttr) $extraParams['userName_attr_id']=$userNameAttr;
    if(isset($args['targetSpeciesAttr']) && $args['targetSpeciesAttr']!="") {
      $targetSpeciesIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', $args['targetSpeciesAttr']);
      $extraParams['target_species_attr_id']=$targetSpeciesIdAttr;
    }
    if(isset($args['filterAttrs']) && $args['filterAttrs']!=''){
      global $custom_terms;
      $filterAttrs = explode(',',$args['filterAttrs']);
      $idxN=1;
      foreach($filterAttrs as $idx=>$filterAttr){
        $filterAttr=explode(':',$filterAttr);
        switch($filterAttr[0]){
        	case 'Parent':
              $custom_terms['location name']=lang::get('location name');
              break;
            case 'Shape':
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
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 25),
      'autoParamsForm' => true,
      'extraParams' => $extraParams));	
    $r .= '<form>';    
    $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    $r .= '</form>
<div style="display:none" />
    <form id="form-delete-survey" action="'.iform_mnhnl_getReloadPath().'" method="POST">'.$auth['write'].'
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
  
  protected static function getSampleListGridPreamble() {
    global $user;
    $r = '<p>'.lang::get('LANG_SampleListGrid_Preamble').(user_access('IForm n'.self::$node->nid.' admin') ? lang::get('LANG_All_Users') : $user->name).'</p>';
    return $r;
  }
  protected function getReportActions() {
    return array(array('display' => '', 'actions' => 
            array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))),
        array('display' => '', 'actions' => 
            array(array('caption' => lang::get('Delete'), 'javascript'=>'deleteSurvey({sample_id})'))));
  }
  
  /* 
   * And now all the controls.
   * 
   * Controls inherited from Dynamic 1
   * Map
   * Sample Comment
   * Species Attributes
   * Date
   * Spatial Reference
   * Location autocomplete
   * Location Select
   * Location Name
   * Place Search
   * REcorder Names
   * Record Status
   * 
   * Note the Dynamic 2 Species control is overridden by the local one.
   */
  protected static function get_control_locationmodule($auth, $args, $tabalias, $options) {
    $ret = iform_mnhnl_lux5kgridControl($auth, $args, self::$node,
      array_merge(array('initLoadArgs' => '{}'), $options));
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
  protected static function get_control_pickrecordernames($auth, $args, $tabalias, $options) {
    return iform_mnhnl_recordernamesControl(self::$node, $auth, $args, $tabalias, $options);
  }
  protected static function get_control_locationcomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'location:comment',
      'label'=>lang::get('Location Comment')
    ), $options)); 
  }
  protected static function get_control_lateJS($auth, $args, $tabalias, $options) {
   return iform_mnhnl_locationmodule_lateJS($auth, $args, $tabalias, $options);
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
    // no duplicate check as samples will be in different places. TBD reinstate for non includeSubSample
/*    var classList = jQuery(Row).attr('class').split(/\s+/);
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
    }); */
    if(child.val()=='' && setval && myParentRow[0]==Row) resetChild=true;
    if(resetChild) resetChildValue(child);
  });
};

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
hook_species_grid_changed=function() {
  if(jQuery('.scPresence').filter('[value=true]').length==0)
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').removeAttr('disabled');
  else
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').filter(':checkbox').attr('disabled','disabled').removeAttr('checked');
};
hook_species_grid_changed();
$.validator.addMethod('no_observation', function(arg1, arg2){
  var numRows = jQuery('.scPresence').filter('[value=true]').length;
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
    return '';
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
    $retval = '<br /><input type="hidden" name="includeTargetSpeciesGrid" value="true" ><table class="target-species-grid"><tr><th colspan=2>'.lang::get('Target Species').'</th>';
    $attrList = explode(',', $options['defaultAttrs']);
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
    $first=true;
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
      $retval .= str_replace('{MyPrefix}',$fieldprefix,'<tr><td>'.$target['term'].'</td><td><input type="hidden" name="'.$fieldname.'" value=0><input type="checkbox" class="targ-presence" name="'.$fieldname.'" value=1 '.$present.'></td>');
      foreach($attrIDs as $attrID){
        $retval .= str_replace('{MyPrefix}',$fieldprefix, 
              '<td class="targ-grid-cell">'.data_entry_helper::outputAttribute(($smpID ? $subSamplesAttrs[$smpID][$attrID] : $smpAttributes[$attrID]),
                $attrOpts).'</td>');
      }
      $retval .= '</tr>';
      $first=false;
    }
    $retval .= '</table><br />';
    data_entry_helper::$javascript .= "// JS for target species grid control.
jQuery('.targ-presence').change(function(){
  var myTR = jQuery(this).closest('tr');
  if(jQuery(this).filter('[checked]').length>0) {
    // remove all existing errors in the grid.
    jQuery(this).closest('table').find('.inline-error').remove();
    jQuery(this).closest('table').find('.ui-state-error').removeClass('ui-state-error');
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
	return jQuery('.targ-presence')[0]!=element || jQuery('.targ-presence').filter('[checked]').length > 0;
},
  \"".lang::get('validation_targ-presence')."\");
";
    return $retval;
  }
  
/*
  Whole thing is based on Dynamic_1, but the submission array is more complicated.
  TBD: zoom to session location, also includes display of all occurrences so far.
  Assume grid based input: TBD remove this option.
  No confidential and no images.
  
  TBD add switch to allow X/Y to be optional: initially mandatory
  TBD add switch to enable field for date: initially not available.
  */
  /**
   * Get the control for species input, either a grid or a single species input control.
   * User Interface options:
   * speciesListID
   * mapPosition = [top|side]
   * maxSpeciesIDs = The maximum number of taxa to be returned by the autocomplete drop down at any one time: OPTIONAL, default 25
   * includeOccurrenceComment = boolean to determine if occurrence_comments are included in the species grid, on a separate line
   */
  protected static function get_control_species($auth, $args, $tabalias, $options) {
    data_entry_helper::$javascript .= "
// Main table existing entries
speciesRows = jQuery('.species-grid > tbody').find('tr');
for(var j=0; j<speciesRows.length; j++){
	occAttrs = jQuery(speciesRows[j]).find('.scOccAttrCell');
	occAttrs.find('.scCount,.scNumber').addClass('required').attr('min',1).after('<span class=\"deh-required\">*</span>');
	occAttrs.find('select').not('.scUnits').addClass('required').width('auto').after('<span class=\"deh-required\">*</span>');
	occAttrs.find('.scUnits').width('auto');
}
hook_species_checklist_pre_delete_row=function(e) {
    return confirm(\"".lang::get('Are you sure you want to delete this row?')."\");
};
";
    $extraParams = $auth['read'];
    // multiple species being input via a grid
    $myLanguage = iform_lang_iso_639_2($args['language']);
    $species_ctrl_opts=array_merge(array(
          'speciesListID'=>$args['speciesListID'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'readAuth'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'includeSubSample'=>isset($options['includeSubSample']),
          'separateCells'=>isset($options['separateCells']),
          'useCaptionsInHeader'=>isset($options['useCaptionsInHeader']) && $options['useCaptionsInHeader']=='true',
          'includeOccurrenceComment'=>isset($options['includeOccurrenceComment']) && $options['includeOccurrenceComment']=='true',
          'PHPtaxonLabel' => true,
          'language' => $myLanguage,
          'args'=>$args
    ), $options);
    $species_ctrl_opts['mapPosition'] = (isset($options['mapPosition']) ? $options['mapPosition'] : 'top');
    call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args);
    return self::species_checklist($species_ctrl_opts);
  }

  /**
   * Build a PHP function  to format the autocomplete item list.
   * This puts the choosen one first, folowed by ones with the same meaning, led by the preferred one.
   * This differs from the JS formatter function, which puts preferred first.
   */
  protected static function build_grid_taxon_label_function($args) {
    global $indicia_templates;
    // always include the searched name
    $php = '$taxa_list_args=array('."\n".
        '  "extraParams"=>array("website_id"=>'.$args['website_id'].','."\n".
        '    "view"=>"detail",'."\n".
        '    "auth_token"=>"'.self::$auth['read']['auth_token'].'",'."\n".
        '    "nonce"=>"'.self::$auth['read']['nonce'].'"),'."\n".
        '  "table"=>"taxa_taxon_list");'."\n".
        '$responseRecords = data_entry_helper::get_population_data($taxa_list_args);'."\n".
        '$taxaList = "";'."\n".
        '$taxaMeaning = -1;'."\n".
        'foreach ($responseRecords as $record)'."\n".
        '  if($record["id"] == {id}){'."\n".
        '    $taxaMeaning=$record["taxon_meaning_id"];'."\n".
        '  }'."\n".
        '$taxaList=array();'."\n".
        'foreach ($responseRecords as $record){'."\n".
        '  if($taxaMeaning==$record["taxon_meaning_id"]){'."\n".
        '    if($record["preferred"] == "f"){'."\n".
        '      if(!in_array($record["taxon"], $taxaList)) {$taxaList[] = $record["taxon"];}'."\n".
        '    } else {'."\n".
        '      if(!in_array("<em>".$record["taxon"]."</em>", $taxaList)) {array_unshift($taxaList, "<em>".$record["taxon"]."</em>");}'."\n".
        '}}}'."\n".
        '$r = implode(", ", $taxaList);'."\n".
        'return $r;'."\n";
    // Set it into the indicia templates
    $indicia_templates['taxon_label'] = $php;
  }

  private static function _getCtrlNames(&$ctrlName, &$oldCtrlName)
  {
    $ctrlArr = explode(':',$ctrlName,6);
    // sc:<rowIdx>:<smp_id>:<ttlid>:<occ_id>:[field]";
    // NB this does not cope with multivalues.
    if ($ctrlArr[4]!="") {
      $search = preg_grep("/^sc:".'[0-9]*'.":$ctrlArr[2]:$ctrlArr[3]:$ctrlArr[4]:$ctrlArr[5]".'[:[0-9]*]?$/', array_keys(data_entry_helper::$entity_to_load));
      var_dump($search);
      if(count($search)===1){
        $oldCtrlName = implode('', $search);
        $ctrlName = explode(':',$oldCtrlName);
        $ctrlName[1]=$ctrlArr[1];
        $ctrlName = implode(':', $ctrlName);
      } else $oldCtrlName=$ctrlName;
    } else $oldCtrlName=$ctrlName;
  }

  public static function species_checklist()
  {
  	global $indicia_templates;
    $options = data_entry_helper::check_arguments(func_get_args(), array('speciesListID', 'occAttrs', 'readAuth', 'extraParams'));
    $options = self::get_species_checklist_options($options);
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    $occAttrControls = array();
    $occAttrs = array();
    // Load any existing sample's occurrence data into $entity_to_load
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      self::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'], $options['readAuth'], $options);
    // at this point we are only dealing with occurrence attributes.
    $attributes = data_entry_helper::getAttributes(array(
           'valuetable'=>'occurrence_attribute_value'
           ,'attrtable'=>'occurrence_attribute'
           ,'key'=>'occurrence_id'
           ,'fieldprefix'=>"{fieldname}"
           ,'extraParams'=>$options['readAuth']
           ,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null));
    $numRows=0;
    $maxCellsPerRow=$i=1;
    do{
      $foundRows=false;
      $attrsPerRow=0;
      foreach($attributes as $attribute){
        if($attribute["inner_structure_block"] == "Row".$i) {
          $numRows=$i;
          $foundRows=true;
          $attrsPerRow++;
        }
      }
      $i++;
      if($attrsPerRow>$maxCellsPerRow) $maxCellsPerRow=$attrsPerRow;
    } while ($foundRows);
    if($numRows) $foundRows=true;
    else {
      $numRows=1;
      $maxCellsPerRow=count($attributes);
    }
    if($options['includeSubSample'] && $maxCellsPerRow<($options['displaySampleDate']?3:2))$maxCellsPerRow=($options['displaySampleDate']?3:2);
    $options['extraParams']['view'] = 'detail';
    $options['numRows'] = 1 + /* taxon name row */
                          ($options['includeSubSample']?1:0) + /* row holding subsample location and date */
                          $numRows +
                          ($options['includeOccurrenceComment']?1:0);
    $recordList = self::get_species_checklist_record_list($options);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $recordList)) {
      $grid = "";
      // Get the attribute and control information required to build the custom occurrence attribute columns
      if($options['includeSubSample']) $grid.="<input type='hidden' name='includeSubSample' value='true' >";
      self::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $grid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      $grid .= '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $grid .= self::get_species_checklist_header($options, $occAttrs);
      $rows = array();
      $rowIdx = 1;
      /*
       * fieldnames: SC:<RowGroup>:<sampleID>:<ttlID>:<occurrenceID>:[present|sample:[date|etc]|occAttr:<attrID>[:<valueID>]]
       */
      foreach ($recordList as $rec) {
        $ttlid = $rec['taxon']['id'];
        $occ_existing_record_id = $rec['occurrence']['id'];
        $smp_existing_record_id = $options['includeSubSample'] ? $rec['sample']['id'] : '';
        $firstCell = data_entry_helper::mergeParamsIntoTemplate($rec['taxon'], 'taxon_label');
        $prefix="sc:$rowIdx:$smp_existing_record_id:$ttlid:$occ_existing_record_id";
        if ($options['PHPtaxonLabel']) $firstCell=eval($firstCell);
        $colspan = ' colspan="'.$maxCellsPerRow.'"';
        // assume always removeable and presence is hidden.
        $row = "<td class='ui-state-default remove-row' rowspan='".$options['numRows']."' >X</td>";
        $row .= str_replace('{content}', $firstCell, str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']));
        $row .= "<td class='scPresenceCell' style='display:none'><input type='hidden' class='scPresence' name='$prefix:present' value='true'/></td>";
        $rows[]='<tr class="scMeaning-'.$rec['taxon']['taxon_meaning_id'].' first scOcc-'.$occ_existing_record_id.'">'.$row.'</tr>';
        if($options['includeSubSample']){
          $row = '<tr class="scMeaning-'.$rec['taxon']['taxon_meaning_id'].' scDataRow">';
          if ($options['displaySampleDate']) {
            $ctrlID=$ctrlName=$prefix.":sample:date";
            self::_getCtrlNames($ctrlName, $oldCtrlName);
            // TBD need to attach a date control
            $row .= "<td class='ui-widget-content'><label class='auto-width' for='$ctrlID'>".lang::get('LANG_Date').":</label> <input type='text' id='$ctrlID' class='date' name='$ctrlName' value='".data_entry_helper::$entity_to_load[$oldCtrlName]."' /></td>";
          }
          $ctrlName=$prefix.":sample:geom";
          self::_getCtrlNames($ctrlName, $oldCtrlName);
          $row .= "<td class='ui-widget-content'><input type='hidden' id='$prefix:imp-geom' name='$ctrlName' value='".data_entry_helper::$entity_to_load[$oldCtrlName]."' />";
          $ctrlName=$prefix.":sample:entered_sref";
          self::_getCtrlNames($ctrlName, $oldCtrlName);
          $row .= "<input type='hidden' id='$prefix:imp-sref' name='$ctrlName' value='".data_entry_helper::$entity_to_load[$oldCtrlName]."' />";
          if(isset(data_entry_helper::$entity_to_load[$oldCtrlName]) && data_entry_helper::$entity_to_load[$oldCtrlName]!=""){
            $parts=explode(' ', data_entry_helper::$entity_to_load[$oldCtrlName]);
            $parts[0]=explode(',',$parts[0]);
            $parts[0]=$parts[0][0];
          } else $parts = array('', '');
          $row .= "<label class='auto-width' for='$prefix:imp-srefX'>".lang::get('LANG_Species_X_Label').":</label> <input type='text' id='$prefix:imp-srefX' class='imp-srefX required integer' name='dummy:srefX' value='$parts[0]' /><span class='deh-required'>*</span></td>
<td class='ui-widget-content'><label class='auto-width' for='$prefix:imp-srefY'>".lang::get('LANG_Species_Y_Label').":</label> <input type='text' id='$prefix:imp-srefY' class='imp-srefY required integer' name='dummy:srefY' value='$parts[1]'/><span class='deh-required'>*</span>
</td>";
          if($maxCellsPerRow>($options['displaySampleDate']?3:2))
            $row .= "<td class='ui-widget-content' colspan=".($maxCellsPerRow-($options['displaySampleDate']?3:2))."></td>";
          $rows[]=$row."</tr>";
        }
        for($i=1; $i<=$numRows; $i++){
          $row = "";
          $numCtrls=0;
          foreach ($attributes as $attrId => $attribute) {
            $control=$occAttrControls[$attrId];
            if($foundRows && $attribute["inner_structure_block"] != "Row".$i) continue;
            $ctrlId = $ctrlName = $prefix.":occAttr:$attrId";
            self::_getCtrlNames($ctrlName, $oldCtrlName);
            if (isset(data_entry_helper::$entity_to_load[$oldCtrlName])) {
              $existing_value = data_entry_helper::$entity_to_load[$oldCtrlName];
            } elseif (array_key_exists('default', $attributes[$attrId])) {
              $existing_value = $attributes[$attrId]['default'];
            } else $existing_value = '';
            $oc = str_replace('{fieldname}', $ctrlName, $control);
            if (!empty($existing_value)) {
              // For select controls, specify which option is selected from the existing value
              if (strpos($oc, '<select') !== false) {
                $oc = str_replace('value="'.$existing_value.'"', 'value="'.$existing_value.'" selected="selected"', $oc);
              } else if(strpos($oc, 'checkbox') !== false) {
                if($existing_value=="1") $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
              } else {
                $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
              }
              // assume all error handling/validation done client side
            }
            $numCtrls++;
            $row .= str_replace(array('{label}', '{content}'), array(lang::get($attributes[$attrId]['caption']), $oc), $indicia_templates[$options['attrCellTemplate']]);
          }
          if($maxCellsPerRow>$numCtrls) $row .= "<td class='ui-widget-content' colspan=".($maxCellsPerRow-$numCtrls)."></td>";
          // no confidential checkbox.
          $rows[]='<tr class="scMeaning-'.$rec['taxon']['taxon_meaning_id'].' scDataRow'.(count($rows)==($options['numRows']-1)?' last':'').'">'.$row.'</tr>'; // no images.
        }
        if ($options['includeOccurrenceComment']) {
          $ctrlId = $ctrlName=$prefix.":occurrence:comment";
          self::_getCtrlNames($ctrlName, $oldCtrlName);
          if (isset(data_entry_helper::$entity_to_load[$oldCtrlName])) {
            $existing_value = data_entry_helper::$entity_to_load[$oldCtrlName];
          } else $existing_value = '';
          $rows[]="<tr class='scMeaning-".$rec['taxon']['taxon_meaning_id']." scDataRow last'>
<td class='ui-widget-content scCommentCell' $colspan>
  <label for='$ctrlId' class='auto-width'>".lang::get("Comment").":</label>
  <input type='text' class='scComment' name='$ctrlName' id='$ctrlId' value='".htmlspecialchars($existing_value)."'>
</td></tr>";
        }
        $rowIdx++;
      }
      $grid .= "\n<tbody>\n";
      if (count($rows)>0) $grid .= implode("\n", $rows)."\n";
      else $grid .= "<tr style=\"display: none\"><td></td></tr>\n";
      $grid .= "</tbody>\n</table>
<label for='taxonLookupControl' class='auto-width'>".lang::get('Add species to list').":</label> <input id='taxonLookupControl' name='taxonLookupControl' >";
      // Javascript to add further rows to the grid
      data_entry_helper::$javascript .= "scRow=".$rowIdx.";
var formatter = function(rowData,taxonCell) {
  taxonCell.html(\"".lang::get('loading')."\");
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list/' + rowData.id +
            '?mode=json&view=detail&auth_token=".$options['readAuth']['auth_token']."&nonce=".$options['readAuth']["nonce"]."&callback=?', function(mdata) {
    if(mdata instanceof Array && mdata.length>0){
      jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list' +
            '?mode=json&view=detail&auth_token=".$options['readAuth']['auth_token']."&nonce=".$options['readAuth']["nonce"]."&taxon_meaning_id='+mdata[0].taxon_meaning_id+'&taxon_list_id=".$options["speciesListID"]."&callback=?', function(data) {
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
bindSpeciesAutocomplete(\"taxonLookupControl\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$options['id']."\", \"".$options['speciesListID']."\", {\"auth_token\" : \"".
            $options['readAuth']['auth_token']."\", \"nonce\" : \"".$options['readAuth']['nonce']."\"}, formatter, \"".lang::get('LANG_Duplicate_Taxon')."\", ".(isset($options['max_species_ids'])?$options['max_species_ids']:25).");
";
      // No help text
      $mapOptions = iform_map_get_map_options($options['args'],$options['readAuth']);
      $olOptions = iform_map_get_ol_options($options['args']);
      $mapOptions['tabDiv'] = 'species';
      $mapOptions['divId'] = 'map2';
      $mapOptions['width'] = isset($options['map2Width']) ? $options['map2Width'] : "250px";
      $mapOptions['height'] = isset($options['map2Height']) ? $options['map2Height'] : "250px";
      $mapOptions['layers']=array("superSampleLocationLayer","occurrencePointLayer");
      $mapOptions['editLayer']=true;
      $mapOptions['maxZoomBuffer']=0.3;
      $mapOptions['initialFeatureWkt']=false;
      $mapOptions['srefId']='sg-imp-sref';
      $mapOptions['srefLatId']='sg-imp-srefX';
      $mapOptions['srefLongId']='sg-imp-srefY';
      $mapOptions['standardControls']=array('layerSwitcher','panZoom');
      $mapOptions['fillColor']=$mapOptions['strokeColor']='Fuchsia';
      $mapOptions['fillOpacity']=0.3;
      $mapOptions['strokeWidth']=1;
      $mapOptions['pointRadius']=6;
      //      $mapOptions['maxZoom']=$args['zoomLevel'];
      $r = '<div>';
      $r .= "<p>".lang::get('LANG_SpeciesInstructions')."</p>\n";
      if($options['includeSubSample'] && $options['mapPosition']=='top') $r .= '<div class="topMap-container">'.data_entry_helper::map_panel($mapOptions, $olOptions).'</div>';
      $r .= '<div class="grid-container">'.$grid.'</div>';
      if($options['includeSubSample'] && $options['mapPosition']!='top') $r .= '<div class="sideMap-container">'.data_entry_helper::map_panel($mapOptions, $olOptions).'</div>';
      data_entry_helper::$javascript .= "var ".$mapOptions['tabDiv']."TabHandler = function(event, ui) {
  if (ui.panel.id=='".$mapOptions['tabDiv']."') {
    var div=$('#".$mapOptions['divId']."')[0];
    div.map.editLayer.destroyFeatures();
    $('.species-grid').find('tr').removeClass('highlight');
    // show the geometry currently held in the main locations part as the parent
    var initialFeatureWkt = $('#imp-boundary-geom').val();
    if(initialFeatureWkt=='') initialFeatureWkt = $('#imp-geom').val();
    var parser = new OpenLayers.Format.WKT();
    var feature = parser.read(initialFeatureWkt);
    superSampleLocationLayer.destroyFeatures();
    superSampleLocationLayer.addFeatures((typeof(feature)=='object'&&(feature instanceof Array) ? feature : [feature]));
    var bounds=superSampleLocationLayer.getDataExtent();
    occurrencePointLayer.removeAllFeatures();
	$('.species-grid').find('.first').each(function(idx, elem){
		if(jQuery(elem).data('feature')!=null){
			jQuery(elem).data('feature').style=null;
			occurrencePointLayer.addFeatures([jQuery(elem).data('feature')]);
		}
	});
    bounds.extend(occurrencePointLayer.getDataExtent());
    // extend the boundary to include a buffer, so the map does not zoom too tight.
    bounds.scale(1.2);
    if (div.map.getZoomForExtent(bounds) > div.settings.maxZoom) {
      // if showing something small, don't zoom in too far
      div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
    } else {
      // Set the default view to show something triple the size of the grid square
      div.map.zoomToExtent(bounds);
    }
  }
};
jQuery(jQuery('#".$mapOptions['tabDiv']."').parent()).bind('tabsshow', ".$mapOptions['tabDiv']."TabHandler);\n";
      return $r.'</div>';
    } else {
      return $taxalist['error'];
    }
  }

    public static function get_species_checklist_options($options) {
    // validate some options
    if (!isset($options['speciesListID']))
      throw new Exception('The speciesListID parameter must be provided for this species checklist.');
    // Apply default values
    $options = array_merge(array(
        'header'=>'true',
        'columns'=>1,
        'attrCellTemplate'=>'attribute_cell',
        'PHPtaxonLabel' => false,
        'occurrenceComment' => false,
        'occurrenceConfidential' => false,
        'occurrenceImages' => false,
        'id' => 'species-grid-'.rand(0,1000),
        'colWidths' => array(),
        'taxonFilterField' => 'none'
    ), $options);
    // If filtering for a language, then use any taxa of that language. Otherwise, just pick the preferred names.
    if (!isset($options['extraParams']['language_iso']))
      $options['extraParams']['preferred'] = 't';
    if (array_key_exists('listId', $options) && !empty($options['listId'])) {
      $options['extraParams']['taxon_list_id']=$options['listId'];
    }
    if (array_key_exists('readAuth', $options)) {
      $options['extraParams'] += $options['readAuth'];
    } else {
      $options['readAuth'] = array(
          'auth_token' => $options['extraParams']['auth_token'],
          'nonce' => $options['extraParams']['nonce']
      );
    }
    $options['table']='taxa_taxon_list';
    return $options;
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
      if($options['useCaptionsInHeader']) unset($attrDef['caption']);
      $attrDef['fieldname'] = '{fieldname}';
      $attrDef['id'] = '{fieldname}';
      $occAttrControls[$occAttrId] = data_entry_helper::outputAttribute($attrDef, $ctrlOptions);
      $idx++;
    }
  }
  
  public static function preload_species_checklist_occurrences($sampleId, $readAuth, $options) {
    $occurrenceIds = array();
    $sampleIds = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the
    // data we need.
    if (is_null(data_entry_helper::$validation_errors)) {
      $extraParams = $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f');
      if(isset($options['includeSubSample'])){
        data_entry_helper::$javascript .= "var occParser = new OpenLayers.Format.WKT();\nvar occFeatures=[];\n";
        $samples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'extraParams' => $readAuth + array('view'=>'detail','parent_id'=>$sampleId,'deleted'=>'f'),
          'nocache' => true));
        foreach($samples as $sample) $sampleIds[$sample['id']] = $sample;
        $extraParams['sample_id'] = array_keys($sampleIds);
      }
      $occurrences = data_entry_helper::get_population_data(array('table' => 'occurrence', 'extraParams' => $extraParams, 'nocache' => true));
      foreach($occurrences as $occurrence){
        if(isset($options['includeSubSample'])){
          $smp=$occurrence['sample_id'];
          data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':sample:date'] = $sampleIds[$smp]['date'];
          data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':sample:entered_sref'] = $sampleIds[$smp]['entered_sref'];
          data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':sample:geom'] = $sampleIds[$smp]['wkt'];
          data_entry_helper::$javascript .= "feature = occParser.read('".$sampleIds[$smp]['wkt']."');\n";
          data_entry_helper::$javascript .= "$('.scOcc-".$occurrence['id']."').data('feature',feature);\noccFeatures.push(feature);\n";
        } else $smp="";
        data_entry_helper::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];
        data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
        data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
        
        data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':present'] = true;
        data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
        data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':occurrence:confidential'] = $occurrence['confidential'];
        // Keep a list of all Ids
        $occurrenceIds[$occurrence['id']] = $occurrence;
      }
      // load the attribute values into the entity to load as well
      $attrValues = data_entry_helper::get_population_data(array(
        'table' => 'occurrence_attribute_value',
        'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
        'nocache' => true
      ));
      foreach($attrValues as $attrValue) {
        data_entry_helper::$entity_to_load['sc::'.$occurrenceIds[$attrValue['occurrence_id']]['sample_id'].':'.$occurrenceIds[$attrValue['occurrence_id']]['taxa_taxon_list_id'].':'.$attrValue['occurrence_id'].':occAttr:'.$attrValue['occurrence_attribute_id'].(isset($attrValue['id'])?':'.$attrValue['id']:'')]
            = $attrValue['raw_value'];
      }
      if(isset($options['includeSubSample']))
        data_entry_helper::$javascript .= "occurrencePointLayer.addFeatures(occFeatures);\n";
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
    // DEBUG/DEV MODE
//     $hiddenCTRL = "text";
//     $r = '<table border=3 id="'.$options['id'].'-scClonable">';
    $r = '<table style="display: none" id="'.$options['id'].'-scClonable">';
    $hiddenCTRL = "hidden";
    $numRows=0;
    $maxCellsPerRow=1;//covers header and comment
    $i=1;
    do{
      $foundRows=false;
      $attrsPerRow=0;
      foreach($attributes as $attribute){
        if($attribute["inner_structure_block"] == "Row".$i) {
          $numRows=$i;
          $foundRows=true;
          $attrsPerRow++;
        }
      }
      $i++;
      if($attrsPerRow>$maxCellsPerRow)$maxCellsPerRow=$attrsPerRow;
    } while ($foundRows);
    if($numRows) $foundRows=true;
    else {
      $numRows=1;
      $maxCellsPerRow=count($attributes);
    }
    if($options['includeSubSample'] && $maxCellsPerRow<($options['displaySampleDate']?3:2))
      $maxCellsPerRow=($options['displaySampleDate']?3:2);
    $idex=1;
    $prefix = "sc:--GroupID--:--SampleID--:--TTLID--:--OccurrenceID--";
    $r .= '<tbody><tr class="scClonableRow first" id="'.$options['id'].'-scClonableRow'.$idex.'"><td class="ui-state-default remove-row" rowspan="'.$options['numRows'].'">X</td>';
    $colspan = ' colspan="'.$maxCellsPerRow.'"';
    $r .= str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']).
        '<td class="scPresenceCell" style="display:none"><input type="hidden" class="scPresence" name="" value="false" /></td></tr>';
    if($options['includeSubSample']){
      $idex++;
      $r .= '<tr class="scClonableRow scDataRow" id="'.$options['id'].'-scClonableRow'.$idex.'">';
      if ($options['displaySampleDate']) {
        $r .= "<td class='ui-widget-content'><label class=\"auto-width\" for=\"$prefix:sample:date\">".lang::get('LANG_Date').":</label> <input type=\"text\" id=\"$prefix:sample:date\" class=\"date\" name=\"$prefix:sample:date\" value=\"\" /></td>";
      }
      $r .= "<td class='ui-widget-content'><input type=\"$hiddenCTRL\" id=\"sg---GroupID---imp-sref\" name=\"$prefix:sample:entered_sref\" value=\"\" />
<input type=\"$hiddenCTRL\" id=\"sg---GroupID---imp-geom\" name=\"$prefix:sample:geom\" value=\"\" />
<label class=\"auto-width\" for=\"sg---GroupID---imp-srefX\">".lang::get('LANG_Species_X_Label').":</label> <input type=\"text\" id=\"sg---GroupID---imp-srefX\" class=\"imp-srefX integer\" name=\"dummy:srefX\" value=\"\" /></td>
<td class='ui-widget-content'><label class=\"auto-width\" for=\"sg---GroupID---imp-srefY\">".lang::get('LANG_Species_Y_Label').":</label> <input type=\"text\" id=\"sg---GroupID---imp-srefY\" class=\"imp-srefY integer\" name=\"dummy:srefY\" value=\"\"/>
</td>";
      if($maxCellsPerRow>($options['displaySampleDate']?3:2))
        $r .= "<td class='ui-widget-content' colspan=".($maxCellsPerRow-($options['displaySampleDate']?3:2))."></td>";
      $row.="</tr>";
    }
    $idx = 0;
    
    for($i=1; $i<=$numRows; $i++){
      $numCtrls=0;
      $row='';
      foreach ($attributes as $attrId => $attribute) {
        $control=$occAttrControls[$attrId];
        if($foundRows && $attribute["inner_structure_block"] != "Row".$i) continue;
        $ctrlId=$prefix.":occAttr:".$attrId;
        $oc = str_replace('{fieldname}', $ctrlId, $control);
        if (isset($attributes[$attrId]['default']) && !empty($attributes[$attrId]['default'])) {
          $existing_value=$attributes[$attrId]['default'];
          if (substr($oc, 0, 7)=='<select') // For select controls, specify which option is selected from the existing value
            $oc = str_replace('value="'.$existing_value.'"', 'value="'.$existing_value.'" selected="selected"', $oc);
          else $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
        }
        $numCtrls++;
        $row .= str_replace(array('{label}', '{content}', '{class}'),
          array(lang::get($attributes[$attrId]['caption']),
            str_replace('{fieldname}', "$prefix:occAttr:$attrId", $oc),
            self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['untranslatedCaption']).'Cell'),
          $indicia_templates['attribute_cell']);
      }
      $idx++;
      if($maxCellsPerRow>$numCtrls)
        $row .= "<td class='ui-widget-content' colspan=".($maxCellsPerRow-$numCtrls)."></td>";
      // no confidential checkbox.
      $idex++;
      $r .='<tr class="scClonableRow scDataRow'.(count($rows)==($options['numRows']-1)?' last':'').'" id="'.$options['id'].'-scClonableRow'.$idex.'">'.$row.'</tr>'; // no images.
    }
    if ($options['includeOccurrenceComment']) {
      $idex++;
      $r .= "</tr><tr class=\"scClonableRow scDataRow last\" id=\"".$options['id']."-scClonableRow".$idex."\">
<td class=\"ui-widget-content scCommentCell\" ".$colspan.">
  <label for=\"$prefix:occurrence:comment\" class=\"auto-width\">".lang::get("Comment").":</label>
  <input type=\"text\" class=\"scComment\" name=\"$prefix:occurrence:comment\" id=\"$prefix:occurrence:comment\" value=\"\">
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
  
  
  public static function get_species_checklist_record_list($options) {
    // at this point the data_entry_helper::$entity_to_load has been preloaded with the occurrence data.
    $taxalist = array();
    // copy the options array so we can modify it
    $extraTaxonOptions = array_merge(array(), $options);
    // We don't want to filter the taxa to be added, because if they are in the sample, then they must be included whatever.
    unset($extraTaxonOptions['extraParams']['taxon_list_id']);
    unset($extraTaxonOptions['extraParams']['preferred']);
    unset($extraTaxonOptions['extraParams']['language_iso']);
     // append the taxa to the list to load into the grid
    $fullTaxalist = data_entry_helper::get_population_data($extraTaxonOptions);
    $recordList = array();
    foreach(data_entry_helper::$entity_to_load as $key => $value) {
      $parts = explode(':', $key,6);
      // Is this taxon attribute data?
      if (count($parts) > 5 && $parts[0] == 'sc' && $parts[3]!='--TTLID--') {
        if($parts[4]=='') $occList['error'] = 'ERROR PROCESSING entity_to_load: found name '.$key.' with no sequence/id number in part 4';
        else if(!isset($recordList[$parts[4]])){
          $record = array('occurrence' => array('id' => $parts[4]), 'sample'=>array('id' => $parts[2]));
          foreach($fullTaxalist as $taxon){
            if($parts[3] == $taxon['id']) {
              $record['taxon'] = $taxon;
            }
          }
          $recordList[$parts[4]] = $record; // index on occurrence ID
        }
      }
    }
  	return $recordList;
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
    if ($options['useCaptionsInHeader']) { // needs also only 1 row of attributes and separate cells
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
    }
    return $r;
  }

  private static function get_species_checklist_col_header($caption, &$colIdx, $colWidths, $attrs='') {
    $width = count($colWidths)>$colIdx && $colWidths[$colIdx] ? ' style="width: '.$colWidths[$colIdx].'%;"' : '';
    if (!strpos($attrs, 'display:none')) $colIdx++;
    return "<th$attrs$width>".$caption."</th>";
  }
  
  /*
   * Finally the submission handler
   */
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
      $subModels = self::wrap_species_checklist($values);
      // TBD target species inclusion to be driven by presence of a field.
      if(isset($values['includeTargetSpeciesGrid'])){
       foreach($values as $key => $value){
        $parts = explode(':', $key, 5);
        if ($parts[0] == 'targ' && $parts[3] == 'presence'){
          $smp = array('fkId' => 'parent_id', 'model' => array('id' => 'sample', 'fields' => array()));
          $smp['model']['fields']['survey_id'] = array('value' => $values['survey_id']);
          $smp['model']['fields']['website_id'] = array('value' => $values['website_id']);
          $smp['model']['fields']['date'] = array('value' => $values['sample:date']);
          $smp['model']['fields']['smpAttr:'.$parts[4]] = array('value' => $parts[2]);
          $smp['copyFields'] = array('location_id'=>'location_id'); // from parent->to child
          if($value != '1') $smp['model']['fields']['deleted'] = array('value' => 't');
          if($parts[1] != '-') $smp['model']['fields']['id'] = array('value' => $parts[1]);
          foreach($values as $key1 => $value1){
            $moreParts = explode(':', $key1, 5);
            if ($moreParts[0] == 'targ' && $moreParts[1] == $parts[1] && $moreParts[2] == $parts[2] && $moreParts[3]== 'smpAttr'){
              $smp['model']['fields']['smpAttr:'.$moreParts[4]] = array('value' => $value1);
            }
          }
          if($value == '1' || $parts[1] != '-') $subModels[]=$smp;
        }
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
    }
    return $sampleMod;
  }
  /**
  * Wraps data from a species checklist grid: modified from original data_entry_helper
  * function to allow multiple rows for the same species.
  */
  private static function wrap_species_checklist($arr){
    if (!array_key_exists('website_id', $arr))
      throw new Exception('Cannot find website id in POST array!');
    // not doing occurrence images at this point - TBD
    $samples = array();
    $occurrences = array();
    foreach ($arr as $key=>$value){
      // Don't explode the last element for attributes
      $a = explode(':', $key, 6);
      // sc:--GroupID--:--SampleID--:--TTLID--:--OccurrenceID--
      if ($a[0]=='sc'){
        $b = explode(':', $a[5]);
        if($a[1]){ // key on the Group ID
          $occurrences[$a[1]]['taxa_taxon_list_id'] = $a[3];
          if($b[0]=='sample' || $b[0]=='smpAttr')
            $samples[$a[1]][$a[5]] = $value;
          else
            $occurrences[$a[1]][$a[5]] = $value;
          // store any id so update existing record prefix
          if(is_numeric($a[2]) && $a[2]>0) $samples[$a[1]]['id'] = $a[2];
          if(is_numeric($a[4]) && $a[4]>0) $occurrences[$a[1]]['id'] = $a[4];
        }
      }
    }
    $subModels = array();
    foreach ($occurrences as $id => $occurrence) {
      $present = self::wrap_species_checklist_record_present($occurrence);
      if (array_key_exists('id', $occurrence) || $present) { // must always handle row if already present in the db
        if (!$present) $occurrence['deleted'] = 't';
        $occurrence['website_id'] = $arr['website_id'];
        if (array_key_exists('occurrence:determiner_id', $arr)) $occurrence['determiner_id'] = $arr['occurrence:determiner_id'];
        if (array_key_exists('occurrence:record_status', $arr)) $occurrence['record_status'] = $arr['occurrence:record_status'];
        $occ = data_entry_helper::wrap($occurrence, 'occurrence');
        if(isset($arr['includeSubSample'])){
          if (!$present) $samples[$id]['deleted'] = 't';
          $samples[$id]['website_id'] = $arr['website_id'];
          $samples[$id]['entered_sref_system'] = '2169'; // TBD 
          $samples[$id]['survey_id'] = $arr['survey_id'];
          $smp = data_entry_helper::wrap($samples[$id], 'sample');
          $smp['subModels'] = array(array('fkId' => 'sample_id', 'model' => $occ));
          $smp = array('fkId' => 'parent_id', 'model' => $smp);
          if(!isset($samples[$id]['date'])) $smp['copyFields'] = array('date'=>'date'); // from parent->to child
          $subModels[] = $smp;
        } else {
          $subModels[] = array('fkId' => 'sample_id', 'model' => $occ);
        }
      }
    }
    return $subModels;
  }

  /**
   * Test whether the data extracted from the $_POST for a species_checklist grid row refers to an occurrence record.
   * @access Private
   */
  private static function wrap_species_checklist_record_present($record) {
    return (array_key_exists('present', $record) && $record['present']!='false'); // inclusion of record detected from the presence
  }
  
} 