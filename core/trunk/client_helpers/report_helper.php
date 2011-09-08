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
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */
 
/**
 * Link in other required php files.
 */
require_once('lang.php');
require_once('helper_base.php');

/**
 * Static helper class that provides methods for dealing with reports.
 */
class report_helper extends helper_base {

 /**
  * Control which outputs a treeview of the reports available on the warehouse, with
  * radio buttons for selecting a report. The title and description of the currently selected
  * report are displayed alongside.
  *
  * @param array $options Options array which accepts the following standard options: id,
  * fieldname, class, default, readAuth.
  */
  public static function report_picker($options) {
    self::add_resource('reportPicker');
    $options = array_merge(array(
      'id'=>'report-picker',
      'fieldname' => 'report_name',
      'default' => '',
      'class' => ''
    ), $options);
    // add class rather than replacing existing
    $options['class'] .= ' report-picker-container control-box ui-widget ui-widget-content';
    $reports = '';
    $response = self::http_post(self::$base_url.'index.php/services/report/report_list?nonce='.
        $options['readAuth']['nonce'].'&auth_token='.$options['readAuth']['auth_token']);
    if (isset($response['output'])) {
      $output = json_decode($response['output'], true);
      $reports .= self::get_report_list_level($options['fieldname'], $options['default'], $output);
    }
    self::$javascript .= '$("#'.$options['id'].' > ul").treeview({collapsed: true});'."\n";
    self::$javascript .= "indiciaData.reportList=".$response['output'].";\n";
    self::$javascript .= '$(\'#'.$options['id'].' > ul input[checked="checked"]\').click();'."\n";
    self::$javascript .= '$(\'#'.$options['id'].' > ul input[checked="checked"]\').parents("#'.$options['id'].' ul").show();'."\n";
    $options['reports']=$reports;
    return self::apply_template('report_picker', $options);
  }

  /**
   * Outputs a single level of the hierarchy of available reports, then iterates into sub-
   * folders.
   * @param string $fieldname The fieldname for the report_picker control (=HTML form value)
   * @param string $default Name of the report to be initially selected
   * @param array $list Array of the reports and folders within the level to be output.
   * @return HTML for the unordered list containing the level.
   * @access private
   */
  private static function get_report_list_level($fieldname, $default, $list) {
    $r = '';
    foreach($list as $name=>$item) {
      if ($item['type']=='report') {
        $id = 'opt_'.str_replace('/','_',$item['path']);
        $checked = $item['path']==$default ? ' checked="checked"' : '';
        $r .= '<li><label class="ui-helper-reset auto">'.
            '<input type="radio" id="'.$id.'" name="'.$fieldname.'" value="'.$item['path'].
            '" onclick="displayReportMetadata(\''.$item['path'].'\');" '.$checked.'>'.
            $item['title'].
            "</input></label></li>\n";
      }
      else {
        $name = ucwords(str_replace('_', ' ', $name));
        $r .= "<li>$name\n";
        $r .= self::get_report_list_level($fieldname, $default, $item['content']);
        $r .= "</li>\n";
      }
    }
    if (!empty($r)) 
      $r = "<ul>\n$r\n</ul>\n";
    return $r;
  }

  /**
   * Returns a simple HTML link to download the contents of a report defined by the options. The options arguments supported are the same as for the 
   * report_grid method. Pagination information will be ignored (e.g. itemsPerPage).
   * If this download link is to be displayed alongside a report_grid to provide a download of the same data, set the id
   * option to the same value for both the report_download_link and report_grid controls to link them together.
   */
  public static function report_download_link($options) {
    $options = array_merge(array(
      'caption' => 'Download this report', // a reasonable maximum
    ), $options);
    $options = self::get_report_grid_options($options);
    $options['itemsPerPage'] = 10000;
    $options['linkOnly'] = true;
    $currentParamValues = self::get_report_grid_current_param_values($options);
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    // don't want to paginate the download link
    unset($sortAndPageUrlParams['page']);
    $extras = self::get_report_sorting_paging_params($options, $sortAndPageUrlParams);    
    $link = self::get_report_data($options, $extras.'&'.self::array_to_query_string($currentParamValues, true), true). '&mode=csv';
    global $indicia_templates;
    return str_replace(array('{link}','{caption}'), array($link, lang::get($options['caption'])), $indicia_templates['report_download_link']);;
  }
  
