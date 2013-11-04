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
          'group'=>'Advanced Chart Options'
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
    "show":{"type":"bool","desc":"Whether to display the legend on the graph."},
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
          'group'=>'Advanced Chart Options'
        ),
        array(
          'name' => 'series_options',
          'caption' => 'Series Options',
          'description' => 'A list of series options to pass to the chart with one entry per series. '.
              'Applies to line and bar charts only. For full details of the options available, see '.
              '<a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Series">chart series options</a>. ',
          'type' => 'jsonwidget',
          'schema'=>'{
  "type":"seq",
  "title":"Series List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Series",
      "mapping":
      {
        "show": {"type":"bool"},
        "label": {"type":"str"},
        "showlabel": {"type":"bool"},
        "color": {"type":"str","desc":"Specify the colour using CSS format, e.g. #ffffff or a named colour."},
        "lineWidth": {"type":"number","desc":"Width of the line in pixels."},
        "shadow": {"type":"bool"},
        "shadowAngle": {"type":"int","desc":"Shadow angle in degrees."},
        "shadowOffset": {"type":"number","desc":"Shadow offset from line in pixels."},
        "shadowDepth": {"type":"int","desc":"Number of times shadow is stroked, each stroke offset shadowOffset from the last."},
        "shadowAlpha": {"type":"number","desc":"Alpha channel transparency of shadow.  0 = transparent."},
        "breakOnNull": {"type":"bool","desc":"Whether line segments should be be broken at null value.  False will join point on either side of line."},
        "showLine": {"type":"bool","desc":"Whether to actually draw the line or not.  Series will still be renderered, even if no line is drawn."},
        "showMarker": {"type":"bool","desc":"Whether or not to show the markers at the data points."},
        "rendererOptions": {"type":"map",
            "mapping": {
            }
        },
        "markerOptions": {"type":"map",
            "mapping": {
              "style": {"type":"str","enum":["diamond","circle","square","x","plus","dash","filledDiamond","filledCircle","filledSquare"]},
              "size": {"type":"int"},
              "color": {"type":"str"}
            }
        },
        "fill": {"type":"bool","desc":"True or false, wether to fill under lines or in bars.  May not be implemented in all renderers."},
        "fillColor": {"type":"str","desc":"CSS color spec to use for fill under line.  Defaults to line color."},
        "fillAlpha": {"type":"number","desc":"Alpha transparency to apply to the fill under the line (between 0 and 1).  Use this to adjust alpha separate from fill color."},
        "useNegativeColors": {"type":"bool","desc":"True to color negative values differently in filled and bar charts."},
        "trendline": {
          "type":"map",
          "mapping": {
            "show": {"type":"bool"},
            "color":{"type":"str","desc":"Specify the colour using CSS format, e.g. #ffffff or a named colour."}
          }
        }
      }
    }
  ]
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
    iform_load_helpers(array('report_helper', 'map_helper'));
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']); 
    $chartOptions = iform_report_get_report_options($args, $auth);
    $chartOptions = array_merge($chartOptions, array(
      'id' => 'chart-div',
      'width'=> $args['width'],
      'height'=> $args['height'],
      'chartType' => $args['chart_type'],
      'yValues'=>explode(',', $args['y_values']),
      'output'=>$args['output']
    ));
    $xLabels = trim($args['x_labels']);
    if (empty($xLabels))
      $chartOptions['xValues']=explode(',', $args['x_values']);
    else
      $chartOptions['xLabels']=explode(',', $args['x_labels']);
    // advanced options
    if (!empty($args['renderer_options'])) {
      $rendererOptions = trim($args['renderer_options']);
      $chartOptions['rendererOptions'] = json_decode($rendererOptions, true);
    }
    if (!empty($args['legend_options'])) {
      $legendOptions = trim($args['legend_options']);
      $chartOptions['legendOptions'] = json_decode($legendOptions, true);
    }
    if (!empty($args['axes_options'])) {
      $seriesOptions = trim($args['series_options']);
      $chartOptions['seriesOptions'] = json_decode($seriesOptions, true);
    }
    if (!empty($args['series_options'])) {
      $axesOptions = trim($args['axes_options']);
      $chartOptions['axesOptions'] = json_decode($axesOptions, true);
    }
    
    //User has elected for parameters form only
    if ($args['output']==='form')
      $chartOptions['paramsOnly']=true;
    else {
      if (isset($chartOptions['paramsOnly']))
        unset($chartOptions['paramsOnly']);
    } 
    //User has elected for parameters form only or 
    //both the chart and parameters form together
    if ($args['output']==='form'||$args['output']==='default')
      $chartOptions['completeParamsForm']=true;
    else {
      if (isset($chartOptions['completeParamsForm']))
        unset($chartOptions['completeParamsForm']);
    }  
    //User has elected for the chart only
    if ($args['output']==='output') {
      $chartOptions['autoParamsForm']=false;
    }
    
    $r = '<br/>'.report_helper::report_chart($chartOptions);
    return $r;
  }

}