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
 * Extension class that supports editing of linear paths on maps.
 */
class extension_paths_editor {

  public static function add_template_locations_to_map($auth, $args, $tabalias, $options, $path) {
    if (empty($options['location_type_id']) || !preg_match('/^\d+$/', $options['location_type_id']))
      throw new exception('Please supply a valid location_type_id option.');
    iform_load_helpers(array('report_helper'));
    $r = report_helper::report_map(array(
      'readAuth' => $auth['read'],
      'dataSource' => 'library/locations/locations_list_mapping',
      'dataSourceLoRes' => 'library/locations/locations_list_mapping',
      'extraParams' => array('location_type_id' => $options['location_type_id']),
      'ajax' => TRUE,
      // handle our own clicking behaviour
      'clickable' => FALSE
    ));
    // output a hidden grid, since the AJAX code for a report_map is in the grid.
    $r .= report_helper::report_grid(array(
      'readAuth' => $auth['read'],
      'dataSource' => 'library/locations/locations_list_mapping',
      'extraParams' => array('location_type_id' => $options['location_type_id']),
      'ajax' => TRUE,
      'class' => 'report-grid hidden'
    ));
    report_helper::$javascript .= "indiciaData.wantPathEditor = true;\n";
    return $r;
  }

  public static function link_to_parent($auth, $args, $tabalias, $options, $path) {
    if (empty($_GET['table']) || $_GET['table']!=='sample' || empty($_GET['id']))
      throw new exception('paths_editor.link_to_parent control needs to be called from a form that saves a sample');
    // construct a query to pull back the parent sample and any existing child samples in one go
    $samples = data_entry_helper::get_population_data(array(
      'table' => 'sample',
      'extraParams' => $auth['read'] + array(
          'query' => json_encode(array('where' => array('id', $_GET['id']), 'orwhere' => array('parent_id', $_GET['id']))),
          'view' => 'detail'
      )
    ));
    $childGeoms = array();
    foreach ($samples as $sample) {
      if ($sample['id']===$_GET['id']) {
        // found the parent sample. Send to JS so it can be shown on the map
        data_entry_helper::$javascript .= "indiciaData.showParentSampleGeom = '$sample[geom]';\n";
        $r = data_entry_helper::hidden_text(array(
          'fieldname' => 'sample:date',
          'default' => $sample['date_start']
        ));
      } else {
        // found an already input child sample
        $childGeoms[] = "'$sample[geom]'";
      }
    }
    // Output some instructions to the user which will depend on whether we are on the first
    // child sample or not.
    if (!empty($options['outputInstructionsTo'])) {
      data_entry_helper::$javascript .= "$('#$options[outputInstructionsTo]').html('$options[firstInstruction]');\n";
    }
    $childGeoms = implode(',', $childGeoms);
    data_entry_helper::$javascript .= "indiciaData.showChildSampleGeoms = [$childGeoms];\n";
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'sample:parent_id',
      'default' => $_GET['id']
    ));
    return $r;
    /*
     * Now, please tell us which plants you found and where they were along the stretch of river. For each point where you found plants along the river, click once on the map to set the location, then on the next tab you can tell us which plants you saw at that point, before continuing to enter further records for other positions along the stretch of river. The stretch of river you walked has been highlighted with a dotted red line on the map for convenience.
     */
  }

  /**
   * A generic extension control button that allows a configured map control to be selected.
   * Useful when the map toolbuttons are not obvious enough.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   * @param $path
   */
  public static function select_map_control($auth, $args, $tabalias, $options, $path) {
    if (!empty($options['instruction'])) {
      data_entry_helper::$javascript .= "indiciaData.select_map_control_$options[control]=\"$options[instruction]\";\n";
    }
    $minZoomLevel = empty($options['minZoomLevel']) ? '' : " data-minzoomlevel=\"$options[minZoomLevel]\"";
    return "<button type=\"button\" class=\"select_map_control\" id=\"select_map_control_$options[control]\" " .
        "data-control=\"$options[control]\"$minZoomLevel>$options[label]</button>\n";
  }

}