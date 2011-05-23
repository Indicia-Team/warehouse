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
 * Prebuilt Indicia data form that lists the output of any report on a chart
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_chart {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_chart_definition() {
    return array(
      'title'=>'Report Chart',
      'category' => 'Reporting',
      'description'=>'Outputs a chart of data loaded from an Indicia report. Can automatically include the report parameters form required for the '.
          'generation of the report.'
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
          'name' => 'chart_type',
          'caption' => 'Chart Type',
          'description' => 'Type of chart.',
          'type' => 'select',
          'lookupValues' => array('line'=>lang::get('Line'), 'bar'=>lang::get('bar'), 'pie'=>lang::get('Pie')),
          'required' => true,
          'default' => 'line',
          'group'=>'Basic Chart Options'
        ),
        array(
          'name' => 'width',
          'caption' => 'Chart Width',
          'description' => 'Width of the output chart in pixels.',
          'type' => 'text_input',
          'required' => true,
          'default' => 500,
          'group'=>'Basic Chart Options'
        ),
        array(
          'name' => 'height',
          'caption' => 'Chart Height',
          'description' => 'Height of the output chart in pixels.',
          'type' => 'text_input',
          'required' => true,
          'default' => 500,
          'group'=>'Basic Chart Options'
        ),
        array(
          'name' => 'y_values',
          'caption' => 'Y Values',
          'description' => 'Fields containing the y values or pie segment sizes, comma separated if this is a multi-series chart.',
          'type' => 'text_input',
          'required' => true,
          'group'=>'Basic Chart Options'
        ),
        array(
          'name' => 'x_values',
          'caption' => 'X Values',
          'description' => 'Fields containing the x values when the x-axis contains numerical values rather than labels, '.
              'comma separated if this is a multi-series chart.',
          'type' => 'text_input',
          'required' => false,
          'group'=>'Basic Chart Options'
        ),
        array(
          'name' => 'x_labels',
          'caption' => 'X Labels',
          'description' => 'Fields containing the x labels or pie segment titles when the x-axis contains arbitrary labels, '.
              'comma separated if this is a multi-series chart.',
          'type' => 'text_input',
          'required' => false,
          'group'=>'Basic Chart Options'
        ),
        array(
          'name' => 'renderer_options',
          'caption' => 'Renderer Options',
          'description' => 'JSON describing the renderer options to pass to the chart. For full details of the options available, '.
              'see <a href="http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html">bar chart renderer options</a>, '.
              '<a href="http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html">line charts rendered options<a/> or '.
              '<a href="http://www.jqplot.com/docs/files/plugins/jqplot-pieRenderer-js.html">pie chart renderer options</a>.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Advanced Chart Options'
        ),
        array(
          'name' => 'legend_options',
          'caption' => 'Legend Options',
          'description' => 'JSON describing the legend options to pass to the chart. For full details of the options available, '.
              'see <a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Legend">chart legend options</a>. '.
              'For example, set the value to <em>{"show":true,"location":"ne"}</em> to show the legend in the top-right '.
              '(north east) corner.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Advanced Chart Options'
        ),
        array(
          'name' => 'series_options',
          'caption' => 'Series Options',
          'description' => 'JSON describing an array of series options to pass to the chart with one entry per series. '.
              'Applies to line and bar charts only. For full details of the options available, see '.
              '<a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Series">chart series options</a>. '.
              'For example, to set the label and colour use <em>[{"label":"Count of records per survey","color":"#FF0000"}]</em>.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Advanced Chart Options'
        ),
        array(
          'name' => 'axes_options',
          'caption' => 'Axes Options',
          'description' => 'JSON describing axes options to pass to the chart. Provide entries for yaxis and xaxis as required. '.
              'Applies to line and bar charts only. For full details of the options available, see '.
              '<a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Axis">chart axes options</a>. '.
              'For example, <em>{"yaxis":{"min":0,"max":100}}</em>.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Advanced Chart Options'
        ),
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
    $chartOptions = iform_report_get_report_options($args, $auth);
    $chartOptions = array_merge($chartOptions, array(
      'id' => 'chart-div',
      'width'=> $args['width'],
      'height'=> $args['height'],
      'chartType' => $args['chart_type'],
      'yValues'=>explode(',', $args['y_values']) 
    ));
    $xLabels = trim($args['x_labels']);
    if (empty($xLabels))
      $chartOptions['xValues']=explode(',', $args['x_values']);
    else
      $chartOptions['xLabels']=explode(',', $args['x_labels']);
    // advanced options
    $rendererOptions = trim($args['renderer_options']);
    if (!empty($rendererOptions))
      $chartOptions['rendererOptions'] = json_decode($rendererOptions, true);
    $legendOptions = trim($args['legend_options']);
    if (!empty($legendOptions))
      $chartOptions['legendOptions'] = json_decode($legendOptions, true);
    $seriesOptions = trim($args['series_options']);
    if (!empty($seriesOptions))
      $chartOptions['seriesOptions'] = json_decode($seriesOptions, true);
    $axesOptions = trim($args['axes_options']);
    if (!empty($axesOptions))
      $chartOptions['axesOptions'] = json_decode($axesOptions, true);
    // now the chart itself
    $r .= '<br/>'.report_helper::report_chart($chartOptions);
    return $r;
  }

}