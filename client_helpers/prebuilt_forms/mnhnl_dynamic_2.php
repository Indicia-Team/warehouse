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
  protected static $check_or_radio_group_template;
  protected static $check_or_radio_group_item_template;
  
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
      	  'name'=>'targetSpeciesAttrList',
      	  'caption'=>'Target Species Attribute List',
      	  'description'=>'Comma separated list of sample attribute IDs used in the target species grid. This is used in the control and the reporting.',
      	  'type'=>'text_input',
      	  'group' => 'User Interface',
          'required' => false
      	),
        array(
          'name' => 'target_species_subsample_method_id',
          'caption' => 'Target Species Sample Method',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:sample_methods'),
          'required' => false,
          'helpText' => 'The sample method that will be used when creating subsamples in the target species grid.'
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
          'name' => 'subsample_method_id',
          'caption' => 'Subsample Method',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:sample_methods'),
          'required' => false,
          'helpText' => 'The sample method that will be used for created subsamples in the species grid.'
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
   * Next the functions which relate to the main front page.
   */
  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    if(!user_access($args['edit_permission'])) return('');
    if(!$retTabs) return array('#downloads' => lang::get('Reports'), '#locations' => lang::get('LANG_Locations'));
    if($args['LocationTypeTerm']=='' && isset($args['loctoolsLocTypeID'])) $args['LocationTypeTerm']=$args['loctoolsLocTypeID'];
    $primary = iform_mnhnl_getTermID(array('read'=>$readAuth), 'indicia:location_types',$args['LocationTypeTerm']);
    $r= '<div id="downloads" >';
    $r .= '<p>'.lang::get('LANG_Data_Download').'</p>';
    if(isset($args['targetSpeciesAttr']) && $args['targetSpeciesAttr']!="") {
      $targetSpeciesAttr=iform_mnhnl_getAttr(parent::$auth, $args, 'sample', $args['targetSpeciesAttr'], $args['target_species_subsample_method_id']);
      if(!$targetSpeciesAttr) return lang::get('This form must be used with a survey that has the '.$args['targetSpeciesAttr'].' sample attribute associated with it.');
      data_entry_helper::$javascript .= "
jQuery('#downloads').find('[name=params]').val('{\"survey_id\":".$args['survey_id'].", \"location_type_id\":".$primary.", \"target_species_attr\":".$targetSpeciesAttr['attributeId'].", \"target_species_termlist\":".$targetSpeciesAttr['termlist_id'].(isset($args['targetSpeciesAttrList']) && $args['targetSpeciesAttrList']!='' ? ", \"target_species_attr_list\":\"".$args['targetSpeciesAttrList']."\"":"")."}');
";
    };
    return $r.($args['sites_download_report']!=''?'
  <form id="sitesReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['sites_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Sites">
    <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "location_type_id":'.$primary.'}\' />
    <label>'.lang::get('Sites report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
  </form>':'').($args['conditions_download_report']!=''?'
  <form id="conditionsReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['conditions_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Conditions">
    <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "location_type_id":'.$primary.'}\' />
    <label>'.lang::get('Conditions report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
  </form>':'').'
  <form id="speciesReportRequestForm" method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report='.$args['species_download_report'].'.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename='.$args['reportFilenamePrefix'].'Species">
    <input type="hidden" name="params" value=\'{"survey_id":'.$args['survey_id'].', "location_type_id":'.$primary.'}\' />
    <label>'.lang::get('Species report').':</label><input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('Download').'">
  </form>
