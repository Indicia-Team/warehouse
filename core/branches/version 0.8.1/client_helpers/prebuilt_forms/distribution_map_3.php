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
class iform_distribution_map_3 {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_distribution_map_3_definition() {
    return array(
      'title'=>'Distribution Map 3',
      'category' => 'Reporting',      
      'description'=>'Outputs a distribution map using Indicia data from GeoServer. '.
        'Can output a map with up to three layers, each for a single species '
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
          'name' => 'layer_title_1',
          'caption' => 'Layer Caption',
          'default' => '{species}',
          'description' => 'Caption to display for the species distribution map layer. Can contain replacement string {species}.',
          'type' => 'textfield',
          'group' => 'Distribution Layer 1'
        ),
        array(
          'name' => 'wms_feature_type_1',
          'caption' => 'Feature Type',
          'description' => 'Name of the feature type (layer) exposed in GeoServer to contain the occurrences. This must expose a taxon_meaning_id and a website_id attribute. '.
              'for the filtering. The detail_occurrences view is suitable for this purpose, though make sure you include the namespace, e.g. indicia:detail_occurrences. '.
              'The list of feature type names can be viewed by clicking on the Layer Preview link in the GeoServer installation.',
          'type' => 'textfield',
          'group' => 'Distribution Layer 1'
        ),
        array(
          'name' => 'wms_style_1',
          'caption' => 'Style',
          'description' => 'Name of the SLD style file that describes how the distribution points are shown. Leave blank if not sure.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 1'
        ),
        array(
          'name' => 'taxon_identifier_1',
          'caption' => 'Taxon Identifier',
          'description' => 'Meaning ID of the species to load, or external key if specified in the next option. Only use this if this page is for a fixed species, ' .
              'else it can be left blank and the ID or key provided in the URL using a parameter called taxon.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 1'
        ),
        array(
          'name' => 'cql_filter_1',
          'caption' => 'Distribution layer filter.',
          'description' => 'Any additional filter to apply to the loaded data, using the CQL format. For example "record_status<>\'R\'"',
          'type' => 'textarea',
          'required' => false,
          'group' => 'Distribution Layer 1',
        ),
        array(
          'fieldname'=>'taxon_list_id_1',
          'label'=>'Species List',
          'helpText'=>'The species list that species can be selected from.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'group' => 'Distribution Layer 1'
        ),
        array(
          'fieldname' => 'external_key_1',
          'label' => 'External Key',
          'helpText' => 'Check this box if the taxon is to be identified using the external key instead of the Meaning ID, either through the Taxon ID ' .
              'box above or through the URL taxon parameter.',
          'type' => 'checkbox',
          'required' => false,
          'group' => 'Distribution Layer 1',
        ), 
        array(
          'name' => 'layer_title_2',
          'caption' => 'Layer Caption',
          'default' => '{species}',
          'description' => 'Caption to display for the species distribution map layer. Can contain replacement string {species}.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 2'
        ),
        array(
          'name' => 'wms_feature_type_2',
          'caption' => 'Feature Type',
          'description' => 'Name of the feature type (layer) exposed in GeoServer to contain the occurrences. This must expose a taxon_meaning_id and a website_id attribute. '.
              'for the filtering. The detail_occurrences view is suitable for this purpose, though make sure you include the namespace, e.g. indicia:detail_occurrences. '.
              'The list of feature type names can be viewed by clicking on the Layer Preview link in the GeoServer installation.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 2'
        ),
        array(
          'name' => 'wms_style_2',
          'caption' => 'Style',
          'description' => 'Name of the SLD style file that describes how the distribution points are shown. Leave blank if not sure.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 2'
        ),
        array(
          'name' => 'taxon_identifier_2',
          'caption' => 'Taxon Identifier',
          'description' => 'Meaning ID of the species to load, or external key if specified in the next option. Only use this if this page is for a fixed species, ' .
              'else it can be left blank and the ID or key provided in the URL using a parameter called taxon.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 2'
        ),
        array(
          'name' => 'cql_filter_2',
          'caption' => 'Distribution layer filter.',
          'description' => 'Any additional filter to apply to the loaded data, using the CQL format. For example "record_status<>\'R\'"',
          'type' => 'textarea',
          'required' => false,
          'group' => 'Distribution Layer 2',
        ),
        array(
          'fieldname'=>'taxon_list_id_2',
          'label'=>'Species List',
          'helpText'=>'The species list that species can be selected from.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'required' => false,
          'group' => 'Distribution Layer 2'
        ),
        array(
          'fieldname' => 'external_key_2',
          'label' => 'External Key',
          'helpText' => 'Check this box if the taxon is to be identified using the external key instead of the Meaning ID, either through the Taxon ID ' .
              'box above or through the URL taxon parameter.',
          'type' => 'checkbox',
          'required' => false,
          'group' => 'Distribution Layer 2',
        ), 
        array(
          'name' => 'layer_title_3',
          'caption' => 'Layer Caption',
          'default' => '{species}',
          'description' => 'Caption to display for the species distribution map layer. Can contain replacement string {species}.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 3',
        ),
        array(
          'name' => 'wms_feature_type_3',
          'caption' => 'Feature Type',
          'description' => 'Name of the feature type (layer) exposed in GeoServer to contain the occurrences. This must expose a taxon_meaning_id and a website_id attribute. '.
              'for the filtering. The detail_occurrences view is suitable for this purpose, though make sure you include the namespace, e.g. indicia:detail_occurrences. '.
              'The list of feature type names can be viewed by clicking on the Layer Preview link in the GeoServer installation.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 3',
        ),
        array(
          'name' => 'wms_style_3',
          'caption' => 'Style',
          'description' => 'Name of the SLD style file that describes how the distribution points are shown. Leave blank if not sure.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 3',
        ),
        array(
          'name' => 'taxon_identifier_3',
          'caption' => 'Taxon Identifier',
          'description' => 'Meaning ID of the species to load, or external key if specified in the next option. Only use this if this page is for a fixed species, ' .
              'else it can be left blank and the ID or key provided in the URL using a parameter called taxon.',
          'type' => 'textfield',
          'required' => false,
          'group' => 'Distribution Layer 3',
        ),
        array(
          'name' => 'cql_filter_3',
          'caption' => 'Distribution layer filter.',
          'description' => 'Any additional filter to apply to the loaded data, using the CQL format. For example "record_status<>\'R\'"',
          'type' => 'textarea',
          'required' => false,
          'group' => 'Distribution Layer 3',
        ),
        array(
          'fieldname'=>'taxon_list_id_3',
          'label'=>'Species List',
          'helpText'=>'The species list that species can be selected from.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title',
          'required' => false,
          'group' => 'Distribution Layer 3',
        ),
        array(
          'fieldname' => 'external_key_3',
          'label' => 'External Key',
          'helpText' => 'Check this box if the taxon is to be identified using the external key instead of the Meaning ID, either through the Taxon ID ' .
              'box above or through the URL taxon parameter.',
          'type' => 'checkbox',
          'required' => false,
          'group' => 'Distribution Layer 3',
        ), 
        array(
          'name' => 'refresh_timer',
          'caption' => 'Automatic reload seconds',
          'description' => 'Set this value to the number of seconds you want to elapse before the report will be automatically reloaded, useful for '.
              'displaying live data updates at BioBlitzes. Combine this with Page to reload to define a sequence of pages that load in turn.',
          'type' => 'int',
          'required' => false,
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
    iform_load_helpers(array('map_helper'));
    $readAuth = map_helper::get_read_auth($args['website_id'], $args['password']);
    // setup the map options
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    
    for ($layer = 1; $layer <= 3; $layer++) {
      $argTitle = $args["layer_title_$layer"];
      if (isset($argTitle) && !empty($argTitle)) {
        //if there is no title then ignore the layer
        $meaningId = self::get_meaning_id($layer, $args, $readAuth);
        $taxon = self::get_taxon($meaningId, $readAuth);
        $layerTitle = str_replace('{species}', $taxon, $argTitle);
        $layerTitle = str_replace("'", "\'", $layerTitle);
        $url = map_helper::$geoserver_url.'wms';

        $argFeature = $args["wms_feature_type_$layer"];
        $layers = "layers: '$argFeature'";

        $argStyle = $args["wms_style_$layer"];
        $style = $argStyle ? ", styles: '$argStyle'" : ''; 

        $argWebsite = $args["website_id"];
        $filter = ", CQL_FILTER: 'website_id=$argWebsite AND taxon_meaning_id=$meaningId";

        $argCql = $args["cql_filter_$layer"];
        if ($argCql) {
          $arg = str_replace("'", "\'", $argCql);
          $filter .= " AND($argCql)'";
        }
        else
          $filter .= "'";

        $script = "  var distLayer$layer = new OpenLayers.Layer.WMS(";
        $script .= "'$layerTitle', '$url',";
        $script .= "{" ."$layers, transparent: true $filter $style},";
        $script .= "{isBaseLayer: false, sphericalMercator: true, singleTile: true}";     
        $script .= ");\n";                 
        map_helper::$onload_javascript .= $script;

        $options['layers'][] = "distLayer$layer";
      }
    }
    
    // This is not a map used for input
    $options['editLayer'] = false;
    // output a legend
    $r .= map_helper::layer_list(array(
      'includeSwitchers' => true,
      'includeHiddenLayers' => true
    ));
    // output a map    
    $r .= map_helper::map_panel($options, $olOptions);
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
  
  /**
   * Figures out the meaning ID of the taxon to show on the layer.
   *
   * @param int $layer The map layer we are preparing.
   * @param array $args The options specified in the form configuration.
   * @param $readAuth Read authentication.
   * @return The meaning ID or an error message.
   */
  private static function get_meaning_id($layer, $args, $readAuth) {
    $argIdent = $args["taxon_identifier_$layer"];
    $getIdent = $_GET["taxon_$layer"];

    if (isset($argIdent) && !empty($argIdent))
      // This page is for a predefined species map
      $taxonIdentifier = $argIdent;
    elseif (isset($getIdent))
      $taxonIdentifier = $getIdent;
    else
      return lang::get("Layer $layer cannot be displayed without a taxon identifier");

    $argKey = $args["external_key_$layer"];
    if ($argKey == true) {
      // the taxon identifier is an external key, so we need to translate to a meaning ID.
      $argList = $args["taxon_list_id_$layer"];
      $meaningId = self::key_to_meaning($taxonIdentifier, $argList, $readAuth);
    } else {
      // the taxon identifier is the meaning ID.
      $meaningId = $taxonIdentifier;
    }

    return $meaningId;
  }
  
  /**
   * Converts an external key to a meaning ID for a taxon.
   *
   * @param string $key The external key of the taxon.
   * @param int $list The id of the species list to search.
   * @param array $readAuth Read authentication.
   * @return The meaning ID or an error message.
   */
  private static function key_to_meaning($key, $list, $readAuth) {
    $fetchOpts = array(
    'table' => 'taxa_taxon_list',
    'extraParams' => $readAuth + array(
        'view' => 'detail',
        'external_key' => $key,
        'taxon_list_id' => $list,
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
    
    return $meaningId;    
  }

  /**
   * Gets a taxon name from a Meaning ID.
   *
   * @param int $meaningId The map layer we are preparing.
   * @param array $readAuth Read authentication.
   * @return The taxon name.
   */
  private static function get_taxon($meaningId, $readAuth) {
    global $user;
    $fetchOpts = array(
      'table' => 'taxa_taxon_list',
      'extraParams' => $readAuth + array(
      'view' => 'detail',
      'language_iso' => iform_lang_iso_639_2($user->lang),
      'taxon_meaning_id' => $meaningId
      )
    );
    $taxonRecords = data_entry_helper::get_population_data($fetchOpts);
    return $taxonRecords[0]['taxon'];
  }
}