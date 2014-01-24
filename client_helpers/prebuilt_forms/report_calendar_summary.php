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

/*
 * Future enhancements:
 * Aggregate other sample based attrs?
 * Extand to allow user to select between line and bar charts.
 * Extend Header processing on table to allow configuration so that user can choose whether to have week numbers or dates.
 * Extend X label processing on chart to allow configuration so that user can choose whether to have week numbers or dates.
 */
require_once('includes/form_generation.php');
require_once('includes/report.php');
require_once('includes/user.php');

/**
 * Prebuilt Indicia data form that lists the output of any report
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_report_calendar_summary {

  /* This is the URL parameter used to pass the user_id filter through */
  private static $userKey = 'userID';
  
  /* This is the URL parameter used to pass the location_id filter through */
  private static $locationKey = 'locationID';
  
  /* This is the URL parameter used to pass the year filter through */
  private static $yearKey = 'year';

  /* This is the URL parameter used to pass the caching filter through */
  private static $cacheKey = 'caching';
  
  /* This is the URL parameter used to pass the download filter through */
  private static $downloadKey = 'downloads';
  
  private static $removableParams = array();
  
  private static $siteUrlParams = array();
  
  private static $branchLocationList = array();
  
  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_calendar_summary_definition() {
    return array(
      'title'=>'Report Calendar Summary',
      'category' => 'Reporting',
      'description'=>'Outputs a grid of sumary data loaded from an Indicia report, arranged by week. Can be displayed as a table, or a line or bar chart.',
      'helpLink' => 'http://code.google.com/p/indicia/wiki/PrebuiltFormReportCalendarSummary'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return 
      array(
        array(
          'name'=>'report_name',
          'caption'=>'Report Name',
          'description'=>'Select the report to provide the output for this page.',
          'type'=>'report_helper::report_picker',
          'default'=>'library/samples/samples_list_for_cms_user.xml',
          'group'=>'Report Settings'
        ),
        array(
          'name' => 'param_presets',
          'caption' => 'Preset Parameter Values',
          'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Report Settings'
        ),
        array(
          'name'=>'removable_params',
          'caption'=>'Removable report parameters',
          'description' => 'Provide a list of any report parameters from the Preset Parameter Values list that can be set to a "blank" value by '.
              'use of a checkbox. For example the report might allow a taxon_list_id parameter to filter for a taxon list or to return all taxon list data '.
              'if an empty value is provided, so the taxon_list_id parameter can be listed here to provide a checkbox to remove this filter. Provide each '.
              'parameter on one line, followed by an equals then the caption of the check box, e.g. taxon_list_id=Check this box to include all species.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Report Settings'
        ),
        array(
          'name' => 'avgFields',
          'caption' => 'Average Fields',
          'description' => 'Comma separated list of the fields in the report which are averaged out at a sample level. Use smpattr:<x> to run this '.
               'processing against a sample attribute. If these are look up attributes, an attempt will be made to convert the look up to a number.',
          'type' => 'string',
          'required' => false,
          'group'=>'Report Settings'
        ),
        array(
          'name'=>'outputTable',
          'caption'=>'Output data table',
          'description'=>'Allow output of data in a table. This is the default if non selected.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Report Output'
        ),
        array(
          'name'=>'outputChart',
          'caption'=>'Output chart',
          'description'=>'Allow output of data as a chart. The exact chart type (e.g. line or bar) can be set using the options below.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Report Output'
        ),
        array(
          'name'=>'simultaneousOutput',
          'caption'=>'Simultaneous output formats',
          'description'=>'If more than one of these output options is selected, then this determines whether all are displayed together, or whether the user is provided with a control to choose between them.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Report Output'
        ),
        array(
          'name'=>'defaultOutput',
          'caption'=>'Default output type',
          'description'=>'When the user is provided with the output type selection control (more then one output type and user selectable), then this determines which is displayed first. If a choice is made which is not selected above, then the default is first selected in the order as above.',
          'type'=>'select',
          'options' => array(
            'table' => 'Data table',
            'chart' => 'Chart'
          ),
          'default' => 'table',
          'required' => false,
          'group' => 'Report Output'
        ),
        
        array(
          'name'=>'dateFilter',
          'caption'=>'Date Filter type',
          'description'=>'Type of control used to select the start and end dates provided to the report.',
          'type'=>'select',
          'options' => array(
//            'none' => 'None',
            'year' => 'User selectable year',
            'currentyear' => 'This year (no user control)'
          ),
          'default' => 'year',
          'group' => 'Controls'
        ),
        array(
          'name'=>'includeUserFilter',
          'caption'=>'Include user filter',
          'description'=>'Choose whether to include a filter on the user. This is passed through to the report parameter list as user_id. If not selected, user_id is not included in the report parameter list.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'managerPermission',
          'caption'=>'Drupal Permission for Manager mode',
          'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager (i.e. full access to full data set). This primarily determines the functionality of the User filter, if selected.',
          'type'=>'string',
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'branchManagerPermission',
          'caption'=>'Drupal Permission for Branch Coordinator mode',
          'description'=>'Enter the Drupal permission name to be used to determine if this user is a Branch Coordinator. This primarily determines the functionality of the User filter, if selected.',
          'type'=>'string',
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'branchFilterAttribute',
          'caption'=>'Location Branch Coordinator Attribute',
          'description'=>'The caption of the location attribute used to assign locations to Branch Coordinators.',
          'type'=>'string',
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'userLookUp',
          'caption'=>'Only Users who have entered data',
          'description'=>'Choose whether to include only users which have entered data (indicated by the created_by_id sample field if Easy Login is enabled, or the CMS User ID attribute lodged against a sample if not).',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'userLookUpSampleMethod',
          'caption'=>'Sample Method',
          'description'=>'When looking up the sample attributes, enter an optional sample method term.',
          'type'=>'string',
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'includeLocationFilter',
          'caption'=>'Include location filter',
          'description'=>'Choose whether to include a filter on the locations. This is passed through to the report parameter list as location_id. If not selected, location_id is not included in the report parameter list.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'userSpecificLocationLookUp',
          'caption'=>'Make location list user specific',
          'description'=>'Choose whether to restrict the list of locations to those assigned to the selected user the CMS User ID location attribute.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'locationTypeFilter',
          'caption'=>'Restrict locations to type',
          'description'=>'Retrict the locations in the user specific location filter to a particular location type. The CMS User ID attribute must be defined for this location type or all location types.',
          'type'=>'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams'=>array('termlist_external_key'=>'indicia:location_types'),
          'default' => false,
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'includeSrefInLocationFilter',
          'caption'=>'Include Sref in location filter name',
          'description'=>'When including the user specific location filter, choose whether to include the sref when generating the select name.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Controls'
        ),

        array(
          'name'=>'includeRawGridDownload',
          'caption'=>'Raw Grid Download',
          'description'=>'Choose whether to include the ability to download the Raw data as a grid. The inclusion of raw data is a pre-requisite for this.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Downloads'
        ),
        array(
          'name'=>'includeRawListDownload',
          'caption'=>'Raw List Download',
          'description'=>'Choose whether to include the ability to download the Raw data as a List. The inclusion of raw data is a pre-requisite for this.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Downloads'
        ),
        array(
          'name'=>'includeSummaryGridDownload',
          'caption'=>'Summary Grid Download',
          'description'=>'Choose whether to include the ability to download the Summary data as a grid. The inclusion of Summary data is a pre-requisite for this.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Downloads'
        ),
        array(
          'name'=>'includeEstimatesGridDownload',
          'caption'=>'Estimates Grid Download',
          'description'=>'Choose whether to include the ability to download the Estimates data as a grid. The inclusion of Estimates data is a pre-requisite for this.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Downloads'
        ),
        array(
          'name'=>'includeListDownload',
          'caption'=>'List Download',
          'description'=>'Choose whether to include the ability to download the data as a List. This is the summary and/or the estimates data, depending on their inclusion.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Downloads'
        ),
        array(
          'name'=>'Download1Caption',
          'caption'=>'Report 1 Download Caption',
          'description'=>'Caption for the first download report.',
          'type'=>'string',
          'required' => false,
          'default' => 'report-1',
          'group' => 'Downloads'
        ),
        array(
          'name'=>'download_report_1',
          'caption'=>'Download Report 1',
          'description'=>'Select the report to provide the first download report.',
          'type'=>'report_helper::report_picker',
          'required' => false,
          'group'=>'Downloads'
        ),
        array(
          'name'=>'download_report_1_format',
          'caption'=>'Report Format',
          'description'=>'Format of file produced.<br/>Note that some options have restrictions on the formats of fields (e.g. geometries in GPX and KML formats) in the report. Please see the Wiki for more details.',
          'type'=>'select',
          'options' => array(
              'json' => 'JSON',
              'xml' => 'XML',
              'csv' => 'CSV',
              'tsv' => 'TSV',
              'nbn' => 'NBN',
              'gpx' => 'GPX',
              'kml' => 'KML'
          ),
          'default' => 'csv',
          'group' => 'Downloads'
        ),
        array(
          'name'=>'Download2Caption',
          'caption'=>'Report 2 Download Caption',
          'description'=>'Caption for the second download report.',
          'type'=>'string',
          'required' => false,
          'default' => 'report-2',
          'group' => 'Downloads'
        ),
        array(
          'name'=>'download_report_2',
          'caption'=>'Download Report 2',
          'description'=>'Select the report to provide the second download report.',
          'type'=>'report_helper::report_picker',
          'required' => false,
          'group'=>'Downloads'
        ),
        array(
          'name'=>'download_report_2_format',
          'caption'=>'Report Format',
          'description'=>'Format of file produced.<br/>Note that some options have restrictions on the formats of fields (e.g. geometries in GPX and KML formats) in the report. Please see the Wiki for more details.',
          'type'=>'select',
          'options' => array(
              'json' => 'JSON',
              'xml' => 'XML',
              'csv' => 'CSV',
              'tsv' => 'TSV',
              'nbn' => 'NBN',
              'gpx' => 'GPX',
              'kml' => 'KML'
          ),
          'default' => 'csv',
          'group' => 'Downloads'
        ),
        array(
          'name'=>'Download3Caption',
          'caption'=>'Report 3 Download Caption',
          'description'=>'Caption for the third download report.',
          'type'=>'string',
          'required' => false,
          'default' => 'report-3',
          'group' => 'Downloads'
        ),
        array(
          'name'=>'download_report_3',
          'caption'=>'Download Report 3',
          'description'=>'Select the report to provide the third download report.',
          'type'=>'report_helper::report_picker',
          'required' => false,
          'group'=>'Downloads'
        ),
        array(
          'name'=>'download_report_3_format',
          'caption'=>'Report Format',
          'description'=>'Format of file produced.<br/>Note that some options have restrictions on the formats of fields (e.g. geometries in GPX and KML formats) in the report. Please see the Wiki for more details.',
          'type'=>'select',
          'options' => array(
              'json' => 'JSON',
              'xml' => 'XML',
              'csv' => 'CSV',
              'tsv' => 'TSV',
              'nbn' => 'NBN',
              'gpx' => 'GPX',
              'kml' => 'KML'
          ),
          'default' => 'csv',
          'group' => 'Downloads'
        ),
        array(
          'name'=>'Download4Caption',
          'caption'=>'Report 4 Download Caption',
          'description'=>'Caption for the fourth download report.',
          'type'=>'string',
          'required' => false,
          'default' => 'report-4',
          'group' => 'Downloads'
        ),
        array(
          'name'=>'download_report_4',
          'caption'=>'Download Report 4',
          'description'=>'Select the report to provide the fourth download report.',
          'type'=>'report_helper::report_picker',
          'required' => false,
          'group'=>'Downloads'
        ),
        array(
          'name'=>'download_report_4_format',
          'caption'=>'Report Format',
          'description'=>'Format of file produced.<br/>Note that some options have restrictions on the formats of fields (e.g. geometries in GPX and KML formats) in the report. Please see the Wiki for more details.',
          'type'=>'select',
          'options' => array(
              'json' => 'JSON',
              'xml' => 'XML',
              'csv' => 'CSV',
              'tsv' => 'TSV',
              'nbn' => 'NBN',
              'gpx' => 'GPX',
              'kml' => 'KML'
          ),
          'default' => 'csv',
          'group' => 'Downloads'
        ),

        array(
          'name'=>'weekstart',
          'caption'=>'Start of week definition',
          'description'=>'Define the first day of the week. There are 2 options.<br/>'.
                        "&nbsp;&nbsp;<strong>weekday=&lt;n&gt;</strong> where <strong>&lt;n&gt;</strong> is a number between 1 (for Monday) and 7 (for Sunday).<br/>".
                        "&nbsp;&nbsp;<strong>date=MMM/DD</strong> where <strong>MMM/DD</strong> is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.",
          'type'=>'string',
          'default' => 'weekday=7',
          'group' => 'Date Axis Options'
        ),
        array(
          'name'=>'weekOneContains',
          'caption'=>'Week One Contains',
          'description'=>'When including a week number column, calculate week one as the week containing this date: value should be in the format <strong>MMM/DD</strong>, which is a month/day combination: e.g. choosing Apr-1 will mean week one contains the date of the 1st of April. Default is the Jan-01',
          'type'=>'string',
          'required' => false,
          'group' => 'Date Axis Options'
        ),
        array(
          'name'=>'weekNumberFilter',
          'caption'=>'Restrict displayed weeks',
          'description'=>'Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.<br />'.
                         'Leaving an empty value means the end of the year. Blank means no restrictions.<br />'.
                         'Examples: "1:30" - Weeks one to thirty inclusive. "4:" - Week four onwards. ":5" - Upto and including week five.',
          'type'=>'string',
          'required' => false,
          'group' => 'Date Axis Options'
        ),

        array(
          'name'=>'tableHeaders',
          'caption'=>'Type of header rows to include in the table output',
          'description'=>'Choose whether to include either the week comence date, week number or both as rows in the table header for each column.',
          'type'=>'select',
          'options' => array(
            'date' => 'Date Only',
            'number' => 'Week number only',
            'both' => 'Both'
          ),
          'group' => 'Table Options'
        ),
        array(
          'name'=>'includeTableTotalColumn',
          'caption'=>'Include Total Column',
          'description'=>'Choose whether to generate a totals column at the right of the table - each row is totaled for the whole time period.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Table Options'
        ),
        array(
          'name'=>'includeTableTotalRow',
          'caption'=>'Include Total Row',
          'description'=>'Choose whether to generate a totals row at the bottom of the table - each week is totaled.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Table Options'
        ),
        array(
          'name'=>'linkURL',
          'caption'=>'Link URL',
          'description'=>'Used when generating link URLs to associated samples. If not included, no links will be generated.',
          'type'=>'string',
          'required' => false,
          'group' => 'Table Options'
        ),
        array(
          'name'=>'allowMultiLocLinks',
          'caption'=>'Allow Links for multilocation searches',
          'description'=>'Used when generating link URLs to associated samples. Select if you wish the links to the associated samples to be generated for all report runs. Leave unselected if you wish the links only to be present when a location is selected.<br />The links are present for all locations for managers, the locations in the branch for branch coordinators, and for the samples that a normal user has produced.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Table Options'
        ),
      	array(
          'name' => 'chartType',
          'caption' => 'Chart Type',
          'description' => 'Type of chart.',
          'type' => 'select',
          'lookupValues' => array('line'=>lang::get('Line'), 'bar'=>lang::get('Bar')),
          'required' => true,
          'default' => 'line',
          'group'=>'Chart Options'
        ),
        array(
          'name'=>'chartLabels',
          'caption'=>'Chart X-axis labels',
          'description'=>'Choose whether to have either the week commence date or week number as the chart X-axis labels.',
          'type'=>'select',
          'options' => array(
            'date' => 'Date Only',
            'number' => 'Week number only',
          ),
          'group' => 'Chart Options'
        ),
        array(
          'name'=>'includeChartTotalSeries',
          'caption'=>'Include Total Series',
          'description'=>'Choose whether to generate a series which gives the totals for each week.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Chart Options'
        ),
        array(
          'name'=>'includeChartItemSeries',
          'caption'=>'Include Item Series',
          'description'=>'Choose whether to individual series for the counts of each species for each week on the charts. Summary (with optional estimates) data only.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Chart Options'
        ),
        array(
          'name' => 'width',
          'caption' => 'Chart Width',
          'description' => 'Width of the output chart in pixels: if not set then it will automatically to fill the space.',
          'type' => 'text_input',
          'required' => false,
          'group'=>'Chart Options'
        ),
        array(
          'name' => 'height',
          'caption' => 'Chart Height',
          'description' => 'Height of the output chart in pixels.',
          'type' => 'text_input',
          'required' => true,
          'default' => 500,
          'group'=>'Chart Options'
        ),
        array(
          'name' => 'disableableSeries',
          'caption' => 'Switchable Series',
          'description' => 'User can switch off display of individual Series.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group'=>'Chart Options'
        ),
        array(
          'name' => 'renderer_options',
          'caption' => 'Renderer Options',
          'description' => 'Editor for the renderer options to pass to the chart. For full details of the options available, '.
              'see <a href="http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html">bar chart renderer options</a> or '.
              '<a href="http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html">line charts rendered options<a/>.',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"map",
  "title":"Renderer Options",
  "mapping":{
    "barPadding":{"title":"Bar Padding", "type":"int","desc":"Number of pixels between adjacent bars at the same axis value."},
    "barMargin":{"title":"Bar Margin", "type":"int","desc":"Number of pixels between groups of bars at adjacent axis values."},
    "barDirection":{"title":"Bar Direction", "type":"str","desc":"Select vertical for up and down bars or horizontal for side to side bars","enum":["vertical","horizontal"]},
    "barWidth":{"title":"Bar Width", "type":"int","desc":"Width of the bar in pixels (auto by devaul)."},
    "shadowOffset":{"title":"Bar Slice Shadow Offset", "type":"number","desc":"Offset of the shadow from the slice and offset of each succesive stroke of the shadow from the last."},
    "shadowDepth":{"title":"Bar Slice Shadow Depth", "type":"int","desc":"Number of strokes to apply to the shadow, each stroke offset shadowOffset from the last."},
    "shadowAlpha":{"title":"Bar Slice Shadow Alpha", "type":"number","desc":"Transparency of the shadow (0 = transparent, 1 = opaque)"},
    "waterfall":{"title":"Bar Waterfall","type":"bool","desc":"Check to enable waterfall plot."},
    "groups":{"type":"int","desc":"Group bars into this many groups."},
    "varyBarColor":{"type":"bool","desc":"Check to color each bar of a series separately rather than have every bar of a given series the same color."},
    "highlightMouseOver":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse over."},
    "highlightMouseDown":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse down."},
    "highlightColors":{"type":"seq","desc":"An array of colors to use when highlighting a bar.",
        "sequence":[{"type":"str"}]
    },
    "highlightColor":{"type":"str","desc":"A colour to use when highlighting an area on a filled line plot."}
  }  
}',
          'required' => false,
          'group'=>'Advanced Chart Options'
        ),
        array(
          'name' => 'legend_options',
          'caption' => 'Legend Options',
          'description' => 'Editor for the legend options to pass to the chart. For full details of the options available, '.
              'see <a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Legend">chart legend options</a>. '.
              'For example, set the value to <em>{"show":true,"location":"ne"}</em> to show the legend in the top-right '.
              '(north east) corner. Note some legend options are set by this form, so are not available in this list.',
          'type' => 'jsonwidget',
          'schema'=>'{
  "type":"map",
  "title":"Legend Options",
  "mapping":{
    "location":{"type":"str","desc":"Placement of the legend (compass direction).","enum":["nw","n","ne","e","se","s","sw","w"]},
    "showLabels":{"type":"bool","desc":"Check to show the label text on the legend."},
    "showSwatch":{"type":"bool","desc":"Check to show the color swatches on the legend."},
    "placement":{"type":"str","desc":"insideGrid places legend inside the grid area of the plot. OutsideGrid places the legend outside the grid but inside the plot container, shrinking the '.
        'grid to accomodate the legend. Outside places the legend ouside the grid area, but does not shrink the grid which can cause the legend to overflow the plot container.",
        "enum":["insideGrid","outsideGrid","outside"]},
    "border":{"type":"str","desc":"CSS spec for the border around the legend box."},
    "background":{"type":"str","desc":"CSS spec for the background of the legend box."},
    "textColor":{"type":"str","desc":"CSS color spec for the legend text."},
    "fontFamily":{"type":"str","desc":"CSS font-family spec for the legend text."},
    "fontSize":{"type":"str","desc":"CSS font-size spec for the legend text."},
    "rowSpacing":{"type":"str","desc":"CSS padding-top spec for the rows in the legend."},
    "marginTop":{"type":"str","desc":"CSS margin for the legend DOM element."},
    "marginRight":{"type":"str","desc":"CSS margin for the legend DOM element."},
    "marginBottom":{"type":"str","desc":"CSS margin for the legend DOM element."},
    "marginLeft":{"type":"str","desc":"CSS margin for the legend DOM element."}
  }
}',
          'required' => false,
          'group'=>'Advanced Chart Options'
        ),
        array(
          'name' => 'axes_options',
          'caption' => 'Axes Options',
          'description' => 'Editor for axes options to pass to the chart. Provide entries for yaxis and xaxis as required. '.
              'Applies to line and bar charts only. For full details of the options available, see '.
              '<a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Axis">chart axes options</a>. '.
              'For example, <em>{"yaxis":{"min":0,"max":100}}</em>.',
          'type' => 'jsonwidget',
          'required' => false,
          'group'=>'Advanced Chart Options',
          'schema'=>'{
  "type":"map",
  "title":"Axis options",
  "mapping":{
    "xaxis":{
      "type":"map",
      "mapping":{
        "show":{"type":"bool"},
        "tickOptions":{"type":"map","mapping":{
          "mark":{"type":"str","desc":"Tick mark type on the axis.","enum":["inside","outside","cross"]},
          "showMark":{"type":"bool"},
          "showGridline":{"type":"bool"},
          "isMinorTick":{"type":"bool"},
          "markSize":{"type":"int","desc":"Length of the tick marks in pixels.  For �cross� style, length will be stoked above and below axis, so total length will be twice this."},
          "show":{"type":"bool"},
          "showLabel":{"type":"bool"},
          "formatString":{"type":"str","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
          "fontFamily":{"type":"str","desc":"CSS spec for the font-family css attribute."},
          "fontSize":{"type":"str","desc":"CSS spec for the font-size css attribute."},
          "textColor":{"type":"str","desc":"CSS spec for the color attribute."},
        }},
        "labelOptions":{"type":"map","mapping":{
          "label":{"type":"str","desc":"Label for the axis."},
          "show":{"type":"bool","desc":"Check to show the axis label."},
          "escapeHTML":{"type":"bool","desc":"Check to escape HTML entities in the label."},
        }},
        "min":{"type":"number","desc":"minimum value of the axis (in data units, not pixels)."},
        "max":{"type":"number","desc":"maximum value of the axis (in data units, not pixels)."},
        "autoscale":{"type":"bool","desc":"Autoscale the axis min and max values to provide sensible tick spacing."},
        "pad":{"type":"number","desc":"Padding to extend the range above and below the data bounds.  The data range is multiplied by this factor to determine minimum '.
            'and maximum axis bounds.  A value of 0 will be interpreted to mean no padding, and pad will be set to 1.0."},
        "padMax":{"type":"number","desc":"Padding to extend the range above data bounds.  The top of the data range is multiplied by this factor to determine maximum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMax will be set to 1.0."},
        "padMin":{"type":"numer","desc":"Padding to extend the range below data bounds.  The bottom of the data range is multiplied by this factor to determine minimum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMin will be set to 1.0."},
        "numberTicks":{"type":"int","desc":"Desired number of ticks."},
        "tickInterval":{"type":"number","desc":"Number of units between ticks."},
        "showTicks":{"type":"bool","desc":"Whether to show the ticks (both marks and labels) or not."},
        "showTickMarks":{"type":"bool","desc":"Wether to show the tick marks (line crossing grid) or not."},
        "showMinorTicks":{"type":"bool","desc":"Wether or not to show minor ticks."},
        "useSeriesColor":{"type":"bool","desc":"Use the color of the first series associated with this axis for the tick marks and line bordering this axis."},
        "borderWidth":{"type":"int","desc":"Width of line stroked at the border of the axis."},
        "borderColor":{"type":"str","desc":"Color of the border adjacent to the axis."},
        "syncTicks":{"type":"bool","desc":"Check to try and synchronize tick spacing across multiple axes so that ticks and grid lines line up."},
        "tickSpacing":{"type":"","desc":"Approximate pixel spacing between ticks on graph.  Used during autoscaling.  This number will be an upper bound, actual spacing will be less."}
      }
    },
    "yaxis":{
      "type":"map",
      "mapping":{
        "show":{"type":"bool"},
        "tickOptions":{"type":"map","mapping":{
          "mark":{"type":"str","desc":"Tick mark type on the axis.","enum":["inside","outside","cross"]},
          "showMark":{"type":"bool"},
          "showGridline":{"type":"bool"},
          "isMinorTick":{"type":"bool"},
          "markSize":{"type":"int","desc":"Length of the tick marks in pixels.  For �cross� style, length will be stoked above and below axis, so total length will be twice this."},
          "show":{"type":"bool"},
          "showLabel":{"type":"bool"},
          "formatString":{"type":"str","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
          "fontFamily":{"type":"str","desc":"CSS spec for the font-family css attribute."},
          "fontSize":{"type":"str","desc":"CSS spec for the font-size css attribute."},
          "textColor":{"type":"str","desc":"CSS spec for the color attribute."},
        }},
        "labelOptions":{"type":"map","mapping":{
          "label":{"type":"str","desc":"Label for the axis."},
          "show":{"type":"bool","desc":"Check to show the axis label."},
          "escapeHTML":{"type":"bool","desc":"Check to escape HTML entities in the label."},
        }},
        "min":{"type":"number","desc":"minimum value of the axis (in data units, not pixels)."},
        "max":{"type":"number","desc":"maximum value of the axis (in data units, not pixels)."},
        "autoscale":{"type":"bool","desc":"Autoscale the axis min and max values to provide sensible tick spacing."},
        "pad":{"type":"number","desc":"Padding to extend the range above and below the data bounds.  The data range is multiplied by this factor to determine minimum '.
            'and maximum axis bounds.  A value of 0 will be interpreted to mean no padding, and pad will be set to 1.0."},
        "padMax":{"type":"number","desc":"Padding to extend the range above data bounds.  The top of the data range is multiplied by this factor to determine maximum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMax will be set to 1.0."},
        "padMin":{"type":"numer","desc":"Padding to extend the range below data bounds.  The bottom of the data range is multiplied by this factor to determine minimum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMin will be set to 1.0."},
        "numberTicks":{"type":"int","desc":"Desired number of ticks."},
        "tickInterval":{"type":"number","desc":"Number of units between ticks."},
        "showTicks":{"type":"bool","desc":"Whether to show the ticks (both marks and labels) or not."},
        "showTickMarks":{"type":"bool","desc":"Wether to show the tick marks (line crossing grid) or not."},
        "showMinorTicks":{"type":"bool","desc":"Wether or not to show minor ticks."},
        "useSeriesColor":{"type":"bool","desc":"Use the color of the first series associated with this axis for the tick marks and line bordering this axis."},
        "borderWidth":{"type":"int","desc":"Width of line stroked at the border of the axis."},
        "borderColor":{"type":"str","desc":"Color of the border adjacent to the axis."},
        "syncTicks":{"type":"bool","desc":"Check to try and synchronize tick spacing across multiple axes so that ticks and grid lines line up."},
        "tickSpacing":{"type":"","desc":"Approximate pixel spacing between ticks on graph.  Used during autoscaling.  This number will be an upper bound, actual spacing will be less."}
      }
    }
  }
}',
        ),

        array(
          'name'=>'rowGroupColumn',
          'caption'=>'Vertical Axis',
          'description'=>'The column in the report which is used as the data series label.',
          'type'=>'string',
          'default'=>'taxon',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'rowGroupID',
          'caption'=>'Vertical Axis ID',
          'description'=>'The column in the report which is used as the data series id. This is used to pass the series displayed as part of a URL which has a restricted length.',
          'type'=>'string',
          'default'=>'taxon_meaning_id',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'countColumn',
          'caption'=>'Count Column',
          'description'=>'The column in the report which is used as the count associated with the occurrence. If not proviced then each occurrence has a count of one.',
          'type'=>'string',
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
          'name' => 'sensitivityLocAttrId',
          'caption' => 'Location attribute used to filter out sensitive sites',
          'description' => 'A boolean location attribute, set to true if a site is sensitive.',
          'type' => 'locAttr',
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name' => 'sensitivityAccessPermission',
          'caption' => 'Sensitivity access permission',
          'description' => 'A permission, which if granted allows viewing of sensitive sites.',
          'type' => 'string',
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name' => 'includeRawData',
          'caption' => 'Include raw data',
          'description' => 'Defines whether to include raw data in the chart/grid.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group' => 'Data Handling'
        ),
        array(
          'name' => 'includeSummaryData',
          'caption' => 'Include summary data',
          'description' => 'Defines whether to include summary data in the chart/grid.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group' => 'Data Handling'
        ),
        array(
          'name' => 'includeEstimatesData',
          'caption' => 'Include estimates data',
          'description' => 'Define whether to include summary data with estimates in the chart/grid.',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Data Handling'
        ),
        array(
          'name'=>'summaryDataCombining',
          'caption'=>'Summary Data Combination method',
          'description'=>'When data is aggregated for a location/week combination, this determines how.',
          'type' => 'select',
          'lookupValues' => array('add'=>'Add all occurrences together',
            'max'=>'Choose the value from the sample with the greatest count',
            'sample'=>'Average over samples with data for the occurrence',
            'location'=>'Average over all samples for that location during that week'),
          'required' => true,
          'default' => 'add',
          'group' => 'Data Handling'
        ),
        array(
          'name'=>'dataRound',
          'caption'=>'Data Rounding',
          'description'=>'When data is averaged, this determines what rounding is carried out. Note that anything between 0 and 1 will be rounded up to 1.',
          'type' => 'select',
          'lookupValues' => array('none'=>'None (may result in non-integer values)',
            'nearest'=>'To the nearest integer, .5 rounds up',
//            'nearest_odd'=>'To the nearest integer, .5 rounds to nearest odd number'
//            'nearest_even'=>'To the nearest integer, .5 rounds to nearest even number'
            'up'=>'To the integer greater than or equal to the value',
            'down'=>'To the integer less than or equal to the value'),
          'required' => true,
          'default' => 'none',
          'group' => 'Data Handling'
        ),
        array(
          'name'=>'zeroPointAnchor',
          'caption'=>'Season Limit',
          'description'=>'This is a comma separated list of the week numbers for the start and end of the season. When provided, and data is not entered for these weeks, the value is taken as zero, irrespective of the First or Last value processing. Unentered values before or after these limits are set to zero.',
          'type' => 'string',
          'required' => true,
          'default' => ',',
          'group' => 'Data Handling'
        ),
        array(
          'name'=>'interpolation',
          'caption'=>'Interpolation method',
          'description'=>'When data is estimated between two entered values, this determines how.',
          'type' => 'select',
          'lookupValues' => array('linear'=>'Linear interpolation'),
          'required' => true,
          'default' => 'linear',
          'group' => 'Data Handling'
        ),
        array(
          'name'=>'firstValue',
          'caption'=>'First Value Processing',
          'description'=>'When encountering the first entered value, this determines what happens.',
          'type' => 'select',
          'lookupValues' => array('nothing'=>'No special processing',
            'half'=>'The entry for the previous week is half the entered value'),
          'required' => true,
          'default' => 'nothing',
          'group' => 'Data Handling'
        ),
        array(
          'name'=>'lastValue',
          'caption'=>'Last Value Processing',
          'description'=>'When encountering the last entered value, this determines what happens.',
          'type' => 'select',
          'lookupValues' => array('nothing'=>'No special processing',
            'half'=>'The entry for the next week is half the entered value'),
          'required' => true,
          'default' => 'nothing',
          'group' => 'Data Handling'
        )
    );
  }

    /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {
      
    if (!isset($args['includeRawData']) && !isset($args['includeSummaryData']) && !isset($args['includeEstimatesData']))
        $args['includeRawData'] = true;
    if (!isset($args['summaryDataCombining'])) $args['summaryDataCombining'] = 'add';
    if (!isset($args['dataRound'])) $args['dataRound'] = 'none';
    if (!isset($args['zeroPointAnchor'])) $args['zeroPointAnchor'] = ',';
    if (!isset($args['interpolation'])) $args['interpolation'] = 'linear';
    if (!isset($args['firstValue'])) $args['firstValue'] = 'nothing';
    if (!isset($args['lastValue'])) $args['lastValue'] = 'nothing';
      
    return $args;
  }
  
  /**
   * Retreives the options array required to set up a report according to the default
   * report parameters.
   * @param string $args
   * @param <type> $readAuth
   * @return string
   */
  private static function get_report_calendar_options($args, $readAuth) {
    $presets = get_options_array_with_user_data($args['param_presets']);
    $reportOptions = array(
      'id' => 'report-summary',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'extraParams' => $presets,
      'downloadFilePrefix' => ''
    );
    return $reportOptions;
  }

  // There are 2 options:
  // 1) easy login: in this case the user restriction is on created_by_id, and refers to the Indicia id
  // 2) non-easy login: in this case the user restriction is based on the CMS user ID sample attribute, and refers to the CMS ID.
  // It is left to the report called to handle the user_id parameter as appropriate.
  // The report helper does the conversion from CMS to Easy Login ID if appropriate, so the user_id passed into the
  // report helper is always the CMS one.
  // Locations are always assigned by a CMS user ID attribute, not by who created them.
  
  private static function location_control($args, $readAuth, $node, &$options)
  {
  	// note that when in user specific mode it returns the list currently assigned to the user: it does not give 
  	// locations which the user previously recorded data against, but is no longer allocated to.
    global $user;
    $siteUrlParams = self::get_site_url_params();
    // loctools is not appropriate here as it is based on a node, for which this is a very simple one, invoking other nodes for the sample creation
    if(!isset($args['includeLocationFilter']) || !$args['includeLocationFilter'])
      return '';
    // this is user specific: when no user selection control, or all users selected then default to all locations
    // this means it does not get a list of all locations if no user is selected: to be added later?
    $options['extraParams']['location_id'] = $siteUrlParams[self::$locationKey]['value'];
    $options['extraParams']['location_list'] = '';
    if((!isset($args['allowMultiLocLinks']) || !$args['allowMultiLocLinks']) &&
        $options['extraParams']['location_id'] == '') // only allow links when a location is specified
      unset($options['linkURL']);
    // for branch users we can also provide links to all walks on locations allocated to us as branch coordinator.
    // normal users only get those we create, managers (admin) get links to all.
    
    // Set up common data.
    $locationListArgs=array(// 'nocache'=>true,
    		'extraParams'=>array_merge(array('website_id'=>$args['website_id'], 'location_type_id' => '', 'sensattr' => '', 'exclude_sensitive' => 0),
    				$readAuth),
            'readAuth' => $readAuth,
            'caching' => true,
            'dataSource' => 'library/locations/locations_list_exclude_sensitive');
    $allowSensitive = empty($args['sensitivityLocAttrId']) ||
        (function_exists('user_access') && !empty($args['sensitivityAccessPermission']) && user_access($args['sensitivityAccessPermission']));
    if(isset($args['locationTypeFilter']) && $args['locationTypeFilter']!="")
      $locationListArgs['extraParams']['location_type_id'] = $args['locationTypeFilter'];
    if(!empty($args['sensitivityLocAttrId']))
      $locationListArgs['extraParams']['locattrs'] = $args['sensitivityLocAttrId']; // if we have a sensitive attribute, may want to change the template to highlight them.
    $presets = get_options_array_with_user_data($args['param_presets']);
    if(!isset($presets['survey_id']) || $presets['survey_id']=='')
    	return(lang::get('Location control: survey_id missing from presets.'));
    $attrArgs = array(
    		'valuetable'=>'location_attribute_value',
    		'attrtable'=>'location_attribute',
    		'key'=>'location_id',
    		'fieldprefix'=>'locAttr',
    		'extraParams'=>$readAuth,
    		'survey_id'=>$presets['survey_id']);
    if(isset($args['locationTypeFilter']) && $args['locationTypeFilter']!="")
    	$attrArgs['location_type_id'] = $args['locationTypeFilter'];
    $locationAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $locationList = array();
    
    // If we are looking a user, then we display all that users sites. If I am that user, or if I am a person with sensitive access, then I can see all the sites, even sensitive.
    if(isset($args['includeUserFilter']) && $args['includeUserFilter'] &&
        isset($args['userSpecificLocationLookUp']) && $args['userSpecificLocationLookUp'] &&
        isset($options['extraParams']['user_id']) && $options['extraParams']['user_id']!="" &&
        $siteUrlParams[self::$userKey]['value']!="branch") {
      if(!$allowSensitive && $options['extraParams']['user_id']!=$user->uid) { // ensure can see sensitive sites for my sites only.
        $locationListArgs['extraParams']['sensattr'] = $args['sensitivityLocAttrId'];
        $locationListArgs['extraParams']['exclude_sensitive'] = 1;
      }
      $cmsAttr=extract_cms_user_attr($locationAttributes,false);
      if(!$cmsAttr) return lang::get('Location control: CMS User ID Attribute missing from locations.');
      $attrListArgs=array(// 'nocache'=>true,
      		'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
      				'location_attribute_id'=>$cmsAttr['attributeId'], 'raw_value'=>$options['extraParams']['user_id']),
      				$readAuth),
      		'table'=>'location_attribute_value');
      $description="All ".($user->uid == $options['extraParams']['user_id'] ? 'my' : 'user')." sites";
      $attrList = data_entry_helper::get_population_data($attrListArgs);
      if (isset($attrList['error'])) return $attrList['error'];
      if(count($attrList)===0) {
      	$options['downloadFilePrefix'] .= 'NS_';
      	return(lang::get('[No sites allocated.]'));
      }
      $locationIDList=array();
      foreach($attrList as $attr)
      	$locationIDList[] = $attr['location_id'];
      $locationListArgs['extraParams']['idlist'] = implode(',', $locationIDList);
      $locationList = report_helper::get_report_data($locationListArgs);
      if (isset($locationList['error']))
      	return $locationList['error'];
    } else
    // If we are looking at a branch, we can see all the sites allocated to me even sensitive.
    if(isset($args['includeUserFilter']) && $args['includeUserFilter'] &&
        isset($args['userSpecificLocationLookUp']) && $args['userSpecificLocationLookUp'] &&
        $siteUrlParams[self::$userKey]['value']=="branch") {
      $description="All branch sites";
      if(count(self::$branchLocationList)===0) {
        $options['downloadFilePrefix'] .= 'NS_';
        return(lang::get('[No sites allocated.]'));
      }
      $locationListArgs['extraParams']['idlist'] = implode(',', self::$branchLocationList);
      $locationList = report_helper::get_report_data($locationListArgs);
      if (isset($locationList['error'])) return $locationList['error'];
      $options['extraParams']['location_list'] = implode(',', self::$branchLocationList);
    } else {
    // If we are looking at all sites, we can see all non sensitive sites, plus sensitive sites if they are allocated to me as a site or as a branch, or if I have access to sensitive.
      $description="All sites";
      $locationListArgs['extraParams']['idlist'] = '';
      // get my sites, including sensitive sites.
      $cmsAttr=extract_cms_user_attr($locationAttributes,false);
      if(!$cmsAttr) return lang::get('Location control: CMS User ID Attribute missing from locations.');
      $attrListArgs=array(// 'nocache'=>true,
      		'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
      				'location_attribute_id'=>$cmsAttr['attributeId'], 'raw_value'=>$user->uid),
      				$readAuth),
      		'table'=>'location_attribute_value');
      $attrList = data_entry_helper::get_population_data($attrListArgs);
      if (isset($attrList['error'])) return $attrList['error'];
      if(count($attrList)>0) {
        $locationIDList=array();
        foreach($attrList as $attr)
          $locationIDList[] = $attr['location_id'];
        $locationListArgs['extraParams']['idlist'] = implode(',', $locationIDList);
      }
      // Next add Branch Sites.
      if(count(self::$branchLocationList)>0)
        $locationListArgs['extraParams']['idlist'] .= ($locationListArgs['extraParams']['idlist'] == '' ? '' : ',').implode(',', self::$branchLocationList);
      $userLocationList = array();
      if($locationListArgs['extraParams']['idlist'] != '') {
        $userLocationList = report_helper::get_report_data($locationListArgs);
        if (isset($userLocationList['error'])) return $userLocationList['error'];
      }
      // Next get all other sites
      if(!$allowSensitive) {
        $locationListArgs['extraParams']['sensattr'] = $args['sensitivityLocAttrId'];
        $locationListArgs['extraParams']['exclude_sensitive'] = 1;
      }
      $locationListArgs['extraParams']['idlist']="";
      $allLocationList = report_helper::get_report_data($locationListArgs);
      if (isset($allLocationList['error']))
        return $allLocationList['error'];
      foreach($userLocationList as $loc){
        $locationList[$loc['id']] = $loc;
      }
      foreach($allLocationList as $loc){
        if(!isset($locationList[$loc['id']]))
          $locationList[$loc['id']] = $loc;
      }
    }
    // we want to sort by name, but also keep details of sensitivity.
    $sort = array();
    $locs = array();
    foreach($locationList as $location){
      $sort[$location['id']]=$location['name'];
      $locs[$location['id']]=$location;
    }
    natcasesort($sort);
    $ctrlid='calendar-location-select-'.$node->nid;
    $ctrl='<label for="'.$ctrlid.'" class="location-select-label">'.lang::get('Filter by site').
          ': </label><select id="'.$ctrlid.'" class="location-select">'.
          '<option value="" class="location-select-option" '.($siteUrlParams[self::$locationKey]['value']=='' ? 'selected="selected" ' : '').'>'.$description.'</option>';
    if($siteUrlParams[self::$locationKey]['value']=='')
      $options['downloadFilePrefix'] .= preg_replace('/[^A-Za-z0-9]/i', '', $description).'_';
    foreach($sort as $id=>$name){
      $ctrl .= '<option value='.$id.' class="location-select-option '.
               (!empty($args['sensitivityLocAttrId']) && $locs[$id]['attr_location_'.$args['sensitivityLocAttrId']] === "1" ? 'sensitive' : '').
               '" '.($siteUrlParams[self::$locationKey]['value']==$id ? 'selected="selected" ' : '').'>'.
               $name.(isset($args['includeSrefInLocationFilter']) && $args['includeSrefInLocationFilter'] ? ' ('.$locs[$id]['centroid_sref'].')' : '').
               '</option>';
      if($siteUrlParams[self::$locationKey]['value']==$id)
        $options['downloadFilePrefix'] .= preg_replace('/[^A-Za-z0-9]/i', '', $name).'_';
    }
    $ctrl.='</select>';
    self::set_up_control_change($ctrlid, self::$locationKey, array());
    return $ctrl;
  }

  private static function _getCacheFileName($userID)
  {
    /* If timeout is not set, we're not caching */
  	$path = data_entry_helper::relative_client_helper_path() . (isset(data_entry_helper::$cache_folder) ? data_entry_helper::$cache_folder : 'cache/');
    if(!is_dir($path) || !is_writeable($path)) return false;
    return $path.'cache_'.data_entry_helper::$website_id.'_CMS_User_List_'.$userID;
  }
  
  // Idea here is to not just cache the query used to get the user data, but also the user_loads also run
  private static function _fetchDBCache($userID)
  {
    if (is_numeric(data_entry_helper::$cache_timeout) && data_entry_helper::$cache_timeout > 0) {
      $cacheTimeOut = data_entry_helper::$cache_timeout;
    } else {
      $cacheTimeOut = false;
    }
    $cacheFile = self::_getCacheFileName($userID);
    
    if ($cacheTimeOut && $cacheFile && is_file($cacheFile) && filemtime($cacheFile) >= (time() - $cacheTimeOut))
    {
      $handle = fopen($cacheFile, 'rb');
      if(!$handle) return false;
      // no tags as only determined by user id
      $response = fread($handle, filesize($cacheFile));
      fclose($handle);
      return(json_decode($response, true));
    }
    if ($cacheFile && is_file($cacheFile)) {
      unlink($cacheFile);
    }
    return false;
  }

  protected static function _cacheResponse($userID, $response)
  {
  	// need to create the file as a binary event - so create a temp file and move across.
    $cacheFile = self::_getCacheFileName($userID);
  	if ($cacheFile && !is_file($cacheFile) && isset($response)) {
  		$handle = fopen($cacheFile.getmypid(), 'wb');
  		fwrite($handle, json_encode($response));
  		fclose($handle);
  		rename($cacheFile.getmypid(),$cacheFile);
  	}
  }
  
  private static function user_control($args, $readAuth, $node, &$options)
  {
    // we don't use the userID option as the user_id can be blank, and will force the parameter request if left as a blank
    global $user;
    if(!isset($args['includeUserFilter']) || !$args['includeUserFilter'])
      return '';
    // if the user is changed then we must reset the location
    $siteUrlParams = self::get_site_url_params();
    var_dump($siteUrlParams);
    $options['extraParams']['user_id'] = $siteUrlParams[self::$userKey]['value'] == "branch" ? '' : $siteUrlParams[self::$userKey]['value'];
    $userList=array();
    if (function_exists('module_exists') && module_exists('easy_login') && function_exists('hostsite_get_user_field')) {
      $options['my_user_id']=hostsite_get_user_field('indicia_user_id');
    } else {
      $options['my_user_id']=$user->uid;
    }
    if(!isset($args['managerPermission']) || $args['managerPermission']=="" || !user_access($args['managerPermission'])) {
      // user is a normal user
      $userList[$user->uid]=$user; // just me
    } else {
      // user is manager, so need to load the list of users they can choose to report against 
      if(!($userList = self::_fetchDBCache($user->uid))) {
       $userList=array();
       if(!isset($args['userLookUp']) || !$args['userLookUp']) {
        // look up all users, not just those that have entered data.
        $results = db_query('SELECT uid, name FROM {users}');
        if(version_compare(VERSION, '7', '<')) {
          while($result = db_fetch_object($results)){
            if($result->uid){ // ignore unauthorised user, uid zero
              $account = user_load($result->uid);
              $userList[$account->uid] = $account;
            }
          }
        } else {
          foreach ($results as $result) {  // DB handling is different in 7
            if($result->uid){ // ignore unauthorised user, uid zero
              $account = user_load($result->uid);
              $userList[$account->uid] = $account;
            }
          }
        }
       } else {
        // need to scan param_presets for survey_id.
        $presets = get_options_array_with_user_data($args['param_presets']);
        if(!isset($presets['survey_id']) || $presets['survey_id']=='') return(lang::get('User control: survey_id missing from presets.'));
        if (function_exists('module_exists') && module_exists('easy_login')) {
          $sampleArgs=array(// 'nocache'=>true,
            'extraParams'=>array_merge(array('view'=>'detail', 'website_id'=>$args['website_id'], 'survey_id'=>$presets['survey_id']), $readAuth),
            'table'=>'sample','columns'=>'created_by_id');
          $sampleList = data_entry_helper::get_population_data($sampleArgs);
          if (isset($sampleList['error'])) return $sampleList['error'];
          $uList = array();
          foreach($sampleList as $sample)
            $uList[intval($sample['created_by_id'])] = true;
          // This next bit is DRUPAL specific, but we are using the Easy Login module.
          if (count($uList)>0) {
            if(version_compare(VERSION, '7', '<')) {
              $results = db_query("SELECT DISTINCT pv.uid, u.name FROM {users} u " .
                  "JOIN {profile_values} pv ON pv.uid=u.uid " .
                  "JOIN {profile_fields} pf ON pf.fid=pv.fid AND pf.name='profile_indicia_user_id' " .
                  "AND pv.value IN (" . implode(',', array_keys($uList)). ")");
              while($result = db_fetch_object($results)){
                if($result->uid)
                  $userList[$result->uid] = $result;
              }
            } else {
              // @todo: This needs optimising as in the Drupal 6 version - don't want to load ALL users
              $results = db_query('SELECT uid, name FROM {users}');
              foreach ($results as $result) { // DB processing is different in 7
                if($result->uid){
                  $account = user_load($result->uid); /* this loads the field_ fields, so no need for profile_load_profile */
                  if(isset($account->profile_indicia_user_id) && isset($uList[$account->profile_indicia_user_id]) && $uList[$account->profile_indicia_user_id])
                    $userList[$account->uid] = $account;
                }
              }
            }
          }
        } else {
          // not easy login so use the CMS User ID attribute hanging off the to find which users have entered data.
          $attrArgs = array(
            'valuetable'=>'sample_attribute_value',
            'attrtable'=>'sample_attribute',
            'key'=>'sample_id',
            'fieldprefix'=>'smpAttr',
            'extraParams'=>$readAuth,
            'survey_id'=>$presets['survey_id']);
          if(isset($args['userLookUpSampleMethod']) && $args['userLookUpSampleMethod']!="") {
            $sampleMethods = helper_base::get_termlist_terms(array('read'=>$readAuth), 'indicia:sample_methods', array(trim($args['userLookUpSampleMethod'])));
            $attrArgs['sample_method_id']=$sampleMethods[0]['id'];
          }
          $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
          if (false== ($cmsAttr = extract_cms_user_attr($sampleAttributes)))
            return(lang::get('User control: CMS User ID sample attribute missing.'));
          $attrListArgs=array(// 'nocache'=>true,
            'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
                             'sample_attribute_id'=>$cmsAttr['attributeId']),
                       $readAuth),
            'table'=>'sample_attribute_value');
          $attrList = data_entry_helper::get_population_data($attrListArgs);
          if (isset($attrList['error'])) return $attrList['error'];
          foreach($attrList as $attr)
            if($attr['id']!=null)
              $userList[intval($attr['raw_value'])] = true;
          // This next bit is DRUPAL specific
          $results = db_query('SELECT uid, name FROM {users}');
          if(version_compare(VERSION, '7', '<')) {
            while($result = db_fetch_object($results)){
              if($result->uid && isset($userList[$result->uid]) && $userList[$result->uid])
                $userList[$account->uid] = user_load($result->uid);;
            }
          } else {
            foreach ($results as $result) { // DB handling is different in 7
              if($result->uid && isset($userList[$result->uid]) && $userList[$result->uid])
                $userList[$account->uid] = user_load($result->uid);;
            }
          }
        }
       }
       self::_cacheResponse($user->uid, $userList);
      }
    }
    $ctrlid = 'calendar-user-select-'.$node->nid;
    $ctrl = '<label for="'.$ctrlid.'" class="user-select-label">'.lang::get('Filter by recorder').
          ': </label><select id="'.$ctrlid.'" class="user-select">'.
          '<option value='.($user->uid).' class="user-select-option" '.($siteUrlParams[self::$userKey]['value']==$user->uid  ? 'selected="selected" ' : '').'>'.lang::get('My data').'</option>'.
          (isset($args['branchManagerPermission']) && $args['branchManagerPermission']!="" && user_access($args['branchManagerPermission']) ? '<option value="branch" class="user-select-option" '.($siteUrlParams[self::$userKey]['value']=="branch"  ? 'selected="selected" ' : '').'>'.lang::get('Branch data').'</option>' : '').
          '<option value="all" class="user-select-option" '.($siteUrlParams[self::$userKey]['value']=='' ? 'selected="selected" ' : '').'>'.lang::get('All recorders').'</option>';
    $found = $siteUrlParams[self::$userKey]['value']==$user->uid ||
          (isset($args['branchManagerPermission']) && $args['branchManagerPermission']!="" && user_access($args['branchManagerPermission']) && $siteUrlParams[self::$userKey]['value']=="branch") ||
          $siteUrlParams[self::$userKey]['value']=='';
    $userListArr = array();
    foreach($userList as $id => $account) {
      // if account comes from cache, then it is an array, if from drupal an object.
      if(!is_array($account))
        $account = get_object_vars($account);
      if($account !== true && $id!=$user->uid){
        $userListArr[$id] = $account['name'];
      }
    }
    natcasesort($userListArr);
    foreach($userListArr as $id => $name) {
      $ctrl .= '<option value='.$id.' class="user-select-option" '.($siteUrlParams[self::$userKey]['value']==$id ? 'selected="selected" ' : '').'>'.$name.'</option>';
      $found = $found || $siteUrlParams[self::$userKey]['value']==$id;
    }
    // masquerading may produce some odd results when flipping between accounts.
    switch($siteUrlParams[self::$userKey]['value']){
      case '' : $options['downloadFilePrefix'] .= lang::get('AllRecorders').'_';
        break;
      case $user->uid : $options['downloadFilePrefix'] .= lang::get('MyData').'_';
        break;
      case "branch" : $options['downloadFilePrefix'] .= lang::get('MyBranch').'_';
        break;
      default :
        // if account comes from cache, then it is an array, if from drupal an object.
        $account = is_array($userList[$siteUrlParams[self::$userKey]['value']]) ? 
                     $userList[$siteUrlParams[self::$userKey]['value']] :
                     get_object_vars($userList[$siteUrlParams[self::$userKey]['value']]);
      	$options['downloadFilePrefix'] .= preg_replace('/[^A-Za-z0-9]/i', '', $account['name']).'_';
        break;
    }
    // Haven't found the selected user on the list: this means select defaults to top option which is the user themselves.
    if(!$found) $siteUrlParams[self::$userKey]['value']=$user->uid;
    $ctrl.='</select>';
    self::set_up_control_change($ctrlid, self::$userKey, array('locationID'));
    return $ctrl;
  }
  
  /**
   * Get the parameters required for the current filter.
   */
  private static function get_site_url_params() {
    global $user;
    if (!self::$siteUrlParams) {
      self::$siteUrlParams = array(
        self::$userKey => array(
          'name' => self::$userKey,
          'value' => isset($_GET[self::$userKey]) ? $_GET[self::$userKey] : ''
        ),
        self::$locationKey => array(
          'name' => self::$locationKey,
          'value' => isset($_GET[self::$locationKey]) ? $_GET[self::$locationKey] : ''
        ),
        self::$yearKey => array(
              'name' => self::$yearKey,
              'value' => isset($_GET[self::$yearKey]) ? $_GET[self::$yearKey] : date('Y')
        ),
        self::$cacheKey => array(
              'name' => self::$cacheKey,
              'value' => isset($_GET[self::$cacheKey]) ? $_GET[self::$cacheKey] : 'true'
        ),
        self::$downloadKey => array(
              'name' => self::$downloadKey,
              'value' => isset($_GET[self::$downloadKey]) ? $_GET[self::$downloadKey] : 'false'
        )
      );
      // Force defaults for the user: if none provided default to user, if all then remove it.
      if(self::$siteUrlParams[self::$userKey]['value'] == 'all')
        self::$siteUrlParams[self::$userKey]['value'] = '';
      elseif(self::$siteUrlParams[self::$userKey]['value'] == '')
        self::$siteUrlParams[self::$userKey]['value'] = $user->uid;
      foreach (self::$removableParams as $param=>$caption) {
        self::$siteUrlParams[$param] = array(
          'name' => $param,
          'value' => isset($_GET[$param]) ? $_GET[$param] : ''
        );
      }
    }
    return self::$siteUrlParams;
  }

  private static function extract_attr(&$attributes, $caption, $unset=true) {
  	$found=false;
  	foreach($attributes as $idx => $attr) {
  		if (strcasecmp($attr['caption'], $caption)===0) { // should this be untranslated?
  			// found will pick up just the first one
  			if (!$found)
  				$found=$attr;
  			if ($unset)
  				unset($attributes[$idx]);
  			else
  				// don't bother looking further if not unsetting them all
  				break;
  		}
  	}
  	return $found;
  }

  private static function set_up_control_change($ctrlid, $urlparam, $skipParams, $checkBox=false) {
    // Need to use a global for pageURI as the internal controls may have changed, and we want
    // their values to be carried over.
    $prop = ($checkBox) ? 'attr("checked")' : 'val()';
    data_entry_helper::$javascript .="
jQuery('#".$ctrlid."').change(function(){
  var dialog = $('<p>Please wait whilst the next set of data is loaded.</p>').dialog({ title: 'Loading...', buttons: { 'OK': function() { dialog.dialog('close'); }}});
  // no need to update other controls on the page, as we jump off it straight away.
  window.location = rebuild_page_url(pageURI, \"".$urlparam."\", jQuery(this).$prop);
});
";
  }

  private static function copy_args($args, &$options, $list){
    foreach($list as $arg){
      if(isset($args[$arg]) && $args[$arg]!="")
        $options[$arg]=$args[$arg];
    }
  }

  private static function date_control($args, $readAuth, $node, &$options)
  {
    // Future enhancements: extend this control to allow user selection by month, a fixed currentmonth, 
    // and completely free user selectable start and end dates
    switch($args['dateFilter']){
      case 'none': return '';
      case 'currentyear':
        $options['date_start'] = date('Y').'-Jan-01';
        $options['date_end'] = date('Y').'-Dec-31';
        $options['downloadFilePrefix'] .= date('Y').'_';
        return '<th>'.lang::get('Data for ').date('Y').'</th>';
      default: // case year
        // Add year paginator where it can have an impact for both tables and plots.
        $siteUrlParams = self::get_site_url_params();
        $reloadUrl = data_entry_helper::get_reload_link_parts();
        // find the names of the params we must not include
        foreach ($reloadUrl['params'] as $key => $value) {
          if (!array_key_exists($key, $siteUrlParams)){
            $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?')===false ? '?' : '&')."$key=$value";
          }
        }
        $param=(strpos($reloadUrl['path'],'?')===false ? '?' : '&').self::$yearKey.'=';
        $r = "<th><a id=\"year-control-previous\" title=\"".($siteUrlParams[self::$yearKey]['value']-1)."\" rel=\"nofollow\" href=\"".$reloadUrl['path'].$param.($siteUrlParams[self::$yearKey]['value']-1)."\" class=\"ui-datepicker-prev ui-corner-all\"><span class=\"ui-icon ui-icon-circle-triangle-w\">Prev</span></a></th><th><span class=\"thisYear\">".$siteUrlParams[self::$yearKey]['value']."</span></th>";
        if($siteUrlParams[self::$yearKey]['value']<date('Y')){
          $r .= "<th><a id=\"year-control-next\" title=\"".($siteUrlParams[self::$yearKey]['value']+1)."\" rel=\"nofollow\" href=\"".$reloadUrl['path'].$param.($siteUrlParams[self::$yearKey]['value']+1)."\" class=\"ui-datepicker-next ui-corner-all\"><span class=\"ui-icon ui-icon-circle-triangle-e\">Next</span></a></th>";
        } else $r .= '<th/>';
        $options['date_start'] = $siteUrlParams[self::$yearKey]['value'].'-Jan-01';
        $options['date_end'] = $siteUrlParams[self::$yearKey]['value'].'-Dec-31';
        $options['downloadFilePrefix'] .= $siteUrlParams[self::$yearKey]['value'].'_';
        return $r;
    }
  }
  
  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    global $user;
    $logged_in = $user->uid>0;
    if(!$logged_in) {
      return('<p>'.lang::get('Please log in before attempting to use this form.').'</p>');
    }
    iform_load_helpers(array('report_helper'));
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);
    // survey_id should be set in param_presets $args entry. This is then fetched by iform_report_get_report_options 
    $reportOptions = self::get_report_calendar_options($args, $auth);
    $reportOptions['id']='calendar-summary-'.$node->nid;
    if (!empty($args['removable_params']))
      self::$removableParams = get_options_array_with_user_data($args['removable_params']);
    self::copy_args($args, $reportOptions,
      array('weekstart','weekOneContains','weekNumberFilter',
            'outputTable','outputChart','simultaneousOutput',
            'tableHeaders','chartLabels','disableableSeries',
            'chartType','rowGroupColumn','rowGroupID','width','height',
            'includeTableTotalRow','includeTableTotalColumn','includeChartTotalSeries','includeChartItemSeries',
            'includeRawData', 'includeSummaryData', 'includeEstimatesData', 'summaryDataCombining', 'dataRound',
            'zeroPointAnchor', 'interpolation', 'firstValue', 'lastValue', 'linkURL',
            'includeRawGridDownload', 'includeRawListDownload', 'includeSummaryGridDownload',
            'includeEstimatesGridDownload', 'includeListDownload', 'avgFields'
      ));
    if (isset($_GET['outputSource']))
      $reportOptions['outputSource']=$_GET['outputSource'];
    if (isset($_GET['outputFormat']))
      $reportOptions['outputFormat']=$_GET['outputFormat'];
    else $reportOptions['outputFormat']=$args['defaultOutput'];
    if (isset($_GET['outputSeries']))
      $reportOptions['outputSeries']=$_GET['outputSeries']; // default is all
    // Advanced Chart options
    $rendererOptions = trim($args['renderer_options']);
    if (!empty($rendererOptions))
      $reportOptions['rendererOptions'] = json_decode($rendererOptions, true);
    $legendOptions = trim($args['legend_options']);
    if (!empty($legendOptions))
      $reportOptions['legendOptions'] = json_decode($legendOptions, true);
    $axesOptions = trim($args['axes_options']);
    if (!empty($axesOptions))
      $reportOptions['axesOptions'] = json_decode($axesOptions, true);
    
    if(isset($args['countColumn']) && $args['countColumn']!='') {
      $reportOptions['countColumn']= 'attr_occurrence_'.str_replace(' ', '_', strtolower($args['countColumn'])); // assume that this is an occurrence attribute.
      $reportOptions['extraParams']['occattrs']=$args['countColumn'];
    }

    // for a normal user, we can only link to those samples we have created
    $reportOptions['location_list'] = array();
    // for a branch user, we have an allowed list of locations for which we can link to the sample.
    self::$branchLocationList = array();
    $presets = get_options_array_with_user_data($args['param_presets']);
    if(!isset($presets['survey_id']) || $presets['survey_id']=='')
      return(lang::get('Survey_id missing from presets.'));
    if(isset($args['branchManagerPermission']) && $args['branchManagerPermission']!="" && user_access($args['branchManagerPermission'])) {
      // Get list of locations attached to this user via the branch cms user id attribute
      // first need to scan param_presets for survey_id..
      $attrArgs = array(
      		'valuetable'=>'location_attribute_value',
      		'attrtable'=>'location_attribute',
      		'key'=>'location_id',
      		'fieldprefix'=>'locAttr',
      		'extraParams'=>$auth,
      		'survey_id'=>$presets['survey_id']);
      if(isset($args['locationTypeFilter']) && $args['locationTypeFilter']!="")
      	$attrArgs['location_type_id'] = $args['locationTypeFilter'];
      $locationAttributes = data_entry_helper::getAttributes($attrArgs, false);
      $cmsAttr= self::extract_attr($locationAttributes, $args['branchFilterAttribute']);
      if(!$cmsAttr)
         return(lang::get('Branch Manager location list lookup: missing Branch allocation attribute : ').$args['branchFilterAttribute']);
      $attrListArgs=array(// 'nocache'=>true,
      			'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
      					'location_attribute_id'=>$cmsAttr['attributeId'], 'raw_value'=>$user->uid),
      					$auth),
      			'table'=>'location_attribute_value');
      $attrList = data_entry_helper::get_population_data($attrListArgs);
      if (isset($attrList['error']))
        return $attrList['error'];
      if(count($attrList)>0)
        foreach($attrList as $attr)
        	self::$branchLocationList[] = $attr['location_id'];
      $reportOptions['location_list'] = self::$branchLocationList;
    }
    // for an admin, we can link to all samples.
    if(isset($args['managerPermission']) && $args['managerPermission']!="" && user_access($args['managerPermission'])) {
    	$reportOptions['location_list'] = 'all';
    }
    
    if (function_exists('module_exists') && module_exists('easy_login') && function_exists('hostsite_get_user_field')) {
      $reportOptions['my_user_id']=hostsite_get_user_field('indicia_user_id');
    } else {
      $reportOptions['my_user_id']=$user->uid;
    }
    $retVal = '';
    // Add controls first: set up a control bar
    $retVal .= "\n<table id=\"controls-table\" class=\"ui-widget ui-widget-content ui-corner-all controls-table\"><thead class=\"ui-widget-header\"><tr>";
    $retVal .= self::date_control($args, $auth, $node, $reportOptions);
    $retVal .= '<th>'.self::user_control($args, $auth, $node, $reportOptions).'</th>';
    $retVal .= '<th>'.self::location_control($args, $auth, $node, $reportOptions).'</th>';
    $siteUrlParams = self::get_site_url_params();
    if (!empty($args['removable_params'])) {      
      foreach(self::$removableParams as $param=>$caption) {
        $checked=(isset($_GET[$param]) && $_GET[$param]==='true') ? ' checked="checked"' : '';
        $retVal .= '<th><input type="checkbox" name="removeParam-'.$param.'" id="removeParam-'.$param.'" class="removableParam"'.$checked.'/>'.
            '<label for="removeParam-'.$param.'" >'.lang::get($caption).'</label></th>';
      }
      self::set_up_control_change('removeParam-'.$param, $param, array(), true);
    }
    // are there any params that should be set to blank using one of the removable params tickboxes?
    foreach (self::$removableParams as $param=>$caption)
      if (isset($_GET[$param]) && $_GET[$param]==='true')
        $reportOptions['extraParams'][$param]='';
    if(self::$siteUrlParams[self::$userKey]['value'] == '' && self::$siteUrlParams[self::$locationKey]['value'] == '') {
      $checked=self::$siteUrlParams[self::$cacheKey]['value']==='true' ? ' checked="checked"' : '';
      $retVal .= '<th><input type="checkbox" name="cachingParam" id="cachingParam" class="cachingParam"'.$checked.'/>'.
            '<label for="cachingParam" title="'.lang::get("When fetching the full data set, selcting this improves performance by not going to the warehouse to get the data. Occassionally, even when selected, the data will be refreshed, which will appear to slow down the response.").'" >'.lang::get("Use cached data").'</label></th>';
      $reportOptions['caching']=self::$siteUrlParams[self::$cacheKey]['value']==='true' ? true : 'store';
      self::set_up_control_change('cachingParam', self::$cacheKey, array(), true);

      $checked=self::$siteUrlParams[self::$downloadKey]['value']==='true' ? ' checked="checked"' : '';
      if(self::$siteUrlParams[self::$downloadKey]['value']!=='true' && isset($reportOptions['includeListDownload']) && $reportOptions['includeListDownload'] ==true){
        $reportOptions['includeRawGridDownload'] = false;
        $reportOptions['includeRawListDownload'] = false;
        $reportOptions['includeSummaryGridDownload'] = false;
        $reportOptions['includeEstimatesGridDownload'] = false;
        $reportOptions['includeListDownload'] = false;
        for($i=1; $i<=4; $i++) {
          unset($args['Download'.$i.'Caption']);
          unset($args['download_report_'.$i]);
        }
        data_entry_helper::$javascript .=
          "jQuery('.downloads-table-label').remove();\n".
          "jQuery('#downloads-table thead tr').prepend('<th>".
            "<label for=\"downloadParam\" title=\"".lang::get("When fetching the full data set, unselecting this improves performance by not including the download data in the page. This extra data can lead to a significantly increase in download time.")."\" >".
              lang::get("Include Downloads").
            "</label>".
            "<input type=\"checkbox\" name=\"downloadParam\" id=\"downloadParam\" class=\"downloadParam\"".$checked."/>".
            "</th>');\n";
        self::set_up_control_change('downloadParam', self::$downloadKey, array(), true);
      }
    }
    $retVal.= '</tr></thead></table>';
    $reportOptions['highlightEstimates']=true;
    $retVal .= report_helper::report_calendar_summary($reportOptions);
    // upto this point the report options holds the cms user id as user_id: the report_helper converts this to the indicia
    // user_id if relevant to this installation. We now need to do the same for the report links.
    if (isset($reportOptions['extraParams']['user_id'])) {
      $reportOptions['extraParams']['cms_user_id'] = $reportOptions['extraParams']['user_id'];
      if (function_exists('module_exists') && module_exists('easy_login')) {
        $account = user_load($reportOptions['extraParams']['user_id']);
        if (function_exists('profile_load_profile'))
          profile_load_profile($account); /* will not be invoked for Drupal7 where the fields are already in the account object */
        if(isset($account->profile_indicia_user_id))
          $reportOptions['extraParams']['user_id'] = $account->profile_indicia_user_id;
      }
    }
    if((isset($args['managerPermission']) && $args['managerPermission']!="" && user_access($args['managerPermission'])) || // if you are super manager then you can see all the downloads.
        $reportOptions['extraParams']['location_list'] != '' || // only filled in for a branch user in branch mode
        $reportOptions['extraParams']['user_id'] != '') { // if user specified - either me in normal or branch mode, or a manager
      global $indicia_templates;
      $indicia_templates['report_download_link'] = '<th><a href="{link}"><button type="button">{caption}</button></a></th>';
      // format is assumed to be CSV
      $downloadOptions = array('readAuth'=>$auth,
        'extraParams'=>array_merge($reportOptions['extraParams'], array('date_from' => $reportOptions['date_start'], 'date_to' => $reportOptions['date_end'])),
        'itemsPerPage' => false);
      // there are problems dealing with location_list as an array if empty, so connvert
      if($downloadOptions['extraParams']['location_list']=="")
        $downloadOptions['extraParams']['location_list']="(-1)";
      else $downloadOptions['extraParams']['location_list']='('.$downloadOptions['extraParams']['location_list'].')';

      for($i=1; $i<=4; $i++){
        if(isset($args['Download'.$i.'Caption']) && $args['Download'.$i.'Caption'] != "" && isset($args['download_report_'.$i]) && $args['download_report_'.$i] != ""){
          $downloadOptions['caption' ]=$args['Download'.$i.'Caption'];
          $downloadOptions['dataSource']=$args['download_report_'.$i];
          $downloadOptions['filename']=$reportOptions['downloadFilePrefix'].preg_replace('/[^A-Za-z0-9]/i', '', $args['Download'.$i.'Caption']);
          if(isset($args['download_report_'.$i.'_format'])) $downloadOptions['format']=$args['download_report_'.$i.'_format'];
          data_entry_helper::$javascript .="\nif(jQuery('#downloads-table th').length==0)\n  jQuery('#downloads-table thead tr').append('<th class=\"downloads-table-label\">".lang::get("Downloads")."</th>');\njQuery('#downloads-table thead tr').append('".report_helper::report_download_link($downloadOptions)."');\n";
        }
      }
    }

    return $retVal;
  }

}