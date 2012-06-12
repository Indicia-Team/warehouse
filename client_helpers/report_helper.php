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
      if (isset($output['error']))
        return $output['error'];
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
    return str_replace(array('{link}','{caption}'), array($link, lang::get($options['caption'])), $indicia_templates['report_download_link']);
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
  * <li><b>rememberParamsReportGroup</b><br/>
  * Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded.
  * The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter
  * settings will be shared across the reports even if they are on different pages of the site. For example if several reports on the
  * site have an ownData boolean parameter which filters the data to the user's own data, this can be set so that the reports all
  * share the setting. This functionality requires cookies to be enabled on the browser.</li>
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
  * Optional. Specify a list of the columns you want to output if you need more control over the columns, for example to
  * specify the order, change the caption or build a column with a configurable data display using a template.
  * Pass an array to this option, with each array entry containing an associative array that specifies the
  * information about the column represented by the position within the array. The associative array for the column can contain
  * the following keys:
  *  - fieldname: name of the field to output in this column. Does not need to be specified when using the template option.
  *  - display: caption of the column, which defaults to the fieldname if not specified
  *  - actions: list of action buttons to add to each grid row. Each button is defined by a sub-array containing
  *      values for caption, visibility_field, url, urlParams, class and javascript. The visibility field is an optional
  *      name of a field in the data which contains true or false to define the visibility of this action. The javascript, url
  *      and urlParams values can all use the field names from the report in braces as substitutions, for example {id} is replaced
  *      by the value of the field called id in the respective row. In addition, the url can use {currentUrl} to represent the
  *      current page's URL, {rootFolder} to represent the folder on the server that the current PHP page is running from, and
  *      {imageFolder} for the image upload folder. Because the javascript may pass the field values as parameters to functions,
  *      there are escaped versions of each of the replacements available for the javascript action type. Add -escape-quote or
  *      -escape-dblquote to the fieldname for quote escaping, or -escape-htmlquote/-escape-htmldblquote for escaping quotes in HTML
  *      attributes. For example this would be valid in the action javascript: foo("{bar-escape-dblquote}");
  *      even if the field value contains a double quote which would have broken the syntax.
  *  - visible: true or false, defaults to true
  *  - template: allows you to create columns that contain dynamic content using a template, rather than just the output
  *      of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values.
  *      Add -escape-quote or -escape-dblquote to the fieldname for quote escaping, or -escape-htmlquote/-escape-htmldblquote
  *      for escaping quotes in HTML attributes. Note that template columns cannot be sorted by clicking grid headers.
  *     An example array for the columns option is:
  *     array(
  *       array('fieldname' => 'survey', 'display' => 'Survey Title'),
  *       array('display' => 'action', 'template' => '<a href="www.mysite.com\survey\{id}\edit">Edit</a>'),
  *       array('display' => 'Actions', 'actions' => array(
  *         array('caption' => 'edit', 'url'=>'{currentUrl}', 'urlParams'=>array('survey_id'=>'{id}'))
  *       ))
  *     )
  *  - json: set to true if the column contains a json string object with properties that can be decoded to give strings that
  *      can be used as replacements in a template. For example, a column is returned from a report with fieldname='data', json=true
  *      and containing a data value '{"species":"Arnica montana","date":"14/04/2004"}'. A second column with fieldname='comment'
  *      contains the value 'Growing on a mountain pasture'. A third column is setup in the report with template set to
  *      '<div>{species} was recorded on {date}.<br/>{comment}</div>'. The json data and the second column's raw value are all
  *      available in the template replacements, so the output is set to
  *      '<div>Arnice montana was recorded on 14/04/2004.<br/>Growing on a mountain pasture</div>'
  *      template
  * </li>
  * <li><b>rowId</b>
  * Optional. Names the field in the data that contains the unique identifier for each row. If set, then the &lt;tr&gt; elements have their id attributes
  * set to row + this field value, e.g. row37. This is used to allow synchronisation of the selected table rows with a report map output showing the same data.</li>
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
  * <li><b>fieldsetClass</b><br/>
  * Optional. Class name(s) to add to fieldsets generated by the auto parameters form.</li>
  * <li><b>filters</b><br/>
  * Array of key value pairs to include as a filter against the data.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request. This should include fixed values which cannot be changed by the
  * user and therefore are not needed in the parameters form.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values. Default values appear in the parameter form and can be overridden.</li>
  * <li><b>paramsOnly</b>
  * Defaults to false. If true, then this method will only return the parameters form, not the grid content. autoParamsForm
  * is ignored if this flag is set.</li>
  * <li><b>ignoreParams</b>
  * Array that can be set to a list of the report parameter names that should not be included in the parameters form. Useful
  * when using paramsOnly=true to display a parameters entry form, but the system has default values for some of the parameters
  * which the user does not need to be asked about. Can also be used to provide parameter values that can be overridden only via
  * a URL parameter.</li>
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
  * <li><b>sharing</b>
  * Assuming the report has been written to take account of website sharing agreements, set this to define the task
  * you are performing with the report and therefore the type of sharing to allow. Options are reporting (default),
  * verification, moderation, peer_review, data_flow, website (this website only) or me (my data only).</li>
  * <li><b>UserId</b>
  * If sharing=me, then this must contain the Indicia user ID of the user to return data for.
  * </li>
  * <li><b>sendOutputToMap</b>
  * Default false. If set to true, then the records visible on the current page are drawn onto a map. This is different to the
  * report_map method when linked to a report_grid, which loads its own report data for display on a map, just using the same input parameters
  * as other reports. In this case the report_grid's report data is used to draw the features on the map, so only 1 report request is made.
  * </li>
  * <li><b>rowClass</b>
  * A CSS class to add to each row in the grid. Can include field value replacements in braces, e.g. {certainty} to construct classes from
  * field values, e.g. to colour rows in the grid according to the data.
  * </li>
  * </ul>
  * @todo Allow additional params to filter by table column or report parameters
  * @todo Display a filter form for direct mode
  * @todo For report mode, provide an AJAX/PHP button that can load the report from parameters
  * in a form on the page.
  */
  public static function report_grid($options) {
    self::add_resource('fancybox');
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    $options = self::get_report_grid_options($options);
    $extras = self::get_report_sorting_paging_params($options, $sortAndPageUrlParams);
    self::request_report($response, $options, $currentParamValues, true, $extras);
    if (isset($response['error'])) return $response['error'];
    $r = self::params_form_if_required($response, $options, $currentParamValues);
    // return the params form, if that is all that is being requested, or the parameters are not complete.
    if ($options['paramsOnly'] || !isset($response['records'])) return $r;
    $records = $response['records'];
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
      // Flag if we know any column data types and therefore can display a filter row
      $wantFilterRow=false;
      $filterRow='';
      // Output the headers. Repeat if galleryColCount>1;
      for ($i=0; $i<$options['galleryColCount']; $i++) {
        foreach ($options['columns'] as $field) {
          if (isset($field['visible']) && ($field['visible']==='false' || $field['visible']===false))
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
              $captionLink = "<a href=\"$sortLink\" rel=\"nofollow\" title=\"Sort by $caption\">$caption</a>";
            // set a style for the sort order
            $orderStyle = ($sortAndPageUrlParams['orderby']['value']==$field['orderby']) ? ' '.$sortdirval : '';
            $orderStyle .= ' sortable';
            $fieldId = ' id="' . $options['id'] . '-th-' . $field['orderby'] . '"';
          } else {
            $orderStyle = '';
            $fieldId = '';
            $captionLink=$caption;
          }
          $r .= "<th$fieldId class=\"$thClass$orderStyle\">$captionLink</th>\n";
          if (isset($field['datatype'])) {
            switch ($field['datatype']) {
              case 'text':
                $title=lang::get("$caption search. Use * as a wildcard.");
                break;
              case 'date':
                $title=lang::get("$caption search. Search for an exact date or use a vague<br/> date such as a year to select a range of dates.");
                break;
              default: $title=lang::get("$caption search. Enter an exact number or use > or < as the first character to<br/>filter for greater than or less than a value.");
            }
            $filterRow .= "<th><input title=\"$title\" type=\"text\" class=\"col-filter\" id=\"col-filter-".$field['fieldname']."\"/></th>";
            $wantFilterRow = true;
          } else
            $filterRow .= '<th></th>';
        }
      }
      $r .= "</tr>";
      if ($wantFilterRow)
        $r .= "<tr class=\"filter-row\">$filterRow</tr>\n";
      $r .= "</thead>\n";
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
    $altRowClass = '';
    $outputCount = 0;
    $imagePath = self::get_uploaded_image_folder();
    $relpath = self::relative_client_helper_path();
    $addFeaturesJs = '';
    $haveUpdates=false;
    $updateformID=0;
    if (count($records)>0) {
      $rowInProgress=false;
      $rowTitle = !empty($options['rowId']) ?
          ' title="'.lang::get('Click the row to highlight the record on the map. Double click to zoom in.').'"' : '';
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
          $classes=array();
          if ($altRowClass)
            $classes[]=$altRowClass;
          if (isset($options['rowClass']))
            $classes[]=self::mergeParamsIntoTemplate($row, $options['rowClass'], true, true);
          $classes=implode(' ',$classes);
          $class = empty($classes) ? '' : "class=\"$classes\" ";
          $r .= "<tr $class$rowId$rowTitle>";
          $rowInProgress=true;
        }
        // first decode any json data
        foreach ($options['columns'] as $field) {
          if (isset($field['json']) && $field['json'] && isset($row[$field['fieldname']]))
            $row = array_merge($row, json_decode($row[$field['fieldname']], true));
        }
        foreach ($options['columns'] as $field) {
          $classes=array();
          if (isset($options['sendOutputToMap']) && $options['sendOutputToMap'] && isset($field['mappable']) && $field['mappable']==='true') {
            $addFeaturesJs.= "  addDistPoint(features, ".json_encode($row).", '".$field['fieldname']."', {}".
                (empty($rowId) ? '' : ", '".$row[$options['rowId']]."'").");\n";
          }
          if (isset($field['visible']) && ($field['visible']==='false' || $field['visible']===false))
            continue; // skip this column as marked invisible
          if (isset($field['actions'])) {
            $value = self::get_report_grid_actions($field['actions'],$row, $pathParam);
            $classes[]='actions';
          } elseif (isset($field['template'])) {
            $value = self::mergeParamsIntoTemplate($row, $field['template'], true, true, true);
          } else if (isset($field['update']) &&(!isset($field['update']['permission']) || user_access($field['update']['permission']))){
          	// TODO include checks to ensure method etc are included in structure -
          	$updateformID++;
          	$value="<form id=\"updateform-".$updateformID."\" method=\"post\" action=\"".iform_ajaxproxy_url($node, $field['update']['method'])."\"><input type=\"hidden\" name=\"website_id\" value=\"".$field['update']['website_id']."\"><input type=\"hidden\" name=\"transaction_id\" value=\"updateform-".$updateformID."-field\"><input id=\"updateform-".$updateformID."-field\" name=\"".$field['update']['tablename'].":".$field['update']['fieldname']."\" class=\"update-input ".(isset($field['update']['class']) ? $field['update']['class'] : "")."\" value=\"".(isset($field['fieldname']) && isset($row[$field['fieldname']]) ? $row[$field['fieldname']] : '')."\">";
          	if(isset($field['update']['parameters'])){
              foreach($field['update']['parameters'] as $pkey=>$pvalue){
                $value.="<input type=\"hidden\" name=\"".$field['update']['tablename'].":".$pkey."\" value=\"".$pvalue."\">";
              }
            }
            $value.="</form>";
          	$value=self::mergeParamsIntoTemplate($row, $value, true);
          	$haveUpdates = true;
            self::$javascript .= "
jQuery('#updateform-".$updateformID."').ajaxForm({
    async: true,
    dataType:  'json',
    success:   function(data, status, form){
      if (checkErrors(data)) {
        var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
        $(selector).removeClass('input-saving');
        $(selector).removeClass('input-edited');
      }
    }
  });
";
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
        $altRowClass = empty($altRowClass) ? $options['altRowClass'] : '';
        $outputCount++;
      }
      if ($rowInProgress)
        $r .= '</tr>';
    }
    $r .= "</tbody></table>\n";
    // amend currentUrl path if we have drupal dirty URLs so javascript will work properly
    if ($pathParam==='q' && isset($currentUrl['params']['q']) && strpos($currentUrl['path'], '?')===false) {
      $currentUrl['path'] = $currentUrl['path'].'?q='.$currentUrl['params']['q'];
    }
    if($haveUpdates){
      self::$javascript .= "
function checkErrors(data) {
  if (typeof data.error!==\"undefined\") {
    if (typeof data.errors!==\"undefined\") {
      $.each(data.errors, function(idx, error) {
        alert(error);
      });
    } else {
      alert('An error occured when trying to save the data: '+data.error);
    }
    // data.transaction_id stores the last cell at the time of the post.
    var selector = '#'+data.transaction_id.replace(/:/g, '\\\\:');
    $(selector).focus();
    $(selector).select();
    return false;
  } else {
    return true;
  }
}
$('.update-input').focus(function(evt) {
  $(evt.target).addClass('input-selected');
}).change(function(evt) {
  $(evt.target).addClass('input-edited');
}).blur(function(evt) {
  var selector = '#'+evt.target.id.replace(/:/g, '\\:');
  currentCell = evt.target.id;
  $(selector).removeClass('input-selected');
  if ($(selector).hasClass('input-edited')) {
    $(selector).addClass('input-saving');
    // WARNING No validation currently applied...
    $(selector).parent().submit();
  }
});
";
    }
    self::addFeaturesLoadingJs($addFeaturesJs);
    // $r may be empty if a spatial report has put all its controls on the map toolbar, when using params form only mode.
    // In which case we don't need to output anything.
    if (!empty($r)) {
      // Output a div to keep the grid and pager together
      $r = "<div id=\"".$options['id']."\">$r</div>\n";
      // Now AJAXify the grid
      self::add_resource('reportgrid');
      $uniqueName = 'grid_' . preg_replace( "/[^a-z0-9]+/", "_", $options['id']);
      $group = preg_replace( "/[^a-zA-Z0-9]+/", "_", $options['reportGroup']);
      global $indicia_templates;
      if (!empty(parent::$warehouse_proxy))
        $warehouseUrl = parent::$warehouse_proxy;
      else
        $warehouseUrl = parent::$base_url;
      self::$javascript .= "
if (typeof indiciaData.reports==='undefined') { indiciaData.reports={}; }
if (typeof indiciaData.reports.$group==='undefined') { indiciaData.reports.$group={}; }
simple_tooltip('input.col-filter','tooltip');
indiciaData.reports.$group.$uniqueName = $('#".$options['id']."').reportgrid({
  id: '".$options['id']."',
  mode: '".$options['mode']."',
  dataSource: '".str_replace('\\','/',$options['dataSource'])."',
  view: '".$options['view']."',
  itemsPerPage: ".$options['itemsPerPage'].",
  auth_token: '".$options['readAuth']['auth_token']."',
  nonce: '".$options['readAuth']['nonce']."',
  callback: '".$options['callback']."',
  url: '".$warehouseUrl."',
  reportGroup: '".$options['reportGroup']."',
  autoParamsForm: '".$options['autoParamsForm']."',
  rootFolder: '".dirname($_SERVER['PHP_SELF'])."/',
  imageFolder: '".self::get_uploaded_image_folder()."',
  currentUrl: '".$currentUrl['path']."',
  rowId: '".(isset($options['rowId']) ? $options['rowId'] : '')."',
  galleryColCount: ".$options['galleryColCount'].",
  pagingTemplate: '".$indicia_templates['paging']."',
  pathParam: '".$pathParam."',
  sendOutputToMap: ".((isset($options['sendOutputToMap']) && $options['sendOutputToMap']) ? 'true' : 'false').",
  linkFeatures: ".(!empty($options['rowId']) ? 'true' : 'false').",
  msgRowLinkedToMapHint: '".lang::get('Click the row to highlight the record on the map. Double click to zoom in.')."',
  altRowClass: '".$options['altRowClass']."'";
      if (isset($options['sharing'])) {
        if (!isset($options['extraParams']))
          $options['extraParams']=array();
        $options['extraParams']['sharing']=$options['sharing'];
      }
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
        self::$javascript .= ",\n  columns: ".json_encode($options['columns']);
      self::$javascript .= "\n});\n";
    }
    if (isset($options['sendOutputToMap']) && $options['sendOutputToMap']) {
      self::$javascript.= "mapSettingsHooks.push(function(opts) {\n";
      self::$javascript.= "  opts.clickableLayers.push(indiciaData.reportlayer);\n";
      self::$javascript.= "  opts.clickableLayersOutputMode='reportHighlight';\n";
      self::$javascript .= "});\n";
    }
    return $r;
  }

  private static function request_report(&$response, &$options, &$currentParamValues, $wantCount, $extras='') {
    $extras .= '&wantColumns=1&wantParameters=1';
    if ($wantCount)
      $extras .= '&wantCount=1';
    // any extraParams are fixed values that don't need to be available in the params form, so they can be added to the
    // list of ignorable parameters.
    if (array_key_exists('extraParams', $options) && array_key_exists('ignoreParams', $options))
      $options['ignoreParams'] = array_merge($options['ignoreParams'], array_keys($options['extraParams']));
    elseif (array_key_exists('extraParams', $options))
      $options['ignoreParams'] = array_keys($options['extraParams']);    
    if (array_key_exists('ignoreParams', $options))
      $extras .= '&paramsFormExcludes='.json_encode($options['ignoreParams']);
    // specify the view variant to load, if loading from a view
    if ($options['mode']=='direct') $extras .= '&view='.$options['view'];
    $currentParamValues = self::get_report_grid_current_param_values($options);
    // if loading the parameters form only, we don't need to send the parameter values in the report request but instead
    // mark the request not to return records
    if ($options['paramsOnly'])
      $extras .= '&wantRecords=0';
    else
      $extras .= '&'.self::array_to_query_string($currentParamValues, true);
    $response = self::get_report_data($options, $extras);
  }

  private static function params_form_if_required($response, $options, $currentParamValues) {
    if (isset($response['parameterRequest'])) {
      // We put report param controls into their own divs, making layout easier. Unless going in the
      // map toolbar as they will then be inline.
      global $indicia_templates;
      $oldprefix = $indicia_templates['prefix'];
      $oldsuffix = $indicia_templates['suffix'];
      if (isset($options['paramPrefix']))
        $indicia_templates['prefix']=$options['paramPrefix'];
      if (isset($options['paramSuffix']))
        $indicia_templates['suffix']=$options['paramSuffix'];
      $r = self::get_report_grid_parameters_form($response, $options, $currentParamValues);
      $indicia_templates['prefix'] = $oldprefix;
      $indicia_templates['suffix'] = $oldsuffix;
      return $r;
    } elseif ($options['autoParamsForm'] && $options['mode']=='direct') {
      // loading records from a view (not a report), so we can put a simple filter parameter form at the top.
      return self::get_direct_mode_params_form($options);
    }

    return ''; // no form required
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
        $r .= "<a class=\"pag-prev pager-button\" rel=\"nofollow\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$prev\">".lang::get('previous')."</a> \n";
      } else
        $r .= "<span class=\"pag-prev ui-state-disabled pager-button\">".lang::get('previous')."</span> \n";
      // if the service call returned more records than we are displaying (because we asked for 1 more), then we can go forward
      if (count($response['records'])>$options['itemsPerPage']) {
        $next = $sortAndPageUrlParams['page']['value'] + 1;
        $r .= "<a class=\"pag-next pager-button\" rel=\"nofollow\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$next\">".lang::get('next')." &#187</a> \n";
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
      $replacements['prev'] = "<a class=\"pag-prev pager-button\" rel=\"\nofollow\" href=\"$pagLinkUrl=$prev\">".lang::get('previous')."</a> \n";
      $replacements['first'] = "<a class=\"pag-first pager-button\" rel=\"nofollow\" href=\"$pagLinkUrl=0\">".lang::get('first')."</a> \n";
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
        $pagelist .= "<a class=\"pag-page pager-button\" rel=\"nofollow\" href=\"$pagLinkUrl=".($i-1)."\" id=\"page-".$options['id']."-$i\">$i</a>\n";
    }
    $replacements['pagelist'] = $pagelist;
    // if not on the last page, display a next link
    if ($page<$response['count']/$options['itemsPerPage']) {
      $next = $sortAndPageUrlParams['page']['value'] + 1;
      $replacements['next'] = "<a class=\"pag-next pager-button\" rel=\"nofollow\" href=\"$pagLinkUrl=$next\">".lang::get('next')."</a>\n";
      $replacements['last'] = "<a class=\"pag-last pager-button\" rel=\"nofollow\" href=\"$pagLinkUrl=".round($response['count']/$options['itemsPerPage']-1)."\">".lang::get('last')."</a>\n";
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
  * <li><b>rememberParamsReportGroup</b><br/>
  * Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded.
  * The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter
  * settings will be shared across the reports even if they are on different pages of the site. For example if several reports on the
  * site have an ownData boolean parameter which filters the data to the user's own data, this can be set so that the reports all
  * share the setting. This functionality requires cookies to be enabled on the browser.</li>
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
  * <li><b>sharing</b>
  * Assuming the report has been written to take account of website sharing agreements, set this to define the task
  * you are performing with the report and therefore the type of sharing to allow. Options are reporting (default),
  * verification, moderation, peer_review, data_flow, website (this website only) or me (my data only).</li>
  * <li><b>UserId</b>
  * If sharing=me, then this must contain the Indicia user ID of the user to return data for.
  * </li>
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

  /**
   * When loading records from a view, put a simple filter parameters form at the top as the view does not specify any
   * parameters.
   */
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
  * <li><b>rememberParamsReportGroup</b><br/>
  * Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded.
  * The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter
  * settings will be shared across the reports even if they are on different pages of the site. For example if several reports on the
  * site have an ownData boolean parameter which filters the data to the user's own data, this can be set so that the reports all
  * share the setting. This functionality requires cookies to be enabled on the browser.</li>
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
  * <li><b>sharing</b>
  * Assuming the report has been written to take account of website sharing agreements, set this to define the task
  * you are performing with the report and therefore the type of sharing to allow. Options are reporting (default),
  * verification, moderation, peer_review, data_flow, website (this website only) or me (my data only).</li>
  * <li><b>UserId</b>
  * If sharing=me, then this must contain the Indicia user ID of the user to return data for.
  * </li>
  * </ul>
  */
  public static function freeform_report($options) {
    $options = self::get_report_grid_options($options);
    self::request_report($response, $options, $currentParamValues, false);
    if (isset($response['error'])) return $response['error'];
    $r = self::params_form_if_required($response, $options, $currentParamValues);
    // return the params form, if that is all that is being requested, or the parameters are not complete.
    if ($options['paramsOnly'] || !isset($response['records'])) return $r;
    $records = $response['records'];

    $options = array_merge(array(
      'header' => '',
      'footer' => '',
      'bands' => array(),
      'class' => 'banded-report'
    ), $options);

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
  * <li><b>rememberParamsReportGroup</b><br/>
  * Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded.
  * The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter
  * settings will be shared across the reports even if they are on different pages of the site. For example if several reports on the
  * site have an ownData boolean parameter which filters the data to the user's own data, this can be set so that the reports all
  * share the setting. This functionality requires cookies to be enabled on the browser.</li>
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
  * <li>clickableLayersOutputColumns<br/>
  * An associated array of column field names with column titles as the values which defines the columns that are output when clicking on a data point.
  * If ommitted, then all available columns are output using their original field names.</li>
  * <li>displaySymbol</li>
  * Symbol to display instead of the actual polygon. The symbol is displayed at the centre of the polygon.
  * If not set then defaults to output the original polygon. Allowed values are circle,
  * square, star, x, cross, triangle.</li>
  * <li>valueOutput
  * Allows definition of how a data value in the report output is used to change the output of each symbol.
  * This allows symbol size, colour and/or opacity to be used to provide an indication of data values.
  * Provide an array of entries. The key of the entries should match the style parameter you want to
  * control which should be one of fillOpacity, fillColor, strokeOpacity, strokeWidth or strokeColor. If using
  * displaySymbol to render symbols rather than polygons then pointRadius (the symbol size) and rotation are also
  * available. If the report defines labels (using the feature_style attribute of a column to define a
  * column that outputs labels), then fontSize, fontColor and fontOpacity are also available.
  * Each array entry is a sub-array with associative array values set for the following:
  *   "from" is the start value of the range of output values (e.g. the minimum opacity or first colour in a range).
  *   "to" is the end value of the range of output values (e.g. the maximum opacity or last colour in a range).
  *   "valueField" is the name of the numeric field in the report output to be used to control display.
  *   "minValue" is the data value that equates to the output value specified by "from". This can be a
  *     fieldname if wrapped in braces.
  *   "maxValue" is the data value that equates to the output value specified by "from". This can be a
  *     fieldname if wrapped in braces.
  * The following example maps a field called value (with minvalue and maxvalue also output by the report)
  * to a range of colours from blue to red.
  * array(
  *   'fillColor'=>array(
  *     'from'=>'#00FF00',
  *     'to' => '#ff0000',
  *     'valueField' => 'value',
  *     'minValue'=> '{minvalue}',
  *     'maxValue'=> '{maxvalue}'
  *   )
  * )
  * </li>
  * <li><b>sharing</b>
  * Assuming the report has been written to take account of website sharing agreements, set this to define the task
  * you are performing with the report and therefore the type of sharing to allow. Options are reporting (default),
  * verification, moderation, peer_review, data_flow, website (this website only) or me (my data only).</li>
  * <li><b>UserId</b>
  * If sharing=me, then this must contain the Indicia user ID of the user to return data for.
  * </li>
  * <li><b>rowId</b>
  * Optional. Set this to the name of a field in the report to define which field is being used to define the feature ID created on the map
  * layer. For example this can be used in conjunction with rowId on a report grid to allow a report's rows to be linked to the associated
  * features.
  * </li>
  * </ul>
  */
  public static function report_map($options) {
    $options = array_merge(array(
      'clickable' => true,
      'clickableLayersOutputMode' => 'popup',
      'clickableLayersOutputDiv' => '',
      'displaySymbol'=>'vector'
    ), $options);
    $options = self::get_report_grid_options($options);
    if (empty($options['geoserverLayer'])) {
      self::request_report($response, $options, $currentParamValues, false, '');
      if (isset($response['error'])) return $response['error'];
      $r = self::params_form_if_required($response, $options, $currentParamValues);
      // return the params form, if that is all that is being requested, or the parameters are not complete.
      if ($options['paramsOnly'] || !isset($response['records'])) return $r;
      $records = $response['records'];
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
            $settings[$def['feature_style']] = '${'.$col.'}';
          }
        }
        // default features are color red by default
        $defsettings = array_merge(array(
          'fillColor'=> '#ff0000',
          'strokeColor'=> '#ff0000',
          'strokeWidth'=>1,
          'fillOpacity'=>0.5,
          'pointRadius'=>4,
          'graphicZIndex'=>0
        ), $settings);
        if ($options['displaySymbol']!=='vector')
          $defsettings['graphicName']=$options['displaySymbol'];
        if (isset($options['valueOutput'])) {
          $styleFns = array();
          foreach($options['valueOutput'] as $type => $outputdef) {
            $value = $outputdef['valueField'];
            if (preg_match('/{(?P<name>.+)}/', $outputdef['minValue'], $matches))
              $minvalue = 'feature.data.'.$matches['name'];
            else
              $minvalue = $outputdef['minValue'];
            if (preg_match('/{(?P<name>.+)}/', $outputdef['maxValue'], $matches))
              $maxvalue = 'feature.data.'.$matches['name'];
            else
              $maxvalue = $outputdef['maxValue'];
            $from = $outputdef['from'];
            $to = $outputdef['to'];
            if (substr($type, -5)==='Color')
              $styleFns[] = "get$type: function(feature) { \n".
                  "var from_r, from_g, from_b, to_r, to_g, to_b, ratio = (feature.data.$value - $minvalue) / ($maxvalue - $minvalue); \n".
                  "from_r = parseInt('$from'.substring(1,3),16);\n".
                  "from_g = parseInt('$from'.substring(3,5),16);\n".
                  "from_b = parseInt('$from'.substring(5,7),16);\n".
                  "to_r = parseInt('$to'.substring(1,3),16);\n".
                  "to_g = parseInt('$to'.substring(3,5),16);\n".
                  "to_b = parseInt('$to'.substring(5,7),16);\n".

                  "return 'rgb('+(from_r + (to_r-from_r)*ratio) + ', '+".
                     "(from_g + (to_g-from_g)*ratio) + ', '+".
                     "(from_b + (to_b-from_b)*ratio) + ')'; \n".
                  '}';
            else
              $styleFns[] = "get$type: function(feature) { \n".
                  "var ratio = (feature.data.$value - $minvalue) / ($maxvalue - $minvalue); \n".
                  "return $from + ($to-$from)*ratio; \n".
                  '}';
            $defsettings[$type]="\${get$type}";
          }
          $styleFns = implode(",\n", $styleFns);
        }
        // selected features are color blue by default
        $selsettings = array_merge($defsettings, array(
          'fillColor'=> '#0000ff',
          'strokeColor'=> '#0000ff',
          'graphicZIndex'=>1
        ));
        // convert these styles into a JSON definition ready to feed into JS.
        $defsettings = json_encode($defsettings);
        $selsettings = json_encode($selsettings);
        $addFeaturesJs = "";
        $opts = json_encode(array('type'=>$options['displaySymbol']));
        $rowId = isset($options['rowId']) ? ' id="row'.$row[$options['rowId']].'"' : '';
        foreach ($records as $record) {
          $addFeaturesJs.= "addDistPoint(features, ".json_encode($record).", '$wktCol', $opts".(empty($rowId) ? '' : ", '".$record[$options['rowId']]."'").");\n";
        }
        if (!empty($styleFns)) {
          $styleFns = ", {context: {
  $styleFns
}}";
        }
        self::addFeaturesLoadingJs($addFeaturesJs, $defsettings, $selsettings, $styleFns);
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
        report_helper::$javascript .= "  indiciaData.reportlayer = new OpenLayers.Layer.WMS('Report output',
      '$layerUrl', { layers: '".$options['geoserverLayer']."', transparent: true,
          $filter, $style},
      {singleTile: true, isBaseLayer: false, sphericalMercator: true});\n";
      }

      report_helper::$javascript.= "
