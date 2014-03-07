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
 * @package	Client
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
    $options['class'] .= ' report-picker-container control-box ui-widget ui-widget-content ui-helper-clearfix';
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
            '" onclick="displayReportMetadata(\'' . $fieldname . '\', \'' . $item['path'] . '\');" ' . $checked . '>'.
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
   * option to the same value for both the report_download_link and report_grid controls to link them together. Use the itemsPerPage parameter
   * to control how many records are downloaded. 
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>format</b><br/>
   * Default to csv. Specify the download format, one of csv, json, xml, nbn.
   * </li>
   * </ul>
   */
  public static function report_download_link($options) {
    $options = array_merge(array(
      'caption' => lang::get('Download this report'), 
      'format' => 'csv',
      'itemsPerPage' => 10000
    ), $options);
    $options = self::get_report_grid_options($options);
    $options['linkOnly'] = true;
    $currentParamValues = self::get_report_grid_current_param_values($options);
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    // don't want to paginate the download link
    unset($sortAndPageUrlParams['page']);
    $extras = self::get_report_sorting_paging_params($options, $sortAndPageUrlParams);
    $link = self::get_report_data($options, $extras.'&'.self::array_to_query_string($currentParamValues, true), true);
    global $indicia_templates;
    return str_replace(array('{link}','{caption}'), array($link, lang::get($options['caption'])), $indicia_templates['report_download_link']);
  }

 /**
  * Outputs a grid that loads the content of a report or Indicia table.
  * 
  * The grid supports a simple pagination footer as well as column title sorting through PHP. If
  * used as a PHP grid, note that the current web page will reload when you page or sort the grid, with the
  * same $_GET parameters but no $_POST information. If you need 2 grids on one page, then you must define a different
  * id in the options for each grid.
  * 
  * For summary reports, the user can optionally setup clicking functionality so that another report is called when the user clicks on the grid.
  * 
  * The grid operation will be handled by AJAX calls when possible to avoid reloading the web page.
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
  * Name of the report file or singular form of the table/view.</li>
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
  *      values for caption, visibility_field, url, urlParams, class, img and javascript. The visibility field is an optional
  *      name of a field in the data which contains true or false to define the visibility of this action. The javascript, url
  *      and urlParams values can all use the field names from the report in braces as substitutions, for example {id} is replaced
  *      by the value of the field called id in the respective row. In addition, the url can use {currentUrl} to represent the
  *      current page's URL, {rootFolder} to represent the folder on the server that the current PHP page is running from, {input_form}
  *     (provided it is returned by the report) to represent the path to the form that created the record, and
  *      {imageFolder} for the image upload folder. Because the javascript may pass the field values as parameters to functions,
  *      there are escaped versions of each of the replacements available for the javascript action type. Add -escape-quote or
  *      -escape-dblquote to the fieldname for quote escaping, or -escape-htmlquote/-escape-htmldblquote for escaping quotes in HTML
  *      attributes. For example this would be valid in the action javascript: foo("{bar-escape-dblquote}");
  *      even if the field value contains a double quote which would have broken the syntax. Set img to the path to an image to use an 
  *      image for the action instead of a text caption - the caption then becomes the image's title. The image path can contain 
  *      {rootFolder} to be replaced by the root folder of the site, in this case it excludes the path parameter used in Drupal when 
  *      dirty URLs are used (since this is a direct link to a URL). 
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
  *      '<div>Arnica montana was recorded on 14/04/2004.<br/>Growing on a mountain pasture</div>'
  *      template
  *  - img: set to true if the column contains a path to an image (relative to the warehouse upload folder). If so then the
  *      path is replaced by an image thumbnail with a fancybox zoom to the full image. Multiple images can be included by
  *      separating each path with a comma. 
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
  * <li><b>linkFilterToMap</b>
  * Default true but requires a rowId to be set. If true, then filtering the grid causes the map to also filter.
  * </li>
  * <li><b>zoomMapToOutput</b>
  * Default true. When combined with sendOutputToMap=true, defines that the map will automatically zoom to show the records.
  * </li>
  * <li><b>rowClass</b>
  * A CSS class to add to each row in the grid. Can include field value replacements in braces, e.g. {certainty} to construct classes from
  * field values, e.g. to colour rows in the grid according to the data.
  * </li>
  * <li><b>callback</b>
  * Set to the name of a JavaScript function that should already exist which will be called each time the grid reloads (e.g. when paginating or sorting).
  * </li>
  * <li><b>linkToReportPath</b>
  * Allows drill down into reports. Holds the URL of the report that is called when the user clicks on 
  * a report row. When this is not set, the report click functionality is disabled. The replacement #param# will
  * be filled in with the row ID of the clicked on row.
  * </li>
  * <li><b>ajax</b>
  * If true, then the first page of records is loaded via an AJAX call after the initial page load, otherwise
  * they are loaded using PHP during page build. This means the grid load will be delayed till after the 
  * rest of the page, speeding up the load time of the rest of the page. If used on a tabbed output then
  * the report will load when the tab is first viewed.
  * Default false.
  * </li>
  * <li><b>autoloadAjax</b>
  * Set to true to prevent autoload of the grid in Ajax mode. You would then need to call the grid's ajaxload() method 
  * when ready to load. This might be useful e.g. if a parameter is obtained from some other user input beforehand.
  * Default false.
  * </li>
  * <li><b>pager</b>
  * Include a pager? Default true. Removing the pager can have a big improvement on performance where there are lots of records to count.
  * </li>
  * </ul>
  */
  public static function report_grid($options) {
    global $indicia_templates;
    self::add_resource('fancybox');
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    $options = self::get_report_grid_options($options);
    $extras = self::get_report_sorting_paging_params($options, $sortAndPageUrlParams);
    if ($options['ajax'])
      $options['extraParams']['limit']=0;
    self::request_report($response, $options, $currentParamValues, $options['pager'], $extras);
    if ($options['ajax'])
      unset($options['extraParams']['limit']);
    if (isset($response['error'])) return $response['error']; 
    $r = self::params_form_if_required($response, $options, $currentParamValues);
    // return the params form, if that is all that is being requested, or the parameters are not complete.
    if ((isset($options['paramsOnly']) && $options['paramsOnly']) || !isset($response['records'])) return $r;
    $records = $response['records'];
    self::report_grid_get_columns($response, $options);
    $pageUrl = self::report_grid_get_reload_url($sortAndPageUrlParams);
    $thClass = $options['thClass'];
    $r .= $indicia_templates['loading_overlay'];
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
          if (empty($field['display']) && empty($field['fieldname']))
            $caption='';
          else
            $caption = lang::get(empty($field['display']) ? $field['fieldname'] : $field['display']);
          if (isset($field['fieldname']) && !(isset($field['img']) && $field['img']=='true')) {
            if (empty($field['orderby'])) $field['orderby']=$field['fieldname'];
            $sortLink = $sortUrl.$sortAndPageUrlParams['orderby']['name'].'='.$field['orderby'];
            // reverse sort order if already sorted by this field in ascending dir
            if ($sortAndPageUrlParams['orderby']['value']==$field['orderby'] && $sortAndPageUrlParams['sortdir']['value']!='DESC')
              $sortLink .= '&'.$sortAndPageUrlParams['sortdir']['name']."=DESC";
            $sortLink=htmlspecialchars($sortLink);
            // store the field in a hidden input field
            $captionLink = "<input type=\"hidden\" value=\"".$field['orderby']."\"/><a href=\"$sortLink\" rel=\"nofollow\" title=\"Sort by $caption\">$caption</a>";
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
          if (isset($field['datatype']) && !empty($caption)) {
            switch ($field['datatype']) {
              case 'text':
                $title=lang::get("$caption text begins with ... search. Use * as a wildcard.");
                break;
              case 'date':
                $title=lang::get("$caption search. Search for an exact date or use a vague<br/> date such as a year to select a range of dates.");
                break;
              default: $title=lang::get("$caption search. Either enter an exact number, use >, >=, <, or <= before the number to filter for ".
                      "$caption more or less than your search value, or enter a range such as 1000-2000.");
            }
            $title = htmlspecialchars(lang::get('Type here to filter.').' '.$title);
            //The filter's input id includes the grid id ($options['id']) in its id as there maybe more than one grid and we need to make the id unique.
            $filterRow .= "<th><input title=\"$title\" type=\"text\" class=\"col-filter\" id=\"col-filter-".$field['fieldname']."-".$options['id']."\"/></th>";
            $wantFilterRow = true;
          } else
            $filterRow .= '<th></th>';
        }
      }
      $r .= "</tr>";
      if ($wantFilterRow)
        $r .= "<tr class=\"filter-row\" title=\"".lang::get('Use this row to filter the grid')."\">$filterRow</tr>\n";
      $r .= "</thead>\n";
    }
    $currentUrl = self::get_reload_link_parts();
    // automatic handling for Drupal clean urls.
    $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
    $rootFolder = self::getRootFolder() . (empty($pathParam) ? '' : "?$pathParam=");
    // amend currentUrl path if we have drupal dirty URLs so javascript will work properly
    if ($pathParam==='q' && isset($currentUrl['params']['q']) && strpos($currentUrl['path'], '?')===false) {
      $currentUrl['path'] = $currentUrl['path'].'?q='.$currentUrl['params']['q'];
    }
    $r .= '<tfoot>';
    $r .= '<tr><td colspan="'.count($options['columns'])*$options['galleryColCount'].'">'.self::output_pager($options, $pageUrl, $sortAndPageUrlParams, $response).'</td></tr>'.
    $extraFooter = '';
    if (isset($options['footer']) && !empty($options['footer'])) {
      $footer = str_replace(array('{rootFolder}', '{currentUrl}'),
          array($rootFolder, $currentUrl['path']), $options['footer']);
      $extraFooter .= '<div class="left">'.$footer.'</div>';
    }
    if (isset($options['downloadLink']) && $options['downloadLink'] && (count($records)>0 || $options['ajax'])) {
      $downloadOpts = array_merge($options);
      unset($downloadOpts['itemsPerPage']);
      $extraFooter .= '<div class="right">'.self::report_download_link($downloadOpts).'</div>';
    }
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
            'rootFolder'=>$rootFolder,
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
        // decode any data in columns that are defined as containing JSON
        foreach ($options['columns'] as $field) {
          if (isset($field['json']) && $field['json'] && isset($row[$field['fieldname']])) {
            $row = array_merge(json_decode($row[$field['fieldname']], true), $row);
          }
        }
        foreach ($options['columns'] as $field) {
          $classes=array();
          if ($options['sendOutputToMap'] && isset($field['mappable']) && ($field['mappable']==='true' || $field['mappable']===true)) {
            $data = json_encode($row + array('type'=>'linked'));
            $addFeaturesJs.= "div.addPt(features, ".$data.", '".$field['fieldname']."', {\"type\":\"circle\"}".
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
          	$value="<form id=\"updateform-".$updateformID."\" method=\"post\" action=\"".iform_ajaxproxy_url(null, $field['update']['method'])."\"><input type=\"hidden\" name=\"website_id\" value=\"".$field['update']['website_id']."\"><input type=\"hidden\" name=\"transaction_id\" value=\"updateform-".$updateformID."-field\"><input id=\"updateform-".$updateformID."-field\" name=\"".$field['update']['tablename'].":".$field['update']['fieldname']."\" class=\"update-input ".(isset($field['update']['class']) ? $field['update']['class'] : "")."\" value=\"".(isset($field['fieldname']) && isset($row[$field['fieldname']]) ? $row[$field['fieldname']] : '')."\">";
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
          if (isset($field['img']) && $field['img']=='true' && !empty($value)) {
            $imgs = explode(',',$value);
            $value='';
            $imgclass=count($imgs)>1 ? 'multi' : 'single';
            foreach($imgs as $img) {
              if (preg_match('/^http(s)?:\/\/(www\.)?(?P<site>[a-z]+)/', $img, $matches)) {
                // http, means an external file
                $value .= "<a href=\"$img\" class=\"social-icon $matches[site]\"></a>";
              } else {
                $value .= "<a href=\"$imagePath$img\" class=\"fancybox $imgclass\"><img src=\"$imagePath"."thumb-$img\" /></a>";
              }
            }
          }
          $r .= "<td$class>$value</td>\n";
        }
        if ($rowIdx % $options['galleryColCount']==$options['galleryColCount']-1) {
          $rowInProgress=false;
          $r .= '</tr>';
        }
        $altRowClass = empty($altRowClass) ? $options['altRowClass'] : '';
        $outputCount++;
      }
      // implement links from the report grid rows if configuration options set
      if (isset($options['linkToReportPath'])) {
        $path=$options['linkToReportPath'];
        if (isset($options['rowId'])) {
          //if the user clicks on a summary table row then open the report specified using the row ID as a parameter.
            self::$javascript .= "
              $('#".$options['id']." tbody').click(function(evt) {
                var tr=$(evt.target).parents('tr')[0], rowId=tr.id.substr(3);
                window.location='$path'.replace(/#param#/g, rowId);
              });
            ";
        }
      }
      if ($rowInProgress)
        $r .= '</tr>';
    } else {
      $r .= '<tr><td></td></tr>';
    }
    $r .= "</tbody></table>\n";
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
    if ($options['sendOutputToMap']) {
      self::addFeaturesLoadingJs($addFeaturesJs, '', '{"strokeColor":"#ff0000","fillColor":"#ff0000","strokeWidth":2}', 
          '', '', $options['zoomMapToOutput']);
    }
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
  id: '$options[id]',
  mode: '$options[mode]',
  dataSource: '" . str_replace('\\','/',$options['dataSource']) . "',
  view: '$options[view]',
  itemsPerPage: $options[itemsPerPage],
  auth_token: '{$options['readAuth']['auth_token']}',
  nonce: '{$options['readAuth']['nonce']}',
  callback: '$options[callback]',
  url: '$warehouseUrl',
  reportGroup: '$options[reportGroup]',
  autoParamsForm: '$options[autoParamsForm]',
  rootFolder: '" . self::getRootFolder() . "',
  imageFolder: '" . self::get_uploaded_image_folder() . "',
  currentUrl: '$currentUrl[path]',
  rowId: '" . (isset($options['rowId']) ? $options['rowId'] : '') . "',
  galleryColCount: $options[galleryColCount],
  pagingTemplate: '$indicia_templates[paging]',
  pathParam: '$pathParam',
  sendOutputToMap: ".((isset($options['sendOutputToMap']) && $options['sendOutputToMap']) ? 'true' : 'false').",
  linkFilterToMap: ".(!empty($options['rowId']) && $options['linkFilterToMap'] ? 'true' : 'false').",
  msgRowLinkedToMapHint: '".lang::get('Click the row to highlight the record on the map. Double click to zoom in.')."',
  altRowClass: '$options[altRowClass]'";
      if (isset($options['sharing'])) {
        if (!isset($options['extraParams']))
          $options['extraParams']=array();
        $options['extraParams']['sharing']=$options['sharing'];
      }
      if (!empty($options['rowClass']))
        self::$javascript .= ",\n  rowClass: '".$options['rowClass']."'";
      if (isset($options['extraParams']))
        self::$javascript .= ",\n  extraParams: ".json_encode(array_merge($options['extraParams'], $currentParamValues));
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
    if ($options['ajax'] && $options['autoloadAjax']) 
      self::$onload_javascript .= "indiciaData.reports.$group.$uniqueName.ajaxload();\n";
    return $r;
  }

 /**
  * Requests the data for a report from the reporting services.
  * @param array $response Data to be returned.
  * @param array $options Options array defining the report request.
  * @param array $currentParamValues Array of current parameter values, e.g. the contents of 
  * parameters form.
  * @param boolean $wantCount Set to true if a count of total results (ignoring limit) is required
  * in the response.
  * @param string $extras Set any additional URL filters if required, e.g. taxon_list_id=1 to filter
  * for taxon list 1.  
  */
  private static function request_report(&$response, &$options, &$currentParamValues, $wantCount, $extras='') {
    $extras .= '&wantColumns=1&wantParameters=1';
    if ($wantCount)
      $extras .= '&wantCount=1';
    // any extraParams are fixed values that don't need to be available in the params form, so they can be added to the
    // list of parameters to exclude from the params form.
    if (array_key_exists('extraParams', $options) && array_key_exists('ignoreParams', $options))
      $options['paramsToExclude'] = array_merge($options['ignoreParams'], array_keys($options['extraParams']));
    elseif (array_key_exists('extraParams', $options))
      $options['paramsToExclude'] = array_keys($options['extraParams']);
    elseif (array_key_exists('ignoreParams', $options))
      $options['paramsToExclude'] = array_merge($options['ignoreParams']);
    if (array_key_exists('paramsToExclude', $options))
      $extras .= '&paramsFormExcludes='.json_encode($options['paramsToExclude']);
    // specify the view variant to load, if loading from a view
    if ($options['mode']=='direct') $extras .= '&view='.$options['view'];
    $currentParamValues = self::get_report_grid_current_param_values($options);
    // if loading the parameters form only, we don't need to send the parameter values in the report request but instead
    // mark the request not to return records
    if (isset($options['paramsOnly']) && $options['paramsOnly'])
      $extras .= '&wantRecords=0&wantCount=0&wantColumns=0';
    else
      $extras .= '&'.self::array_to_query_string($currentParamValues, true);
    // allow URL parameters to override any extra params that are set. Default params
    // are handled elsewhere.
    if (isset($options['extraParams']) && isset($options['reportGroup'])) {
      foreach ($options['extraParams'] as $key=>&$value) {
        // allow URL parameters to override the provided params
        if (isset($_REQUEST[$options['reportGroup'] . '-' . $key]))
          $value = $_REQUEST[$options['reportGroup'] . '-' . $key];
      }
    }
    $response = self::get_report_data($options, $extras);
  }

  /**
   * Returns the parameters form for a report, only if it is needed because there are
   * parameters without preset values to fill in.
   * @param array $response Response from the call to the report services, which may contain
   * a parameter request.
   * @param array $options Array of report options.
   * @param string $currentParamValues Array of current parameter values, e.g. the contents
   * of a submitted parameters form.
   */
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
   * Output pagination links.
   * @param array $options Report options array.
   * @param string $pageUrl The URL of the page to reload when paginating (normally the current page). Only
   * used when JavaScript is disabled.
   * @param array $sortAndPageUrlParams Current parameters for the page and sort order.
   * @param array $response Response from the call to reporting services, which we are paginating.
   * @return string The HTML for the paginator.
   */
  private static function output_pager($options, $pageUrl, $sortAndPageUrlParams, $response) {
    if ($options['pager']) {
      global $indicia_templates;
      $pagLinkUrl = $pageUrl . ($sortAndPageUrlParams['orderby']['value'] ? $sortAndPageUrlParams['orderby']['name'].'='.$sortAndPageUrlParams['orderby']['value'].'&' : '');
      $pagLinkUrl .= $sortAndPageUrlParams['sortdir']['value'] ? $sortAndPageUrlParams['sortdir']['name'].'='.$sortAndPageUrlParams['sortdir']['value'].'&' : '';
      if (!isset($response['count'])) {
        $r = self::simple_pager($options, $sortAndPageUrlParams, $response, $pagLinkUrl);
      } else {
        $r = self::advanced_pager($options, $sortAndPageUrlParams, $response, $pagLinkUrl);
      }
      $r = str_replace('{paging}', $r, $indicia_templates['paging_container']);
      return $r;
    }
    else
      return '';
  }

 /**
  * Creates the HTML for the simple version of the pager.
  * @param array $options Report options array.
  * @param array $sortAndPageUrlParams Current parameters for the page and sort order.
  * @param array $response Response from the call to reporting services, which we are paginating.
  * @param string $pagLinkUrl The basic URL used to construct page reload links in the pager.
  * @return string The HTML for the simple paginator.
  */
  private static function simple_pager($options, $sortAndPageUrlParams, $response, $pagLinkUrl) {
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
  * @param array $options Report options array.
  * @param array $sortAndPageUrlParams Current parameters for the page and sort order.
  * @param array $response Response from the call to reporting services, which we are paginating.
  * @param string $pagLinkUrl The basic URL used to construct page reload links in the pager.
  * @return string The HTML for the advanced paginator.
  */
  private static function advanced_pager($options, $sortAndPageUrlParams, $response, $pagLinkUrl) {
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
  * <p>For summary reports, the user can optionally setup clicking functionality so that another report is called when the user clicks on the chart.</p>
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
  * <li><b>linkToReportPath</b>
  * Allows drill down into reports. Holds the URL of the report that is called when the user clicks on 
  * a chart data item. When this is not set, the report click functionality is disabled. The path will have replacement
  * tokens replaced where the token is the report output field name wrapped in # and the token will be replaced by the 
  * report output value for the row clicked on. For example, you can specify id=#id# in the URL to define a URL 
  * parameter to receive the id field in the report output. In addition, create a global JavaScript function 
  * on the page called handle_chart_click_path and this will be called with the path, series index, point index and row data as parameters. It can
  * then return the modified path, so you can write custom logic, e.g. to map the series index to a specific report filter.
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
    if (empty($options['rendererOptions']))
      $options['rendererOptions'] = array();
    if (empty($options['legendOptions']))
      $options['legendOptions'] = array();
    if (empty($options['seriesOptions']))
      $options['seriesOptions'] = array();
    if (empty($options['axesOptions']))
      $options['axesOptions'] = array();
    $standardReportOptions = self::get_report_grid_options($options);   
    $options = array_merge($standardReportOptions,$options);
    $currentParamValues = self::get_report_grid_current_param_values($options);
    //If we want the report_chart to only return the parameters control, then don't provide
    //the report with parameters so that it will return parameter requests for all the 
    //parameters which can then be displayed on the screen.
    //Use != 1, as am not sure what style all the existing code would provide the $options['paramsOnly']
    //as being set to true.
    if (empty($options['paramsOnly']) || $options['paramsOnly']!=1)
      $options['extraParams'] = array_merge($options['extraParams'],$currentParamValues);
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
    // other chart options
    if (isset($options['stackSeries']) && $options['stackSeries'])
      $opts[] = 'stackSeries: true';
    if(isset($options['linkToReportPath'])) 
      // if linking to another report when clicked, store the full data so we can pass it as a parameter to the report
      self::$javascript .= "indiciaData.reportData=[];\n";
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
      $r = self::params_form_if_required($data, $options, $currentParamValues);
      //If we don't have any data for the chart, or we only want to display the params form,
      //then return $r before we even reach the chart display code.
      //Use '==' as the comparison once again as am not sure what style the exiting code will provide
      //$options['paramsOnly'] as being true.
      if ((!empty($options['paramsOnly']) && ($options['paramsOnly'])==1) || !isset($data[0])) {
        return $r;
      }
      if (isset($data['parameterRequest']))
        $r .= self::build_params_form(array_merge($options, array('form'=>$data['parameterRequest'], 'defaults'=>$params)), $hasVisibleContent);

      $lastRequestSource = $options['dataSource'];
      $values=array();
      $xLabelsForSeries=array();
      //The options to pass to the report when the user clicks on a summary are held in an array whose keys match
      //the number of the bar column (this is different to a pie chart). We use this variable to return the data 
      //from the correct key in the array.
      $trackerForBarGraph = 1;
      $jsData = array();
      foreach ($data as $row) {
        if (isset($options['xValues']))
          // 2 dimensional data
          $values[] = array(self::string_or_float($row[$options['xValues']]), self::string_or_float($row[$options['yValues']]));
        else {
          // 1 dimensional data, so we should have labels. For a pie chart these are use as x data values. For other charts they are axis labels.
          if ($options['chartType']=='pie') {
            $values[] = array(lang::get($row[$options['xLabels']]), self::string_or_float($row[$options['yValues']]));
          } else {
            $values[] = self::string_or_float($row[$options['yValues']]);
            if (isset($options['xLabels']))
              $xLabelsForSeries[] = $row[$options['xLabels']];
          }
        }
        // pie charts receive click information with the pie segment label. Bar charts receive the bar index.
        if ($options['chartType']==='pie')
          $jsData[$row['name']] = $row;
        else
          $jsData[] = $row;
      }  
      // each series will occupy an entry in $seriesData
      $seriesData[] = $values;
      if(isset($options['linkToReportPath']))
        self::$javascript .= "indiciaData.reportData[$idx]=" . json_encode($jsData) . ";\n";
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
    self::$javascript .= "$.jqplot('".$options['id']."', " . json_encode($seriesData) . ", \n{".implode(",\n", $opts)."});\n";
     //once again we only include summary report clicking functionality if user has setup the appropriate options
    if(isset($options['linkToReportPath'])) {
      //open the report, note that data[0] varies depending on whether we are using a pie or bar. But we have
      //saved the data to the array twice already to handle this
      // Note the data[0] is a pie label, or a 1 indexed bar index.
      self::$javascript .= "$('#$options[id]').bind('jqplotDataClick', 
  function(ev, seriesIndex, pointIndex, data) {
    var path='$options[linkToReportPath]';
    var rowId = " . ($options['chartType']==='pie' ? 'data[0]' : 'data[0]-1') . ";
    if (typeof handle_chart_click_path!=='undefined') {
      // custom path handler
      path=handle_chart_click_path(path, seriesIndex, pointIndex, indiciaData.reportData[seriesIndex][rowId]);
    }
    // apply field replacements from the report row that we clicked on
    $.each(indiciaData.reportData[seriesIndex][rowId], function(field, val) {
      path = path.replace(new RegExp('#'+field+'#', 'g'), val);
    });
    window.location=path.replace(/#series#/g, seriesIndex);
  }
);\n";
    }
    self::$javascript .= "$('#chartdiv').bind('jqplotDataHighlight', function(ev, seriesIndex, pointIndex, data) {
      $('table.jqplot-table-legend td').removeClass('highlight');
      $('table.jqplot-table-legend td').filter(function() { 
        return this.textContent == data[0]; 
      }).addClass('highlight');
  });
  $('#chartdiv').bind('jqplotDataUnhighlight', function(ev, seriesIndex, pointIndex, data) {
    $('table.jqplot-table-legend td').removeClass('highlight');
  });\n";
    $r .= '<div class="'.$options['class'].'" style="width:'.$options['width'].'; ">';
    if (isset($options['title']))
      $r .= '<div class="'.$options['headerClass'].'">'.$options['title'].'</div>';
    $r .= '<div id="'.$options['id'].'" style="height:'.$options['height'].'px;width:'.$options['width'].'px; "></div>'."\n";
    $r .= "</div>\n";
    return $r;
  }
  
  /**
   * Json_encode puts quotes around numbers read from the db, since they end up in string objects.
   * So, convert them back to numbers. If the value is a string, then it is run through translation
   * and returned as a string.
   */
  private static function string_or_float($value) {
    return (string)((float) $value) == $value ? (float) $value : lang::get($value);
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
   * @param array $options Options passed to the report control, which should contain the column definitions.
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
    $r .= '<input type="submit" value="Filter" class="run-filter ui-corner-all ui-state-default"/>'.
        '<button class="clear-filter" style="display: none">Clear</button>';
    $r .= "</form>\n";
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
    $options = array_merge(array('class'=>''), $options);
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
          if ($outputBand) {
            $row['imageFolder'] = self::get_uploaded_image_folder();
            $r .= self::apply_replacements_to_template($band['content'], $row);
          }
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
  * <li><b>dataSourceLoRes</b><br/>
  * Name of the report file or table/view to use when zoomed out. For example this might aggregate records to 1km or 10km grid squares.</li>
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
  * <li>locationParams<br/>
  * Set to a comma seperated list of report parameters that are associated with locations. For instance, this might
  * be location_id,region_id. The system then knows to zoom the map when these parameters are supplied.
  * The bigger locations should always appear to the right in the list so that if multiple parameters are filled in by the user
  * the system will always zoom to the biggest one. Default location_id.</li>
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
  * features. Note that the row ID can point to either an integer value, or a list of integers separated by commas if the rows returned
  * by the report map to features which are shared by multiple records.
  * </li>
  * <li><b>ajax</b>
  * Optional. Set to true to load the records onto the map using an AJAX request after the initial page load. Not relevant for 
  * GeoServer layers. Note that when ajax loading the map, the map will not automatically zoom to the layer extent.
  * </li>
  * <li><b>zoomMapToOutput</b>
  * Default true. Defines that the map will automatically zoom to show the records.
  * </li>
  * <li><b>featureDoubleOutlineColour</b>
  * If set to a CSS colour class, then feature outlines will be doubled up, for example a 1 pixel dark outline
  * over a 3 pixel light outline, creating a line halo effect which can make the map clearer.
  * </li>
  * </ul>
  */
  public static function report_map($options) {
    $options = array_merge(array(
      'clickable' => true,
      'clickableLayersOutputMode' => 'popup',
      'clickableLayersOutputDiv' => '',
      'displaySymbol'=>'vector',
      'ajax'=>false,
      'extraParams'=>'',
      'featureDoubleOutlineColour'=>'',
      'dataSourceLoRes'=>'',
    ), $options);
    $options = self::get_report_grid_options($options);
    // keep track of the columns in the report output which we need to draw the layer
    $colsToInclude=array();
    if (empty($options['geoserverLayer'])) {
      if ($options['ajax']) 
        // just load the report structure, as Ajax will load content later
        $options['extraParams']['limit']=0;
      self::request_report($response, $options, $currentParamValues, false, '');
      if (isset($response['error'])) return $response['error'];
      $r = self::params_form_if_required($response, $options, $currentParamValues);
      // return the params form, if that is all that is being requested, or the parameters are not complete.
      if ($options['paramsOnly'] || !isset($response['records']))
        return $r;
      $records = $response['records'];
      // find the geom column
      foreach($response['columns'] as $col=>$cfg) {
        if (isset($cfg['mappable']) && $cfg['mappable']=='true') {
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
    if (!isset($response['parameterRequest']) || count(array_intersect_key($currentParamValues, $response['parameterRequest']))==count($response['parameterRequest'])) {
      if (empty($options['geoserverLayer'])) {
        // we are doing vector reporting via indicia services
        // first we need to build a style object which respects columns in the report output that define style settings for each vector.
        // default features are blue and red if selected
        $defsettings = array(
          'fillColor'=> '#0000ff',
          'strokeColor'=> '#0000ff',
          'strokeWidth'=>empty($options['featureDoubleOutlineColour']) ? "\${getstrokewidth}" : 1,
          'fillOpacity'=>"\${getfillopacity}",
          'strokeOpacity'=>0.8,
          'pointRadius'=>5,
          'graphicZIndex'=>"\${getgraphiczindex}");
        $selsettings = array_merge($defsettings, array(
          'fillColor'=> '#ff0000',
          'strokeColor'=> '#ff0000',
          'strokeOpacity'=>0.9)
        );
        $defStyleFns=array();
        $selStyleFns=array();
        // default fill opacity, more opaque if selected, and gets more transparent as you zoom in.
        $defStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
          return Math.max(0, 0.4-feature.layer.map.zoom/100);
        }";
        // when selected, a little bit more opaque
        $selStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
          return Math.max(0, 0.7-feature.layer.map.zoom/100);
        }";
        // default z index, smaller objects on top
        $defStyleFns['graphicZIndex'] = "getgraphiczindex: function(feature) {
          return Math.round(feature.geometry.getBounds().left - feature.geometry.getBounds().right)+100000;
        }";
        // when selected, move objects upwards
        $selStyleFns['graphicZIndex'] = "getgraphiczindex: function(feature) {
          return Math.round(feature.geometry.getBounds().left - feature.geometry.getBounds().right)+200000;
        }";
        foreach($response['columns'] as $col=>$def) {
          if (!empty($def['feature_style'])) {
            if ($def['feature_style']==='fillOpacity') {
              // replace the fill opacity functions to use a column value, with the same +0.3 change
              // when selected
              $defStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
                return Math.max(0, feature.attributes.$col-feature.layer.map.zoom/100);
              }";
              $selStyleFns['fillOpacity'] = "getfillopacity: function(feature) {
                return Math.max(0, feature.attributes.$col-feature.layer.map.zoom/100+0.3);
              }";
            } elseif ($def['feature_style']==='graphicZIndex') {
              // replace the default z index with the column value, using an fn to add 1000 when selected
              $defsettings['graphicZIndex'] = '${'.$col.'}';
              $selStyleFns['graphicZIndex'] = "getgraphiczindex: function(feature) {
                return feature.attributes.$col+1000;
              }";
              $selsettings['graphicZIndex'] = '${getgraphiczindex}';
            } else {
              // found a column that outputs data to input into a feature style parameter. ${} syntax is explained at http://docs.openlayers.org/library/feature_styling.html.
              $defsettings[$def['feature_style']] = '${'.$col.'}';
              if ($def['feature_style']!=='strokeColor')
                $selsettings[$def['feature_style']] = '${'.$col.'}';
            }
          }
        }
        if ($options['displaySymbol']!=='vector')
          $defsettings['graphicName']=$options['displaySymbol'];
        // The following function uses the strokeWidth to pad out the squares which go too small when zooming the map out. Points 
        // always display the same size so are no problem. Also, no need if using a double outline.
        if (empty($options['featureDoubleOutlineColour'])) {
          $strokeWidthFn = "getstrokewidth: function(feature) {
            var width=feature.geometry.getBounds().right - feature.geometry.getBounds().left,
              strokeWidth=(width===0) ? 1 : %d - (width / feature.layer.map.getResolution());
            return (strokeWidth<%d) ? %d : strokeWidth;
          }";
          $defStyleFns['getStrokeWidth'] = sprintf($strokeWidthFn, 9, 2, 2);
          $selStyleFns['getStrokeWidth'] = sprintf($strokeWidthFn, 10, 3, 3);
        }
        if (isset($options['valueOutput'])) {
          foreach($options['valueOutput'] as $type => $outputdef) {
            $value = $outputdef['valueField'];
            // we need this value in the output
            $colsToInclude[$value]='';
            if (preg_match('/{(?P<name>.+)}/', $outputdef['minValue'], $matches)) {
              $minvalue = 'feature.data.'.$matches['name'];
              $colsToInclude[$matches['name']]='';
            } else
              $minvalue = $outputdef['minValue'];
            if (preg_match('/{(?P<name>.+)}/', $outputdef['maxValue'], $matches)) {
              $maxvalue = 'feature.data.'.$matches['name'];
              $colsToInclude[$matches['name']]='';
            } else
              $maxvalue = $outputdef['maxValue'];
            $from = $outputdef['from'];
            $to = $outputdef['to'];
            if (substr($type, -5)==='Color')
              $defStyleFns[$type] = "get$type: function(feature) { \n".
                  "var from_r, from_g, from_b, to_r, to_g, to_b, r, g, b, ratio = Math.pow((feature.data.$value - $minvalue) / ($maxvalue - $minvalue), .2); \n".
                  "from_r = parseInt('$from'.substring(1,3),16);\n".
                  "from_g = parseInt('$from'.substring(3,5),16);\n".
                  "from_b = parseInt('$from'.substring(5,7),16);\n".
                  "to_r = parseInt('$to'.substring(1,3),16);\n".
                  "to_g = parseInt('$to'.substring(3,5),16);\n".
                  "to_b = parseInt('$to'.substring(5,7),16);\n".
                  "r=Math.round(from_r + (to_r-from_r)*ratio);\n".
                  "g=Math.round(from_g + (to_g-from_g)*ratio);\n".
                  "b=Math.round(from_b + (to_b-from_b)*ratio);\n".
                  "return 'rgb('+r+','+g+','+b+')';\n".
                '}';
            else
              $defStyleFns[$type] = "get$type: function(feature) { \n".
                  "var ratio = Math.pow((feature.data.$value - $minvalue) / ($maxvalue - $minvalue), .2); \n".
                  "return $from + ($to-$from)*ratio; \n".
                  '}';
            $defsettings[$type]="\${get$type}";
          }
        }
        $selStyleFns = implode(",\n", array_values(array_merge($defStyleFns, $selStyleFns)));
        $defStyleFns = implode(",\n", array_values($defStyleFns));
        // convert these styles into a JSON definition ready to feed into JS.
        $defsettings = json_encode($defsettings);
        $selsettings = json_encode($selsettings);
        $addFeaturesJs = "";        
        // No need to pass the default type of vector display, so use empty obj to keep JavaScript size down
        $opts = $options['displaySymbol']==='vector' ? '{}' : json_encode(array('type'=>$options['displaySymbol']));
        if ($options['clickableLayersOutputMode']<>'popup' && $options['clickableLayersOutputMode']<>'div') {
          // If we don't need record data for every row for feature clicks, then only include necessary columns to minimise JS
          $colsToInclude['occurrence_id']='';
          $colsToInclude[$wktCol]='';
          foreach ($response['columns'] as $name=>$def) {
            if (isset($def['feature_style']))
              $colsToInclude[$name] = '';
          }
        }
        $defStyleFns = ", {context: {\n    $defStyleFns\n  }}";
        $selStyleFns = ", {context: {\n    $selStyleFns\n  }}";
        if ($options['ajax']) {
          self::$javascript .= "mapInitialisationHooks.push(function(div) {\n".
            "  if (typeof indiciaData.reports!==\"undefined\") {\n" .
            "    $.each(indiciaData.reports.".$options['reportGroup'].", function(idx, grid) {\n" .
            "      grid.mapRecords('".$options['dataSource']."', '".$options['dataSourceLoRes']."');\n" .
            "      return false;\n" . // only need the first grid to draw the map. 
            "    });\n" .
            "  }\n";
          if ($options['dataSourceLoRes']) {
            // hook up a zoom and pan handler so we can switch reports
            self::$javascript .= "  div.map.events.on({'moveend': function(){\n".
              "    if (!indiciaData.disableMapDataLoading) {\n" .
              "      $.each(indiciaData.reports.".$options['reportGroup'].", function(idx, grid) {\n" .
              "        indiciaData.selectedRows=[];\n".
              "        $.each($(grid).find('tr.selected'), function(idx, tr) {\n".
              "          indiciaData.selectedRows.push($(tr).attr('id').replace(/^row/, ''));\n".
              "        });\n".
              "        grid.mapRecords('".$options['dataSource']."', '".$options['dataSourceLoRes']."', true);\n" .
              "        return false;\n" . // only need the first grid to draw the map. 
              "      });\n".
		          "    }\n".
              "  }});\n";
          }
          self::$javascript .= "});\n";
        } else {
          $geoms = array();
          foreach ($records as $record) { 
            if (!empty($record[$wktCol])) {
              $record[$wktCol]=preg_replace('/\.(\d+)/', '', $record[$wktCol]);
              // rather than output every geom separately, do a list of distinct geoms to minify the JS
              if (!$geomIdx = array_search('"'.$record[$wktCol].'"', $geoms)) {          
                $geoms[] = '"'.$record[$wktCol].'"';
                $geomIdx = count($geoms)-1;
              }
              $record[$wktCol] = $geomIdx;
              if (!empty($colsToInclude)) {
                $colsToInclude[$wktCol]='';
                $record = array_intersect_key($record, $colsToInclude); 
              }
              $addFeaturesJs.= "div.addPt(features, ".json_encode($record).", '$wktCol', $opts" . (empty($options['rowId']) ? '' : ", '" . $record[$options['rowId']] . "'") . ");\n";
            }
          }
          self::$javascript .= 'indiciaData.geoms=['.implode(',',$geoms)."];\n";
        }
        self::addFeaturesLoadingJs($addFeaturesJs, $defsettings, $selsettings, $defStyleFns, $selStyleFns, $options['zoomMapToOutput'] && !$options['ajax'], $options['featureDoubleOutlineColour']);
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
      $setLocationJs = '';
      //When the user uses a page like dynamic report explorer with a map, then there might be more than
      //one parameter that is a location based parameter. For instance, Site and Region might be seperate parameters,
      //the user should supply options to the map that specify which parameters are location based, this is used by the 
      //code below to allow the map to show records for those locations and to zoom the map appropriately.
      //The bigger location types should always appear to the right in the list so that if multiple parameters are filled in by the user 
      //the system will always zoom to the biggest one (but still show records for any smaller locations inside the bigger one).
      //If the user chooses two locations that don't intersect then no records are shown as the records need to satisfy both criteria.
      
      //Default is that there is just a location_id parameter if user doesn't give options.
      if (!empty($currentParamValues['location_id']))
        $locationParamVals=array($currentParamValues['location_id']);
      //User has supplied location parameter options.
      if (!empty($options['locationParams'])) {
        $locationParamVals=array();
        $locationParamsArray = explode(',',$options['locationParams']);
        //Create an array of the user's supplied location parameters.
        foreach ($locationParamsArray as $locationParam) {
          if (!empty($currentParamValues[$locationParam]))
            array_push($locationParamVals,$currentParamValues[$locationParam]);
        }
      }
      if (!empty($locationParamVals)) {
        foreach ($locationParamVals as $locationParamVal) { 
          $location=data_entry_helper::get_population_data(array(
            'table'=>'location',
            'nocache'=>true,
            'extraParams'=>$options['readAuth'] + array('id'=>$locationParamVal,'view'=>'detail')
          ));        
          if (count($location)===1) {
            $location=$location[0];
            $setLocationJs = "\n  opts.initialFeatureWkt='".(!empty($location['boundary_geom']) ? $location['boundary_geom'] : $location['centroid_geom'])."';";
          }
        }
      }
      report_helper::$javascript.= "
