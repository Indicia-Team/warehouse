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
 * Aggregate sample based attrs?
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
class iform_report_calendar_summary_2 {

  /* This is the URL parameter used to pass the user_id filter through */
  private static $userKey = 'userID';
  
  /* This is the URL parameter used to pass the location_id filter through */
  private static $locationKey = 'locationID';
  
  /* This is the URL parameter used to pass the location_type_id filter through */
  private static $locationTypeKey = 'locationType';
  
  /* This is the URL parameter used to pass the year filter through */
  private static $yearKey = 'year';

  // internal key, not used on URL: maps the location_id to the survey_id.
  private static $SurveyKey = 'survey_id';

  // internal key, not used on URL: maps the location_id to a url extension.
  private static $URLExtensionKey = 'URLExtension';

  private static $removableParams = array();
  
  private static $siteUrlParams = array();
  
  private static $branchLocationList = array();
  
  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_report_calendar_summary_2_definition() {
    return array(
      'title'=>'Report Calendar Summary 2',
      'category' => 'Reporting',
      'description'=>'Outputs a grid of sumary data loaded from the Summary Builder Module. Can be displayed as a table, or a line or bar chart.',
    );
  }

  /* Installation/configuration notes
   * get_css function is now reducdant: use form arguments to add link to report_calendar_summary_2.css file
   * */
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return 
      array(
        array(
          'name' => 'includeRawData',
          'caption' => 'Include raw data',
          'description' => 'Defines whether to include raw data in the chart/grid.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group' => 'Data Inclusion'
        ),
        array(
          'name' => 'includeSummaryData',
          'caption' => 'Include summary data',
          'description' => 'Defines whether to include summary data in the chart/grid.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group' => 'Data Inclusion'
        ),
        array(
          'name' => 'includeEstimatesData',
          'caption' => 'Include estimates data',
          'description' => 'Define whether to include summary data with estimates in the chart/grid.',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Data Inclusion'
        ),
      	array(
          'name'=>'manager_permission',
          'caption'=>'Drupal Permission for Manager mode',
          'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager (i.e. full access to full data set). This primarily determines the functionality of the User and Location filters, if selected.',
          'type'=>'string',
          'required' => false,
          'group' => 'Access Control'
        ),
        array(
          'name'=>'branch_manager_permission',
          'caption'=>'Drupal Permission for Branch Coordinator mode',
          'description'=>'Enter the Drupal permission name to be used to determine if this user is a Branch Coordinator. This primarily determines the functionality of the User and Location filters, if selected.',
          'type'=>'string',
          'required' => false,
          'group' => 'Access Control'
        ),
        array(
          'name'=>'branchFilterAttribute',
          'caption'=>'Location Branch Coordinator Attribute',
          'description'=>'The caption of the location attribute used to assign locations to Branch Coordinators.',
          'type' => 'string',
          'required' => false,
          'group' => 'Access Control'
        ),
        
        array(
          'name'=>'dateFilter',
          'caption'=>'Date Filter type',
          'description'=>'Type of control used to select the start and end dates.',
          'type'=>'select',
          'options' => array(
//            'none' => 'None',
            'year' => 'User selectable year',
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
      		'name'=>'userLookUp', // TODO Convert to use new report.
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
          'description'=>'Choose whether to restrict the list of locations to those assigned to the selected user using the CMS User ID location attribute.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'locationTypesFilter',
          'caption'=>'Restrict locations to types',
      		'description'=>'Implies a location type selection control. Comma separated list of the location types definitions to be included in the control, of form {Location Type Term}:{Survey ID}[:{Link URL Extension}]. Restricts the locations in the user specific location filter to the selected location type, and restricts the data retrieved to the defined survey. In the Raw Data grid, the Links to the data entry page have the optional extension added. The CMS User ID attribute must be defined for all location types selected or all location types.',
          'type'=>'string',
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
      		'name'=>'removable_params',
      		'caption'=>'Removable report parameters',
      		'description' => 'Provide a list of any report parameters from the Preset Parameter Values list that can be set to a "blank" value by '.
      			'use of a checkbox. For example the report might allow a taxon_list_id parameter to filter for a taxon list or to return all taxon list data '.
      			'if an empty value is provided, so the taxon_list_id parameter can be listed here to provide a checkbox to remove this filter. Provide each '.
      			'parameter on one line, followed by an equals then the caption of the check box, e.g. taxon_list_id=Check this box to include all species.',
      		'type' => 'textarea',
      		'required' => false,
      		'group'=>'Controls'
      	),
      		
        array(
          'name' => 'sampleFields',
          'caption' => 'Sample Fields',
          'description' => 'Comma separated list of the sample level fields in the report. Format is Caption:field. Use field = smpattr:<x> to run this '.
               'processing against a sample attribute.',
          'type' => 'string',
          'required' => false,
          'group'=>'Raw Data Report Settings'
        ),
      	array(
          'name'=>'report_name',
          'caption'=>'Raw Data Report Name',
          'description'=>'Select the report to provide the raw data for this page. If not provided, then Raw Data will not be available.',
          'type'=>'report_helper::report_picker',
          'required' => false,
          'group'=>'Raw Data Report Settings'
        ),
        array(
          'name' => 'param_presets',
          'caption' => 'Raw Data Preset Parameter Values',
          'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username.',
          'type' => 'textarea',
          'required' => false,
          'group'=>'Raw Data Report Settings'
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
          'name'=>'includeFilenameTimestamps',
          'caption'=>'Include Timestamp in Filename',
          'description'=>'Include a Timestamp (YYYYMMDDHHMMSS) in the download filenames.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
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
          'name'=>'linkURL',
          'caption'=>'Link URL',
          'description'=>'Used when generating link URLs to associated samples. If not included, no links will be generated.',
          'type'=>'string',
          'required' => false,
          'group'=>'Raw Data Report Settings'
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
          'group' => 'Raw Data Report Settings'
        ),
        array(
          'name'=>'rowGroupID',
          'caption'=>'Vertical Axis ID',
          'description'=>'The column in the report which is used as the data series id. This is used to pass the series displayed as part of a URL which has a restricted length.',
          'type'=>'string',
          'default'=>'taxon_meaning_id',
          'group' => 'Raw Data Report Settings'
        ),
        array(
          'name'=>'countColumn',
          'caption'=>'Count Column',
          'description'=>'The column in the report which is used as the count associated with the occurrence. If not proviced then each occurrence has a count of one.',
          'type'=>'string',
          'required' => false,
          'group' => 'Raw Data Report Settings'
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
      
    return $args;
  }
  
  /**
   * Retreives the options array required to set up a report according to the default
   * report parameters.
   * @param string $args
   * @param <type> $readAuth
   * @return string
   */
  private static function get_report_calendar_2_options($args, $readAuth) {
    $presets = get_options_array_with_user_data($args['param_presets']);
    $reportOptions = array(
      'id' => 'report-summary',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'extraParams' => $presets, // needed for download reports
      'downloadFilePrefix' => ''
    ) + $presets;
    $reportOptions['extraParams']['survey_id'] = self::$siteUrlParams[self::$SurveyKey]; // catch if not in presets: location_type control
    return $reportOptions;
  }

  // There are 2 options:
  // 1) easy login: in this case the user restriction is on created_by_id, and refers to the Indicia id
  // 2) non-easy login: in this case the user restriction is based on the CMS user ID sample attribute, and refers to the CMS ID.
  // It is left to the report called to handle the user_id parameter as appropriate.
  // The report helper does the conversion from CMS to Easy Login ID if appropriate, so the user_id passed into the
  // report helper is always the CMS one.
  // Locations are always assigned by a CMS user ID attribute, not by who created them.

  private static function set_up_survey($args, $readAuth)
  {
    $siteUrlParams = self::get_site_url_params();
    $presets = get_options_array_with_user_data($args['param_presets']);
    if(isset($presets['survey_id']))
      self::$siteUrlParams[self::$SurveyKey]=$presets['survey_id'];
    if(isset($args['locationTypesFilter']) && $args['locationTypesFilter']!=""){
      $types = explode(',',$args['locationTypesFilter']);
      $types1=array();
      $types2=array();
      foreach($types as $type){
        $parts = explode(':',$type);
        $types1[] = $parts[0];
        $types2[] = $parts;
      }
      $terms = self::get_sorted_termlist_terms(array('read'=>$readAuth), 'indicia:location_types', $types1);
      $default = $siteUrlParams[self::$locationTypeKey]['value'] == '' ? $terms[0]['id'] : $siteUrlParams[self::$locationTypeKey]['value'];
      self::$siteUrlParams[self::$locationTypeKey]['value'] = $default;
      for($i = 0; $i < count($terms); $i++){
        if($terms[$i]['id'] == $default && count($types2[$i])>1 && $types2[$i][1]!='') {
          self::$siteUrlParams[self::$SurveyKey] = $types2[$i][1];
        }
        if($terms[$i]['id'] == $default && count($types2[$i])>2 && $types2[$i][2]!='') {
          self::$siteUrlParams[self::$URLExtensionKey] = $types2[$i][2];
        }
  	  }
  	}
  	return isset(self::$siteUrlParams[self::$SurveyKey]);
  }

  private static function location_control($args, $readAuth, $node, &$options)
  {
  	// note that when in user specific mode it returns the list currently assigned to the user: it does not give 
  	// locations which the user previously recorded data against, but is no longer allocated to.
    global $user;
    $ctrl = '';
    $siteUrlParams = self::get_site_url_params();
    // loctools is not appropriate here as it is based on a node, for which this is a very simple one, invoking other nodes for the sample creation
    if(!isset($args['includeLocationFilter']) || !$args['includeLocationFilter'])
      return '';
    // this is user specific: when no user selection control, or all users selected then default to all locations
    // this means it does not get a list of all locations if no user is selected: to be added later?
    $options['location_id'] = $siteUrlParams[self::$locationKey]['value'];
    $options['extraParams']['location_id'] = $siteUrlParams[self::$locationKey]['value'];
    $options['extraParams']['location_list'] = '';
    
    // Set up common data.
    $locationListArgs=array(// 'nocache'=>true,
    		'extraParams'=>array_merge(array('website_id'=>$args['website_id'], 'location_type_id' => '', 'sensattr' => '', 'exclude_sensitive' => 0),
    				$readAuth),
            'readAuth' => $readAuth,
            'caching' => true,
            'dataSource' => 'library/locations/locations_list_exclude_sensitive');
    $allowSensitive = empty($args['sensitivityLocAttrId']) ||
        (function_exists('user_access') && !empty($args['sensitivityAccessPermission']) && user_access($args['sensitivityAccessPermission']));
    if(!empty($args['sensitivityLocAttrId']))
      $locationListArgs['extraParams']['locattrs'] = $args['sensitivityLocAttrId'];
    $attrArgs = array(
    		'valuetable'=>'location_attribute_value',
    		'attrtable'=>'location_attribute',
    		'key'=>'location_id',
    		'fieldprefix'=>'locAttr',
    		'extraParams'=>$readAuth,
    		'survey_id'=>self::$siteUrlParams[self::$SurveyKey]);
    
    if(isset($args['locationTypesFilter']) && $args['locationTypesFilter']!=""){
      $types = explode(',',$args['locationTypesFilter']);
      $types1=array();
      $types2=array();
      foreach($types as $type){
        $parts = explode(':',$type);
        $types1[] = $parts[0];
        $types2[] = $parts;
      }
      $terms = self::get_sorted_termlist_terms(array('read'=>$readAuth), 'indicia:location_types', $types1);      
      $attrArgs['location_type_id'] = $siteUrlParams[self::$locationTypeKey]['value'];
      $locationListArgs['extraParams']['location_type_id'] = $siteUrlParams[self::$locationTypeKey]['value'];
      if(count($types)>1){
        $lookUpValues = array();
        foreach($terms as $termDetails){
          $lookUpValues[$termDetails['id']] = $termDetails['term'];
        }
        // if location is predefined, can not change unless a 'manager_permission'
        $ctrlid='calendar-location-type-'.$node->nid;
        $ctrl .= data_entry_helper::select(array(
                 'label' => lang::get('Site Type'),
                 'id' => $ctrlid,
                 'fieldname' => 'location_type_id',
                 'lookupValues' => $lookUpValues,
                 'default' => $siteUrlParams[self::$locationTypeKey]['value']
        )).'</th><th>';
        self::set_up_control_change($ctrlid, self::$locationTypeKey, array());
      	$options['downloadFilePrefix'] .= preg_replace('/[^A-Za-z0-9]/i', '', $lookUpValues[$siteUrlParams[self::$locationTypeKey]['value']]).'_';
      }
    }
    
    $locationAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $locationList = array();
    
    // If we are looking a user, then we display all that users sites. If I am that user, or if I am a person with sensitive access, then I can see all the sites, even sensitive.
    if(isset($args['includeUserFilter']) && $args['includeUserFilter'] &&
        	isset($args['userSpecificLocationLookUp']) && $args['userSpecificLocationLookUp'] &&
        	isset($options['user_id']) && $options['user_id']!="" &&
        	$siteUrlParams[self::$userKey]['value']!="branch") {
      if(!$allowSensitive && $options['user_id']!=$user->uid) { // ensure can see sensitive sites for my sites only, unless manager who can see all
        $locationListArgs['extraParams']['sensattr'] = $args['sensitivityLocAttrId'];
        $locationListArgs['extraParams']['exclude_sensitive'] = 1;
      }
      $cmsAttr=extract_cms_user_attr($locationAttributes,false);
      if(!$cmsAttr) return lang::get('Location control: CMS User ID Attribute missing from locations.');
      $attrListArgs=array(// 'nocache'=>true,
      		'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
      				'location_attribute_id'=>$cmsAttr['attributeId'], 'raw_value'=>$options['user_id']),
      				$readAuth),
      		'table'=>'location_attribute_value');
      $description="All ".($user->uid == $options['user_id'] ? 'my' : 'user')." sites";
      $attrList = data_entry_helper::get_population_data($attrListArgs);
      if (isset($attrList['error'])) return $attrList['error'];
      if(count($attrList)===0) {
      	$options['downloadFilePrefix'] .= 'NS_';
      	return($ctrl.lang::get('[No sites allocated.]'));
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
        return($ctrl.lang::get('[No branch sites allocated.]'));
      }
      $locationListArgs['extraParams']['idlist'] = implode(',', self::$branchLocationList);
      $locationList = report_helper::get_report_data($locationListArgs);
      if (isset($locationList['error'])) return $locationList['error'];
      $options['extraParams']['location_list'] = implode(',', self::$branchLocationList);
      $options['branch_location_list'] = self::$branchLocationList;
    } else {
    // If we are looking at all sites, we can see all non sensitive sites, plus sensitive sites if they are allocated to me as a site or as a branch, or if I have access to sensitive.
      $description="All sites";
      $locationListArgs['extraParams']['idlist'] = '';
      // get my sites, including sensitive sites.
      $cmsAttr=extract_cms_user_attr($locationAttributes,false);
      if($cmsAttr) {
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
      }
      // Next add Branch Sites, sensitive or not
      if(count(self::$branchLocationList)>0)
        $locationListArgs['extraParams']['idlist'] .= ($locationListArgs['extraParams']['idlist'] == '' ? '' : ',').implode(',', self::$branchLocationList);
      $userLocationList = array();
      if($locationListArgs['extraParams']['idlist'] != '') {
        $userLocationList = report_helper::get_report_data($locationListArgs);
        if (isset($userLocationList['error'])) return $userLocationList['error'];
      }
      // Next get all other non sensitive sites
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
    $ctrl .='<label for="'.$ctrlid.'" class="location-select-label">'.lang::get('Filter by site').
          ': </label><select id="'.$ctrlid.'" class="location-select">'.
          '<option value="" class="location-select-option" '.($siteUrlParams[self::$locationKey]['value']=='' ? 'selected="selected" ' : '').'>'.$description.'</option>';
    if($siteUrlParams[self::$locationKey]['value']=='')
      $options['downloadFilePrefix'] .= preg_replace('/[^A-Za-z0-9]/i', '', $description).'_';
    foreach($sort as $id=>$name){
    	$ctrl .= '<option value='.$id.' class="location-select-option '.
               (!empty($args['sensitivityLocAttrId']) && $locs[$id]['attr_location_'.$args['sensitivityLocAttrId']] === "1" ? 'sensitive' : '').
               '" '.($siteUrlParams[self::$locationKey]['value']==$id ? 'selected="selected" ' : '').'>'.
               $name.(isset($args['includeSrefInLocationFilter']) && $args['includeSrefInLocationFilter'] ? ' ('.$locs[$id]['centroid_sref'].')' : '').
               '</option>'."\n";
      if($siteUrlParams[self::$locationKey]['value']==$id)
        $options['downloadFilePrefix'] .= preg_replace('/[^A-Za-z0-9]/i', '', $name).'_';
    }
    $ctrl .='</select>';
    self::set_up_control_change($ctrlid, self::$locationKey, array());
    return $ctrl;
  }

  private static function _getCacheFileName($userID)
  {
    /* If timeout is not set, we're not caching */
  	$path = data_entry_helper::$cache_folder ? data_entry_helper::$cache_folder : data_entry_helper::relative_client_helper_path() . 'cache/';
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
  
  private static function user_control(&$args, $readAuth, $node, &$options)
  {
    // we don't use the userID option as the user_id can be blank, and will force the parameter request if left as a blank
    global $user;
    $ctrl = '';
    if(!isset($args['includeUserFilter']) || !$args['includeUserFilter'])
      return '';
    // if the user is changed then we must reset the location
    $siteUrlParams = self::get_site_url_params();
    $options['user_id'] = $siteUrlParams[self::$userKey]['value'] == "branch" ? '' : $siteUrlParams[self::$userKey]['value'];
    $options['extraParams']['user_id'] = $options['user_id'];
    $userList=array();
    
    if(!isset($args['manager_permission']) || $args['manager_permission']=="" || !user_access($args['manager_permission'])) {
      // user is a normal user or branch manager
      $userList[$user->uid]=$user; // just me
      // unset linkURL if normal user and user_id is not specified to be me: pass in flag so warning message displayed.
      // branch manager: user must be self or branch data: can use both for normal user as normal does have branch option
      // manager: access to all.
      switch($siteUrlParams[self::$userKey]['value']){
      	case $user->uid : // me so OK
      	case "branch" : // my branch so OK
	      	break;
      	default : // all users or another user so no access to samples via links
      		unset($args['linkURL']);
      		$options['linkMessage'] = '<p>'.lang::get('In order to have the column headings as links to the data entry pages for the Visit, you must set the').' "'.lang::get('Filter by recorder').'" '.
      				(isset($args['branch_manager_permission']) && $args['branch_manager_permission']!="" && user_access($args['branch_manager_permission']) ?
      							lang::get(' control to yourself or branch data.') : lang::get(' control to yourself.')).'</p>';
      }
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
        if (function_exists('module_exists') && module_exists('easy_login')) {
          $sampleArgs=array(// 'nocache'=>true,
            'extraParams'=>array_merge(array('view'=>'detail', 'website_id'=>$args['website_id'], 'survey_id'=>self::$siteUrlParams[self::$SurveyKey]), $readAuth),
            'table'=>'sample','columns'=>'created_by_id');
          if(isset($args['userLookUpSampleMethod']) && $args['userLookUpSampleMethod']!="") {
            $sampleMethods = helper_base::get_termlist_terms(array('read'=>$readAuth), 'indicia:sample_methods', array(trim($args['userLookUpSampleMethod'])));
            $sampleArgs['extraParams']['sample_method_id']=$sampleMethods[0]['id'];
          }
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
            'survey_id'=>self::$siteUrlParams[self::$SurveyKey]);
          if(isset($args['userLookUpSampleMethod']) && $args['userLookUpSampleMethod']!="") {
            $sampleMethods = helper_base::get_termlist_terms(array('read'=>$readAuth), 'indicia:sample_methods', array(trim($args['userLookUpSampleMethod'])));
            $attrArgs['sample_method_id']=$sampleMethods[0]['id'];
          }
          $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
          if (false== ($cmsAttr = extract_cms_user_attr($sampleAttributes)))
            return(lang::get('User control: CMS User ID sample attribute missing.'.'<span style="display:none;">'.print_r($attrArgs,true).'</span>'));
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
    // The option values are CMS User ID, not Indicia ID.
    // This implies that $siteUrlParams[self::$userKey] is also CMS User ID.
    $ctrlid = 'calendar-user-select-'.$node->nid;
    $ctrl .= '<label for="'.$ctrlid.'" class="user-select-label">'.lang::get('Filter by recorder').
          ': </label><select id="'.$ctrlid.'" class="user-select">'.
          '<option value='.($user->uid).' class="user-select-option" '.($siteUrlParams[self::$userKey]['value']==$user->uid  ? 'selected="selected" ' : '').'>'.lang::get('My data').'</option>'.
          (isset($args['branch_manager_permission']) && $args['branch_manager_permission']!="" && user_access($args['branch_manager_permission']) ? '<option value="branch" class="user-select-option" '.($siteUrlParams[self::$userKey]['value']=="branch"  ? 'selected="selected" ' : '').'>'.lang::get('Branch data').'</option>' : '').
          '<option value="all" class="user-select-option" '.($siteUrlParams[self::$userKey]['value']=='' ? 'selected="selected" ' : '').'>'.lang::get('All recorders').'</option>';
    $found = $siteUrlParams[self::$userKey]['value']==$user->uid ||
          (isset($args['branch_manager_permission']) && $args['branch_manager_permission']!="" && user_access($args['branch_manager_permission']) && $siteUrlParams[self::$userKey]['value']=="branch") ||
          $siteUrlParams[self::$userKey]['value']=='';
    $userListArr = array();
    foreach($userList as $id => $account) {
      // if account comes from cache, then it is an array, if from drupal an object - convert.
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
      case "branch" : $options['downloadFilePrefix'] .= lang::get('MyBranch').'_';
        // need to add user after branch so we know which branch
      default :
        // can't use "myData" as with cached reports >1 person may have same filename, but different reports. Also
        // providing explicit name makes it clearer.
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
          // Force defaults for the user: if none provided default to user, if all then remove it.
          'value' => isset($_GET[self::$userKey]) ? ($_GET[self::$userKey] == "all" ? '' : $_GET[self::$userKey])  : $user->uid
        ),
        self::$locationKey => array(
          'name' => self::$locationKey,
          'value' => isset($_GET[self::$locationKey]) ? $_GET[self::$locationKey] : ''
        ),
        self::$locationTypeKey => array(
          'name' => self::$locationTypeKey,
          'value' => isset($_GET[self::$locationTypeKey]) ? $_GET[self::$locationTypeKey] : ''
        ),
        self::$yearKey => array(
              'name' => self::$yearKey,
              'value' => isset($_GET[self::$yearKey]) ? $_GET[self::$yearKey] : date('Y')
        )
      );
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

  private static function year_control($args, $readAuth, $node, &$options)
  {
    switch($args['dateFilter']){
      case 'none': return '';
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
        $options['year'] = $siteUrlParams[self::$yearKey]['value'];
        $options['date_start'] = $siteUrlParams[self::$yearKey]['value'].'-Jan-01';
        $options['date_end'] = $siteUrlParams[self::$yearKey]['value'].'-Dec-31';
        $options['downloadFilePrefix'] .= $siteUrlParams[self::$yearKey]['value'].'_';
        return $r;
    }
  }

  public static function get_sorted_termlist_terms($auth, $key, $filter){
    $terms = helper_base::get_termlist_terms($auth, $key, $filter);
    $retVal = array();
    foreach($filter as $f) {
      foreach($terms as $term) {
        if($f == $term['term']) $retVal[] = $term;
      }
    }
    return $retVal;
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
    $retVal = '';
    $logged_in = $user->uid>0;
    if(!$logged_in) {
      return('<p>'.lang::get('Please log in before attempting to use this form.').'</p>');
    }
    // can't really do this automatically: better to give warning
    if(isset($args['locationTypeFilter'])) {
      return('<p>'.lang::get('Please contact the site administrator. This version of the form uses a different method of specifying the location types.').'</p>');
    }
    
    iform_load_helpers(array('report_helper'));
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);
    if(!self::set_up_survey($args, $auth))
      return(lang::get('set_up_survey returned false: survey_id missing from presets or location_type definition.'));
    $reportOptions = self::get_report_calendar_2_options($args, $auth);
    $reportOptions['id']='calendar-summary-'.$node->nid;
    if (!empty($args['removable_params']))
      self::$removableParams = get_options_array_with_user_data($args['removable_params']);
    self::copy_args($args, $reportOptions,
      array('weekstart','weekOneContains','weekNumberFilter',
            'outputTable','outputChart',
            'tableHeaders','chartLabels','disableableSeries',
            'chartType','rowGroupColumn','rowGroupID','width','height',
            'includeChartTotalSeries','includeChartItemSeries',
            'includeRawData', 'includeSummaryData', 'includeEstimatesData',
            'includeRawGridDownload', 'includeSummaryGridDownload',
            'includeEstimatesGridDownload', 'sampleFields'
      ));
    if (isset($_GET['outputSeries']))
      $reportOptions['outputSeries']=$_GET['outputSeries']; // default is all
    // Advanced Chart options
    $rendererOptions = trim($args['renderer_options']);
    if (!empty($rendererOptions))
      $reportOptions['rendererOptions'] = json_decode($rendererOptions, true);
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
    if(isset($args['branch_manager_permission']) && $args['branch_manager_permission']!="" && user_access($args['branch_manager_permission'])) {
      // Get list of locations attached to this user via the branch cms user id attribute
      // first need to scan param_presets for survey_id..
      $attrArgs = array(
      		'valuetable'=>'location_attribute_value',
      		'attrtable'=>'location_attribute',
      		'key'=>'location_id',
      		'fieldprefix'=>'locAttr',
      		'extraParams'=>$auth,
      		'survey_id'=>self::$siteUrlParams[self::$SurveyKey]);
      if(isset($args['locationTypesFilter']) && $args['locationTypesFilter']!=""){
        $attrArgs['location_type_id'] = self::$siteUrlParams[self::$locationTypeKey]['value'];
      }
      $locationAttributes = data_entry_helper::getAttributes($attrArgs, false);
      $cmsAttr= self::extract_attr($locationAttributes, $args['branchFilterAttribute']);
      if(!$cmsAttr)
         return(lang::get('Branch Manager location list lookup: missing Branch allocation attribute').' {'.print_r($attrArgs,true).'} : '.$args['branchFilterAttribute']);
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
    if(isset($args['manager_permission']) && $args['manager_permission']!="" && user_access($args['manager_permission'])) {
    	$reportOptions['location_list'] = 'all';
    }
    
    // Add controls first: set up a control bar
    $retVal .= "\n<table id=\"controls-table\" class=\"ui-widget ui-widget-content ui-corner-all controls-table\"><thead class=\"ui-widget-header\"><tr>";
    $retVal .= self::year_control($args, $auth, $node, $reportOptions);
    $retVal .= '<th>'.self::user_control($args, $auth, $node, $reportOptions).'</th>';
    $retVal .= '<th>'.self::location_control($args, $auth, $node, $reportOptions).'</th>'; // note this includes the location_type control if needed
    $siteUrlParams = self::get_site_url_params();
    if (!empty($args['removable_params'])) {      
      foreach(self::$removableParams as $param=>$caption) {
        $checked=(isset($_GET[$param]) && $_GET[$param]==='true') ? ' checked="checked"' : '';
        $retVal .= '<th><input type="checkbox" name="removeParam-'.$param.'" id="removeParam-'.$param.'" class="removableParam"'.$checked.'/>'.
            '<label for="removeParam-'.$param.'" >'.lang::get($caption).'</label></th>';
        if($checked != '')
          $reportOptions['downloadFilePrefix'] .= 'RM'.preg_replace('/[^A-Za-z0-9]/i', '', $param).'_';
      }
      self::set_up_control_change('removeParam-'.$param, $param, array(), true);
    }
    // are there any params that should be set to blank using one of the removable params tickboxes?
    foreach (self::$removableParams as $param=>$caption)
      if (isset($_GET[$param]) && $_GET[$param]==='true') {
        $reportOptions[$param]='';
      	$reportOptions['extraParams'][$param]='';
      }
    if(self::$siteUrlParams[self::$locationTypeKey]['value'] == '') {
      if(isset($args['locationTypesFilter']) && $args['locationTypesFilter']!="" ){
        $types = explode(',',$args['locationTypesFilter']);
        $terms = self::get_sorted_termlist_terms(array('read'=>$auth), 'indicia:location_types', array($types[0]));
        $reportOptions['extraParams']['location_type_id'] = $terms[0]['id'];
      }
    } else
      $reportOptions['extraParams']['location_type_id'] = self::$siteUrlParams[self::$locationTypeKey]['value'];
    
    if(isset($args['linkURL'])) {
    	$reportOptions['linkURL'] = $args['linkURL'] . (isset($siteUrlParams[self::$URLExtensionKey]) ? $siteUrlParams[self::$URLExtensionKey] : '');
	    $reportOptions['linkURL'] .= (strpos($reportOptions['linkURL'], '?') !== FALSE ? '&' : '?').'sample_id=';
    }

    $reportOptions['includeReportTimeStamp']=isset($args['includeFilenameTimestamps']) && $args['includeFilenameTimestamps'];
    
    $retVal.= '</tr></thead></table>';
    $reportOptions['survey_id']=self::$siteUrlParams[self::$SurveyKey]; // Sort of assuming that only one location type recorded against per survey.
    $reportOptions['downloads'] = array();
    if((isset($args['manager_permission']) && $args['manager_permission']!="" && user_access($args['manager_permission'])) || // if you are super manager then you can see all the downloads.
        $reportOptions['extraParams']['location_list'] != '' || // only filled in for a branch user in branch mode
        $reportOptions['extraParams']['user_id'] != '') { // if user specified - either me in normal or branch mode, or a manager
      for($i=1; $i<=4; $i++){
        if(isset($args['Download'.$i.'Caption']) && $args['Download'.$i.'Caption'] != "" && isset($args['download_report_'.$i]) && $args['download_report_'.$i] != ""){
          $reportOpts = array('caption' => $args['Download'.$i.'Caption'],
                'dataSource' => $args['download_report_'.$i],
                'filename' => $reportOptions['downloadFilePrefix'].preg_replace('/[^A-Za-z0-9]/i', '', $args['Download'.$i.'Caption']).(isset($reportOptions['includeReportTimeStamp']) && $reportOptions['includeReportTimeStamp'] ? '_'.date('YmdHis') : ''));
          if(isset($args['download_report_'.$i.'_format'])) $reportOpts['format']=$args['download_report_'.$i.'_format'];
          $reportOptions['downloads'][] = $reportOpts;
        }
      }
    } else $reportOptions['includeRawGridDownload'] = false;
    $retVal .= report_helper::report_calendar_summary2($reportOptions);
    
    return $retVal;
  }

}