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
 * Prebuilt Indicia data form that lists the output of any report on a map.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_map {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_map_definition() {
    return array(
      'title'=>'Report Map',
      'category' => 'Reporting',
      'description'=>'Outputs data from a report onto a map. To work, the report must include a column containing spatial data. '.
          'Can automatically include the report parameters form required for the generation of the report.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
      iform_report_get_report_parameters(),
      iform_map_get_map_parameters(),
      array(
        array(
          'name' => 'layer_picker',
          'caption' => 'Include Layer Picker',
          'description' => 'Choose whether to include a layer picker and where to include it. Use the '.
              'CSS id map-layer-picker for styling.',
          'type' => 'select',
          'required' => true,
          'options' => array(
            'none'=>'Exclude the layer picker',
            'before'=>'Include the layer picker before the map.',
            'after'=>'Include the layer picker after the map.',
          ),
          'default'=>'none'
        ),
        array(
          'name' => 'legend',
          'caption' => 'Include Legend',
          'description' => 'Choose whether to include a legend and where to include it. Use the '.
              'CSS id map-legend for styling.',
          'type' => 'select',
          'required' => true,
          'options' => array(
            'none'=>'Exclude the legend',
            'before'=>'Include the legend before the map.',
            'after'=>'Include the legend after the map.',
          ),
          'default'=>'after'
        )
      )
    );
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    require_once drupal_get_path('module', 'iform').'/client_helpers/report_helper.php';
    require_once drupal_get_path('module', 'iform').'/client_helpers/map_helper.php';
    $auth = report_helper::get_read_write_auth($args['website_id'], $args['password']);
    $reportOptions = iform_report_get_report_options($args, $auth);
    $r = '<div class="ui-helper-clearfix">';
    $r .= '<br/>'.report_helper::report_map($reportOptions);
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    // This is not a map used for input
    $options['editLayer'] = true;
    if ($args['layer_picker']!='none') {
      $picker = array(
        'id'=>'map-layer-picker',
        'includeIcons'=>false,
        'includeSwitchers'=>true,
        'includeHiddenLayers'=>true
      );
      if ($args['layer_picker']=='before')
        $r .= map_helper::layer_list($picker);
      // as we have a layer picker, we can drop the layerSwitcher from the OL map.
      $options['standardControls']=array('panZoom');
    }
    if ($args['legend']!='none') {
      $legend = array(
        'id'=>'map-legend',
        'includeIcons'=>true,
        'includeSwitchers'=>false,
        'includeHiddenLayers'=>false
      );
      if ($args['legend']=='before')
        $r .= map_helper::layer_list($legend);
    }
    $r .= map_helper::map_panel($options, $olOptions);
    if ($args['layer_picker']=='after')
      $r .= map_helper::layer_list($picker);
    if ($args['legend']=='after')
      $r .= map_helper::layer_list($legend);
    $r .= '</div>';
    return $r;
  }

}