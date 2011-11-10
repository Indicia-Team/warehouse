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
 * TBD: Code editable by admin
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
    $parentVal = parent::get_parameters();
    $retVal = array();
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
              "[location buttons]\r\n".
              "[map]\r\n".
              "@clickableLayers=['locationListLayer']\r\n".
              "@clickableLayersOutputMode=div\r\n".
              "@clickableLayersOutputDiv=wms-info-target\r\n".
              "@clickableLayersOutputFn=clickedSite\r\n".
              "@clickPixelTolerance=10\r\n".
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
              "[species attributes]\r\n".
              "[*]\r\n".
              "[lateJS]\r\n";
      }
      if($param['name'] == 'attribute_termlist_language_filter')
        $param['default'] = true;
      if($param['name'] == 'grid_report')
        $param['default'] = 'reports_for_prebuilt_forms/mnhnl_bats_grid';
        
      if($param['name'] != 'species_include_taxon_group' &&
          $param['name'] != 'link_species_popups' &&
          $param['name'] != 'species_include_both_names' &&
          $param['name'] != 'includeLocTools' &&
          $param['name'] != 'loctoolsLocTypeID')
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
    $retVal[] = array(
          'name'=>'communeLayerLookup',
          'caption'=>'WFS Layer specification for Commune Lookup',
          'description'=>'Comma separated: proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames',
          'type'=>'string',
          'required' => false,
          'group'=>'Georeferencing',
        );
    $retVal[] = array(
          'name'=>'OrigLocationTypeID',
          'caption'=>'Original Site Location Type ID',
          'description'=>'Location Type ID for locations which are Original Sites (non editable by normal users).',
          'type'=>'int',
          'required' => true,
          'group' => 'Locations'
        );
    $retVal[] = array(
          'name'=>'NewLocationTypeID',
          'caption'=>'New Site Location Type ID',
          'description'=>'Location Type ID for locations which are New Sites (editable by normal users).',
          'type'=>'int',
          'required' => true,
          'group' => 'Locations'
        );
        
    return $retVal;
  }

  protected static function getExtraGridModeTabs($retTabs, $readAuth, $args, $attributes) {
    $isAdmin = user_access('IForm n'.self::$node->nid.' admin');
  	if(!$isAdmin) return('');
    if(!$retTabs) return array('#downloads' => lang::get('LANG_Download'), '#locations' => lang::get('LANG_Locations'));

   $r = '<div id="downloads" >
    <form method="post" action="'.data_entry_helper::$base_url.'/index.php/services/report/requestReport?report=reports_for_prebuilt_forms/MNHNL/mnhnl_bats_sites_download_report.xml&reportSource=local&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'].'&mode=csv&filename=batsitesreport">
      <p>'.lang::get('LANG_Sites_Download').'</p>
      <input type="hidden" id="params" name="params" value=\'{"website_id":'.$args['website_id'].', "orig_location_type_id":'.$args['OrigLocationTypeID'].', "new_location_type_id":'.$args['NewLocationTypeID'].'}\' />
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
  </div>
  <div id="locations" ><table>';
      $location_list_args=array(
        'nocache'=>true,
        'includeCodeField'=>true,
        'NameFieldName'=>'dummy:location_name_DD',
        'NameID'=>'imp-location-name',
        'extraParams'=>array_merge(array(
          'orderby'=>'name',
          'view'=>'detail',
          'website_id'=>$args['website_id']), $readAuth),
        'table'=>'location',
        'template' => 'select',
        'itemTemplate' => 'select_item',
        'filterField'=>'parent_id',
        'size'=>3
    );
    $location_list_args['extraParams'] += array('location_type_id' => array($args['OrigLocationTypeID'],$args['NewLocationTypeID']));
    // Idea here is to get a list of all locations in order to build drop downs.
    // control used can be configured on Indicia
    $responseRecords = data_entry_helper::get_population_data($location_list_args);
    // The way this will work: have 2 drop downs: code and name. Doesn't matter what they are set to
    // investigate self::init_linked_lists($options);
    if (isset($responseRecords['error']))
      return $responseRecords['error'];
    $attributeRecords = array(array());
    $attribute_list_args=array(
        'nocache'=>true,
        'view'=>'detail',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $readAuth),
        'table'=>'location_attribute_value'
      );
    $attributeResponse = data_entry_helper::get_population_data($attribute_list_args);
    $attrList=array();
    foreach ($attributeResponse as $record){
        $attributeRecords[$record['location_id']][$record['location_attribute_id']] = $record;
        $attrList[$record['location_attribute_id']] = $record['caption'];
    }
    $r .= "<tr><th>".lang::get('Name')."</th>
      <th>".lang::get('Type')."</th>
      <th>".lang::get('Code')."</th>
      <th>".lang::get('Sref')."</th>
      <th>".lang::get('Comment')."</th>";
    foreach ($attrList as $id => $caption){
      $r .= "<th>".lang::get($caption)."</th>";
    }
    $r .= "</tr>";
    foreach ($responseRecords as $record){
      $r .= "<form method=\"post\"><tr><td>".self::$auth['write']."<input type=\"hidden\" name=\"locmode\" value=\"Yes\" /><input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />
      <input name=\"l:".$record['id'].":name\" value=\"".$record['name']."\" class=\"locgrid-name\" ></td>
      <td><select name=\"l:".$record['id'].":location_type_id\" class=\"locgrid-type\" ><option value=\"".$args['OrigLocationTypeID']."\" ".($record['location_type_id']==$args['OrigLocationTypeID'] ? "selected=\"selected\"" : "").">Original</option><option value=\"".$args['NewLocationTypeID']."\" ".($record['location_type_id']==$args['NewLocationTypeID'] ? "selected=\"selected\"" : "").">New</option></td>
      <td><input name=\"l:".$record['id'].":code\" value=\"".$record['code']."\" class=\"locgrid-code\" ></td>
      <td><input name=\"l:".$record['id'].":centroid_sref\" value=\"".$record['centroid_sref']."\" class=\"locgrid-sref\"><input type=\"hidden\" name=\"l:".$record['id'].":centroid_sref_system\" value=\"".$record['centroid_sref_system']."\"><input type=\"hidden\" name=\"l:".$record['id'].":centroid_geom\" value=\"".$record['centroid_geom']."\" class=\"locgrid-geom\" ></td>
      <td><input name=\"l:".$record['id'].":comment\" value=\"".$record['comment']."\" class=\"locgrid-comment\"></td>";
      foreach ($attrList as $id => $caption){
        if(isset($attributeRecords[$record['id']][$id])){
          $class="locgrid-".str_replace (" ", "", $attributeRecords[$record['id']][$id]['caption']);
          unset($attributeRecords[$record['id']][$id]['caption']);
          $attributeRecords[$record['id']][$id]['default'] = $attributeRecords[$record['id']][$id]['raw_value'];
          $attributeRecords[$record['id']][$id]['fieldname'] = "l:".$record['id'].':locAttr:'.$attributeRecords[$record['id']][$id]['location_attribute_id'].(isset($attributeRecords[$record['id']][$id]['id']) ? ':'.$attributeRecords[$record['id']][$id]['id'] : '');
          $attributeRecords[$record['id']][$id]['id'] = $attributeRecords[$record['id']][$id]['fieldname'];
          $r .= "<td>".
            ($X=data_entry_helper::outputAttribute(
                $attributeRecords[$record['id']][$id],
                array('lookUpListCtrl'=>'select',
                      'lookUpKey'=>'meaning_id',
                      'extraParams' => $readAuth,
                      'class'=>$class,
                      'language' => iform_lang_iso_639_2($args['language'])))).
             "</td>";
        } else
          $r .= "<td>**</td>";
      }
      $r .= "<td><input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save')."\" /></td></tr></form>";
    }
    data_entry_helper::$javascript .= "
