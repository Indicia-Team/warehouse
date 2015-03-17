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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */
 
require_once('dynamic_sample_occurrence.php');

// TO DO
// ZERO RECORDS
// INPUT BOXES FOR LAT LONGS
// Navigate button to front page


/**
 * An input form to support the Big Sea Survey methodology. 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_big_sea_survey extends iform_dynamic_sample_occurrence {

  private static $parentSample;
  private static $parentSampleAttrs;
  private static $thisSampleAttrs;
  private static $transectCountAttrs;
  private static $selectedTransect;
  private static $selectedZoneAttrId;
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_big_sea_survey_definition() {
    return array(
      'title'=>'Big Sea Survey',
      'category' => 'Forms for specific surveying methods',
      'description' => 'A dynamic form which allows a front page to define the number of transects to record across '.
          'a set of zones and multiple copies of the second page allow data to be input per transect.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    $r = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'transect_count_attr_ids',
          'caption'=>'Transect count attribute IDs',
          'description'=>'Comma separated list of sample attribute IDs. Specify each attribute that can contain a count of transects surveyed '.
              '(e.g. low shore, middle shore, high shore). For each attribute, n transects will be available for data input.',
          'type'=>'textfield',
          'required' => true,
          'group' => 'Big Sea setup'
        ), array(
          'name'=>'transect_captions',
          'caption'=>'Transect captions',
          'description'=>'Comma separated list of captions to use for each of the above attributes, in the same order.',
          'type'=>'textfield',          
          'required' => true,
          'group' => 'Big Sea setup'
        ),
        array(
          'name'=>'child_sample_zone_attr_id',
          'caption'=>'Child sample zone attribute ID',
          'description'=>'A text attribute used to store the zone in the child sample.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Big Sea setup'
        ), array(
          'name'=>'child_sample_transect_attr_id',
          'caption'=>'Child sample transect attribute ID',
          'description'=>'An integer attribute used to store the transect in the child sample.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Big Sea setup'
        ), array(
          'name'=>'search_species_transect_attr_id',
          'caption'=>'Parent sample search species attribute ID',
          'description'=>'An integer multivalut attribute used to store the search species list in the parent attribute.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Big Sea setup'
        ),
        array(
          'name'=>'front_page_path',
          'caption'=>'Front page path',
          'description'=>'Path to the front page input form.',
          'type'=>'textfield',          
          'required' => true,
          'group'=>'Big Sea setup'
        ),
        array(
          'name' => 'parent_sample_method_id',
          'caption' => 'Parent Sample Method',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:sample_methods'),
          'required' => false,
          'helpText' => 'The sample method that will be used for created visit samples.',
          'group'=>'Big Sea setup'
        )
      )
    );
    return $r;
  }
  
  /**
   * Override get_form_html. We remove the second tab when first inputting a new sample, because
   * the second tab can't save unless there is a parent sample available to link to.
   */
  protected static function get_form_html($args, $auth, $attributes) { 
    if (empty($_GET['id'])) 
      return 'This form must be called with an id in the URL parameters';
    data_entry_helper::$javascript .= "indiciaData.latLongNotationPrecision=5;\n";
    return parent::get_form_html($args, $auth, $attributes);
  }
  
  protected static function getEntity($args, $auth) {
    self::$parentSample =  data_entry_helper::get_population_data(array(
      'table' => 'sample',
      'extraParams' => $auth['read'] + array('id' => $_GET['id'], 'view' => 'detail'),
      'nocache' => true
    ));
    // since getting attrs from the parent, so filter on the parent's sample method not the child's
    $parentargs = array_merge($args);
    $parentargs['sample_method_id']=$args['parent_sample_method_id'];
    $attrs = self::getAttributesForSample($parentargs, $auth, $_GET['id']);
    self::$parentSampleAttrs = array();
    // convert to keyed array
    foreach ($attrs as $attr) 
      self::$parentSampleAttrs[$attr['id']]=$attr;
    self::$thisSampleAttrs = array();
    $attrs = self::getAttributes($args, $auth);
    // convert to keyed array
    foreach ($attrs as $attr) 
      self::$thisSampleAttrs[$attr['id']]=$attr; 
      
    self::$parentSample = self::$parentSample[0];
    self::$transectCountAttrs = explode(',', $args['transect_count_attr_ids']);
    self::$selectedTransect =  empty($_GET['transect']) ? 1 : $_GET['transect'];
    // Get the attribute ID from the parent sample which holds the count in the active shore zone
    self::$selectedZoneAttrId = empty($_GET['zone']) ? self::$parentSampleAttrs['smpAttr:'.self::$transectCountAttrs[0]]['id'] : "smpAttr:$_GET[zone]";
    
    // Work out the zone caption - a bit convoluted
    $captions = explode(',', $args['transect_captions']);
    foreach(self::$transectCountAttrs as $idx=>$id) {
      if ("smpAttr:$id"===self::$selectedZoneAttrId)
        $zone=$captions[$idx];
    }
    if (!isset($zone))
      throw new exception('Zone not found');
    // See if an existing sample is available to link to
    $sample = data_entry_helper::get_population_data(array(
      'report' => 'reports_for_prebuilt_forms/big_sea/find_sample',
      'extraParams' => $auth['read'] + array('parent_sample_id' => $_GET['id'], 
        'zone' => $zone, 'transect' => self::$selectedTransect,
        'zone_attr_id' => $args['child_sample_zone_attr_id'], 'transect_attr_id' => $args['child_sample_transect_attr_id']),
      'nocache' => true
    ));    
    if (count($sample)>0) {
      self::$loadedSampleId=$sample[0]['id'];
      foreach ($sample[0] as $key=>$value)
        data_entry_helper::$entity_to_load["sample:$key"]=$value;
    } else {
      // default to the same plot as entered for the parent
      data_entry_helper::$entity_to_load["sample:date"]=self::$parentSample['date_start'];
      data_entry_helper::$entity_to_load["sample:entered_sref"]=self::$parentSample['entered_sref'];
      data_entry_helper::$entity_to_load["sample:entered_sref_system"]=self::$parentSample['entered_sref_system'];
      data_entry_helper::$entity_to_load["sample:wkt"]=self::$parentSample['geom'];
    }
  }
  
  protected static function getMode($args, $node) {
    // reload the page after initial save - show parent sample so we can enter the transect data.
    if (!empty($_GET['id']) && !empty($_GET['table']) && $_GET['table']==='sample')
      $_GET['sample_id']=$_GET['id'];
    return parent::getMode($args, $node);
  }
  
  /**
   * Save button takes us to the next transect.
   */
  public static function get_redirect_on_success($values, $args) {
    
    if (!empty($values['next-zone']) && !empty($values['next-transect']))
      return $args['redirect_on_success'] . '?' . data_entry_helper::array_to_query_string(array(
        'table'=>'sample',
        'id'=>$values['sample:parent_id'],
        'zone'=>$values['next-zone'],
        'transect'=>$values['next-transect']
      ));
    else 
      return $args['front_page_path'];
  }
  
  protected static function get_control_transectsbar($auth, $args, $tabAlias, $options) {
    $captions = explode(',', $args['transect_captions']);
    $r = '<input type="hidden" name="sample:parent_id" value="'.$_GET['id'].'"/>';
    // use same date as parent
    $r .= '<input type="hidden" name="sample:date" value="'.data_entry_helper::$entity_to_load["sample:date"].'"/>';
    $r .= '<input type="hidden" id="imp-sref" name="sample:entered_sref" value="'.data_entry_helper::$entity_to_load["sample:entered_sref"].'"/>';
    $r .= '<input type="hidden" id="imp-sref-system" name="sample:entered_sref_system" value="'.data_entry_helper::$entity_to_load["sample:entered_sref_system"].'"/>';
    $r .= '<input type="hidden" id="imp-geom" name="sample:geom" value="'.data_entry_helper::$entity_to_load["sample:wkt"].'"/>';
    $r .= '<div class="ui-helper-clearfix">';
    $ids = array();
    $wantNext = false;
    // loop through the list of attributes which hold a transect count (one per shore zone)    
    foreach (self::$transectCountAttrs as $idx => $id) {
      $attr = self::$parentSampleAttrs["smpAttr:$id"];
      // output a fieldset per zone
      $r .= "<fieldset class=\"left\" id=\"tcount-$id\"><legend>$captions[$idx]</legend><div>";
      // output buttons, 1 per transect in the zone
      for ($i=1; $i<=$attr['default']; $i++) {
        $selected = (self::$selectedZoneAttrId===$attr['id']) && (self::$selectedTransect==$i);
        if ($selected) {
          // As this is the selected zone/transect, we can grab the information about it now
          $title = "$captions[$idx] - transect $i";
          $fld = self::$thisSampleAttrs['smpAttr:'.$args['child_sample_zone_attr_id']]['fieldname'];
          $r .= "<input type=\"hidden\" name=\"$fld\" value=\"$captions[$idx]\"/>";
          $fld = self::$thisSampleAttrs['smpAttr:'.$args['child_sample_transect_attr_id']]['fieldname'];
          $r .= "<input type=\"hidden\" name=\"$fld\" value=\"$i\"/>";
          $wantNext = true;
        } elseif ($wantNext) {
          // This is the button after the selected transect. Store the values required to find the next page after successful save.
          $r .= "<input type=\"hidden\" name=\"next-zone\" value=\"$id\"/>";
          $r .= "<input type=\"hidden\" name=\"next-transect\" value=\"$i\"/>";
          $wantNext=false;
        }
        if ($selected) 
          $r .= "<span class=\"button select-transect ui-state-highlight\" id=\"sel-$attr[id]-$i\">$i</span>";
        else {
          $link=hostsite_get_url($args['redirect_on_success'], array(
            'table'=>'sample',
            'id'=>$_GET['id'],
            'zone'=>$id,
            'transect'=>$i
          ));
          $r .= "<a href=\"$link\" class=\"button select-transect\" id=\"sel-$attr[id]-$i\">$i</a>";
        }
      }
      $r .= "</div></fieldset>";
      $ids[] = $attr['id'];
    }
    $r .= "</div><h2 id=\"transect-name\">$title</h2><p>".data_entry_helper::$entity_to_load["sample:date"].'</p>';
    data_entry_helper::$javascript .= "indiciaData.transectCountAttrs=".json_encode($ids).";\n";
    return $r;
  }
  
  /**
   * A variant of the species grid control with just the fixed species categories available.
   */
  protected static function get_control_fixedspecies($auth, $args, $tabAlias, $options) {
    unset($args['extra_list_id']);
    $options['rowInclusionCheck']='hasData';
    $options['id']='fixed-list';
    return parent::get_control_species($auth, $args, $tabAlias, $options);
  }
  
  /**
   * A variant of the species grid control with just the flexible search species available.
   */
  protected static function get_control_searchspecies($auth, $args, $tabAlias, $options) {
    // build a list of the search species IDs
    $ttlIds=array();
    foreach (self::$parentSampleAttrs['smpAttr:'.$args['search_species_transect_attr_id']]['default'] as $value) {
      $ttlIds[] = $value['default'];
    }
    if (empty($ttlIds))
      return '<p>'.lang::get('Please fill in the search species on the front page.').'</p>'; // safety
    $args['list_id']=$args['extra_list_id'];
    $args['taxon_filter_field']='taxa_taxon_list_id';
    $args['taxon_filter']=implode("\n", $ttlIds);
    $options['rowInclusionCheck']='alwaysFixed';
    $options['id']='search-list';
    unset($args['extra_list_id']);
    return parent::get_control_species($auth, $args, $tabAlias, $options);
  }
  
  /**
   * A set of input controls for defining the transect.
   */
  protected static function get_control_latlongs($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::text_input(array(
      'label'=>'Transect start',
      'fieldname'=>'gpsstart',
      'helpText' => lang::get('Transect start, GPS coordinate (decimal WGS84 latitude and longitude). Click once on the map to set.')
    ));
    $r .= data_entry_helper::text_input(array(
      'label'=>'Transect end',
      'fieldname'=>'gpsend',
      'helpText' => lang::get('Transect end, GPS coordinate (decimal WGS84 latitude and longitude). Click again on the map to set.')
    ));
    return $r;
  }
  
  protected static function get_control_map($auth, $args, $tabAlias, $options) {
    $file = data_entry_helper::$js_path.strtolower("drivers/sref/4326.js");
    // dynamically build a resource to link us to the handler js file.
    data_entry_helper::$required_resources[] = 'sref_handlers_4326';
    data_entry_helper::$resource_list['sref_handlers_4326'] = array(
      'javascript' => array($file)
    );
    $options['gridRefHint']=true;
    $options['clickForSpatialRef']=false;
    return parent::get_control_map($auth, $args, $tabAlias, $options);
  }
}
