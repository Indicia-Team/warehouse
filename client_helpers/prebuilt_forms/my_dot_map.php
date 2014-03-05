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
    $filters = array(
      // Developer note - these fields should be in the report used for the grid.
      'none' => 'No Filter',
      'taxon_meaning_id' => 'Species',
      'external_key' => 'Species using External Key',
      'survey_id' => 'Survey',
      'sample_id' => 'Submitted data'
    );
    return array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name' => 'hide_grid',
          'caption' => 'Hide grid',
          'description' => 'Check this box to hide the grid of the records just entered.',
          'type' => 'checkbox',
          'group' => 'Other IForm Parameters' ,
          'required' => false,
          'default' => false,
        ),
        // Distribution layer 1
        array(
          'name' => 'wms_dist_1_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the optional WMS full species distribution map layer. Can contain replacement strings {species} or {survey}.',
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
          'options' => $filters,
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
        array(
          'name' => 'wms_dist_1_opacity',
          'caption' => 'Opacity',
          'description' => 'Opacity of layer 1, ranging from 0 (not visible) to 1 (fully opaque). If you want to print this map using Internet Explorer 8 or earlier it is recommended '.
             'that you set this to 1 otherwise the printout may not render correctly.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 1',
          'required'=>false
        ),
        // Distribution layer 2
        array(
          'name' => 'wms_dist_2_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the optional WMS full species distribution map layer. Can contain replacement strings {species} or {survey}.',
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
          'options' => $filters,
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
        array(
          'name' => 'wms_dist_2_opacity',
          'caption' => 'Opacity',
          'description' => 'Opacity of layer 2, ranging from 0 (not visible) to 1 (fully opaque). If you want to print this map using Internet Explorer 8 or earlier it is recommended '.
             'that you set this to 1 otherwise the printout may not render correctly.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 2',
          'required'=>false
        ),
        // Distribution layer 3
        array(
          'name' => 'wms_dist_3_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the optional WMS full species distribution map layer. Can contain replacement strings {species} or {survey}.',
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
          'options' => $filters,
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
        ),
        array(
          'name' => 'wms_dist_3_opacity',
          'caption' => 'Opacity',
          'description' => 'Opacity of layer 3, ranging from 0 (not visible) to 1 (fully opaque). If you want to print this map using Internet Explorer 8 or earlier it is recommended '.
             'that set this to 1 otherwise the printout may not render correctly.',
          'type' => 'textfield',
          'group'=>'Distribution Layer 3',
          'required'=>false
        ),
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
    global $user;
    $lang = isset($user) ? iform_lang_iso_639_2($user->lang) : 'eng';
    if (function_exists('iform_load_helpers')) {
      iform_load_helpers(array('map_helper'));
    } else {
      require_once dirname(dirname(__FILE__)) . '/map_helper.php';
    }
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $r = '';
    // setup the map options
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    if (array_key_exists('table', $_GET) && $_GET['table']=='sample') {
      // Use a cUrl request to get the data from Indicia which contains the value we need to filter against
      // Read the record that was just posted.
      $fetchOpts = array(
        'dataSource'=>'reports_for_prebuilt_forms/my_dot_map/occurrences_list',
        'mode'=>'report',
        'readAuth' => $readAuth,
        'extraParams' => array('sample_id' => $_GET['id'], 'language'=>$lang)
      );
      // @todo Error handling on the response
      $occurrence = data_entry_helper::get_report_data($fetchOpts);
      self::prepare_layer_titles($args, $occurrence);
      // Add the 3 distribution layers if present. Reverse the order so 1st layer is topmost
      $layerName = self::build_distribution_layer(3, $args, $occurrence);
      if ($layerName) {
        $options['layers'][] = $layerName;
      }
      $layerName = self::build_distribution_layer(2, $args, $occurrence);
      if ($layerName) {
        $options['layers'][] = $layerName;
      }
      $layerName = self::build_distribution_layer(1, $args, $occurrence);            
      if ($layerName) {        
        $options['layers'][] = $layerName;
      }
      if ($layerName) $options['layers'][] = $layerName;
      // This is not a map used for input
      $options['editLayer']=false;
      
      if ($args['hide_grid'] == false) {
        // Now output a grid of the occurrences that were just saved.
        $r .= "<table class=\"submission\"><thead><tr><th>".lang::get('Species')."</th><th>".lang::get('Latin Name')."</th><th>".lang::get('Date')."</th><th>".lang::get('Spatial Ref')."</th></tr></thead>\n";
        $r .= "<tbody>\n";
        foreach ($occurrence as $record) {
          $r .= '<tr class="biota"><td>'.$record['lt4_taxon'].'</td><td class="binomial"><em>'.$record['lt7_taxon'].'</em></td><td>'.$record['lt0_date_start'].'</td><td>'.$record['lt0_entered_sref']."</td></tr>\n";
        }
        $r .= "</tbody></table>\n";
      }
    }
    $r .= '<div id="mapandlegend">';
    $r .= map_helper::layer_list(array(
      'id'=>'legend',
      'includeSwitchers' => false,
      'includeHiddenLayers' => false,
      'includeIcons' => true,
      'layerTypes' => array('overlay')
    ));
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>';
    return $r;
  }

  /**
   * Perform replacements on the legend titles. Replaces {species} with the species name and
   * {survey} with the survey name,
   * @access private
   */
  private static function prepare_layer_titles(&$args, $occurrence) {
    $species = array();
    foreach ($occurrence as $record) {
      $species[] = empty($record['lt4_taxon']) ? $record['lt7_taxon'] : $record['lt4_taxon'];
      $survey = $record['lt8_title'];
    }
    $last=array_pop($species);
    $species = implode(', ',$species);
    $species.= (empty($species) ? '' : ' ' . lang::get('and') . ' ') . $last;
    
    for ($i = 1; $i<=3; $i++) {
      $args['wms_dist_'.$i.'_title'] = str_replace(array('{species}','{survey}'), array($species, $survey), $args['wms_dist_'.$i.'_title']);
    }
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
    // create a mapping from the field names that we can filter to the fields in the occurrence list report
    $filterMappings = array(
      'taxon_meaning_id' => 'lt2_taxon_meaning_id',
      'external_key' => 'lt4_external_key',
      'survey_id' => 'lt8_id',
      'sample_id' => 'lt0_id'
    );
    $filter = '';
    if ($args["wms_dist_$layerId"."_title"]) {
      // if we have a filter specified, then set it up. Note we can only do this if the sample id is passed in at the moment.
      // @todo support passing an occurrence ID.
      if ($args["wms_dist_$layerId"."_filter_against"]!='none' && array_key_exists('table', $_GET) && $_GET['table']=='sample') {
        // Build a list of filters for each record. If there are multiple, then wrap in an OR filter.
        data_entry_helper::$onload_javascript .= "var filters = new Array();\n";
        $filterField = $args["wms_dist_$layerId"."_internal"] ? $args["wms_dist_$layerId"."_filter_against"] : $args["wms_dist_$layerId"."_filter_field"];
        // Use an array of handled values so we only build each distinct filter once
        $handled = array();        
        foreach($occurrence as $record) {
          $filterValue = $record[$filterMappings[$args["wms_dist_$layerId"."_filter_against"]]];
          if (!in_array($filterValue, $handled)) {
            $filter .= ($filter==='' ? '' : ' OR ') . "$filterField=$filterValue";
            $handled[] = $filterValue;
          }
        }
      }
      // force a filter on the website ID.
      if ($filter!=='') 
        $filter = "($filter) AND ";
      $filter .= "website_id=".$args['website_id'];     
      // Get the url, either the external one specified, or our internally registered GeoServer
      $url = $args["wms_dist_$layerId"."_internal"] ? data_entry_helper::$geoserver_url.'wms' : $args["wms_dist_$layerId"."_url"];
      // Get the style if there is one selected
      $style = $args["wms_dist_$layerId"."_style"] ? ", styles: '".$args["wms_dist_$layerId"."_style"]."'" : '';
      // and also the opacity
      $opacity = $args["wms_dist_$layerId"."_opacity"] ? $args["wms_dist_$layerId"."_opacity"] : 1;
      if ($opacity!=1) 
        $opacity = " opacity: $opacity,";
      else
        // don't set opacity if not required as it messes up printing in IE<=8
        $opacity = '';
      $filter = ', CQL_FILTER: "'.$filter.'"';
      data_entry_helper::$onload_javascript .= "var distLayer$layerId = new OpenLayers.Layer.WMS(
        '".str_replace("'", "\'", $args["wms_dist_$layerId"."_title"])."',
        '$url',
        {layers: '".$args["wms_dist_$layerId"."_layer"]."', transparent: true $filter $style},
        {isBaseLayer: false,$opacity sphericalMercator: true, singleTile: true}
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