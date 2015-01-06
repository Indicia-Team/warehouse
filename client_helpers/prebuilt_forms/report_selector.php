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
 * A form for providing a way of selecting and running one of a catalogue of reports.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_selector {
  
  /** 
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_report_selector_definition() {
    return array(
      'title'=>'Report selector',
      'category' => 'Reporting',
      'description' => 'Provides a library of ready made reports that the user can browse through and run.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    require_once 'includes/map.php';
    require_once 'includes/report.php';
    return array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name'=>'my_sites_psn_attr_id',
          'caption'=>'Attribute used to store my sites',
          'description'=>'Select the person attribute used to link users to their recording sites.',
          'type'=>'select',
          'table'=>'person_attribute',
          'valueField'=>'id',
          'captionField'=>'caption'
	      ),
        array(
          'name' => 'main_location_layer_type_id',
          'caption' => 'Location type for main layer of locations',
          'description' => 'Select the location type you are using for your main regional breakdown, e.g. counties or vice counties. ' .
              'This layer must be indexed using the warehouse <em>spatial_index_builder</em> module; check with your warehouse ' .
              'administrator if not sure.',
          'required' => true,
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
        ),
        array(
          'name' => 'min_rank_sort_order_for_species',
          'caption' => 'Minimum taxon rank sort order for species',
          'description' => 'By default, all distinct taxa are counted as species. Set this to ensure that ' .
              'only taxa of rank species or lower are counted in species counts',
          'type' => 'int',
          'required' => false
        ),
        array(
          'name' => 'report_my_sites_records',
          'caption' => 'Include report - My sites heat map showing record counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_my_sites_species',
          'caption' => 'Include report - My sites heat map showing species counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_regions_records',
          'caption' => 'Include report - regions heat map showing record counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_regions_species',
          'caption' => 'Include report - regions heat map showing species counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_months_records',
          'caption' => 'Include report - months showing total record counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_months_species',
          'caption' => 'Include report - months showing total species counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_months_records_by_taxon_groups',
          'caption' => 'Include report - months showing total record counts by species groups',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_months_species_by_taxon_groups',
          'caption' => 'Include report - months showing total species counts by species groups',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_years_records',
          'caption' => 'Include report - years showing total record counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_years_species',
          'caption' => 'Include report - years showing total species counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_taxon_groups_records',
          'caption' => 'Include report - species groups showing total record counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
        array(
          'name' => 'report_taxon_groups_species',
          'caption' => 'Include report - species groups showing total species counts',
          'type' => 'checkbox',
          'default' => 1,
          'required' => false,
          'group'=>'Reports to include'
        ),
      )
    );
  }
  
  private static function get_reports() {
    return array(
        'my_sites' => array(
          'title' => 'Reports on my sites',
          'reports' => array(
            'records' => array(
              'title' => 'My sites heat map showing record counts',
              'description' => 'Show a map of your recording sites, with the site colouration indicating the number of records.',
              'outputs' => array('map', 'raw_data')
            ),
            'species' => array(
              'title' => 'My sites heat map showing species counts',
              'description' => 'Show a map of your recording sites, with the site colouration indicating the number of species.',
              'outputs' => array('map', 'raw_data')
            ) 
          )
        ),
        'regions' => array(
          'title' => 'Reports broken down by #main_location_layer_type#',
          'reports' => array(
            'records' => array(
              'title' => '#main_location_layer_type# heat map showing record counts',
              'description' => 'Show a map of #main_location_layer_type# boundaries, with the area colouration indicating the number of records.',
              'outputs' => array('map', 'raw_data')
            ),
            'species' => array(
              'title' => '#main_location_layer_type# heat map showing species counts',
              'description' => 'Show a map of #main_location_layer_type# boundaries, with the area  colouration indicating the number of species.',
              'outputs' => array('map', 'raw_data')
            ) 
          )
        ),
        'months' => array(
          'title' => 'Reports broken down by month',
          'reports' => array(
            'records' => array(
              'title' => 'Total number of records in each month',
              'description' => 'Shows the total number of records in any month, summing data from different years together. ',
              'outputs' => array('chart', 'raw_data')
            ),
            'species' => array(
              'title' => 'Total number of species in each month',
              'description' => 'Shows the total number of species in any month, summing data from different years together. ',
              'outputs' => array('chart', 'raw_data')
            ),
            'records_by_taxon_groups' => array(
              'title' => 'Total number of records in each month, by species group',
              'description' => 'Shows the total number of records in any month broken down into species groups, summing data from different years together. ',
              'outputs' => array('chart', 'raw_data')
            ),
            'species_by_taxon_groups' => array(
              'title' => 'Total number of species in each month, by species group',
              'description' => 'Shows the total number of species in any month broken down into species groups, summing data from different years together. ',
              'outputs' => array('chart', 'raw_data')
            )
          )
        ),
      'years' => array(
          'title' => 'Reports showing year on year data',
          'reports' => array(
            'records' => array(
              'title' => 'Total number of records in each year',
              'description' => 'Shows the total number of records by year. ',
              'outputs' => array('chart', 'raw_data')
            ),
            'species' => array(
              'title' => 'Total number of species in each year',
              'description' => 'Shows the total number of species by year. ',
              'outputs' => array('chart', 'raw_data')
            )
          )
        ),
        'taxon_groups' => array(
          'title' => 'Reports showing data broken down by species group',
          'reports' => array(
            'records' => array(
              'title' => 'Total number of records by species group',
              'description' => 'Shows the total number of records broken down by species group. ',
              'outputs' => array('pie_chart', 'raw_data')
            ),
            'species' => array(
              'title' => 'Total number of species by species group',
              'description' => 'Shows the total number of species broken down by species group. ',
              'outputs' => array('pie_chart', 'raw_data')
            )
          )
        )
    );
  }
  
  /**
   * Either return a report picker, or if already picked, the report content.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $node, $response=null) {
    iform_load_helpers(array('report_helper', 'map_helper'));
    $conn = iform_get_connection_details($node);
    $readAuth = report_helper::get_read_auth($conn['website_id'], $conn['password']);
    if (empty($_GET['catname']) || empty($_GET['report'])) 
      return self::report_picker($args, $node, $readAuth);
    else {
      $reports = self::get_reports();
      $reportDef = $reports[$_GET['catname']]['reports'][$_GET['report']];
      $regionTerm = self::get_region_term($args, $readAuth);
      $reportDef['title'] = str_replace('#main_location_layer_type#', $regionTerm, $reportDef['title']);
      hostsite_set_page_title($reportDef['title']);
      $fn = "build_report_{$_GET['catname']}_{$_GET['report']}";
      $output = $_GET['output'];
      hostsite_set_breadcrumb(array($node->title => $_GET['q']));
      return call_user_func(array('iform_report_selector', $fn), $args, $readAuth, $output);
    }
  }
  
  public static function report_picker($args, $node, $readAuth) {
    $r = '<ul class="categories">';
    $available = self::get_reports();
    $regionTerm = self::get_region_term($args, $readAuth);
    foreach ($available as $catName => $catDef) {
      $catDef['title'] = str_replace('#main_location_layer_type#', $regionTerm, $catDef['title']);
      $catTitleDone = false;
      foreach ($catDef['reports'] as $report => $reportDef) {
        $reportDef['title'] = str_replace('#main_location_layer_type#', $regionTerm, $reportDef['title']);
        $reportDef['description'] = str_replace('#main_location_layer_type#', $regionTerm, $reportDef['description']);
        $argName = "report_{$catName}_{$report}";
        if (!empty($args[$argName]) && $args[$argName]) {
          if (!$catTitleDone) {
            $r .= "<li>\n";
            $r .= "<h2>$catDef[title]</h2>\n<ul class=\"reports\">";
            $catTitleDone = true;
          }
          $r .= "<li><h3>$reportDef[title]</h3><p>$reportDef[description]</p>";
          $reload = data_entry_helper::get_reload_link_parts();
          $path = $reload['path'] . '?' . data_entry_helper::array_to_query_string($reload['params'] + array(
            'catname' => $catName,
            'report' => $report
          ));
          $r .= '<ul class="report-outputs">';
          foreach ($reportDef['outputs'] as $idx => $output) {
            $outputLabel = str_replace('_', ' ', $output);
            $r .= "<li><a class=\"link-$output\" href=\"$path&output=$output\"/>View $outputLabel</a></li>";
          }
          $r .= '</ul>';
          $r .= '</li>';
        }
      }
      if ($catTitleDone)
        $r .= '</ul></li>';
    }
    $r .= '</ul>';
    return $r;
  }
  
  /**
   * Applies any filters set in the page URL to the report options.
   */
  private static function check_filters(&$reportOptions) {
    if (!empty($_GET['my_records']))
      $reportOptions['extraParams']['my_records']=$_GET['my_records'];
    if (!empty($_GET['year'])) {
      $_GET['year'] = trim($_GET['year']);
      if (is_numeric($_GET['year']) && $_GET['year']>1600 && $_GET['year']<date('Y')) {
        $reportOptions['extraParams']['date_from']="$_GET[year]/01/01";
        $reportOptions['extraParams']['date_to']="$_GET[year]/12/31";
      } else {
        hostsite_show_message(lang::get('Please enter a valid 4 digit year'), 'warning');
      }
    }
    if (!empty($_GET['taxon_group_list'])) {
      $reportOptions['extraParams']['taxon_group_list']=$_GET['taxon_group_list'];
    }
  }
  
  /**
   * Returns the term for the location type selected in the form arguments which defines the 
   * main layer of regions, e.g. could be vice county or province.
   * @param type $args
   * @param type $readAuth
   * @return string The term for the region type.
   */
  private static function get_region_term($args, $readAuth) {
    $data = data_entry_helper::get_population_data(array(
      'table' => 'termlists_term',
      'extraParams' => $readAuth + array('id' => $args['main_location_layer_type_id'], 'view' => 'cache')
    ));
    return $data[0]['term'];
  }
  
  private static function filter_toolbar($filters, $readAuth) {
    if (count($filters)===0)
      return '';
    $reload = data_entry_helper::get_reload_link_parts();
    $r = "<form id=\"filters\" method=\"GET\" action=\"$reload[path]\">";
    foreach ($reload['params'] as $key=>$value) {
      $value=urldecode($value);
      if ($key!=='my_records')
        $r .= "<input name=\"$key\" value=\"$value\" type=\"hidden\" />\n";
    }
    foreach ($filters as $filter) {
      switch($filter) {
        case 'my_records':
          $checked = (!empty($_GET['my_records']) && $_GET['my_records']==='1') ? ' checked="checked"' : '';
          $r .= '<label>Show only my records?<input type="checkbox" name="my_records" value="1"$checked /></label>';
          break;
        case 'year':
          $value = empty($_GET['year']) ? '' : $_GET['year'];
          $r .= "<label>Limit to records from year:<input type=\"text\" name=\"year\" value=\"$value\" /></label>";
          break;
        case 'taxon_group_list':
          $r .= "<label>Limit to records from species group:<select name=\"taxon_group_list\"><option value=\"\">&lt;show all&gt;</option>";
          $groups = report_helper::get_report_data(array(
            'dataSource' => '/library/taxon_groups/taxon_groups_used_in_checklist',
            'readAuth' => $readAuth,
            'extraParams' => array('taxon_list_id' => variable_get('iform_master_checklist_id', 0))
          ));
          $selectedId = empty($_GET['taxon_group_list']) ? '' : $_GET['taxon_group_list'];
          foreach ($groups as $group) {
            $selected = $group['id'] === $selectedId ? ' selected="selected"' : '';
            $r .= "<option value=\"$group[id]\"$selected>$group[title]</option>";
          }
          $r .= "</select></label>";
          break;
      }
    }
    $r .='<input type="submit" value="Go"/>';
    $r .= '</form>';
    return $r;
  }
  
  private static function _build_sites_report($args, $readAuth, $output, $type, $mySites) {
    $r = self::filter_toolbar(array('my_records', 'year', 'taxon_group_list'), $readAuth);
    $reportNameSuffix = $mySites 
        ? '_my_sites' 
        : '_indexed_sites';
    $extraParams = $mySites 
        ? array('person_site_attr_id' => $args['my_sites_psn_attr_id'])
        : array('location_type_ids' => $args['main_location_layer_type_id']);
    if (!empty($args['min_rank_sort_order_for_species']))
      $extraParams['min_taxon_rank_sort_order'] = $args['min_rank_sort_order_for_species'];
    $reportOptions = array(
      'readAuth' => $readAuth,
      'dataSource' => "library/locations/filterable_{$type}_counts_mappable$reportNameSuffix",
      'extraParams' => $extraParams
    );
    self::check_filters($reportOptions);
    if ($output==='map') {
      require_once iform_client_helpers_path() . 'prebuilt_forms/includes/map.php';
      $reportOptions += array(
        'featureDoubleOutlineColour' => '#f7f7f7',
        'rowId' => 'id',
        'caching' => true,
        'cachePerUser' => $mySites,
        'valueOutput' => array(
          'fillColor'=>array(
            'from'=>'#0000ff',
            'to' => '#ff0000',
            'valueField' => 'value',
            'minValue'=> '{minvalue}',
            'maxValue'=> '{maxvalue}'
          ),
          'fillOpacity'=>array(
            'from'=>0.25,
            'to' =>0.6,
            'valueField' => 'value',
            'minValue'=> '{minvalue}',
            'maxValue'=> '{maxvalue}'
          )
        )
      );
      $mapOptions = iform_map_get_map_options($args, $readAuth);
      $olOptions = iform_map_get_ol_options($args);
      $mapOptions['clickForSpatialRef'] = false;
      $r .= map_helper::map_panel($mapOptions, $olOptions);
      $r .= report_helper::report_map($reportOptions);
    } else {
      $reportOptions += array(
        'downloadLink' => true
      );
      $r .= report_helper::report_grid($reportOptions);
    }
    return $r; 
  }
  
  private static function _build_months_report($args, $readAuth, $output, $type) {
    $r = self::filter_toolbar(array('my_records', 'year', 'taxon_group_list'), $readAuth);
    $reportOptions = array(
      'readAuth' => $readAuth,
      'dataSource' => "library/months/filterable_{$type}_counts"
    );
    if (!empty($args['min_rank_sort_order_for_species']))
      $reportOptions['extraParams'] = array('min_taxon_rank_sort_order' => $args['min_rank_sort_order_for_species']);
    self::check_filters($reportOptions);
    if ($output==='chart') {
      $reportOptions += array(
        'chartType' => 'bar',
        'yValues'=>array('count'),
        'xLabels'=>'month',
        'autoParamsForm' => false,
        'caching' => true,
        'cachePerUser' => false,
        'axesOptions' => array('yaxis'=>array('min' => 0, 'tickOptions' => array('formatString' => '%d')))
      );
      $r .= report_helper::report_chart($reportOptions);
    } else {
      $reportOptions += array(
        'downloadLink' => true
      );
      $r .= report_helper::report_grid($reportOptions);
    }
    return $r; 
  }
  
  private static function _build_months_by_taxon_groups_report($args, $readAuth, $output, $type) {
    $r = self::filter_toolbar(array('my_records', 'year'), $readAuth);
    // first we need a quick (cached) prefetch of the main species groups recorded
    $reportOptions = array(
      'readAuth' => $readAuth,
      'dataSource' => "library/taxon_groups/filterable_explore_list",
      'extraParams' => array('limit' => 5, 'orderby'=>'taxon_count', 'sortdir'=>'DESC'),
      'caching' => true
    );
    self::check_filters($reportOptions);
    $groups = report_helper::get_report_data($reportOptions);
    if (!count($groups))
      return lang::get('No data available');
    // to prevent errors if not enough groups available, pad them out
    $groups = array_pad($groups, 5, $groups[0]);
    $extraParams = array();
    $groupLabels = array();
    // pass the group data to the report
    foreach ($groups as $idx => $group) {
      $extraParams['group_' . ($idx+1)] = $group['taxon_group_id'];
      $groupLabels[] = $group['taxon_group'];
    }
    $groupLabels[] = lang::get('other');
    $reportOptions = array(
      'readAuth' => $readAuth,
      'dataSource' => "library/months/filterable_{$type}_counts_by_selected_taxon_groups",
      'extraParams' => $extraParams
    );
    self::check_filters($reportOptions);
    if (!empty($args['min_rank_sort_order_for_species']))
      $reportOptions['extraParams'] += array('min_taxon_rank_sort_order' => $args['min_rank_sort_order_for_species']);
    if ($output==='chart') {
      $reportOptions += array(
        "seriesColors" => array('#8dd3c7','#ffffb3','#bebada','#fb8072','#80b1d3','#fdb462'),
        'stackSeries' => true,
        'chartType' => 'bar',
        'height' => '500',
        'yValues'=>array('other', 'group_5', 'group_4', 'group_3', 'group_2', 'group_1'),
        'xLabels'=>'month',
        'autoParamsForm' => false,
        'caching' => true,
        'cachePerUser' => false,
        'axesOptions' => array('yaxis'=>array('min' => 0, 'tickOptions' => array('formatString' => '%d'))),
        'legendOptions' => array(
          'show' => true,
          'labels' => array_reverse($groupLabels)
        )
      );
      $r .= report_helper::report_chart($reportOptions);
    } else {
      $reportOptions += array(
        'downloadLink' => true
      );
      $r .= report_helper::report_grid($reportOptions);
    }
    return $r; 
  }
  
  private static function _build_years_report($args, $readAuth, $output, $type) {
    $r = self::filter_toolbar(array('my_records', 'taxon_group_list'), $readAuth);
    // do a quick (cached) search for the first record to output
    $reportOptions = array(
      'readAuth' => $readAuth,
      'dataSource' => "library/occurrences/filterable_explore_list",
      'extraParams' => array('limit' => 1, 'orderby'=>'date_start', 'sortdir'=>'ASC', 'smpattrs'=>'', 'occattrs'=>''),
      'caching' => true
    );
    if (!empty($args['min_rank_sort_order_for_species']))
      $reportOptions['extraParams'] += array('min_taxon_rank_sort_order' => $args['min_rank_sort_order_for_species']);
    self::check_filters($reportOptions);
    $firstRecords = report_helper::get_report_data($reportOptions);
    $reportOptions = array(
      'readAuth' => $readAuth,
      'dataSource' => "library/years/filterable_{$type}_counts"
    );
    if (!empty($args['min_rank_sort_order_for_species']))
      $reportOptions['extraParams'] = array('min_taxon_rank_sort_order' => $args['min_rank_sort_order_for_species']);
    self::check_filters($reportOptions);
    if (count($firstRecords)) {
      $firstYear = date('Y', strtotime($firstRecords[0]['date_start']));
      $thisYear = date('Y');
      // show at least 4 years, more if there is stuff to show
      $reportOptions['extraParams']['from_year'] = ($firstYear < $thisYear - 4) ? $firstYear : $thisYear - 4;
    }
    if ($output==='chart') {
      $reportOptions += array(
        'chartType' => 'bar',
        'height' => '500',
        'yValues'=>array('count'),
        'xLabels'=>'year',
        'autoParamsForm' => false,
        'caching' => true,
        'cachePerUser' => false,
        'axesOptions' => array('yaxis'=>array('min' => 0, 'tickOptions' => array('formatString' => '%d')))
      );
      $r .= report_helper::report_chart($reportOptions);
    } else {
      $reportOptions += array(
        'downloadLink' => true
      );
      $r .= report_helper::report_grid($reportOptions);
    }
    return $r; 
  }
  
  private static function _build_taxon_groups_report($args, $readAuth, $output, $type) {
    $r = self::filter_toolbar(array('my_records', 'year'), $readAuth);
    $reportOptions = array(
      'readAuth' => $readAuth,
      'dataSource' => "library/taxon_groups/filterable_{$type}_counts"
    );
    self::check_filters($reportOptions);
    if (!empty($args['min_rank_sort_order_for_species']))
      $reportOptions['extraParams'] = array('min_taxon_rank_sort_order' => $args['min_rank_sort_order_for_species']);
    if ($output==='pie_chart') {
      $data = report_helper::get_report_data($reportOptions);
      // roll categories into 'other' if too many
      if (count($data)>5) {
        $totalOther = 0;
        for ($i = 5; $i<count($data); $i++) {
          $totalOther += $data[$i]['count'];
        }
        array_splice($data, 5);
        $data[] = array('taxon_group'=>lang::get('other'), 'count'=>$totalOther);
      }
      $reportOptions['dataSource'] = 'static';
      $reportOptions += array(
        'staticData' => $data,
        'chartType' => 'pie',
        "seriesColors" => array('#8dd3c7','#ffffb3','#bebada','#fb8072','#80b1d3','#fdb462'),
        'height' => '500',
        'yValues'=>'count',
        'xLabels'=>'taxon_group',
        'autoParamsForm' => false,
        'caching' => true,
        'cachePerUser' => false,
        'axesOptions' => array('yaxis'=>array('min' => 0, 'tickOptions' => array('formatString' => '%d'))),
        'legendOptions' => array(
          'show' => true,
          'rendererOptions' => array('numberColumns' => ceil(count($data) / 10)) // 2 columns if > 10
        ),
        'rendererOptions' => array(
          'sliceMargin' => 2
        )
      );
      $r .= report_helper::report_chart($reportOptions);
    } else {
      $reportOptions += array(
        'downloadLink' => true
      );
      $r .= report_helper::report_grid($reportOptions);
    }
    return $r; 
  }
  
  private static function build_report_my_sites_records($args, $readAuth, $output) {
    return self::_build_sites_report($args, $readAuth, $output, 'occurrence', true);
  }
  
  private static function build_report_my_sites_species($args, $readAuth, $output) {
    return self::_build_sites_report($args, $readAuth, $output, 'species', true);
  }
  
  private static function build_report_regions_records($args, $readAuth, $output) {
    return self::_build_sites_report($args, $readAuth, $output, 'occurrence', false);
  }
  
  private static function build_report_regions_species($args, $readAuth, $output) {
    return self::_build_sites_report($args, $readAuth, $output, 'species', false);
  }
  
  private static function build_report_months_records($args, $readAuth, $output) {
    return self::_build_months_report($args, $readAuth, $output, 'occurrence');
  }
  
  private static function build_report_months_species($args, $readAuth, $output) {
    return self::_build_months_report($args, $readAuth, $output, 'species');
  }
  
  private static function build_report_months_records_by_taxon_groups($args, $readAuth, $output) {
    return self::_build_months_by_taxon_groups_report($args, $readAuth, $output, 'occurrence');
  }
  
  private static function build_report_months_species_by_taxon_groups($args, $readAuth, $output) {
    return self::_build_months_by_taxon_groups_report($args, $readAuth, $output, 'species');
  }
  
  private static function build_report_years_records($args, $readAuth, $output) {
    return self::_build_years_report($args, $readAuth, $output, 'occurrence');
  }
  
  private static function build_report_years_species($args, $readAuth, $output) {
    return self::_build_years_report($args, $readAuth, $output, 'species');
  }
  
  private static function build_report_taxon_groups_records($args, $readAuth, $output) {
    return self::_build_taxon_groups_report($args, $readAuth, $output, 'occurrence');
  }
  
  private static function build_report_taxon_groups_species($args, $readAuth, $output) {
    return self::_build_taxon_groups_report($args, $readAuth, $output, 'species');
  }

}
