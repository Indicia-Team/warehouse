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
 * Extension class that supplies new controls to support reporting on public events such as bioblitzes.
 */
class extension_event_reports {

  /**
   * Outputs a map with an overlay of regions, showing a count for each. Default is to count records, but can
   * be configured to count taxa.
   *
   * @param array $auth Authorisation tokens.
   * @param array $args Form arguments (the settings on the form edit tab).
   * @param string $tabalias The alias of the tab this is being loaded onto.
   * @param array $options The options passed to this control using @option=value settings in the form structure.
   * Options supported are those which can be passed to the report_helper::report_map method. In addition
   * set @output=species to configure the report to show a species counts map.   
   * @param string $path The page reload path, in case it is required for the building of links.
   * @return string HTML to insert into the page for the location map. JavaScript is added to the variables in helper_base.
   *
   * @link http://www.biodiverseit.co.uk/indicia/dev/docs/classes/report_helper.html#method_report_map API docs for report_helper::report_map
   */
  public static function count_by_location_map($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('map_helper', 'report_helper'));
    require_once iform_client_helpers_path() . 'prebuilt_forms/includes/map.php';
    $mapOptions = iform_map_get_map_options($args, $auth['read']);
    $olOptions = iform_map_get_ol_options($args);
    $mapOptions['clickForSpatialRef'] = false;
    $r = map_helper::map_panel($mapOptions, $olOptions);
    if (!empty($options['output']) && $options['output']==='species')
      $type='species';
    else
      $type='occurrence';
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'dataSource' => "library/locations/{$type}_counts_mappable_for_event",
        'featureDoubleOutlineColour' => '#f7f7f7',
        'rowId' => 'id'
      ),
      $options
    );
    $r .= report_helper::report_map($reportOptions);
    return $r; 
  }
  
  /**
   * Outputs a block with total records, species and photos for the event.  
   
   * @param array $auth Authorisation tokens.
   * @param array $args Form arguments (the settings on the form edit tab).
   * @param string $tabalias The alias of the tab this is being loaded onto.
   * @param array $options The options passed to this control using @option=value settings in the form structure.
   * Options supported are those which can be passed to the report_helper::freeform_report method.
   * @param string $path The page reload path, in case it is required for the building of links.
   * @return string HTML to insert into the page for the location map. JavaScript is added to the variables in helper_base.
   *
   * @link http://www.biodiverseit.co.uk/indicia/dev/docs/classes/report_helper.html#method_freeform_report API docs for report_helper::freeform_report
   */
  public static function totals_block($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $userId=hostsite_get_user_field('indicia_user_id');
    iform_load_helpers(array('report_helper'));
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'dataSource' => 'library/totals/species_occurrence_image_counts'       
      ),
      $options
    );
    $reportOptions['extraParams']['ownData'] = 0;
    $reportOptions['extraParams']['currentUser'] = $userId;
    $reportOptions['bands']=array(array('content'=>
        '<div class="totals species">{species_count} species</div>'.
        '<div class="totals species">{occurrences_count} records</div>'.
        '<div class="totals species">{photos_count} photos</div>'));
    return report_helper::freeform_report($reportOptions);
  }
  
  /**
   * Outputs a block of recent photos for the event.  
   
   * @param array $auth Authorisation tokens.
   * @param array $args Form arguments (the settings on the form edit tab).
   * @param string $tabalias The alias of the tab this is being loaded onto.
   * @param array $options The options passed to this control using @option=value settings in the form structure.
   * Options supported are those which can be passed to the report_helper::report_grid method, for example set @limit
   * to control how many photos to display.
   * @param string $path The page reload path, in case it is required for the building of links.
   * @return string HTML to insert into the page for the location map. JavaScript is added to the variables in helper_base.
   *
   * @link http://www.biodiverseit.co.uk/indicia/dev/docs/classes/report_helper.html#method_report_grid API docs for report_helper::report_grid
   */
  public static function photos_block($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $reportOptions = array_merge(      
      iform_report_get_report_options($args, $auth['read']),
      array(
        'dataSource' => 'library/occurrence_images/images_for_event',
        'bands' => array(array('content'=>
          '<div class="gallery-item status-{record_status} certainty-{certainty} ">'.
          '<a class="fancybox" href="{imageFolder}{path}"><img src="{imageFolder}thumb-{path}" title="{taxon}" alt="{taxon}"/><br/>{formatted}</a></div>')),
        'limit' => 10
      ),
      $options
    );
    $reportOptions['extraParams']['limit']=$reportOptions['limit'];
    return report_helper::freeform_report($reportOptions);
  }
  
  /**
   * Outputs a div containing a "cloud" of recorder names, based on the proportion of the recent records
   * recorded by each recorder.  
   
   * @param array $auth Authorisation tokens.
   * @param array $args Form arguments (the settings on the form edit tab).
   * @param string $tabalias The alias of the tab this is being loaded onto.
   * @param array $options The options passed to this control using @option=value settings in the form structure.
   * Options supported are those which can be passed to the report_helper::report_grid method, for example set @limit
   * to control how many recorders to display.
   * @param string $path The page reload path, in case it is required for the building of links.
   * @return string HTML to insert into the page for the location map. JavaScript is added to the variables in helper_base.
   *
   * @link http://www.biodiverseit.co.uk/indicia/dev/docs/classes/report_helper.html#method_freeform_report API docs for report_helper::freeform_report
   */
  public static function trending_recorders_cloud($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $reportOptions = array_merge(      
      iform_report_get_report_options($args, $auth['read']),
      array(
        'dataSource' => 'library/users/trending_people_for_event',
        'header' => '<ul class="people cloud">',
        'bands' => array(array('content'=>
          '<li style="font-size: {font_size}px">{recorders}</li>')),
        'footer' => '</ul>',
        'limit' => 15
      ),
      $options
    );
    $reportOptions['extraParams']['limit']=$reportOptions['limit'];
    return report_helper::freeform_report($reportOptions);
  }
  
  /**
   * Outputs a div containing a "cloud" of taxon names, based on the proportion of the recent records
   * recorded for each taxon.  
   
   * @param array $auth Authorisation tokens.
   * @param array $args Form arguments (the settings on the form edit tab).
   * @param string $tabalias The alias of the tab this is being loaded onto.
   * @param array $options The options passed to this control using @option=value settings in the form structure.
   * Options supported are those which can be passed to the report_helper::report_grid method, for example set @limit
   * to control how many taxa to display.
   * @param string $path The page reload path, in case it is required for the building of links.
   * @return string HTML to insert into the page for the location map. JavaScript is added to the variables in helper_base.
   *
   * @link http://www.biodiverseit.co.uk/indicia/dev/docs/classes/report_helper.html#method_freeform_report API docs for report_helper::freeform_report
   */
  public static function trending_taxa_cloud($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $reportOptions = array_merge(      
      iform_report_get_report_options($args, $auth['read']),
      array(
        'dataSource' => 'library/taxa/trending_taxa_for_event',
        'header' => '<ul class="taxon cloud">',
        'bands' => array(array('content'=>
          '<li style="font-size: {font_size}px">{species}</li>')),
        'footer' => '</ul>',
        'limit' => 15
      ),
      $options
    );
    $reportOptions['extraParams']['limit']=$reportOptions['limit'];
    return report_helper::freeform_report($reportOptions);
  }
  
  /**
   * Outputs a pie chart for the proportion of each taxon group being recorded.  
   *
   * @param array $auth Authorisation tokens.
   * @param array $args Form arguments (the settings on the form edit tab).
   * @param string $tabalias The alias of the tab this is being loaded onto.
   * @param array $options The options passed to this control using @option=value settings in the form structure.
   * Options supported are those which can be passed to the report_helper::report_chart method, for example set @limit
   * to control how many taxa to display, set @width and @height to control the dimensions.
   * @param string $path The page reload path, in case it is required for the building of links.
   * @return string HTML to insert into the page for the location map. JavaScript is added to the variables in helper_base.
   *
   * @link http://www.biodiverseit.co.uk/indicia/dev/docs/classes/report_helper.html#method_report_chart API docs for report_helper::report_chart
   */
  public static function groups_pie($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $reportOptions = array_merge(      
      iform_report_get_report_options($args, $auth['read']),
      array(
        'dataSource' => 'library/taxon_groups/group_counts_for_event',
        'id' => 'groups-pie',
        'width'=> 340,
        'height'=> 340,
        'chartType' => 'pie',
        'yValues'=>array('count'),
        'xValues'=>'taxon_group',
        'rendererOptions' => array(
          'sliceMargin' => 4,
          'showDataLabels' => true,
          'dataLabelThreshold' => 2,
          'dataLabels' => 'label',
          'dataLabelPositionFactor' => 1
        )
      ),
      $options
    );
    return report_helper::report_chart($reportOptions);
  }
  
  public static function species_by_location_league($auth, $args, $tabalias, $options, $path) {
    $label = empty($options['label']) ? 'Location' : $options['label'];
    return self::league_table($auth, $args, $options, 'library/locations/species_counts_league_for_event', $label);
  }
  
  /**
   * Outputs a league table of the recorders.  
   *
   * @param array $auth Authorisation tokens.
   * @param array $args Form arguments (the settings on the form edit tab).
   * @param string $tabalias The alias of the tab this is being loaded onto.
   * @param array $options The options passed to this control using @option=value settings in the form structure.
   * Options supported are those which can be passed to the report_helper::get_report_data method. In addition
   * provide a parameter @groupByRecorderName=true to use the recorder's name as a string in the report grouping,
   * rather than basing the report on the logged in user.
   * @param string $path The page reload path, in case it is required for the building of links.
   * @return string HTML to insert into the page for the league table. JavaScript is added to the variables in helper_base.
   */
  public static function species_by_recorders_league($auth, $args, $tabalias, $options, $path) { 
    $label = empty($options['label']) ? 'Recorders' : $options['label'];
    $groupby = isset($options['groupByRecorderName']) && $options['groupByRecorderName'] ? 'recorder_name' : 'users';
    return self::league_table($auth, $args, $options, "library/$groupby/species_counts_league_for_event", $label);  
  }
  
  private static function league_table($auth, $args, $options, $report, $label) { 
    iform_load_helpers(array('report_helper'));
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'dataSource' => $report,
        'limit' => 20
      ),
      $options
    );
    if (hostsite_get_user_field('training')) 
      $reportOptions['extraParams']['training'] = 'true';
    $reportOptions['extraParams']['limit']=$reportOptions['limit'];
    $rows = report_helper::get_report_data($reportOptions);
    $r = "<table class=\"league\"><thead><th>Pos</th><th>$label</th><th>Species</th></thead><tbody>";
    if (count($rows)) {
      $pos = 1;
      $lastVal = $rows[0]['value'];
      foreach ($rows as $idx => $row) {
        if ($row['value']<$lastVal) {
          $pos = $idx+1; // +1 because zero indexed $idx
          $lastVal = $row['value'];
        }
        $r .= "<tr><td>$pos</td><td>{$row[name]}</td><td>{$row[value]}</td></tr>\n";
      }
    } else {
      $r .= '<td colspan="3">' . lang::get('No results yet') . '</td>';
    }
    $r .= '</tbody></table>';
    return $r;    
  }
}