</div>'.iform_mnhnl_locModTool(parent::$auth, $args, parent::$node);
  }
  /*
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
  	global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    $userIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS User ID', isset($args['sample_method_id']) && $args['sample_method_id']!="" ? $args['sample_method_id'] : false);
    if (!$userIdAttr) return lang::get('getSampleListGrid function: This form must be used with a survey that has the CMS User ID sample attribute associated with it, so records can be tagged against their creator.');
    $extraParams = array('survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>(user_access($args['edit_permission']) ? -1 :  $user->uid)); // use -1 if admin - non logged in will not get this far.
    $userNameAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS Username', isset($args['sample_method_id']) && $args['sample_method_id']!="" ? $args['sample_method_id'] : false);
    if ($userNameAttr) $extraParams['userName_attr_id']=$userNameAttr;
    if(isset($args['targetSpeciesAttr']) && $args['targetSpeciesAttr']!="") {
      $targetSpeciesIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', $args['targetSpeciesAttr'], $args['target_species_subsample_method_id']);
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
    $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'new')).'\'">';    
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
    $r = '<p>'.lang::get('LANG_SampleListGrid_Preamble').(user_access($args['edit_permission']) ? lang::get('LANG_All_Users') : $user->name).'</p>';
    return $r;
  }
  protected static function getReportActions() {
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
    $ret = iform_mnhnl_lux5kgridControl($auth, $args, parent::$node,
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
    return iform_mnhnl_recordernamesControl(parent::$node, $auth, $args, $tabalias, $options);
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
    global $indicia_templates;
    data_entry_helper::$javascript .= "
if($.browser.msie && $.browser.version < 9)
  $('input[type=radio],[type=checkbox]').live('click', function(){
    this.blur();
    this.focus();
});\n";
    self::$check_or_radio_group_template = $indicia_templates['check_or_radio_group'];
    self::$check_or_radio_group_item_template = $indicia_templates['check_or_radio_group_item'];
    $indicia_templates['check_or_radio_group'] = '<div class="radio_group_container"><span {class}>{items}</span></div>';
    $indicia_templates['check_or_radio_group_item'] = '<nobr><div class="radio_group_item"><input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked} {disabled}/><label for="{itemId}">{caption}</label></div></nobr> {sep}';
    if(isset($options['resizeRadioGroupSelector'])){
      $selectors = explode(',',$options['resizeRadioGroupSelector']);
      foreach($selectors as $selector){
        data_entry_helper::$javascript .= "resize_radio_groups('".$selector."');\n";
      }
    }
    data_entry_helper::$javascript .= "\nindiciaData.resizeSpeciesRadioGroup = ".(isset($options['resizeRadioGroupSelector']) && (in_array('*',$selectors) || in_array('species',$selectors)) ? 'true' : 'false').";\n";
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
    // possible clash with link_species_popups, so latter disabled.
    iform_mnhnl_addCancelButton($args['interface']);
    $attrOpts = array(
    		'valuetable'=>'sample_attribute_value'
    		,'attrtable'=>'sample_attribute'
    		,'key'=>'sample_id'
    		,'extraParams'=>$auth['read']
    		,'survey_id'=>$args['survey_id']);
    if(isset($args['sample_method_id']) && $args['sample_method_id']!="") $attrOpts['sample_method_id'] = $args['sample_method_id'];
    $attributes = data_entry_helper::getAttributes($attrOpts);
    foreach($attributes as $attribute){
      data_entry_helper::$javascript .= "$('#smpAttr\\\\:".$attribute['attributeId']."').addClass('smpAttr-".str_replace(' ', '', ucWords($attribute['untranslatedCaption']))."');\n";
    }
    $restrictText = array();
    if(isset($options["attrRestrictions"]) && $options["attrRestrictions"]!=""){
      $restrictionRules = explode(';', $options["attrRestrictions"]);
      foreach($restrictionRules as $restrictionRule){
        $parts = explode(':', $restrictionRule);
        $valList = array();
        for($i = 2; $i < count($parts); $i++){
          $values = explode(',', trim($parts[$i]));
          $valString = "{value : ".$values[0].", list: [\"";
          unset($values[0]);
          $valList[] = $valString.(implode("\",\"", $values))."\"]}";
        }
        $restrictText[] = "{parent : ".$parts[0].", child : ".$parts[1].",
  values: [".(implode(",\n    ",$valList))."]}";
      }
    }
    data_entry_helper::$javascript .= "\nrelationships = [".implode(",\n",$restrictText)."\n];";
    if(isset($options["attrRestrictionsProcessOrder"]) && $options["attrRestrictionsProcessOrder"]!=""){
      $attrOrder = explode(':', $options["attrRestrictionsProcessOrder"]);
      if(!isset($options["attrRestrictionsDuplicateAttrList"]))
      	$options["attrRestrictionsDuplicateAttrList"] = $options["attrRestrictionsProcessOrder"];
      $duplicateAttrList = explode(':', $options["attrRestrictionsDuplicateAttrList"]);
      data_entry_helper::$javascript .= "
attrRestrictionsProcessOrder = [".(implode(',', $attrOrder))."];
attrRestrictionsDuplicates = ".(isset($options["attrRestrictionsEnforceDuplicates"]) ? 'true' : 'false').";
attrRestrictionsDuplicateAttrList = [".(implode(',', $duplicateAttrList))."];
// set up pre-existing ones: trigger first which will bubble through
jQuery('.mnhnl-species-grid').find('[name$=occAttr\\:".$attrOrder[0]."],[name*=occAttr\\:".$attrOrder[0]."\\:]').each(function(){
    set_up_relationships(".$attrOrder[1].", $(this), false, ".(isset($options["attrRestrictionsEnforceDuplicates"]) ? 'true' : 'false').");
});
// Set up what happens when existing fields are changed\n";
      // need to check all but last
      for($i = 0; $i < count($attrOrder)-1; $i++){
        data_entry_helper::$javascript .= "
jQuery('.mnhnl-species-grid').find('[name$=occAttr\\:".$attrOrder[$i]."],[name*=occAttr\\:".$attrOrder[$i]."\\:]').change(function(){
  set_up_relationships(".$attrOrder[$i+1].", $(this), true, ".(isset($options["attrRestrictionsEnforceDuplicates"]) ? 'true' : 'false').");
});\n";
      }
      data_entry_helper::$javascript .= "// last is special - only updates similar on other rows.
jQuery('.mnhnl-species-grid').find('[name$=occAttr\\:".$attrOrder[count($attrOrder)-1]."],[name*=occAttr\\:".$attrOrder[count($attrOrder)-1]."\\:]').change(function(){
  set_up_last_relationship(this, ".$attrOrder[count($attrOrder)-1].", ".$attrOrder[count($attrOrder)-2].", true);
});\n";
      // for duplicate checks had to trigger on all duplicate based fields. Don't include the precision field, which is on the sample field.
      $selector = (isset($options['includeSubSample']) ? '.imp-srefX,.imp-srefY' : '');
      foreach($duplicateAttrList as $attr){
        if(!in_array($attr, $attrOrder))
          $selector .= ($selector==""?"":",")."[name$=occAttr\\:".$attr."],[name*=occAttr\\:".$attr."\\:]";
      }
      if($selector != "" && isset($options["attrRestrictionsEnforceDuplicates"]))
        data_entry_helper::$javascript .= "
attrRestrictionsDuplicateSelector = \"".$selector."\";
jQuery('.mnhnl-species-grid').find('".$selector."').change(function(){
  set_up_last_relationship(this, ".$attrOrder[count($attrOrder)-1].", ".$attrOrder[count($attrOrder)-2].", true);
});\n";
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
          } else if($rule[$i]=='end_time'){
            // we are assuming this is on the main supersample.
          	$attrOpts = array(
          			'valuetable'=>'sample_attribute_value'
          			,'attrtable'=>'sample_attribute'
          			,'key'=>'sample_id'
                    ,'fieldprefix'=>'smpAttr'
          			,'extraParams'=>$auth['read']
          			,'survey_id'=>$args['survey_id']);
          	if(isset($args['sample_method_id']) && $args['sample_method_id']!="") $attrOpts['sample_method_id'] = $args['sample_method_id'];
          	$sampleAttrs = data_entry_helper::getAttributes($attrOpts, true);
            // fetch start time.
            $found = false;
            foreach ($sampleAttrs as $id => $attr)
              if($attr["untranslatedCaption"]=="Start time") {
                $found = $id;
                break;
              }
            if($found === false) continue;
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').rules('add', {end_time: true});
$.validator.addMethod('end_time', function(value, element){
  var startTime = jQuery('[name=smpAttr\\:".$found."],[name^=smpAttr\\:".$found."\\:]') 
  if(value=='' || startTime.val() == '') return true; 
  return (value >= startTime.val()); 
},
  \"".lang::get('validation_end_time')."\");
";
          } else if($rule[$i]=='N=2'){
            // We change the way this is handled: no longer a validation rule as this is only applied when the next  step is pressed
            // we want immediate, restrict number checkable. Still are assuming this is on the main supersample.
            // allow a maximum of 2 entries in a multiple value checkbox set.
            $func = "check_N2_".str_replace(':','_',$rule[0]);
            $selector = str_replace(':','\\:',$rule[0]);
            data_entry_helper::$late_javascript .= $func." = function(){
  var controls = jQuery('[name=".$selector."\\[\\]],[name=".$selector."],[name^=".$selector."\\:]').filter('[type=checkbox]'); 
  var checkedControls = controls.filter(':checked'); 
  if(checkedControls.length >= 2)
    controls.not(':checked').attr('disabled',true);
  else
    controls.removeAttr('disabled');
};
jQuery('[name=".$selector."\\[\\]],[name=".$selector."],[name^=".$selector."\\:]').click(".$func.");\n".$func."();\n";
          }  else if($rule[$i]=='N=3'){
            // we want immediate, restrict number checkable..
            // allow a maximum of 3 entries in a multiple value checkbox set. name will be the same.
            $func = "check_N3_".str_replace(':','_',$rule[0]);
            $selector = str_replace(':','\\:',$rule[0]);
            data_entry_helper::$late_javascript .= $func."_sub = function(elem){
  var controls = jQuery('[name='+elem.name+']'); 
  var checkedControls = controls.filter(':checked');
  if(checkedControls.length >= 3)
    controls.not(':checked').attr('disabled',true);
  else
    controls.removeAttr('disabled');
};\n".$func." = function(){
  ".$func."_sub(this);
};
jQuery('[name$=".$selector."\\[\\]]').live('click', ".$func.");
jQuery('[name$=".$selector."\\[\\]]').each(function(){
  ".$func."_sub(this);
});\n";
          } else if(substr($rule[0], 3, 4)!= 'Attr'){ // have to add for non attribute case.
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').addClass('".$rule[$i]."');";
          }
    }
    if(!isset($options['speciesListInTextAttr'])) return '';
    $ctrlArgs=explode(',',$options['speciesListInTextAttr']);
    data_entry_helper::$javascript .= "
indiciaData.speciesListInTextSelector = '.".$ctrlArgs[0]."';
indiciaData.None = '".lang::get('None')."';
indiciaData.speciesListInTextLabel = '".lang::get('Add supporting plant species to list')."';
indiciaData.speciesListInTextSpeciesList = ".$ctrlArgs[1].";
indiciaData.speciesListInTextMax = '".$ctrlArgs[2]."';\n";
    return '';
  }

  protected static function get_control_targetspeciesgrid($auth, $args, $tabalias, $options) {
    $targetSpeciesAttr=iform_mnhnl_getAttr($auth, $args, 'sample', $args['targetSpeciesAttr'], $args['target_species_subsample_method_id']);
    if (!$targetSpeciesAttr) return lang::get('The Target Species Grid control must be used with a survey that has the '.$args['targetSpeciesAttr'].' attribute associated with it.');
    if (!isset($args['target_species_subsample_method_id']) || $args['target_species_subsample_method_id']=="")
      return lang::get('The Target Species Grid control must be configured with a target_species_subsample_method_id.');
    // the target species grid is based on a grouping of samples determined by the
    // 1) the termlist id of the list of target species: argument targetSpeciesTermList
    // 2) a default set of attributes to be loaded: visit, Unsuitablity
    // 3) Overrides for specific target species: Common wall disabled second survey
    $termlist = $targetSpeciesAttr["termlist_id"];
    $extraParams = $auth['read'] + array('termlist_id' => $termlist, 'view'=>'detail');
    $targetSpecies = data_entry_helper::get_population_data(array('table' => 'termlists_term', 'extraParams' => $extraParams)); // cachable
    $smpAttributes = data_entry_helper::getAttributes(array(
       'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'{MyPrefix}:smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
       ,'sample_method_id'=>$args['target_species_subsample_method_id']
    ), true);
    $retval = '<br /><input type="hidden" name="includeTargetSpeciesGrid" value="true" ><input type="hidden" name="target_species_subsample_method_id" value="'.$args['target_species_subsample_method_id'].'" ><table class="target-species-grid"><tr><th colspan=2>'.lang::get('Target Species').'</th>';
    $attrList = explode(',', $args['targetSpeciesAttrList']);
    $attrIDs = array();
    foreach($attrList as $attr){
      $cell = false;
      // $retval .= '<th></th>'; // blank headings: will put captions in table itself.
      if(is_numeric($attr)) {
      	if(isset($smpAttributes[intval($attr)])){
          $cell = $smpAttributes[intval($attr)]['caption'];
          $attrIDs[] = intval($attr);
      	}
      } else {
        foreach($smpAttributes as $id=>$sattr){
          if($attr == $sattr['untranslatedCaption']){
            $cell = $sattr['caption'];
            $attrIDs[] = $id;
          }
        }
        if($cell===false)
          $retval = lang::get('The configuration of the Target Species Grid includes a '.$attr.' samples attribute, which is not associated with this survey.').'<br/>'.$retval;
      }
      if(!isset($options['useCaptionsInHeader'])) $cell="";
      if($cell!==false)
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
        'extraParams'=> $auth['read']+ array('view'=>'detail',
            'parent_id' => data_entry_helper::$entity_to_load['sample:id'],
            'sample_method_id'=>$args['target_species_subsample_method_id']));
      $subSamples = data_entry_helper::get_population_data($smpOptions);
      foreach($subSamples as $sample) {
        $subSamplesAttrs[$sample['id']] = data_entry_helper::getAttributes(array(
             'attrtable'=>'sample_attribute'
            ,'valuetable'=>'sample_attribute_value'
            ,'id'=>$sample['id']
            ,'key'=>'sample_id'
            ,'fieldprefix'=>'{MyPrefix}:smpAttr'
            ,'extraParams'=>$auth['read']
            ,'survey_id'=>$args['survey_id']
            ,'sample_method_id'=>$args['target_species_subsample_method_id']), true);
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
                        'disabled'=>' disabled="disabled"');
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
        if(isset($smpAttributes[$attrID])){
          $attrOpts['class']='targ-smpAttr-'.str_replace(' ', '', ucWords($smpAttributes[$attrID]['untranslatedCaption']));
          if($smpID && $smpAttributes[$attrID]["data_type"]!="B") $attrOpts['validation'] = array('required');
          else unset($attrOpts['validation']);
          $retval .= str_replace('{MyPrefix}',$fieldprefix, 
              '<td class="targ-grid-cell"><nobr>'.data_entry_helper::outputAttribute(($smpID ? $subSamplesAttrs[$smpID][$attrID] : $smpAttributes[$attrID]),
                $attrOpts).'</nobr></td>');
        }
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
        data_entry_helper::$javascript .= "').remove();\n";
      }
    }    
    if(!isset($options['optional']))
      data_entry_helper::$late_javascript .= "// JS for target species grid control.
$.validator.addMethod('targ-presence', function(value, element){
	return jQuery('.targ-presence')[0]!=element || jQuery('.targ-presence').filter('[checked]').length > 0;
},
  \"".lang::get('validation_targ-presence')."\");
";
    return $retval;
  }

  protected static function get_control_moveotherfields($auth, $args, $tabalias, $options) {
    // We assume that the key is meaning_id.
    $groups=explode(';',$options['groups']);
    foreach($groups as $group){
      $parts=explode(',',$group);
      $attr=iform_mnhnl_getAttr($auth, $args, $parts[0], $parts[1]);
      $other=helper_base::get_termlist_terms($auth, intval($attr['termlist_id']), array('Other'));
      $attr2=iform_mnhnl_getAttrID($auth, $args, $parts[0], $parts[2]);
      switch($parts[0]){
        case 'sample': $prefix='smpAttr';
          break;
        default: break;
      }
      data_entry_helper::$javascript .= "
var other = jQuery('[name=".$prefix."\\:".$attr2."],[name^=".$prefix."\\:".$attr2."\\:]');
other.next().remove(); // remove break
other.prev().remove(); // remove legend
other.removeClass('wide').remove(); // remove Other field, then bolt in after the other radio button.
jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').filter('[value=".$other[0]['meaning_id']."],[value^=".$other[0]['meaning_id']."\\:]').parent().css('width','auto').append(other);
jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').change(function(){
  jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').filter('[value=".$other[0]['meaning_id']."],[value^=".$other[0]['meaning_id']."\\:]').each(function(){
    if(this.checked)
      jQuery('[name=".$prefix."\\:".$attr2."],[name^=".$prefix."\\:".$attr2."\\:]').addClass('required').removeAttr('readonly');
    else
      jQuery('[name=".$prefix."\\:".$attr2."],[name^=".$prefix."\\:".$attr2."\\:]').removeClass('required').val('').attr('readonly',true);
  });
});
jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').filter('[value=".$other[0]['meaning_id']."],[value^=".$other[0]['meaning_id']."\\:]').change();
";
    }
    return '';
  }

/*
  Whole thing is based on Dynamic_1, but the submission array is more complicated.
  TBD: zoom to session location, also includes display of all occurrences so far.
  Assume grid based input: TBD remove this option.
  No images.
  TBD Add Select (and draw?) controls to the map.
  TBD allow hiding of species not being entered.
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
  	global $indicia_templates;
  	$indicia_templates['check_or_radio_group'] = '<span {class}>{items}</span>';
  	$indicia_templates['check_or_radio_group_item'] = '<span><input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked} {disabled}/><label for="{itemId}">{caption}</label></span>{sep}';
//  	$indicia_templates['check_or_radio_group'] = self::$check_or_radio_group_template;
//  	$indicia_templates['check_or_radio_group_item'] = self::$check_or_radio_group_item_template;
    data_entry_helper::$javascript .= "
// Main table existing entries
speciesRows = jQuery('.mnhnl-species-grid > tbody').find('tr');
for(var j=0; j<speciesRows.length; j++){
	occAttrs = jQuery(speciesRows[j]).find('.scOccAttrCell');\n";
    if(isset($options['unitSpeciesMeaning']))
      data_entry_helper::$javascript .= "
	if(jQuery(speciesRows[j]).hasClass('scMeaning-".$options['unitSpeciesMeaning']."')){
		var units = occAttrs.find('.scUnits');
		if(units.length > 0){
		  if(units.find('option').filter(':selected')[0].text=='m2')
		    occAttrs.find('.scNumber').removeClass('integer').attr('min',0);
		  units.change(function(){
		    jQuery('.ui-state-error').removeClass('ui-state-error');
		    jQuery('.inline-error').remove();
		    if(jQuery(this).find('option').filter(':selected')[0].text=='m2')
		      jQuery(this).closest('tr').find('.scNumber').removeClass('integer').attr('min',0);
		    else
		      jQuery(this).closest('tr').find('.scNumber').addClass('integer').attr('min',1);
		  });
		}
	} else {
		occAttrs.find('.scNumber').addClass('integer');
		occAttrs.find('.scUnits').find('option').each(function(index, elem){
		  if(elem.text == 'm2' || elem.value == '') jQuery(elem).remove();
		});
	}\n";
    data_entry_helper::$javascript .= "
}
hook_species_checklist_pre_delete_row=function(e) {
    return confirm(\"".lang::get('Are you sure you want to delete this row?')."\");
};
";
    $extraParams = $auth['read'];
    // multiple species being input via a grid
    $myLanguage = iform_lang_iso_639_2($args['language']);
    $species_ctrl_opts=array_merge(array(
          'speciesListID'=>$options['speciesListID'],
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'columns'=>1,          
          'extraParams'=>$extraParams,
          'readAuth'=>$extraParams,
          'survey_id'=>$args['survey_id'],
          'includeSubSample'=>isset($options['includeSubSample']),
          'subsample_method_id'=>$args['subsample_method_id'],
          'separateCells'=>isset($options['separateCells']),
          'useCaptionsInHeader'=>isset($options['useCaptionsInHeader']) && $options['useCaptionsInHeader']=='true',
          'useCaptionsInPreRow'=>isset($options['useCaptionsInPreRow']) && $options['useCaptionsInPreRow']=='true',
          'resizeRadioGroup'=>isset($options['resizeRadioGroup']) && $options['resizeRadioGroup']=='true',
          'includeOccurrenceComment'=>isset($options['includeOccurrenceComment']) && $options['includeOccurrenceComment']=='true',
          'PHPtaxonLabel' => true,
          'language' => $myLanguage,
          'args'=>$args
    ), $options);
    $species_ctrl_opts['mapPosition'] = (isset($options['mapPosition']) ? $options['mapPosition'] : 'top');
    call_user_func(array(get_called_class(), 'build_grid_taxon_label_function'), $args, $options);
    return self::species_checklist($species_ctrl_opts);
  }

  /**
   * Build a PHP function  to format the autocomplete item list.
   * This puts the choosen one first, folowed by ones with the same meaning, led by the preferred one.
   * This differs from the JS formatter function, which puts preferred first.
   */
  protected static function build_grid_taxon_label_function($args, $options) {
    global $indicia_templates;
    // always include the searched name
    $php = '$taxa_list_args=array('."\n".
        '  "extraParams"=>array("view"=>"detail",'."\n".
        '    "auth_token"=>"'.parent::$auth['read']['auth_token'].'",'."\n".
        '    "nonce"=>"'.parent::$auth['read']['nonce'].'",'."\n".
        '    "taxon_list_id"=>'.$options['speciesListID'].'),'."\n".
        '  "table"=>"taxa_taxon_list");'."\n".
        '$responseRecords = data_entry_helper::get_population_data($taxa_list_args);'."\n".
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

  private static function _getCtrlNames(&$ctrlName, &$oldCtrlName, $isAttr)
  {
    $ctrlArr = explode(':',$ctrlName,6);
    // sc:<rowIdx>:<smp_id>:<ttlid>:<occ_id>:[field]";
    // NB this does not cope with multivalues.
    if ($ctrlArr[4]!="") {
      // preg_grep is behaving oddly.... have to use flag ...
      $search = preg_grep("/^sc:[0-9]*:$ctrlArr[2]:$ctrlArr[3]:$ctrlArr[4]:$ctrlArr[5]".($isAttr ? ':[0-9]*' : '')."$/", array_keys(data_entry_helper::$entity_to_load));
      if(count($search)===1){
        $oldCtrlName = implode('', $search);
        $ctrlName = explode(':',$oldCtrlName);
        $ctrlName[1]=$ctrlArr[1];
        $ctrlName = implode(':', $ctrlName);
      } else $oldCtrlName=$ctrlName;
    } else $oldCtrlName=$ctrlName;
  }

  public static function species_checklist($options)
  {
  	global $indicia_templates;
//    $options = data_entry_helper::check_arguments(func_get_args(), array('speciesListID', 'occAttrs', 'readAuth', 'extraParams'));
    $options = self::get_species_checklist_options($options);
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    $occAttrControls = array();
    $occAttrCaptions = array();
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
      $maxCellsPerRow = max($maxCellsPerRow,$attrsPerRow);
    } while ($foundRows);
    if($numRows) $foundRows=true;
    else {
      $numRows=1;
      $maxCellsPerRow=count($attributes);
    }
    $options['extraParams']['view'] = 'detail';
    $options['numRows'] = 1 + /* taxon name row */
                          ($options['includeSubSample']?1:0) + /* row holding subsample location and date */
                          $numRows * ($options['useCaptionsInPreRow'] ? 2 : 1) +
                          ($options['includeOccurrenceComment']?1:0);
    $recordList = self::get_species_checklist_record_list($options);
    $grid = "";
    $precision = false;
    if($options['includeSubSample']) {
      $grid.="<input type='hidden' name='includeSubSample' id='includeSubSample' value='true' >";
      $smpattributes = data_entry_helper::getAttributes(array(
           'valuetable'=>'sample_attribute_value'
           ,'attrtable'=>'sample_attribute'
           ,'key'=>'sample_id'
           ,'fieldprefix'=>"smpAttr"
           ,'extraParams'=>$options['readAuth']+array("untranslatedCaption"=>"Precision")
           ,'survey_id'=>(array_key_exists('survey_id', $options) ? $options['survey_id'] : null)
           ,'sample_method_id'=>$options['subsample_method_id']), false);
      $precision = count($smpattributes)>0 ? $smpattributes[0] : false;
      $maxCellsPerRow = max($maxCellsPerRow, 2+($options['displaySampleDate']?1:0)+($precision?1:0));
    }
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $recordList)) {
      // Get the attribute and control information required to build the custom occurrence attribute columns
      self::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrCaptions, $occAttrs);
      $grid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $occAttrCaptions, $attributes, $precision);
      $grid .= '<table class="ui-widget ui-widget-content mnhnl-species-grid '.$options['class'].'" id="'.$options['id'].'">';
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
        $firstCell = data_entry_helper::mergeParamsIntoTemplate($rec['taxon'], 'taxon_label', false, true);
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
            self::_getCtrlNames($ctrlName, $oldCtrlName, false);
            // TBD need to attach a date control
            $row .= "<td class='ui-widget-content'><label class='auto-width' for='$ctrlID'>".lang::get('LANG_Date').":</label> <input type='text' id='$ctrlID' class='date' name='$ctrlName' value='".data_entry_helper::$entity_to_load[$oldCtrlName]."' /></td>";
          }
          $ctrlName=$prefix.":sample:geom";
          self::_getCtrlNames($ctrlName, $oldCtrlName, false);
          $row .= "<td class='ui-widget-content'><input type='hidden' id='$prefix:imp-geom' name='$ctrlName' value='".data_entry_helper::$entity_to_load[$oldCtrlName]."' />";
          $ctrlName=$prefix.":sample:entered_sref";
          self::_getCtrlNames($ctrlName, $oldCtrlName, false);
          $row .= "<input type='hidden' id='$prefix:imp-sref' name='$ctrlName' value='".data_entry_helper::$entity_to_load[$oldCtrlName]."' />";
          if(isset(data_entry_helper::$entity_to_load[$oldCtrlName]) && data_entry_helper::$entity_to_load[$oldCtrlName]!=""){
            $parts=explode(' ', data_entry_helper::$entity_to_load[$oldCtrlName]);
            $parts[0]=explode(',',$parts[0]);
            $parts[0]=$parts[0][0];
          } else $parts = array('', '');
          // for existing samples, don't need to specify sample_method, as it won't change.
          $row .= "<label class='auto-width' for='$prefix:imp-srefX'>".lang::get('LANG_Species_X_Label').":</label> <input type='text' id='$prefix:imp-srefX' class='imp-srefX required integer' name='dummy:srefX' value='$parts[0]' /><span class='deh-required'>*</span></td>
