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

require_once('includes/report.php');
require_once('includes/map.php');

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * A list of species, with simple distribution mapping capability
 */
class iform_quick_species_maps {
  
  /** 
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_quick_species_maps_definition() {
    return array(
      'title'=>'Quick Species Maps',
      'category' => 'Reporting',
      'description'=>'A list of species that can quickly be added to a distribution map.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    $r = array_merge(
      iform_report_get_report_parameters(),
      iform_map_get_map_parameters(),  
      array(
        array(
          'name'=>'indicia_species_layer_feature_type',
          'caption'=>'Feature type for Indicia species layer',
          'description'=>'Set to the name of a feature type on GeoServer that will be loaded to display the Indicia species data for the selected record. '.
              'Leave empty for no layer. Normally this should be set to a feature type that exposes the cache_occurrences view.',
          'type'=>'text_input',
          'required'=>false,
          'default'=>'indicia:cache_occurrences',
          'group'=>'Other Map Settings'
        ), array(
          'name'=>'indicia_species_layer_filter_field',
          'caption'=>'Field to filter on',
          'description'=>'Set to the name of a field exposed by the feature type which can be used to filter for the species data to display. Examples include '.
              'taxon_external_key, taxon_meaning_id.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),array(
          'name'=>'indicia_species_layer_sld',
          'caption'=>'SLD file from GeoServer for Indicia species layer',
          'description'=>'Set to the name of an SLD file available on the GeoServer for the rendering of the Indicia species layer, or leave blank for default.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        )
      )
    );
    foreach ($r as &$param) {
      if ($param['name']==='report_name') {
        $param['default'] = 'library/taxa/occurrence_counts_summary_by_external_key';
      }
    }
    return $r;
    
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method 
   */
  public static function get_form($args, $node, $response=null) {
    iform_load_helpers(array('report_helper', 'map_helper'));
    $conn = iform_get_connection_details($node);
    $readAuth = report_helper::get_read_auth($conn['website_id'], $conn['password']);
    $r = '<div style="width: 50%; float: left;">';
    $reportOptions = iform_report_get_report_options($args, $readAuth);
    $reportOptions['rowId']='external_key';
    $r .= report_helper::report_grid($reportOptions);
    $r .= '</div>';
    $r .= '<div style="width: 50%; float: right;">';
    $mapOptions = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args, $readAuth);
    $r .= map_helper::map_panel($mapOptions, $olOptions);
    $r .= '</div>';
    $websites = data_entry_helper::get_population_data(array(
      'table'=>'index_websites_website_agreement',
      'extraParams'=>$readAuth+array('receive_for_reporting'=>'t'),
    ));
    $websiteIds = array();
    foreach ($websites as $website) 
      $websiteIds[] = $website['to_website_id'];
    drupal_set_message(print_r($websiteIds, true));
    if (!empty($args['indicia_species_layer_feature_type']) && !empty(report_helper::$geoserver_url)) {
      $cql='website_id IN ('.implode(',',$websiteIds).') AND '.$args['indicia_species_layer_filter_field']."='{filterValue}'";
      report_helper::$javascript .= "indiciaData.indiciaSpeciesLayer = {\n".
          '  "title":"'.lang::get('Online recording data for this species')."\",\n".
          '  "featureType":"'.$args['indicia_species_layer_feature_type']."\",\n".
          '  "wmsUrl":"'.data_entry_helper::$geoserver_url."wms\",\n".
          "  \"cqlFilter\":\"$cql\",\n".
          "  \"filterField\":\"taxon_meaning_id\",\n".
          '  "sld":"'.(isset($args['indicia_species_layer_sld']) ? $args['indicia_species_layer_sld'] : '')."\"\n".
          "};\n";
    }
    return $r;
  }  

}
