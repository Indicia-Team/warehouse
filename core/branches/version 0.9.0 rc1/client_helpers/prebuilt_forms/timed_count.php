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

require_once 'includes/map.php';
require_once 'includes/user.php';
require_once 'includes/form_generation.php';

/**
 * @package Client
 * @subpackage PrebuiltForms
 */
function iform_timed_count_subsample_cmp($a, $b)
{
    return strcmp($a["date_start"], $b["date_start"]);
}

// BIG WARNING: in this form the sample Sref will not represent the geometry.
// Each visit the flight area is reentered: no 2 visits have the same geometry. No location records.

// TODO Check if flight area attribute is to be calculated from the site polygon, or entered by the user.
// TODO Check if OS Map number is to be included.
// TODO Check validation rules to be applied to each field.

class iform_timed_count {

  /**
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_timed_count_definition() {
    return array(
      'title'=>'Timed Count',
      'category' => 'Forms for specific surveying methods',
      'description'=>'A form for inputting the counts of species during a timed period. Can be called with sample=<id> to edit an existing sample.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $params = array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      array(
        array(
          'name'=>'survey_id',
          'caption'=>'Survey',
          'description'=>'The survey that data will be posted into.',
          'type'=>'select',
          'table'=>'survey',
          'captionField'=>'title',
          'valueField'=>'id',
          'siteSpecific'=>true
        ),
        array(
          'name'=>'occurrence_attribute_id',
          'caption'=>'Occurrence Attribute',
          'description'=>'The attribute (typically an abundance attribute) that will be presented in the grid for input. Entry of an attribute value will create '.
              ' an occurrence.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'siteSpecific'=>true
        ),
        array(
          'name'=>'taxon_list_id',
          'caption'=>'Species List',
          'description'=>'The species checklist used for the species autocomplete.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'siteSpecific'=>true,
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_filter_field',
          'caption'=>'Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_filter',
          'caption'=>'Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'custom_attribute_options',
          'caption'=>'Options for custom attributes',
          'description'=>'A list of additional options to pass through to custom attributes, one per line. Each option should be specified as '.
              'the attribute name followed by | then the option name, followed by = then the value. For example, smpAttr:1|class=control-width-5.',
          'type'=>'textarea',
          'required'=>false,
          'siteSpecific'=>true
        ),
        array(
          'name'=>'summary_page',
          'caption'=>'Path to summary page',
          'description'=>'Path used to access the main page giving a summary of the entered time walks after a successful submission (e.g. a report_calendar_grid page).',
          'type'=>'text_input',
          'required'=>true,
          'siteSpecific'=>true
        ),
        array(
          'name'=>'numberOfCounts',
          'caption'=>'Max number of counts',
          'description'=>'Max number of counts to be entered in this location.',
          'type'=>'int',
          'required'=>true,
          'siteSpecific'=>true,
          'default'=>2
        ),
        array(
          'name'=>'numberOfSpecies',
          'caption'=>'Number of species',
          'description'=>'The number of species that can be entered per count.',
          'type'=>'int',
          'required'=>true,
          'siteSpecific'=>true,
          'default'=>2
        ),
        array(
          'name'=>'spatial_systems',
          'caption'=>'Allowed Spatial Ref Systems',
          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326). '.
              'Set to "default" to use the settings defined in the IForm Settings page.',
          'type'=>'string',
          'default' => 'default',
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'precision',
          'caption'=>'Sref Precision',
          'description'=>'The precision to be applied to the polygon centroid when determining the SREF. Leave blank to not set.',
          'type'=>'int',
          'required'=>false,
          'group'=>'Other Map Settings'
        )
      )
    );
    return $params;
  }

  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $node, $response=null) {
    if (isset($response['error'])){
      data_entry_helper::dump_errors($response);
    }
    if (isset($_REQUEST['page']) && 
          (($_REQUEST['page']==='site' && !isset(data_entry_helper::$validation_errors)) || // we have just saved the main sample page with no errors, so move on to the occurrences list
           ($_REQUEST['page']==='occurrences' && isset(data_entry_helper::$validation_errors)))) { // or we have just saved the occurrences page with errors, so redisplay the occurrences list
      return self::get_occurrences_form($args, $node, $response);
    } else {
      return self::get_sample_form($args, $node, $response);
    }
  }

  public static function get_sample_form($args, $node, $response) {
    global $user;
    iform_load_helpers(array('map_helper'));
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // either looking at existing, creating a new one, or an error occurred: no successful posts...
    // first check some conditions are met
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Timed Count'));
    if (count($sampleMethods)==0)
      return 'The sample method "Timed Count" must be defined in the termlist in order to use this form.';

    $sampleId = isset($_GET['sample_id']) ? $_GET['sample_id'] : null;
    if ($sampleId && !isset(data_entry_helper::$validation_errors))
      data_entry_helper::load_existing_record($auth['read'], 'sample', $sampleId);

    $r = '<form method="post" id="sample">'.$auth['write'];
    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call, so it can get location details.
    $r .= '<input type="hidden" name="read_nonce" value="'.$auth['read']['nonce'].'"/>';
    $r .= '<input type="hidden" name="read_auth_token" value="'.$auth['read']['auth_token'].'"/>';
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
    // pass a param that sets the next page to display
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\"/>
<input type=\"hidden\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\"/>
<input type=\"hidden\" name=\"page\" value=\"site\"/>";

    $attributes = data_entry_helper::getAttributes(array(
      'id' => $sampleId,
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id']
    ));
    $r .= get_user_profile_hidden_inputs($attributes, $args, '', $auth['read']).
        data_entry_helper::text_input(array('label' => lang::get('Site Name'), 'fieldname' => 'sample:location_name', 'validation' => array('required') /*, 'class' => 'control-width-5' */ ))
        // .data_entry_helper::textarea(array('label'=>lang::get('Recorder names'), 'fieldname'=>'sample:recorder_names'))
        ;
    $help = lang::get('The Year field is read-only, and is calculated automatically from the date(s) of the Counts.');
    $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    if ($sampleId == null){
      if(isset($_GET['date'])) data_entry_helper::$entity_to_load['C1:sample:date'] = $_GET['date'];
      $r .= data_entry_helper::date_picker(array('label' => lang::get('Date of first count'), 'fieldname' => 'C1:sample:date', 'validation' => array('required','date')));
      data_entry_helper::$javascript .= "jQuery('#C1\\\\:sample\\\\:date').change(function(){
  jQuery('#sample\\\\:date').val(jQuery(this).val() == '' ? '' : jQuery(this).datepicker('getDate').getFullYear());
});
if(jQuery('#C1\\\\:sample\\\\:date').val() != '') jQuery('#sample\\\\:date').val(jQuery('#C1\\\\:sample\\\\:date').datepicker('getDate').getFullYear());\n";
    }
    if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
      // Date has 4 digit year first (ISO style) - only interested in Year.
      $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
      data_entry_helper::$entity_to_load['sample:date'] = $d->format('Y');
    }
    unset(data_entry_helper::$default_validation_rules['sample:date']);
    $r .= data_entry_helper::text_input(array('label' => lang::get('Year'), 'fieldname' => 'sample:date', 'readonly'=>' readonly="readonly" ' ));

    // are there any option overrides for the custom attributes?
    if (isset($args['custom_attribute_options']) && $args['custom_attribute_options']) 
      $blockOptions = get_attr_options_array_with_user_data($args['custom_attribute_options']);
    else $blockOptions=array();
    $r .= get_attribute_html($attributes, $args, array('extraParams'=>$auth['read']), null, $blockOptions);
    $r .= '<input type="hidden" name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $help = lang::get('Now draw the flight area for the timed count on the map below. The Grid Reference is filled in automatically when the site is drawn.');
    $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['allowPolygonRecording'] = true;
    $options['clickForSpatialRef'] = false;
    if(isset($args['precision']) && $args['precision'] != ''){
      $options['clickedSrefPrecisionMin'] = $args['precision'];
      $options['clickedSrefPrecisionMax'] = $args['precision'];
    }
    $olOptions = iform_map_get_ol_options($args);
    if(!in_array('drawPolygon', $options['standardControls'])) $options['standardControls'][]= 'drawPolygon';
    if(!in_array('modifyFeature', $options['standardControls'])) $options['standardControls'][]= 'modifyFeature';

    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) $systems[$system] = lang::get($system); 
    $r .= "<label for=\"imp-sref\">".lang::get('Grid Reference').":</label> <input type=\"text\" id=\"imp-sref\" name=\"sample:entered_sref\" value=\"".data_entry_helper::$entity_to_load['sample:entered_sref']."\" readonly=\"readonly\" class=\"required\" />";
    $r .= "<input type=\"hidden\" id=\"imp-geom\" name=\"sample:geom\" value=\"".data_entry_helper::$entity_to_load['sample:geom']."\" />";
    if (count($systems) == 1) {
      // Hidden field for the system
      $keys = array_keys($systems);
      $r .= "<input type=\"hidden\" id=\"imp-sref-system\" name=\"sample:entered_sref_system\" value=\"".$keys[0]."\" />\n";
    } else {
      $r .= self::sref_system_select(array('fieldname'=>'sample:entered_sref_system'));
    }
    
    $r .= '<br />'.data_entry_helper::georeference_lookup(iform_map_get_georef_options($args, $auth['read']));
    $r .= data_entry_helper::map_panel($options, $olOptions);
    // switch off the sref functionality.
    data_entry_helper::$javascript .= "mapInitialisationHooks.push(function(div){
  $('#imp-sref').unbind('change');
  // Programatic activation does not rippleout, so deactivate Nav first, which is actibve by default.
  for(var i=0; i<div.map.controls.length; i++)
    if(div.map.controls[i].CLASS_NAME == \"OpenLayers.Control.Navigation\")
      div.map.controls[i].deactivate();
  activeCtrl = false;
  for(var i=0; i<div.map.controls.length; i++){
    if(div.map.controls[i].CLASS_NAME == \"".
     (isset(data_entry_helper::$entity_to_load['sample:id']) ? "OpenLayers.Control.ModifyFeature" : "OpenLayers.Control.DrawFeature")."\"){
      div.map.controls[i].activate();
      activeCtrl = div.map.controls[i];
      break;
    }}\n".
(isset(data_entry_helper::$entity_to_load['sample:id']) ?
"  if(activeCtrl && div.map.editLayer.features.length>0) activeCtrl.selectFeature(div.map.editLayer.features[0]);\n" : "")."});\n";

    $r .= data_entry_helper::textarea(array('label'=>'Comment', 'fieldname'=>'sample:comment', 'class'=>'wide'));
    $r .= '<input type="submit" value="'.lang::get('Next').'" />';
    $r .= '<a href="'.$args['summary_page'].'"><button type="button" class="ui-state-default ui-corner-all" />'.lang::get('Cancel').'</button></a>';

    // allow deletes if sample id is present: i.e. existing sample.
    if (isset(data_entry_helper::$entity_to_load['sample:id'])){
      $r .= '<button id="delete-button" type="button" class="ui-state-default ui-corner-all" />'.lang::get('Delete').'</button>';
      // note we only require bare minimum in order to flag a sample as deleted.
      $r .= '</form><form method="post" id="delete-form" style="display: none;">';
      $r .= $auth['write'];
      $r .= '<input type="hidden" name="page" value="delete"/>';
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
      $r .= '<input type="hidden" name="sample:deleted" value="t"/>';
      data_entry_helper::$javascript .= "jQuery('#delete-button').click(function(){
  if(confirm(\"".lang::get('Are you sure you want to delete this timed count?')."\"))
    jQuery('#delete-form').submit();
});\n";
    }

    $r .= '</form>';
    data_entry_helper::enable_validation('sample');
    return $r;
  }

  public static function get_occurrences_form($args, $node, $response) {
    global $user;
    data_entry_helper::add_resource('jquery_form');
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // did the parent sample previously exist? Default is no.
    $parentSampleId=null;
    $existing=false;
    if (isset($_POST['sample:id'])) {
      // have just posted an edit to the existing parent sample, so can use it to get the parent location id.
      $parentSampleId = $_POST['sample:id'];
      $existing=true;
    } else {
      if (isset($response['outer_id']))
        // have just posted a new parent sample, so can use it to get the parent location id.
        $parentSampleId = $response['outer_id'];
      else {
        $parentSampleId = $_GET['sample_id'];
        $existing=true;
      }
    }
    if(!$parentSampleId || $parentSampleId == '') return ('Could not determine the parent sample.');

    // find any attributes that apply to Timed Count Count samples.
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Timed Count Count'));
    if (count($sampleMethods)==0)
      return 'The sample method "Timed Count Count" must be defined in the termlist in order to use this form.';
    $attributes = data_entry_helper::getAttributes(array(
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id'],
      'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    if(!isset(data_entry_helper::$validation_errors)){
      // the parent sample and at least one sub-sample have already been created: can't cache in case a new subsample (Count) added.
      data_entry_helper::load_existing_record($auth['read'], 'sample', $parentSampleId);
      $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
      data_entry_helper::$entity_to_load['sample:date'] = $d->format('Y');
      // using the report returns the attributes as well.
      $subSamples = data_entry_helper::get_population_data(array(
        'report' => 'library/samples/samples_list_for_parent_sample',
        'extraParams' => $auth['read'] + array('sample_id'=>$parentSampleId,'date_from'=>'','date_to'=>'', 'sample_method_id'=>'', 'smpattrs'=>implode(',', array_keys($attributes))),
        'nocache'=>true
      ));
      // subssamples ordered by id desc, so reorder by date asc.
      usort($subSamples, "iform_timed_count_subsample_cmp");
      for($i = 0; $i < count($subSamples); $i++){
        data_entry_helper::$entity_to_load['C'.($i+1).':sample:id'] = $subSamples[$i]['sample_id'];
        data_entry_helper::$entity_to_load['C'.($i+1).':sample:date'] = $subSamples[$i]['date']; // this is in correct format
        foreach($subSamples[$i] as $field => $value){
          if(preg_match('/^attr_sample_/',  $field)){
            $parts=explode('_',$field);
            if($subSamples[$i]['attr_id_sample_'.$parts[2]] != null)
              data_entry_helper::$entity_to_load['C'.($i+1).':smpAttr:'+$parts[2]+':'+$subSamples[$i]['attr_id_sample_'.$parts[2]]] = $value;
          }
        }
      }
    }

    data_entry_helper::$javascript .= "indiciaData.speciesList = ".$args['taxon_list_id'].";\n";
    if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesListFilterField = '".$args['taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesListFilterValues = '".json_encode($filterLines)."';\n";
    }
    data_entry_helper::$javascript .= "
indiciaData.indiciaSvc = '".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";
    data_entry_helper::$javascript .= "indiciaData.parentSample = ".$parentSampleId.";\n";
    data_entry_helper::$javascript .= "indiciaData.occAttrId = ".$args['occurrence_attribute_id'] .";\n";

    if ($existing) {
      // Only need to load the occurrences for a pre-existing sample
      $o = data_entry_helper::get_population_data(array(
        'report' => 'library/occurrences/occurrences_list_for_parent_sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','sample_id'=>$parentSampleId,'survey_id'=>'','date_from'=>'','date_to'=>'','taxon_group_id'=>'',
            'smpattrs'=>'', 'occattrs'=>$args['occurrence_attribute_id']),
        // don't cache as this is live data
        'nocache' => true
      ));
      // the report is ordered id desc. REverse it
      $o = array_reverse($o);
    } else $o = array(); // empty array of occurrences when no creating a new sample. 

    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call.
    // pass a param that sets the next page to display
    $r = "<form method='post' id='subsamples'>".$auth['write']."