$('.locgrid-sref').change(function() {
  if ($(this).val()!='')
    $.getJSON(\"".data_entry_helper::$base_url."/index.php/services/spatial/sref_to_wkt\"+
            \"?sref=\" + $(this).val() + \"&system=2169&callback=?\",
      function(data) {
        if(typeof data.error != 'undefined')
          alert(data.error);
        else
          $(this).nearest('tr').find('.locgrid-geom').val(data.wkt);
      });
});
";
    return $r."</table></div>";
  }
  
  protected static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    foreach($attributes as $attr) { // note that the sample attrs are not indexed.
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attr['attributeId'];
      } else if (strcasecmp($attr['caption'],'CMS Username')==0) {
        $usernameAttr = $attr['attributeId'];
      }
    }
    $locAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value'
       ,'attrtable'=>'location_attribute'
       ,'key'=>'location_id'
       ,'fieldprefix'=>'locAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ));
    foreach($locAttributes as $attrId => $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'village')==0) {
        $villageAttr = $attrId;
      } else if (strcasecmp($attr['untranslatedCaption'],'commune')==0) {
        $communeAttr = $attrId;
      }
    }
      
    if ($user->uid===0)
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');

    if (!isset($userIdAttr))
      return lang::get('This form must be used with a survey that has the CMS User ID attribute associated with it so records can '.
          'be tagged against the user.');
    if (!isset($usernameAttr))
      return lang::get('This form must be used with a survey that has the CMS Username attribute associated with it so records can '.
          'be tagged against the user.');
    if (!isset($villageAttr))
      return lang::get('This form must be used with a survey that has the Village Location attribute associated with it.');
    if (!isset($communeAttr))
      return lang::get('This form must be used with a survey that has the Commune Location attribute associated with it.');
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      $reportName = 'reports_for_prebuilt_forms/MHNL/mnhnl_bats_grid';
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
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 25),
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
    data_entry_helper::$late_javascript .= "
