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
require_once 'includes/language_utils.php';
require_once 'includes/form_generation.php';


// TODO
// Add check to prevent duplicate rows

/**
 *
 *
 * @package Client
 * @subpackage PrebuiltForms
 * A form for data entry of occurrence data by entering counts of each for flower type in an area.
 */
class iform_ukbms_timed_observations {

  /**
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_ukbms_timed_observations_definition() {
    return array(
      'title'=>'UKBMS Timed Observations',
      'category' => 'UKBMS Timed Observations',
      'description'=>'TODO'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
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
          'name'=>'occurrence_attribute_ids',
          'caption'=>'Occurrence Attribute',
          'description'=>'Comma separated list of attribute IDs to be used in the species grids.',
          'type'=>'string',
          'required' => true,
          'siteSpecific'=>true
        ),
        array(
          'name'=>'percent_width',
          'caption'=>'Map Percent Width',
          'description'=>'The percentage width that the map will take on the front page.',
          'type'=>'int',
          'required' => true,
          'default' => 50,
          'siteSpecific'=>true
        ),
        array(
          'name'=>'species_tab_1',
          'caption'=>'Species Tab 1 Title',
          'description'=>'The title to be used on the species checklist for the main tab.',
          'type'=>'string',
          'required' => true,
          'group'=>'Species Tab 1'
        ),
        array(
          'name'=>'taxon_list_id_1',
          'caption'=>'All Species List',
          'description'=>'The species checklist used to populate the grid on the main grid when All Species is selected. Also used to drive the autocomplete when other options selected.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'siteSpecific'=>true,
          'group'=>'Species Tab 1'
        ),
        array(
          'name'=>'taxon_filter_field_1',
          'caption'=>'All Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected All Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species Tab 1'
        ),
        array(
          'name'=>'taxon_filter_1',
          'caption'=>'All Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species Tab 1'
        ),
        array(
          'name'=>'species_tab_2',
          'caption'=>'Species Tab 2 Title',
          'description'=>'The title to be used on the species checklist for the second tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species Tab 2'
        ),
        array(
          'name'=>'taxon_list_id_2',
          'caption'=>'Second Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional second grid. If not provided, the second grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species Tab 2'
        ),
        array(
          'name'=>'taxon_filter_field_2',
          'caption'=>'Second Tab Species List: Field used to filter taxa',
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
          'group'=>'Species Tab 2'
        ),
        array(
          'name'=>'taxon_filter_2',
          'caption'=>'Second Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species Tab 2'
        ),
        array(
          'name'=>'species_tab_3',
          'caption'=>'Species Tab 3 Title',
          'description'=>'The title to be used on the species checklist for the third tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species Tab 3'
        ),
        array(
          'name'=>'taxon_list_id_3',
          'caption'=>'Third Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional third grid. If not provided, the third grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species Tab 3'
        ),
        array(
          'name'=>'taxon_filter_field_3',
          'caption'=>'Third Tab Species List: Field used to filter taxa',
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
          'group'=>'Species Tab 3'
        ),
        array(
          'name'=>'taxon_filter_3',
          'caption'=>'Third Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species Tab 3'
        ),
        array(
          'name'=>'species_tab_4',
          'caption'=>'Fourth Species Tab Title',
          'description'=>'The title to be used on the species checklist for the fourth tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species Tab 4'
        ),
        array(
          'name'=>'taxon_list_id_4',
          'caption'=>'Fourth Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional fourth grid. If not provided, the fourth grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>'false',
          'siteSpecific'=>true,
          'group'=>'Species Tab 4'
        ),
        array(
          'name'=>'taxon_filter_field_4',
          'caption'=>'Fourth Tab Species List: Field used to filter taxa',
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
          'group'=>'Species Tab 4'
        ),
        array(
          'name'=>'taxon_filter_4',
          'caption'=>'Fourth Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species Tab 4'
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
          'name'=>'locationType',
          'caption'=>'Restrict locations to type',
          'description'=>'When choosing the parent location, restrict the locations in the drop down to a particular location type.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams'=>array('termlist_external_key'=>'indicia:location_types')
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
          'name'=>'my_obs_page',
          'caption'=>'Path to My Observations',
          'description'=>'Path used to access the My Observations page after a successful submission.',
          'type'=>'text_input',
          'required'=>true,
          'siteSpecific'=>true
        ),
      )
    );
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
    if (isset($response['error']))
      data_entry_helper::dump_errors($response);
    if ((isset($_REQUEST['page']) && $_REQUEST['page']==='mainSample' && !isset(data_entry_helper::$validation_errors) && !isset($response['error'])) ||
        (isset($_REQUEST['page']) && $_REQUEST['page']==='notes')) {
      // we have just saved the sample page, so move on to the occurrences list,
      // or we have had an error in the notes page
      return self::get_occurrences_form($args, $node, $response);
    } else {
      return self::get_sample_form($args, $node, $response);
    }
  }

  public static function get_sample_form($args, $node, $response) {
    global $user;
    if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
    iform_load_helpers(array('map_helper'));
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $sampleId = isset($_GET['sample_id']) ? $_GET['sample_id'] : null;
    $locationId = null;
    if ($sampleId) {
      data_entry_helper::load_existing_record($auth['read'], 'sample', $sampleId, 'detail', false, true);
      $locationId = data_entry_helper::$entity_to_load['sample:location_id'];
    } else {
      // location ID also might be in the $_POST data after a validation save of a new record
      if (isset($_POST['sample:location_id'])) $locationId = $_POST['sample:location_id'];
    }
    
    $url = explode('?', $args['my_obs_page'], 2);
    $params = NULL;
    $fragment = NULL;
    // fragment is always at the end.
    if(count($url)>1){
      $params = explode('#', $url[1], 2);
      if(count($params)>1) $fragment=$params[1];
      $params=$params[0];
    } else {
      $url = explode('#', $url[0], 2);
      if (count($url)>1) $fragment=$url[1];
    }
    $args['my_obs_page'] = url($url[0], array('query' => $params, 'fragment' => $fragment, 'absolute' => TRUE));
    $r = '<form method="post" id="sample">';
    $r .= $auth['write'];
    $r .= '<input type="hidden" name="page" value="mainSample"/>';
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
    }
    $r .= '<input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'"/>';
    $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: '.(98-(isset($args['percent_width']) ? $args['percent_width'] : 50)).'%">';
    // Output only the locations for this website and location type.
    $availableSites = data_entry_helper::get_population_data(array(
    		'report'=>'library/locations/locations_list',
    		'extraParams' => $auth['read'] + array('website_id' => $args['website_id'], 'location_type_id'=>$args['locationType'],
    				'locattrs'=>'CMS User ID', 'attr_location_cms_user_id'=>$user->uid),
    		'nocache' => true));
    // convert the report data to an array for the lookup, plus one to pass to the JS so it can keep the map updated
    $sitesLookup = array();
    $sitesIds = array();
    $sitesJs = array();
    foreach ($availableSites as $site) {
      $sitesLookup[$site['location_id']]=$site['name'];
      $sitesIds[] = $site['location_id'];
    }
    $sites = data_entry_helper::get_population_data(array(
        'table'=>'location',
        'extraParams' => $auth['read'] + array('website_id' => $args['website_id'], 'id'=>$sitesIds,'view'=>'detail')));
    foreach ($sites as $site) {
      $sitesJs[$site['id']] = $site;
    }
    data_entry_helper::$javascript .= "indiciaData.sites = ".json_encode($sitesJs).";\n";
    if ($locationId) {
      $r .= '<input type="hidden" name="sample:location_id" id="sample_location_id" value="'.$locationId.'"/>';
      // for reload of existing, don't let the user switch the square as that could mess everything up.
      $r .= '<label>'.lang::get('1km square').':</label><span>'.$sitesJs[$locationId]['name'].'</span><br/>' .
            lang::get('<p class="ui-state-highlight page-notice ui-corner-all">Please use the map to select a more precise location for your timed observation.</p>');
      ;
    } else {
      $options = array(
                'label' => lang::get('Select 1km square'),
                'validation' => array('required'),
                'blankText'=>lang::get('Please select'),
                'lookupValues' => $sitesLookup,
                'id' => "sample_location_id"
      );
      // if ($locationId) $options['default'] = $locationId;
      $r .= data_entry_helper::location_select($options) .
            lang::get('<p class="ui-state-highlight page-notice ui-corner-all">After selecting the 1km square, use the map to select a more precise location for your timed observation.</p>');
    }
    // [spatial reference]
    $systems=array();
    foreach(explode(',', str_replace(' ', '', $args['spatial_systems'])) as $system)
      $systems[$system] = lang::get("sref:$system");
    $r .= data_entry_helper::sref_and_system(array('label' => lang::get('Grid Ref'), 'systems' => $systems));
    $r .= data_entry_helper::file_box(array('table'=>'sample_image', 'readAuth' => $auth['read'], 'caption'=>lang::get('Upload photo(s) of timed search area')));
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Field Observation'));
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
    $r .= get_user_profile_hidden_inputs($attributes, $args, '', $auth['read']);
    if(isset($_GET['date'])){
      $r .= '<input type="hidden" name="sample:date" value="'.$_GET['date'].'"/>';
      $r .= '<label>'.lang::get('Date').':</label> <span class="value-label">'.$_GET['date'].'</span><br/>';
    } else {
      if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
        // Date has 4 digit year first (ISO style) - convert date to expected output format
        // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
        $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
        data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
      }
      $r .= data_entry_helper::date_picker(array(
        'label' => lang::get('Date'),
        'fieldname' => 'sample:date',
      ));
    }
    // are there any option overrides for the custom attributes?
    if (isset($args['custom_attribute_options']) && $args['custom_attribute_options']) 
      $blockOptions = get_attr_options_array_with_user_data($args['custom_attribute_options']);
    else 
      $blockOptions=array();
    $r .= get_attribute_html($attributes, $args, array('extraParams'=>$auth['read']), null, $blockOptions);
    $r .= '<input type="hidden" name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $r .= '<input type="submit" value="'.lang::get('Next').'" />';
    $r .= '<a href="'.$args['my_obs_page'].'" class="button">'.lang::get('Cancel').'</a>';
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      $r .= '<button id="delete-button" type="button" class="ui-state-default ui-corner-all" />'.lang::get('Delete').'</button>';
    $r .= "</div>"; // left
    $r .= '<div class="right" style="width: '.(isset($args['percent_width']) ? $args['percent_width'] : 50).'%">';
    // [place search]
    $georefOpts = iform_map_get_georef_options($args, $auth['read']);
    $georefOpts['label'] = lang::get('Search for Place on Map');
    // can't use place search without the driver API key
    if ($georefOpts['driver']=='geoplanet' && empty(helper_config::$geoplanet_api_key))
      $r .= '<span style="display: none;">The form structure includes a place search but needs a geoplanet api key.</span>';
    else
      $r .= data_entry_helper::georeference_lookup($georefOpts);
    // [map]
    $options = iform_map_get_map_options($args, $auth['read']);
    if (!empty(data_entry_helper::$entity_to_load['sample:wkt'])) {
      $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['sample:wkt'];
    }
    $olOptions = iform_map_get_ol_options($args);
    if (!isset($options['standardControls']))
      $options['standardControls']=array('layerSwitcher','panZoomBar');
    $r .= map_helper::map_panel($options, $olOptions);
    data_entry_helper::$javascript .= "
mapInitialisationHooks.push(function(mapdiv) {
  var defaultStyle = new OpenLayers.Style({pointRadius: 6,fillOpacity: 0,strokeColor: \"Red\",strokeWidth: 1});
  var SiteStyleMap = new OpenLayers.StyleMap({\"default\": defaultStyle});
  indiciaData.SiteLayer = new OpenLayers.Layer.Vector('1km square',{styleMap: SiteStyleMap, displayInLayerSwitcher: true});
  mapdiv.map.addLayer(indiciaData.SiteLayer);
  if(jQuery('#sample_location_id').length > 0) {
    if(jQuery('#sample_location_id').val() != ''){
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(indiciaData.sites[jQuery('#sample_location_id').val()].geom);
      indiciaData.SiteLayer.addFeatures([feature]);
      // for existing data we zoom on the site, not this parent location
    } 
    jQuery('#sample_location_id').change(function(){
      indiciaData.SiteLayer.destroyFeatures();
      if(jQuery('#sample_location_id').val() != ''){
        var parser = new OpenLayers.Format.WKT();
        var feature = parser.read(indiciaData.sites[jQuery('#sample_location_id').val()].geom);
        indiciaData.SiteLayer.addFeatures([feature]);
        var layerBounds = indiciaData.SiteLayer.getDataExtent().clone(); // use a clone
        indiciaData.SiteLayer.map.zoomToExtent(layerBounds);
      }
    });
  }
});\n";

    $r .= "</div>"; // right
    $r .= '</form>';
    // Recorder Name - assume Easy Login uid
    if (function_exists('module_exists') && module_exists('easy_login')) {
      $userId = hostsite_get_user_field('indicia_user_id');
 // For non easy login test only     $userId = 1;
      foreach($attributes as $attrID => $attr){
        if(strcasecmp('Recorder Name', $attr["untranslatedCaption"]) == 0 && !empty($userId)){
          // determining which you have used is difficult from a services based autocomplete, esp when the created_by_id is not available on the data.
          data_entry_helper::add_resource('autocomplete');
          data_entry_helper::$javascript .= "bindRecorderNameAutocomplete(".$attrID.", '".$userId."', '".data_entry_helper::$base_url."', '".$args['survey_id']."', '".$auth['read']['auth_token']."', '".$auth['read']['nonce']."');\n";
        }
      }
    }
    if (isset(data_entry_helper::$entity_to_load['sample:id'])){
      // allow deletes if sample id is present.
      data_entry_helper::$javascript .= "jQuery('#delete-button').click(function(){
  if(confirm(\"".lang::get('Are you sure you want to delete this walk?')."\")){
    jQuery('#delete-form').submit();
  } // else do nothing.
});\n";
      // note we only require bare minimum in order to flag a sample as deleted.
      $r .= '<form method="post" id="delete-form" style="display: none;">';
      $r .= $auth['write'];
      $r .= '<input type="hidden" name="page" value="delete"/>';
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
      $r .= '<input type="hidden" name="sample:deleted" value="t"/>';
      $r .= '</form>';
    }
    data_entry_helper::enable_validation('sample');
    return $r;
  }

  public static function get_occurrences_form($args, $node, $response) {
    global $user;
  	if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
  	drupal_add_js('misc/tableheader.js'); // for sticky heading
    data_entry_helper::add_resource('jquery_form');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // did the parent sample previously exist? Default is no.
    $existing=false;
    $url = explode('?', $args['my_obs_page'], 2);
    $params = NULL;
    $fragment = NULL;
    // fragment is always at the end.
    if(count($url)>1){
      $params = explode('#', $url[1], 2);
      if(count($params)>1) $fragment=$params[1];
      $params=$params[0];
    } else {
      $url = explode('#', $url[0], 2);
      if (count($url)>1) $fragment=$url[1];
    }
    $args['my_obs_page'] = url($url[0], array('query' => $params, 'fragment' => $fragment, 'absolute' => TRUE));
    if (isset($_POST['sample:id'])) {
      // have just posted an edit to the existing sample
      $sampleId = $_POST['sample:id'];
      $existing=true;
      data_entry_helper::load_existing_record($auth['read'], 'sample', $sampleId);
    } else {
      if (isset($response['outer_id']))
        // have just posted a new sample.
        $sampleId = $response['outer_id'];
      else {
        $sampleId = $_GET['sample_id'];
        $existing=true;
      }
    }
    $sample = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$sampleId,'deleted'=>'f')
    ));
    $sample=$sample[0];
    $date=$sample['date_start'];
    if (!function_exists('module_exists') || !module_exists('easy_login')) {
      // work out the CMS User sample ID.
      $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Field Observation'));
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'fieldprefix'=>'smpAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'sample_method_id'=>$sampleMethods[0]['id']
      ));
      if (false== ($cmsUserAttr = extract_cms_user_attr($attributes)))
        return 'Easy Login not active: This form is designed to be used with the CMS User ID attribute setup for samples in the survey.';
    }
    $allTaxonMeaningIdsAtSample = array();
    if ($existing) {
      // Only need to load the occurrences for a pre-existing sample
      $o = data_entry_helper::get_population_data(array(
        'report' => 'reports_for_prebuilt_forms/UKBMS/ukbms_occurrences_list_for_sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','sample_id'=>$sampleId,'survey_id'=>$args['survey_id'],'date_from'=>'','date_to'=>'','taxon_group_id'=>'',
            'smpattrs'=>'', 'occattrs'=>$args['occurrence_attribute_ids']),
        // don't cache as this is live data
        'nocache' => true
      ));
      // build an array keyed for easy lookup
      $occurrences = array();
      $attrs = explode(',',$args['occurrence_attribute_ids']);
      if(!isset($o['error'])) foreach($o as $occurrence) {
      	if(!in_array($occurrence['taxon_meaning_id'], $allTaxonMeaningIdsAtSample))
      		$allTaxonMeaningIdsAtSample[] = $occurrence['taxon_meaning_id'];
        $occurrences[$occurrence['taxon_meaning_id']] = array(
          'ttl_id'=>$occurrence['taxa_taxon_list_id'],
          'ttl_id'=>$occurrence['taxa_taxon_list_id'],
          'preferred_ttl_id'=>$occurrence['preferred_ttl_id'],
          'o_id'=>$occurrence['occurrence_id'],
          'processed'=>false
        );
        foreach($attrs as $attr){
          $occurrences[$occurrence['taxon_meaning_id']]['value_'.$attr] = $occurrence['attr_occurrence_'.$attr];
          $occurrences[$occurrence['taxon_meaning_id']]['a_id_'.$attr] = $occurrence['attr_id_occurrence_'.$attr];
        }
      }
      // store it in data for JS to read when populating the grid
      data_entry_helper::$javascript .= "indiciaData.existingOccurrences = ".json_encode($occurrences).";\n";
    } else {
      data_entry_helper::$javascript .= "indiciaData.existingOccurrences = {};\n";
    }
    $occ_attributes = data_entry_helper::getAttributes(array(
    		'valuetable'=>'occurrence_attribute_value',
    		'attrtable'=>'occurrence_attribute',
    		'key'=>'occurrence_id',
    		'fieldprefix'=>'occAttr',
    		'extraParams'=>$auth['read'],
    		'survey_id'=>$args['survey_id'],
    		'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    data_entry_helper::$javascript .= "indiciaData.occurrence_totals = [];\n";
    data_entry_helper::$javascript .= "indiciaData.occurrence_attribute = [];\n";
    data_entry_helper::$javascript .= "indiciaData.occurrence_attribute_ctrl = [];\n";
    $defAttrOptions = array('extraParams'=>$auth['read']+array('orderby'=>'id'), 'suffixTemplate' => 'nosuffix');
    $occ_attributes_captions = array();
    foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr){
      $occ_attributes_captions[$idx] = $occ_attributes[$attr]['caption'];
      unset($occ_attributes[$attr]['caption']);
      $ctrl = data_entry_helper::outputAttribute($occ_attributes[$attr], $defAttrOptions);
      data_entry_helper::$javascript .= "indiciaData.occurrence_totals[".$idx."] = [];\n";
      data_entry_helper::$javascript .= "indiciaData.occurrence_attribute[".$idx."] = $attr;\n";
      data_entry_helper::$javascript .= "indiciaData.occurrence_attribute_ctrl[".$idx."] = jQuery('".(str_replace("\n","",$ctrl))."');\n";
    }
//    $r = "<h2>".$location[0]['name']." on ".$date."</h2>\n";
    $r = '<div id="tabs">';
    $tabs = array('#grid1'=>t($args['species_tab_1'])); // tab 1 is required.
    if(isset($args['taxon_list_id_2']) && $args['taxon_list_id_2']!='')
      $tabs['#grid2']=t(isset($args['species_tab_2']) && $args['species_tab_2'] != '' ? $args['species_tab_2'] : 'Species Tab 2');
    if(isset($args['taxon_list_id_3']) && $args['taxon_list_id_3']!='')
      $tabs['#grid3']=t(isset($args['species_tab_3']) && $args['species_tab_3'] != '' ? $args['species_tab_3'] : 'Species Tab 3');
    if(isset($args['taxon_list_id_4']) && $args['taxon_list_id_4']!='')
      $tabs['#grid4']=t(isset($args['species_tab_4']) && $args['species_tab_4'] != '' ? $args['species_tab_4'] : 'Species Tab 4');
    $tabs['#notes']=lang::get('Notes');
    $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
    data_entry_helper::enable_tabs(array('divId'=>'tabs', 'style'=>'Tabs'));
    // will assume that first table is based on abundance count, so do totals
    $r .= '<div id="grid1"><table id="observation-input1" class="ui-widget species-grid"><thead class="table-header"><tr><th class="ui-widget-header"></th>';
    foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
      $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $occ_attributes_captions[$idx] . '</th>';
    $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th></tr></thead>';

    $r .= '<tbody class="ui-widget-content occs-body"></tbody><tfoot><tr><td>Total</td>';
    foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
      $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
    $r .= '<td class="ui-state-disabled first"></td></tr></tfoot></table><br /><a href="'.$args['my_obs_page'].'" class="button">'.lang::get('Finish').'</a></div>';

    $extraParams = array_merge($auth['read'],
                   array('taxon_list_id' => $args['taxon_list_id_1'],
                         'preferred' => 't',
                         'allow_data_entry' => 't',
                         'view' => 'cache',
                         'orderby' => 'taxonomic_sort_order'));
    if (!empty($args['taxon_filter_field_1']) && !empty($args['taxon_filter_1']))
      $extraParams[$args['taxon_filter_field_1']] = helper_base::explode_lines($args['taxon_filter_1']);
    $taxa = data_entry_helper::get_population_data(array('table' => 'taxa_taxon_list', 'extraParams' => $extraParams));
    data_entry_helper::$javascript .= "indiciaData.speciesList1List = [";
    $first = true;
    foreach($taxa as $taxon){
      data_entry_helper::$javascript .= ($first ? "\n" : ",\n")."{'id':".$taxon['id'].",'taxon_meaning_id':".$taxon['taxon_meaning_id'].",'preferred_language_iso':'".$taxon["preferred_language_iso"]."','default_common_name':'".str_replace("'","\\'", $taxon["default_common_name"])."'}";
      $first = false;
    }
    data_entry_helper::$javascript .= "];\n";
    data_entry_helper::$javascript .= "indiciaData.allTaxonMeaningIdsAtSample = [".implode(',', $allTaxonMeaningIdsAtSample)."];\n";

    if(isset($args['taxon_list_id_2']) && $args['taxon_list_id_2']!=''){
      $r .= '<div id="grid2"><p id="grid2-loading">' . lang::get('Loading - Please Wait') . '</p><table id="observation-input2" class="ui-widget species-grid"><thead class="table-header"><tr><th class="ui-widget-header"></th>';
      foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $occ_attributes_captions[$idx] . '</th>';
      $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th></tr></thead><tbody class="ui-widget-content occs-body"></tbody><tfoot><tr><td>Total</td>';
      foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
        $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
      $r .= '<td class="ui-state-disabled first"></td></tr></tfoot></table><br /><a href="'.$args['my_obs_page'].'" class="button">'.lang::get('Finish').'</a></div>';
    }
    if(isset($args['taxon_list_id_3']) && $args['taxon_list_id_3']!=''){
      $r .= '<div id="grid3"><p id="grid3-loading">' . lang::get('Loading - Please Wait') . '</p><table id="observation-input3" class="ui-widget species-grid"><thead class="table-header"><tr><th class="ui-widget-header"></th>';
      foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $occ_attributes_captions[$idx] . '</th>';
      $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th></tr></thead><tbody class="ui-widget-content occs-body"></tbody><tfoot><tr><td>Total</td>';
      foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
        $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
      $r .= '<td class="ui-state-disabled first"></td></tr></tfoot></table><br /><a href="'.$args['my_obs_page'].'" class="button">'.lang::get('Finish').'</a></div>';
    }
    if(isset($args['taxon_list_id_4']) && $args['taxon_list_id_4']!=''){
      $r .= '<div id="grid4"><p id="grid4-loading">' . lang::get('Loading - Please Wait') . '</p><table id="observation-input4" class="ui-widget species-grid"><thead class="table-header"><tr><th class="ui-widget-header"></th>';
      foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $occ_attributes_captions[$idx] . '</th>';
      $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th></tr></thead><tbody class="ui-widget-content occs-body"></tbody><tfoot><tr><td>Total</td>';
      foreach(explode(',',$args['occurrence_attribute_ids']) as $idx => $attr)
        $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
      $r .= '<td class="ui-state-disabled first"></td></tr></tfoot></table>';
      $r .= '<label for="taxonLookupControl4" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl4" name="taxonLookupControl4" >';
      $r .= '<br /><a href="'.$args['my_obs_page'].'" class="button">'.lang::get('Finish').'</a></div>';
    }

    // for the comment form, we want to ensure that if there is a timeout error that it reloads the
    // data as stored in the DB.
    $reload = data_entry_helper::get_reload_link_parts();
    $reload['params']['sample_id'] = $parentSampleId;
    unset($reload['params']['new']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) {
    	// decode params prior to encoding to prevent double encoding.
    	foreach ($reload['params'] as $key => $param) {
    		$reload['params'][$key] = urldecode($param);
    	}
    	$reloadPath .= '?'.http_build_query($reload['params']);
    }
    // fragment is always at the end. discard this.
    $reloadPath = explode('#', $reloadPath, 2);
    $reloadPath = $reloadPath[0];
    $r .= "<div id=\"notes\"><form method=\"post\" id=\"notes_form\" action=\"".$reloadPath."#notes\">\n";
    $r .= $auth['write'];
    $r .= '<input type="hidden" name="sample:id" value="'.$sampleId.'" />' .
          '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>' .
          '<input type="hidden" name="survey_id" value="'.$args['survey_id'].'"/>' .
          '<input type="hidden" name="page" value="notes"/>';
    $r .= '<p class="page-notice ui-state-highlight ui-corner-all">'.
          lang::get('When using this page, please remember that the data is not saved to the database as you go (which is the case for the previous tabs). In order to save the data entered in this page you must click on the Submit button at the bottom of the page.').
          '</p>';
    $r .= data_entry_helper::textarea(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Notes'),
      'helpText'=>"Use this space to input comments about this week's walk."
    ));    
    $r .= '<input type="submit" value="'.lang::get('Submit').'" id="save-button"/></form>';
    $r .= '<br /><a href="'.$args['my_walks_page'].'" class="button">'.lang::get('Finish').'</a></div></div>';
    // enable validation on the comments form in order to include the simplified ajax queuing for the autocomplete.
    data_entry_helper::enable_validation('notes_form');
    
    // A stub form for AJAX posting when we need to create an occurrence
    $r .= '<form style="display: none" id="occ-form" method="post" action="'.iform_ajaxproxy_url($node, 'occurrence').'">';
    $r .= '<input name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input name="occurrence:id" id="occid" />';
    $r .= '<input name="occurrence:taxa_taxon_list_id" id="ttlid" />';
    $r .= '<input name="occurrence:sample_id" value="'.$sampleId.'"/>';
    $r .= '<input name="occAttr:" id="occattr"/>';
    $r .= '<input name="transaction_id" id="transaction_id"/>';
    $r .= '<input name="user_id" value="'.hostsite_get_user_field('user_id', 1).'"/>';
    $r .= '</form>';

    // tell the Javascript where to get species from.
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');

    data_entry_helper::$javascript .= "indiciaData.speciesList1 = ".$args['taxon_list_id_1'].";\n";
    if (!empty($args['taxon_filter_field_1']) && !empty($args['taxon_filter_1'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList1FilterField = '".$args['taxon_filter_field_1']."';\n";
      $filterLines = helper_base::explode_lines($args['taxon_filter_1']);
      data_entry_helper::$javascript .= "indiciaData.speciesList1FilterValues = '".json_encode($filterLines)."';\n";
    }

    data_entry_helper::$javascript .= "indiciaData.speciesList2 = ".(isset($args['taxon_list_id_2']) && $args['taxon_list_id_2'] != "" ? $args['taxon_list_id_2'] : "-1").";\n";
    if (!empty($args['taxon_filter_field_2']) && !empty($args['taxon_filter_2'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList2FilterField = '".$args['taxon_filter_field_2']."';\n";
      $filterLines = helper_base::explode_lines($args['taxon_filter_2']);
      data_entry_helper::$javascript .= "indiciaData.speciesList2FilterValues = ".json_encode($filterLines).";\n";
    }

    data_entry_helper::$javascript .= "indiciaData.speciesList3 = ".(isset($args['taxon_list_id_3']) && $args['taxon_list_id_3'] != "" ? $args['taxon_list_id_3'] : "-1").";\n";
    if (!empty($args['taxon_filter_field_3']) && !empty($args['taxon_filter_3'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList3FilterField = '".$args['taxon_filter_field_3']."';\n";
      $filterLines = helper_base::explode_lines($args['taxon_filter_3']);
      data_entry_helper::$javascript .= "indiciaData.speciesList3FilterValues = ".json_encode($filterLines).";\n";
    }
    
    data_entry_helper::$javascript .= "indiciaData.speciesList4 = ".(isset($args['taxon_list_id_4']) && $args['taxon_list_id_4'] != "" ? $args['taxon_list_id_4'] : "-1").";\n";
    if (!empty($args['taxon_filter_field_4']) && !empty($args['taxon_filter_4'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList4FilterField = '".$args['taxon_filter_field_4']."';\n";
      $filterLines = helper_base::explode_lines($args['taxon_filter_4']);
      data_entry_helper::$javascript .= "indiciaData.speciesList4FilterValues = ".json_encode($filterLines).";\n";
    }
    // allow js to do AJAX by passing in the information it needs to post forms
    data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl4\",\"table#observation-input4\",\"".data_entry_helper::$base_url."index.php/services/data\", indiciaData.speciesList4,
  indiciaData.speciesList4FilterField, indiciaData.speciesList4FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
  \"".lang::get('LANG_Duplicate_Taxon')."\", 25, 4);\n\n";

    data_entry_helper::$javascript .= "indiciaData.indiciaSvc = '".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";
    data_entry_helper::$javascript .= "indiciaData.sample = ".$sampleId.";\n";
    if (function_exists('module_exists') && module_exists('easy_login')) {
      data_entry_helper::$javascript .= "indiciaData.easyLogin = true;\n";
      $userId = hostsite_get_user_field('indicia_user_id');
      if (!empty($userId)) data_entry_helper::$javascript .= "indiciaData.UserID = ".$userId.";\n";
      else return '<p>Easy Login active but could not identify user</p>'; // something is wrong 
    } else {
      data_entry_helper::$javascript .= "indiciaData.easyLogin = false;\n";
      data_entry_helper::$javascript .= "indiciaData.CMSUserAttrID = ".$cmsUserAttr['attributeId'] .";\n";
      data_entry_helper::$javascript .= "indiciaData.CMSUserID = ".$user->uid.";\n";
    }
    // Do an AJAX population of the grid rows.
    data_entry_helper::$javascript .= "loadSpeciesList();
jQuery('#tabs').bind('tabsshow', function(event, ui) {
    var target = ui.panel;
    // first get rid of any previous tables
    jQuery('table.sticky-header').remove();
    jQuery('table.sticky-enabled thead.tableHeader-processed').removeClass('tableHeader-processed');
    jQuery('table.sticky-enabled.tableheader-processed').removeClass('tableheader-processed');
    jQuery('table.species-grid.sticky-enabled').removeClass('sticky-enabled');
    var table = jQuery('#'+target.id+' table.species-grid');
    if(table.length > 0) {
        table.addClass('sticky-enabled');
        if(typeof Drupal.behaviors.tableHeader == 'object') // Drupal 7
          Drupal.behaviors.tableHeader.attach(table.parent());
        else // Drupal6 : it is a function
          Drupal.behaviors.tableHeader(target);
    }
    // remove any hanging autocomplete select list.
    jQuery('.ac_results').hide();
});";
    return $r;
  }

  /**
   * Load the attributes for the sample defined by $entity_to_load
   */
  protected static function getAttributes($args, $auth) {
  	return self::getAttributesForSample($args, $auth, data_entry_helper::$entity_to_load['sample:id']);
  }
  