  /**
  * <p>Outputs a grid that loads the content of a report or Indicia table.</p>
  * <p>The grid supports a simple pagination footer as well as column title sorting through PHP. If
  * used as a PHP grid, note that the current web page will reload when you page or sort the grid, with the
  * same $_GET parameters but no $_POST information. If you need 2 grids on one page, then you must define a different
  * id in the options for each grid.</p>
  * <p>The grid operation will be handled by AJAX calls when possible to avoid reloading the web page.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid's container div. This is required if there is more than
  * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>reportGroup</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report by giving both report controls the same reportGroup string. This will only work when all 
  * parameters required by this report are covered by the other report's parameters form.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view.</li>
  * <li><b>view</b>
  * When loading from a view, specify list, gv or detail to determine which view variant is loaded. Default is list.
  * </li>
  * <li><b>itemsPerPage</b><br/>
  * Number of rows to display per page. Defaults to 20.</li>
  * <li><b>columns</b><br/>
  * Specify a list of the columns you want to output if you need more control over the columns, for example to
  * specify the order, change the caption or build a column with a configurable data display using a template.
  * Pass an array to this option, with each array entry containing an associative array that specifies the
  * information about the column represented by the position within the array. The associative array for the column can contain
  * the following keys:
  *  - fieldname: name of the field to output in this column. Does not need to be specified when using the template option.
  *  - display: caption of the column, which defaults to the fieldname if not specified
  *  - actions: list of action buttons to add to each grid row. Each button is defined by a sub-array containing
  *      values for caption, visibility_field, url, urlParams, class and javascript. The visibibility field is an optional
  *      name of a field in the data which contains true or false to define the visibility of this action. The javascript, url 
  *      and urlParams values can all use the field names from the report in braces as substitutions, for example {id} is replaced
  *      by the value of the field called id in the respective row. In addition, the url can use {currentUrl} to represent the 
  *      current page's URL, {rootFolder} to represent the folder on the server that the current PHP page is running from, and 
  *      {imageFolder} for the image upload folder. Because the javascript may pass the field values as parameters to functions, 
  *      there are escaped versions of each of the replacements available for the javascript action type. Add -escape-quote or 
  *      -escape-dblquote to the fieldname. For example this would be valid in the action javascript: foo("{bar-escape-dblquote}"); 
  *      even if the field value contains a double quote which would have broken the syntax.
  *  - visible: true or false, defaults to true
  *  - template: allows you to create columns that contain dynamic content using a template, rather than just the output
  *  of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values.
  *  Note that template columns cannot be sorted by clicking grid headers.
  * An example array for the columns option is:
  * array(
  *   array('fieldname' => 'survey', 'display' => 'Survey Title'),
  *   array('display' => 'action', 'template' => '<a href="www.mysite.com\survey\{id}\edit">Edit</a>'),
  *   array('display' => 'Actions', 'actions' => array(
  *     array('caption' => 'edit', 'url'=>'{currentUrl}', 'urlParams'=>array('survey_id'=>'{id}'))
  *   ))
  *
  * )
  * </li>
  * <li><b>rowId</b>
  * Optional. Names the field in the data that contains the unique identifier for each row. If set, then the &lt;tr&gt; elements have their id attributes
  * set to row + this field value, e.g. row37.</li>
  * <li><b>includeAllColumns</b>
  * Defaults to true. If true, then any columns in the report, view or table which are not in the columns
  * option array are automatically added to the grid after any columns specified in the columns option array.
  * Therefore the default state for a report_grid control is to include all the report, view or table columns
  * in their default state, since the columns array will be empty.</li>
  * <li><b>headers</b>
  * Should a header row be included? Defaults to true.
  * <li><b>galleryColCount</b>
  * If set to a value greater than one, then each grid row will contain more than one record of data from the database, allowing
  * a gallery style view to be built. Defaults to 1.
  * <li><b>autoParamsForm</b>
  * Defaults to true. If true, then if a report requires parameters, a parameters input form will be auto-generated
  * at the top of the grid. If set to false, then it is possible to manually build a parameters entry HTML form if you
  * follow the following guidelines. First, you need to specify the id option for the report grid, so that your
  * grid has a reproducable id. Next, the form you want associated with the grid must itself have the same id, but with
  * the addition of params on the end. E.g. if the call to report_grid specifies the option 'id' to be 'my-grid' then
  * the parameters form must be called 'my-grid-params'. Finally the input controls which define each parameter must have
  * the name 'param-id-' followed by the actual parameter name, replacing id with the grid id. So, in our example,
  * a parameter called survey will need an input or select control with the name attribute set to 'param-my-grid-survey'.
  * The submit button for the form should have the method set to "get" and should post back to the same page.
  * As a final alternative, if parameters are required by the report but some can be hard coded then
  * those may be added to the filters array.</li>
  * <li><b>filters</b><br/>
  * Array of key value pairs to include as a filter against the data.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values.</li>
  * <li><b>paramsOnly</b>
  * Defaults to false. If true, then this method will only return the parameters form, not the grid content. autoParamsForm
  * is ignored if this flag is set.</li>
  * <li><b>ignoreParams</b>
  * Array that can be set to a list of the report parameter names that should not be included in the parameters form. Useful
  * when using paramsOnly=true to display a parameters entry form, but the system has default values for some of the parameters
  * which the user does not need to be asked about.</li>
  * <li><b>completeParamsForm</b>
  * Defaults to true. If false, the control HTML is returned for the params form without being wrapped in a <form> and
  * without the Run Report button, allowing it to be embedded into another form.</li>
  * <li><b>paramsFormButtonCaption</b>
  * Caption of the button to run the report on the report parameters form. Defaults to Run Report. This caption
  * is localised when appropriate.
  * <li><b>paramsInMapToolbar</b>
  * If set to true, then the parameters for this report are not output, but are passed to a map_panel control
  * (which must therefore exist on the same web page) and are output as part of the map's toolbar.
  * </li>
  * <li><b>footer</b>
  * Additional HTML to include in the report footer area. {currentUrl} is replaced by the
  * current page's URL, {rootFolder} is replaced by the folder on the server that the current PHP page 
  * is running from.</li>
  * </li>
  * <li><b>downloadLink</b>
  * Should a download link be included in the report footer? Defaults to false.</li>
  * </ul>
  * @todo Allow additional params to filter by table column or report parameters
  * @todo Display a filter form for direct mode
  * @todo For report mode, provide an AJAX/PHP button that can load the report from parameters
  * in a form on the page.
  */
  public static function report_grid($options) {
    self::add_resource('fancybox');
    self::$javascript .= "jQuery('a.fancybox').fancybox();\n";
    $options = self::get_report_grid_options($options);
    $r = '';
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    $extras = self::get_report_sorting_paging_params($options, $sortAndPageUrlParams);
    // specify the view variant to load, if loading from a view
    if ($options['mode']=='direct') $extras .= '&view='.$options['view'];
    // request the report data using the preset values in extraParams but not any parameter defaults or entries in the URL. This is because the preset
    // values cause the parameter not to be shown, whereas defaults and URL params still show the param in the parameters form. So here we are asking for the 
    // parameters form if needed, else the report data. 
    $response = self::get_report_data($options, $extras);
    if (isset($response['error'])) return $response['error'];
    if (isset($response['parameterRequest'])) {
      $currentParamValues = self::get_report_grid_current_param_values($options);
      $r .= self::get_report_grid_parameters_form($response, $options, $currentParamValues);
      // if we have a complete set of parameters in the URL, we can re-run the report to get the data
      if (count(array_intersect_key($currentParamValues, $response['parameterRequest']))==count($response['parameterRequest'])) {
        $response = self::get_report_data($options, $extras.'&'.self::array_to_query_string($currentParamValues, true));
        if (isset($response['error'])) return $response['error'];
        $records = $response['records'];
      }
    } else {
      if ($options['autoParamsForm'] && $options['mode']=='direct') {
        $r .= self::get_direct_mode_params_form($options);
      }
      $records = $response['records'];
    }
    // return the params form, if that is all that is being requested, or the parameters are not complete.
    if ($options['paramsOnly'] || !isset($records)) return $r;
    
    self::report_grid_get_columns($response, $options);
    
    $pageUrl = self::report_grid_get_reload_url($sortAndPageUrlParams);
    $thClass = $options['thClass'];
    $r .= "\n<table class=\"".$options['class']."\">";
    if ($options['headers']!==false) {
      $r .= "\n<thead class=\"$thClass\"><tr>\n";
      // build a URL with just the sort order bit missing, so it can be added for each table heading link
      $sortUrl = $pageUrl . ($sortAndPageUrlParams['page']['value'] ?
          $sortAndPageUrlParams['page']['name'].'='.$sortAndPageUrlParams['page']['value'].'&' :
          ''
      );
      $sortdirval = $sortAndPageUrlParams['sortdir']['value'] ? strtolower($sortAndPageUrlParams['sortdir']['value']) : 'asc';
      // Output the headers. Repeat if galleryColCount>1;
      for ($i=0; $i<$options['galleryColCount']; $i++) {
        foreach ($options['columns'] as $field) {
          if (isset($field['visible']) && ($field['visible']=='false' || $field['visible']===false))
            continue; // skip this column as marked invisible
          // allow the display caption to be overriden in the column specification
          $caption = lang::get(empty($field['display']) ? $field['fieldname'] : $field['display']);
          if (isset($field['fieldname'])) {
            if (empty($field['orderby'])) $field['orderby']=$field['fieldname'];
            $sortLink = $sortUrl.$sortAndPageUrlParams['orderby']['name'].'='.$field['orderby'];
            // reverse sort order if already sorted by this field in ascending dir
            if ($sortAndPageUrlParams['orderby']['value']==$field['orderby'] && $sortAndPageUrlParams['sortdir']['value']!='DESC')
              $sortLink .= '&'.$sortAndPageUrlParams['sortdir']['name']."=DESC";
            if (!isset($field['img']) || $field['img']!='true')
              $caption = "<a href=\"$sortLink\" title=\"Sort by $caption\">$caption</a>";
            // set a style for the sort order
            $orderStyle = ($sortAndPageUrlParams['orderby']['value']==$field['orderby']) ? ' '.$sortdirval : '';
            $orderStyle .= ' sortable';
            $fieldId = ' id="' . $options['id'] . '-th-' . $field['orderby'] . '"';
          } else {
            $orderStyle = '';
            $fieldId = '';
          }
          $r .= "<th$fieldId class=\"$thClass$orderStyle\">$caption</th>\n";
        }
      }
      $r .= "</tr></thead>\n";
    }
    $currentUrl = self::get_reload_link_parts();
    // automatic handling for Drupal clean urls.
    $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
    $r .= '<tfoot>';
    $r .= '<tr><td colspan="'.count($options['columns'])*$options['galleryColCount'].'">'.self::output_pager($options, $pageUrl, $sortAndPageUrlParams, $response).'</td></tr>'.
    $extraFooter = '';
    if (isset($options['footer']) && !empty($options['footer'])) {
      $footer = str_replace(array('{rootFolder}', '{currentUrl}'), 
          array(dirname($_SERVER['PHP_SELF']) . ($pathParam=='' ? '/' : "?$pathParam="), $currentUrl['path']), $options['footer']);
      $extraFooter .= '<div class="left">'.$footer.'</div>';
    }
    if (isset($options['downloadLink']) && $options['downloadLink'] && count($records)>0)
      $extraFooter .= '<div class="right">'.self::report_download_link($options).'</div>';
    if (!empty($extraFooter))
      $r .= '<tr><td colspan="'.count($options['columns']).'">'.$extraFooter.'</td></tr>';
    $r .= '</tfoot>';
    $r .= "<tbody>\n";
    $rowClass = '';
    $outputCount = 0;
    $imagePath = self::get_uploaded_image_folder();
    $relpath = self::relative_client_helper_path();
    if (count($records)>0) {
      $rowInProgress=false;
      foreach ($records as $rowIdx => $row) {
        // Don't output the additional row we requested just to check if the next page link is required.
        if ($outputCount>=$options['itemsPerPage'])
          break;
        // Put some extra useful paths into the row data, so it can be used in the templating
        $row = array_merge($row, array(
            'rootFolder'=>dirname($_SERVER['PHP_SELF']) . '/',
            'imageFolder'=>$imagePath,
            // allow the current URL to be replaced into an action link. We extract url parameters from the url, not $_GET, in case
            // the url is being rewritten.
            'currentUrl' => $currentUrl['path']
        ));
        // set a unique id for the row if we know the identifying field.
        $rowId = isset($options['rowId']) ? ' id="row'.$row[$options['rowId']].'"' : '';
        if ($rowIdx % $options['galleryColCount']==0) {
          $r .= "<tr $rowClass$rowId>";
          $rowInProgress=true;
        }
        foreach ($options['columns'] as $field) {
          $classes=array();
          if (isset($field['visible']) && ($field['visible']=='false' || $field['visible']===false))
            continue; // skip this column as marked invisible
          if (isset($field['actions'])) {
            $value = self::get_report_grid_actions($field['actions'],$row, $pathParam);
            $classes[]='actions';
          } elseif (isset($field['template'])) {
            $value = self::mergeParamsIntoTemplate($row, $field['template'], true, true);
          }
          else {
            $value = isset($field['fieldname']) && isset($row[$field['fieldname']]) ? $row[$field['fieldname']] : '';
            // The verification_1 form depends on the tds in the grid having a class="data fieldname".
            $classes[]='data';
            $classes[]=$field['fieldname'];
          }
          if (isset($field['class']))
            $classes[] = $field['class'];
          if (count($classes)>0)
            $class = ' class="'.implode(' ', $classes).'"';
          else
            $class = '';
          if (isset($field['img']) && $field['img']=='true' && !empty($value))
            $value = "<a href=\"$imagePath$value\" class=\"fancybox\"><img src=\"$imagePath"."thumb-$value\" /></a>";
          $r .= "<td$class>$value</td>\n";
        }
        if ($rowIdx % $options['galleryColCount']==$options['galleryColCount']-1) {
          $rowInProgress=false;
          $r .= '</tr>';
        }
        $rowClass = empty($rowClass) ? ' class="'.$options['altRowClass'].'"' : '';
        $outputCount++;
      }
      if ($rowInProgress)
        $r .= '</tr>';
    }
    $r .= "</tbody></table>\n";
    // $r may be empty if a spatial report has put all its controls on the map toolbar, when using params form only mode.
    // In which case we don't need to output anything.
    if (!empty($r)) {
      // Output a div to keep the grid and pager together
      $r = "<div id=\"".$options['id']."\">$r</div>\n";
      // Now AJAXify the grid
      self::add_resource('reportgrid');
      $uniqueName = 'grid_' . preg_replace( "/[^a-z0-9]+/", "_", $options['id']);
      $group = preg_replace( "/[^a-z0-9]+/", "_", $options['reportGroup']);
      global $indicia_templates;
      
      self::$javascript .= "
if (typeof indiciaData.reports==='undefined') { indiciaData.reports={}; }
if (typeof indiciaData.reports.$group==='undefined') { indiciaData.reports.$group={}; }
indiciaData.reports.$group.$uniqueName = $('#".$options['id']."').reportgrid({
  id: '".$options['id']."',
  mode: '".$options['mode']."',
  dataSource: '".str_replace('\\','/',$options['dataSource'])."',
  view: '".$options['view']."',
  itemsPerPage: ".$options['itemsPerPage'].",
  auth_token: '".$options['readAuth']['auth_token']."',
  nonce: '".$options['readAuth']['nonce']."',
  callback: '".$options['callback']."',
  url: '".parent::$base_url."',
  reportGroup: '".$options['reportGroup']."',
  autoParamsForm: '".$options['autoParamsForm']."',
  rootFolder: '".dirname($_SERVER['PHP_SELF'])."/',
  imageFolder: '".self::get_uploaded_image_folder()."',
  currentUrl: '".$currentUrl['path']."',
  galleryColCount: ".$options['galleryColCount'].",
  pagingTemplate: '".$indicia_templates['paging']."',
  pathParam: '".$pathParam."',
  altRowClass: '".$options['altRowClass']."'";
      if (isset($options['extraParams']))
        self::$javascript .= ",\n  extraParams: ".json_encode($options['extraParams']);
      if (isset($options['filters']))
        self::$javascript .= ",\n  filters: ".json_encode($options['filters']);
      if (isset($orderby))
        self::$javascript .= ",\n  orderby: '".$orderby."'";
      if (isset($sortdir))
        self::$javascript .= ",\n  sortdir: '".$sortdir."'";
      if (isset($response['count']))
        self::$javascript .= ",\n  recordCount: ".$response['count'];
      if (isset($options['columns']))
        self::$javascript .= ",\n  columns: ".json_encode($options['columns'])."
});\n";
    }
    return $r;
  }
  