// integer is similar to digit but allows negative
$.validator.addMethod('fillgroup', function(value, element){
	return jQuery(element).closest('table').find('input').not('[value=]').length > 0;
},
  \"".lang::get('validation_fillgroup')."\");
// jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').rules('add', {integer: true});";
      
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
  var rows=jQuery('.species-grid > tbody > tr').not(':hidden').not('.scClonableRow').length;
  if(rows==0)
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').removeAttr('disabled');
  else
    jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').attr('disabled','disabled').removeAttr('checked');
};
hook_species_checklist_pre_delete_row=function(e) {
    return confirm(\"".lang::get('Are you sure you want to delete this row?')."\");
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
              if(jQuery('[name^=sc\\:'+data[i].id+'\\:]').length > 0)
                duplicate=true;
            } else
              if(jQuery('[name^=sc\\:'+data[i].id+'\\:]').length > 8)
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
var numChecked = jQuery('[name^=sc]').not(':hidden').not('[name^=sc\\:-ttlId-]').filter(':radio').filter('[checked=true]').length;
var numFilledIn = jQuery('[name^=sc]').not(':hidden').not('[name^=sc\\:-ttlId-]').not(':radio').filter('[value!=]').length;
if(jQuery('[name='+jQuery(arg2).attr('name')+']').not(':hidden').filter('[checked=true]').length>0)
 // is checked.
 return(numChecked==0&&numFilledIn==0)
else if(numChecked>0||numFilledIn>0)
 return true;
// there are no rows filled in, in which case ensure no obs can be filled in.
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').removeAttr('disabled','disabled');
return false;
},
  \"".lang::get('validation_no_observation')."\");
  ";
