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

/**
 * Prebuilt Indicia data form that lists the output of any report
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_grid {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_grid_definition() {
    return array(
      'title'=>'Report Grid',
      'category' => 'Reporting',
      'description'=>'Outputs a grid of data loaded from an Indicia report. Can automatically include the report parameters form required for the '.
          'generation of the report.',
      'helpLink' => 'http://code.google.com/p/indicia/wiki/PrebuiltFormReportGrid'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
      iform_report_get_report_parameters(),
      array(
        array(
          'name' => 'columns_config',
          'caption' => 'Columns Configuration JSON',
          'description' => 'JSON that describes the columns configuration parameter sent to the report grid component.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Report Settings'
        ), array(
          'name' => 'gallery_col_count',
          'caption' => 'Gallery Column Count',
          'description' => 'If set to a value greater than one, then each grid row will contain more than one record of data from the database, allowing '.
              ' a gallery style view to be built.',
          'type' => 'int',
          'required' => false,
          'default' => 1,
          'group'=>'Report Settings'
        ),
        array(
          'name' => 'items_per_page',
          'caption' => 'Items per page',
          'description' => 'Maximum number of rows shown on each page of the table',
          'type' => 'int',
          'default' => 20,
          'required' => true,
          'group'=>'Report Settings'
        ),
        array(
          'name' => 'download_link',
          'caption' => 'Download link',
          'description' => 'Should a link be made available to download the report content as CSV?',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Report Settings'
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
    // Add a download link - get_report_data does not use paramDefaults but needs all settings in the extraParams
    if (!isset($args['download_link']) || $args['download_link'])
      $r .= '<br/>'.report_helper::report_download_link($reportOptions);
    // now the grid
    $r .= '<br/>'.report_helper::report_grid($reportOptions);
    return $r;
  }

}