  /**
   * Output pagination links
   */
  private static function output_pager($options, $pageUrl, $sortAndPageUrlParams, $response) {
    global $indicia_templates;
    $pagLinkUrl = $pageUrl . ($sortAndPageUrlParams['orderby']['value'] ? $sortAndPageUrlParams['orderby']['name'].'='.$sortAndPageUrlParams['orderby']['value'].'&' : '');
    $pagLinkUrl .= $sortAndPageUrlParams['sortdir']['value'] ? $sortAndPageUrlParams['sortdir']['name'].'='.$sortAndPageUrlParams['sortdir']['value'].'&' : '';
    if (!isset($response['count'])) {
      $r = self::simple_pager($options, $pageUrl, $sortAndPageUrlParams, $response, $pagLinkUrl);
    } else {
      $r = self::advanced_pager($options, $pageUrl, $sortAndPageUrlParams, $response, $pagLinkUrl);
    }
    $r = str_replace('{paging}', $r, $indicia_templates['paging_container']);
    return $r;
  }
  
  /**
   * Creates the HTML for the simple version of the pager.
   */
  private static function simple_pager($options, $pageUrl, $sortAndPageUrlParams, $response, $pagLinkUrl) {
    // skip pager if all records fit on one page
    if ($sortAndPageUrlParams['page']['value']==0 && count($response['records'])<=$options['itemsPerPage'])
      return '';
    else {
      $r = '';
      // If not on first page, we can go back.
      if ($sortAndPageUrlParams['page']['value']>0) {
        $prev = max(0, $sortAndPageUrlParams['page']['value']-1);
        $r .= "<a class=\"pag-prev pager-button\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$prev\">".lang::get('previous')."</a> \n";
      } else 
        $r .= "<span class=\"pag-prev ui-state-disabled pager-button\">".lang::get('previous')."</span> \n";
      // if the service call returned more records than we are displaying (because we asked for 1 more), then we can go forward
      if (count($response['records'])>$options['itemsPerPage']) {
        $next = $sortAndPageUrlParams['page']['value'] + 1;
        $r .= "<a class=\"pag-next pager-button\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$next\">".lang::get('next')." &#187</a> \n";
      } else 
        $r .= "<span class=\"pag-next ui-state-disabled pager-button\">".lang::get('next')."</span> \n";
      return $r;
    }
  }
  
  /**
   * Creates the HTML for the advanced version of the pager.
   */
  private static function advanced_pager($options, $pageUrl, $sortAndPageUrlParams, $response, $pagLinkUrl) {
    global $indicia_templates;
    $r = '';
    $replacements = array();
    // build a link URL to an unspecified page
    $pagLinkUrl .= $sortAndPageUrlParams['page']['name'];
    // If not on first page, we can include previous link.
    if ($sortAndPageUrlParams['page']['value']>0) {
      $prev = max(0, $sortAndPageUrlParams['page']['value']-1);
      $replacements['prev'] = "<a class=\"pag-prev pager-button\" href=\"$pagLinkUrl=$prev\">".lang::get('previous')."</a> \n";
      $replacements['first'] = "<a class=\"pag-first pager-button\" href=\"$pagLinkUrl=0\">".lang::get('first')."</a> \n";
    } else {
      $replacements['prev'] = "<span class=\"pag-prev pager-button ui-state-disabled\">".lang::get('prev')."</span>\n";
      $replacements['first'] = "<span class=\"pag-first pager-button ui-state-disabled\">".lang::get('first')."</span>\n";
    }
    $pagelist = '';
    $page = ($sortAndPageUrlParams['page']['value'] ? $sortAndPageUrlParams['page']['value'] : 0)+1;
    for ($i=max(1, $page-5); $i<=min(ceil($response['count']/$options['itemsPerPage']), $page+5); $i++) {
      if ($i===$page) 
        $pagelist .= "<span class=\"pag-page pager-button ui-state-disabled\" id=\"page-".$options['id']."-$i\">$i</span>\n";
      else
        $pagelist .= "<a class=\"pag-page pager-button\" href=\"$pagLinkUrl=".($i-1)."\" id=\"page-".$options['id']."-$i\">$i</a>\n";
    }
    $replacements['pagelist'] = $pagelist;
    // if not on the last page, display a next link
    if ($page<$response['count']/$options['itemsPerPage']) {
      $next = $sortAndPageUrlParams['page']['value'] + 1;
      $replacements['next'] = "<a class=\"pag-next pager-button\" href=\"$pagLinkUrl=$next\">".lang::get('next')."</a>\n";
      $replacements['last'] = "<a class=\"pag-last pager-button\" href=\"$pagLinkUrl=".round($response['count']/$options['itemsPerPage']-1)."\">".lang::get('last')."</a>\n";
    } else {
      $replacements['next'] = "<span class=\"pag-next pager-button ui-state-disabled\">".lang::get('next')."</span>\n";
      $replacements['last'] = "<span class=\"pag-last pager-button ui-state-disabled\">".lang::get('last')."</span>\n";
    }
    if ($response['count']) {
      $replacements['showing'] = '<span class="pag-showing">'.lang::get('Showing records {1} to {2} of {3}', 
          ($page-1)*$options['itemsPerPage']+1, 
          min($page*$options['itemsPerPage'], $response['count']),
          $response['count']).'</span>';
    } else {
      $replacements['showing'] = lang::get('No records');
    }
    $r = $indicia_templates['paging'];
    foreach($replacements as $search => $replace)
      $r = str_replace('{'.$search.'}', $replace, $r);
    return $r;
  }

