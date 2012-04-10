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
 */
// TBD Future the specimen stage and sex may be checkboxes rather than radio buttons.
// Setup main form structure.
// sample Type: checkboxes? when checked sets the disabled/required status of the relevant sections.

require_once('mnhnl_dynamic_2.php');
require_once('includes/mnhnl_common.php');

class iform_mnhnl_mammals1 extends iform_mnhnl_dynamic_2 {
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_mammals1_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Mammals1 form. Inherits from Dynamic 2. Originally developed for the Dormouse survey.'
    );
  }
  /** 
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Mammals1';  
  }

  public static function get_parameters() {    
    $retVal = array();
    $parentVal = array_merge(
      parent::get_parameters(),
      iform_mnhnl_getParameters(),
      array(
        array(
          'name'=>'communeLayerLookup',
          'caption'=>'WFS Layer specification for Commune Lookup',
          'description'=>'Comma separated: proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames',
          'type'=>'string',
          'required' => false,
          'group'=>'Georeferencing',
        ))
    );
    foreach($parentVal as $param){
      if($param['name'] == 'occurrence_structure'){
        $param['default'] = "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or click on the map.?\r\n".
              "[spatial reference]\r\n".
              "@splitLatLong=true\r\n".
              "[map]\r\n".
              "@layers=[\"SSLayer\"]\r\n".
              "[apply defaults]\r\n".
              "[species attributes]\r\n".
              "[*]\r\n";
      }
      if($param['name'] == 'attribute_termlist_language_filter')
        $param['default'] = true;

      if($param['name'] != 'species_include_taxon_group' &&
          $param['name'] != 'link_species_popups' &&
          $param['name'] != 'species_include_both_names')
        $retVal[] = $param;
    }
    return $retVal;
  }

  public static function get_css() {
    return array('mnhnl_mammals1.css');
  }

  protected static function get_form_sampleoccurrence($args, $node){
    // remove any line break between the split lat long field, sort label size and spacing.
    data_entry_helper::$javascript .= "
jQuery('#imp-sref-lat').prev().filter('label').addClass('auto-width');
jQuery('#imp-sref-long').prev().filter('label').addClass('auto-width prepad');
jQuery('#imp-sref-lat').next().filter('br').remove();\n";
    return parent::get_form_sampleoccurrence($args, $node);
  }
  /**
   * Get the location module control
   */
  protected static function get_control_locationmodule($auth, $args, $tabalias, $options) {
    return iform_mnhnl_lux5kgridControl($auth, $args, self::$node, array_merge( // NB we will use as a 1x1km instead!
      array('initLoadArgs' => '{initial: true}',
       'canCreate'=>true
       ), $options));
  }
  protected static function get_control_locationcomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname'=>'location:comment',
      'label'=>lang::get('Location Comment')
    ), $options)); 
  }
  protected static function get_control_customJS($auth, $args, $tabalias, $options) {
//    iform_mnhnl_addCancelButton();
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
          } else if(substr($rule[0], 3, 4)!= 'Attr'){ // have to add for non attribute case.
            data_entry_helper::$late_javascript .= "
jQuery('[name=".str_replace(':','\\:',$rule[0])."],[name^=".str_replace(':','\\:',$rule[0])."\\:]').addClass('".$rule[$i]."');";
          }
    }
    // proxiedurl,featurePrefix,featureType,geometryName,featureNS,srsName,propertyNames
    // http://localhost/geoserver/wfs,indiciaCommune,Communes,the_geom,indicia,EPSG:2169,COMMUNE
  	if(isset($args['communeLayerLookup']) && $args['communeLayerLookup']!=''){
      $communeAttr=iform_mnhnl_getAttrID($auth, $args, 'location', 'Commune');
      if (!$communeAttr) return lang::get('The customJS control Commune lookup functionality must be used with a survey that has the Commune attribute associated with it.');
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
    return '';
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
  protected static function get_control_applydefaults($auth, $args, $tabalias, $options) {
    $r='';
    foreach($args['defaults'] as $key=>$value){
      if($key!='occurrence:record_status' && $key!='sample:date'){
        $r .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
      }
  	}
    return $r; 
  }
  protected static function get_control_recordernames($auth, $args, $tabalias, $options) {
    return iform_mnhnl_recordernamesControl(self::$node, $auth, $args, $tabalias, $options);
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
jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').filter('[value=".$other[0]['meaning_id']."]').parent().append(other);
jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').change(function(){
  jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').filter('[value=".$other[0]['meaning_id']."]').each(function(){
    if(this.checked)
      jQuery('[name=".$prefix."\\:".$attr2."],[name^=".$prefix."\\:".$attr2."\\:]').addClass('required').removeAttr('readonly');
    else
      jQuery('[name=".$prefix."\\:".$attr2."],[name^=".$prefix."\\:".$attr2."\\:]').removeClass('required').val('').attr('readonly',true);
  });
});
jQuery('[name=".str_replace(':','\\:',$attr['id'])."],[name^=".str_replace(':','\\:',$attr['id'])."\\:],[name=".str_replace(':','\\:',$attr['id'])."\\[\\]]').filter('[value=".$other[0]['meaning_id']."]').change();
";
    }
    return '';
  }
  protected static function get_control_nonoccurrencespecies($auth, $args, $tabalias, $options) {
    // We assume that the key is meaning_id.
    $r = '<label>'.lang::get($options['label']).'</label>';
  	global $indicia_templates;
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    // load the full list of species for the grid, including the main checklist plus any additional species in the reloaded occurrences.
    if (isset($options['lookupListId'])) {
      $grid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
    }
    $grid = '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'"><tbody>';
    $grid .= "</tbody>\n</table>\n";
    $grid .= "<label for=\"taxonLookupControl\" class=\"auto-width\">".lang::get('Add species to list').":</label> <input id=\"taxonLookupControl\" name=\"taxonLookupControl\" >";
    // Javascript to add further rows to the grid
    data_entry_helper::$javascript .= "
function bindSpeciesAutocomplete(selectorID, url, gridId, lookupListId, readAuth, formatter, duplicateMsg, max) {
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    var myClass='scMeaning-'+data.taxon_meaning_id;
    if(jQuery('.'+myClass).not('.deleted-row').length>0){
      alert(duplicateMsg);
      $(event.target).val('');
      return;
    }
    var rows=$('#'+gridId + '-scClonable > tbody > tr');
    var newRows=[];
    rows.each(function(){newRows.push($(this).clone(true))})
    var taxonCell=newRows[0].find('td:eq(1)');
    // Replace the tags in the row template with the taxa_taxon_list_ID
    $.each(newRows, function(i, row) {
      row.appendTo('#'+gridId);
    }); 
    $(event.target).val('');
    formatter(data,taxonCell);
  };
    // Attach auto-complete code to the input
  ctrl = $('#' + selectorID).autocomplete(url+'/taxa_taxon_list', {
      extraParams : {
        view : 'detail',
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: readAuth.auth_token,
        nonce: readAuth.nonce,
        taxon_list_id: lookupListId
      },
      max : max,
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) {
          results[results.length] = {'data' : item, 'result' : item.taxon, 'value' : item.taxon};
        });
        return results;
      },
      formatItem: function(item) {
        return item.taxon;
      }
  });
  ctrl.bind('result', handleSelectedTaxon);
  setTimeout(function() { $('#' + ctrl.attr('id')).focus(); });
}
$('.remove-row').live('click', function(e) {
  e.preventDefault();
  var row = $(e.target.parentNode);
  row.remove();
});
bindSpeciesAutocomplete(\"taxonLookupControl\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$options['id']."\", \"".$options['lookupListId']."\", {\"auth_token\" : \"".
            $options['readAuth']['auth_token']."\", \"nonce\" : \"".$options['readAuth']['nonce']."\"}, formatter, \"".lang::get('LANG_Duplicate_Taxon')."\", ".$options['max_species_ids'].");
";
    return $grid;
  }
  
  public static function get_submission($values, $args) {
    if (isset($values['source']))
      return submission_builder::wrap_with_images($values, 'location');
  	if(array_key_exists('newsample_parent_id', $_POST)){
      // $mode = MODE_NEW_OCCURRENCE
      return null;
    }
    if(array_key_exists('sample:parent_id', $_POST)) {
      //  $mode = MODE_POST_OCCURRENCE;
      return data_entry_helper::build_sample_occurrence_submission($values);
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
  
}