//          } else if($rule[$i]=='fillgroup'){
//               data_entry_helper::$late_javascript .= "
//// integer is similar to digit but allows negative
//$.validator.addMethod('fillgroup', function(value, element){/
//	return this.optional(element) || /^-?\d+$/.test(value);
//},
//  \"".lang::get('validation_fillgroup')."\");
//// jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').rules('add', {integer: true});";
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
      if (strcasecmp($attr['untranslatedCaption'],'Bat Visit')==0) {
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
    // There are 5 events that trigger a set up of the location data
    // 1) a newSample is being generated
    // 2) a exisitng sample is being displayed with its reference to an existing location
    // 3) the new Location button is pressed
    // 4) the existing location name drop down is selected.
    // 5) an existing location is clicked on the map.
    global $indicia_templates, $user;
    // have to override the name of the imp-geom to point to the location centroid_geometry
    $smpAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ));
    foreach($smpAttributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attrId;
      }
    }
    $locAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value'
       ,'attrtable'=>'location_attribute'
       ,'key'=>'location_id'
       ,'fieldprefix'=>'locAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ));
    foreach($locAttributes as $attrId => $attr) {
      if (strcasecmp($attr['untranslatedCaption'],'village')==0) {
        $villageAttr = $attrId;
      } else if (strcasecmp($attr['untranslatedCaption'],'commune')==0) {
        $communeAttr = $attrId;
      }
    }
    
    if (!isset($villageAttr)) {
      return lang::get('This form must be used with a survey that has the Village Location attribute associated with it.');
    }
    if (!isset($communeAttr)) {
      return lang::get('This form must be used with a survey that has the Commune Location attribute associated with it.');
    }
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
        'includeCodeField'=>true,
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
    $attributeRecords = array(array());
    $attribute_list_args=array_merge(array(
        'nocache'=>true,
        'view'=>'list',
        'extraParams'=>array_merge(array('orderby'=>'name', 'website_id'=>$args['website_id']), $auth['read']),
        'table'=>'location_attribute_value'
      ), $options);
    $attributeResponse = data_entry_helper::get_population_data($attribute_list_args);
    
    foreach ($attributeResponse as $record)
        $attributeRecords[$record['location_id']][$record['location_attribute_id']] = $record;
    $NameOpts = '';
    foreach ($responseRecords as $record){
      if($record['name']!=''){
        $item = array('selected' => ((array_key_exists('location:id', data_entry_helper::$entity_to_load) &&
                                      data_entry_helper::$entity_to_load['location:id']==$record['id']) ? 'selected' : ''),
                      'value' => $record['id'],
                      'caption' => htmlspecialchars(utf8_decode($record['name'].' ('.
                           (isset($attributeRecords[$record['id']][$communeAttr]['value']) ? $attributeRecords[$record['id']][$communeAttr]['value'] : '-').' / '.
                           (isset($attributeRecords[$record['id']][$villageAttr]['value']) ? $attributeRecords[$record['id']][$villageAttr]['value'] : '-').') n° '.
                           ($record['code'] != '' ? $record['code'] : '-'))));
        $NameOpts .= data_entry_helper::mergeParamsIntoTemplate($item, $location_list_args['itemTemplate']);
      }
    }
    $r = '<fieldset><legend>'.lang::get('Existing Locations').'</legend><input type="hidden" id="imp-location" name="location:id" value="'.(array_key_exists('location:id', data_entry_helper::$entity_to_load) ? data_entry_helper::$entity_to_load['location:id'] : ''). '" >';
    if($NameOpts != ''){
      $location_list_args['label']=$location_list_args['NameLabel'];
      $location_list_args['fieldname']=$location_list_args['NameFieldName'];
      $location_list_args['id']=$location_list_args['NameID'];
      $location_list_args['items'] = str_replace(array('{value}', '{caption}', '{selected}'),
          array('', htmlentities($location_list_args['NameBlankText'])),
          $indicia_templates[$location_list_args['itemTemplate']]).$NameOpts;
      $r .= data_entry_helper::apply_template($location_list_args['template'], $location_list_args);
    }
    $isAdmin = user_access('IForm n'.self::$node->nid.' admin');
    data_entry_helper::$javascript .= "
