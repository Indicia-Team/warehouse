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

/**
 * Prebuilt Indicia data form that lists the output of any report
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_grid {
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array(
      array(
        'name'=>'report_name',
        'caption'=>'Report Name',
        'description'=>'The name of the report file to load into the verification grid, excluding the .xml suffix.',
        'type'=>'string'
      ), array(
        'name' => 'auto_params_form',
        'caption' => 'Automatic Parameters Form',
        'description'=>'If the report requires input parameters, shall an automatic form be generated to allow the user to '.
            'specify those parameters?',
        'type' => 'boolean',
        'default' => true
      ), array(
        'name' => 'param_presets',
        'caption' => 'Preset Parameter Values',
        'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
            'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6.',
        'type' => 'textarea',
        'required' => false
      ), array(
        'name' => 'columns_config',
        'caption' => 'Columns Configuration JSON',
        'description' => 'JSON that describes the columns configuration parameter sent to the report grid component.',
        'type' => 'textarea',
        'required' => false
      ), array(
        'name' => 'refresh_timer',
        'caption' => 'Automatic reload seconds',
        'description' => 'Set this value to the number of seconds you want to elapse before the report will be automatically reloaded, useful for '.
		    'displaying live data updates at BioBlitzes. Combine this with Page to reload to define a sequence of pages that load in turn.',
        'type' => 'int',
        'required' => false
      ), array(
        'name' => 'load_on_refresh',
        'caption' => 'Page to reload',
        'description' => 'Provide the full URL of a page to reload after the number of seconds indicated above.',
        'type' => 'string',
        'required' => false
      ), array(
        'name' => 'items_per_page',
        'caption' => 'Items per page',
        'description' => 'Maximum number of rows shown on each page of the table',
		    'type' => 'int',
        'default' => 20,
        'required' => true
      )
    );
  }

  /**
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'Report grid - a simple grid report';
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    global $user;
    $r = '';
    $presets = array();
    if ($args['param_presets'] != ''){
      $presetList = explode("\n", $args['param_presets']);
      foreach ($presetList as $param) {
        $tokens = explode('=', $param);
        if (count($tokens)==2) {
          $presets[$tokens[0]]=$tokens[1];
        } else {
          $r .= '<div class="page-notice ui-widget ui-widget-content ui-corner-all ui-state-error">' .
              'Some of the preset parameters defined for this page are not of the form param=value.</div>';
        }
      }
    }
    // default columns behaviour is to just include anything returned by the report
    $columns = array();
    // this can be overridden
    if (isset($args['columns_config']) && !empty($args['columns_config']))
      $columns = json_decode($args['columns_config'], true);
    $reportOptions = array(
      'id' => 'report-grid',
      'class' => '',
      'thClass' => '',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => $columns,
      'itemsPerPage' => $args['items_per_page'],
      'autoParamsForm' => $args['auto_params_form'],
      'extraParams' => $presets
    );
    // Add a download link
    $r .= '<a href="'.data_entry_helper::get_report_data(array_merge($reportOptions, array('linkOnly'=>true))). '&mode=csv">Download this report</a>';
    // now the grid
    $r .= data_entry_helper::report_grid($reportOptions);
	// Set up a page refresh for dynamic update of the report at set intervals
	if ($args['refresh_timer']!==0 && is_numeric($args['refresh_timer'])) { // is_int prevents injection
      if (isset($args['load_on_refresh']) && !empty($args['load_on_refresh']))
	    data_entry_helper::$javascript .= "setTimeout('window.location=\"".$args['load_on_refresh']."\";', ".$args['refresh_timer']."*1000 );\n";
	  else
	    data_entry_helper::$javascript .= "setTimeout('window.location.reload( false );', ".$args['refresh_timer']."*1000 );\n";
	}
    return $r;
  }

}