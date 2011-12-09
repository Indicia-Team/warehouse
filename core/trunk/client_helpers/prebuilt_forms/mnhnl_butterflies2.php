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
 * Should be set up in "wizard" buttons mode
 * TBD: extend conditions grid to allow specification of column widths.
 */

require_once('mnhnl_dynamic_1.php');
require_once('includes/mnhnl_common.php');

class iform_mnhnl_butterflies2 extends iform_mnhnl_dynamic_1 {
  protected static $svcUrl;
  protected static $locationsInGrid;
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_butterflies2_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Butterflies de Jour form. Inherits from Dynamic 1.'
    );
  }
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Butterflies de Jour';  
  }

  public static function get_parameters() {    
    $retVal=array();
    $parentVal = array_merge(
      parent::get_parameters(),
      iform_mnhnl_getParameters(),
      array(
        array(
          'name'=>'max_species_ids',
          'caption'=>'max number of species to be returned by a search',
          'description'=>'The maximum number of species to be returned by the drop downs at any one time.',
          'default'=>25,
          'type'=>'int'
        ),
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
             "=Sites=\r\n".
              "[lux5kgrid2]\r\n".
              "[map]\r\n".
              "@layers=[\"ParentLocationLayer\",\"SitePointLayer\",\"SitePathLayer\",\"SiteAreaLayer\",\"SiteLabelLayer\"]\r\n".
              "@editLayer=false\r\n".
              "@scroll_wheel_zoom=false\r\n".
              "@searchUpdatesSref=true\r\n".
              "[sample comment]\r\n".
             "=Conditions=\r\n".
              "[recorder names]\r\n".
              "[*]\r\n".
              "@sep= \r\n".
              "@lookUpKey=meaning_id\r\n".
              "[year]\r\n".
              "@boltTo=passage\r\n".
              "[conditions grid]\r\n".
              "@sep= \r\n".
              "@lookUpKey=meaning_id\r\n".
              "@tabNameFilter=ConditionsGrid\r\n".
              "@setColumnsRequired=2:3:4:9\r\n".
             "=Species=\r\n".
              "[species grid]\r\n".
              "[*]\r\n".
              "[late JS]";
      }
      if($param['name'] == 'attribute_termlist_language_filter')
        $param['default'] = true;
      if($param['name'] == 'grid_report')
        $param['default'] = 'reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies2';
        
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
  	// TBD add check for loctools 
    global $indicia_templates;
  	$isAdmin = user_access('IForm n'.self::$node->nid.' admin');
  	if(!$isAdmin) return('');
  	if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'), '#locations' => lang::get('LANG_Locations'));
    $retVal = '<div id="downloads" >
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies2_conditions_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=downloadconditions">
      <p>'.lang::get('LANG_Conditions_Report_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
	<form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies2_species_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=downloadoccurrences">
      <p>'.lang::get('LANG_Occurrence_Report_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"survey_id":'.$args['survey_id'].'}\' />
      <input type="submit" class="ui-state-default ui-corner-all" value="'.lang::get('LANG_Download_Button').'">
    </form>
  </div>'.iform_mnhnl_locModTool(self::$auth, $args, self::$node);
    return $retVal;
  }
  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
  	global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    if ($user->uid===0) return lang::get('LANG_Please_Login').'<a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">'.lang::get('LANG_Login').'</a>';
    $userIdAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS User ID');
    if (!$userIdAttr) return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can be tagged against their creator.');
    $userNameAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'CMS Username');
    if (!$userNameAttr) return lang::get('This form must be used with a survey that has the CMS User Name attribute associated with it so records can be tagged against their creator.');
    $passageAttr=iform_mnhnl_getAttrID($auth, $args, 'sample', 'Passage');
    if (!$passageAttr) return lang::get('This form must be used with a survey that has the Passage attribute associated with it.');    
      
    if (isset($args['grid_report'])) $reportName = $args['grid_report'];
    else // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/MNHNL/mnhnl_butterflies';
    $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 25),
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID_attr_id'=>$userIdAttr,
        'userID'=>(iform_loctools_checkaccess($node,'superuser') ? -1 :  $user->uid), // use -1 if superuser - non logged in will not get this far.
        'userName_attr_id'=>$userNameAttr
       ,'passage_attr_id'=>$passageAttr
        )));	
    $r .= '<form>';    
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= "</form>
<div style=\"display:none\" />
    <form id=\"form-delete-survey\" method=\"POST\">".self::$auth['write']."
       <input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />
       <input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />
       <input type=\"hidden\" name=\"sample:id\" value=\"\" />
       <input type=\"hidden\" name=\"sample:deleted\" value=\"t\" />
    </form>
</div>";
    data_entry_helper::$javascript .= "