setAllDisabled = function(existingLoc){
  enableItems = [];
  disableItems = ['[name=locations_website\\:website_id]',
                  '[name=location\\:id]',
                  '[name=location\\:code]',
                  '[name=location\\:name]',
                  '[name=location\\:comment]',
                  '[name=location\\:location_type_id]',
                  '[name^=locAttr\\:]',
                  '#imp-sref',
                  '#imp-sref-lat',
                  '#imp-sref-long',
                  '#imp-sref-system',
                  '#imp-geom'];
  if(existingLoc)
    enableItems.push('[name=sample\\:location_id]');
  else
    disableItems.push('[name=sample\\:location_id]');
  if(jQuery('#map')[0].map != undefined) jQuery('#map')[0].map.editLayer.clickControl.deactivate();
  jQuery(disableItems.join(',')).attr('disabled',true);
  if(enableItems.length > 0) jQuery(enableItems.join(',')).removeAttr('disabled');
}
setLocationEditable = function(creating){
  disableItems = ['[name=sample\\:location_id]'];
  readonlyItems = [];
  enableItems = ['[name=location\\:id]',
                  '[name=location\\:name]',
                  '[name=location\\:comment]',
                  '[name^=locAttr\\:]',
                  '#imp-sref',
                  '#imp-sref-lat',
                  '#imp-sref-long',
                  '#imp-sref-system',
                  '#imp-geom'];
  if(creating) {
    enableItems.push('[name=locations_website\\:website_id]');
    enableItems.push('[name=location\\:location_type_id]');
    disableItems.push('[name=location\\:id]');
  } else {
    disableItems.push('[name=locations_website\\:website_id]');
    disableItems.push('[name=location\\:location_type_id]');
    enableItems.push('[name=location\\:id]');
  }
";
    if(isset($args['communeLayerLookup']) && $args['communeLayerLookup']!='')
      data_entry_helper::$javascript .= "readonlyItems.push('[name=locAttr\\:$communeAttr],[name^=locAttr\\:$communeAttr\\:]');";
    else
      data_entry_helper::$javascript .= "enableItems.push('[name=locAttr\\:$communeAttr],[name^=locAttr\\:$communeAttr\\:]');";
    if(!$isAdmin)
      data_entry_helper::$javascript .= "readonlyItems.push('[name=location\\:code]');";
    else
      data_entry_helper::$javascript .= "enableItems.push('[name=location\\:code]');";
    data_entry_helper::$javascript .= "
  if(jQuery('#map')[0].map != undefined) jQuery('#map')[0].map.editLayer.clickControl.activate();
  jQuery(disableItems.join(',')).attr('disabled',true);
  jQuery(enableItems.join(',')).removeAttr('disabled');
  if(readonlyItems.length > 0) jQuery(readonlyItems.join(',')).removeAttr('disabled').attr('readonly',true);
}
clearLocationContents = function(setCode){ // note this does not set the editable state.
  jQuery('#clear-pos-button').attr('disabled','');
  if(jQuery('#map')[0].map != undefined) jQuery('#map')[0].map.editLayer.destroyFeatures();
  jQuery('[name=location\\:code],[name=location\\:id],[name=location\\:name],[name=location\\:comment],#imp-sref,#imp-sref-lat,#imp-sref-long,#imp-geom').val('');
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
  jQuery('[name^=locAttr\\:]').filter(':text').val('');
  checkRadioStatus();
  if(!setCode) return;
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/location' +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(data) {
      // store value in saved field?
      if (data instanceof Array && data.length>0) {
        var maxCode = 0;
        for(var i = 0; i< data.length; i++){
          if(parseInt(data[i].code) > maxCode)
            maxCode = parseInt(data[i].code)
        }
        if(jQuery('[name=location\\:code]').val()=='') jQuery('[name=location\\:code]').val(maxCode+1)
      }});
};
loadLocation = function(myValue){
  clearLocationContents(false);
  if (myValue!=='') {
    jQuery('#clear-pos-button').attr('disabled','disabled');
    setLocationEditable(false);
    jQuery('[name=location\\:id]').val(myValue);
    jQuery('[name=sample\\:location_id]').val(myValue);";
    if(!$isAdmin) data_entry_helper::$javascript .= "
    // normal bods can not edit the location details if someone else has recorded a samples on this location, or the location is an original.
    jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/sample?location_id='+myValue +
            '&mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(sdata) {
      if (sdata instanceof Array && sdata.length>0) {
        var sampleList=[];
        for (var i=0;i<sdata.length;i++)
          sampleList.push(sdata[i].id);
        jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/sample_attribute_value' +
            '?mode=json&view=list&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?&sample_attribute_id=$userIdAttr&query='+escape(JSON.stringify({'in': ['sample_id', sampleList]})), function(sadata) {
          if (sadata instanceof Array && sadata.length>0) {
            for (var i=0;i<sadata.length;i++)
              if(sadata[i].value != ".$user->uid.") {
                setAllDisabled(true);
                return;
              }}});
    }});";
    data_entry_helper::$javascript .= "
    jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/data/location/'+myValue +
            '?mode=json&view=detail&auth_token=".$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&callback=?', function(data) {
      // store value in saved field?
      if (data instanceof Array && data.length>0) {
        ".(!$isAdmin ? "if(data[0].location_type_id == ".$args['OrigLocationTypeID'].") setAllDisabled(true);
        " : '').
        "jQuery('[name=location\\:code]').val(data[0].code);
        jQuery('[name=location\\:name]').val(data[0].name);
        jQuery('[name=location\\:comment]').val(data[0].comment);
        jQuery('#imp-sref-system').val(data[0].centroid_sref_system);
        jQuery('#imp-geom').val(data[0].centroid_geom);
        jQuery('#imp-sref').val(data[0].centroid_sref);
        var refxy = data[0].centroid_sref.split(' ');
        var refx = refxy[0].split(',');
        jQuery('#imp-sref-lat').val(refx[0]);
        jQuery('#imp-sref-long').val(refxy[1]).change();
      }});
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
  } else
    setAllDisabled(false);
};
jQuery('#imp-location-name').change(function(){
  var myValue = jQuery('#imp-location-name').val();
  loadLocation(myValue);
});
newLocation = function(){
  jQuery('#imp-location-name').val('');
  clearLocationContents(true);
  setLocationEditable(true);
};
";
    // assume all validation carried out client side -> no error situations.
    if(array_key_exists('sample:id', data_entry_helper::$entity_to_load)) {
      // viewing an existing sample.
      data_entry_helper::$onload_javascript .= "loadLocation(".data_entry_helper::$entity_to_load['sample:location_id'].");
jQuery('#imp-location-name').val(".data_entry_helper::$entity_to_load['sample:location_id'].");
";
    } else { // newSample
      data_entry_helper::$onload_javascript .= "clearLocationContents(false);
setAllDisabled(false);
jQuery('#imp-location-name').val('');
";
  }
    // Create Layers.
