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

require_once('includes/map.php');
require_once('includes/language_utils.php');
/**
 *
 *
 * @package Client
 * @subpackage PrebuiltForms
 * @todo A simple distribution map.
 */
class iform_distribution_map_1 {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_distribution_map_1_definition() {
    return array(
      'title'=>'Distribution Map 1',
      'category' => 'Reporting',      
      'description'=>'Outputs a distribution map using Indicia data from GeoServer. Can output a map for a single species '.
          'or all data from a website. Also features clicking on the data points to see details.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name' => 'include_layer_list',
          'caption' => 'Include Legend',
          'description' => 'Should a legend be shown on the page?',
          'type' => 'boolean',
          'required'=>false,
          'default'=>true,
          'group' => 'Other Map Settings'
        ),
        array(
          'name' => 'include_layer_list_switchers',
          'caption' => 'Include Layer switchers',
          'description' => 'Should the legend include checkboxes and/or radio buttons for controlling layer visibility?',
          'type' => 'boolean',
          'required'=>false,
          'default'=>true,
          'group' => 'Other Map Settings'
        ),
        array(
          'name' => 'include_layer_list_types',
          'caption' => 'Types of layer to include in legend',
          'description' => 'Select which types of layer to include in the legend.',
          'type' => 'select',
          'options' => array(
            'base,overlay' => 'All',
            'base' => 'Base layers only',
            'overlay' => 'Overlays only'
          ),
          'default' => 'base,overlay',
          'group' => 'Other Map Settings'
        ),
        array(
          'name' => 'layer_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the species distribution map layer. Can contain replacement strings {species} or {survey}.',
          'type' => 'textfield',
          'group' => 'Distribution Layer'
        ),
        array(
          'name' => 'wms_feature_type',
          'caption' => 'Feature Type',
          'description' => 'Name of the feature type (layer) exposed in GeoServer to contain the occurrences. This must expose a taxon_meaning_id and a website_id attribute. '.
              'for the filtering. The detail_occurrences view is suitable for this purpose, though make sure you include the namespace, e.g. indicia:detail_occurrences. '.
              'The list of feature type names can be viewed by clicking on the Layer Preview link in the GeoServer installation.',
          'type' => 'textfield',
          'group' => 'Distribution Layer'
        ),
        array(
          'name' => 'wms_style',
          'caption' => 'Style',
          'description' => 'Name of the SLD style file that describes how the distribution points are shown. Leave blank if not sure.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer'
        ),
        array(
          'name' => 'taxon_identifier',
          'caption' => 'Taxon Identifier',
          'description' => 'Meaning ID of the species to load, or external key if specified in the next option. Only use this if this page is for a fixed species, ' .
              'else it can be left blank and the ID or key provided in the URL using a parameter called taxon.',
          'required' => false,
          'type' => 'textfield',
          'group' => 'Distribution Layer'
        ),
        array(
          'name' => 'show_all_species',
          'caption' => 'Show all species',
          'description' => 'Set this flag to show a map of all species occurrences rather than just one species',
          'type' => 'boolean',
          'default' => false,
          'group' => 'Distribution Layer',
          'required'=>false
        ),
        array(
          'name' => 'click_on_occurrences_mode',
          'caption' => 'Click on Occurrences Mode',
          'description' => 'This option allows you to enable querying the occurrences under a point by clicking on the map, displaying the results in a div or popup. '.
              'For this to work, the Indicia Proxy module must be enabled in Drupal and the IForm Proxy Passthrough permission must be granted to the Drupal user roles that '.
              'are able to access the map.',
          'type' => 'select',
          'options' => array(
            'none' => 'None',
            'popup' => 'Popup',
            'div' => 'HTML Div'
          ),
          'default' => 'none',
          'group' => 'Click to Query Occurrences'
        ),
        array(
          'name' => 'click_columns',
          'caption' => 'Click Columns',
          'description' => 'List of the columns to display in the click results, one line per column using format <em>column name</em>=<em>column title</em>. Leave blank for all.',
          'required' => false,
          'type' => 'textarea',
          'default' => "taxon=Species\r\n".
              "entered_sref=Grid Ref\r\n".
              "date_start=Date",
          'group' => 'Click to Query Occurrences'
        ),
        array(
          'name' => 'cql_filter',
          'caption' => 'Distribution layer filter.',
          'description' => 'Any additional filter to apply to the loaded data, using the CQL format. For example "record_status<>\'R\'"',
          'type' => 'textarea',
          'group' => 'Distribution Layer',
          'required' => false
        ),
        array(
          'fieldname' => 'external_key',
          'label' => 'External Key',
          'helpText' => 'Check this box if the taxon is to be identified using the external key instead of the Meaning ID, either through the Taxon ID ' .
              'box above or through the URL taxon parameter.',
          'type' => 'checkbox',
          'group' => 'Distribution Layer',
          'required' => 'false'
        ), 
        array(
          'fieldname'=>'taxon_list_id',
          'label'=>'Species List used to find external key',
          'helpText'=>'If External Key is ticked, then choose the list which has the external key set for each of the taxa that might be displayed.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'group' => 'Distribution Layer',
          'required' => false
        ),
        array(
          'name' => 'refresh_timer',
          'caption' => 'Automatic reload seconds',
          'description' => 'Set this value to the number of seconds you want to elapse before the report will be automatically reloaded, useful for '.
              'displaying live data updates at BioBlitzes. Combine this with Page to reload to define a sequence of pages that load in turn.',
          'type' => 'int',
          'required' => false
        ),
        array(
          'name' => 'load_on_refresh',
          'caption' => 'Page to reload',
          'description' => 'Provide the full URL of a page to reload after the number of seconds indicated above.',
          'type' => 'string',
          'required' => false
        )  
      )
    );
  }

  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {
    iform_load_helpers(array('map_helper', 'data_entry_helper'));
    global $user;
    $readAuth = map_helper::get_read_auth($args['website_id'], $args['password']);
    // setup the map options
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    if (!$args['show_all_species']) {
      if (isset($args['taxon_identifier']) && !empty($args['taxon_identifier']))
        // This page is for a predefined species map
        $taxonIdentifier = $args['taxon_identifier'];
      else {
        if (isset($_GET['taxon']))
        $taxonIdentifier = $_GET['taxon'];
        else
        return lang::get("The distribution map cannot be displayed without a taxon identifier");
      }
      if ($args['external_key']==true) {
        if (empty($args['taxon_list_id']))
          return lang::get('This form is configured with the Distribution Layer - External Key option ticked, but no species list has been configured to '.
              'lookup the external keys against.');
        // the taxon identifier is an external key, so we need to translate to a meaning ID.
        $fetchOpts = array(
        'table' => 'taxa_taxon_list',
        'extraParams' => $readAuth + array(
            'view' => 'detail',
            'external_key' => $taxonIdentifier,
            'taxon_list_id' => $args['taxon_list_id'],
            'preferred' => true
        )
        );
        $prefRecords = data_entry_helper::get_population_data($fetchOpts);
        // We might have multiple records back, e.g. if there are several photos, but we should have a unique meaning id.
        $meaningId=0;
        foreach($prefRecords as $prefRecord) {
        if ($meaningId!=0 && $meaningId!=$prefRecord['taxon_meaning_id'])
            // bomb out, as we  don't know which taxon to display
            return lang::get("The taxon identifier cannot be used to identify a unique taxon.");
        $meaningId = $prefRecord['taxon_meaning_id'];
        }
        if ($meaningId==0)
        return lang::get("The taxon identified by the taxon identifier cannot be found.");
        $meaningId = $prefRecords[0]['taxon_meaning_id'];
      } else
        // the taxon identifier is the meaning ID.
        $meaningId = $taxonIdentifier;
      // We still need to fetch the species record, to get its common name
      $fetchOpts = array(
        'table' => 'taxa_taxon_list',
        'extraParams' => $readAuth + array(
        'view' => 'detail',
        'language_iso' => iform_lang_iso_639_2($user->lang),
        'taxon_meaning_id' => $meaningId
        )
      );
      $taxonRecords = data_entry_helper::get_population_data($fetchOpts);
    }

    $url = map_helper::$geoserver_url.'wms';
    // Get the style if there is one selected
    $style = $args["wms_style"] ? ", styles: '".$args["wms_style"]."'" : '';   
    map_helper::$onload_javascript .= "\n    var filter='website_id=".$args['website_id']."';";
    if ($args['show_all_species'])
      $layerTitle = lang::get('All species occurrences');
    else {
      $layerTitle = str_replace('{species}', $taxonRecords[0]['taxon'], $args['layer_title']);
      map_helper::$onload_javascript .= "\n    filter += ' AND taxon_meaning_id=$meaningId';\n";
    }
    if ($args['cql_filter']) 
      map_helper::$onload_javascript .= "\n    filter += ' AND(".str_replace("'","\'",$args['cql_filter']).")';\n";
    
    $layerTitle = str_replace("'","\'",$layerTitle);
    map_helper::$onload_javascript .= "\n    var distLayer = new OpenLayers.Layer.WMS(
          '".$layerTitle."',
          '$url',
          {layers: '".$args["wms_feature_type"]."', transparent: true, CQL_FILTER: filter $style},
          {isBaseLayer: false, sphericalMercator: true, singleTile: true}
    );\n";
    $options['layers'][]='distLayer';
    if (isset($args['click_on_occurrences_mode']) && $args['click_on_occurrences_mode']!='none') {
      $options['clickableLayersOutputMode'] = $args['click_on_occurrences_mode'];
      $options['clickableLayersOutputDiv'] = 'getinfo-output';
      $options['clickableLayers'][]='distLayer';
      if (!empty($args['click_columns'])) {
        // convert the input column list argument to a structured array to pass to the map
        $inputarr = explode("\r\n", $args['click_columns']);
        $outputarr = array();
        foreach ($inputarr as $coldef) {
          $coldef = explode('=', $coldef);
          $outputarr[$coldef[0]] = $coldef[1];
        }
        $options['clickableLayersOutputColumns'] = $outputarr;
      }
    }
    // This is not a map used for input
    $options['editLayer'] = false;
    // if in Drupal, and IForm proxy is installed, then use this path as OpenLayers proxy
    if (defined('DRUPAL_BOOTSTRAP_CONFIGURATION') && module_exists('iform_proxy')) {
      global $base_url;
      $options['proxy'] = $base_url . '?q=' . variable_get('iform_proxy_path', 'proxy') . '&url=';
    }
    // output a legend
    if (isset($args['include_layer_list_types']))
      $layerTypes = explode(',', $args['include_layer_list_types']);
    else
      $layerTypes = array('base', 'overlay');
    if (!isset($args['include_layer_list']) || $args['include_layer_list'])
      $r .= map_helper::layer_list(array(
        'includeSwitchers' => isset($args['include_layer_list_switchers']) ? $args['include_layer_list_switchers'] : true,
        'includeHiddenLayers' => true,
        'layerTypes' => $layerTypes
      ));
    // output a map    
    $r .= map_helper::map_panel($options, $olOptions);
    // add an empty div for the output of getinfo requests
    if (isset($args['click_on_occurrences_mode']) && $args['click_on_occurrences_mode']=='div') {
      $r .= '<div id="getinfo-output"></div>';
    }
    // Set up a page refresh for dynamic update of the map at set intervals
    if ($args['refresh_timer']!==0 && is_numeric($args['refresh_timer'])) { // is_int prevents injection
      if (isset($args['load_on_refresh']) && !empty($args['load_on_refresh']))
        map_helper::$javascript .= "setTimeout('window.location=\"".$args['load_on_refresh']."\";', ".$args['refresh_timer']."*1000 );\n";
      else
        map_helper::$javascript .= "setTimeout('window.location.reload( false );', ".$args['refresh_timer']."*1000 );\n";
    }
    return $r;
  }

  /**
   * Because the my_dot_map form cannot be submitted, it returns null for the submission structure.
   *
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    return null;
  }

}