deleteSurvey = function(sampleID){
  if(confirm(\"".lang::get('LANG_ConfirmSurveyDelete')." \"+sampleID)){
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
  
  protected static function get_control_lux5kgrid2($auth, $args, $tabalias, $options) {
    // can only change the location for a new sample: fixed afterwards. *
    global $indicia_templates;
    $countAttr = iform_mnhnl_getAttrID($auth, $args, 'occurrence','Count');
    if (!$countAttr) return lang::get('This form must be used with a survey that has the Count Occurrence attribute associated with it.');
    $noObAttr = iform_mnhnl_getAttrID($auth, $args, 'sample','No observation');
    if (!$noObAttr) return lang::get('This form must be used with a survey that has the No observation Sample attribute associated with it.');

    data_entry_helper::$javascript .= "
// because the fetch is generic, we can't guarentee that the sort order will be numeric eg name 2 comes after 10.
hook_loadLocation= function(feature) {
  if(feature.attributes.new) setNameDropDowns(false, false);
  else setNameDropDowns(true, false);
}
createGridEntries = function(feature, isnew) {
  cgRowNum++;
  var mySiteNum=false;
  var name = '';
  if(typeof(feature)=='object'&&(feature instanceof Array)){
    mySiteNum = feature[0].attributes.SiteNum;
  } else {
    mySiteNum = feature.attributes.SiteNum;
  }
  var newCGrow = jQuery('.cgCloneableRow').clone().removeClass('cgCloneableRow').addClass(isnew ? 'cgAddedRow':'cggrid-row').data('cgRowNum', cgRowNum).data('SiteNum', mySiteNum);
  newCGrow.find('td:not(.cggrid-datecell,.cggrid-namecell,.remove-cgnewrow)').css('opacity',0.25);
  newCGrow.find('*:not(.cggrid-date,.cggrid-datecell,.cggrid-name,.cggrid-namecell,.remove-cgnewrow)').attr('disabled','disabled');
  if(!isnew){
    if(typeof(feature)=='object'&&(feature instanceof Array)){
      myID = feature[0].attributes.data.id;
      name = feature[0].attributes.data.name;
    } else {
      myID = feature.attributes.data.id;
      name = feature.attributes.data.name;
    }
    var fieldname=newCGrow.find('.cggrid-name').attr('name');
    newCGrow.find('.cggrid-namecell').empty().append('<input name=\"'+fieldname+'\" class=\"cggrid-name narrow\" value=\"'+name+'\" readonly=\"readonly\" ><input type=\"hidden\" name=\"CG:--rownum--:--sampleid--:location_id\" value=\"'+myID+'\" >');
    //  cggrid-centroid_sref,cggrid-centroid_geom,cggrid-boundary_geom,cggrid-location_type_id are all removed by the cggrid-name empty above
    newCGrow.find('.remove-cgnewrow').removeClass('remove-cgnewrow').addClass('clear-cgrow');
  }
  jQuery.each(newCGrow.children(), function(i, cell) {cell.innerHTML = cell.innerHTML.replace(/--rownum--/g, cgRowNum);});
  if(isnew){
    jQuery('#dummy-name').find('option').each(function (index, option){
      if(name == '' && jQuery('.cggrid-row,.cgAddedRow').find('.cggrid-name').filter('[value='+jQuery(option).val()+']').length == 0)
        name=jQuery(option).val();
    });
    newCGrow.find('.cggrid-name').val(name);
  }
  var insertPoint=false;
  insertCount=0; // we'll assume that the existing entries are in numerical order
  jQuery('#conditions-grid').find('tr').each(function(index,elem){
    if(jQuery(elem).find('.cggrid-name').length == 0 || 
        parseInt(jQuery(elem).find('.cggrid-name').val()) < parseInt(name)){
      insertCount++;
      insertPoint = jQuery(elem);
    }
  });
  insertCount--;
  newCGrow.insertAfter(insertPoint);
  newCGrow.find('.cggrid-date').datepicker({dateFormat : 'dd/mm/yy', changeMonth: true, changeYear: true, constrainInput: false, maxDate: '0', onClose: function() { $(this).valid(); }});
  recalcNumSites();
  // Species grid 1) add to header, 2) add to cloneable row, 3) add to existing rows
  insertPoint=jQuery('#species-grid-header').children(':eq('+insertCount+')');
  jQuery('<th class=\"smp-'+cgRowNum+'\">'+name+'</th>').css('opacity',0.25).insertAfter(insertPoint);
  jQuery('.sgNoObRow').each(function(i, Row) {
    insertPoint=jQuery(Row).children(':eq('+insertCount+')');
    var newNoObCell = jQuery('<td class=\"smp-'+cgRowNum+'\">'+
      '<input type=\"hidden\" name=\"CG:'+cgRowNum+':--sampleid--:smpAttr:".$noObAttr."\" value=\"0\" \"/>'+
      '<input type=\"checkbox\" name=\"CG:'+cgRowNum+':--sampleid--:smpAttr:".$noObAttr."\" value=\"1\" class=\"cgAttr\" disabled=\"disabled\" />'+
      '</td>').css('opacity',0.25).insertAfter(insertPoint);
    newNoObCell.find(':checkbox').rules('add', {no_observation: cgRowNum});
  });
  insertCount++;// double cells at start for these rows.
  insertPoint=jQuery('.sgCloneableRow').children(':eq('+insertCount+')');
  jQuery('<td class=\"smp-'+cgRowNum+'\"><input class=\"digits narrow\" name=\"SG:'+cgRowNum+':--sampleid--:--ttlid--:--occid--:occAttr:".$countAttr."\" disabled=\"disabled\" min=\"1\"></td>').css('opacity',0.25).insertAfter(insertPoint);
  jQuery('.sgAddedRow,.sgOrigRow').each(function(i, Row) {
    insertPoint=jQuery(Row).children(':eq('+insertCount+')');
    jQuery('<td class=\"smp-'+cgRowNum+'\"><input class=\"digits narrow\" name=\"SG:'+cgRowNum+':--sampleid--:'+jQuery(Row).data('ttlid')+':--occid--:occAttr:".$countAttr."\" disabled=\"disabled\" min=\"1\"></td>').css('opacity',0.25).insertAfter(insertPoint);
  });
  return name;
};
moveGridEntries = function(cgRowNum) {
  var oldPosition=false;
  var newPosition=false;
  var name;
  jQuery('#conditions-grid').find('tr').each(function(index,elem){
    if(jQuery(elem).data('cgRowNum')==cgRowNum){
      name = jQuery(elem).find('.cggrid-name').val(); // has been updated to new value.
      oldPosition=index;
    }});
  jQuery('#conditions-grid').find('tr').each(function(index,elem){
    if(index != 0 && index != oldPosition && parseInt(jQuery(elem).find('.cggrid-name').val()) < parseInt(name)){
      newPosition=index; // points to row we insert after.
    }});
  if(newPosition==oldPosition-1) return;
  var insertPoint=jQuery('#conditions-grid').find('tr:eq('+newPosition+')');
  jQuery('#conditions-grid').find('tr:eq('+oldPosition+')').insertAfter(insertPoint);
  // Species grid 1) add to header, 2) add to cloneable row, 3) add to existing rows
  jQuery('#species-grid-header,.sgNoObRow').each(function(i, Row) {
    insertPoint=jQuery(Row).children(':eq('+newPosition+')');
    insertPoint=jQuery(Row).children(':eq('+oldPosition+')').insertAfter(insertPoint);
  });
  jQuery('.sgCloneableRow,.sgAddedRow,.sgOrigRow').each(function(i, Row) {
    insertPoint=jQuery(Row).children(':eq('+(newPosition+1)+')');
    insertPoint=jQuery(Row).children(':eq('+(oldPosition+1)+')').insertAfter(insertPoint);
  });
};

hook_ChildFeatureLoad = function(feature, data, child_id, options){
  // this is a multisite, so child_id will never be filled in.
  if(!options.initial){
    var mySiteNum=false;
    if(typeof(feature)=='object'&&(feature instanceof Array)){
      mySiteNum = feature[0].attributes.SiteNum;
    } else {
      mySiteNum = feature.attributes.SiteNum;
    }
    createGridEntries(feature, false);
    var allFeatures = SiteAreaLayer.features.concat(SitePathLayer.features,SitePointLayer.features,SiteLabelLayer.features);
    for(var i=0; i< allFeatures.length; i++){ // need to get the label as well
      if(typeof allFeatures[i].attributes.SiteNum != 'undefined' &&
          allFeatures[i].attributes.SiteNum == mySiteNum){
        allFeatures[i].attributes.cgRowNum = cgRowNum;
      }}
  }
  setNameDropDowns(false, false);
}
hook_mnhnl_parent_changed = function(){
  jQuery('#conditions-grid').find('tr').not(':eq(0)').remove();
  jQuery('#species-grid').find('tr').not(':eq(0)').not('.sgNoObRow').remove();
  jQuery('#species-grid').find('th').not(':eq(0)').remove();
  jQuery('#species-grid').find('td').not(':eq(0)').remove();
  jQuery('.sgCloneableRow').find('td:gt(1)').remove();
};";
    $retVal = iform_mnhnl_lux5kgridControl($auth, $args, self::$node, array_merge(
      array('initLoadArgs' => '{initial: true}',
       'canCreate'=>true
       ), $options));
    return $retVal;
  }

 /**
   * Get the recorder names control
   */
  protected static function get_control_recordernames($auth, $args, $tabalias, $options) {
    return iform_mnhnl_recordernamesControl(self::$node, $auth, $args, $tabalias, $options);
  }
    
  /**
   * Get the year names control
   */
  protected static function get_control_year($auth, $args, $tabalias, $options) {
    if($args['language'] != 'en')
      data_entry_helper::add_resource('jquery_ui_'.$args['language']); // this will autoload the jquery_ui resource. The date_picker does not have access to the args.
    if (isset(data_entry_helper::$entity_to_load['sample:date']))
      $year=substr(data_entry_helper::$entity_to_load['sample:date'],0,4);
    else $year = date('Y');
    $startYear = isset($options['startYear']) ? $options['startYear'] : 2009;
    $retVal = "<label for='sample:date'>".lang::get('Year').":</label><select id='sample:date' name='sample:date' class='required' >";
    //    <input type='text' size='4' class='required digits' min='2000' id='sample:date' name='sample:date' value='".$year."' /><span class='deh-required'>*</span><br/>";
    while($startYear <= date('Y')){
	    $retVal .= '<option '.($startYear == $year ? 'selected=\\"selected\\"' : '').'>'.$startYear.'</option>';
		$startYear++;
	}
	$retVal .= "</select><span class='deh-required'>*</span><br/>";
    if(!isset($options['boltTo']))
      return $retVal;
    data_entry_helper::$javascript .= '
jQuery("#fieldset-'.$options['boltTo'].'").find("legend").after("'.$retVal.'");';
    return '';
  }
  
  private static function getLocationsInGrid($auth, $args)
  {
    if(isset(self::$locationsInGrid)) return self::$locationsInGrid;
    if(isset(data_entry_helper::$entity_to_load["sample:updated_by_id"])){ // only set if data loaded from db, not error condition
      $url = data_entry_helper::$base_url."/index.php/services/data/sample?parent_id=".data_entry_helper::$entity_to_load['sample:id']."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $sampleEntities = json_decode(curl_exec($session), true);
      // primary only location type: not secondary
      $LocationTypeID = iform_mnhnl_getTermID(self::$auth, $args['locationTypeTermListExtKey'],$args['LocationTypeTerm']);
      $url = data_entry_helper::$base_url."/index.php/services/data/location?parent_id=".data_entry_helper::$entity_to_load['sample:location_id']."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&location_type_id=".$LocationTypeID;
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $locationEntities = json_decode(curl_exec($session), true);
      self::$locationsInGrid=array();
      // merge the locations and existing subsamples lists
      if (isset($locationEntities))
        foreach($locationEntities as $entity){
          self::$locationsInGrid[intval($entity['name'])] = array('location_id'=>$entity['id'], 'name'=>$entity['name']);
        }
      if (isset($sampleEntities))
        foreach($sampleEntities as $entity){
          $id=intval($entity['location_name']);
          if(!isset(self::$locationsInGrid[$id]))
            self::$locationsInGrid[$id] = array('location_id'=>$entity['location_id']);
          self::$locationsInGrid[$id]['name'] = $entity['location_name'];
          self::$locationsInGrid[$id]['date'] = $entity['date_start'];
          self::$locationsInGrid[$id]['comment'] = $entity['comment'];
          self::$locationsInGrid[$id]['sample_id'] = $entity['id'];
        }
      ksort(self::$locationsInGrid);
      self::$locationsInGrid = array_values(self::$locationsInGrid);
    }
    return self::$locationsInGrid;
  }
  
  protected static function get_control_speciesgrid($auth, $args, $tabalias, $options) {
  	/* We will make the assumption that only one of these will be put onto a form.
  	 * A lot of this is copied from the species control and has the same features. */
    $countAttr = iform_mnhnl_getAttrID($auth, $args, 'occurrence','Count');
    if (!$countAttr) return lang::get('This form must be used with a survey that has the Count Occurrence attribute associated with it.');
    $subsamples = self::getLocationsInGrid($auth, $args);
    $ret = '<p>'.lang::get("LANG_SpeciesGridInstructions").'</p><table style="display:none">';
    $cloneprefix='SG:--rownum--:--sampleid--:--ttlid--:--occid--:';
    $ret .= "<tr class=\"sgCloneableRow\">
<td class=\"ui-state-default remove-sgnewrow\" style=\"width: 1%\">X</td><td class=\"sggrid-namecell\"></td>";
    $taxonList = array();
    if (isset($subsamples))
      foreach($subsamples as $key => $entity){
        $ret .= str_replace(array('--rownum--', '--sampleid--'),
                         array($key+1, $entity['sample_id']),
                         '<td class="smp---rownum--" '.(isset($entity['date']) ? '' : 'style="opacity: 0.25"').'><input class="digits narrow" name="'.$cloneprefix.'occAttr:'.$countAttr.'" '.(isset($entity['date']) ? '' : 'disabled="disabled"').' min="1" ></td>');
        if(isset($entity['sample_id'])){
          $url = data_entry_helper::$base_url."/index.php/services/data/occurrence?sample_id=".$entity['sample_id']."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $occs = json_decode(curl_exec($session), true);
          $occList = array();
          $subsampleOccs = array();
          foreach($occs as $an_occ) {
            $occList[] = $an_occ['id'];
            $taxonList[$an_occ['taxa_taxon_list_id']]=true;
            $subsampleOccs[$an_occ['taxa_taxon_list_id']]=$an_occ; // this indexes the occurrences for the subsample by taxa_taxon_list_id
          }
          $subsamples[$key]['occurrences'] = $subsampleOccs;
          $url = data_entry_helper::$base_url."/index.php/services/data/occurrence_attribute_value?query=".json_encode(array('in' => array('occurrence_id', $occList)))."&mode=json&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $occAttributes = json_decode(curl_exec($session), true);
          $subsampleOccAttrs = array();
          foreach($occAttributes as $an_occAttr) {
            if($an_occAttr['occurrence_attribute_id'] == $countAttr)
              $subsampleOccAttrs[$an_occAttr['occurrence_id']]=$an_occAttr;
          }
          $subsamples[$key]['occattrs'] = $subsampleOccAttrs;
        } else $subsamples[$key]['occurrences'] = array();
      }
    $ret .= '</table>';
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
          'label'=>lang::get('speciesgrid:taxa_taxon_list_id'),
          'fieldname'=>'speciesgrid_taxa_taxon_list_id',
          'id'=>'speciesgrid_taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,          
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams,
          'numValues'=>$args['max_species_ids']
    ), $options);
    // do not allow tree browser
    if ($args['species_ctrl']=='tree_browser')
      return '<p>Can not use tree browser in this context</p>';
    $ret .= '<div>'.call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args).'</div>';
    $ret .= '<table id="species-grid"><tr id="species-grid-header"><th colSpan=2>'.lang::get('Species').'</th>';
    if (isset($subsamples))
      foreach($subsamples as $key => $entity){
        $ret .= '<th class="smp-'.($key+1).'" '.(isset($entity['date']) ? '' : 'style="opacity: 0.25"').'>'.$entity['name'].'</th>';
      }
    $ret .= '</tr>';
    $taxonRow=0;
    foreach($taxonList as $ttlid=>$disgard){
      $url = data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list?id=".$ttlid."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $taxon = json_decode(curl_exec($session), true);
      $url = data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list?taxon_meaning_id=".$taxon[0]['taxon_meaning_id']."&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"];
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $allnames = json_decode(curl_exec($session), true);
      $name = '';
      foreach($allnames as $aname){
        if($aname['preferred'] == 't')
          $name = '<em>'.$aname['taxon'].'</em>'.($name!=''?', ':'').$name;
        else
          $name = $name.($name!=''?', ':'').$aname['taxon'];
      }
      $taxonRow++;
      data_entry_helper::$javascript .= "
jQuery('#species-grid').find('tr:eq(".$taxonRow.")').data('ttlid', ".$ttlid.").data('meaning_id', ".$taxon[0]['taxon_meaning_id'].");";
      $ret .= '
<tr class="sgOrigRow"><td class="ui-state-default clear-sgrow" style="width: 1%">X</td><td class="sggrid-namecell">'.$name.'</td>';
      foreach($subsamples as $key => $entity){
        $template = '<td class="smp---rownum--" '.(isset($entity['date']) ? '' : 'style="opacity: 0.25"').'><input class="digits narrow" name="'.$cloneprefix.'occAttr:'.$countAttr.'--attrid--" '.(isset($entity['date']) ? '' : 'disabled="disabled"').' value="--value--" min="1"></td>';
        if(isset($entity['occurrences'][$ttlid])){
          $occid=$entity['occurrences'][$ttlid]['id'];
          $ret .= str_replace(array('--rownum--', '--sampleid--','--ttlid--','--occid--','--attrid--','--value--'),
                         array($key+1, $entity['sample_id'], $ttlid, $occid, isset($entity['occattrs'][$occid]) ? ':'.$entity['occattrs'][$occid]['id'] : '', isset($entity['occattrs'][$occid]) ? $entity['occattrs'][$occid]['value'] : ''),
                         $template);
        } else
          $ret .= str_replace(array('--rownum--', '--sampleid--','--ttlid--','--attrid--','--value--'),
                         array($key+1, isset($entity['sample_id']) ? $entity['sample_id'] : '', $ttlid, '',''),
                         $template);
      }
      $ret .= '</tr>';
    }
    $ret .= '<tr class="sgNoObRow" ><td colspan=2>'.lang::get('No observation').'</td>';
    if (isset($subsamples))
      foreach($subsamples as $key => $entity){
      // pretend that the no observations are actually part of the conditions grid.
        $attrArgs = array(
         'valuetable'=>'sample_attribute_value',
         'attrtable'=>'sample_attribute',
         'key'=>'sample_id',
         'fieldprefix'=>'smpAttr',
         'extraParams'=>$auth['read'],
         'survey_id'=>$args['survey_id']
        );
        $defAttrOptions = array_merge(
              array('cellClass' => 'smp-'.($key+1),
                'class' => 'cgAttr',
                'extraParams' => array_merge($auth['read'], array('view'=>'detail')),
                'language' => iform_lang_iso_639_2($args['language'])),$options);
        if(isset($entity['sample_id'])) $attrArgs['id'] = $entity['sample_id'];
        $attrArgs['fieldprefix']='CG:'.($key+1).':'.(isset($entity['sample_id']) ? $entity['sample_id'] : '').':smpAttr';
        $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
        $ret .= self::get_sample_attribute_html($sampleAttributes, $args, $defAttrOptions, 'Species');
        // validation has to be put on hidden element in the checkbox group/
       data_entry_helper::$late_javascript .= "
jQuery('.sgNoObRow').find(':checkbox:eq(".$key.")').rules('add', {no_observation: ".($key+1)."});";
      }
    $ret .= '</tr></table>';
    // remembering that validation for checkbox is actually called on the hidden element, not the checkbox itself.
    data_entry_helper::$late_javascript .= "
$.validator.addMethod('no_observation', function(value, element, params){
  if(jQuery('[name='+jQuery(element).attr('name')+']').not(':hidden').filter('[disabled]').length>0) return true;
  var numFilledIn = jQuery('.smp-'+params).find('.digits').filter('[value!=]').length;
  if(jQuery('[name='+jQuery(element).attr('name')+']').not(':hidden').filter('[checked]').length>0)
    return(numFilledIn==0);
  else
    return(numFilledIn>0);
}, \"".lang::get('validation_no_observation')."\");
";

  data_entry_helper::$javascript .= "
jQuery('#speciesgrid_taxa_taxon_list_id').change(function(){
  jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list/\" +jQuery('#speciesgrid_taxa_taxon_list_id').val()+
    \"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?\",
    function(tdata) {
      jQuery('#speciesgrid_taxa_taxon_list_id\\\\:taxon').val('');
      if (tdata.length>0) {
        found=false;
        jQuery('#species-grid').find('tr').each(function(i, row){
          if(tdata[0].taxon_meaning_id == jQuery(row).data('meaning_id'))
            found=true;
        });
        if(found){
          alert(\"".lang::get('speciesgrid:rowexists')."\");
          return;
        }
        newSGrow = jQuery('.sgCloneableRow').clone().removeClass('sgCloneableRow').addClass('sgAddedRow');
        jQuery.each(newSGrow.children(), function(i, cell) {
          cell.innerHTML = cell.innerHTML.replace(/--ttlid--/g, tdata[0].id);
        });
        newSGrow.find('.sggrid-namecell').append(tdata[0].taxon);
        newSGrow.data('ttlid',tdata[0].id).data('meaning_id',tdata[0].taxon_meaning_id).insertBefore('.sgNoObRow');
        jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list\" +
            \"?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&taxon_meaning_id=\" + tdata[0].taxon_meaning_id + \"&callback=?\",
          function(ldata) {
            if (ldata.length>0) {
              var name = '';
              for(var i=0; i< ldata.length; i++){
                if(ldata[i].preferred == 't')
                  name = '<em>'+ldata[i].taxon+'</em>'+(i>0?', ':'')+name;
                else
                  name = name+(i>0?', ':'')+ldata[i].taxon;
              }
              jQuery('#species-grid').find('tr').each(function(i, row){
                if(ldata[0].taxon_meaning_id == jQuery(row).data('meaning_id'))
                  jQuery(row).find('.sggrid-namecell').empty().append(name);});
            }});
        }});
});
jQuery('.clear-sgrow').live('click', function() {
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_speciesgrid:clearconfirm')."\")) return;
  thisRow.find('*').removeClass('ui-state-error');
  thisRow.find(':text').val('');
  thisRow.find('.inline-error').remove();
});
jQuery('.remove-sgnewrow').live('click', function() {
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_speciesgrid:removeconfirm')."\")) return;
  thisRow.remove();
});";
    return $ret;
  }
  
  protected static function get_control_conditionsgrid($auth, $args, $tabalias, $options) {
  	/* We will make the assumption that only one of these will be put onto a form.
  	 * A lot of this is copied from the species control and has the same features. */
    data_entry_helper::$javascript .= "
///////////////////////////////////////
// Functions for the conditions grid //
///////////////////////////////////////
";
  	$extraParams = $auth['read'] + array('view' => 'detail', 'reset_timeout' => 'true');
    // A single species entry control of some kind
    $attrArgs = array(
       'valuetable'=>'sample_attribute_value',
       'attrtable'=>'sample_attribute',
       'key'=>'sample_id',
       'fieldprefix'=>'smpAttr',
       'extraParams'=>$auth['read'],
       'survey_id'=>$args['survey_id']
      );
    $defAttrOptions = array_merge(
              array('class' => 'cgAttr',
                'extraParams' => array_merge($auth['read'], array('view'=>'detail')),
                'language' => iform_lang_iso_639_2($args['language'])),$options);
    $tabName = (isset($options['tabNameFilter']) ? $options['tabNameFilter'] : null);
    $ret = '<p>'.lang::get("LANG_ConditionsGridInstructions").'</p><table style="display:none">';
    $cloneprefix='CG:--rownum--:--sampleid--:';
    $LocationTypeID = iform_mnhnl_getTermID(self::$auth, $args['locationTypeTermListExtKey'],$args['LocationTypeTerm']);
    $ret .= "<tr class=\"cgCloneableRow\">
<td class=\"ui-state-default remove-cgnewrow\" style=\"width: 1%\">X</td>
<td class=\"cggrid-namecell\"><input name=\"".$cloneprefix."name\" class=\"cggrid-name narrow\" value=\"\" readonly=\"readonly\" >
<input type=\"hidden\" name=\"".$cloneprefix."location:centroid_sref\" class=\"cggrid-centroid_sref\" ><input type=\"hidden\" name=\"".$cloneprefix."location:centroid_geom\" class=\"cggrid-centroid_geom\" ><input type=\"hidden\" name=\"".$cloneprefix."location:boundary_geom\" class=\"cggrid-boundary_geom\" ><input type=\"hidden\" name=\"".$cloneprefix."location:location_type_id\" class=\"cggrid-location_type_id\" value=\"".$LocationTypeID."\"></td>
<td class=\"cggrid-datecell\"><input name=\"".$cloneprefix."date\" class=\"cggrid-date customDate checkYear checkComplete\" value=\"\" ></td>";
    unset($attrArgs['id']);
    $attrArgs['fieldprefix']=$cloneprefix.'smpAttr';
    $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $ret .= self::get_sample_attribute_html($sampleAttributes, $args, $defAttrOptions, $tabName)."<td><input name=\"".$cloneprefix."comment\" class=\"cggrid-comment\" ></td></tr>";
//    throw(1);
    $ret .= '</table><table id ="conditions-grid"><tr><th colSpan=2>'.lang::get("Site").'</th><th>'.lang::get('Date').'</th>';
    foreach($sampleAttributes as $attr){
      if ((!isset($options['tabNameFilter']) || strcasecmp($options['tabNameFilter'],$attr['inner_structure_block'])==0))
        $ret .= '<th>'.$attr['caption'].'</th>';
    }
    $ret .= '<th>'.lang::get('Comment').'</th></tr>';
    $cgRowNum=0;
    if(isset(data_entry_helper::$entity_to_load["sample:updated_by_id"])){ // only set if data loaded from db, not error condition
      $subsamples = self::getLocationsInGrid($auth, $args);
      if (isset($subsamples))
        foreach($subsamples as $entity){
          $cgRowNum++;
          data_entry_helper::$javascript .= "
jQuery('#conditions-grid').find('tr:eq(".$cgRowNum.")').data('locID', ".$entity['location_id'].").data('cgRowNum', ".$cgRowNum.");
jQuery('#conditions-grid').find('tr:eq(".$cgRowNum.")').find('.cggrid-date').datepicker({dateFormat : 'dd/mm/yy', changeMonth: true, changeYear: true, constrainInput: false, maxDate: '0', onClose: function() { $(this).valid(); }});
";
          if (isset($entity['sample_id'])){
            $fieldprefix='CG:'.$cgRowNum.':'.$entity['sample_id'].':';
            $attrArgs['id'] = $entity['sample_id'];
          } else {
            $fieldprefix='CG:'.$cgRowNum.':--sampleid--:';
            unset($attrArgs['id']);
          }
          if (isset($entity['date']) && preg_match('/^(\d{4})/', $entity['date'])) {
            // Date has 4 digit year first (ISO style) - convert date to expected output format
            // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
            $d = new DateTime($entity['date']);
            $entity['date'] = $d->format('d/m/Y');
          }
          $ret .= "<tr class=\"cggrid-row\"><td class=\"ui-state-default clear-cgrow\" style=\"width: 1%\">X</td><td class=\"cggrid-namecell\"><input name=\"".$fieldprefix."name\" class=\"cggrid-name narrow\" value=\"".$entity['name']."\" readonly=\"readonly\" ></td><td class=\"cggrid-datecell\"><input type=\"hidden\" name=\"".$fieldprefix."location_id\" value=\"".$entity['location_id']."\" class=\"cggrid-location_id\" ><input name=\"".$fieldprefix."date\" class=\"cggrid-date customDate checkYear checkComplete\" value=\"".$entity['date']."\" ></td>";
          $attrArgs['fieldprefix']=$fieldprefix.'smpAttr';
          $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
          $ret .= self::get_sample_attribute_html($sampleAttributes, $args, $defAttrOptions, $tabName);
          $ret .= "<td><input name=\"".$fieldprefix."comment\" class=\"cggrid-comment\" value=\"".$entity['comment']."\" ></td></tr>";
        }
    }
    $ret .= '</table>';
    data_entry_helper::$onload_javascript .= "
cgRowNum=$cgRowNum;";
    data_entry_helper::$javascript .= "
if (typeof jQuery.validator !== \"undefined\") {
  jQuery.validator.addMethod('customDate',
    function(value, element) {
      // parseDate throws exception if the value is invalid
      try{jQuery.datepicker.parseDate( 'dd/mm/yy', value);return true;}
      catch(e){return false;}
    }, '".lang::get('Please enter a valid date')."'
  );
  jQuery.validator.addMethod('checkYear',
    function(value, element) {
      if(jQuery(element).val() == '') return true;
      var myYear = jQuery(element).datepicker(\"getDate\").getFullYear();
      return (myYear == jQuery('[name=sample\\:date]').val());
    }, '".lang::get('Please ensure the year matches the year entered above.')."'
  );
  jQuery.validator.addMethod('checkNumSites',
    function(value, element) {
      return (jQuery(element).val() > 0);
    }, '".lang::get('You must add at least one site to the square before you can continue.')."'
  );
  jQuery.validator.addMethod('checkComplete',
    function(value, element) {
      return (jQuery('.cggrid-date').not('[value=]').length > 0);
    }, '".lang::get('You must fill in the data for at least one site in this grid.')."'
  );
}
jQuery('.cggrid-row').each(function(index, Element) {  // initial rows: don't need to worry about name drop down.
  if(jQuery(this).find('.cggrid-date').val()==\"\"){ // disable if blank.
    jQuery(this).find('td:not(.cggrid-datecell,.cggrid-namecell)').css('opacity',0.25);
    // disable all  active controls from the row apart from the date.
    // Do NOT disable the date or the container td, otherwise it is not submitted.
    jQuery(this).find('*:not(.cggrid-date,.cggrid-datecell,.cggrid-name,.cggrid-namecell)').attr('disabled','disabled');
    var myRowNum = jQuery(this).closest('tr').data('cgRowNum');
    jQuery('.smp-'+myRowNum).css('opacity','0.25').find(':checkbox').attr('disabled','disabled');
  } else {";
    if (isset($options['setColumnsRequired'])){
      $columns = explode(':',$options['setColumnsRequired']);
      foreach($columns as $column)
        data_entry_helper::$javascript .= "
      jQuery(this).closest('tr').find('td').eq(".$column.").append('<span class=\"deh-required\">*</span>').find('input,select').addClass('required');";
    }
    data_entry_helper::$javascript .= "
  }
});
jQuery('.cggrid-date').live('change', function() {
  jQuery(this).closest('tr').find('td:not(.cggrid-datecell,.cggrid-namecell)').css('opacity','');
  jQuery(this).closest('tr').find('*').removeAttr('disabled');
  var myRowNum = jQuery(this).closest('tr').data('cgRowNum');
  jQuery('.smp-'+myRowNum).css('opacity','').find('input').removeAttr('disabled');
  jQuery(this).closest('tr').find('.deh-required').remove();
  jQuery(this).closest('tr').find('*').removeClass('required');";
  if (isset($options['setColumnsRequired'])){
    $columns = explode(':',$options['setColumnsRequired']);
    foreach($columns as $column)
      data_entry_helper::$javascript .= "
  jQuery(this).closest('tr').find('td').eq(".$column.").append('<span class=\"deh-required\">*</span>').find('input,select').addClass('required');";
  }
  data_entry_helper::$javascript .= "
});
jQuery('.clear-cgrow').live('click', function() { // existing location - no name select.
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_conditionsgrid:clearconfirm')."\")) return;
  thisRow.find('td:not(.cggrid-datecell,.cggrid-namecell)').css('opacity',0.25);
  thisRow.find(':checkbox').attr('checked',false);
  thisRow.find('*').removeClass('required').removeClass('ui-state-error');
  thisRow.find(':text').filter('*:not(.cggrid-name)').val('');
  thisRow.find('select').val('');
  thisRow.find('.inline-error,.deh-required').remove();
  thisRow.find('*:not(.cggrid-date,.cggrid-datecell,.cggrid-name,.cggrid-namecell)').attr('disabled','disabled');
  var myRowNum = thisRow.data('cgRowNum');
  jQuery('.smp-'+myRowNum).css('opacity',0.25).find(':text').attr('disabled','disabled').val('');
  jQuery('.smp-'+myRowNum).css('opacity',0.25).find(':checkbox').attr('disabled','disabled').attr('checked','');
});
setNameDropDowns(true, false);
jQuery('.remove-cgnewrow').live('click', function() {
  if(jQuery('.cggrid-row,.cgAddedRow').length < 2) {
    alert(\"".lang::get("You can't remove the last site in the square/grid - there must be at least one. If you wish to remove this one, you must first add another in the Site tab.")."\");
    return;
  }
  var thisRow=jQuery(this).closest('tr');
  if(!confirm(\"".lang::get('LANG_conditionsgrid:removeconfirm')."\")) return;
  var myRowNum = thisRow.data('cgRowNum');
  var mySiteNum = thisRow.data('SiteNum');
  jQuery('.smp-'+myRowNum).remove();
  thisRow.remove();
  recalcNumSites();
  // TBD de highlight, demodify, and remove from map
  for(var i=SiteLabelLayer.features.length-1; i>=0; i--){ // Row may not be selected on map
    if(SiteLabelLayer.features[i].attributes.SiteNum == mySiteNum){
      SiteLabelLayer.destroyFeatures([SiteLabelLayer.features[i]]);
      setNameDropDowns(true, false);
    }
  }
  for(var i=SiteAreaLayer.features.length-1; i>=0; i--){ // Row may not be selected on map
    if(SiteAreaLayer.features[i].attributes.SiteNum == mySiteNum){
      if(SiteAreaLayer.features[i].attributes.highlighted){
        modAreaFeature.unselect(SiteAreaLayer.features[i]);
        selectFeature.unhighlight(SiteAreaLayer.features[i]);
      }
      SiteAreaLayer.destroyFeatures([SiteAreaLayer.features[i]]);
      setNameDropDowns(true, false);
    }
  }
  for(var i=SitePathLayer.features.length-1; i>=0; i--){ // Row may not be selected on map
    if(SitePathLayer.features[i].attributes.SiteNum == mySiteNum){
      if(SitePathLayer.features[i].attributes.highlighted){
        modPathFeature.unselect(SitePathLayer.features[i]);
        selectFeature.unhighlight(SitePathLayer.features[i]);
      }
      setNameDropDowns(true, false);
      SitePathLayer.destroyFeatures([SitePathLayer.features[i]]);
    }
  }
  for(var i=SitePointLayer.features.length-1; i>=0; i--){ // Row may not be selected on map
    if(SitePointLayer.features[i].attributes.SiteNum == mySiteNum){
      if(SitePointLayer.features[i].attributes.highlighted){
        modPointFeature.unselect(SitePointLayer.features[i]);
      }
      setNameDropDowns(true, false);
      SitePointLayer.destroyFeatures([SitePointLayer.features[i]]);
    }
  }
  cgRowNum=0;
  jQuery('.cggrid-row,.cgAddedRow').each(function(i,thisRow){
    if(jQuery(thisRow).data('cgRowNum') > cgRowNum)
      cgRowNum=jQuery(thisRow).data('cgRowNum');
  });
  setNameDropDowns('leave', false);
});
// 2 places we can delete from the main map delete site button and on the conditions grid 'X' button
hook_RemoveNewSite= function() {
  // assume all checks done by main function, and it will destroy the features after this is called.
  var highlighted = gethighlight();
  var myRowNum = highlighted[0].attributes.cgRowNum;
  jQuery('.cgAddedRow').each(function(index, elem){
    if(jQuery(elem).data('cgRowNum') == myRowNum)
      jQuery(elem).remove();
  });
  jQuery('.smp-'+myRowNum).remove();
  cgRowNum=0;
  jQuery('.cggrid-row,.cgAddedRow').each(function(i,thisRow){
    if(jQuery(thisRow).data('cgRowNum') > cgRowNum)
      cgRowNum=jQuery(thisRow).data('cgRowNum');
  });
};
hook_multisite_setGeomFields=function(feature, boundaryWKT, centreWKT){
  if(feature.attributes.new != true) return; // just to be safe...
  // AND assume that we can modify existing.
  // want newCGRow to stay valid until json returns so don't scope local.
  newCGrow=false;
  jQuery('.cgAddedRow').each(function (index, Element){
    if(jQuery(this).data('SiteNum') == feature.attributes.SiteNum) newCGrow=jQuery(this);
  });
  if(!newCGrow) return; 
  newCGrow.find('.cggrid-boundary_geom').val(boundaryWKT);
  newCGrow.find('.cggrid-centroid_geom').val(centreWKT);
  jQuery.getJSON(\"".data_entry_helper::$base_url."/index.php/services/spatial/wkt_to_sref?wkt=\" + centreWKT + \"&system=2169&precision=8&callback=?\",
    function(data){
      if(typeof data.error != 'undefined') alert(data.error);
      else newCGrow.find('.cggrid-centroid_sref').val(data.sref);});
}
jQuery('#dummy-name').change(function() {
  var highlighted = gethighlight();
  if(highlighted.length == 0 || !highlighted[0].attributes.new) {
    setNameDropDowns(true, false);
    return;
  }
  var myRowNum = highlighted[0].attributes.cgRowNum;
  ZoomToSite();
  jQuery('.cgAddedRow').each(function(index, elem){
    if(jQuery(elem).data('cgRowNum') == myRowNum)
      jQuery(elem).find('.cggrid-name').val(jQuery('#dummy-name').val());
  });
  moveGridEntries(myRowNum);
  jQuery('#species-grid-header').find('.smp-'+myRowNum).empty().append(jQuery(this).val());
  for(var i=SiteLabelLayer.features.length-1; i>=0; i--){ // Row may not be selected on map
    if(typeof SiteLabelLayer.features[i].attributes.cgRowNum != 'undefined'
        && SiteLabelLayer.features[i].attributes.cgRowNum == myRowNum
        && SiteLabelLayer.features[i].attributes.new){
      feature = SiteLabelLayer.features[i];
      SiteLabelLayer.removeFeatures([feature]);
      feature.style.label = jQuery(this).val();
      SiteLabelLayer.addFeatures([feature]);
      break;
    }
  }
});
";
    return $ret;
  }

  // This function pays no attention to the outer block. This is needed when the there is no outer/inner block pair, 
  // if the attribute is put in a single block level, then that block appears in the inner, rather than the outer .
  private function get_sample_attribute_html($attributes, $args, $defAttrOptions, $blockFilter=null, $blockOptions=null) {
   $r = '';
   if(!isset($attributes)) return $r;
   foreach ($attributes as $attribute) {
    // Apply filter to only output 1 block at a time. Also hide controls that have already been handled.
    if (($blockFilter===null || strcasecmp($blockFilter,$attribute['inner_structure_block'])==0) && !isset($attribute['handled'])) {
      $options = $defAttrOptions + get_attr_validation($attribute, $args);
      if (isset($blockOptions[$attribute['fieldname']])) {
        $options = array_merge($options, $blockOptions[$attribute['fieldname']]);
      }
      $options['suffixTemplate']='nosuffix';
      unset($attribute['caption']);
      $r .= '<td '.(isset($defAttrOptions['cellClass'])? 'class="'.$defAttrOptions['cellClass'].'"' : '').'>'.data_entry_helper::outputAttribute($attribute, $options).'</td>';
      $attribute['handled']=true;
    }
   }
   return $r;
  }

  protected static function get_control_lateJS($auth, $args, $tabalias, $options) {
    iform_mnhnl_addCancelButton();
  	data_entry_helper::$javascript .= "
hook_new_site_added = function(feature) {
  var name=createGridEntries(feature, true);
  feature.attributes.cgRowNum=cgRowNum;
  var centreGeom;
  var centrefeature;
  if(feature.geometry.CLASS_NAME == \"OpenLayers.Geometry.Point\"){
    centreGeom = feature.geometry;
  }else{
    centreGeom = feature.geometry.getCentroid();}
  centrefeature = new OpenLayers.Feature.Vector(centreGeom);
  centrefeature.attributes.new=true;
  centrefeature.attributes.highlighted=false;
  centrefeature.attributes.SiteNum=feature.attributes.SiteNum;
  centrefeature.attributes.cgRowNum=cgRowNum;
  centrefeature.style = jQuery.extend({}, SiteListPrimaryLabelStyleHash);
  centrefeature.style.label = name;
  SiteLabelLayer.addFeatures([centrefeature]);
  setNameDropDowns(false,name);
  setGeomFields();
};
";
    iform_mnhnl_locationmodule_lateJS($auth, $args, $tabalias, $options);
    return '';
  }
  
  
  protected static function getSampleListGridPreamble() {
    global $user;
    $r = '<p>'.lang::get('LANG_SampleListGrid_Preamble').(iform_loctools_checkaccess(self::$node,'superuser') ? lang::get('LANG_All_Users') : $user->name).'</p>';
    return $r;
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
    $sampleMod = data_entry_helper::wrap_with_attrs($values, 'sample');
    if(isset($values['sample:deleted'])) return($sampleMod);
    $subsamples = array();
    $locations = array();
    foreach($values as $key => $value){
      $parts = explode(':', $key, 4);
      //     $cloneprefix='CG:--rownum--:--sampleid--:smpAttr:--attr_id--[:--attr_value_id--]
      if($parts[0]=='CG' && count($parts)>1 && $parts[1] != '--rownum--' && $parts[1] != ''){
        $field = explode(':',$parts[3]);
        if($field[0]=='location'){
          $locations[$parts[1]][$field[1]]=array('value' => $value);
          $locations[$parts[1]]['website_id']=array('value' => $values['website_id']);
          $locations[$parts[1]]['survey_id']=array('value' => $values['survey_id']);
        }else {
          if($parts[2] != "--sampleid--" && $parts[2] != "")
            $subsamples[$parts[1]]['id']=array('value' => $parts[2]);
          $subsamples[$parts[1]][$parts[3]]=array('value' => $value);
          $subsamples[$parts[1]]['website_id']=array('value' => $values['website_id']);
          $subsamples[$parts[1]]['survey_id']=array('value' => $values['survey_id']);
        }
      }
    }
    $subsamples2 = array();
    foreach($subsamples as $sampleIndex => $subsampleFields) {
      if(isset($subsampleFields['date']) && $subsampleFields['date']['value']!=""){
        $subsampleFields['location_name']=$subsampleFields['name'];
        $subsample = array('fkId' => 'parent_id','model' => array('id' => 'sample','fields' => $subsampleFields));
        if(isset($locations[$sampleIndex])) {
          $locweb = array('fkId' => 'location_id','model' => array('id' => 'locations_website','fields' => array('website_id' =>array('value' => $values['website_id']))));
          $locations[$sampleIndex]['name']=$subsampleFields['name'];
          $locations[$sampleIndex]['parent_id']=array('value' => $values['sample:location_id']);
          $locations[$sampleIndex]['centroid_sref_system']=array('value' => $values['location:centroid_sref_system']);
          $subsample['model']['superModels'] = array(array('fkId' => 'location_id','model' => array('id' => 'location','fields' => $locations[$sampleIndex], 'subModels'=>array($locweb))));
        }
        $occs=array();
        foreach($values as $key => $value){
          $parts = explode(':', $key, 6);
          // SG:--rownum--:--sampleid--:--ttlid--:--occid--:occAttr:--attr_id--[:--attr_value_id--]
          if($parts[0]=='SG' && count($parts)>1 && $parts[3] != '--ttlid--' && $parts[3] != '' && $parts[1] == $sampleIndex && (($parts[4] != "--occid--" && $parts[4] != "")||$value!="")){
            $occ = array('fkId' => 'sample_id',
                         'model' => array('id' => 'occurrence',
                             'fields' => array('taxa_taxon_list_id' => array('value' => $parts[3]),
                                               'website_id'=>array('value' => $values['website_id']),
                                               'survey_id'=>array('value' => $values['survey_id']),
                                               $parts[5]=>array('value' => $value))));
            if($parts[4] != "--occid--" && $parts[4] != "")
              $occ['model']['fields']['id']=array('value' => $parts[4]);
            if($value==""){
              $occ['model']['fields']['deleted']=array('value' => 't');
              unset($occ['model']['fields'][$parts[5]]);
            }
            $occs[] = $occ;
          }
        }
        if(count($occs)>0) $subsample['model']['subModels'] = $occs;
        $subsamples2[] = $subsample;
      } else if(isset($subsampleFields['id']) && $subsampleFields['id']['value']!=""){
        $subsample = array('fkId' => 'parent_id',
          'model' => array('id' => 'sample','fields' => array(
            'id'=>$subsampleFields['id'],
            'deleted'=>array('value' => 't'),
            'website_id'=>$subsampleFields['website_id'],
            'survey_id'=>$subsampleFields['survey_id'])));
        $subsamples2[] = $subsample;
      }
    }
    if(count($subsamples2)>0)
      $sampleMod['subModels'] = $subsamples2;
    return($sampleMod);
  }
  
  protected function getReportActions() {
    return array(array('display' => '', 'actions' => 
            array(array('caption' => 'Edit', 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}')))),
        array('display' => '', 'actions' => 
            array(array('caption' => 'Delete', 'javascript'=>'deleteSurvey({sample_id})'))));
  } 
}