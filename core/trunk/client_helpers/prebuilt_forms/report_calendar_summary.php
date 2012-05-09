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
 * Extend to accept bar charts as well as line.
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
        ), array(
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
          'name'=>'outputTable',
          'caption'=>'Output data table',
          'description'=>'Allow output of data in a table.',
          'type'=>'boolean',
          'default' => true,
          'required' => false,
          'group' => 'Report Output'
        ),
        array(
          'name'=>'outputLineChart',
          'caption'=>'Output line chart',
          'description'=>'Allow output of data as a line chart.',
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
            'line' => 'Line graph'
          ),
          'default' => 'table',
          'required' => false,
          'group' => 'Report Output'
        ),
        
        array(
          'name'=>'managerPermission',
          'caption'=>'Drupal Permission for Manager mode',
          'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager. Entering this will allow the identified users to filter by user, which sets the user_id in the report parameter list to the CMS user id (not the Indicia user id). For non managers the user_id is automatically set to the users CMS ID. The selection of the "All Users" option sets the user_id of the report parameter list to a empty string.',
          'type'=>'string',
          'required' => false,
          'group' => 'Controls'
        ),
        array(
          'name'=>'includeLocationFilter',
          'caption'=>'Include user specific location filter',
          'description'=>'Choose whether to include a filter on the locations assigned to this user using the CMS User ID location attribute. This is passed through to the report parameter list as location_id. If not selected, location_id is not included in the report parameter list.',
          'type'=>'boolean',
          'default' => false,
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
          'name'=>'dateFilter',
          'caption'=>'Date Filter type',
          'description'=>'When the user is provided with the output type selection control (more then one output type and user selectable), then this determines which is displayed first. If a choice is made which is not selected above, then the default is first selected in the order as above (table, line, bar).',
          'type'=>'select',
          'options' => array(
            'none' => 'None',
            'year' => 'User selectable year',
            'currentyear' => 'This year (no user control)'
          ),
          'default' => 'year',
          'required' => false,
          'group' => 'Controls'
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
          'group' => 'Report Output'
        ),
        array(
          'name'=>'chartLabels',
          'caption'=>'Chart X-axis labels',
          'description'=>'Choose whether to have either the week comence date or week number as the chart X-axis labels.',
          'type'=>'select',
          'options' => array(
            'date' => 'Date Only',
            'number' => 'Week number only',
          ),
          'group' => 'Report Output'
        ),
        array(
          'name'=>'weekstart',
          'caption'=>'Start of week definition',
          'description'=>'Define the first day of the week. There are 2 options.<br/>'.
                        "&nbsp;&nbsp;<strong>weekday=&lt;n&gt;</strong> where <strong>&lt;n&gt;</strong> is a number between 1 (for Monday) and 7 (for Sunday).<br/>".
                        "&nbsp;&nbsp;<strong>date=MMM/DD</strong> where <strong>MMM/DD</strong> is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.",
          'type'=>'string',
          'default' => 'weekday=7',
          'group' => 'Report Output'
        ),
        array(
          'name'=>'weekOneContains',
          'caption'=>'Week One Contains',
          'description'=>'When including a week number column, calculate week one as the week containing this date: value should be in the format <strong>MMM/DD</strong>, which is a month/day combination: e.g. choosing Apr-1 will mean week one contains the date of the 1st of April. Default is the Jan-01',
          'type'=>'string',
          'required' => false,
          'group' => 'Report Output'
        ),
        array(
          'name'=>'weekNumberFilter',
          'caption'=>'Restrict displayed weeks',
          'description'=>'Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.<br />'.
                         'Leaving an empty value means the end of the year. Blank means no restrictions.<br />'.
                         'Examples: "1:30" - Weeks one to thirty inclusive. "4:" - Week four onwards. ":5" - Upto and including week five.',
          'type'=>'string',
          'required' => false,
          'group' => 'Report Output'
        ),
        // Chart ttype is determined by the output options above. Note there is no provision for pie chart at the moments.
        array(
          'name' => 'width',
          'caption' => 'Chart Width',
          'description' => 'Width of the output chart in pixels.',
          'type' => 'text_input',
          'required' => true,
          'default' => 500,
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
        array( // TODO
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
              'see <a href="http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html">bar chart renderer options</a>, '.
              '<a href="http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html">line charts rendered options<a/> or '.
              '<a href="http://www.jqplot.com/docs/files/plugins/jqplot-pieRenderer-js.html">pie chart renderer options</a>.',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"map",
  "title":"Renderer Options",
  "mapping":{
    "barPadding":{"title":"Bar Padding", "type":"int","desc":"Number of pixels between adjacent bars at the same axis value."},
    "barMargin":{"title":"Bar Margin", "type":"int","desc":"Number of pixels between groups of bars at adjacent axis values."},
    "barDirection":{"title":"Bar Direction", "type":"str","desc":"Select vertical for up and down bars or horizontal for side to side bars","enum":["vertical","horizontal"]},
    "barWidth":{"title":"Bar Width", "type":"int","desc":"Width of the bar in pixels (auto by devaul)."},
    "shadowOffset":{"title":"Bar or Pie Slice Shadow Offset", "type":"number","desc":"Offset of the shadow from the slice and offset of each succesive stroke of the shadow from the last."},
    "shadowDepth":{"title":"Bar or Pie Slice Shadow Depth", "type":"int","desc":"Number of strokes to apply to the shadow, each stroke offset shadowOffset from the last."},
    "shadowAlpha":{"title":"Bar or Pie Slice Shadow Alpha", "type":"number","desc":"Transparency of the shadow (0 = transparent, 1 = opaque)"},
    "waterfall":{"title":"Bar Waterfall","type":"bool","desc":"Check to enable waterfall plot."},
    "groups":{"type":"int","desc":"Group bars into this many groups."},
    "varyBarColor":{"type":"bool","desc":"Check to color each bar of a series separately rather than have every bar of a given series the same color."},
    "highlightMouseOver":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse over."},
    "highlightMouseDown":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse down."},
    "highlightColors":{"type":"seq","desc":"An array of colors to use when highlighting a bar or pie slice.",
        "sequence":[{"type":"str"}]
    },
    "highlightColor":{"type":"str","desc":"A colour to use when highlighting an area on a filled line plot."},
    "diameter":{"title":"Pie Diameter","type":"int","desc":"Outer diameter of the pie, auto computed by default."},
    "padding":{"title":"Pie Padding","type":"int","desc":"padding between the pie and plot edges, legend, etc."},
    "sliceMargin":{"title":"Pie Slice Margin","type":"int","desc":"Angular spacing between pie slices in degrees."},
    "fill":{"title":"Pie Fill", "type":"bool","desc":"true or false, whether to fill the slices."},
    "dataLabels":{"title":"Pie Data Labels", "type":"str","desc":"Select what to display as labels on pie slices.",
      "enum":["label","value","percent"]
    },
    "showDataLabels":{"title":"Pie Show Data Labels", "type":"bool","desc":"Check to show data labels on pie slices."},
    "dataLabelFormatString":{"title":"Pie Data Label Format String", "type":"str","desc":"Format string for data labels. %s is replaced with the label, %d with the value, %d%% with the percentage."},
    "dataLabelThreshold":{"title":"Pie Data Label Threshold", "type":"int","desc":"Threshhold in percentage (0-100) of pie area, below which no label will be displayed.  This applies to all label types, not just to percentage labels."},
    "dataLabelPositionFactor":{"title":"Pie Data Label Position Factor", "type":"number","desc":"A Multiplier (0-1) of the pie radius which controls position of label on slice."},
    "dataLabelNudge":{"title":"Pie Data Label Nudge", "type":"number","desc":"Number of pixels to slide the label away from (+) or toward (-) the center of the pie."},
    "dataLabelCenterOn":{"title":"Pie Data Label Centre On", "type":"bool","desc":"Check to center the data label at its position."},
    "startAngle":{"title":"Pie Start Angle", "type":"int","desc":"Angle to start drawing pie in degrees."}
  }  
}',
          'required' => false,
          'group'=>'Chart Options'
        ),
        array(
          'name' => 'legend_options',
          'caption' => 'Legend Options',
          'description' => 'Editor for the legend options to pass to the chart. For full details of the options available, '.
              'see <a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Legend">chart legend options</a>. '.
              'For example, set the value to <em>{"show":true,"location":"ne"}</em> to show the legend in the top-right '.
              '(north east) corner.',
          'type' => 'jsonwidget',
          'schema'=>'{
  "type":"map",
  "title":"Legend Options",
  "mapping":{
    "location":{"type":"str","desc":"Placement of the legend (compass direction).","enum":["nw","n","ne","e","se","s","sw","w"]},
    "labels":{"type":"seq","desc":"Array of labels to use. By default the renderer will look for labels on the series.  Labels specified in this array will override labels specified on the series.",
        "sequence":[{"type":"str"}]},
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
          'group'=>'Chart Options'
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
          'group'=>'Chart Options',
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
          "formatString":{"type":"bool","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
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
          "formatString":{"type":"bool","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
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
          'description'=>'The column in the report which is used as the data series.',
          'type'=>'string',
          'default'=>'taxon',
          'group' => 'Report Settings'
        ),
        array(
          'name'=>'countColumn',
          'caption'=>'Count Column',
          'description'=>'The column in the report which is used as the count associated with the occurrence. If not proviced then each occurrence has a count of one.',
          'type'=>'string',
          'required' => false,
          'group' => 'Report Settings'
        )
    );
  }

  /**
   * Retreives the options array required to set up a report according to the default
   * report parameters.
   * @param string $args
   * @param <type> $readAuth
   * @return string
   */
  private function get_report_calendar_options($args, $readAuth) {
    $presets = get_options_array_with_user_data($args['param_presets']);
    $reportOptions = array(
      'id' => 'report-summary',
      'dataSource' => $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'extraParams' => $presets);
    return $reportOptions;
  }

  /* This is the URL parameter used to pass the location_id filter through */
  private static $locationKey = 'location_id';
  
  private function location_control($args, $readAuth, $node)
  {
    global $user;
    // loctools is not appropriate here as it is based on a node, for which this is a very simple one, invoking other nodes for the sample creation
    // need to scan param_presets for survey_id..
    $siteUrlParams = array(
      self::$locationKey => array(
        'name' => self::$locationKey,
        'value' => isset($_GET[self::$locationKey]) ? $_GET[self::$locationKey] : null
      )
    );
    $presets = get_options_array_with_user_data($args['param_presets']);
    if(!isset($presets['survey_id']) || $presets['survey_id']==''){
      return('<p>'.lang::get('The location selection control requires that survey_id is set in the presets in the form parameters.').'</p>');
    }
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
    $cmsAttr=extract_cms_user_attr($locationAttributes,false);
    if(!$cmsAttr){
      return('<p>'.lang::get('The location selection control requires that CMS User ID location attribute is defined for locations in this survey.').'</p>');
    }
    $attrListArgs=array('nocache'=>true,
        'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
                           'location_attribute_id'=>$cmsAttr['attributeId'], 'raw_value'=>$user->uid),
                     $readAuth),
        'table'=>'location_attribute_value');
    $attrList = data_entry_helper::get_population_data($attrListArgs);
    if (isset($attrList['error'])){
      return $attrList['error'];
    }
    $locationIDList=array();
    foreach($attrList as $attr) {
      $locationIDList[] = $attr['location_id'];
    }
    $locationListArgs=array('nocache'=>true,
        'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'], 'id'=>$locationIDList),
                     $readAuth),
        'table'=>'location');
    $locationList = data_entry_helper::get_population_data($locationListArgs);
    if (isset($locationList['error'])) {
      return $locationList['error'];
    }
    $ctrlid='calendar-location-select-'.$node->nid;
    $ctrl='<label for="'.$ctrlid.'" class="location-select-label">'.lang::get('Filter by location').
          ' :</label><select id="'.$ctrlid.'" class="location-select">'.
          '<option value="" class="location-select-option" '.($siteUrlParams[self::$locationKey]['value']==null ? 'selected=\"selected\" ' : '').'>'.lang::get('All locations').'</option>';
    foreach($locationList as $location){
      $ctrl .= '<option value='.$location['id'].' class="location-select-option" '.($siteUrlParams[self::$locationKey]['value']==$location['id'] ? 'selected=\"selected\" ' : '').'>'.
               $location['name'].(isset($args['includeSrefInLocationFilter']) && $args['includeSrefInLocationFilter'] ? ' ('.$location['centroid_sref'].')' : '').
               '</option>';
    }
    $ctrl.='</select><br />';
    self::set_up_control_change($ctrlid, self::$locationKey, $siteUrlParams);
    return $ctrl;
  }

  /* This is the URL parameter used to pass the user_id filter through */
  private static $userKey = 'userID';
  
  private function user_control($args, $readAuth, $node)
  {
    global $user;
    // if the user is changed then we must reset the location
    $siteUrlParams = array(
      self::$userKey => array(
        'name' => self::$userKey,
        'value' => isset($_GET[self::$userKey]) ? $_GET[self::$userKey] : ''),
      self::$locationKey => array(
        'name' => self::$locationKey,
        'value' => ''));
    // need to scan param_presets for survey_id.
    $presets = get_options_array_with_user_data($args['param_presets']);
    if(!isset($presets['survey_id']) || $presets['survey_id']=='')
      return('<p>'.lang::get('The user selection control requires that survey_id is set in the presets in the form parameters.').'</p>');
    $attrArgs = array(
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'fieldprefix'=>'smpAttr',
        'extraParams'=>$readAuth,
        'survey_id'=>$presets['survey_id']);
    $sampleAttributes = data_entry_helper::getAttributes($attrArgs, false);
    $cmsAttr=extract_cms_user_attr($sampleAttributes,false);
    if(!$cmsAttr)
      return('<p>'.lang::get('The user selection control requires that CMS User ID sample attribute is defined for samples in this survey.').'</p>');
    $attrListArgs=array('nocache'=>true,
        'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
                           'sample_attribute_id'=>$cmsAttr['attributeId']),
                     $readAuth),
        'table'=>'sample_attribute_value');
    $attrList = data_entry_helper::get_population_data($attrListArgs);
    if (isset($attrList['error']))
      return $attrList['error'];
    $userList=array();
    foreach($attrList as $attr)
      if($attr['id']!=null)
        $userList[intval($attr['raw_value'])] = true;
    // This next bit is DRUPAL specific
    $retVal = array();
    $results = db_query('SELECT uid, name FROM {users}');
    while($result = db_fetch_object($results)){
      $account = user_load($result->uid);
      if(isset($userList[$account->uid]) && $userList[$account->uid])
        $userList[$account->uid] = $account;
    }
    $ctrlid='calendar-user-select-'.$node->nid;
    $ctrl='<label for="'.$ctrlid.'" class="user-select-label">'.lang::get('Filter by user').
          ' :</label><select id="'.$ctrlid.'" class="user-select">'.
          '<option value="" class="user-select-option" '.($siteUrlParams[self::$userKey]['value']=='' ? 'selected=\"selected\" ' : '').'>'.lang::get('All Users').'</option>';
    foreach($userList as $id => $account){
      $ctrl .= '<option value='.$id.' class="user-select-option" '.($siteUrlParams[self::$userKey]['value']==$id ? 'selected=\"selected\" ' : '').'>'.$account->name.'</option>';
    }
    $ctrl.='</select><br />';
    self::set_up_control_change($ctrlid, self::$userKey, $siteUrlParams);
    return $ctrl;
  }

  private function set_up_control_change($ctrlid, $urlparam, $siteUrlParams) {
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = data_entry_helper::get_reload_link_parts();
    // find the names of the params we must not include
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!array_key_exists($key, $siteUrlParams)){
        $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?')===false ? '?' : '&')."$key=$value";
      }
    }
    $param=(strpos($reloadUrl['path'],'?')===false ? '?' : '&').$urlparam.'=';
    data_entry_helper::$javascript .="
