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
/**
 *
 *
 * @package Client
 * @subpackage PrebuiltForms
 * @todo Provide form description in this comment block.
 * @todo Rename the form class to iform_...
 */
class iform_my_dot_map {

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      array(      
        // Distribution layer 1
        array(
          'name' => 'wms_dist_1_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the optional WMS full species distribution map layer',
          'type' => 'textfield',
          'group'=>'Distribution Layer 1' ,
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_1_internal',
          'caption' => 'Layer 1 uses GeoServer to access Indicia database?',
          'description' => 'Check this box if layer 1 uses a GeoServer instance to access the Indicia database.',
          'type' => 'checkbox',
          'group'=>'Distribution Layer 1' ,
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_1_url',
          'caption' => 'Service URL (External Layers Only)',
          'description' => 'URL of the WMS service to display for this layer. Leave blank '.
              'if using GeoServer to access this instance of Indicia.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 1',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_1_layer',
          'caption' => 'Layer Name',
          'description' => 'Layer name of the WMS service layer. If using GeoServer to access this instance of Indicia, please ensure that the '.
              'detail_occurrences view is exposed as a feature type and the name and prefix is given here.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 1',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_1_filter_against',
          'caption' => 'What to Filter Against?',
          'description' => 'Select what to match this layer against. The layer shown will be those points which match the previously saved record '.
            'on the selected value.',
          'type' => 'select',
          'options' => array(
            // Developer note - these fields should be in the occurrence detail view.
            'none' => 'No Filter',
            'taxa_taxon_list_id' => 'Species',
            'external_key' => 'Species using External Key',
            'survey_id' => 'Survey'
          ),
          'group'=>'Distribution Layer 1',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_1_filter_field',
          'caption' => 'Field in WMS Dataset to Filter Against (External Layers Only)',
          'description' => 'If using an external layer, specify the name of the field in the database table underlying the WMS layer which you want to filter against. '.
              'Leave blank for layers using the GeoServer set up for this instance of Indicia.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 1',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_1_style',
          'caption' => 'Style',
          'description' => 'Name of the style to load for this layer (e.g. the style registered on GeoServer you want to use). This style must exist, '.
              'and the setting is case sensitive.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 1',
          'required'=>false
        ),
        // Distribution layer 2
        array(
          'name' => 'wms_dist_2_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the optional WMS full species distribution map layer',
          'type' => 'textfield',
          'group'=>'Distribution Layer 2' ,
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_2_internal',
          'caption' => 'Layer 2 uses GeoServer to access Indicia database?',
          'description' => 'Check this box if layer 2 uses a GeoServer instance to access the Indicia database.',
          'type' => 'checkbox',
          'group'=>'Distribution Layer 2' ,
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_2_url',
          'caption' => 'Service URL  (External Layers Only)',
          'description' => 'URL of the WMS service to display for this layer. Leave blank '.
              'if using GeoServer to access this instance of Indicia.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 2',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_2_layer',
          'caption' => 'Layer Name',
          'description' => 'Layer name of the WMS service layer. If using GeoServer to access this instance of Indicia, please ensure that the '.
              'detail_occurrences view is exposed as a feature type and the name and prefix is given here.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 2',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_2_filter_against',
          'caption' => 'What to Filter Against?',
          'description' => 'Select what to match this layer against. The layer shown will be those points which match the previously saved record '.
            'on the selected value.',
          'type' => 'select',
          'options' => array(
            // Developer note - these fields should be in the occurrence detail view.
            'none' => 'No Filter',
            'taxa_taxon_list_id' => 'Species',
            'external_key' => 'Species using External Key',
            'survey_id' => 'Survey'
          ),
          'group'=>'Distribution Layer 2',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_2_filter_field',
          'caption' => 'Field in WMS Dataset to Filter Against  (External Layers Only)',
          'description' => 'If using an external layer, specify the name of the field in the database table underlying the WMS layer which you want to filter against. '.
              'Leave blank for layers using the GeoServer set up for this instance of Indicia.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 2',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_2_style',
          'caption' => 'Style',
          'description' => 'Name of the style to load for this layer (e.g. the style registered on GeoServer you want to use). This style must exist, '.
              'and the setting is case sensitive.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 2',
          'required'=>false
        ),
        // Distribution layer 3
        array(
          'name' => 'wms_dist_3_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the optional WMS full species distribution map layer',
          'type' => 'textfield',
          'group'=>'Distribution Layer 3' ,
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_3_internal',
          'caption' => 'Layer 3 uses GeoServer to access Indicia database?',
          'description' => 'Check this box if layer 3 uses a GeoServer instance to access the Indicia database.',
          'type' => 'checkbox',
          'group'=>'Distribution Layer 3' ,
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_3_url',
          'caption' => 'Service URL',
          'description' => 'URL of the WMS service to display for this layer. Leave blank '.
              'if using GeoServer to access this instance of Indicia.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 3',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_3_layer',
          'caption' => 'Layer Name',
          'description' => 'Layer name of the WMS service layer. If using GeoServer to access this instance of Indicia, please ensure that the '.
              'detail_occurrences view is exposed as a feature type and the name and prefix is given here.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 3',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_3_filter_against',
          'caption' => 'What to Filter Against?',
          'description' => 'Select what to match this layer against. The layer shown will be those points which match the previously saved record '.
            'on the selected value.',
          'type' => 'select',
          'options' => array(
            // Developer note - these fields should be in the occurrence detail view.
            'none' => 'No Filter',
            'taxa_taxon_list_id' => 'Species',
            'external_key' => 'Species using External Key',
            'survey_id' => 'Survey'
          ),
          'group'=>'Distribution Layer 3',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_3_filter_field',
          'caption' => 'Field in WMS Dataset to Filter Against',
          'description' => 'If using an external layer, specify the name of the field in the database table underlying the WMS layer which you want to filter against. '.
              'Leave blank for layers using the GeoServer set up for this instance of Indicia.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 3',
          'required'=>false
        ),
        array(
          'name' => 'wms_dist_3_style',
          'caption' => 'Style',
          'description' => 'Name of the style to load for this layer (e.g. the style registered on GeoServer you want to use).',
          'type' => 'textfield',
          'group'=>'Distribution Layer 3',
          'required'=>false
        )
      )
    );
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   * @todo: Implement this method
   */
  public static function get_title() {
    return 'My dot map';
  }

  /**
   * Return the generated form output.
   * @return Form HTML.
   * @todo: Implement this method
   */
  public static function get_form($args) {
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    // setup the map options
    $options = iform_map_get_map_options($args, $readAuth);
    if (array_key_exists('table', $_GET) && $_GET['table']=='sample') {
      // Use a cUrl request to get the data from Indicia which contains the value we need to filter against
      // Read the record that was just posted.
      $fetchOpts = array(
        'table'=>'occurrence',
        'extraParams' => $readAuth + array('sample_id'=>$_GET['id'], 'view' => 'detail')
      );
      // @todo Error handling on the response
      $occurrence = data_entry_helper::get_population_data($fetchOpts);      
    }
    // Add the 3 distribution layers if present
    $layerName = self::build_distribution_layer(1, $args, $occurrence);
    if ($layerName) $options['layers'][] = $layerName;
    $layerName = self::build_distribution_layer(2, $args, $occurrence);
    if ($layerName) $options['layers'][] = $layerName;
    $layerName = self::build_distribution_layer(3, $args, $occurrence);
    if ($layerName) $options['layers'][] = $layerName;
    // Now output a grid of the occurrences that were just saved.
    if (isset($occurrence)) {
      $r = '<div class="page-notice ui-widget ui-corner-all">';
      $r .= "<table><thead><tr><th>Species</th><th>Date</th><th>Spatial Reference</th></tr></thead>\n";
      $r .= "<tbody>\n";      
      foreach ($occurrence as $record) {
        $r .= "<tr><td>".$record['taxon']."</td><td>".$record['date_start']."</td><td>".$record['entered_sref']."</td></tr>\n";
      }
      $r .= "</tbody></table>\n";
    }
    $r .= data_entry_helper::map_panel($options);
    return $r;
  }

  /**
   * Creates the JavaScript to build one of the 3 optional distribution layers, and returns the name of the
   * layer it built.
   * @param int $layerId Id of the layer, 1, 2 or 3.
   * @param array List of arguments supplied to this form from the Drupal configuration.
   * @param string $occurrence Response from data services for a request for the posted occurrence(s).
   * @return string Name of the layer object built in JavaScript.
   */
  private static function build_distribution_layer($layerId, $args, $occurrence) {
    if ($args["wms_dist_$layerId"."_title"]) {
      // if we have a filter specified, then set it up. Note we can only do this if the sample id is passed in at the moment.
      // @todo support passing an occurrence ID.
      if ($args["wms_dist_$layerId"."_filter_against"]!='none' && array_key_exists('table', $_GET) && $_GET['table']=='sample') {
        // Build a list of filters for each record. If there are multiple, then wrap in an OR filter.
        data_entry_helper::$javascript .= "var filters = new Array();\n";
        $filterField = $args["wms_dist_$layerId"."_internal"] ? $args["wms_dist_$layerId"."_filter_against"] : $args["wms_dist_$layerId"."_filter_field"];
        // Use an array of handled values so we only build each distinct filter once
        $handled = array();
        foreach($occurrence as $record) {
          $filterValue = $record[$args["wms_dist_$layerId"."_filter_against"]];
          if (!in_array($filterValue, $handled)) {
            data_entry_helper::$javascript .= "filters.push(new OpenLayers.Filter.Comparison({
                type: OpenLayers.Filter.Comparison.EQUAL_TO,
                property: '".$filterField."',
                value: '".$filterValue."'
                }));\n";
            $handled[] = $filterValue;
          }
        }
        // JavaScript to build the filter as XML. If many filter rows, wrap them in an OR logical filter.
        data_entry_helper::$javascript .= "
var filterObj;
if (filters.length==1) {
  filterObj = filters[0];
} else {
  filterObj = new OpenLayers.Filter.Logical({
    type: OpenLayers.Filter.Logical.OR,
    filters: filters
  });
}
var filter = $.fn.indiciaMapPanel.convertFilterToText(filterObj);\n";
      } else {
        data_entry_helper::$javascript .= "var filter = null;\n";
      }
      // Get the url, either the external one specified, or our internally registered GeoServer
      $url = $args["wms_dist_$layerId"."_internal"] ? data_entry_helper::$geoserver_url.'wms' : $args["wms_dist_$layerId"."_url"];
      // Get the style if there is one selected
      $style = $args["wms_dist_$layerId"."_style"] ? ", styles: '".$args["wms_dist_$layerId"."_style"]."'" : '';

      data_entry_helper::$javascript .= "var distLayer$layerId = new OpenLayers.Layer.WMS(
        '".$args["wms_dist_$layerId"."_title"]."',
        '$url',
        {layers: '".$args["wms_dist_$layerId"."_layer"]."', transparent: true, filter: filter $style},
        {isBaseLayer: false, opacity: 0.5, sphericalMercator: true, singleTile: true}
      );\n";
      return "distLayer$layerId";
    }
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