mapSettingsHooks.push(function(opts) { $setLocationJs
  opts.reportGroup = '".$options['reportGroup']."';
  if (typeof indiciaData.reportlayer!=='undefined') {
    opts.layers.push(indiciaData.reportlayer);\n";
      if ($options['clickable'])
        report_helper::$javascript .= "    opts.clickableLayers.push(indiciaData.reportlayer);\n";
      report_helper::$javascript .= "  }\n";
      if (!empty($options["customClickFn"]))
        report_helper::$javascript .= "  opts.customClickFn=".$options['customClickFn'].";\n";
      report_helper::$javascript .= "  opts.clickableLayersOutputMode='".$options['clickableLayersOutputMode']."';\n";
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
   * <li><b>caching</b>
   * If true, then the response will be cached and the cached copy used for future calls. Default false.
   * If 'store' then although the response is not fetched from a cache, the response will be stored in the cache for possible
   * later use.
   * </li>
   * <li><b>cachePerUser</b>
   * Default true. Because a report automatically receives the user_id of a user as a parameter, if the user is linked to the warehouse,
   * report caching will be granular to the user level. That is, if a user loads a report and another user loads the same report, the 
   * cache is not used because they have different user IDs. Set this to false to make the cache entry global so that all users will receive
   * the same copy of the report. Generally you should only use this on reports that are non-user specific.
   * </li>
   * </ul>

   * @param string $extra Any additional parameters to append to the request URL, for example orderby, limit or offset.
   * @return object If linkOnly is set in the options, returns the link string, otherwise returns the response as an array.
   */
  public static function get_report_data($options, $extra='') {
    $query = array();
    if (!isset($options['mode'])) $options['mode']='report';
    if (!isset($options['format'])) $options['format']='json';
    if ($options['mode']=='report') {
      $serviceCall = 'report/requestReport?report='.$options['dataSource'].'.xml&reportSource=local&'.
          (isset($options['filename']) ? 'filename='.$options['filename'].'&' : '');
    } elseif ($options['mode']=='direct') {
      $serviceCall = 'data/'.$options['dataSource'].'?';
      if (isset($_GET['filters']) && isset($_GET['columns'])) {
        $filters=explode(',', $_GET['filters']);
        $columns=explode(',', $_GET['columns']);
        $assoc = array_combine($columns, $filters);
        $query['like'] = $assoc;
      }
    } else {
      throw new Exception('Invalid mode parameter for call to report_grid - '.$options['mode']);
    }
    if (!empty($extra) && substr($extra, 0, 1)!=='&')
      $extra = '&'.$extra;
    $request = 'index.php/services/'.
        $serviceCall.
        'mode='.$options['format'].'&nonce='.$options['readAuth']['nonce'].
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
      foreach ($options['extraParams'] as $key=>$value) {
        // Must urlencode the keys and parameters, as things like spaces cause curl to hang.
        $request .= '&'.urlencode($key).'='.urlencode($value);
      }
    }
    // Pass through the type of data sharing
    if (isset($options['sharing']))
      $request .= '&sharing='.$options['sharing'];
    if (isset($options['userId']))
      $request .= '&user_id='.$options['userId'];
    if (isset($options['linkOnly']) && $options['linkOnly']) {
      // a link must be proxied as can be used client-site 
      return (empty(parent::$warehouse_proxy) ? parent::$base_url : parent::$warehouse_proxy).$request;
    }
    else {
      if (isset($options['caching']) && $options['caching'] && $options['caching'] !== 'store') {
        // Get the URL params, so we know what the unique thing is we are caching
        $query=parse_url(parent::$base_url.$request, PHP_URL_QUERY);
        parse_str($query, $cacheOpts);
        unset($cacheOpts['auth_token']);
        unset($cacheOpts['nonce']);
        if (isset($options['cachePerUser']) && !$options['cachePerUser']) 
          unset($cacheOpts['user_id']);
        $cacheTimeOut = self::_getCacheTimeOut($options);
        $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
        $cacheFile = self::_getCacheFileName($cacheFolder, $cacheOpts, $cacheTimeOut);        
        $response = self::_getCachedResponse($cacheFile, $cacheTimeOut, $cacheOpts);
      }
      // no need to proxy the request, as coming from server-side
      if (!isset($response) || $response===false) {
        $response = self::http_post(parent::$base_url.$request, null);
      }
      $decoded = json_decode($response['output'], true);
      if (!is_array($decoded))
        return array('error'=>print_r($response, true));
      else {
        if (isset($options['caching']) && $options['caching']) { 
          self::_cacheResponse($cacheFile, $response, $cacheOpts);
        }
        return $decoded;
      }
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
    $page = (isset($sortAndPageUrlParams['page']) && $sortAndPageUrlParams['page']['value'] 
        ? $sortAndPageUrlParams['page']['value'] : 0);
    // set the limit to one higher than we need, so the extra row can trigger the pagination next link
    if($options['itemsPerPage'] !== false) {
      $extraParams = '&limit='.($options['itemsPerPage']+1);
      $extraParams .= '&offset=' . $page * $options['itemsPerPage'];
    } else $extraParams = '';
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
   * @param array $sortAndPageUrlParams List of the sorting and pagination parameters which should be excluded.
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
      if (isset($options['completeParamsForm']) && $options['completeParamsForm']) {
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
      if (isset($options['ignoreParams']))
        // tell the params form builder to hide the ignored parameters.
        $options['paramsToHide']=$options['ignoreParams'];
      $r .= self::build_params_form(array_merge($options, array('form'=>$response['parameterRequest'], 'defaults'=>$params)), $hasVisibleContent);
      if (isset($options['completeParamsForm']) && $options['completeParamsForm']) {
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
if (typeof mapSettingsHooks!=='undefined') {
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
    if (isset($response['columns'])) {
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
        if (isset($col['fieldname']) && array_key_exists($col['fieldname'], $response['columns'])) {
          if (isset($response['columns'][$col['fieldname']]['datatype']))
            $col['datatype']=$response['columns'][$col['fieldname']]['datatype'];
        }
      }
      if ($options['includeAllColumns']) {
        foreach ($response['columns'] as $resultField => $value) {
          if (!in_array($resultField, $specifiedCols)) {
            $options['columns'][] = array_merge(
              $value,
              array('fieldname'=>$resultField)
            );
          }
        }
      }
      // add the actions columns back in at the end
      $options['columns'] = array_merge($options['columns'], $actionCols);
    }
  }

  /**
   * Retrieve the HTML for the actions in a grid row.
   * @param array $actions List of the action definitions to convert to HTML.
   * @param array $row The content of the row loaded from the database.
   * @param string $pathParam Set to the name of a URL param used to pass the path to this page. E.g. in Drupal
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
      // If we are using a path parameter (like Drupal's q= dirty URLs), then we must ignore this part of the current URL's parameters
      // so that it can be replaced by the path we are navigating to.
      unset($currentUrl['params'][$pathParam]);
    }
    foreach ($actions as $action) {
      // skip any actions which are marked as invisible for this row.
      if (isset($action['visibility_field']) && $row[$action['visibility_field']]==='f')
        continue;
      if (isset($action['url'])) {
        // Catch lazy cases where the URL does not contain the rootFolder so assumes a relative path
        if ( strcasecmp(substr($action['url'], 0, 12), '{rootfolder}') !== 0 && 
             strcasecmp(substr($action['url'], 0, 12), '{currentUrl}') !== 0 && 
             strcasecmp(substr($action['url'], 0, 4), 'http') !== 0 && 
             strcasecmp(substr($action['url'], 0, 12), '{input_form}') !== 0 ) {
          $action['url'] = '{rootFolder}'.$action['url'];
        }
        
        // Catch cases where {input_form} is unavailable, a relative path or null
        // You may want the report to return a default value if input_form is null.
        if ( strcasecmp(substr($action['url'], 0, 12), '{input_form}') === 0 ) {
          if ( array_key_exists('input_form', $row) ) {
            // The input_form field is available
            if ( !isset($row['input_form']) || $row['input_form'] == '' ) {
              // If it has no value, use currentUrl as default
              $action['url'] = '{currentUrl}';
            } elseif (strcasecmp(substr($row['input_form'], 0, 4), 'http') !== 0 ) {
              // assume a relative path if it doesn't begin with 'http'
              $action['url'] = '{rootFolder}'.$action['url'];
            }
          } else {
            // If input_form is not available use surrentUrl as default
            $action['url'] = '{currentUrl}';
          }          
        } 
                
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
      if (isset($action['img'])) {
        $img=str_replace(array('{rootFolder}'), array(self::getRootfolder()), $action['img']);
        $content = '<img src="'.$img.'" title="'.$action['caption'].'" />';
      } elseif (isset($action['caption']))
        $content = $action['caption'];
      $links[] = "<a class=\"action-button$class\"$href$onclick>".$content.'</a>';
    }
    return implode('', $links);
  }

  /**
   * Apply the defaults to the options for the report grid.
   * @param array $options Array of control options.
   */
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
      'view' => 'list',
      'caching' => isset($options['paramsOnly']) && $options['paramsOnly'],
      'sendOutputToMap' => false,
      'zoomMapToOutput' => true,
      'ajax' => false,
      'autoloadAjax' => true,
      'linkFilterToMap' => true,
      'pager' => true
    ), $options);
    // if using AJAX we are only loading parameters and columns, so may as well use local cache
    if ($options['ajax'])
      $options['caching']=true;
    if ($options['galleryColCount']>1) $options['class'] .= ' gallery';
    // use the current report as the params form by default
    if (empty($options['reportGroup'])) $options['reportGroup'] = $options['id'];
    if (empty($options['fieldNamePrefix'])) $options['fieldNamePrefix'] = $options['reportGroup'];
    if (function_exists('hostsite_get_user_field')) {
      // If the host environment (e.g. Drupal module) can tell us which Indicia user is logged in, pass that
      // to the report call as it might be required for filters.
      if (!isset($options['extraParams']['user_id']) && $indiciaUserId = hostsite_get_user_field('indicia_user_id'))
        $options['extraParams']['user_id'] = $indiciaUserId;
      if (hostsite_get_user_field('training')) 
        $options['extraParams']['training'] = 'true';
    }
    return $options;
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
    // Is there a saved cookie containing previously used report parameters?
    if (isset($_COOKIE['providedParams']) && !empty($options['rememberParamsReportGroup'])) {
      $cookieData = json_decode($_COOKIE['providedParams'], true);
      // guard against a corrupt cookie
      if (!is_array($cookieData)) 
        $cookieData=array();
      if (!empty($cookieData[$options['rememberParamsReportGroup']]))
        $cookieParams = $cookieData[$options['rememberParamsReportGroup']];
        // We shouldn't use the cookie values to overwrite any parameters that are hidden in the form as this is confusing.
        $ignoreParamNames = array();
        foreach($options['paramsToExclude'] as $param)
          $ignoreParamNames[$options['reportGroup']."-$param"] = '';
        $cookieParams = array_diff_key($cookieParams, $ignoreParamNames);       
        $providedParams = array_merge(
          $cookieParams,
          $providedParams
        );
    }
    if (!empty($options['rememberParamsReportGroup'])) {
      // need to store the current set of saved params. These need to be merged into an array to go in
      // the single stored cookie with the array key being the rememberParamsReportGroup and the value being
      // an associative array of params.
      if (!isset($cookieData))
        $cookieData = array();
      $cookieData[$options['rememberParamsReportGroup']]=$providedParams;
      setcookie('providedParams', json_encode($cookieData));
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
  * </li>
  * </ul>
  * @todo Future Enhancements? Allow restriction to month.
  */
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
      return '<p>Internal Error: Report request parameters not set up correctly.<br />'.(print_r($response,true)).'<p>';
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
    $r = "\n<table class=\"".$options['class']."\">";
    $r .= "\n<thead class=\"$thClass\"><tr>".($options['includeWeekNumber'] ? "<td>".lang::get("Week Number")."</td>" : "")."<td></td><td></td><td></td>".
        "<td colspan=\"3\" class=\"year-picker\"><a title=\"".($options["year"]-1)."\" rel=\"\nofollow\" href=\"".$pageUrl.$pageUrlParams['year']['name']."=".($options["year"]-1).
        "\" class=\"ui-datepicker-prev ui-corner-all\"><span class=\"ui-icon ui-icon-circle-triangle-w\">Prev</span></a>".
        "<span class=\"thisYear\">".$options["year"]."</span>";
    if($options["year"]<date('Y')){
      $r .= "  <a title=\"".($options["year"]+1)."\" rel=\"\nofollow\" href=\"".$pageUrl.$pageUrlParams['year']['name']."=".($options["year"]+1)."\" class=\"ui-datepicker-next ui-corner-all\">
        <span class=\"ui-icon ui-icon-circle-triangle-e\">Next</span></a>";
    }
    $r .= "</td><td></td><td></td></tr></thead>\n";
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
      unset($options['extraParams']['date_from']);
      unset($options['extraParams']['date_to']);
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
        $cellclass="future"; // can't enter data in the future.
      } else if($consider_date->format('Y') == $options["year"]){ // only allow data to be entered for the year being considered.
        if(isset($options['buildLinkFunc'])){
          $options['consider_date'] = $consider_date->format('d/m/Y');
          $callbackVal = call_user_func_array($options['buildLinkFunc'],
              array(isset($dateRecords[$consider_date->format('d/m/Y')]) ? $dateRecords[$consider_date->format('d/m/Y')] : array(),
                    $options, $cellContents));
          $cellclass=$callbackVal['cellclass'];
          $cellContents=$callbackVal['cellContents'];
        } else if(isset($dateRecords[$consider_date->format('d/m/Y')])){ // check if there is a record on this date
          $cellclass="existingLink";
          $cellContents .= ' <a href="'.$options["newURL"].'date='.$consider_date->format('d/m/Y').'" class="newLink" title="Create a new sample on '.$consider_date->format('d/m/Y').'" ><div class="ui-state-default add-button">&nbsp;</div></a> ';
          foreach($dateRecords[$consider_date->format('d/m/Y')] as $record)
            $cellContents.='<a href="'.$options["existingURL"].'sample_id='.$record["sample_id"].'" title="View existing sample for '.$record["location_name"].' on '.$consider_date->format('d/m/Y').'" ><div class="ui-state-default view-button">&nbsp;</div></a>';
        } else {
          $cellclass="newLink";
          $cellContents .= ' <a href="'.$options["newURL"].'date='.$consider_date->format('d/m/Y').'" class="newLink" title="Create a new sample on '.$consider_date->format('d/m/Y').'" ><div class="ui-state-default add-button">&nbsp;</div></a>';
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

  /**
   * Applies the defaults to the options array passed to a report_calendar_grid.
   * @param array $options Options array passed to the control.
   */
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
      'user_id' => $user->uid, // Initially CMS User, changed to Indicia User later if in Easy Login mode.
      'cms_user_id' => $user->uid, // CMS User, not Indicia User.
      'smpattrs' => ''), $options["extraParams"]);
    $options['my_user_id'] = $user->uid; // Initially CMS User, changed to Indicia User later if in Easy Login mode.
    // Note for the calendar reports, the user_id is assumed to be the CMS user id as recorded in the CMS User ID attribute,
    // not the Indicia user id.
    if (function_exists('hostsite_get_user_field') && $options["extraParams"]['user_id'] == $options["extraParams"]['cms_user_id']) {
      $indicia_user_id = hostsite_get_user_field('indicia_user_id');
      if($indicia_user_id)
        $options["extraParams"]['user_id'] = $indicia_user_id;
      if($options['my_user_id']){ // false switches this off.
        $account = user_load($options['my_user_id']);
        if (function_exists('profile_load_profile'))
          profile_load_profile($account); /* will not be invoked for Drupal7 where the fields are already in the account object */
        if(isset($account->profile_indicia_user_id))
          $options['my_user_id'] = $account->profile_indicia_user_id;
      }
    }
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
   * @param string $addFeaturesJs JavaScript which creates the list of features.
   * @param string $defsettings Default style settings.
   * @param string $selsettings Selected item style settings.
   * @param string $defStyleFns JavaScript snippet which places any style functions required into the 
   * context parameter when creating a default Style.
   * @param string $selStyleFns JavaScript snippet which places any style functions required into the 
   * context parameter when creating a selected Style.
   * @param boolean $zoomToExtent If true, then the map will zoom to show the extent of the features added.
   */
  private static function addFeaturesLoadingJs($addFeaturesJs, $defsettings='',
      $selsettings='{"strokeColor":"#ff0000","fillColor":"#ff0000","strokeWidth":2}', $defStyleFns='', $selStyleFns='', $zoomToExtent=true,
      $featureDoubleOutlineColour='') {
    // Note that we still need the Js to add the layer even if using AJAX (when $addFeaturesJs will be empty)
    report_helper::$javascript.= "
  if (typeof OpenLayers !== \"undefined\") {
    var defaultStyle = new OpenLayers.Style($defsettings$defStyleFns);
    var selectStyle = new OpenLayers.Style($selsettings$selStyleFns);
    var styleMap = new OpenLayers.StyleMap({'default' : defaultStyle, 'select' : selectStyle});
    if (typeof indiciaData.reportlayer==='undefined') {
    indiciaData.reportlayer = new OpenLayers.Layer.Vector('Report output', {styleMap: styleMap, rendererOptions: {zIndexing: true}});
    }";
    // If there are some special styles to apply, but the layer exists already, apply the styling
    if ($defStyleFns!=='' || $selStyleFns) {
      report_helper::$javascript.= "
    else {
      indiciaData.reportlayer.styleMap = styleMap;
    }";  
    }
    report_helper::$javascript .= "\n    mapInitialisationHooks.push(function(div) {\n";
    if (!empty($addFeaturesJs)) {
      report_helper::$javascript .= "      var features = [];\n";
      report_helper::$javascript .= "$addFeaturesJs\n";
      report_helper::$javascript .= "      indiciaData.reportlayer.addFeatures(features);\n";
      if ($zoomToExtent && !empty($addFeaturesJs))
        self::$javascript .= "      div.map.zoomToExtent(indiciaData.reportlayer.getDataExtent());\n";
      report_helper::$javascript .= "      div.map.addLayer(indiciaData.reportlayer);\n";
      if (!empty($featureDoubleOutlineColour)) {
        // push a clone of the array of features onto a layer which will draw an outline.
        report_helper::$javascript .= "
        var defaultStyleOutlines = new OpenLayers.Style({\"strokeWidth\":5,\"strokeColor\":\"$featureDoubleOutlineColour\",
            \"fillOpacity\":0});
        var styleMap = new OpenLayers.StyleMap({'default' : defaultStyleOutlines});
        indiciaData.outlinelayer = new OpenLayers.Layer.Vector('Outlines', {styleMap: defaultStyleOutlines});
        outlinefeatures=[];
        $.each(features, function(idx, f) { outlinefeatures.push(f.clone()); });      
        indiciaData.outlinelayer.addFeatures(outlinefeatures);
        div.map.addLayer(indiciaData.outlinelayer);
        div.map.setLayerIndex(indiciaData.outlinelayer, 1);\n";
      }
    }
    self::$javascript .= "    });
  }\n";
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
  * The column in the report which is used as the label for the vertical axis on the grid.</li>
  * <li><b>rowGroupID</b>
  * The column in the report which is used as the id for the vertical axis on the grid.</li>
  * <li><b>countColumn</b>
  * OPTIONAL: The column in the report which contains the count for this occurrence. If omitted then the default
  * is to assume one occurrence = count of 1</li>
  * <li><b>includeChartItemSeries</b>
  * Defaults to true. Include a series for each item in the report output.
  * </li>
  * <li><b>includeChartTotalSeries</b>
  * Defaults to true. Include a series for the total of each item in the report output.
  * </li>
  * </ul>
  * @todo: Future Enhancements? Allow restriction to month.
  */
  public static function report_calendar_summary($options) {
    // I know that there are better ways to approach some of the date manipulation, but they are PHP 5.3+.
    // We support back to PHP 5.2
    // TODO : i8n
    // TODO invariant IDs and names prevents more than one on a page.
    // TODO convert to tabs when switching between chart and table.
    $warnings = '<span style="display:none;">Starting report_calendar_summary : '.date(DATE_ATOM).'</span>'."\n";
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
    $warnings .= '<span style="display:none;">Report request finish : '.date(DATE_ATOM).'</span>'."\n";
    $records = $response['records'];
    $pageUrlParams = self::get_report_calendar_grid_page_url_params($options);
    $pageUrl = self::report_calendar_grid_get_reload_url($pageUrlParams);
    $pageUrl .= (strpos($pageUrl , '?')===false) ? '?' : '&';
    data_entry_helper::$javascript .= "
var pageURI = \"".$_SERVER['REQUEST_URI']."\";
function rebuild_page_url(oldURL, overrideparam, overridevalue) {
  var parts = oldURL.split('?');
  var params = [];
  if(overridevalue!=='') params.push(overrideparam+'='+overridevalue);
  if(parts.length > 1) {
    var oldparams = parts[1].split('&');
    for(var i = 0; i < oldparams.length; i++){
      var bits = oldparams[i].split('=');
      if(bits[0] != overrideparam) params.push(oldparams[i]);
    }
  }
  return parts[0]+(params.length > 0 ? '?'+params.join('&') : '');
};
var pageURI = \"".$_SERVER['REQUEST_URI']."\";
function update_controls(){
  $('#year-control-previous').attr('href',rebuild_page_url(pageURI,'year',".substr($options['date_start'],0,4)."-1));
  $('#year-control-next').attr('href',rebuild_page_url(pageURI,'year',".substr($options['date_start'],0,4)."+1));
  // user and location ids are dealt with in the main form. their change functions look a pageURI
}
update_controls();
";
    
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
    $weekOne_date_weekday = $weekOne_date->format('N');
    if($weekOne_date_weekday > $weekstart[1]) // scan back to start of week
      $weekOne_date->modify('-'.($weekOne_date_weekday-$weekstart[1]).' day'); 
    else if($weekOne_date_weekday < $weekstart[1])
      $weekOne_date->modify('-'.(7+$weekOne_date_weekday-$weekstart[1]).' day');
    // don't do anything if equal.
    $year_start = date_create(substr($options['date_start'],0,4).'-Jan-01');
    $year_end = date_create(substr($options['date_start'],0,4).'-Dec-25'); // don't want to go beyond the end of year: this is 1st Jan minus 1 week: it is the start of the last full week
    $firstWeek_date = clone $weekOne_date; // date we start providing data for
    $weekOne_date_yearday = $weekOne_date->format('z'); // day within year note year_start_yearDay is by definition 0
    $weekOne_date_weekday = $weekOne_date->format('N'); // day within week
    $minWeekNo = $weeknumberfilter[0]!='' ? $weeknumberfilter[0] : 1;
    $numWeeks = ceil($weekOne_date_yearday/7); // number of weeks in year prior to $weekOne_date - 1st Jan gives zero, 2nd-8th Jan gives 1, etc
    if($minWeekNo-1 < (-1 * $numWeeks)) $minWeekNo=(-1 * $numWeeks)+1; // have to allow for week zero
    if($minWeekNo < 1)
      $firstWeek_date->modify((($minWeekNo-1)*7).' days'); // have to allow for week zero
    else if($minWeekNo > 1)
      $firstWeek_date->modify('+'.(($minWeekNo-1)*7).' days');
    
    if($weeknumberfilter[1]!=''){
      $maxWeekNo = $weeknumberfilter[1];
    } else {
      $year_end = date_create(substr($options['date_start'],0,4).'-Dec-25'); // don't want to go beyond the end of year: this is 1st Jan minus 1 week: it is the start of the last full week
      $year_end_yearDay = $year_end->format('z'); // day within year
      $maxWeekNo = 1+ceil(($year_end_yearDay-$weekOne_date_yearday)/7);
    }
    $warnings .= '<span style="display:none;">Initial date processing complete : '.date(DATE_ATOM).'</span>'."\n";
    $tableNumberHeaderRow = "";
    $tableDateHeaderRow = "";
    $downloadNumberHeaderRow = "";
    $downloadDateHeaderRow = "";
    $chartNumberLabels=array();
    $chartDateLabels=array();
    $fullDates=array();
    for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
      $tableNumberHeaderRow.= '<td class="week">'.$i.'</td>';
      $tableDateHeaderRow.= '<td class="week">'.$firstWeek_date->format('M').'<br/>'.$firstWeek_date->format('d').'</td>';
      $downloadNumberHeaderRow.= '%2C'.$i;
      $downloadDateHeaderRow.= '%2C'.$firstWeek_date->format('d/m/Y');
      $chartNumberLabels[] = "".$i;
      $chartDateLabels[] = $firstWeek_date->format('M-d');
      $fullDates[$i] = $firstWeek_date->format('d/m/Y');
      $firstWeek_date->modify('+7 days');
    }
    $summaryArray=array(); // this is used for the table output format
    $rawArray=array(); // this is used for the table output format
    // In order to apply the data combination and estmation processing, we assume that the the records are in taxon, location_id, sample_id order.
    $locationArray=array(); // this is for a single species at a time.
    $lastLocation=false;
    $seriesLabels=array();
    $lastTaxonID=false;
    $lastSample=false;
    $locationSamples = array();
    $dateList = array();
    $weekList = array();
    $avgFieldList = !empty($options['avgFields']) ? explode(',',$options['avgFields']) : false;
    $smpAttrList = array();
    if(!$avgFieldList || count($avgFieldList)==0) $avgFields = false;
    else {
      $avgFields = array();
      foreach($avgFieldList as $avgField) {
        $avgFields[$avgField] = array('caption'=>$avgField, 'attr'=>false);
        $parts = explode(':',$avgField);
        if(count($parts)==2 && $parts[0]='smpattr') {
          $smpAttribute=data_entry_helper::get_population_data(array(
              'table'=>'sample_attribute',
              'extraParams'=>$options['readAuth'] + array('view'=>'list', 'id'=>$parts[1])
          ));
          if(count($smpAttribute)>=1){ // may be assigned to more than one survey on this website. This is not relevant to info we want.
            $avgFields[$avgField]['id'] = $parts[1];
            $avgFields[$avgField]['attr'] = $smpAttribute[0];
            $avgFields[$avgField]['caption'] = $smpAttribute[0]['caption'];
            if($smpAttribute[0]['data_type']=='L')
              $avgFields[$avgField]['attr']['termList'] = data_entry_helper::get_population_data(array(
                'table'=>'termlists_term',
                'extraParams'=>$options['readAuth'] + array('view'=>'detail', 'termlist_id'=>$avgFields[$avgField]['attr']['termlist_id'])
            ));
          }
        }
      }
    }
    
    // we are assuming that there can be more than one occurrence of a given taxon per sample.
    if($options['location_list'] != 'all' && count($options['location_list']) == 0) $options['location_list'] = 'none';
    foreach($records as $recid => $record){
      // If the taxon has changed
      $this_date = date_create(str_replace('/','-',$record['date'])); // prevents day/month ordering issues
      $this_index = $this_date->format('z');
      $this_weekday = $this_date->format('N');
      if($this_weekday > $weekstart[1]) // scan back to start of week
      	$this_date->modify('-'.($this_weekday-$weekstart[1]).' day');
      else if($this_weekday < $weekstart[1])
      	$this_date->modify('-'.(7+$this_weekday-$weekstart[1]).' day');
      // this_date now points to the start of the week. Next work out the week number.
      $this_yearday = $this_date->format('z');
      $weekno = (int)floor(($this_yearday-$weekOne_date_yearday)/7)+1;
      if(isset($weekList[$weekno])){
        if(!in_array($record['location_name'],$weekList[$weekno])) $weekList[$weekno][] = $record['location_name'];
      } else $weekList[$weekno] = array($record['location_name']);
      if(!isset($rawArray[$this_index])){
        $rawArray[$this_index] = array('weekno'=>$weekno, 'counts'=>array(), 'date'=>$record['date'], 'total'=>0, 'samples'=>array(), 'avgFields'=>array());
      }
      // we assume that the report is configured to return the user_id which matches the method used to generate my_user_id
      if (($options['my_user_id']==$record['user_id'] ||
           $options['location_list'] == 'all' ||
           ($options['location_list'] != 'none' && in_array($record['location_id'], $options['location_list'])))
          && !isset($rawArray[$this_index]['samples'][$record['sample_id']])){
        $rawArray[$this_index]['samples'][$record['sample_id']]=array('id'=>$record['sample_id'], 'location_name'=>$record['location_name'], 'avgFields'=>array());
        if($avgFields){
          foreach($avgFields as $field => $avgField) {
            if(!$avgField['attr'])
              $rawArray[$this_index]['samples'][$record['sample_id']]['avgFields'][$field] = $record[$field];
            else if($avgField['attr']['data_type']=='L') {
              $term = trim($record['attr_sample_term_'.$avgField['id']], "% \t\n\r\0\x0B");
              $rawArray[$this_index]['samples'][$record['sample_id']]['avgFields'][$field] = is_numeric($term) ? $term : null;
            } else
              $rawArray[$this_index]['samples'][$record['sample_id']]['avgFields'][$field] = $record['attr_sample_'.$avgField['id']];
          }
        }
      }
      $records[$recid]['weekno']=$weekno;
      $records[$recid]['date_index']=$this_index;
      if(isset($locationSamples[$record['location_id']])){
        if(isset($locationSamples[$record['location_id']][$weekno])) {
          if(!in_array($record['sample_id'], $locationSamples[$record['location_id']][$weekno]))
            $locationSamples[$record['location_id']][$weekno][] = $record['sample_id'];
        } else $locationSamples[$record['location_id']][$weekno] = array($record['sample_id']);
      } else $locationSamples[$record['location_id']] = array($weekno => array($record['sample_id']));
    }
    $warnings .= '<span style="display:none;">Records date pre-processing complete : '.date(DATE_ATOM).'</span>'."\n";
    if($avgFields) {
      foreach($rawArray as $dateIndex => $rawData) {
        foreach($avgFields as $field=>$avgField){
          $total=0;
          $count=0;
          foreach($rawArray[$dateIndex]['samples'] as $sample) {
            if($sample['avgFields'][$field] != null){
              $total += $sample['avgFields'][$field];
              $count++;
            }
          }
          $rawArray[$dateIndex]['avgFields'][$field] = $count ? $total/$count : "";
          if($options['avgFieldRound']=='nearest' && $rawArray[$dateIndex]['avgFields'][$field]!="")
            $rawArray[$dateIndex]['avgFields'][$field] = (int)round($rawArray[$dateIndex]['avgFields'][$field]);
        }
      }
    }
    $warnings .= '<span style="display:none;">Sample Attribute processing complete : '.date(DATE_ATOM).'</span>'."\n";
    $count = count($records);
    self::report_calendar_summary_initLoc1($minWeekNo, $maxWeekNo, $weekList);
    if($count>0) $locationArray = self::report_calendar_summary_initLoc2($minWeekNo, $maxWeekNo, $locationSamples[$records[0]['location_id']]);
    $warnings .= '<span style="display:none;">Number of records processed : '.$count.' : '.date(DATE_ATOM).'</span>'."\n";
    $downloadList = 'Location%2C'.
          ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? lang::get('Week Number').'%2C' : '').
          lang::get('Week Commencing').'%2C'.lang::get('Species').'%2C'.lang::get('Type').'%2C'.lang::get('Value').'%0A';
    foreach($records as $idex => $record){
      // If the taxon has changed
      if(($lastTaxonID && $lastTaxonID!=$record[$options['rowGroupID']]) ||
         ($lastLocation && $lastLocation!=$record['location_id'])) {
        self::report_calendar_summary_processEstimates($summaryArray, $locationArray, $locationSamples[$lastLocation], $minWeekNo, $maxWeekNo, $fullDates, $lastTaxonID, $seriesLabels[$lastTaxonID], $options, $downloadList);
        $locationArray = self::report_calendar_summary_initLoc2($minWeekNo, $maxWeekNo, $locationSamples[$record['location_id']]);
      }
      $lastTaxonID=$record[$options['rowGroupID']];
      $seriesLabels[$lastTaxonID]=$record[$options['rowGroupColumn']];
      $lastLocation=$record['location_id'];
      $lastSample=$record['sample_id'];
      $weekno = $record['weekno'];
      if($lastTaxonID === null) $count = 0;
      else if(isset($options['countColumn']) && $options['countColumn']!=''){
        $count = (isset($record[$options['countColumn']])?$record[$options['countColumn']]:0);
      } else
        $count = 1; // default to single row = single occurrence
      // leave this conditional in - not sure what may happen in future, and it works.
      if($weekno >= $minWeekNo && $weekno <= $maxWeekNo){
        if($locationArray[$weekno]['this_sample'] != $lastSample) {
          $locationArray[$weekno]['max'] = max($locationArray[$weekno]['max'], $locationArray[$weekno]['sampleTotal']);
          $locationArray[$weekno]['this_sample'] = $lastSample;
          $locationArray[$weekno]['numSamples']++;
          $locationArray[$weekno]['sampleTotal'] = $count;
        } else
          $locationArray[$weekno]['sampleTotal'] += $count;
        $locationArray[$weekno]['total'] += $count;
        $locationArray[$weekno]['forcedZero'] = false;
        $locationArray[$weekno]['location'] = $record['location_name'];
      }
      $this_index = $record['date_index'];
      if($lastTaxonID != null) {
      	if(isset($rawArray[$this_index]['counts'][$lastTaxonID]))
          $rawArray[$this_index]['counts'][$lastTaxonID] += $count;
        else
          $rawArray[$this_index]['counts'][$lastTaxonID] = $count;
        $rawArray[$this_index]['total'] += $count;
      }
    }
    if($lastTaxonID || $lastLocation) {
      self::report_calendar_summary_processEstimates($summaryArray, $locationArray, $locationSamples[$lastLocation], $minWeekNo, $maxWeekNo, $fullDates, $lastTaxonID, $seriesLabels[$lastTaxonID], $options, $downloadList);
    }
    $warnings .= '<span style="display:none;">Estimate processing finished : '.date(DATE_ATOM).'</span>'."\n";
    if(count($summaryArray)==0)
      return $warnings.'<p>'.lang::get('No data returned for this period.').'</p>';
    $r="";
    // will storedata in an array[Y][X]
    $format= array();
    if(isset($options['outputTable']) && $options['outputTable']){
      $format['table'] = array('include'=>true,
          'display'=>(isset($options['simultaneousOutput']) && $options['simultaneousOutput'])||(isset($options['outputFormat']) && $options['outputFormat']=='table')||!isset($options['outputFormat']));
    }
    if(isset($options['outputChart']) && $options['outputChart']){
      $format['chart'] = array('include'=>true,
          'display'=>(isset($options['simultaneousOutput']) && $options['simultaneousOutput'])||(isset($options['outputFormat']) && $options['outputFormat']=='chart'));
      data_entry_helper::add_resource('jqplot');
      switch ($options['chartType']) {
        case 'bar' :
          self::add_resource('jqplot_bar');
          $renderer='$.jqplot.BarRenderer';
          break;
        case 'pie' :
          self::add_resource('jqplot_pie');
          $renderer='$.jqplot.PieRenderer';
          break;
        default :
          $renderer='$.jqplot.LineRenderer';
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
      $defaultSet=$defaultSet || $info['display'];
    }
    if(!$defaultSet){
      if(isset($format['table'])) $format['table']['display']=true;
      else if(isset($format['chart'])) $format['chart']['display']=true;
    }
    $seriesData=array();
    $r .= "\n<div class=\"inline-control report-summary-controls\">";
    $userPicksFormat = count($format)>1 && !(isset($options['simultaneousOutput']) && $options['simultaneousOutput']);
    $userPicksSource = ($options['includeRawData'] ? 1 : 0) +
       ($options['includeSummaryData'] ? 1 : 0) + 
       ($options['includeEstimatesData'] ? 1 : 0) > 1;
    if(!$userPicksFormat && !$userPicksSource) {
        $r .= '<input type="hidden" id="outputSource" name="outputSource" value="'.
    			($options['includeRawData'] ? "raw" :
    					($options['includeSummaryData'] ? "summary" : "estimates")).'"/>';
    	if(isset($options['simultaneousOutput']) && $options['simultaneousOutput']) {
    		// for combined format its fairly obvious what it is, so no need to add text.
    		$r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="both"/>';
    	} else { // for single format its fairly obvious what it is, so no need to add text.
    		foreach($format as $type => $details){
    			$r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="'.$type.'"/>';
    		}
    	}
    	// don't need to set URI as only 1 option.
    } else {
    	$r .= lang::get('View ');
    	if($userPicksSource) {
    		$r .= '<select id="outputSource" name="outputSource">'.
    				($options['includeRawData'] ? '<option id="viewRawData" value="raw"/>'.lang::get('raw data').'</option>' : '').
    				($options['includeSummaryData'] ? '<option id="viewSummaryData" value="summary"/>'.lang::get('summary data').'</option>' : '').
    				($options['includeEstimatesData'] ? '<option id="viewDataEstimates" value="estimates"/>'.lang::get('summary data with estimates').'</option>' : '').
    				'</select>';
    		data_entry_helper::$javascript .= "jQuery('#outputSource').change(function(){
  pageURI = rebuild_page_url(pageURI, \"outputSource\", jQuery(this).val());
  update_controls();
  switch(jQuery(this).val()){
    case 'raw':
        jQuery('#".$options['tableID']."-raw,#".$options['chartID']."-raw').show();
        jQuery('#".$options['tableID'].",#".$options['chartID']."-summary,#".$options['chartID']."-estimates').hide();
        break;
    case 'summary':
        jQuery('#".$options['tableID'].",.summary,#".$options['chartID']."-summary').show();
        jQuery('#".$options['tableID']."-raw,#".$options['chartID']."-raw,.estimates,#".$options['chartID']."-estimates').hide();
        break;
    case 'estimates':
        jQuery('#".$options['tableID'].",.estimates,#".$options['chartID']."-estimates').show();
        jQuery('#".$options['tableID']."-raw,#".$options['chartID']."-raw,.summary,#".$options['chartID']."-summary').hide();
        break;
   }
   if(jQuery('#outputFormat').val() != 'table')
     replot();
});\n".
(isset($options['outputSource']) ?
"$('#outputSource').val('".$options['outputSource']."').change();\n" :
"if($('#viewDataEstimates').length > 0){
    $('#outputSource').val('estimates').change();
} else if($('#viewSummaryData').length > 0){
    $('#outputSource').val('summary').change();
} else {
    $('#outputSource').val('raw').change();
}\n");
    	} else $r .= '<input type="hidden" id="outputSource" name="outputSource" value="'.
           ($options['includeRawData'] ? "raw" : 
               ($options['includeSummaryData'] ? "summary" : "estimates")).'"/>';
        if($userPicksFormat) {
            $defaultTable = !isset($options['outputFormat']) || $options['outputFormat']=='' || $options['outputFormat']=='table';
            $r .= lang::get(' as a ').'<select id="outputFormat" name="outputFormat">'.
                  '<option '.($defaultTable?'selected="selected"':'').' value="table"/>'.lang::get('table').'</option>'.
                  '<option '.(!$defaultTable?'selected="selected"':'').' value="chart"/>'.lang::get('chart').'</option>'.
                  '</select>'; // not providing option for both at moment
            data_entry_helper::$javascript .= "jQuery('[name=outputFormat]').change(function(){
  pageURI = rebuild_page_url(pageURI, \"outputFormat\", jQuery(this).val());
  update_controls();
  switch($(this).val()) {
    case 'table' :
        jQuery('#".$options['tableContainerID']."').show();
        jQuery('#".$options['chartContainerID']."').hide();
        break;
    default : // chart
        jQuery('#".$options['tableContainerID']."').hide();
        jQuery('#".$options['chartContainerID']."').show();
        replot();
        break;
  }
});
jQuery('[name=outputFormat]').change();\n";
    	} else if(isset($options['simultaneousOutput']) && $options['simultaneousOutput']) {
    		// for combined format its fairly obvious what it is, so no need to add text.
            $r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="both"/>';
    	} else { // for single format its fairly obvious what it is, so no need to add text.
    		foreach($format as $type => $details){
    			$r .= '<input type="hidden" id="outputFormat" name="outputFormat" value="'.$type.'"/>';
    		}
    	}
    }
    $r .= "</div>\n";
    $warnings .= '<span style="display:none;">Controls complete : '.date(DATE_ATOM).'</span>'."\n";
    ksort($rawArray);
    $warnings .= '<span style="display:none;">Raw data sort : '.date(DATE_ATOM).'</span>'."\n";
    if(isset($format['chart'])){
      $seriesToDisplay=(isset($options['outputSeries']) ? explode(',', $options['outputSeries']) : 'all');
      $seriesIDs=array();
      $rawSeriesData=array();
      $rawTicks= array();
      $summarySeriesData=array();
      $estimatesSeriesData=array();
      $seriesOptions=array();
      // Series options are not configurable as we need to setup for ourselves...
      // we need show, label and show label filled in. rest are left to defaults
      $rawTotalRow = array();
      $summaryTotalRow = array();
      $estimatesTotalRow = array();
      for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
      	$summaryTotalRow[$i] = 0;
      	$estimatesTotalRow[$i] = 0;
      }
      foreach($rawArray as $dateIndex => $rawData) {
      	$rawTotalRow[] = 0;
      	$rawTicks[] = "\"".$rawData['date']."\"";
      }
      foreach($summaryArray as $seriesID => $summaryRow){
        if (empty($seriesLabels[$seriesID])) continue;
        $rawValues=array();
        $summaryValues=array();
        $estimatesValues=array();
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
          if(isset($summaryRow[$i])){
            $estimatesValues[]=$summaryRow[$i]['estimates'];
            $estimatesTotalRow[$i] += $summaryRow[$i]['estimates'];
            if($summaryRow[$i]['summary']!==false){
              $summaryValues[]=$summaryRow[$i]['summary'];
              $summaryTotalRow[$i] += $summaryRow[$i]['summary'];
            } else {
              $summaryValues[]=0;
              $summaryTotalRow[$i]+=0;
            }
          } else {
            $summaryValues[]=0;
            $estimatesValues[]=0;
          }
        }
        // we want to ensure that series match between summary and raw data. raw data is indexed by date.
        foreach($rawArray as $dateIndex => $rawData) {
          $rawValues[] = (isset($rawData['counts'][$seriesID]) ? $rawData['counts'][$seriesID] : 0);
        }
        foreach($rawValues as $idx => $rawValue) {
          $rawTotalRow[$idx] += $rawValue;
        }
        // each series will occupy an entry in $seriesData
        if ($options['includeChartItemSeries']) {
          $seriesIDs[] = $seriesID;
          $rawSeriesData[] = '['.implode(',', $rawValues).']';
          $summarySeriesData[] = '['.implode(',', $summaryValues).']';
          $estimatesSeriesData[] = '['.implode(',', $estimatesValues).']';
          $seriesOptions[] = '{"show":'.($seriesToDisplay == 'all' || in_array($seriesID, $seriesToDisplay) ? 'true' : 'false').',"label":"'.$seriesLabels[$seriesID].'","showlabel":true}';
        }
      }
      if(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']){ // totals are put at the start
        array_unshift($seriesIDs,0); // Total has ID 0
      	array_unshift($rawSeriesData, '['.implode(',', $rawTotalRow).']');
      	array_unshift($summarySeriesData, '['.implode(',', $summaryTotalRow).']');
        array_unshift($estimatesSeriesData, '['.implode(',', $estimatesTotalRow).']');
        array_unshift($seriesOptions, '{"show":'.($seriesToDisplay == 'all' || in_array(0, $seriesToDisplay) ? 'true' : 'false').',"label":"'.lang::get('Total').'","showlabel":true}');
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
      data_entry_helper::$javascript .= "var seriesData = {ids: [".implode(',', $seriesIDs)."], raw: [".implode(',', $rawSeriesData)."], summary: [".implode(',', $summarySeriesData)."], estimates: [".implode(',', $estimatesSeriesData)."]};\n";
      // Finally, dump out the Javascript with our constructed parameters.
      // width stuff is a bit weird, but jqplot requires a fixed width, so this just stretches it to fill the space.
      data_entry_helper::$javascript .= "\nvar plots = [];
function replot(){
  // there are problems with the coloring of series when added to a plot: easiest just to completely redraw.
  var max=0;
  var type = jQuery('#outputSource').val();
  jQuery('#".$options['chartID']."-'+type).empty();";
      if(!isset($options['width']) || $options['width'] == '')
        data_entry_helper::$javascript .= "\n  jQuery('#".$options['chartID']."-'+type).width(jQuery('#".$options['chartID']."-'+type).width());";
      data_entry_helper::$javascript .= "
  var opts = {".implode(",\n", $opts)."};
  if(type == 'raw') opts.axes.xaxis.ticks = [".implode(',',$rawTicks)."];
  // copy series from checkboxes.
  jQuery('[name=".$options['chartID']."-series]').each(function(idx, elem){
      opts.series[idx].show = (jQuery(elem).filter('[checked]').length > 0);
  });
  for(var i=0; i<seriesData[type].length; i++)
    if(opts.series[i].show)
      for(var j=0; j<seriesData[type][i].length; j++)
          max=(max>seriesData[type][i][j]?max:seriesData[type][i][j]);
  opts.axes.yaxis.max=max+1;
  opts.axes.yaxis.tickInterval = Math.floor(max/15); // number of ticks - too many takes too long to display
  if(!opts.axes.yaxis.tickInterval) opts.axes.yaxis.tickInterval=1;
  plots[type] = $.jqplot('".$options['chartID']."-'+type,  seriesData[type], opts);
};\n";
      // div are full width.
      $r .= '<div id="'.$options['chartContainerID'].'" class="'.$options['chartClass'].'" style="'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').($format['chart']['display']?'':'display:none;').'">';
      if (isset($options['title']))
        $r .= '<div class="'.$options['headerClass'].'">'.$options['title'].'</div>';
      if($options['includeRawData'])
        $r .= '<div id="'.$options['chartID'].'-raw" style="height:'.$options['height'].'px;'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').(($options['includeSummaryData']) || ($options['includeEstimatesData']) ? ' display:none;':'').'"></div>'."\n";
      if($options['includeSummaryData'])
        $r .= '<div id="'.$options['chartID'].'-summary" style="height:'.$options['height'].'px;'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').($options['includeEstimatesData'] ? ' display:none;':'').'"></div>'."\n";
      if($options['includeEstimatesData'])
        $r .= '<div id="'.$options['chartID'].'-estimates" style="height:'.$options['height'].'px;'.(isset($options['width']) && $options['width'] != '' ? 'width:'.$options['width'].'px;':'').'"></div>'."\n";
      if(isset($options['disableableSeries']) && $options['disableableSeries'] &&
           (count($summaryArray)>(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries'] ? 0 : 1)) && 
           isset($options['includeChartItemSeries']) && $options['includeChartItemSeries']) {
        $class='series-fieldset';
        if (function_exists('hostsite_add_library') && (!defined('DRUPAL_CORE_COMPATIBILITY') || DRUPAL_CORE_COMPATIBILITY!=='7.x')) {
          hostsite_add_library('collapse');
          $class.=' collapsible collapsed';
        }
        $r .= '<fieldset id="'.$options['chartID'].'-series" class="'.$class.'"><legend>'.lang::get('Display Series')."</legend><span>\n";
        $idx=0;
        if(isset($options['includeChartTotalSeries']) && $options['includeChartTotalSeries']){
          // use value = 0 for Total
          $r .= '<span class="chart-series-span"><input type="checkbox" checked="checked" id="'.$options['chartID'].'-series-'.$idx.'" name="'.$options['chartID'].'-series" value="'.$idx.'"/><label for="'.$options['chartID'].'-series-'.$idx.'">'.lang::get('Total')."</label></span>\n";
          $idx++;
          data_entry_helper::$javascript .= "\njQuery('[name=".$options['chartID']."-series]').filter('[value=0]').".($seriesToDisplay == 'all' || in_array(0, $seriesToDisplay) ? 'attr("checked","checked");' : 'removeAttr("checked");');
        }
        $r .= '<input type="button" class="disable-button" id="'.$options['chartID'].'-series-disable" value="'.lang::get('Hide all ').$options['rowGroupColumn']."\"/>\n";
        foreach($summaryArray as $seriesID => $summaryRow){
          if (empty($seriesLabels[$seriesID])) continue;
          $r .= '<span class="chart-series-span"><input type="checkbox" checked="checked" id="'.$options['chartID'].'-series-'.$idx.'" name="'.$options['chartID'].'-series" value="'.$seriesID.'"/><label for="'.$options['chartID'].'-series-'.$idx.'">'.$seriesLabels[$seriesID]."</label></span>\n";
          $idx++;
          data_entry_helper::$javascript .= "\njQuery('[name=".$options['chartID']."-series]').filter('[value=".$seriesID."]').".($seriesToDisplay == 'all' || in_array($seriesID, $seriesToDisplay) ? 'attr("checked","checked");' : 'removeAttr("checked");');
        }
        $r .= "</span></fieldset>\n";
        // Known issue: jqplot considers the min and max of all series when drawing on the screen, even those which are not displayed
        // so replotting doesn't scale to the displayed series!
        if($format['chart']['display']){
          data_entry_helper::$javascript .= "replot();\n";
        }
        data_entry_helper::$javascript .= "
// above done due to need to ensure get around field caching on browser refresh.
setSeriesURLParam = function(){
  var activeSeries = [],
    active = jQuery('[name=".$options['chartID']."-series]').filter('[checked]'),
    total = jQuery('[name=".$options['chartID']."-series]');
  if(active.length == total.length) {
    pageURI = rebuild_page_url(pageURI, 'outputSeries', '');
  } else {
    active.each(function(idx,elem){ activeSeries.push($(elem).val()); });
    pageURI = rebuild_page_url(pageURI, 'outputSeries', activeSeries.join(','));
  }
  update_controls();
}
jQuery('[name=".$options['chartID']."-series]').change(function(){
  var seriesID = jQuery(this).val(), index;
  $.each(seriesData.ids, function(idx, elem){
    if(seriesID == elem) index = idx;
  });
  if(jQuery(this).filter('[checked]').length){
    if(typeof plots['raw'] != 'undefined') plots['raw'].series[index].show = true;
    if(typeof plots['summary'] != 'undefined') plots['summary'].series[index].show = true;
    if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[index].show = true;
  } else {
    if(typeof plots['raw'] != 'undefined') plots['raw'].series[index].show = false;
    if(typeof plots['summary'] != 'undefined') plots['summary'].series[index].show = false;
    if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[index].show = false;
  }
  setSeriesURLParam();
  replot();
});
jQuery('#".$options['chartID']."-series-disable').click(function(){
  if(jQuery(this).is('.cleared')){ // button is to show all
    jQuery('[name=".$options['chartID']."-series]').not('[value=0]').attr('checked','checked');
    $.each(seriesData.ids, function(idx, elem){
      if(elem == 0) return; // ignore total series
      if(typeof plots['raw'] != 'undefined') plots['raw'].series[idx].show = true;
      if(typeof plots['summary'] != 'undefined') plots['summary'].series[idx].show = true;
      if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[idx].show = true;
    });
    jQuery(this).removeClass('cleared').val(\"".lang::get('Hide all ').$options['rowGroupColumn']."\");
  } else {
    jQuery('[name=".$options['chartID']."-series]').not('[value=0]').removeAttr('checked');
    $.each(seriesData.ids, function(idx, elem){
      if(elem == 0) return; // ignore total series
      if(typeof plots['raw'] != 'undefined') plots['raw'].series[idx].show = false;
      if(typeof plots['summary'] != 'undefined') plots['summary'].series[idx].show = false;
      if(typeof plots['estimates'] != 'undefined') plots['estimates'].series[idx].show = false;
    });
    jQuery(this).addClass('cleared').val(\"".lang::get('Show all ').$options['rowGroupColumn']."\");
  }
  setSeriesURLParam();
  replot();
});
";
      }
      $r .= "</div>\n";
      $warnings .= '<span style="display:none;">Output chart complete : '.date(DATE_ATOM).'</span>'."\n";
    }
    if(isset($format['table'])){
      $r .= '<div id="'.$options['tableContainerID'].'">';
      if($options['includeRawData']){
        $thClass = $options['thClass'];
        $rawDataDownloadGrid="";
        $rawDataDownloadList='Location%2C'.(($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number') ? 'Week%20Number%2C' : '').'Date%2CSpecies%2CCount%0A';
        $r .= "\n<table id=\"".$options['tableID']."-raw\" class=\"".$options['tableClass']."\" style=\"".($format['table']['display']?'':'display:none;')."\">";
        $r .= "\n<thead class=\"$thClass\">";
        // raw data headers: %Sun, mean temp, Date, Week Number, Location?
        // the Total column is driven as per summary
        // no total row
        if($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number'){
          $r .= '<tr><td>Week</td>';
          $rawDataDownloadGrid .= "Week";
          foreach($rawArray as $idx => $rawColumn){
            $r .= '<td class="week">'.$rawColumn['weekno'].'</td>';
            $rawDataDownloadGrid .= '%2C'.$rawColumn['weekno'];
          }
          if($options['includeTableTotalColumn']){
            $r.= '<td class="total-column"></td>';
            $rawDataDownloadGrid .= '%2C';
          }
        }
        $r .= '</tr><tr><td>Date</td>';
        $rawDataDownloadGrid .= '%0ADate';
        $rawTotalRow = "";
        $rawDataDownloadGridTotalRow = "";
        $rawGrandTotal = 0;
        foreach($rawArray as $idx => $rawColumn){
          $this_date = date_create(str_replace('/','-',$rawColumn['date'])); // prevents day/month ordering issues
          $r .= '<td class="week">'.$this_date->format('M').'<br/>'.$this_date->format('d').'</td>';
          $rawDataDownloadGrid .= '%2C'.$this_date->format('d/m/Y');
          $rawTotalRow .= '<td>'.$rawColumn['total'].'</td>';
          $rawDataDownloadGridTotalRow .= '%2C'.$rawColumn['total'];
          $rawGrandTotal += $rawColumn['total'];
        }
        if($options['includeTableTotalColumn']){
          $r.= '<td class="total-column">Total</td>';
          $rawDataDownloadGrid .= '%2CTotal';
        }
        $r .= "</tr>";
        $rawDataDownloadGrid .= '%0A';
        // don't include links in download
        if(isset($options['linkURL']) && $options['linkURL']!= ''){
          $r .= '<tr><td>Sample Links</td>';
          foreach($rawArray as $idx => $rawColumn){
            $links = array();
            if(count($rawColumn['samples'])>0)
              foreach($rawColumn['samples'] as $sample)
            	$links[] = '<a href="'.$options['linkURL'].$sample['id'].'" target="_blank" title="'.$sample['location_name'].'">('.$sample['id'].')</a>';
            $r .= '<td class="links">'.implode('<br/>',$links).'</td>';
          }
          $r.= ($options['includeTableTotalColumn'] ? '<td class="total-column"></td>' : '')."</tr>";
        }
        $r.= "</thead>\n<tbody>\n";
        $altRow=false;
        if($avgFields) {
          foreach($avgFieldList as $i => $field){
            $r .= "<tr class=\"sample-datarow ".($altRow?$options['altRowClass']:'')." ".($i==(count($avgFields)-1)?'last-sample-datarow':'')."\">";
            $caption = t('Mean '.ucwords($avgFields[$field]['caption']));
            $r .= '<td>'.$caption.'</td>';
            $rawDataDownloadGrid .= '"'.$caption.'"';
            foreach($rawArray as $dateIndex => $rawData) {
              $r.= '<td>'.$rawData['avgFields'][$field].'</td>';
              $rawDataDownloadGrid .= '%2C'.$rawData['avgFields'][$field];
            }
            if($options['includeTableTotalColumn']){
              $r.= '<td class="total-column"></td>';
              $rawDataDownloadGrid .= '%2C';
            }
            $r .= "</tr>";
            $rawDataDownloadGrid .= '%0A';
            $altRow=!$altRow;
          }
        }
        foreach($summaryArray as $seriesID => $summaryRow){ // use the same row headings as the summary table.
          if (!empty($seriesLabels[$seriesID])) {
            $total=0;  // row total
            $r .= "<tr class=\"datarow ".($altRow?$options['altRowClass']:'')."\">";
            $r.= '<td>'.$seriesLabels[$seriesID].'</td>';
            $rawDataDownloadGrid .= '"'.$seriesLabels[$seriesID].'"';
            foreach($rawArray as $date => $rawColumn){
              if(isset($rawColumn['counts'][$seriesID])) {
                $r.= '<td>'.$rawColumn['counts'][$seriesID].'</td>';
                $total += $rawColumn['counts'][$seriesID];
                $rawDataDownloadGrid .= '%2C'.$rawColumn['counts'][$seriesID];
                $locations = array();
                if(count($rawColumn['samples'])>0)
                  foreach($rawColumn['samples'] as $sample)
                    $locations[$sample['location_name']]=true;
                $this_date = date_create(str_replace('/','-',$rawColumn['date'])); // prevents day/month ordering issues
                $rawDataDownloadList .= '"'.implode(': ',array_keys($locations)).'"'.
                     ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? '%2C'.$rawColumn['weekno'] : '').
                     '%2C'.$this_date->format('d/m/Y').'%2C"'.$seriesLabels[$seriesID].'"%2C'.$rawColumn['counts'][$seriesID].'%0A';
              } else {
                $r.= '<td></td>';
                $rawDataDownloadGrid .= '%2C';
              }
            }
            if($options['includeTableTotalColumn']){
              $r.= '<td class="total-column">'.$total.'</td>';
              $rawDataDownloadGrid .= '%2C'.$total;
            }
            $r .= "</tr>";
            $rawDataDownloadGrid .= '%0A';
            $altRow=!$altRow;
          }
        }
        if($options['includeTableTotalRow']){
          $r.= '<tr class="totalrow"><td>Total</td>'.$rawTotalRow.
            ($options['includeTableTotalColumn'] ? '<td>'.$rawGrandTotal.'</td>' : '').'</tr>';
          $rawDataDownloadGrid .= 'Total'.$rawDataDownloadGridTotalRow.
            ($options['includeTableTotalColumn'] ? '%2C'.$rawGrandTotal : '').'%0A';
        }
        $r .= "</tbody></table>\n";
      }
      $summaryDataDownloadGrid="";
      $estimateDataDownloadGrid="";
      $r .= "\n<table id=\"".$options['tableID']."\" class=\"".$options['tableClass']."\" style=\"".($format['table']['display']?'':'display:none;')."\">";
      $r .= "\n<thead class=\"$thClass\">";
      if($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number'){
        $r .= '<tr><td>Week</td>'.$tableNumberHeaderRow.($options['includeTableTotalColumn']
        		                                         ?($options['includeSummaryData'] ? '<td>Total</td>' : '').
        		                                          ($options['includeEstimatesData'] ? '<td class="estimates">Total with<br />estimates</td>' : '')
        		                                         :'').'</tr>';
        $summaryDataDownloadGrid .= 'Week'.$downloadNumberHeaderRow.($options['includeTableTotalColumn']
        		                                         ?($options['includeSummaryData'] ? '%2CTotal' : '')
        		                                         :'').'%0A';
      }
      if($options['tableHeaders'] != 'number'){
        $r .= '<tr><td>'.lang::get('Date').'</td>'.$tableDateHeaderRow.($options['includeTableTotalColumn']
        		                                         ?($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ?
        		                                              ($options['includeSummaryData'] && $options['includeEstimatesData']
        		                                              		?'<td></td><td class="estimates"></td>':'<td '.($options['includeEstimatesData'] ? 'class="estimates"' : '').'></td>') :
        		                                              ($options['includeSummaryData'] ? '<td>Total</td>' : '').
        		                                          ($options['includeEstimatesData'] ? '<td>Total with<br />estimates</td>' : ''))
        		                                         :'').'</tr>';
        $summaryDataDownloadGrid .= lang::get('Date').$downloadDateHeaderRow.($options['includeTableTotalColumn']
        		                                         ? ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? '%2C' : '%2CTotal')
        		                                         :'').'%0A';
      }
      $estimateDataDownloadGrid = $summaryDataDownloadGrid;
      $r.= "</thead>\n";
      $r .= "<tbody>\n";
      $altRow=false;
      $grandTotal=0;
      $totalRow = array();
      $estimatesGrandTotal=0;
      $totalEstimatesRow = array();
      for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
        $totalRow[$i] = 0;
        $totalEstimatesRow[$i] = 0;
      }
      
      foreach($summaryArray as $seriesID => $summaryRow){
        // skip rows with no labels, caused by report left joins to fill in all date columns even if no records
        if (!empty($seriesLabels[$seriesID])) {
          $total=0;  // row total
          $estimatesTotal=0;  // row total
          $r .= "<tr class=\"datarow ".($altRow?$options['altRowClass']:'')."\">";
          $r.= '<td>'.$seriesLabels[$seriesID].'</td>';
          $summaryDataDownloadGrid .= '"'.$seriesLabels[$seriesID].'"';
          $estimateDataDownloadGrid .= '"'.$seriesLabels[$seriesID].'"';
          for($i= $minWeekNo; $i <= $maxWeekNo; $i++){
            $r.= '<td>';
            $summaryDataDownloadGrid .= '%2C';
            $estimateDataDownloadGrid .= '%2C';
            if(isset($summaryRow[$i])){
              $summaryValue = $summaryRow[$i]['forcedZero'] ? 0 : ($summaryRow[$i]['hasData'] ? $summaryRow[$i]['summary'] : '');
              $class = '';
              $estimatesClass = '';
              if($summaryValue!=='' && $options['includeSummaryData'])
              	$class = ($options['includeEstimatesData'] && $summaryRow[$i]['hasEstimates'] && $summaryRow[$i]['estimates']!==$summaryValue ? 'summary' : '').($summaryRow[$i]['forcedZero'] && $options['highlightEstimates'] ? ' forcedZero' : '');
              if($options['includeEstimatesData'])
                $estimatesClass = ($options['includeSummaryData'] ? 'estimates' : '').($options['highlightEstimates'] ? ' highlight-estimates' : '');
              $summaryDataDownloadGrid .= $summaryValue;
              if($summaryRow[$i]['hasEstimates'] || $summaryRow[$i]['forcedZero']) $estimateDataDownloadGrid .= $summaryRow[$i]['estimates'];
              if($options['includeSummaryData'] && $summaryValue !== '') {
                if($class == '') $r .= $summaryValue;
                else $r.= '<span class="'.$class.'">'.$summaryValue.'</span>';
              }
              if(!$options['includeSummaryData'] || ($options['includeEstimatesData'] && $summaryRow[$i]['hasEstimates'] && $summaryRow[$i]['estimates']!==$summaryValue))
                $r.= '<span class="'.$estimatesClass.'">'.$summaryRow[$i]['estimates'].'</span>';
              if($summaryValue !== '' && $summaryValue !== 0){
                $total += $summaryValue;
                $totalRow[$i] += $summaryValue;
                $grandTotal += $summaryValue;
              }
              $estimatesTotal += $summaryRow[$i]['estimates'];
              $totalEstimatesRow[$i] += $summaryRow[$i]['estimates'];
              $estimatesGrandTotal += $summaryRow[$i]['estimates'];
            } // else absolutely nothing - so leave blank.
            $r .= '</td>';
          }
          if($options['includeTableTotalColumn']){
            if($options['includeSummaryData']) {
              $r.= '<td class="total-column">'.$total.'</td>';
              $summaryDataDownloadGrid .= '%2C'.$total;
            }
            if($options['includeEstimatesData']) {
              $r.= '<td class="total-column estimates">'.$estimatesTotal.'</td>';
              $estimateDataDownloadGrid .= '%2C'.$estimatesTotal;
            }
          }
          $r .= "</tr>";
          $summaryDataDownloadGrid .= '%0A';
          $estimateDataDownloadGrid .= '%0A';
          $altRow=!$altRow;
        }
      }
      
      if($options['includeTableTotalRow']){
        if($options['includeSummaryData']){
          $r .= "<tr class=\"totalrow\"><td>".lang::get('Total (Summary)').'</td>';
          $summaryDataDownloadGrid .= '"'.lang::get('Total (Summary)').'"';
          for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
            $r .= '<td>'.$totalRow[$i].'</td>';
            $summaryDataDownloadGrid .= '%2C'.$totalRow[$i];
          }
          if($options['includeTableTotalColumn']) {
            $r .= '<td class="total-column grand-total">'.$grandTotal.'</td>'.($options['includeEstimatesData'] ? '<td class="estimates"></td>' : '');
            $summaryDataDownloadGrid .= '%2C'.$grandTotal;
          }
          $r .= "</tr>";
          $summaryDataDownloadGrid .= '%0A';
        }
        if($options['includeEstimatesData']){
          $r .= "<tr class=\"totalrow estimates\"><td>".lang::get('Total inc Estimates').'</td>';
          $estimateDataDownloadGrid .= '"'.lang::get('Total').'"';
          for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
            $r.= '<td>'.$totalEstimatesRow[$i].'</td>';
            $estimateDataDownloadGrid .= '%2C'.$totalEstimatesRow[$i];
          }
          if($options['includeTableTotalColumn']) {
            $r .= ($options['includeSummaryData'] ? '<td></td>' : '').'<td class="total-column grand-total estimates">'.$estimatesGrandTotal.'</td>';
            $estimateDataDownloadGrid .= '%2C'.$estimatesGrandTotal;
          }
          $r .= "</tr>";
          $estimateDataDownloadGrid .= '%0A';
        }
      }
      $r .= "</tbody></table>\n";
      $r .= "</div>";
      $downloads="";
      if($options['includeRawData']){
        if($options['includeRawGridDownload']) $downloads .= '<th><a download="'.$options['downloadFilePrefix'].'rawDataGrid.csv" href="data:application/csv;charset=utf-8,'.str_replace(array(' ','"'),array('%20','%22'),$rawDataDownloadGrid).'"><button type="button">Raw Grid Data</button></a></th>'."\n";
        if($options['includeRawListDownload']) $downloads .= '<th><a download="'.$options['downloadFilePrefix'].'rawDataList.csv" href="data:application/csv;charset=utf-8,'.str_replace(array(' ','"'),array('%20','%22'),$rawDataDownloadList).'"><button type="button">Raw List Data</button></a></th>'."\n";
      }
      if($options['includeSummaryData'] && $options['includeSummaryGridDownload'])
        $downloads .= '<th><a download="'.$options['downloadFilePrefix'].'summaryDataGrid.csv" href="data:application/csv;charset=utf-8,'.str_replace(array(' ','"'),array('%20','%22'),$summaryDataDownloadGrid).'"><button type="button">Summary Grid Data</button></a></th>'."\n";
      if($options['includeEstimatesData'] && $options['includeEstimatesGridDownload'])
        $downloads .= '<th><a download="'.$options['downloadFilePrefix'].'estimateDataGrid.csv" href="data:application/csv;charset=utf-8,'.str_replace(array(' ','"'),array('%20','%22'),$estimateDataDownloadGrid).'"><button type="button">Estimate Grid Data</button></a></th>'."\n";
      if(($options['includeSummaryData'] || $options['includeEstimatesData']) && $options['includeListDownload'])
        $downloads .= '<th><a download="'.$options['downloadFilePrefix'].'dataList.csv" href="data:application/csv;charset=utf-8,'.str_replace(array(' ','"'),array('%20','%22'),$downloadList).'"><button type="button">List Data</button></a></th>'."\n";
//      $r .= '<br/><table id="downloads-table" class="ui-widget ui-widget-content ui-corner-all downloads-table" '.($downloads == '' ? 'style="display:none"' : '').'><thead class="ui-widget-header"><tr>'.
      $r .= '<br/><table id="downloads-table" class="ui-widget ui-widget-content ui-corner-all downloads-table" ><thead class="ui-widget-header"><tr>'.
            ($downloads == '' ? '' : '<th class="downloads-table-label">Downloads</th>'.$downloads).
            "</tr></thead></table>\n";
      $warnings .= '<span style="display:none;">Output table complete : '.date(DATE_ATOM).'</span>'."\n";
    }
    if(count($summaryArray)==0)
      $r .= '<p>'.lang::get('No data returned for this period.').'</p>';
    $warnings .= '<span style="display:none;">Finish report_calendar_summary : '.date(DATE_ATOM).'</span>'."\n";
    return $warnings.$r;
  }

  /**
   * Creates a default array of entries for any location.
   * @param integer $minWeekNo start week number : index in array.
   * @param integer $maxWeekNo end week number : index in array
   * @param array $weekList list of samples in a particular week.
   */
  private static function report_calendar_summary_initLoc1($minWeekNo, $maxWeekNo, $weekList){
  	$locationArray= array();
  	for($weekno = $minWeekNo; $weekno <= $maxWeekNo; $weekno++)
  		$locationArray[$weekno] = array('this_sample'=>-1,
  				'total'=>0,
  				'sampleTotal'=>0,
  				'max'=>0,
  				'numSamples'=>0,
  				'estimates'=>0,
  				'summary'=>false,
  				'hasData'=>false,
  				'hasEstimates'=>false,
  				'forcedZero'=>isset($weekList[$weekno]),
  				'location'=>'');
  	self::$initLoc = $locationArray;
  }
  
  /*
   * store the initial default location array, so doesn't have to be rebuilt each time.
   */
  private static $initLoc;
  
  /**
   * Creates an array of entries for a specific location.
   * @param integer $minWeekNo start week number : index in array.
   * @param integer $maxWeekNo end week number : index in array
   * @param array $weekList list of samples in a particular week for the location.
   */
  private static function report_calendar_summary_initLoc2($minWeekNo, $maxWeekNo, $inWeeks){
  	$locationArray= self::$initLoc;
  	for($weekno = $minWeekNo; $weekno <= $maxWeekNo; $weekno++) {
  		$locationArray[$weekno]['hasData']=isset($inWeeks[$weekno]);
    }
  	return $locationArray;
  }
  
  /**
   * @todo: document this method
   * @param array $summaryArray
   * @param array $locationArray
   * @param integer $numSamples
   * @param integer $minWeekNo
   * @param integer $maxWeekNo
   * @param string $taxon
    *@param array $options
   */
  private static function report_calendar_summary_processEstimates(&$summaryArray, $locationArray, $numSamples, $minWeekNo, $maxWeekNo, $weekList, $taxonID, $taxon, $options, &$download) {
  	switch($options['summaryDataCombining']){
      case 'max':
        for($i = $minWeekNo; $i <= $maxWeekNo; $i++)
            $locationArray[$i]['summary'] = max($locationArray[$i]['max'], $locationArray[$i]['sampleTotal']);
        break;
      case 'sample':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
          if($locationArray[$i]['numSamples'])
            $locationArray[$i]['summary'] = ($locationArray[$i]['total'].'.0')/$locationArray[$i]['numSamples'];
          else $locationArray[$i]['summary'] = 0;
          if($locationArray[$i]['summary']>0 && $locationArray[$i]['summary']<1) $locationArray[$i]['summary']=1;
        }
        break;
      case 'location':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++) {
      	  $count=isset($numSamples[$i]) ? count($numSamples[$i]) : 0;
          if($count) $locationArray[$i]['summary'] = ($locationArray[$i]['total'].'.0')/$count;
          else $locationArray[$i]['summary'] = 0;
          if($locationArray[$i]['summary']>0 && $locationArray[$i]['summary']<1) $locationArray[$i]['summary']=1;
        }
        break;
      default : 
      case 'add':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['summary'] = $locationArray[$i]['total'];
        break;
    }
    if($options['summaryDataCombining'] == 'sample' || $options['summaryDataCombining'] == 'location') // other 2 are interger anyway : preformance
     switch($options['dataRound']){
      case 'nearest':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          if($locationArray[$i]['summary']) $locationArray[$i]['summary'] = (int)round($locationArray[$i]['summary']);
        break;
      case 'up':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          if($locationArray[$i]['summary']) $locationArray[$i]['summary'] = (int)ceil($locationArray[$i]['summary']);
        break;
      case 'down':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          if($locationArray[$i]['summary']) $locationArray[$i]['summary'] = (int)floor($locationArray[$i]['summary']);
        break;
      case 'none':
      default : break;
    }
    $anchors=explode(',',$options['zeroPointAnchor']);
    $firstAnchor = false;
    $lastAnchor = false;
    if(count($anchors)>0)
      $firstAnchor = $anchors[0]!='' ? $anchors[0] : false;
    if(count($anchors)>1)
      $lastAnchor = $anchors[1]!='' ? $anchors[1] : false;
    $thisLocation=false;
    for($i= $minWeekNo, $foundFirst=false; $i <= $maxWeekNo; $i++){
      if(!$foundFirst) {
        if(($locationArray[$i]['hasData'])){
          if(($firstAnchor===false || $i-1>$firstAnchor) && $options['firstValue']=='half') {
            $locationArray[$i-1]['estimates'] = $locationArray[$i]['summary']/2;
            $locationArray[$i-1]['hasEstimates'] = true;
          }
          $foundFirst=true;
        }
      }
      if(!$thisLocation && $locationArray[$i]['numSamples'] > 0)
        $thisLocation = $locationArray[$i]['location'];
      if($foundFirst){
       $locationArray[$i]['estimates'] = $locationArray[$i]['summary'];
       $locationArray[$i]['hasEstimates'] = true;
       if(!$locationArray[$i+1]['hasData']) {
        for($j= $i+2; $j <= $maxWeekNo; $j++)
          if($locationArray[$j]['hasData']) break;
        if($j <= $maxWeekNo) { // have found another value later on, so interpolate between them
          for($m=1; $m<($j-$i); $m++) {
            $locationArray[$i+$m]['estimates']=$locationArray[$i]['summary']+$m*($locationArray[$j]['summary']-$locationArray[$i]['summary'])/($j-$i);
            $locationArray[$i+$m]['hasEstimates'] = true;
          }
          $i = $j-1;
        } else {
          if(($lastAnchor===false || $i+1<$lastAnchor) && ($i-1>$firstAnchor) && $options['lastValue']=='half'){
            $locationArray[$i+1]['estimates']= $locationArray[$i]['summary']/2;
            $locationArray[$i+1]['hasEstimates'] = true;
          }
          $i=$maxWeekNo+1;
        }
       }
      }
    }
    switch($options['dataRound']){
      case 'nearest':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['estimates'] = round($locationArray[$i]['estimates']);
        break;
      case 'up':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['estimates'] = ceil($locationArray[$i]['estimates']);
        break;
      case 'down':
        for($i= $minWeekNo; $i <= $maxWeekNo; $i++)
          $locationArray[$i]['estimates'] = floor($locationArray[$i]['estimates']);
        break;
      case 'none':
      default : break;
    }
    // add the location array into the summary data.
    foreach($locationArray as $weekno => $data){
      if($taxonID !== null){ // don't include lines for the sample only entries
        if($data['hasData']) {
          $download .= '"'.$thisLocation.'"%2C'.
            ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? $weekno.'%2C' : '').
            $weekList[$weekno].'%2C'.$taxon.'%2C'.lang::get('Actual').'%2C'.$data['summary'].'%0A';
        } else if($options['includeEstimatesData'] && $data['hasEstimates']){
          $download .= '"'.$thisLocation.'"%2C'.
            ($options['tableHeaders'] == 'both' || $options['tableHeaders'] == 'number' ? $weekno.'%2C' : '').
            $weekList[$weekno].'%2C'.$taxon.'%2C'.lang::get('Estimate').'%2C'.$data['estimates'].'%0A';
        }
      }
      if(isset($summaryArray[$taxonID])) {
        if(isset($summaryArray[$taxonID][$weekno])){
          $summaryArray[$taxonID][$weekno]['hasEstimates'] |= $data['hasEstimates'];
          $summaryArray[$taxonID][$weekno]['hasData'] |= $data['hasData'];
          $summaryArray[$taxonID][$weekno]['summary'] += (int)$data['summary'];
          $summaryArray[$taxonID][$weekno]['estimates'] += (int)$data['estimates'];
          if($data['hasEstimates'] && !$data['hasData']) {
            $summaryArray[$taxonID][$weekno]['estimatesLocations'] .= ($summaryArray[$taxonID][$weekno]['estimatesLocations']=""?' : ':'').$thisLocation;
          }
          $summaryArray[$taxonID][$weekno]['forcedZero'] &= $data['forcedZero'];
        } else {
          $summaryArray[$taxonID][$weekno] = array('summary'=>(int)$data['summary'], 'estimates'=>(int)$data['estimates'], 'forcedZero' => $data['forcedZero'], 'hasEstimates' => $data['hasEstimates'], 'hasData' => $data['hasData'], 'estimatesLocations' => ($data['hasEstimates'] && !$data['hasData'] ? $thisLocation : ''));
        }
      } else {
        $summaryArray[$taxonID] = array($weekno => array('summary'=>(int)$data['summary'], 'estimates'=>(int)$data['estimates'], 'forcedZero' => $data['forcedZero'], 'hasEstimates' => $data['hasEstimates'], 'hasData' => $data['hasData'], 'estimatesLocations' => ($data['hasEstimates'] && !$data['hasData'] ? $thisLocation : '')));
      }
    }
  }
  
  /**
   * Applies defaults to the options array passed to a report calendar summary control.
   * @param array $options Options array passed to the control.
   * @return array The processed options array.
   */
  private static function get_report_calendar_summary_options($options) {
    global $user;
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'calendar-report-output', // this needs to be set explicitly when more than one report on a page
      'tableContainerID' => 'tablediv-container',
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
      'rowGroupID'=>'taxa_taxon_list_id',
      'chartContainerID' => 'chartdiv-container',
      'chartID' => 'chartdiv',
      'chartClass' => 'ui-widget ui-widget-content ui-corner-all',
      'headerClass' => 'ui-widget-header ui-corner-all',
      'height' => 400,
      // 'width' is optional
      'chartType' => 'line', // bar, pie
      'rendererOptions' => array(),
      'legendOptions' => array(),
      'axesOptions' => array(),
      'includeRawData' => true,
      'includeSummaryData' => true,
      'includeEstimatesData' => false,
      'includeTableTotalColumn' => true,
      'includeTableTotalRow' => true,
      'tableHeaders' => 'date',
      'rawDataCombining' => 'add',
      'dataRound' => 'nearest',
      'avgFieldRound' => 'nearest',
      'avgFields' => '',
      'zeroPointAnchor' => ',',
      'interpolation' => 'linear',
      'firstValue' => 'none',
      'lastValue' => 'none',
      'highlightEstimates' => false,
      'includeRawGridDownload' => false,
      'includeRawListDownload' => true,
      'includeSummaryGridDownload' => false,
      'includeEstimatesGridDownload' => false,
      'includeListDownload' => true,
      'downloadFilePrefix' => ''
    ), $options);
    $options["extraParams"] = array_merge(array(
      'date_from' => $options['date_start'],
      'date_to' => $options['date_end'],
//      'user_id' => '', // CMS User, not Indicia User.
//      'smpattrs' => '',
      'occattrs' => ''), $options["extraParams"]);

    if (isset($options["extraParams"]['user_id'])) {
      $options["extraParams"]['cms_user_id'] = $options["extraParams"]['user_id'];
      if (function_exists('module_exists') && module_exists('easy_login')) {
        $account = user_load($options["extraParams"]['user_id']);
        if (function_exists('profile_load_profile'))
          profile_load_profile($account); /* will not be invoked for Drupal7 where the fields are already in the account object */
        if(isset($account->profile_indicia_user_id))
          $options["extraParams"]['user_id'] = $account->profile_indicia_user_id;
      }
    }
    
    // Note for the calendar reports, the user_id is assumed to be the CMS user id as recorded in the CMS User ID attribute,
    // not the Indicia user id.
    return $options;
  }

}