jQuery('#".$ctrlid."').change(function(){
  window.location = '".$reloadUrl['path']."' + (jQuery(this).val()=='' ? '' : '".$param."'+jQuery(this).val());
});
";
  }

  private function copy_args($args, &$options, $list){
    foreach($list as $arg){
      if(isset($args[$arg]))
        $options[$arg]=$args[$arg];
    }
  }

  private function date_control($args, $readAuth, $node, &$options)
  {
    // Future enhancements: extend this control to allow user selection by month, a fixed currentmonth, 
    // and completely free user selectable start and end dates
    switch($args['dateFilter']){
      case 'none': return '';
      case 'currentyear':
        $options['date_start'] = date('Y').'-Jan-01';
        $options['date_end'] = date('Y').'-Dec-31';
        return '<p>'.lang::get('Data for ').date('Y').'</p>';
      default: // case year
        // Add year paginator where it can have an impact for both tables and plots.
        $yearKey = 'year';
        $siteUrlParams = array(
          $yearKey => array(
            'name' => $yearKey,
            'value' => isset($_GET[$yearKey]) ? $_GET[$yearKey] : date('Y')));
              $reloadUrl = data_entry_helper::get_reload_link_parts();
        // find the names of the params we must not include
        foreach ($reloadUrl['params'] as $key => $value) {
          if (!array_key_exists($key, $siteUrlParams)){
            $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?')===false ? '?' : '&')."$key=$value";
          }
        }
        $param=(strpos($reloadUrl['path'],'?')===false ? '?' : '&').$yearKey.'=';
        $r .= "\n<table id=\"date-control-table\" class=\"ui-widget ui-widget-content ui-corner-all report-grid\"><thead class=\"ui-widget-header\"><tr><th><a title=\"".($siteUrlParams[$yearKey]['value']-1)."\" rel=\"\nofollow\" href=\"".$reloadUrl['path'].$param.($siteUrlParams[$yearKey]['value']-1)."\" class=\"ui-datepicker-prev ui-corner-all\"><span class=\"ui-icon ui-icon-circle-triangle-w\">Prev</span></a></th><th><span class=\"thisYear\">".$siteUrlParams[$yearKey]['value']."</span></th><th>";
        if($siteUrlParams[$yearKey]['value']<date('Y')){
          $r .= "<a title=\"".($siteUrlParams[$yearKey]['value']+1)."\" rel=\"\nofollow\" href=\"".$reloadUrl['path'].$param.($siteUrlParams[$yearKey]['value']+1)."\" class=\"ui-datepicker-next ui-corner-all\"><span class=\"ui-icon ui-icon-circle-triangle-e\">Next</span></a>";
        }
        $r.= '</th></tr></thead></table>';
        $options['date_start'] = $siteUrlParams[$yearKey]['value'].'-Jan-01';
        $options['date_end'] = $siteUrlParams[$yearKey]['value'].'-Dec-31';
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
    self::copy_args($args, $reportOptions,
      array('weekstart','weekOneContains','weekNumberFilter',
            'outputTable','outputLineChart','simultaneousOutput','defaultOutput',
            'tableHeaders','chartLabels','disableableSeries',
            'rowGroupColumn','width','height'));
      
    // Advanced Chart options
    $rendererOptions = trim($args['renderer_options']);
    if (!empty($rendererOptions))
      $chartOptions['rendererOptions'] = json_decode($rendererOptions, true);
    $legendOptions = trim($args['legend_options']);
    if (!empty($legendOptions))
      $chartOptions['legendOptions'] = json_decode($legendOptions, true);
    $axesOptions = trim($args['axes_options']);
    if (!empty($axesOptions))
      $chartOptions['axesOptions'] = json_decode($axesOptions, true);
      
    if(isset($args['countColumn']) && $args['countColumn']!='') {
      $reportOptions['countColumn']= 'attr_occurrence_'.str_replace(' ', '_', strtolower($args['countColumn'])); // assume that this is an occurrence attribute.
      $reportOptions['extraParams']['occattrs']=$args['countColumn'];
    }

    $retVal = '';
    // Add controls first
    if((isset($args['managerPermission']) && $args['managerPermission']!="" && user_access($args['managerPermission']))) {
      $retVal .= self::user_control($args, $auth, $node);
      $reportOptions['userId'] = (isset($_GET[self::$userKey])?$_GET[self::$userKey]:'');
    } else {
      $reportOptions['userId'] = $user->uid;
    }
    if(isset($args['includeLocationFilter']) && $args['includeLocationFilter']){
    	$retVal .= self::location_control($args, $auth, $node);
    	$reportOptions['extraParams']['location_id'] = (isset($_GET[self::$locationKey])?$_GET[self::$locationKey]:'');
    };
    $retVal .= self::date_control($args, $auth, $node, $reportOptions);
    $retVal .= report_helper::report_calendar_summary($reportOptions);
    return $retVal;
  }
}