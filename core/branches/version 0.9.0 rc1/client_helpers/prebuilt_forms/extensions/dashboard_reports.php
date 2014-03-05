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
 * Extension class that supplies new controls to support reporting dashboards.
 */
class extension_dashboard_reports {

  /** 
   * A report showing a chart of verification progress per week.
   */
  public static function verification_by_week_chart($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $args = array_merge(array(
      'report_name' => 'library/weeks/filterable_week_verification_breakdown'
    ), $args);
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'id' => 'verification-by-week-chart',
        'width'=> 900,
        'height'=> 500,
        'chartType' => 'bar',
        'yValues'=>array('verified','queried','rejected'),
        'xLabels'=>'week',
        'stackSeries'=>true,
        'rendererOptions' => array('barMargin'=>5),
        'legendOptions' => array('show'=>true),
        'seriesOptions' => array(array('label'=>'Verified','color'=>'#00CC00'),array('label'=>'Queried','color'=>'#FF9900'),array('label'=>'Rejected','color'=>'#CC0000')),
        'axesOptions' => array('yaxis'=>array('min' => 0,'tickOptions'=>array('formatString'=>'%d')),'xaxis'=>array('label'=>'Weeks ago'))
      ),
      $options
    );
    return report_helper::report_chart($reportOptions);
  }
  
  /** 
   * A report showing a chart of incoming records per week.
   */
  public static function records_by_week_chart($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $args = array_merge(array(
      'report_name' => 'library/weeks/filterable_records_by_week',
    ), $args);
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'id' => 'records-by-week-chart',
        'width'=> 900,
        'height'=> 500,
        'chartType' => 'line',
        'yValues'=>array('processed', 'total'),
        'xLabels'=>'week',
        'legendOptions' => array('show'=>true),
        'seriesOptions' => array(array('label'=>'Processed by verifiers','color'=>'#00FF00'),array('label'=>'All records','color'=>'#FF9900')),
        'axesOptions' => array('yaxis'=>array('min' => 0,'tickOptions'=>array('formatString'=>'%d')),'xaxis'=>array('label'=>'Weeks ago'))
      ),
      $options
    );
    return report_helper::report_chart($reportOptions);
  }
}