  /**
   * Load the attributes for the sample defined by a supplied Id.
   */
  private static function getAttributesForSample($args, $auth, $id) {
  	$attrOpts = array(
  			'valuetable'=>'sample_attribute_value'
  			,'attrtable'=>'sample_attribute'
  			,'key'=>'sample_id'
  			,'fieldprefix'=>'smpAttr'
  			,'extraParams'=>$auth['read']
  			,'survey_id'=>$args['survey_id']
  	);
  	if (!empty($id))
  		$attrOpts['id'] = $id;
  	// select only the custom attributes that are for this sample method or all sample methods, if this
  	// form is for a specific sample method.
  	if (!empty($args['sample_method_id']))
  		$attrOpts['sample_method_id']=$args['sample_method_id'];
  	$attributes = data_entry_helper::getAttributes($attrOpts, false);
  	return $attributes;
  }
  

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
  	$values['sample:location_name'] = $values['sample:entered_sref'];
    $submission = submission_builder::build_submission($values, array('model' => 'sample'));
  	return($submission);
  }
  
  /**
   * Override the form redirect to go back to My Walks after the grid is submitted. Leave default redirect (current page)
   * for initial submission of the parent sample.
   */
  public static function get_redirect_on_success($values, $args) {
    return  ($values['page']==='delete') ? $args['my_obs_page'] : '';
  }

}