<input type='hidden' name='page' value='occurrences'/>
<input type='hidden' name='read_nonce' value='".$auth['read']['nonce']."'/>
<input type='hidden' name='read_auth_token' value='".$auth['read']['auth_token']."'/>
<input type='hidden' name='website_id' value='".$args['website_id']."'/>
<input type='hidden' name='sample:id' value='".data_entry_helper::$entity_to_load['sample:id']."'/>
<input type='hidden' name='sample:survey_id' value='".$args['survey_id']."'/>
<input type='hidden' name='sample:date' value='".data_entry_helper::$entity_to_load['sample:date']."'/>
<input type='hidden' name='sample:entered_sref' value='".data_entry_helper::$entity_to_load['sample:entered_sref']."'/>
<input type='hidden' name='sample:entered_sref_system' value='".data_entry_helper::$entity_to_load['sample:entered_sref_system']."'/>
<input type='hidden' name='sample:geom' value='".data_entry_helper::$entity_to_load['sample:geom']."'/>
";

    if (isset($args['custom_attribute_options']) && $args['custom_attribute_options']) 
      $blockOptions = get_attr_options_array_with_user_data($args['custom_attribute_options']);
    else $blockOptions=array();

    for($i = 0; $i < $args['numberOfCounts']; $i++){
      $subSampleId = (isset($subSamples[$i]) ? $subSamples[$i]['sample_id'] : null);
      $r .= "<fieldset id=\"count-$i\"><legend>".lang::get('Count ').($i+1)."</legend>";
      if($subSampleId) $r .= "<input type='hidden' name='C".($i+1).":sample:id' value='".$subSampleId."'/>";
      $r .= '<input type="hidden" name="C'.($i+1).':sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
      if($subSampleId || (isset(data_entry_helper::$entity_to_load['C'.($i+1).':sample:date']) && data_entry_helper::$entity_to_load['C'.($i+1).':sample:date'] != ''))
        $dateValidation = array('required','date');
      else
        $dateValidation = array('date');
      $r .= data_entry_helper::date_picker(array('label' => lang::get('Date'), 'fieldname' => 'C'.($i+1).':sample:date', 'validation' => $dateValidation));
      data_entry_helper::$javascript .= "$('#C".($i+1)."\\\\:sample\\\\:date' ).datepicker( 'option', 'minDate', new Date(".data_entry_helper::$entity_to_load['sample:date'].", 1 - 1, 1) );
$('#C".($i+1)."\\\\:sample\\\\:date' ).datepicker( 'option', 'maxDate', new Date(".data_entry_helper::$entity_to_load['sample:date'].", 12 - 1, 31) );\n";
      if(!$subSampleId && $i) {
        $r .= "<p>".lang::get('You must enter the date before you can enter any further information.').'</p>';
        data_entry_helper::$javascript .= "$('#C".($i+1)."\\\\:sample\\\\:date' ).change(function(){
  myFieldset = $(this).addClass('required').closest('fieldset');
  myFieldset.find('.smp-input,[name=taxonLookupControl]').removeAttr('disabled'); // leave the count fields as are.
});\n";
      }
      if($subSampleId && $i)
        $r .= "<label for='C".($i+1).":sample:deleted'>Delete this count:</label>