<td class='ui-widget-content'><label class='auto-width' for='$prefix:imp-srefY'>".lang::get('LANG_Species_Y_Label').":</label> <input type='text' id='$prefix:imp-srefY' class='imp-srefY required integer' name='dummy:srefY' value='$parts[1]'/><span class='deh-required'>*</span>
</td>";
          if ($precision) {
            //  'sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':'.$sampleAttr['fieldname']] = $sampleAttr['default'];
            // sc:<rowIdx>:<smp_id>:<ttlid>:<occ_id>:[field]";
            $ctrlId = $ctrlName = $prefix.":smpAttr:".$precision['attributeId'];
            self::_getCtrlNames($ctrlName, $oldCtrlName, true);
            $existing_value = (isset(data_entry_helper::$entity_to_load[$oldCtrlName]) ? data_entry_helper::$entity_to_load[$oldCtrlName] :
                                  (array_key_exists('default', $precision) ? $precision['default'] : ''));
            $ctrlOptions = array(
              'class'=>'scPrecision ' . (isset($precision['class']) ? ' '.$precision['class'] : ''),
              'extraParams' => $options['readAuth'],
              'suffixTemplate' => 'nosuffix',
              'language' => $options['language']
            );
            if(isset($options['lookUpKey'])) $ctrlOptions['lookUpKey']=$options['lookUpKey'];
            // if($options['useCaptionsInHeader'] || $options['useCaptionsInPreRow']) unset($attrDef['caption']);
            $precision['fieldname'] = $ctrlName;
            $precision['id'] = $ctrlId;
            $precision['default'] = $existing_value;
            //$headerPreRow .= '<td class="ui-widget-content" ><label class="auto-width">'.$occAttrCaptions[$attrId].':</label></td>';
            $row .= '<td class="ui-widget-content scSmpAttrCell">'.data_entry_helper::outputAttribute($precision, $ctrlOptions).'</td>';
          }
          if($maxCellsPerRow > (2+($options['displaySampleDate']?1:0)+($precision?1:0)))
            $row .= "<td class='ui-widget-content' colspan=".($maxCellsPerRow-(2+($options['displaySampleDate']?1:0)+($precision?1:0)))."></td>";
          $rows[]=$row."</tr>";
        }
        for($i=1; $i<=$numRows; $i++){
          $row = "";
          $headerPreRow = "";
          $numCtrls=0;
          foreach ($attributes as $attrId => $attribute) {
            if($foundRows && $attribute["inner_structure_block"] != "Row".$i) continue;
            $ctrlId = $ctrlName = $prefix.":occAttr:$attrId";
            // the control prebuild method fails for multiple value checkboxes, as each checkbox can be attached to a different attribute value record.
            if($attribute["control_type"]=='checkbox_group'){ // implies multi_value
              $attrDef = array_merge($attribute);
              // sc:<rowIdx>:<smp_id>:<ttlid>:<occ_id>:[field]";
              $ctrlArr = explode(':',$ctrlName,6);
              $default = array();
              if ($ctrlArr[4]!="") {
                $search = preg_grep("/^sc:".'[0-9]*'.":$ctrlArr[2]:$ctrlArr[3]:$ctrlArr[4]:$ctrlArr[5]".':[0-9]*$/', array_keys(data_entry_helper::$entity_to_load));
                if(count($search)>0){
                  foreach($search as $existingField){
                    $ctrlNameX = explode(':',$existingField);
                    $ctrlNameX[1]=$ctrlArr[1]; // copy row index across.
                    $ctrlNameX = implode(':', $ctrlNameX);
                    $default[] = array('fieldname'=>$ctrlNameX, 'default'=>data_entry_helper::$entity_to_load[$existingField]);
                  }
                }
              }
              // Get the control class if available. If the class array is too short, the last entry gets reused for all remaining.
              $ctrlOptions = array(
                'class'=>self::species_checklist_occ_attr_class($options, $idx, $attrDef['untranslatedCaption']) .
                  (isset($attrDef['class']) ? ' '.$attrDef['class'] : ''),
                'extraParams' => $options['readAuth'],
                'suffixTemplate' => 'nosuffix',
                'language' => $options['language']
              );
              if(isset($options['lookUpKey'])) $ctrlOptions['lookUpKey']=$options['lookUpKey'];
              if($options['useCaptionsInHeader'] || $options['useCaptionsInPreRow']) unset($attrDef['caption']);
              $attrDef['fieldname'] = $ctrlName;
              $attrDef['id'] = $ctrlId;
              if(count($default)>0) $attrDef['default'] = $default;
              $oc = data_entry_helper::outputAttribute($attrDef, $ctrlOptions);
            } else {
              // use prebuilt attribute list
              $control=$occAttrControls[$attrId];
              self::_getCtrlNames($ctrlName, $oldCtrlName, true);
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
                } else if(strpos($oc, 'radio') !== false) {
                  $oc = str_replace('value="'.$existing_value.'"','value="'.$existing_value.'" checked="checked"', $oc);
                } else if(strpos($oc, 'checkbox') !== false) {
                  if($existing_value=="1") $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
                } else {
                  $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
                }
                // assume all error handling/validation done client side
              }
            }
            $numCtrls++;
            $row .= str_replace(array('{label}', '{content}'), array($attributes[$attrId]['caption'], $oc), $indicia_templates[$options['attrCellTemplate']]);
            $headerPreRow .= '<td class="ui-widget-content" ><label class="auto-width">'.$occAttrCaptions[$attrId].':</label></td>';
          }
          if($maxCellsPerRow>$numCtrls) {
          	$row .= "<td class='ui-widget-content sg-filler' colspan=".($maxCellsPerRow-$numCtrls)."></td>";
            $headerPreRow .= "<td class='ui-widget-content sg-filler' colspan=".($maxCellsPerRow-$numCtrls)."></td>";
          }
          if($options['useCaptionsInPreRow'])
            $rows[]='<tr class="scMeaning-'.$rec['taxon']['taxon_meaning_id'].' scDataRow">'.$headerPreRow.'</tr>'; // no images.
          $rows[]='<tr class="scMeaning-'.$rec['taxon']['taxon_meaning_id'].' scDataRow'.($i==$numRows && !$options['includeOccurrenceComment'] ?' last':'').'">'.$row.'</tr>'; // no images.
        }
        if ($options['includeOccurrenceComment']) {
          $ctrlId = $ctrlName=$prefix.":occurrence:comment";
          self::_getCtrlNames($ctrlName, $oldCtrlName, false);
          if (isset(data_entry_helper::$entity_to_load[$oldCtrlName])) {
            $existing_value = data_entry_helper::$entity_to_load[$oldCtrlName];
          } else $existing_value = '';
          $rows[]="<tr class='scMeaning-".$rec['taxon']['taxon_meaning_id']." scDataRow last'>
<td class='ui-widget-content scCommentCell' $colspan>
  <label for='$ctrlId' class='auto-width'>".lang::get("Comment").":</label>
  <input type='text' class='scComment' name='$ctrlName' id='$ctrlId' value=\"".htmlspecialchars($existing_value)."\">
</td></tr>";
        }
        $rowIdx++;
      }
      $grid .= "\n<tbody>\n";
      if (count($rows)>0) $grid .= implode("\n", $rows)."\n";
      else $grid .= "<tr style=\"display: none\"><td></td></tr>\n";
      $grid .= "</tbody>\n</table>\n";
      // Javascript to add further rows to the grid
      data_entry_helper::$javascript .= "scRow=".$rowIdx.";