mapSettingsHooks.push(function(opts) {
  opts.reportGroup = '".$options['reportGroup']."';
  if (typeof indiciaData.reportlayer!=='undefined') {
    opts.layers.push(indiciaData.reportlayer);\n";
      if ($options['clickable'])
        report_helper::$javascript .= "    opts.clickableLayers.push(indiciaData.reportlayer);\n";

      report_helper::$javascript .= "}\n  opts.clickableLayersOutputMode='".$options['clickableLayersOutputMode']."';\n";
      if ($options['clickableLayersOutputDiv'])
        report_helper::$javascript .= "  opts.clickableLayersOutputDiv='".$options['clickableLayersOutputDiv']."';\n";
      if (isset($options['clickableLayersOutputColumns']))
        report_helper::$javascript .= "  opts.clickableLayersOutputColumns=".json_encode($options['clickableLayersOutputColumns']).";\n";
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
   * <li><b>sharing</b>
   * Assuming the report has been written to take account of website sharing agreements, set this to define the task
   * you are performing with the report and therefore the type of sharing to allow. Options are reporting (default),
   * verification, moderation, peer_review, data_flow, website (this website only) or me (my data only).</li>
   * <li><b>UserId</b>
   * If sharing=me, then this must contain the Indicia user ID of the user to return data for.
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
    // Pass through the type of data sharing
    if (isset($options['sharing']))
      $request .= '&sharing='.$options['sharing'];
    if (isset($options['userId']))
      $request .= '&user_id='.$options['userId'];
    if (isset($options['linkOnly']) && $options['linkOnly']) {
      return $request;
    }
    else {
      $response = self::http_post($request, null);
      $decoded = json_decode($response['output'], true);
      if (!is_array($decoded))
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
    $extraParams = '&limit='.($options['itemsPerPage']+1);
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
        if (!empty($options['fieldsetClass']))
          $cls .= ' '.$options['fieldsetClass'];
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
      $r .= self::build_params_form(array_merge($options, array('form'=>$response['parameterRequest'], 'defaults'=>$params)));
      if ($options['completeParamsForm']==true) {
        $suffix = '<input type="submit" value="'.lang::get($options['paramsFormButtonCaption']).'" id="run-report"/>'.
            '</fieldset></form>';
      } else
        $suffix = '';
      // look for idlist parameters with an alias. If we find one, we need to pass this information to any map panel, because the
      // alias provides the name of the key field in the features loaded onto the map. E.g. if you click on the feature, the alias
      // allows the map to find the primary key value and therefore filter the report to show the matching feature.
      foreach($response['parameterRequest'] as $key=>$param)
        if (!empty($param['alias']) && $param['datatype']=='idlist') {
          $alias = $param['alias'];
          data_entry_helper::$javascript .= "
if (typeof(mapSettingsHooks)!=='undefined') {
  mapSettingsHooks.push(function(opts) {
    opts.featureIdField='$alias';
  });
}\n";
        }
      if ($options['paramsInMapToolbar']) {
        $toolbarControls = str_replace(array('<br/>', "\n", "'"), array('', '', "\'"), $r);
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
      if (!isset($options['columns']))
        $options['columns'] = array();
      foreach ($options['columns'] as &$col) {
        if (isset($col['fieldname'])) $specifiedCols[] = $col['fieldname'];
        // action columns need to be removed and added to the end
        if (isset($col['actions'])) {
          // remove the action col from its current location, store it so we can add it to the end
          unset($options['columns'][$idx]);
          $actionCols[] = $col;
        }
        $idx++;
        // datatype of column always defined in the server XML report file. Copy into the col def
        if (array_key_exists($col['fieldname'], $response['columns'])) {
          if (isset($response['columns'][$col['fieldname']]['datatype']))
            $col['datatype']=$response['columns'][$col['fieldname']]['datatype'];
        }
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
      $jsReplacements["$key-escape-htmlquote"]=str_replace("'", "&#39;", $value);
      $jsReplacements["$key-escape-htmldblquote"]=str_replace('"', '&quot;', $value);
    }
    $links = array();
    $currentUrl = self::get_reload_link_parts(); // needed for params
    if (!empty($pathParam)) {
      $pathParamValue = isset($currentUrl['params'][$pathParam]) ? $currentUrl['params'][$pathParam] : '';
      unset($currentUrl['params'][$pathParam]);
    }
    // Ensure the rootFolder replacement value supports Drupal's dirty URLs
    if (!empty($pathParam) && strpos($row['rootFolder'], "?$pathParam=")===false)
      $row['rootFolder'] .="?$pathParam=";
    foreach ($actions as $action) {
      // skip any actions which are marked as invisible for this row.
      if (isset($action['visibility_field']) && $row[$action['visibility_field']]==='f')
        continue;
      if (isset($action['url'])) {
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
      'paramsInMapToolbar' => false,
      'view' => 'list'
    ), $options);
    if ($options['galleryColCount']>1) $options['class'] .= ' gallery';
    // use the current report as the params form by default
    if (!isset($options['reportGroup'])) $options['reportGroup'] = $options['id'];
    if (!isset($options['fieldNamePrefix'])) $options['fieldNamePrefix'] = $options['reportGroup'];
    if (function_exists('hostsite_get_user_field')) {
      // If the host environment (e.g. Drupal module) can tell us which Indicia user is logged in, pass that
      // to the report call as it might be required for filters.
      if ($indiciaUserId = hostsite_get_user_field('indicia_user_id'))
        $options['extraParams']['user_id'] = $indiciaUserId;
    }
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
    // Are there any parameters embedded in the request data, e.g. after submitting the params form?
    $providedParams = $_REQUEST;
    if (isset($_COOKIE['providedParams']) && !empty($options['rememberParamsReportGroup'])) {
      $savedParams = json_decode($_COOKIE['providedParams'], true);
      if (!empty($savedParams[$options['rememberParamsReportGroup']]))
        $savedParams = $savedParams[$options['rememberParamsReportGroup']];
        // We shouldn't use the cookie values to overwrite any parameters that are hidden in the form as this is confusing.
        $ignoreParamNames = array();
        foreach($options['ignoreParams'] as $param)
          $ignoreParamNames[$options['reportGroup']."-$param"] = '';
        $savedParams = array_diff_key($savedParams, $ignoreParamNames);
        $providedParams = array_merge(
          $savedParams,
          $providedParams
        );
    }
    if (!empty($options['rememberParamsReportGroup'])) {
      // need to store the current set of saved params. These need to be merged into an array to go in
      // the single stored cookie with the array key being the rememberParamsReportGroup and the value being
      // an associative array of params.
      if (!isset($savedParams))
        $savedParams=array($options['rememberParamsReportGroup']=>array());
      elseif (!isset($savedParams[$options['rememberParamsReportGroup']]))
        $savedParams[$options['rememberParamsReportGroup']]=array();
      // merge the params with any others stored under the same rememberParamsReportGroup name
      $savedParams[$options['rememberParamsReportGroup']] = array_merge(
        $savedParams[$options['rememberParamsReportGroup']],
        $providedParams
      );
      setcookie('providedParams', json_encode($savedParams));
    }
    // Get the report group prefix required for each relevant parameter
    $paramKey = (isset($options['reportGroup']) ? $options['reportGroup'] : '').'-';
    foreach ($providedParams as $key=>$value) {
      if (substr($key, 0, strlen($paramKey))==$paramKey) {
        // We have found a parameter, so put it in the request to the report service
        $param = substr($key, strlen($paramKey));
        $params[$param]=$value;
      }
    }
    return $params;
  }

 /**
  * <p>Outputs a calendar grid that loads the content of a report.</p>
  * <p>The grid supports a pagination header (year by year). If you need 2 grids on one page, then you must
  * define a different id in the options for each grid.</p>
  * <p>The grid operation has NOT been AJAXified.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>year</b><br/>
  * The year to output the calendar for. Default is this year.</li>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid's container div. This is required if there is more than
  * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view. when used, any user_id must refer to the CMS user ID, not the Indicia
  * User.</li>
  * <li><b>view</b>
  * When loading from a view, specify list, gv or detail to determine which view variant is loaded. Default is list.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request. This should include fixed values which cannot be changed by the
  * user and therefore are not needed in the parameters form.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values. Default values appear in the parameter form and can be overridden.</li>
  * <li><b>includeWeekNumber</b>
  * Should a Week Number column be included in the grid? Defaults to false.</li>
  * <li><b>weekstart</b>
  * Defines the first day of the week. There are 2 options.<br/>'.
  *  weekday=<n> where <n> is a number between 1 (for Monday) and 7 (for Sunday). Default is 'weekday=7'
  *  date=MMM-DD where MMM-DD is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.</li>
  * <li><b>weekOneContains</b>
  * Defines week one as the week which contains this date. Format should be MMM-DD, which is a month/day combination: e.g. choosing Apr-1 will define
  * week one as being the week containing the 1st of April. Defaults to the 1st of January.</li>
  * <li><b>weekNumberFilter</b>
  * Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.
  * Leaving an empty value means the end of the year.
  * Examples: "1:30" - Weeks one to thirty inclusive.
  * "4:" - Week four onwards.
  * ":5" - Upto and including week five.</li>
  * <li><b>viewPreviousIfTooEarly</b>
  * Boolean. When using week filters, it is possible to bring up a calendar for this year which is entirely in the future. This
  * option will cause the display of the previous year.
  * <li><b>newURL</b>
  * The URL to invoke when selecting a date which does not have a previous sample associated with it.
  * To the end of this will be appended "&date=<X>" whose value will be the date selected.</li>
  * <li><b>existingURL</b>
  * The URL to invoke when selecting an existing sample.
  * To the end of this will be appended "&sample_id=<n>".
  * <li><b>buildLinkFunc</b>
  * A callback (taking 3 arguments - record array, options, and baseline cell contents - just the date as a string)
  * to generate the link. This is optional. Can be used if special classes are to be added, or to
  * handle extra filter constraints.
  */
  // Future Enhancements? Allow restriction to month.
  public static function report_calendar_grid($options) {
    // I know that there are better ways to approach some of the date manipulation, but they are PHP 5.3+.
    // We support back to PHP 5.2
    // TODO : i8n
    $warnings="";
    data_entry_helper::add_resource('jquery_ui');
    // there are some report parameters that we can assume for a calendar based request...
    // the report must have a date field, a user_id field if set in the configuration, and a location_id.
    // default is samples_list_for_cms_user.xml
    $options = self::get_report_calendar_grid_options($options);
    $extras = '';
    self::request_report($response, $options, $currentParamValues, false, $extras);
    if (isset($response['error'])) {
      return "ERROR RETURNED FROM request_report:".$response['error'];
    }
    // We're not even going to bother with asking the user to populate a partially filled in report parameter set.
    if (isset($response['parameterRequest'])) {
      return '<p>Internal Error: Report request parameters not set up correctly.<p>';
    }
    // convert records to a date based array so it can be used when generating the grid.
    $records = $response['records'];
    $dateRecords=array();
    foreach($records as $record){
      if(isset($dateRecords[$record['date']])) {
        $dateRecords[$record['date']][] = $record;
      } else {
        $dateRecords[$record['date']] = array($record);
      }
    }
    $pageUrlParams = self::get_report_calendar_grid_page_url_params($options);
    $pageUrl = self::report_calendar_grid_get_reload_url($pageUrlParams);
    $pageUrl .= (strpos($pageUrl , '?')===false) ? '?' : '&';
    $thClass = $options['thClass'];
    $r .= "\n<table class=\"".$options['class']."\">";
    $r .= "\n<thead class=\"$thClass\"><tr>".($options['includeWeekNumber'] ? "<td>".lang::get("Week Number")."</td>" : "")."<td></td><td>
  <a title=\"".($options["year"]-1)."\" rel=\"\nofollow\" href=\"".$pageUrl.$pageUrlParams['year']['name']."=".($options["year"]-1)."\" class=\"ui-datepicker-prev ui-corner-all\">
    <span class=\"ui-icon ui-icon-circle-triangle-w\">Prev</span></a></td><td></td><td colspan=3><span class=\"thisYear\">".$options["year"]."</span></td><td></td><td>";
    if($options["year"]<date('Y')){
      $r .= "  <a title=\"".($options["year"]+1)."\" rel=\"\nofollow\" href=\"".$pageUrl.$pageUrlParams['year']['name']."=".($options["year"]+1)."\" class=\"ui-datepicker-next ui-corner-all\">
        <span class=\"ui-icon ui-icon-circle-triangle-e\">Next</span></a>";
    }
    $r .= "</td></tr></thead>\n";
    // don't need a separate "Add survey" button as they just need to click the day....
    // Not implementing a download.
    $r .= "<tbody>\n";
    $date_from = array('year'=>$options["year"], 'month'=>1, 'day'=>1);
    $date_to = array('year'=>$options["year"], 'month'=>12, 'day'=>31);
    $weekno=0;
    // ISO Date - Mon=1, Sun=7
    // Week 1 = the week with date_from in
    if(!isset($options['weekstart']) || $options['weekstart']=='') {
      $options['weekstart']="weekday=7"; // Default Sunday
    }
    $weekstart=explode('=',$options['weekstart']);
    if(!isset($options['weekNumberFilter']) || $options['weekNumberFilter']=='') {
      $options['weekNumberFilter']=":";
    }
    $weeknumberfilter=explode(':',$options['weekNumberFilter']);
    if(count($weeknumberfilter)!=2){
      $warnings .= "Week number filter unrecognised {".$options['weekNumberFilter']."} defaulting to all<br />";
      $weeknumberfilter=array('','');
    } else {
      if($weeknumberfilter[0] != '' && (intval($weeknumberfilter[0])!=$weeknumberfilter[0] || $weeknumberfilter[0]>52)){
        $warnings .= "Week number filter start unrecognised or out of range {".$weeknumberfilter[0]."} defaulting to year start<br />";
        $weeknumberfilter[0] = '';
      }
      if($weeknumberfilter[1] != '' && (intval($weeknumberfilter[1])!=$weeknumberfilter[1] || $weeknumberfilter[1]<$weeknumberfilter[0] || $weeknumberfilter[1]>52)){
        $warnings .= "Week number filter end unrecognised or out of range {".$weeknumberfilter[1]."} defaulting to year end<br />";
        $weeknumberfilter[1] = '';
      }
    }
    if($weekstart[0]=='date'){
      $weekstart_date = date_create($date_from['year']."-".$weekstart[1]);
      if(!$weekstart_date){
        $warnings .= "Weekstart month-day combination unrecognised {".$weekstart[1]."} defaulting to weekday=7 - Sunday<br />";
        $weekstart[1]=7;
      } else $weekstart[1]=$weekstart_date->format('N');
    }
    if(intval($weekstart[1])!=$weekstart[1] || $weekstart[1]<1 || $weekstart[1]>7) {
      $warnings .= "Weekstart unrecognised or out of range {".$weekstart[1]."} defaulting to 7 - Sunday<br />";
      $weekstart[1]=7;
    }
    $consider_date = new DateTime($date_from['year'].'-'.$date_from['month'].'-'.$date_from['day']);
    while($consider_date->format('N')!=$weekstart[1]) {
      $consider_date->modify('-1 day');
    }
    $header_date=clone $consider_date;
    $r .= "<tr>".($options['includeWeekNumber'] ? "<td></td>" : "")."<td></td>";
    for($i=0; $i<7; $i++){
      $r .= "<td class=\"day\">".$header_date->format('D')."</td>"; // i8n
      $header_date->modify('+1 day');
    }
    $r .= "</tr>";
    if(isset($options['weekOneContains']) && $options['weekOneContains']!=""){
      $weekOne_date = date_create($date_from['year'].'-'.$options['weekOneContains']);
      if(!$weekOne_date){
        $warnings .= "Week one month-day combination unrecognised {".$options['weekOneContains']."} defaulting to Jan-01<br />";
        $weekOne_date = date_create($date_from['year'].'-Jan-01');
      }
    } else
      $weekOne_date = date_create($date_from['year'].'-Jan-01');
    while($weekOne_date->format('N')!=$weekstart[1]){
      $weekOne_date->modify('-1 day'); // scan back to start of week
    }
    while($weekOne_date > $consider_date){
      $weekOne_date->modify('-7 days');
      $weekno--;
    }
    if($weeknumberfilter[0]!=''){
      while($weekno < ($weeknumberfilter[0]-1)){
        $consider_date->modify('+7 days');
        $weekno++;
      }
    }
    $now = new DateTime();
    if($now < $consider_date && $options["viewPreviousIfTooEarly"]){
      $options["year"]--;
      $options["viewPreviousIfTooEarly"]=false;
      return self::report_calendar_grid($options);
    }
    $options["newURL"] .= (strpos($options["newURL"] , '?')===false) ? '?' : '&';
    $options["existingURL"] .= (strpos($options["existingURL"] , '?')===false) ? '?' : '&';

    while($consider_date->format('Y') <= $options["year"] && ($weeknumberfilter[1]=='' || $consider_date->format('N')!=$weekstart[1] || $weekno < $weeknumberfilter[1])){
      if($consider_date->format('N')==$weekstart[1]) {
        $weekno++;
        $r .= "<tr class=\"datarow\">".($options['includeWeekNumber'] ? "<td class=\"weeknum\">".$weekno."</td>" : "")."<td class\"month\">".$consider_date->format('M')."</td>";
      }
      $cellContents=$consider_date->format('j');  // day in month.
      $cellclass="";
      if($now < $consider_date){
        $cellclass="future";
      } else if($consider_date->format('Y') == $options["year"]){
      	if(isset($dateRecords[$consider_date->format('d/m/Y')])){
          if(isset($options['buildLinkFunc'])){
            $callbackVal = call_user_func_array($options['buildLinkFunc'], array($dateRecords[$consider_date->format('d/m/Y')], $options, $cellContents));
            $cellclass=$callbackVal['cellclass'];
            $cellContents=$callbackVal['cellContents'];
          } else {
            $cellclass="existingLink";
            if(count($dateRecords[$consider_date->format('d/m/Y')])==1){
              $cellContents='<a href="'.$options["existingURL"].'sample_id='.$dateRecords[$consider_date->format('d/m/Y')][0]["sample_id"].'" title="View existing sample for '.$dateRecords[$consider_date->format('d/m/Y')][0]["location_name"].' on this date" >'.$cellContents.'</a>';
            } else {
              foreach($dateRecords[$consider_date->format('d/m/Y')] as $record){
                $cellContents.='<br/><a href="'.$options["existingURL"].'sample_id='.$record["sample_id"].'" title="View existing sample for '.$record["location_name"].' on this date" >'.$record["location_name"].'</a>';
              }
            }
          }
        } else {
          $cellclass="newLink";
          $cellContents='<a href="'.$options["newURL"].'date='.$consider_date->format('d/m/Y').'" class="newLink" title="Create a new sample for this date" >'.$cellContents.'</a>';
        }
      }
      $r .= "<td class=\"".$cellclass." ".($consider_date->format('N')>=6 ? "weekend" : "weekday")."\" >".$cellContents."</td>";
      $consider_date->modify('+1 day');
      $r .= ($consider_date->format('N')==$weekstart[1] ? "</tr>" : "");
    }
    if($consider_date->format('N')!=$weekstart[1]) { // need to fill up rest of week
      while($consider_date->format('N')!=$weekstart[1]){
        $r .= "<td class=\"".($consider_date->format('N')>=6 ? "weekend" : "weekday")."\">".$consider_date->format('j')."</td>";
        $consider_date->modify('+1 day');
      }
      $r .= "</tr>";
    }
    $r .= "</tbody></table>\n";
    return $warnings.$r;
  }

  private static function get_report_calendar_grid_options($options) {
    global $user;
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'calendar-report-output', // this needs to be set explicitly when more than one report on a page
      'class' => 'ui-widget ui-widget-content report-grid',
      'thClass' => 'ui-widget-header',
      'extraParams' => array(),
      'year' => date('Y'),
      'viewPreviousIfTooEarly' => true, // if today is before the start of the calendar, display last year.
        // it is possible to create a partial calendar.
      'includeWeekNumber' => false,
      'weekstart' => 'weekday=7', // Default Sunday
      'weekNumberFilter' => ':'
    ), $options);
    $options["extraParams"] = array_merge(array(
      'date_from' => $options["year"].'-01-01',
      'date_to' => $options["year"].'-12-31',
      'user_id' => $user->uid, // CMS User, not Indicia User.
      'smpattrs' => ''), $options["extraParams"]);
    // Note for the calendar reports, the user_id is assumed to be the CMS user id as recorded in the CMS User ID attribute,
    // not the Indicia user id.
    return $options;
  }

 /**
   * Works out the page URL param names for this report calendar grid, and also gets their current values.
   * Note there is no need to sort for the calender grid.
   * @param $options Control options array
   * @return array Contains the page params, as an assoc array. Each array value is an array containing name & value.
   */
  private static function get_report_calendar_grid_page_url_params($options) {
    $yearKey = 'year';
    return array(
      'year' => array(
        'name' => $yearKey,
        'value' => isset($_GET[$yearKey]) ? $_GET[$yearKey] : null
      )
    );
  }
  /**
   * Build a url suitable for inclusion in the links for the report calendar grid column pagination
   * bar. This effectively re-builds the current page's URL, but drops the query string parameters that
   * indicate the year and site.
   * Note there is no need to sort for the calender grid.
   * @param array $pageUrlParams List pagination parameters which should be excluded.
   * @return string
   */
  private static function report_calendar_grid_get_reload_url($pageUrlParams) {
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = data_entry_helper::get_reload_link_parts();
    // find the names of the params we must not include
    $excludedParams = array();
    foreach($pageUrlParams as $param) {
      $excludedParams[] = $param['name'];
    }
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!in_array($key, $excludedParams)){
        $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?')===false ? '?' : '&')."$key=$value";
      }
    }
    return $reloadUrl['path'];
  }

  /**
   * Inserts into the page javascript a function for loading features onto the map as a result of report output.
   */
  private static function addFeaturesLoadingJs($addFeaturesJs, $defsettings='',
      $selsettings='{"strokeColor":"#ff0000","fillColor":"#ff0000","strokeWidth":2}', $styleFns='', $zoomToExtent=true) {
    if (!empty($addFeaturesJs)) {
      report_helper::$javascript.= "
  if (typeof OpenLayers !== \"undefined\") {
    var defaultStyle = new OpenLayers.Style($defsettings$styleFns);
    var selectStyle = new OpenLayers.Style($selsettings$styleFns);
    var styleMap = new OpenLayers.StyleMap({'default' : defaultStyle, 'select' : selectStyle});
    indiciaData.reportlayer = new OpenLayers.Layer.Vector('Report output', {styleMap: styleMap, rendererOptions: {zIndexing: true}});
    mapInitialisationHooks.push(function(div) {
      function addDistPoint(features, record, wktCol, opts, id) {
        if (record[wktCol]!==null) {
          var feature, geom=OpenLayers.Geometry.fromWKT(record[wktCol]);
          if (div.map.projection.getCode() != div.indiciaProjection.getCode()) {
            geom.transform(div.indiciaProjection, div.map.projection);
          }
          delete record[wktCol];
          if (opts.type!=='vector') {
            // render a point for symbols
            geom = geom.getCentroid();
          }
          feature = new OpenLayers.Feature.Vector(geom, record);
          if (typeof id!=='undefined') {
            // store a supplied identifier against the feature
            feature.id=id;
          }
          features.push(feature);
        }
      }
      features = [];
      $addFeaturesJs
      indiciaData.reportlayer.addFeatures(features);\n";
        if ($zoomToExtent)
          self::$javascript .= "  div.map.zoomToExtent(indiciaData.reportlayer.getDataExtent());\n";
        self::$javascript .= "  div.map.addLayer(indiciaData.reportlayer);
    });
  }\n";
    }
  }

 /**
  * <p>Outputs a calendar based summary grid that loads the content of a report.</p>
  * <p>If you need 2 grids on one page, then you must define a different id in the options for each grid.</p>
  * <p>The grid operation has NOT been AJAXified. There is no download option.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid's container div. This is required if there is more than
  * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view. when used, any user_id must refer to the CMS user ID, not the Indicia
  * User.</li>
  * <li><b>view</b>
  * When loading from a view, specify list, gv or detail to determine which view variant is loaded. Default is list.
  * </li>
  * <li><b>extraParams</b><br/>
  * Array of additional key value pairs to attach to the request. This should include fixed values which cannot be changed by the
  * user and therefore are not needed in the parameters form.
  * </li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values. Default values appear in the parameter form and can be overridden.</li>
  * <li><b>tableHeaders</b>
  * Defines which week column headers should be included: date, number or both
  * <li><b>weekstart</b>
  * Defines the first day of the week. There are 2 options.<br/>'.
  *  weekday=<n> where <n> is a number between 1 (for Monday) and 7 (for Sunday). Default is 'weekday=7'
  *  date=MMM-DD where MMM-DD is a month/day combination: e.g. choosing Apr-1 will start each week on the day of the week on which the 1st of April occurs.</li>
  * <li><b>weekOneContains</b>
  * Defines week one as the week which contains this date. Format should be MMM-DD, which is a month/day combination: e.g. choosing Apr-1 will define
  * week one as being the week containing the 1st of April. Defaults to the 1st of January.</li>
  * <li><b>weekNumberFilter</b>
  * Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.
  * Leaving an empty value means the end of the year.
  * Examples: "1:30" - Weeks one to thirty inclusive.
  * "4:" - Week four onwards.
  * ":5" - Upto and including week five.</li>
  * <li><b>rowGroupColumn</b>
  * The column in the report which is used as the vertical axis on the grid.</li>
  * <li><b>countColumn</b>
  * OPTIONAL: The column in the report which contains the count for this occurrence. If omitted then the default
  * is to assume one occurrence = count of 1</li>
  */
  // Future Enhancements? Allow restriction to month.
  public static function report_calendar_summary($options) {
    // I know that there are better ways to approach some of the date manipulation, but they are PHP 5.3+.
    // We support back to PHP 5.2
    // TODO : i8n
    // TODO invariant IDs and names prevents more than one on a page.
    // TODO convert to tabs when switching between chart and table.
    $warnings="";
    data_entry_helper::add_resource('jquery_ui');
    // there are some report parameters that we can assume for a calendar based request...
    // the report must have a date field, a user_id field if set in the configuration, and a location_id.
    // default is samples_list_for_cms_user.xml
    $options = self::get_report_calendar_summary_options($options);
    $extras = '';
    self::request_report($response, $options, $currentParamValues, false, $extras);
    if (isset($response['error'])) {
      return "ERROR RETURNED FROM request_report:<br />".(print_r($response,true));
    }
    // We're not even going to bother with asking the user to populate a partially filled in report parameter set.
    if (isset($response['parameterRequest'])) {
      return '<p>Internal Error: Report request parameters not set up correctly.<br />'.(print_r($response,true)).'<p>';
    }
    // convert records to a date based array so it can be used when generating the grid.
    $records = $response['records'];
    $pageUrlParams = self::get_report_calendar_grid_page_url_params($options);
    $pageUrl = self::report_calendar_grid_get_reload_url($pageUrlParams);
    $pageUrl .= (strpos($pageUrl , '?')===false) ? '?' : '&';
    // ISO Date - Mon=1, Sun=7
    // Week 1 = the week with date_from in
    if(!isset($options['weekstart']) || $options['weekstart']=="") {
      $options['weekstart']="weekday=7"; // Default Sunday
    }
    if(!isset($options['weekNumberFilter']) ||$options['weekNumberFilter']=="") {
      $options['weekNumberFilter']=":";
    }
    $weeknumberfilter=explode(':',$options['weekNumberFilter']);
    if(count($weeknumberfilter)!=2){
      $warnings .= "Week number filter unrecognised {".$options['weekNumberFilter']."} defaulting to all<br />";
      $weeknumberfilter=array('','');
    } else {
      if($weeknumberfilter[0] != '' && (intval($weeknumberfilter[0])!=$weeknumberfilter[0] || $weeknumberfilter[0]>52)){
        $warnings .= "Week number filter start unrecognised or out of range {".$weeknumberfilter[0]."} defaulting to year start<br />";
        $weeknumberfilter[0] = '';
      }
      if($weeknumberfilter[1] != '' && (intval($weeknumberfilter[1])!=$weeknumberfilter[1] || $weeknumberfilter[1]<$weeknumberfilter[0] || $weeknumberfilter[1]>52)){
        $warnings .= "Week number filter end unrecognised or out of range {".$weeknumberfilter[1]."} defaulting to year end<br />";
        $weeknumberfilter[1] = '';
      }
    }
    $weekstart=explode('=',$options['weekstart']);
    if($weekstart[0]=='date'){
      $weekstart_date = date_create(substr($options['date_start'],0,4)."-".$weekstart[1]);
      if(!$weekstart_date){
        $warnings .= "Weekstart month-day combination unrecognised {".$weekstart[1]."} defaulting to weekday=7 - Sunday<br />";
        $weekstart[1]=7;
      } else $weekstart[1]=$weekstart_date->format('N');
    }
    if(intval($weekstart[1])!=$weekstart[1] || $weekstart[1]<1 || $weekstart[1]>7) {
      $warnings .= "Weekstart unrecognised or out of range {".$weekstart[1]."} defaulting to 7 - Sunday<br />";
      $weekstart[1]=7;
    }
    if(isset($options['weekOneContains']) && $options['weekOneContains']!=""){
      $weekOne_date = date_create(substr($options['date_start'],0,4).'-'.$options['weekOneContains']);
      if(!$weekOne_date){
        $warnings .= "Week one month-day combination unrecognised {".$options['weekOneContains']."} defaulting to Jan-01<br />";
        $weekOne_date = date_create(substr($options['date_start'],0,4).'-Jan-01');
      }
    } else
      $weekOne_date = date_create(substr($options['date_start'],0,4).'-Jan-01');
    while($weekOne_date->format('N')!=$weekstart[1]){
      $weekOne_date->modify('-1 day'); // scan back to start of week
    }
    $summaryArray=array(); // this is used for the table output format
    foreach($records as $record){
    	// first work out the week number
      $this_date = date_create(str_replace('/','-',$record['date'])); // prevents day/month orderinfg issues
      while($this_date->format('N')!=$weekstart[1]){
        $this_date->modify('-1 day'); // scan back to start of week
      }
      $weekno=1;
      while($this_date>$weekOne_date){
        $this_date->modify('-7 days');
        $weekno++;
      }
      while($this_date<$weekOne_date){
        $this_date->modify('+7 days');
        $weekno--;
      }
      if(isset($options['countColumn']) && $options['countColumn']!=''){
        $count = (isset($record[$options['countColumn']])?$record[$options['countColumn']]:0);
      } else
        $count = 1; // default to single row = single occurrence
      if(isset($summaryArray[$record[$options['rowGroupColumn']]])) {
        if(isset($summaryArray[$record[$options['rowGroupColumn']]][$weekno])){
          $summaryArray[$record[$options['rowGroupColumn']]][$weekno] += $count;
        } else {
          $summaryArray[$record[$options['rowGroupColumn']]][$weekno] = $count;
        }
      } else {
        $summaryArray[$record[$options['rowGroupColumn']]] = array($weekno => $count);
      }
    }
    if(count($summaryArray)==0)
      return $warnings.'<p>'.lang::get('No data returned for this period.').'</p>';
    $year_start = date_create(substr($options['date_start'],0,4).'-Jan-01');
    $year_end = date_create(substr($options['date_start'],0,4).'-Dec-25'); // don't want to go beyond the end of year: this is 1st Jan minus 1 week: it is the start of the last full week
    $firstWeek_date = clone $weekOne_date;
    if($weeknumberfilter[0]!=''){
      $minWeekNo = 1;
      while($firstWeek_date > $year_start && $minWeekNo>$weeknumberfilter[0]){
        $firstWeek_date->modify('-7 days');
        $minWeekNo--;
      }
      while($firstWeek_date < $year_end && $minWeekNo<$weeknumberfilter[0]){
        $firstWeek_date->modify('+7 days');
        $minWeekNo++;
      }
    } else {
      $minWeekNo = 1;
      while($firstWeek_date > $year_start){
        $firstWeek_date->modify('-7 days');
        $minWeekNo--;
      }
    }
    if($weeknumberfilter[1]!=''){
      $maxWeekNo = $weeknumberfilter[1];
    } else {
      $maxWeekNo = 1;
      $lastWeek_date = clone $weekOne_date;
      $year_end = date_create(substr($options['date_start'],0,4).'-Dec-25'); // don't want to go beyond the end of year: this is 1st Jan minus 1 week: it is the start of the last full week
      while($lastWeek_date <= $year_end){
        $lastWeek_date->modify('+7 days');
        $maxWeekNo++;
      }
    }
    $r="";
    // will storedata in an array[Y][X]
    $format= array();
    if(isset($options['outputTable']) && $options['outputTable']){
      $format['table'] = array('include'=>true,
          'display'=>(isset($options['simultaneousOutput']) && $options['simultaneousOutput'])||(isset($options['defaultOutput']) && $options['defaultOutput']=='table')||!isset($options['defaultOutput']));
    }
    if(isset($options['outputChart']) && $options['outputChart']){
      $format['chart'] = array('include'=>true,
          'display'=>(isset($options['simultaneousOutput']) && $options['simultaneousOutput'])||(isset($options['defaultOutput']) && $options['defaultOutput']=='chart'));
      data_entry_helper::add_resource('jqplot');
      switch ($options['chartType']) {
        case 'bar' :
          self::add_resource('jqplot_bar');
          $renderer='$.jqplot.BarRenderer';
          break;
        // default is line
      }
      self::add_resource('jqplot_category_axis_renderer');
      $opts = array();
      $options['legendOptions']["show"]=true;
      $opts[] = "seriesDefaults:{\n".(isset($renderer) ? "  renderer:$renderer,\n" : '')."  rendererOptions:".json_encode($options['rendererOptions'])."}";
      $opts[] = 'legend:'.json_encode($options['legendOptions']);
    }
    if(count($format)==0) $format['table'] = array('include'=>true);
    $defaultSet=false;
    foreach($format as $type=>$info){
      if($info['display']==true)
        $defaultSet=true;
    }
    if(!$defaultSet){
      if(isset($format['table'])) $format['table']['display']=true;
      else if(isset($format['chart'])) $format['chart']['display']=true;
    }
    $chartDateLabels=array();
    $chartNumberLabels=array();
    $tableDateHeaderRow = "";
    $tableNumberHeaderRow = "";
    $seriesData=array();
    for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
      $tableNumberHeaderRow.= '<td class="week">'.$i.'</td>';
      $tableDateHeaderRow.= '<td class="week">'.$firstWeek_date->format('M').'<br/>'.$firstWeek_date->format('d').'</td>';
      $chartNumberLabels[] = $i;
      $chartDateLabels[] = $firstWeek_date->format('M').'-'.$firstWeek_date->format('d');
      $firstWeek_date->modify('+7 days');
    }
    if(count($format)>1 && !(isset($options['simultaneousOutput']) && $options['simultaneousOutput'])){
      $checked = !isset($options['defaultOutput']) || $options['defaultOutput']=='' || $options['defaultOutput']=='table';
      $r .= "\n".'<div class="simultaneousOutputGroup"><input type="radio" name="simultaneousOutput" id="simultaneousOutput:table" '.($checked?'checked="checked"':'').' value="table"/><label for="simultaneousOutput:table" >'.lang::get('View data as a table').'</label>'.
            '<input type="radio" name="simultaneousOutput" '.(!$checked?'checked="checked"':'').' id="simultaneousOutput:chart" value="chart"/><label for="simultaneousOutput:chart" >'.lang::get('View data as a chart').'</label></div>'."\n";
      data_entry_helper::$javascript .= "jQuery('[name=simultaneousOutput]').change(function(){
  jQuery('#".$options['tableID'].",#".$options['chartContainerID']."').toggle();
  // TODO global variable prevents more than one on a page.
  replot();
});
jQuery('[name=simultaneousOutput]').filter('[value=".($checked?'table':'chart')."]').attr('checked','checked');
";
    }
    if(isset($format['chart'])){
      $seriesData=array();
      $seriesOptions=array();
      // Series options are not configurable as we need to setup for ourselves...
      // we need show, label and show label filled in. rest are left to defaults
      $totalRow = array();
      for($i= $minWeekNo; $i <= $maxWeekNo; $i++) $totalRow[$i] = 0;
      foreach($summaryArray as $label => $summaryRow){
        $values=array();
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
          if(isset($summaryRow[$i])){
            $values[]=$summaryRow[$i];
            $totalRow[$i] += $summaryRow[$i];
          } else {
            $values[]=0;
          }
        }
        // each series will occupy an entry in $seriesData
        $seriesData[] = '['.implode(',', $values).']';
        $seriesOptions[] = '{"show":true,"label":"'.$label.'","showlabel":true}';
      }
      if(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']){
        array_unshift($seriesData, '['.implode(',', $totalRow).']');
        array_unshift($seriesOptions, '{"show":true,"label":"'.lang::get('Total').'","showlabel":true}');
      }
      $opts[] = 'series:['.implode(',', $seriesOptions).']';
      $options['axesOptions']['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';
      if(isset($options['chartLabels']) && $options['chartLabels'] == 'number')
        $options['axesOptions']['xaxis']['ticks'] = $chartNumberLabels;
      else
        $options['axesOptions']['xaxis']['ticks'] = $chartDateLabels;
      // We need to fudge the json so the renderer class is not a string
      $axesOpts = str_replace('"$.jqplot.CategoryAxisRenderer"', '$.jqplot.CategoryAxisRenderer',
        'axes:'.json_encode($options['axesOptions']));
      $opts[] = $axesOpts;
      data_entry_helper::$javascript .= "\nvar axesOpts = {".$axesOpts."};\naxesOpts.resetAxes=['yaxis'];\n";
      // Finally, dump out the Javascript with our constructed parameters.
      // width stuff is a bit weird, but jqplot requires a fixed width, so this just stretches it to fill the space.
      if($format['chart']['display']){
        if(!isset($options['width']) || $options['width'] == '')
          data_entry_helper::$javascript .= "\njQuery(\"#".$options['chartID']."\").width(jQuery(\"#".$options['chartID']."\").width());";
        data_entry_helper::$javascript .= "
var plot = $.jqplot('".$options['chartID']."',  [".implode(',', $seriesData)."], \n{".implode(",\n", $opts)."});
function replot(){
  plot.redraw();
}
";
      }else{
        data_entry_helper::$javascript .= "\nvar plot;
function replot(){
  if(typeof plot=='undefined'){";
        if(!isset($options['width']) || $options['width'] == '')
          data_entry_helper::$javascript .= "\njQuery(\"#".$options['chartID']."\").width(jQuery(\"#".$options['chartID']."\").width());";
        data_entry_helper::$javascript .= "
    plot = $.jqplot('".$options['chartID']."',  [".implode(',', $seriesData)."], \n{".implode(",\n", $opts)."});
  }else
    plot.redraw();
};
";
      }
      // div are full width.
      $r .= '<div id="'.$options['chartContainerID'].'" class="'.$options['chartClass'].'" style="'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').($format['chart']['display']?'':'display:none;').'">';
      if (isset($options['title']))
        $r .= '<div class="'.$options['headerClass'].'">'.$options['title'].'</div>';
      $r .= '<div id="'.$options['chartID'].'" style="height:'.$options['height'].'px;'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').'"></div>'."\n";
      if(isset($options['disableableSeries']) && $options['disableableSeries'] && count($summaryArray)>1){
        drupal_add_js('misc/collapse.js');
        $r .= '<fieldset id="'.$options['chartID'].'-series" class="collapsible collapsed series-fieldset"><legend>'.lang::get('Display ').$options['rowGroupColumn']."</legend><span>\n";
        $idx=0;
        if(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']){
          $r .= '<span class="chart-series-span"><input type="checkbox" checked="checked" id="'.$options['chartID'].'-series-'.$idx.'" name="'.$options['chartID'].'-series" value="'.$idx.'"/><label for="'.$options['chartID'].'-series-'.$idx.'">'.lang::get('Total')."</label></span>\n";
          $idx++;
        }
        $r .= '<input type="button" class="disable-button" id="'.$options['chartID'].'-series-disable" value="'.lang::get('Hide all ').$options['rowGroupColumn']."\"/>\n";
        foreach($summaryArray as $label => $summaryRow){
          $r .= '<span class="chart-series-span"><input type="checkbox" checked="checked" id="'.$options['chartID'].'-series-'.$idx.'" name="'.$options['chartID'].'-series" value="'.$idx.'"/><label for="'.$options['chartID'].'-series-'.$idx.'">'.$label."</label></span>\n";
          $idx++;
        }
        $r .= "</span></fieldset>\n";
        // Known issue: jqplot considers the min and max of all series when drawing on the screen, even those which are not displayed
        // so replotting doesn't scale to the displayed series!
        data_entry_helper::$javascript .= "
// initially all are displayed: need to ensure get around field caching on browser refresh.
jQuery('[name=".$options['chartID']."-series]').attr('checked','checked');
jQuery('[name=".$options['chartID']."-series]').change(function(){
  if(jQuery(this).filter('[checked]').length){
    plot.series[jQuery(this).val()].show = true;
  } else {
    plot.series[jQuery(this).val()].show = false;
  }
  var max=0;
  for(var i=0; i<plot.series.length; i++)
    if(plot.series[i].show)
      for(var j=0; j<plot.series[i].data.length; j++)
        max=(max>plot.series[i].data[j][1]?max:plot.series[i].data[j][1]);
  axesOpts.axes.yaxis.max=max+1;
  plot.replot(axesOpts);
});
jQuery('#".$options['chartID']."-series-disable').click(function(){
  if(jQuery(this).is('.cleared')){ // button is to show all
    jQuery('[name=".$options['chartID']."-series]')".(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']?".not('[value=0]')":"").".attr('checked','checked').each(function(){plot.series[jQuery(this).val()].show = true;});
    jQuery(this).removeClass('cleared').val(\"".lang::get('Hide all ').$options['rowGroupColumn']."\");
  } else {
    jQuery('[name=".$options['chartID']."-series]')".(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']?".not('[value=0]')":"").".removeAttr('checked').each(function(){plot.series[jQuery(this).val()].show = false;});
    jQuery(this).addClass('cleared').val(\"".lang::get('Show all ').$options['rowGroupColumn']."\");
  }
  var max=0;
  for(var i=0; i<plot.series.length; i++)
    if(plot.series[i].show)
      for(var j=0; j<plot.series[i].data.length; j++)
        max=(max>plot.series[i].data[j][1]?max:plot.series[i].data[j][1]);
  axesOpts.axes.yaxis.max=max+1;
  plot.replot(axesOpts);
});
";
      }
      $r .= "</div>\n";
    }
    if(isset($format['table'])){
      $thClass = $options['thClass'];
      $r .= "\n<table id=\"".$options['tableID']."\" class=\"".$options['tableClass']."\" style=\"".($format['table']['display']?'':'display:none;')."\">";
      $r .= "\n<thead class=\"$thClass\">";
      if(isset($options['tableHeaders']) && ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number')){
        $r .= '<tr><td>Week</td>'.$tableNumberHeaderRow.(isset($options['includeTableTotalColumn']) && $options['includeTableTotalColumn']?'<td>Total</td>':'').'</tr>';
      }
      if(!isset($options['tableHeaders']) || $options['tableHeaders'] != 'number'){
        $r .= '<tr><td>Date</td>'.$tableDateHeaderRow.(isset($options['includeTableTotalColumn']) && $options['includeTableTotalColumn']?(!isset($options['tableHeaders']) || $options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number'?'<td></td>':'<td>Total</td>'):'').'</tr>';
      }
      $r.= "</thead>\n";
      $r .= "<tbody>\n";
      $altRow=false;
      $grandTotal=0;
      $totalRow = array();
      for($i= $minWeekNo; $i <= $maxWeekNo; $i++) $totalRow[$i] = 0;
      foreach($summaryArray as $label => $summaryRow){
        $total=0;  // row total
        $r .= "<tr class=\"datarow ".($altRow?$options['altRowClass']:'')."\">";
        $r.= '<td>'.$label.'</td>';
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
          if(isset($summaryRow[$i])){
            $r.= '<td>'.$summaryRow[$i].'</td>';
            $total += $summaryRow[$i];
            $totalRow[$i] += $summaryRow[$i];
            $grandTotal += $summaryRow[$i];
          } else {
            $r.= '<td></td>';
          }
        }
        if(isset($options['includeTableTotalColumn']) && $options['includeTableTotalColumn']) $r.= '<td class="total-column">'.$total.'</td>';
        $r .= "</tr>";
        $altRow=!$altRow;
      }
      if(isset($options['includeTableTotalRow']) && $options['includeTableTotalRow']){
        $r .= "<tr class=\"totalrow\">";
        $r.= '<td>'.lang::get('Total').'</td>';
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
          $r.= '<td>'.$totalRow[$i].'</td>';
        }
        if(isset($options['includeTableTotalColumn']) && $options['includeTableTotalColumn']) $r.= '<td class="total-column grand-total">'.$grandTotal.'</td>';
        $r .= "</tr>";
      }
      $r .= "</tbody></table>\n";
    }
    if(count($summaryArray)==0)
      $r .= '<p>'.lang::get('No data returned for this period.').'</p>';
    return $warnings.$r;
  }

  private static function get_report_calendar_summary_options($options) {
    global $user;
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'calendar-report-output', // this needs to be set explicitly when more than one report on a page
      'tableID' => 'report-table',
      'tableClass' => 'ui-widget ui-widget-content report-grid',
      'thClass' => 'ui-widget-header',
      'altRowClass' => 'odd',
      'extraParams' => array(),
      'viewPreviousIfTooEarly' => true, // if today is before the start of the calendar, display last year.
        // it is possible to create a partial calendar.
      'includeWeekNumber' => false,
      'weekstart' => 'weekday=7', // Default Sunday
      'weekNumberFilter' => ':',
      'rowGroupColumn'=>'taxon',
      'chartContainerID' => 'chartdiv-container',
      'chartID' => 'chartdiv',
      'chartClass' => 'ui-widget ui-widget-content ui-corner-all',
      'headerClass' => 'ui-widget-header ui-corner-all',
      'height' => 400,
      // 'width' is optional
      'chartType' => 'line', // bar, pie
      'rendererOptions' => array(),
      'legendOptions' => array(),
      'axesOptions' => array()
    ), $options);
    $options["extraParams"] = array_merge(array(
      'date_from' => $options['date_start'],
      'date_to' => $options['date_end'],
//      'user_id' => '', // CMS User, not Indicia User.
//      'smpattrs' => '',
      'occattrs' => ''), $options["extraParams"]);
    // Note for the calendar reports, the user_id is assumed to be the CMS user id as recorded in the CMS User ID attribute,
    // not the Indicia user id.
    return $options;
  }

}

?>