<input id='C".($i+1).":sample:deleted' type='checkbox' value='t' name='C".($i+1).":sample:deleted'><br />
<p>".lang::get('Setting this will delete this count when the page is saved.').'</p>';
      
      foreach ($attributes as $attr) {
        if(strcasecmp($attr['untranslatedCaption'],'Unconfirmed Individuals')==0) continue;
        // output the attribute - tag it with a class & id to make it easy to find from JS.
        $attrOpts = array_merge(
          (isset($blockOptions[$attr['fieldname']]) ? $blockOptions[$attr['fieldname']] : array()),
          array(
            'class' => 'smp-input smpAttr-'.($i+1),
            'id' => 'C'.($i+1).':'.$attr['fieldname'],
            'fieldname' => 'C'.($i+1).':'.$attr['fieldname'],
            'extraParams'=>$auth['read']
          ));
          // if there is an existing value, set it and also ensure the attribute name reflects the attribute value id.
        if (isset($subSampleId)) {
          // but have to take into account possibility that this field has been blanked out, so deleting the attribute.
          if(isset($subSamples[$i]['attr_id_sample_'.$attr['attributeId']]) && $subSamples[$i]['attr_id_sample_'.$attr['attributeId']] != ''){
            $attrOpts['fieldname'] = 'C'.($i+1).':'.$attr['fieldname'] . ':' . $subSamples[$i]['attr_id_sample_'.$attr['attributeId']];
            $attr['default'] = $subSamples[$i]['attr_sample_'.$attr['attributeId']];
          }
        } else if($i) $attrOpts['disabled'] = "disabled=\"disabled\"";
        $r .= data_entry_helper::outputAttribute($attr, $attrOpts);
      }
      $r .= '<table id="timed-counts-input-'.$i.'" class="ui-widget">';
      $r .= '<thead><tr><th class="ui-widget-header">' . lang::get('Species') . '</th><th class="ui-widget-header">' . lang::get('Count') . '</th><th class="ui-widget-header"></th></tr></thead>';
      $r .= '<tbody class="ui-widget-content">';
      $occs = array();
      // not very many occurrences so no need to optimise.
      if (isset($subSampleId) && $existing && count($o)>0)
        foreach ($o as $oc)
          if ($oc['sample_id'] == $subSampleId)
            $occs[] = $oc;
      for($j = 0; $j < $args['numberOfSpecies']; $j++){
        $rowClass='';
        // O<i>:<j>:<ttlid>:<occid>:<attrid>:<attrvalid>
        if(isset($occs[$j])){
          $taxon = $occs[$j]['common'].' ('.$occs[$j]['taxon'].')';
          $fieldname = 'O'.($i+1).':'.($j+1).':'.$occs[$j]['taxa_taxon_list_id'].':'.$occs[$j]['occurrence_id'].':'.$args['occurrence_attribute_id'].':'.$occs[$j]['attr_id_occurrence_'.$args['occurrence_attribute_id']];
          $value = $occs[$j]['attr_occurrence_'.$args['occurrence_attribute_id']];
        } else {
          $taxon = '';
          $fieldname = 'O'.($i+1).':'.($j+1).':--ttlid--:--occid--:'.$args['occurrence_attribute_id'].':--valid--';
          $value = '';
        }
        $r .= '<tr '.$rowClass.'>'.
               '<td><input id="TLC-'.($i+1).'-'.($j+1).'" name="taxonLookupControl" value="'.$taxon.'" '.((!$j && (!$i||$subSampleId))||$taxon ? 'class="required"' : '').' '.(!$subSampleId && $i ? 'disabled="disabled"' : '' ).'>'.((!$j && (!$i||$subSampleId))||$taxon ? '<span class="deh-required">*</span>' : '').'</td>'.
               '<td><input name="'.$fieldname.'" id="occ-'.($i+1).'-'.($j+1).'" value="'.$value.'" class="occValField integer '.((!$j && (!$i||$subSampleId))||$taxon ? 'required' : '').'" '.((!$subSampleId && $i) || ($taxon=='' && ($i || $j)) ? 'disabled="disabled"' : '').' min=0 >'.((!$j && (!$i||$subSampleId))||$taxon ? '<span class="deh-required">*</span>' : '').'</td>'.
               '<td>'.(!$j ? '' : '<div class="ui-state-default remove-button">&nbsp;</div>').'</td>'.
               '</tr>';
        $rowClass=$rowClass=='' ? 'class="alt-row"':'';
        data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"TLC-".($i+1)."-".($j+1)."\",\"occ-".($i+1)."-".($j+1)."\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$args['taxon_list_id']."\",
  indiciaData.speciesListFilterField, indiciaData.speciesListFilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"}, 25);\n";
      }
      foreach ($attributes as $attr) {
        if(strcasecmp($attr['untranslatedCaption'],'Unconfirmed Individuals')) continue;
        // output the attribute - tag it with a class & id to make it easy to find from JS.
        $attrOpts = array(
            'class' => 'smp-input smpAttr-'.($i+1),
            'id' => 'C'.($i+1).':'.$attr['fieldname'],
            'fieldname' => 'C'.($i+1).':'.$attr['fieldname'],
            'extraParams'=>$auth['read']
        );
        // if there is an existing value, set it and also ensure the attribute name reflects the attribute value id.
        if (isset($subSampleId)) {
          // but have to take into account possibility that this field has been blanked out, so deleting the attribute.
          if(isset($subSamples[$i]['attr_id_sample_'.$attr['attributeId']]) && $subSamples[$i]['attr_id_sample_'.$attr['attributeId']] != ''){
            $attrOpts['fieldname'] = 'C'.($i+1).':'.$attr['fieldname'] . ':' . $subSamples[$i]['attr_id_sample_'.$attr['attributeId']];
            $attr['default'] = $subSamples[$i]['attr_sample_'.$attr['attributeId']];
          }
        } else if($i) $attrOpts['disabled'] = "disabled=\"disabled\"";
        $r .= '<tr '.$rowClass.'>'.
               '<td>'.$attr['caption'].'</td>';
        unset($attr['caption']);
        $r .= '<td>'.data_entry_helper::outputAttribute($attr, $attrOpts).'</td>'.
               '<td></td>'.
               '</tr>';
      }
      
      $r .= '</tbody></table>';
      if($i && !$subSampleId) $r .= '<button type="button" class="clear-button ui-state-default ui-corner-all smp-input" disabled="disabled" />'.lang::get('Clear this count').'</button>';
      $r .= '</fieldset>';
    }
    $r .= '<input type="submit" value="'.lang::get('Save').'" />';
    $r .= '<a href="'.$args['summary_page'].'"><button type="button" class="ui-state-default ui-corner-all" />'.lang::get('Cancel').'</button></a></form>';
    data_entry_helper::enable_validation('subsamples');
    data_entry_helper::$javascript .= "initButtons();\n";
    return $r;
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $subsampleModels = array();
    $read = array('nonce' => $values['read_nonce'], 'auth_token' => $values['read_auth_token']);
    if (!isset($values['page']) || $values['page']=='site') {
      // submitting the first page, with top level sample details
      // keep the first count date on a subsample for use later.
      if(isset($values['C1:sample:date'])){
        $sampleMethods = helper_base::get_termlist_terms(array('read'=>$read), 'indicia:sample_methods', array('Timed Count Count'));
        $smp = array('fkId' => 'parent_id',
                   'model' => array('id' => 'sample',
                     'fields' => array('survey_id' => array('value' => $values['sample:survey_id']),
                                       'website_id' => array('value' => $values['website_id']),
                                       'date' => array('value' => $values['C1:sample:date']),
                                       'sample_method_id' => array('value' => $sampleMethods[0]['id'])
                     )),
                   'copyFields' => array('entered_sref'=>'entered_sref','entered_sref_system'=>'entered_sref_system'));
//                   'copyFields' => array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type'));
        $subsampleModels[] = $smp;
      }
    } else if($values['page']=='occurrences'){
      // at this point there is a parent supersample.
      // loop from 1 to numberOfCounts, or number of existing subsamples, whichever is bigger.
      $subSamples = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $read + array('parent_id'=>$values['sample:id']),
        'nocache'=>true
      ));
      for($i = 1; $i <= max(count($subSamples), $args['numberOfCounts']); $i++){
        if(isset($values['C'.$i.':sample:id']) || (isset($values['C'.$i.':sample:date']) && $values['C'.$i.':sample:date']!='')){
          $subSample = array('website_id' => $values['website_id'],
                             'survey_id' => $values['sample:survey_id']);
          $occurrences = array();
          $occModels = array();
          foreach($values as $field => $value){
            $parts = explode(':',$field,2);
            if($parts[0]=='C'.$i) $subSample[$parts[1]] = $value;
            if($parts[0]=='O'.$i) $occurrences[$parts[1]] = $value;
          }
          ksort($occurrences);
          foreach($occurrences as $field => $value){
            // have take off O<i> do is now <j>:<ttlid>:<occid>:<attrid>:<attrvalid> - sorted in <j> order
            $parts = explode(':',$field);
            $occurrence = array('website_id' => $values['website_id']);
            if($parts[1] != '--ttlid--') $occurrence['taxa_taxon_list_id'] = $parts[1];
            if($parts[2] != '--occid--') $occurrence['id'] = $parts[2];
            if($value == '') $occurrence['deleted'] = 't';
            else if($parts[4] == '--valid--') $occurrence['occAttr:'.$parts[3]] = $value;
            else $occurrence['occAttr:'.$parts[3].':'.$parts[4]] = $value;
            if (array_key_exists('occurrence:determiner_id', $values)) $occurrence['determiner_id'] = $values['occurrence:determiner_id'];
            if (array_key_exists('occurrence:record_status', $values)) $occurrence['record_status'] = $values['occurrence:record_status'];
            if(isset($occurrence['id']) || !isset($occurrence['deleted'])){
              $occ = data_entry_helper::wrap($occurrence, 'occurrence');
              $occModels[] = array('fkId' => 'sample_id', 'model' => $occ);
            }
          }
          $smp = array('fkId' => 'parent_id',
            'model' => data_entry_helper::wrap($subSample, 'sample'),
            'copyFields' => array('entered_sref'=>'entered_sref','entered_sref_system'=>'entered_sref_system')); // from parent->to child
          if(!isset($subSample['sample:deleted']) && count($occModels)>0) $smp['model']['subModels'] = $occModels;
          $subsampleModels[] = $smp;
        }
      }
    }
    $sampleMod = submission_builder::build_submission($values, array('model' => 'sample'));
    if(count($subsampleModels)>0){
      $sampleMod['subModels'] = $subsampleModels;
    }
    return($sampleMod);
  }
  
  /**
   * Override the form redirect to go back to My Walks after the grid is submitted. Leave default redirect (current page)
   * for initial submission of the parent sample.
   */
  public static function get_redirect_on_success($values, $args) {
    return  ($values['page']==='occurrences' || $values['page']==='delete') ? $args['summary_page'] : '';
  }

}