$('.sg-filler').each(function(idx,elem){
  var colSpan = elem.colSpan;
  var prev = $(elem).prev();
  prev[0].colSpan=colSpan+1;
  $(elem).remove();
});
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
};
bindSpeciesOptions = {selectorID: \"addTaxonControl\",
              url: \"".data_entry_helper::$base_url."index.php/services/data\",
              gridId: \"".$options['id']."\",
              lookupListId: \"".$options['speciesListID']."\",
              auth_token : \"".$options['readAuth']['auth_token']."\",
              nonce : \"".$options['readAuth']['nonce']."\",
              formatter: formatter,
              max: ".(isset($options['max_species_ids'])?$options['max_species_ids']:25)."};
";
      if(isset($options['unitSpeciesMeaning']))
          data_entry_helper::$javascript .= "bindSpeciesOptions.unitSpeciesMeaning=\"".$options['unitSpeciesMeaning']."\";\n";
      if(isset($options['rowControl'])){
        $rowControls = explode(';',$options['rowControl']);
        $controlArr = array();
        for($i=2; $i < count($rowControls); $i++){
          $parts=explode(',',$rowControls[$i],2);
          $termList = helper_base::get_termlist_terms(parent::$auth, $rowControls[1], array($parts[0]));
          $controlArr[] = '{meaning_id: "'.$termList[0]['meaning_id'].'" , rows: ['.$parts[1].']}';
        }
        data_entry_helper::$javascript .= "bindSpeciesOptions.rowControl = {selector: 'sc".str_replace(' ', '', ucWords($rowControls[0]))."',\n  controls: [".implode(',',$controlArr)."]};\n";
      }
      if(isset($options['singleSpeciesID'])){
        $fetchOpts = array(
          'table' => 'taxa_taxon_list',
          'extraParams' => array(
            'auth_token'=>$options['readAuth']['auth_token'],
            'nonce'=> $options['readAuth']['nonce'],
            'id'=>$options['singleSpeciesID'],
            'view' => 'detail',
            'taxon_list_id' => $options['speciesListID']));
        $speciesRecord = data_entry_helper::get_population_data($fetchOpts);
        $grid .= "<input id='addTaxonControl' type='button' value='".lang::get('Add another record')."'>";
        data_entry_helper::$javascript .= "
bindSpeciesOptions.speciesData = {id : \"".$options['singleSpeciesID']."\", taxon_meaning_id: \"".$speciesRecord[0]['taxon_meaning_id']."\"},
bindSpeciesButton(bindSpeciesOptions);\n";
      } else {
        $grid .= "<label for='addTaxonControl' class='auto-width'>".lang::get('Add species to list').":</label> <input id='addTaxonControl' name='addTaxonControl' >";
        data_entry_helper::$javascript .= "bindSpeciesAutocomplete(bindSpeciesOptions);\n";
      }
      // No help text
      if($options['includeSubSample']){
        $mapOptions = iform_map_get_map_options($options['args'],$options['readAuth']);
        $olOptions = iform_map_get_ol_options($options['args']);
        $mapOptions['tabDiv'] = 'tab-species';
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
        $mapOptions['standardControls']=array('layerSwitcher','panZoomBar');
        $mapOptions['fillColor']=$mapOptions['strokeColor']='Fuchsia';
        $mapOptions['fillOpacity']=0.3;
        $mapOptions['strokeWidth']=1;
        $mapOptions['pointRadius']=6;
        //      $mapOptions['maxZoom']=$args['zoomLevel'];
      }
      $r = "<div><p>".lang::get('LANG_SpeciesInstructions')."</p>\n";
      if($options['includeSubSample'] && $options['mapPosition']=='top') $r .= '<div class="topMap-container">'.data_entry_helper::map_panel($mapOptions, $olOptions).'</div>';
      $r .= '<div class="grid-container">'.$grid.'</div>';
      if($options['includeSubSample'] && $options['mapPosition']!='top') $r .= '<div class="sideMap-container">'.data_entry_helper::map_panel($mapOptions, $olOptions).'</div>';
      if($options['includeSubSample'])
      	data_entry_helper::$javascript .= "var speciesMapTabHandler = function(event, ui) {
  if (ui.panel.id=='".$mapOptions['tabDiv']."') {
    $('.mnhnl-species-grid').find('tr').removeClass('highlight');
    var div=$('#".$mapOptions['divId']."')[0];
    div.map.editLayer.destroyFeatures();
    // show the geometry currently held in the main locations part as the parent
    var initialFeatureWkt = $('#imp-boundary-geom').val();
    if(initialFeatureWkt=='') initialFeatureWkt = $('#imp-geom').val();
    var parser = new OpenLayers.Format.WKT();
    var feature = parser.read(initialFeatureWkt);
    feature=convertFeature(feature, div.map.projection);
    superSampleLocationLayer.destroyFeatures();
    superSampleLocationLayer.addFeatures((typeof(feature)=='object'&&(feature instanceof Array) ? feature : [feature]));
    var bounds=superSampleLocationLayer.getDataExtent();
    occurrencePointLayer.removeAllFeatures();
	$('.mnhnl-species-grid').find('.first').each(function(idx, elem){
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
      // assume this is within the map extent.
      div.map.zoomToExtent(bounds);
    }
  }
};
jQuery(jQuery('#".$mapOptions['tabDiv']."').parent()).bind('tabsshow', speciesMapTabHandler);\n";
      // move the cloneable table outside the form, so allowing the validation to ignore it.
      data_entry_helper::$javascript .= "var cloneableDiv = $('<div style=\"display: none;\">');
cloneableDiv.append($('#".$options['id']."-scClonable'));
$('#entry_form').before(cloneableDiv);\n";
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
        'occurrenceImages' => false,
        'id' => 'mnhnl-species-grid-'.rand(0,1000),
        'colWidths' => array(),
        'taxonFilterField' => 'none'
    ), $options);
    // If filtering for a language, then use any taxa of that language. Otherwise, just pick the preferred names.
    if (!isset($options['extraParams']['language_iso']))
      $options['extraParams']['preferred'] = 't';
    $options['extraParams']['taxon_list_id']=$options['speciesListID'];
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

  
  public static function species_checklist_prepare_attributes($options, $attributes, &$occAttrControls, &$occAttrCaptions, &$occAttrs) {
    $idx=0;
    // this sets up client side only required validation
    if (array_key_exists('required', $options))
      $requiredAttrs = explode(',',$options['required']);
    else $requiredAttrs = array();
    
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
      $occAttrCaptions[$occAttrId] = $attrDef['caption'];
      // Get the control class if available. If the class array is too short, the last entry gets reused for all remaining.
      $ctrlOptions = array(
        'class'=>self::species_checklist_occ_attr_class($options, $idx, $attrDef['untranslatedCaption']) .
            (isset($attrDef['class']) ? ' '.$attrDef['class'] : ''),
        'extraParams' => $options['readAuth'],
        'cols' => 20,
        'suffixTemplate' => 'nosuffix',
        'language' => $options['language'] // required for lists eg radio boxes: kept separate from options extra params as that is used to indicate filtering of species list by language
      );
      if(in_array($occAttrId,$requiredAttrs)) $ctrlOptions['validation'] = array('required');
      if(isset($options['lookUpKey'])) $ctrlOptions['lookUpKey']=$options['lookUpKey'];
      if(isset($options['blankText'])) $ctrlOptions['blankText']=$options['blankText'];
      if($options['useCaptionsInHeader'] || $options['useCaptionsInPreRow']) unset($attrDef['caption']);
      $attrDef['fieldname'] = '{fieldname}';
      $attrDef['id'] = '{fieldname}';
      $occAttrControls[$occAttrId] = data_entry_helper::outputAttribute($attrDef, $ctrlOptions);
      $idx++;
    }
  }
  
  public static function preload_species_checklist_occurrences($sampleId, $readAuth, $options) {
    $occurrenceIds = array();
    $sampleIds = array();
    $sampleAttrs = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the
    // data we need.
    if (is_null(data_entry_helper::$validation_errors)) {
      $extraParams = $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f');
      if($options['includeSubSample']){
        if(!isset($options['subsample_method_id']) || $options['subsample_method_id']=="")
          throw new exception('subsample_method_id must be set for this form configuration.');
        $samples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'extraParams' => $readAuth + array('view'=>'detail','parent_id'=>$sampleId,'deleted'=>'f','sample_method_id'=>$options['subsample_method_id']),
          'nocache' => true));
        foreach($samples as $sample) {
          $sampleIds[$sample['id']] = $sample;
          $sampleAttrs[$sample['id']] = data_entry_helper::getAttributes(array(
               'attrtable'=>'sample_attribute'
              ,'valuetable'=>'sample_attribute_value'
              ,'key'=>'sample_id'
              ,'fieldprefix'=>'smpAttr'
              ,'extraParams'=>$readAuth
              ,'survey_id'=>$options['survey_id']
              ,'sample_method_id'=>$options['subsample_method_id']
              ,'id'=>$sample['id']), true);
        }
        $extraParams['sample_id'] = array_keys($sampleIds);
      }
      $occurrences = data_entry_helper::get_population_data(array('table' => 'occurrence', 'extraParams' => $extraParams, 'nocache' => true));
      foreach($occurrences as $occurrence){
        if($options['includeSubSample']){
          $smp=$occurrence['sample_id'];
          data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':sample:date'] = $sampleIds[$smp]['date_start'];
          data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':sample:entered_sref'] = $sampleIds[$smp]['entered_sref'];
          data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':sample:geom'] = $sampleIds[$smp]['wkt'];
          foreach($sampleAttrs[$occurrence['sample_id']] as $sampleAttr){
            data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':'.$sampleAttr['fieldname']] = $sampleAttr['default'];
          }
        } else $smp="";
        data_entry_helper::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];
        data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
        data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
        
        data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':present'] = true;
        data_entry_helper::$entity_to_load['sc::'.$smp.':'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
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
        if(isset($attrValue['id']) && $attrValue['id'] != null)
          data_entry_helper::$entity_to_load['sc::'.($options['includeSubSample'] ? $occurrenceIds[$attrValue['occurrence_id']]['sample_id'] : '').':'.$occurrenceIds[$attrValue['occurrence_id']]['taxa_taxon_list_id'].':'.$attrValue['occurrence_id'].':occAttr:'.$attrValue['occurrence_attribute_id'].':'.$attrValue['id']]
            = $attrValue['raw_value'];
      }
      if($options['includeSubSample']){
        data_entry_helper::$javascript .= "
mapInitialisationHooks.push(function(mapdiv) {
  // try to identify if this map is the second one
  if(mapdiv.id=='map2'){
    var occParser = new OpenLayers.Format.WKT();
    var occFeatures=[];
    var feature;\n";
        foreach($occurrences as $occurrence){
          $smp=$occurrence['sample_id'];
          data_entry_helper::$javascript .= "    feature = occParser.read('".$sampleIds[$smp]['wkt']."');
    feature = convertFeature(feature,occurrencePointLayer.map.projection);
    $('.scOcc-".$occurrence['id']."').data('feature',feature);
    occFeatures.push(feature);\n";
        }
        data_entry_helper::$javascript .= "    occurrencePointLayer.addFeatures(occFeatures);
  }});\n";
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
  private static function get_species_checklist_clonable_row($options, $occAttrControls, $occAttrCaptions, $attributes, $precision) {
    global $indicia_templates;
    // assume always removeable and presence is hidden.
    // DEBUG/DEV MODE
//     $r = '<table border=3 id="'.$options['id'].'-scClonable">';
//     $hiddenCTRL = "text";
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
    if($options['includeSubSample'])
      $maxCellsPerRow = max($maxCellsPerRow, 2+($options['displaySampleDate']?1:0)+($precision?1:0));
    $idex=1;
    $prefix = "sc:--GroupID--:--SampleID--:--TTLID--:--OccurrenceID--";
    $r .= '<tbody><tr class="scClonableRow first" id="'.$options['id'].'-scClonableRow'.$idex.'"><td class="ui-state-default remove-row" rowspan="'.$options['numRows'].'">X</td>';
    $colspan = ' colspan="'.$maxCellsPerRow.'"';
    $r .= str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']).
        '<td class="scPresenceCell" style="display:none"><input type="hidden" class="scPresence" name="" value="false" /></td></tr>';
    if($options['includeSubSample']){
      if(!isset($options['subsample_method_id']) || $options['subsample_method_id']=="")
        throw new exception('subsample_method_id must be set for this form configuration.');
      $idex++;
      $r .= '<tr class="scClonableRow scDataRow" id="'.$options['id'].'-scClonableRow'.$idex.'">';
      if ($options['displaySampleDate']) {
        $r .= "<td class='ui-widget-content'><label class=\"auto-width\" for=\"$prefix:sample:date\">".lang::get('LANG_Date').":</label> <input type=\"text\" id=\"$prefix:sample:date\" class=\"date\" name=\"$prefix:sample:date\" value=\"\" /></td>";
      }
      $r .= "<td class='ui-widget-content'><input type=\"$hiddenCTRL\" id=\"sg---GroupID---imp-sref\" name=\"$prefix:sample:entered_sref\" value=\"\" />
<input type=\"$hiddenCTRL\" id=\"sg---GroupID---imp-geom\" name=\"$prefix:sample:geom\" value=\"\" />
<input type=\"$hiddenCTRL\" name=\"$prefix:sample:sample_method_id\" value=\"".$options['subsample_method_id']."\" />
<label class=\"auto-width\" for=\"sg---GroupID---imp-srefX\">".lang::get('LANG_Species_X_Label').":</label> <input type=\"text\" id=\"sg---GroupID---imp-srefX\" class=\"imp-srefX integer required\" name=\"dummy:srefX\" value=\"\" /><span class='deh-required'>*</span></td>
<td class='ui-widget-content'><label class=\"auto-width\" for=\"sg---GroupID---imp-srefY\">".lang::get('LANG_Species_Y_Label').":</label> <input type=\"text\" id=\"sg---GroupID---imp-srefY\" class=\"imp-srefY integer required\" name=\"dummy:srefY\" value=\"\"/><span class='deh-required'>*</span></td>";
      if ($precision) {
      	$ctrlId = $prefix.":smpAttr:".$precision['attributeId'];
      	$ctrlOptions = array(
      			'class'=>'scPrecision ' . (isset($precision['class']) ? ' '.$precision['class'] : ''),
      			'extraParams' => $options['readAuth'],
      			'suffixTemplate' => 'nosuffix',
      			'language' => $options['language']
      	);
      	if(isset($options['lookUpKey'])) $ctrlOptions['lookUpKey']=$options['lookUpKey'];
      	// if($options['useCaptionsInHeader'] || $options['useCaptionsInPreRow']) unset($attrDef['caption']);
      	$precision['fieldname'] = $ctrlId;
      	$precision['id'] = $ctrlId;
      	//$headerPreRow .= '<td class="ui-widget-content" ><label class="auto-width">'.$occAttrCaptions[$attrId].':</label></td>';
      	$row .= "<td class='ui-widget-content'>".data_entry_helper::outputAttribute($precision, $ctrlOptions)."' /></td>";
      }
      if($maxCellsPerRow>2+($options['displaySampleDate']?1:0)+($precision?1:0))
        $r .= "<td class='ui-widget-content' colspan=".($maxCellsPerRow-(2+($options['displaySampleDate']?1:0)+($precision?1:0)))."></td>";
      $r .="</tr>";
    }
    $idx = 0;
    
    for($i=1; $i<=$numRows; $i++){
      $numCtrls=0;
      $row='';
      $headerPreRow='';
      foreach ($attributes as $attrId => $attribute) {
        $control=$occAttrControls[$attrId];
        if($foundRows && $attribute["inner_structure_block"] != "Row".$i) continue;
        $ctrlId=$prefix.":occAttr:".$attrId;
        $oc = str_replace('{fieldname}', $ctrlId, $control);
        if (isset($attributes[$attrId]['default']) && !empty($attributes[$attrId]['default'])) {
          $existing_value=$attributes[$attrId]['default'];
          if (substr($oc, 0, 7)=='<select') { // For select controls, specify which option is selected from the existing value
            $oc = str_replace('value="'.$existing_value.'"', 'value="'.$existing_value.'" selected="selected"', $oc);
          } else if(strpos($oc, 'radio') !== false) {
            $oc = str_replace('value="'.$existing_value.'"','value="'.$existing_value.'" checked="checked"', $oc);
          } else if(strpos($oc, 'checkbox') !== false) {
            if($existing_value=="1") $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
          } else {
            $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
          }
        }
        $numCtrls++;
        $row .= str_replace(array('{label}', '{content}', '{class}'),
          array(lang::get($attributes[$attrId]['caption']),
            str_replace('{fieldname}', "$prefix:occAttr:$attrId", $oc),
            self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['untranslatedCaption']).'Cell'),
          $indicia_templates['attribute_cell']);
        $headerPreRow .= '<td class="ui-widget-content" ><label class="auto-width">'.$occAttrCaptions[$attrId].':</label></td>';
      }
      $idx++;
      if($maxCellsPerRow>$numCtrls){
      	$headerPreRow .= "<td class='ui-widget-content sg-filler' colspan=".($maxCellsPerRow-$numCtrls)."></td>";
      	$row .= "<td class='ui-widget-content sg-filler' colspan=".($maxCellsPerRow-$numCtrls)."></td>";
      }
      if($options['useCaptionsInPreRow']){
      	$idex++;
      	$r .='<tr class="scClonableRow scDataRow" id="'.$options['id'].'-scClonableRow'.$idex.'">'.$headerPreRow.'</tr>';
      }
      $idex++;
      $r .='<tr class="scClonableRow scDataRow'.($i==$numRows && !$options['includeOccurrenceComment'] ? ' last':'').'" id="'.$options['id'].'-scClonableRow'.$idex.'">'.$row.'</tr>'; // no images.
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
    // unset($extraTaxonOptions['extraParams']['taxon_list_id']);
    unset($extraTaxonOptions['extraParams']['preferred']);
    unset($extraTaxonOptions['extraParams']['language_iso']);
    unset($extraTaxonOptions['nocache']);
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
          $smp['model']['fields']['sample_method_id'] = array('value' => $values['target_species_subsample_method_id']);
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
      if ($a[0]=='sc' && $a[1]!='' && $a[1]!='--GroupID--'){
        $b = explode(':', $a[5]);
        if($a[1]){ // key on the Group ID
          $occurrences[$a[1]]['taxa_taxon_list_id'] = $a[3];
          if($b[0]=='sample' || $b[0]=='smpAttr') {
            $samples[$a[1]][$a[5]] = $value;
          } else {
            // for a multiple entry checkbox group, need to remove the sc:--GroupID--:--SampleID--:--TTLID--:--OccurrenceID-- to give value:occAttr:value[:value]
            $newvalue = $value;
            if(is_array($value)) {
              $newvalue = array();
              foreach($value as $X){
                $tokens = explode(':', $X, 7);
                $newvalue[] = (count($tokens)==7 ? $tokens[0].':'.$tokens[6] : $X);
              }
            }
            $occurrences[$a[1]][$a[5]] = $newvalue;
          }
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
          if(!isset($samples[$id]['date'])) $smp['copyFields'] = array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type'); // from parent->to child
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