// Base Layers first.
  data_entry_helper::$onload_javascript .= "
WMSoptions = {
          SERVICE: 'WMS',
          VERSION: '1.1.0',
          STYLES: '',
          SRS: 'EPSG:2169', // TBD configurable.
          FORMAT: 'image/png',
          TRANSPARENT: 'true',
          LAYERS: 'indicia:bat_locations' // TBD configurable.
};
locationListLayer = new OpenLayers.Layer.WMS('sites', // TBD configurable.
        '".iform_proxy_url('http://localhost:8080/geoserver/wms')."', // TBD configurable.
        WMSoptions, {
             minScale: 0, // TBD configurable.
            maxScale: 10000, // TBD configurable.
            units: 'm', // TBD configurable.
            isBaseLayer: false,
            singleTile: true
        });
clickedSite = function(features, div){
  if(features.length>0){
    var myValue = features[0].attributes.id;
    jQuery('#imp-location-name').val(myValue);
    loadLocation(myValue);
  }
};
";
  
    $r .= '<input type="button" value="'.lang::get('Create New Location').'" onclick="newLocation();">'.
      '<input type="hidden" id="locations_website:website_id" name="locations_website:website_id" value="'.$args['website_id'].'" disabled="'.(array_key_exists('location:id', data_entry_helper::$entity_to_load) ? 'disabled' : ''). '" />'.
      '<input type="hidden" name="location:location_type_id" value="'.$args['NewLocationTypeID'].'" />'.
      '</fieldset>'.
      '<label for="location:name">'.lang::get('LANG_Location_Name_Label').':</label>'.
      '<input type="text" id="location:name" name="location:name" class="required wide" value="'.data_entry_helper::$entity_to_load['location:name'].'" /><span class="deh-required">*</span><br/>';
    if($location_list_args['includeCodeField'])
      $r .= '<label for="location:code">'.lang::get('LANG_Location_Code_Label').':</label><input type="text" id="location:code" name="location:code" disabled="disabled" value="'.data_entry_helper::$entity_to_load['location:code'].'" /><br/>';
    $r .= "<div id='wms-info-target' style=\"display:none\" ></div>";
    return $r;
  }

  protected static function get_control_lateJS($auth, $args, $tabalias, $options) {
    // proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames
    // http://localhost/geoserver/wfs,indiciaCommune,Communes,the_geom,indicia,EPSG:2169,COMMUNE
  	if(isset($args['communeLayerLookup']) && $args['communeLayerLookup']!=''){
      $communeAttr=iform_mnhnl_getAttrID($auth, $args, 'location', 'commune');
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
  if(a1.features.length > 0)
    jQuery('[name=locAttr\\:$communeAttr],[name^=locAttr\\:$communeAttr\\:]').val(a1.features[0].attributes[\"".$parts[6]."\"]).attr('readonly','readonly');
  else {
    alert('".lang::get('LANG_PositionOutsideCommune')."');
  }
}
jQuery('#map')[0].map.editLayer.events.register('featuresadded', {}, function(a1){
    jQuery('[name=locAttr\\:$communeAttr],[name^=locAttr\\:$communeAttr\\:]').val('').attr('readonly','readonly');
	if(a1.features.length < 1)return;
	var feature = a1.features[0];
	var filter = new OpenLayers.Filter.Spatial({
  		type: OpenLayers.Filter.Spatial.CONTAINS ,
    	property: '".$parts[3]."',
    	value: feature.geometry
	});
	communeProtocol.read({filter: filter, callback: fillCommune});
});
";
    }
    return '';
}

  protected static function get_control_locationspatialreference($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    // have to override the name of the imp-geom to point to the location centroid_geometry
    $indicia_templates['sref_textbox_latlong'] = '<label for="{idLat}" style="width: auto; ">{labelLat}:</label>'.
        '<input type="text" id="{idLat}" name="{fieldnameLat}" {class} {disabled} value="{defaultLat}" />' .
        '<label for="{idLong}" style="width: auto; margin-left: 20px; ">{labelLong}:</label>'.
        '<input type="text" id="{idLong}" name="{fieldnameLong}" {class} {disabled} value="{defaultLong}" />' .
        '<input type="hidden" id="imp-geom" name="{geomFieldname}" value="{defaultGeom}" />'.
        '<input type="hidden" id="{id}" name="{fieldname}" value="{default}" />'.
        '<span style="margin-left: 20px; ">'.lang::get('LANG_LatLong_Bumpf').'</span><br />';

    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    return data_entry_helper::sref_and_system(array_merge(array(
      'splitLatLong' => true,
      'fieldname' => 'location:centroid_sref',
      'srefField' => 'location:centroid_sref',
      'geomFieldname' => 'location:centroid_geom',
      'systems' => $systems
    ), $options));
  }
  
  protected static function get_control_locationbuttons($auth, $args, $tabalias, $options) {
    data_entry_helper::$javascript .= "
ViewAllLuxembourg = function(){
	var div = jQuery('#map')[0];
	var center = new OpenLayers.LonLat(".$args['map_centroid_long'].", ".$args['map_centroid_lat'].");
	center.transform(div.map.displayProjection, div.map.projection);
	div.map.setCenter(center, ".((int) $args['map_zoom']).");
};
ClearPosition = function(){
  jQuery('#imp-geom').val('');
  jQuery('#imp-sref').val('');
  jQuery('#imp-sref-lat').val('');
  jQuery('#imp-sref-long').val('');
  var div = jQuery('#map')[0];
  div.map.editLayer.destroyFeatures();
};
";
    return '<input id="clear-pos-button" type="button" value="'.lang::get('Clear Position').'" onclick="ClearPosition();">
<input type="button" value="'.lang::get('Zoom to Site').'" onclick="jQuery(\'#imp-sref-long\').change();">
<input type="button" value="'.lang::get('View All Luxembourg').'" onclick="ViewAllLuxembourg();">';
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
    return $r."<span>".lang::get('LANG_RecorderInstructions')."</span><br />";
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
    if (isset($values['locmode'])){
      if (!array_key_exists('website_id', $values))
        throw new Exception('Cannot find website id in POST array!');
      $record = array();
      foreach ($values as $key=>$value){
        if (substr($key, 0, 2)=='l:'){
          // Don't explode the last element for location attributes
          $a = explode(':', $key, 3);
          $record['id']=$a[1];
          $record[$a[2]] = $value;
        }
      }
      $record['website_id'] = $values['website_id'];
      return (data_entry_helper::wrap($record, 'location'));
    }
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
      if (isset($values['location:name'])){
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