 /**
  * <p>Outputs a div that contains a chart.</p>
  * <p>The chart is rendered by the jqplot plugin.</p>
  * <p>The chart loads its data from a report, table or view indicated by the dataSource parameter, and the
  * method of loading is indicated by xValues, xLabels and yValues. Each of these can be an array to define
  * a multi-series chart. The largest array from these 4 options defines the total series count. If an option
  * is not an array, or the array is smaller than the total series count, then the last option is used to fill
  * in the missing values. For example, by setting:<br/>
  * 'dataSource' => array('report_1', 'report_2'),<br/>
  * 'yValues' => 'count',<br/>
  * 'xLabels' => 'month'<br/>
  * then you get a chart of count by month, with 2 series' loaded separately from report 1 and report 2. Alternatively
  * you can use a single report, with 2 different columns for count to define the 2 series:<br/>
  * 'dataSource' => 'combined_report',<br/>
  * 'yValues' => array('count_1','count_2'),<br/>
  * 'xLabels' => 'month'<br/>
  * The latter is obviuosly slightly more efficient as only a single report is run. Pie charts will always revert to a
  * single series.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>mode</b><br/>
  * Pass report to retrieve the underlying chart data from a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view(s) to retrieve underlying data. Can be an array for multi-series charts.</li>
  * <li><b>class</b><br/>
  * CSS class to apply to the outer div.</li>
  * <li><b>headerClass</b><br/>
  * CSS class to apply to the box containing the header.</li>
  * <li><b>reportGroup</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report by giving both report controls the same reportGroup string. This will only work when all 
  * parameters required by this report are covered by the other report's parameters form.</li>
  * <li><b>height</b><br/>
  * Chart height in pixels.</li>
  * <li><b>width</b><br/>
  * Chart width in pixels.</li>
  * <li><b>chartType</b><br/>
  * Currently supports line, bar or pie.</li>
  * <li><b>rendererOptions</b><br/>
  * Associative array of options to pass to the jqplot renderer.
  * </li>
  * <li><b>legendOptions</b><br/>
  * Associative array of options to pass to the jqplot legend. For more information see links below.
  * </li>
  * <li><b>seriesOptions</b><br/>
  * For line and bar charts, associative array of options to pass to the jqplot series. For example:<br/>
  * 'seriesOptions' => array(array('label'=>'My first series','label'=>'My 2nd series'))<br/>
  * For more information see links below.
  * </li>
  * <li><b>axesOptions</b><br/>
  * For line and bar charts, associative array of options to pass to the jqplot axes. For example:<br/>
  * 'axesOptions' => array('yaxis'=>array('min' => 0, 'max' => '3', 'tickInterval' => 1))<br/>
  * For more information see links below.
  * </li>
  * <li><b>yValues</b><br/>
  * Report or table field name(s) which contains the data values for the y-axis (or the pie segment sizes). Can be
  * an array for multi-series charts.</li>
  * <li><b>xValues</b><br/>
  * Report or table field name(s) which contains the data values for the x-axis. Only used where the x-axis has a numerical value
  * rather than showing arbitrary categories. Can be an array for multi-series charts.</li>
  * <li><b>xLabels</b><br/>
  * When the x-axis shows arbitrary category names (e.g. a bar chart), then this indicates the report or view/table
  * field(s) which contains the labels. Also used for pie chart segment names. Can be an array for multi-series
  * charts.</li>
  * </ul>
  * @todo look at the ReportEngine to check it is not prone to SQL injection (eg. offset, limit).
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Series
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Axis
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html
  * @link http://www.jqplot.com/docs/files/plugins/jqplot-pieRenderer-js.html
  * @link http://www.jqplot.com/docs/files/jqplot-core-js.html#Legend
  */
  public static function report_chart($options) {
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'chartdiv',
      'class' => 'ui-widget ui-widget-content ui-corner-all',
      'headerClass' => 'ui-widget-header ui-corner-all',
      'height' => 400,
      'width' => 400,
      'chartType' => 'line', // bar, pie
      'rendererOptions' => array(),
      'legendOptions' => array(),
      'seriesOptions' => array(),
      'axesOptions' => array()
    ), $options);
    // @todo Check they have supplied a valid set of data & label field names
    self::add_resource('jqplot');
    $opts = array();
    switch ($options['chartType']) {
      case 'bar' :
        self::add_resource('jqplot_bar');
        $renderer='$.jqplot.BarRenderer';
        break;
      case 'pie' :
        self::add_resource('jqplot_pie');
        $renderer='$.jqplot.PieRenderer';
        break;
      // default is line
    }
    self::check_for_jqplot_plugins($options);
    $opts[] = "seriesDefaults:{\n".(isset($renderer) ? "  renderer:$renderer,\n" : '')."  rendererOptions:".json_encode($options['rendererOptions'])."}";
    $opts[] = 'legend:'.json_encode($options['legendOptions']);
    $opts[] = 'series:'.json_encode($options['seriesOptions']);
    // make yValues, xValues, xLabels and dataSources into arrays of the same length so we can treat single and multi-series the same
    $yValues = is_array($options['yValues']) ? $options['yValues'] : array($options['yValues']);
    $dataSources = is_array($options['dataSource']) ? $options['dataSource'] : array($options['dataSource']);
    if (isset($options['xValues'])) $xValues = is_array($options['xValues']) ? $options['xValues'] : array($options['xValues']);
    if (isset($options['xLabels'])) $xLabels = is_array($options['xLabels']) ? $options['xLabels'] : array($options['xLabels']);
    // What is this biggest array? This is our series count.
    $seriesCount = max(
        count($yValues),
        count($dataSources),
        (isset($xValues) ? count($xValues) : 0),
        (isset($xLabels) ? count($xLabels) : 0)
    );
    // any array that is too short must be padded out with the last entry
    if (count($yValues)<$seriesCount) $yValues = array_pad($yValues, $seriesCount, $yValues[count($yValues)-1]);
    if (count($dataSources)<$seriesCount) $dataSources = array_pad($dataSources, $seriesCount, $dataSources[count($dataSources)-1]);
    if (isset($xValues) && count($xValues)<$seriesCount) $xValues = array_pad($xValues, $seriesCount, $xValues[count($xValues)-1]);
    if (isset($xLabels) && count($xLabels)<$seriesCount) $xLabels = array_pad($xLabels, $seriesCount, $xLabels[count($xLabels)-1]);
    // build the series data
    $seriesData = array();
    $lastRequestSource = '';
    for ($idx=0; $idx<$seriesCount; $idx++) {
      // copy the array data back into the options array to make a normal request for report data
      $options['yValues'] = $yValues[$idx];
      $options['dataSource'] = $dataSources[$idx];
      if (isset($xValues)) $options['xValues'] = $xValues[$idx];
      if (isset($xLabels)) $options['xLabels'] = $xLabels[$idx];
      // now request the report data, only if the last request was not for the same data
      if ($lastRequestSource != $options['dataSource'])
        $data=self::get_report_data($options);
      if (isset($data['error']))
        // data returned must be an error message so may as well display it
        return $data['error'];
      $lastRequestSource = $options['dataSource'];
      $values=array();
      $xLabelsForSeries=array();
      foreach ($data as $row) {
        if (isset($options['xValues']))
          // 2 dimensional data
          $values[] = '['.$row[$options['xValues']].','.$row[$options['yValues']].']';
        else {
          // 1 dimensional data, so we should have labels. For a pie chart these are use as x data values. For other charts they are axis labels.
          if ($options['chartType']=='pie') {
            $values[] = '["'.$row[$options['xLabels']].'",'.$row[$options['yValues']].']';
          } else {
            $values[] = $row[$options['yValues']];
            if (isset($options['xLabels']))
              $xLabelsForSeries[] = $row[$options['xLabels']];
          }
        }
      }
      // each series will occupy an entry in $seriesData
      $seriesData[] = '['.implode(',', $values).']';
    }
    if (isset($options['xLabels']) && $options['chartType']!='pie') {
      // make a renderer to output x axis labels
      $options['axesOptions']['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';
      $options['axesOptions']['xaxis']['ticks'] = $xLabelsForSeries;
    }
    // We need to fudge the json so the renderer class is not a string
    $opts[] = str_replace('"$.jqplot.CategoryAxisRenderer"', '$.jqplot.CategoryAxisRenderer',
        'axes:'.json_encode($options['axesOptions']));

    // Finally, dump out the Javascript with our constructed parameters
    self::$javascript .= "$.jqplot('".$options['id']."',  [".implode(',', $seriesData)."], \n{".implode(",\n", $opts)."});\n";
    $r = '<div class="'.$options['class'].'" style="width:'.$options['width'].'; ">';
    if (isset($options['title']))
      $r .= '<div class="'.$options['headerClass'].'">'.$options['title'].'</div>';
    $r .= '<div id="'.$options['id'].'" style="height:'.$options['height'].'px;width:'.$options['width'].'px; "></div>'."\n";
    $r .= "</div>\n";
    return $r;
  }

  /**
   * Checks through the options array for the chart to look for any jqPlot plugins that
   * have been referred to so should be included.
   * Currently only scans for the trendline and category_axis_rendered plugins.
   * @param Array $options Chart control's options array
   */
  private static function check_for_jqplot_plugins($options) {
    foreach($options['seriesOptions'] as $series) {
      if (isset($series['trendline']))
        self::add_resource('jqplot_trendline');
    }
    if (isset($options['xLabels'])) {
      self::add_resource('jqplot_category_axis_renderer');
    }
  }
  
  private static function get_direct_mode_params_form($options) {
    $reloadUrl = self::get_reload_link_parts();
    $r = '<form action="'.$reloadUrl['path'].'" method="get" class="linear-form" id="filterForm-'.$options['id'].'">';
    $r .= '<label for="filters" class="auto">'.lang::get('Filter for').'</label> ';
    $value = (isset($_GET['filters'])) ? ' value="'.$_GET['filters'].'"' : '';
    $r .= '<input type="text" name="filters" id="filters" class="filterInput"'.$value.'/> ';
    $r .= '<label for="columns" class="auto">'.lang::get('in').'</label> <select name="columns" class="filterSelect" id="columns">';
    
    foreach ($options['columns'] as $column) {
      if (isset($column['fieldname']) && isset($column['display']) && (!isset($column['visible']) || $column['visible']===false)) {
        $selected = (isset($_GET['columns']) && $_GET['columns']==$column['fieldname']) ? ' selected="selected"' : '';
        $r .= "<option value=\"".$column['fieldname']."\"$selected>".$column['display']."</option>";
      }
    }
    $r .= "</select>\n";
    $r .= '<input type="submit" value="Filter" class="run-filter" class="ui-corner-all ui-state-default"/>'.
        '<button class="clear-filter" style="display: none">Clear</button>';
    $r .= "</form>\n";
    self::$javascript .= '$("#filter-'.$options['id'].'").click(function(e) {
  e.preventDefault();
  refreshGrid("'.$options['id'].'")
});
';
    return $r;
  }

 /**
  * Outputs the content of a report using freeform text templates to create output as required,
  * as opposed to the report_grid which forces a table based output. Has a header and footer
  * plus any number of bands which are output once per row, or once each time a particular
  * field value changes (i.e. acting as a header band).
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>mode</b><br/>
  * Pass report to retrieve the underlying data from a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view(s) to retrieve underlying data.</li>
  * <li><b>class</b><br/>
  * CSS class to apply to the outer div. Default is banded-report.</li>
  * <li><b>reportGroup</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report by giving both report controls the same reportGroup string. This will only work when all 
  * parameters required by this report are covered by the other report's parameters form.</li>
  * <li><b>header</b><br/>
  * Text to output as the header of the report.</li>
  * <li><b>footer</b><br/>
  * Text to output as the footer of the report.</li>
  * <li><b>bands</b><br/>
  * Array of bands to output per row. Each band is itself an array, with at least an
  * item called 'content' which contains an HTML template for the output of the band. The
  * template can contain replacements for each field value in the row, e.g. the
  * replacement {survey} is replaced with the value of the field called survey. In
  * addition, the band array can contain a triggerFields element, which contains an
  * array of the names of fields which act as triggers for the band to be output.
  * The band will then only be output once at the beginning of the report, then once
  * each time one of the named trigger fields' values change. Therefore when using
  * trigger fields the band acts as a group header band.</li>
  */
  public static function freeform_report($options) {
    $options = self::get_report_grid_options($options);
    $options = array_merge(array(
      'header' => '',
      'footer' => '',
      'bands' => array(),
      'class' => 'banded-report'
    ), $options);
    $r = '';
    $response = self::get_report_data($options);
    if (isset($response['error'])) return $response['error'];
    if (isset($response['parameterRequest'])) {
      $currentParamValues = self::get_report_grid_current_param_values($options);
      $r .= self::get_report_grid_parameters_form($response, $options, $currentParamValues);
      // if we have a complete set of parameters in the URL, we can re-run the report to get the data
      if (count(array_intersect_key($currentParamValues, $response['parameterRequest']))==count($response['parameterRequest'])) {
        $response = self::get_report_data($options, self::array_to_query_string($currentParamValues, true).'&wantColumns=1&wantParameters=1');
        if (isset($response['error'])) return $response['error'];
        $records = $response['records'];
      }
    } else {
      // because we did not ask for columns, the records are at the root of the response
      $records = $response;
    }

    if (!isset($records))
      return $r;
    if (count($records)>0) {
      // add a header
      $r .= '<div class="'.$options['class'].'">'.$options['header'];
      // output each row
      foreach ($records as $row) {
        // for each row, check through the list of report bands
        foreach ($options['bands'] as &$band) {
          // default is to output a band
          $outputBand = true;
          // if the band has fields which trigger it to be output when they change value between rows,
          // we need to check for changes to see if the band is to be output
          if (isset($band['triggerFields'])) {
            $outputBand = false;
            // Make sure we have somewhere to store the current field values for checking against
            if (!isset($band['triggerValues']))
              $band['triggerValues']=array();
            // look for changes in each trigger field
            foreach ($band['triggerFields'] as $triggerField) {
              if (!isset($band['triggerValues'][$triggerField]) || $band['triggerValues'][$triggerField]!=$row[$triggerField])
                // one of the trigger fields has changed value, so it means the band gets output
                $outputBand=true;
              // store the last value to compare against next time
              $band['triggerValues'][$triggerField] = $row[$triggerField];
            }
          }
          // output the band only if it has been triggered, or has no trigger fields specified.
          if ($outputBand)
            $r .= self::apply_replacements_to_template($band['content'], $row);
        }
      }
      // add a footer
      $r .= $options['footer'].'</div>';
    }
    return $r;
  }
  
 /**
  * Function to output a report onto a map rather than a grid.
  * Because there are many options for the map, this method does not generate the
  * map itself, rather it sends the output of the report onto a map_panel output
  * elsewhere on the page. Like the report_grid, this can output a parameters
  * form or can be set to use the parameters form from another output report (e.g.
  * another call to report_grid, allowing both a grid and map of the same data
  * to be generated). The report definition must contain a single column which is
  * configured as a mappable column or the report must specify a parameterised
  * CQL query to draw the map using WMS.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the report. This is required if there is more than
  * one different report (grid, chart or map) on a single web page to allow separation
  * of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>reportGroup</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report by giving both report controls the same reportGroup string. This will only work when all 
  * parameters required by this report are covered by the other report's parameters form.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view.</li>
  * <li><b>autoParamsForm</b>
  * Defaults to true. If true, then if a report requires parameters, a parameters input form will be auto-generated
  * at the top of the grid. If set to false, then it is possible to manually build a parameters entry HTML form if you
  * follow the following guidelines. First, you need to specify the id option for the report grid, so that your
  * grid has a reproducable id. Next, the form you want associated with the grid must itself have the same id, but with
  * the addition of params on the end. E.g. if the call to report_grid specifies the option 'id' to be 'my-grid' then
  * the parameters form must be called 'my-grid-params'. Finally the input controls which define each parameter must have
  * the name 'param-id-' followed by the actual parameter name, replacing id with the grid id. So, in our example,
  * a parameter called survey will need an input or select control with the name attribute set to 'param-my-grid-survey'.
  * The submit button for the form should have the method set to "get" and should post back to the same page.
  * As a final alternative, if parameters are required by the report but some can be hard coded then
  * those may be added to the filters array.</li>
  * <li><b>filters</b><br/>
  * Array of key value pairs to include as a filter against the data.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values.</li>
  * <li><b>paramsOnly</b>
  * Defaults to false. If true, then this method will only return the parameters form, not the grid content. autoParamsForm
  * is ignored if this flag is set.</li>
  * <li><b>ignoreParams</b>
  * Array that can be set to a list of the report parameter names that should not be included in the parameters form. Useful
  * when using paramsOnly=true to display a parameters entry form, but the system has default values for some of the parameters
  * which the user does not need to be asked about.</li>
  * <li><b>completeParamsForm</b>
  * Defaults to true. If false, the control HTML is returned for the params form without being wrapped in a <form> and
  * without the Run Report button, allowing it to be embedded into another form.</li>
  * <li><b>paramsFormButtonCaption</b>
  * Caption of the button to run the report on the report parameters form. Defaults to Run Report. This caption
  * is localised when appropriate.
  * <li><b>geoserverLayer</b>
  * For improved mapping performance, specify a layer on GeoServer which
  * has the same attributes and output as the report file. Then the report map can output
  * the contents of this layer filtered by the report parameters, rather than build a layer
  * from the report data.</li>
  * <li><b>geoserverLayerStyle</b>
  * Optional name of the SLD file available on GeoServer which is to be applied to the GeoServer layer.
  * </li>
  * <li><b>cqlTemplate</b>
  * Use with the geoserver_layer to provide a template for the CQL to filter the layer
  * according to the parameters of the report. For example, if you are using the report called
  * <em>map_occurrences_by_survey</em> then you can set the geoserver_layer to the indicia:detail_occurrences
  * layer and set this to <em>INTERSECTS(geom, #searchArea#) AND survey_id=#survey#</em>.</li>
  * <li>proxy<br/>
  * URL of a proxy on the local server to direct GeoServer WMS requests to. This proxy must be able to
  * cache filters in the same way as the iform_proxy Drupal module.</li>
  * <li>clickable<br/>
  * Set to true to enable clicking on the data points to see the underlying data. Default true.</li>
  * <li>clickableLayersOutputMode<br/>
  * Set popup, div or report to display popups, output data to a div, or filter associated reports when clicking on data points
  * with the query tool selected.</li>
  * <li>clickableLayersOutputDiv<br/>
  * Set to the id of a div to display the clicked data in, or leave blank to display a popup.</li>
  * </ul>
   */
  public static function report_map($options) {
    $options = self::get_report_grid_options($options);
    $options = array_merge(array(
      'clickable' => true,
      'clickableLayersOutputMode' => 'popup',
      'clickableLayersOutputDiv' => '',
    ), $options);
    if (empty($options['geoserverLayer'])) {
      // request the report data using the preset values in extraParams but not any parameter defaults or entries in the URL. This is because the preset
      // values cause the parameter not to be shown, whereas defaults and URL params still show the param in the parameters form. So here we are asking for the 
      // parameters form if needed, else the report data. 
      $response = self::get_report_data($options);
      if (isset($response['error'])) return $response['error'];
      if (isset($response['parameterRequest'])) {
        $currentParamValues = self::get_report_grid_current_param_values($options);
        $r .= self::get_report_grid_parameters_form($response, $options, $currentParamValues);
        // if we have a complete set of parameters in the URL, we can re-run the report to get the data
        if (count(array_intersect_key($currentParamValues, $response['parameterRequest']))==count($response['parameterRequest'])) {
          $response = self::get_report_data($options, self::array_to_query_string($currentParamValues, true).'&wantColumns=1&wantParameters=1');
          if (isset($response['error'])) return $response['error'];
          $records = $response['records'];
        }
      } else {
        // because we did not ask for columns, the records are at the root of the response
        $records = $response;
      }
    
      if (!isset($records))
        return $r;
      // find the geom column
      foreach($response['columns'] as $col=>$cfg) {
        if ($cfg['mappable']=='true') {
          $wktCol = $col;
          break;
        }
      }
      if (!isset($wktCol))
        $r .= "<p>".lang::get("The report's configuration does not output any mappable data")."</p>";
    } else {
      // using geoserver, so we just need to know the param values. 
      $response = self::get_report_data($options, self::array_to_query_string($currentParamValues, true).'&wantRecords=0&wantParameters=1');
      $currentParamValues = self::get_report_grid_current_param_values($options);
      $r .= self::get_report_grid_parameters_form($response, $options, $currentParamValues);
    }
    // don't load the report layer if not all parameters are filled in
    if (!isset($response['parameterRequest']) || count(array_intersect_key($currentParamValues, $response['parameterRequest']))==count($response['parameterRequest'])) {
      if (empty($options['geoserverLayer'])) {  
        // we are doing vector reporting via indicia services
        // first we need to build a style object which respects columns in the report output that define style settings for each vector.
        $settings=array();
        foreach($response['columns'] as $col=>$def) {
          if (!empty($def['feature_style'])) {
            // found a column that outputs data to input into a feature style parameter. ${} syntax is explained at http://docs.openlayers.org/library/feature_styling.html.
            $settings[$def['feature_style']] = "'\$\{$col\}'";
          }
        }
        // default features are color red by default
        $defsettings = array_merge(array(
          'fillColor'=> "'#ff0000'",
          'strokeColor'=> "'#ff0000'",
        ), $settings);
        // selected features are color blue by default
        $selsettings = array_merge(array(
          'fillColor'=> "'#0000ff'",
          'strokeColor'=> "'#0000ff'",
        ), $settings);
        // convert these styles into a JSON definition ready to feed into JS.
        $defsettings = '{'.implode(',', array_map(create_function('$key, $value', 'return $key.":".$value;'), array_keys($defsettings), array_values($defsettings))).'}';
        $selsettings = '{'.implode(',', array_map(create_function('$key, $value', 'return $key.":".$value;'), array_keys($selsettings), array_values($selsettings))).'}';
        report_helper::$javascript.= "
       
function addDistPoint(features, record, wktCol) {
  var geom=OpenLayers.Geometry.fromWKT(record[wktCol]);
  delete record[wktCol];
  features.push(new OpenLayers.Feature.Vector(geom, record));
}

var defaultStyle = OpenLayers.Util.applyDefaults($defsettings, OpenLayers.Feature.Vector.style['default']);
var selectStyle = OpenLayers.Util.applyDefaults($selsettings, OpenLayers.Feature.Vector.style['select']);
  
var styleMap = new OpenLayers.StyleMap({'default' : defaultStyle, 'select' : selectStyle});
var reportlayer = new OpenLayers.Layer.Vector('Report output', {styleMap: styleMap});  
features = [];\n";
        foreach ($records as $record)
          report_helper::$javascript.= "addDistPoint(features, ".json_encode($record).", '".$wktCol."');\n";
        report_helper::$javascript.= "  
reportlayer.addFeatures(features);\n";
      } else {
        // doing WMS reporting via GeoServer
        $replacements = array();
        foreach(array_keys($currentParamValues) as $key)
          $replacements[] = "#$key#";
        $options['cqlTemplate'] = str_replace($replacements, $currentParamValues, $options['cqlTemplate']);
        $options['cqlTemplate'] = str_replace("'", "\'", $options['cqlTemplate']);
        $style = empty($options['geoserverLayerStyle']) ? '' : ", STYLES: '".$options['geoserverLayerStyle']."'";
        if (isset($options['proxy'])) {
          $proxyResponse = self::http_post($options['proxy'], array(
            'cql_Filter' => urlencode($options['cqlTemplate'])
          ));
          $filter = 'CACHE_ID: "'.$proxyResponse['output'].'"';
          $proxy = $options['proxy'];
        } else {
          $filter = "cql_Filter: '".$options['cqlTemplate']."'";
          $proxy = '';
        }
        $layerUrl = $proxy . self::$geoserver_url . 'wms';
        map_helper::$javascript .= "  reportlayer = new OpenLayers.Layer.WMS('Report output',
      '$layerUrl', { layers: '".$options['geoserverLayer']."', transparent: true,
          $filter, $style},
      {singleTile: true, isBaseLayer: false, sphericalMercator: true});\n";
      }
      report_helper::$javascript.= "
mapSettingsHooks.push(function(opts) {
  opts.reportGroup = '".$options['reportGroup']."';
  opts.layers.push(reportlayer);\n";
      if ($options['clickable'])
        report_helper::$javascript .= "  opts.clickableLayers.push(reportlayer);\n";
      report_helper::$javascript .= "  opts.clickableLayersOutputMode='".$options['clickableLayersOutputMode']."';\n";
      if ($options['clickableLayersOutputDiv'])
        report_helper::$javascript .= "  opts.clickableLayersOutputDiv='".$options['clickableLayersOutputDiv']."';\n";
      report_helper::$javascript .= "});\n";
    }
  
    return $r;
  }
  
  /**
   * Method that retrieves the data from a report or a table/view, ready to display in a chart or grid.
   * Respects the filters and columns $_GET variables generated by a grid's filter form when JavaScript is disabled.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>mode</b><br/>
   * Defaults to report, which means report data is being loaded. Set to direct to load data directly from an entity's view.
   * </li>
   * <li><b>dataSource</b><br/>
   * Name of the report or entity being queried.
   * <li><b>mode</b><br/>
   * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
   * </li>
   * <li><b>readAuth</b><br/>
   * Read authentication tokens.
   * </li>
   * <li><b>filters</b><br/>
   * Array of key value pairs to include as a filter against the data.
   * </li>
   * <li><b>extraParams</b><br/>
   * Array of additional key value pairs to attach to the request.
   * </li>
   * <li><b>linkOnly</b><br/>
   * Pass true to return a link to the report data request rather than the data itself. Default false.
   * </li>
   * </ul>
   
   * @param string $extra Any additional parameters to append to the request URL, for example orderby, limit or offset.
   * @return object If linkOnly is set in the options, returns the link string, otherwise returns the response as an array. 
   */
  public static function get_report_data($options, $extra='') {
    $query = array();
    if (!isset($options['mode'])) $options['mode']='report';
    if ($options['mode']=='report') {
      $serviceCall = 'report/requestReport?report='.$options['dataSource'].'.xml&reportSource=local&';
    } elseif ($options['mode']=='direct') {
      $serviceCall = 'data/'.$options['dataSource'].'?';
      if (isset($_GET['filters']) && isset($_GET['columns'])) {
        $filters=explode(',', $_GET['filters']);
        $columns=explode(',', $_GET['columns']);
        $assoc = array_combine($columns, $filters);
        $query['like'] = $assoc;
      }
    } else {
      throw new Exception('Invalid mode parameter for call to report_grid');
    }
    if (!empty($extra) && substr($extra, 0, 1)!=='&')
      $extra = '&'.$extra;
    $request = parent::$base_url.'index.php/services/'.
        $serviceCall.
        'mode=json&nonce='.$options['readAuth']['nonce'].
        '&auth_token='.$options['readAuth']['auth_token'].
        $extra;
    if (isset($options['filters'])) {
      foreach ($options['filters'] as $key=>$value) {
        if (is_array($value)) {
          if (!isset($query['in'])) $query['in'] = array();
          $query['in'][$key] = $value;
        } else {
          if (!isset($query['where'])) $query['where'] = array();
          $query['where'][$key] = $value;
        }
      }
    }
    if (!empty($query))
      $request .= "&query=".urlencode(json_encode($query));
    if (isset($options['extraParams'])) { 
      foreach ($options['extraParams'] as $key=>$value)
        // Must urlencode the keys and parameters, as things like spaces cause curl to hang.
        $request .= '&'.urlencode($key).'='.urlencode($value);
    }
    if (isset($options['linkOnly']) && $options['linkOnly']) {
      return $request;
    }
    else {
      $response = self::http_post($request, null);
      $decoded = json_decode($response['output'], true);
      if (empty($decoded)) 
        return array('error'=>print_r($response, true));
      else
        return $decoded;
    }
  }
  
  /**
   * Generates the extra URL parameters that need to be appended to a report service call request, in order to 
   * include the sorting and pagination parameters.
   * @param array @options Options array sent to the report.
   * @param array @sortAndPageUrlParams Paging and sorting info returned from a call to get_report_grid_sort_page_url_params.
   * @return string Snippet of URL containing the required URL parameters.
   */
  private static function get_report_sorting_paging_params($options, $sortAndPageUrlParams) {
    // Work out the names and current values of the params we expect in the report request URL for sort and pagination    
    $page = ($sortAndPageUrlParams['page']['value'] ? $sortAndPageUrlParams['page']['value'] : 0);
    // set the limit to one higher than we need, so the extra row can trigger the pagination next link
    $extraParams = '&limit='.($options['itemsPerPage']+1).'&wantColumns=1&wantParameters=1&wantCount=1';
    $extraParams .= '&offset=' . $page * $options['itemsPerPage'];

    // Add in the sort parameters
    foreach ($sortAndPageUrlParams as $param => $content) {
      if ($content['value']!=null) {
        if ($param != 'page')
          $extraParams .= '&' . $param .'='. $content['value'];
      }
    }
    return $extraParams;
  }

  /**
   * Works out the orderby, sortdir and page URL param names for this report grid, and also gets their
   * current values.
   * @param $options Control options array
   * @return array Contains the orderby, sortdir and page params, as an assoc array. Each array value
   * is an array containing name & value.
   */
  private static function get_report_grid_sort_page_url_params($options) {
    $orderbyKey = 'orderby' . (isset($options['id']) ? '-'.$options['id'] : '');
    $sortdirKey = 'sortdir' . (isset($options['id']) ? '-'.$options['id'] : '');
    $pageKey = 'page' . (isset($options['id']) ? '-'.$options['id'] : '');
    return array(
      'orderby' => array(
        'name' => $orderbyKey,
        'value' => isset($_GET[$orderbyKey]) ? $_GET[$orderbyKey] : null
      ),
      'sortdir' => array(
        'name' => $sortdirKey,
        'value' => isset($_GET[$sortdirKey]) ? $_GET[$sortdirKey] : null
      ),
      'page' => array(
        'name' => $pageKey,
        'value' => isset($_GET[$pageKey]) ? $_GET[$pageKey] : null
      )
    );
  }


  /**
   * Build a url suitable for inclusion in the links for the report grid column headings or pagination
   * bar. This effectively re-builds the current page's URL, but drops the query string parameters that
   * indicate the sort order and page number.
   * @param array $sortAndPageParams List of the sorting and pagination parameters which should be excluded.
   * @return unknown_type
   */
  private static function report_grid_get_reload_url($sortAndPageUrlParams) {
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = self::get_reload_link_parts();
    // Build a basic URL path back to this page, but with the page, sortdir and orderby removed
    $pageUrl = $reloadUrl['path'].'?';
    // find the names of the params we must not include
    $excludedParams = array();
    foreach($sortAndPageUrlParams as $param) {
      $excludedParams[] = $param['name'];
    }
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!in_array($key, $excludedParams))
        $pageUrl .= "$key=$value&";
    }
    return $pageUrl;
  }

  /**
   * Private function that builds a parameters form according to a parameterRequest recieved
   * when calling a report. If the autoParamsForm is false then an empty string is returned.
   * @param $response
   * @param $options
   * @param $params
   * @return string HTML for the form.
   */
  private static function get_report_grid_parameters_form($response, $options, $params) {
    if ($options['autoParamsForm'] || $options['paramsOnly']) {
      $r = '';
      // The form must use POST, because polygon parameters can be too large for GET.
      if ($options['completeParamsForm']==true) {
        $cls = $options['paramsInMapToolbar'] ? 'no-border' : '';
        $r .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post" id="'.$options['reportGroup'].'-params">'."\n<fieldset class=\"$cls\">";
        if (!$options['paramsInMapToolbar']) 
          // don't use the fieldset legend in toolbar mode
          $r .= '<legend>'.lang::get('Report Parameters').'</legend>';
      }
      $reloadUrl = self::get_reload_link_parts();
      // Output any other get parameters from our URL as hidden fields
      foreach ($reloadUrl['params'] as $key => $value) {
        // since any param will be from a URL it could be encoded. So we don't want to double encode if they repeatedly
        // run the report.
        $value = urldecode($value);
        // ignore any parameters that are going to be in the grid parameters form
        if (substr($key,0,6)!='param-')
          $r .= "<input type=\"hidden\" value=\"$value\" name=\"$key\" />\n";
      }
      if ($options['paramsInMapToolbar'])
        $options['helpText']=false;
      $r .= self::build_params_form(array_merge(array('form'=>$response['parameterRequest'], 'fieldPamePrefix'=>'param', 'defaults'=>$params), $options));
      if ($options['completeParamsForm']==true) {
        $suffix = '<input type="submit" value="'.lang::get($options['paramsFormButtonCaption']).'" id="run-report"/>'.
            '</fieldset></form>';
      } else 
        $suffix = '';
      // look for idlist parameters with an alias. If we find one, we need to pass this information to any map panel, because the 
      // alias provides the name of the key field in the features loaded onto the map. E.g. if you click on the feature, the alias
      // allows the map to find the primary key value and therefore filter the report to show the matching feature.
      foreach($response['parameterRequest'] as $key=>$param) {
        if (!empty($param['alias']) && $param['datatype']=='idlist')
          $alias = $param['alias'];
          data_entry_helper::$javascript .= "
if (typeof(mapSettingsHooks)!=='undefined') {
  mapSettingsHooks.push(function(opts) {
    opts.featureIdField='$alias';
  });
}\n";
      }
      if ($options['paramsInMapToolbar']) {
        $toolbarControls = str_replace(array('<br/>', "\n"), '', $r);
        data_entry_helper::$javascript .= "$.fn.indiciaMapPanel.defaults.toolbarPrefix+='$toolbarControls';\n";
        data_entry_helper::$javascript .= "$.fn.indiciaMapPanel.defaults.toolbarSuffix+='$suffix';\n";
        return '';
      } else      
        return "$r$suffix\n";
    } else {
      return '';
    }
  }

  /**
   * Add any columns that don't have a column definition to the end of the columns list, by first
   * building an array of the column names of the columns we did specify, then adding any missing fields
   * from the results to the end of the options['columns'] array.
   * @param $response
   * @param $options
   * @return unknown_type
   */
  private static function report_grid_get_columns($response, &$options) {
    if ($options['includeAllColumns'] && isset($response['columns'])) {
      $specifiedCols = array();
      $actionCols = array();
      $idx=0;
      foreach ($options['columns'] as $col) {
        if (isset($col['fieldname'])) $specifiedCols[] = $col['fieldname'];
        // action columns need to be removed and added to the end
        if (isset($col['actions'])) {
          // remove the action col from its current location, store it so we can add it to the end
          unset($options['columns'][$idx]);
          $actionCols[] = $col;
        }
        $idx++;
      }
      foreach ($response['columns'] as $resultField => $value) {
        if (!in_array($resultField, $specifiedCols)) {
          $options['columns'][] = array_merge(
            $value,
            array('fieldname'=>$resultField)
          );
        }
      }
      // add the actions columns back in at the end
      $options['columns'] = array_merge($options['columns'], $actionCols);
    }
  }

  /**
   * Retrieve the HTML for the actions in a grid row.
   * @param array $actions
   * @param array $row
   * @param string $dirtyUrlParam Set to the name of a URL param used to pass the path to this page. E.g. in Drupal
   * with clean urls disabled, this is set to q. Otherwise leave empty.
   */   
  private static function get_report_grid_actions($actions, $row, $pathParam='') {
    $jsReplacements = array();
    foreach ($row as $key=>$value) {
      $jsReplacements[$key]=$value;
      $jsReplacements["$key-escape-quote"]=str_replace("'", "\'", $value);
      $jsReplacements["$key-escape-dblquote"]=str_replace('"', '\"', $value);
    }
    $links = array();
    $currentUrl = self::get_reload_link_parts(); // needed for params
    if (!empty($pathParam)) 
      unset($currentUrl['params'][$pathParam]);
    foreach ($actions as $action) {
      // skip any actions which are marked as invisible for this row.
      if (isset($action['visibility_field']) && $row[$action['visibility_field']]==='f')
        continue;
      if (isset($action['url'])) {
        if (!empty($pathParam) && strpos($action['url'], "?$pathParam=")===false)
          $row['rootFolder'] .="?$pathParam=";
        $actionUrl = self::mergeParamsIntoTemplate($row, $action['url'], true);
        // include any $_GET parameters to reload the same page, except the parameters that are specified by the action
        if (isset($action['urlParams']))
          $urlParams = array_merge($currentUrl['params'], $action['urlParams']);
        else if (substr($action['url'], 0, 1)=='#')
          // if linking to an internal bookmark, no need to attach the url parameters
          $urlParams = array();
        else
          $urlParams = array_merge($currentUrl['params']);
        if (count($urlParams)>0) {
          $actionUrl.= (strpos($actionUrl, '?')===false) ? '?' : '&';
        }
        $href=' href="'.$actionUrl.self::mergeParamsIntoTemplate($row, self::array_to_query_string($urlParams), true).'"';
      } else {
        $href='';
      }
      if (isset($action['javascript'])) {
        $onclick=' onclick="'.self::mergeParamsIntoTemplate($jsReplacements,$action['javascript'],true).'"';
      } else {
        $onclick = '';
      }
      $class=(isset($action['class'])) ? ' '.$action['class'] : '';
      $links[] = "<a class=\"action-button$class\"$href$onclick>".$action['caption'].'</a>';
    }
    return implode('<br/>', $links);
  }

  private static function get_report_grid_options($options) {
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'report-output', // this needs to be set explicitly when more than one report on a page
      'itemsPerPage' => 20,
      'class' => 'ui-widget ui-widget-content report-grid',
      'thClass' => 'ui-widget-header',
      'altRowClass' => 'odd',
      'columns' => array(),
      'galleryColCount' => 1,
      'headers' => true,
      'includeAllColumns' => true,
      'autoParamsForm' => true,
      'paramsOnly' => false,
      'extraParams' => array(),
      'completeParamsForm' => true,
      'callback' => '',
      'paramsFormButtonCaption' => 'Run Report',
      'view' => 'list'
    ), $options);
    if ($options['galleryColCount']>1) $options['class'] .= ' gallery';
    // use the current report as the params form by default
    if (!isset($options['reportGroup'])) $options['reportGroup'] = $options['id'];
    if (!isset($options['fieldNamePrefix'])) $options['fieldNamePrefix'] = $options['reportGroup'];
    return $options;
  }


  /**
   * Returns the query string describing additional sort query params for a
   * data request to populate the report grid.
   */
  private static function get_report_grid_data_request_sort_params($options, $paramKeyNames) {
    $r = '';
    if (isset($_GET[$paramKeyNames['orderby']]))
      $orderby = $_GET[$paramKeyNames['orderby']];
    else
      $orderby = null;
    if ($orderby)
      $r .= "&orderby=$orderby";
    if (isset($_GET[$paramKeyNames['sortdir']]))
      $sortdir = $_GET[$paramKeyNames['sortdir']];
    else
      $sortdir = 'ASC';
    if ($sortdir && $orderby)
      $r .= "&sortdir=$sortdir";
    return $r;
  }

  /**
   * Returns the parameters for the report grid data services call which are embedded in the query string or 
   * default param value data.
   * @param $options
   * @return Array Associative array of parameters.
   */
  private static function get_report_grid_current_param_values($options) {
    $params = array();
    // get defaults first
    if (isset($options['paramDefaults'])) {
      foreach ($options['paramDefaults'] as $key=>$value) {
        // trim data to ensure blank lines are not handled.
        $key = trim($key);
        $value = trim($value);
        // We have found a parameter, so put it in the request to the report service
        if (!empty($key))
          $params[$key]=$value;
      }
    }
    // Are there any parameters embedded in the URL, e.g. after submitting the params form?
    $paramKey = (isset($options['reportGroup']) ? $options['reportGroup'] : '').'-';
    foreach ($_POST as $key=>$value) {
      if (substr($key, 0, strlen($paramKey))==$paramKey) {
        // We have found a parameter, so put it in the request to the report service
        $param = substr($key, strlen($paramKey));
        $params[$param]=$value;
      }
    }
    return $params;
  }

}

?>
