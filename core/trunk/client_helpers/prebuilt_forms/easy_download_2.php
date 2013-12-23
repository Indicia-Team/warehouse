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

require_once ('includes/report_filters.php');
 
/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * A quick and easy way to download data you have access to. 
 */
class iform_easy_download_2 {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_easy_download_2_definition() {
    return array(
      'title'=>'Easy download 2',
      'category' => 'Utilities',
      'description'=>'A page for quick and easy download of the data you have access to. Improved integration with record sharing and permissions.',
      'helpLink'=>'https://indicia-docs.readthedocs.org/en/latest/site-building/iform/prebuilt-forms/easy-download.html'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array(
      array(
        'name'=>'my_records_user_filter_permission',
        'caption'=>'Download user filter permission - my records',
        'description'=>'Provide the name of the permission required to allow download of my records. '.
            'Leave blank to disallow download of my records.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'all_records_user_filter_permission',
        'caption'=>'Download user filter permission - all records',
        'description'=>'Provide the name of the permission required to allow download of all records. '.
            'Leave blank to disallow download of all records.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'indicia data admin'
      ),
      array(
        'name'=>'reporting_type_permission',
        'caption'=>'Download type permission - reporting',
        'description'=>'Provide the name of the permission required to allow download of reporting recordsets. '.
            'Leave blank to disallow this download type.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'peer_review_type_permission',
        'caption'=>'Download type permission - peer review',
        'description'=>'Provide the name of the permission required to allow download of peer review recordsets. '.
            'Leave blank to disallow this download type.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'verification_type_permission',
        'caption'=>'Download type permission - verification',
        'description'=>'Provide the name of the permission required to allow download of verification recordsets. '.
            'Leave blank to disallow this download type.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'verification'
      ),
      array(
        'name'=>'data_flow_type_permission',
        'caption'=>'Download type permission - data flow',
        'description'=>'Provide the name of the permission required to allow download of data flow recordsets. '.
            'Leave blank to disallow this download type.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'indicia data admin'
      ),
      array(
        'name'=>'moderation_type_permission',
        'caption'=>'Download type permission - moderation',
        'description'=>'Provide the name of the permission required to allow download of moderation recordsets. '.
            'Leave blank to disallow this download type.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'csv_format_permission',
        'caption'=>'Download format permission - csv',
        'description'=>'Provide the name of the permission required to allow download of csv format. '.
            'Leave blank to disallow this download format.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'tsv_format_permission',
        'caption'=>'Download format permission - csv',
        'description'=>'Provide the name of the permission required to allow download of csv format. '.
            'Leave blank to disallow this download format.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'kml_format_permission',
        'caption'=>'Download format permission - csv',
        'description'=>'Provide the name of the permission required to allow download of csv format. '.
            'Leave blank to disallow this download format.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'gpx_format_permission',
        'caption'=>'Download format permission - csv',
        'description'=>'Provide the name of the permission required to allow download of csv format. '.
            'Leave blank to disallow this download format.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'nbn_format_permission',
        'caption'=>'Download format permission - csv',
        'description'=>'Provide the name of the permission required to allow download of csv format. '.
            'Leave blank to disallow this download format.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>'authenticated user'
      ),
      array(
        'name'=>'survey_id',
        'caption'=>'Survey for download',
        'description'=>'Select the survey to download data for, or leave blank to allow user selection.',
        'type'=>'select',
        'required'=>false,
        'table'=>'survey',
        'valueField'=>'id',
        'captionField'=>'title',
        'sharing'=>'data_flow',
        'siteSpecific'=>true
      ),
      array(
        'name'=>'report_csv',
        'caption'=>'CSV download format report',
        'description'=>'Choose the report used for CSV downloads. Report should be compatible with the standard report parameters.',
        'type'=>'report_helper::report_picker',
        'required'=>true,
        'default'=>'library/occurrences/filterable_occurrences_download'
      ),
      array(
        'name'=>'report_params_csv',
        'caption'=>'CSV Additional parameters',
        'description'=>'Additional parameters to provide to the report when doing a CSV download. One per line, param=value format.',
        'type'=>'textarea',
        'required'=>false,
        'default'=>"smpattrs=\noccattrs=\n"
      ),
      array(
        'name'=>'report_tsv',
        'caption'=>'TSV download format report',
        'description'=>'Choose the report used for TSV downloads. Report should be compatible with the standard report parameters.',
        'type'=>'report_helper::report_picker',
        'required'=>true,
        'default'=>'library/occurrences/filterable_occurrences_download'
      ),
      array(
        'name'=>'report_params_tsv',
        'caption'=>'TSV Additional parameters',
        'description'=>'Additional parameters to provide to the report when doing a TSV download. One per line, param=value format.',
        'type'=>'textarea',
        'required'=>false,
        'default'=>"smpattrs=\noccattrs=\n"
      ),
      array(
        'name'=>'report_kml',
        'caption'=>'KML download format report',
        'description'=>'Choose the report used for KML downloads. Report should be compatible with the standard report parameters and return a WKT for the geometry of the record '.
            'transformed to EPSG:4326.',
        'type'=>'report_helper::report_picker',
        'required'=>true,
        'default'=>'library/occurrences/filterable_occurrences_download_gis'
      ),
      array(
        'name'=>'report_params_kml',
        'caption'=>'KML Additional parameters',
        'description'=>'Additional parameters to provide to the report when doing a KML download. One per line, param=value format.',
        'type'=>'textarea',
        'required'=>false,
        'default'=>"smpattrs=\noccattrs=\n"
      ),
      array(
        'name'=>'report_gpx',
        'caption'=>'GPX download format report',
        'description'=>'Choose the report used for GPX downloads. Report should be compatible with the standard report parameters and return a WKT for the geometry of the record '.
            'transformed to EPSG:4326.',
        'type'=>'report_helper::report_picker',
        'required'=>true,
        'default'=>'library/occurrences/filterable_occurrences_download_gis'
      ),
      array(
        'name'=>'report_params_gpx',
        'caption'=>'GPX Additional parameters',
        'description'=>'Additional parameters to provide to the report when doing a GPX download. One per line, param=value format.',
        'type'=>'textarea',
        'required'=>false,
        'default'=>"smpattrs=\noccattrs=\n"
      ),
      array(
        'name'=>'report_nbn',
        'caption'=>'NBN download format report',
        'description'=>'Choose the report used for NBN downloads. Report should be compatible with the standard report parameters.',
        'type'=>'report_helper::report_picker',
        'required'=>true,
        'default'=>'library/occurrences/filterable_nbn_exchange'
      ),
      array(
        'name'=>'report_params_nbn',
        'caption'=>'NBN Additional parameters',
        'description'=>'Additional parameters to provide to the report when doing an NBN download. One per line, param=value format.',
        'type'=>'textarea',
        'required'=>false,
        'default'=>"smpattrs=\noccattrs=\n"
      ),
      array(
        'name'=>'limit',
        'caption'=>'Limit to number of records',
        'description'=>'For performance reasons, unlimited downloads are not recommended. Set this to control the number of records '.
            'that can be downloaded at one time, or set to 0 for no limit.',
        'type'=>'text_input',
        'required'=>false,
        'default'=>20000
      )
    );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $node, $response=null) {
    $conn = iform_get_connection_details($node);
    data_entry_helper::$js_read_tokens = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    if (!empty($_POST))
      self::do_data_services_download($args, $node);
    // set up a control wrap template, to make it easy to turn them on and off
    global $indicia_templates;
    $indicia_templates['controlWrap']="<div id=\"wrap-{id}\">{control}</div>\n";
    $conn = iform_get_connection_details($node);
    data_entry_helper::$js_read_tokens = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    $userFilterOptions = self::get_user_filter_options($args);
    $types = self::get_download_types($args);
    $formats = self::get_download_formats($args);
    if (count($userFilterOptions)===0)
      return 'This download page is configured so that no download user filter options are available.';
    if (count($types)===0)
      return 'This download page is configured so that no download type options are available.';
    if (count($formats)===0)
      return 'This download page is configured so that no download format options are available.';
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.helper_base::array_to_query_string($reload['params']);
    $r = '<form method="POST" action="'.$reloadPath.'">';
    $r .= '<fieldset><legend>'.lang::get('Records to download').'</legend>';
    if (count($types)===1) 
      $r .= '<input type="hidden" name="download-type" value="'.implode('', array_keys($types)).'"/>';
    else 
      $r .= data_entry_helper::select(array(
        'fieldname'=>'download-type',
        'label'=>lang::get('Download type'),
        'lookupValues'=>$types,
        'class'=>'control-width-5',
        'helpText'=>'Select the type of download you require, i.e. the purpose for the data. This defines which records are available to download.'
      ));
    $r .= data_entry_helper::select(array(
      'fieldname'=>'download-subfilter',
      'label'=>lang::get('Filter to apply'),
      'lookupValues'=>array(),
      'class'=>'control-width-5',
      'helpText'=>lang::get('Optionally select from the available filters.')
    ));
    $r .= '</fieldset><fieldset><legend>'.lang::get('Limit the records').'</legend>';
    if (count($userFilterOptions)===1) 
      $r .= '<input type="hidden" name="user-filter" value="'.implode('', array_keys($userFilterOptions)).'"/>';
    else 
      $r .= data_entry_helper::select(array(
        'fieldname'=>'user-filter',
        'label'=>lang::get('Users to include'),
        'lookupValues'=>$userFilterOptions,
        'class'=>'control-width-5',
        'helpText'=>'Select the users whose records you want to include.'
      ));
    if (empty($args['survey_id'])) {
      // put up an empty surveys drop down. AJAX will populate it.
      $r .= data_entry_helper::select(array(
        'fieldname' => 'survey_id',
        'label' => lang::get('Survey to include'),
        'helpText' => 'Choose a survey, or <all> to not filter by survey.',
        'lookupValues' => array(),
        'class' => 'control-width-5'
      ));
    } else 
      $r .= '<input type="hidden" name="survey_id" value="'.$args['survey_id'].'"/>';
    // Let the user pick the date range to download.
    $r .= data_entry_helper::date_picker(array(
      'fieldname' => 'date_from',
      'label' => lang::get('Start Date'),
      'helpText' => 'Leave blank for no start date filter',
      'class' => 'control-width-4'
    ));
    $r .= data_entry_helper::date_picker(array(
      'fieldname' => 'date_to',
      'label' => lang::get('End Date'),
      'helpText' => 'Leave blank for no end date filter',
      'class' => 'control-width-4'
    ));
    $r .= '</fieldset><fieldset><legend>'.lang::get('Format').'</legend>';
    $r .= '<label>Download options:</label>';
    foreach($formats as $format=>$label) {
      $r .= "<input class=\"inline-control\" type=\"submit\" name=\"format\" value=\"$label\"/>\n";
    }
    $r .= '</fieldset></form>';
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="'.url('iform/ajax/easy_download_2')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.nid = "'.$node->nid."\";\n";
    return $r;
  } 
  
  /**
   * Works out the list of user filters that are available, e.g. my records, all records.
   * @param type $args
   * @return array Associative array of user filters
   */
  private static function get_user_filter_options($args) {
    $r = array();
    if ($args['my_records_user_filter_permission'] && user_access('my_records_user_filter_permission')) 
      $r['my']=lang::get('Me');
    if ($args['all_records_user_filter_permission'] && user_access('all_records_user_filter_permission')) 
      $r['all']=lang::get('All users');
    return $r;
  }
  
  /** 
   * Works out the list of download type options available to the user. This is the list
   * of sharing modes they have permission for, combined with any filters defined for the user
   * which define their permissions for that sharing type.
   * @param array $args Form parameters
   * @return array Associative array of download types
   */
  private static function get_download_types($args) {
    $r = array();
    // we'll store any standard params filters that are user optional ones into js data, so the UI can allow
    // selection as appropriate.
    data_entry_helper::$javascript.="indiciaData.optionalFilters={};\n";
    foreach ($args as $arg=>$value) {
      if ($value && preg_match('/^([a-z_]+)_type_permission$/', $arg, $matches) && user_access($value)) {
        // download type available. What they can actually download might be limited by a context filter...
        $sharingType=  ucwords(str_replace('_', ' ', $matches[1]));
        $sharingTypeCode=substr($sharingType, 0, 1);
        $gotPermissionsFilterForThisType=false;
        // a place to store optional filters of this type in the js data
        data_entry_helper::$javascript.="indiciaData.optionalFilters.$sharingTypeCode={};\n";
        // load their filters
        $filters = report_filters_load_existing(data_entry_helper::$js_read_tokens, $sharingTypeCode);
        foreach ($filters as $filter) {
          // the filter either defines their permissions, or is a user defined filter which they can optionally apply
          if ($filter['defines_permissions']==='t') {
            $r["$sharingTypeCode filter $filter[id]"]="$sharingType - $filter[title]";
            $gotPermissionsFilterForThisType=true;
          } else {
            data_entry_helper::$javascript.="indiciaData.optionalFilters.$sharingTypeCode.filter_$filter[id]='$filter[title]';\n";
          }
        }
        // If no permissions defined for this sharing type for this user, then allow an all-access download
        if (!$gotPermissionsFilterForThisType) 
          $r[$sharingTypeCode]=$sharingType;    
      }
    }
    return $r;
  }
  
  /** 
   * Works out the list of download format options available to the user. This depends on the 
   * permissions settings in the form configuration
   * @param array $args Form parameters
   * @return array Associative array of download formats
   */
  private static function get_download_formats($args) {
    $r = array();
    foreach ($args as $arg=>$value) {
      if ($value && preg_match('/^([a-z_]+)_format_permission$/', $arg, $matches) && user_access($value)) {
        $r[$matches[1]]=lang::get("format_$matches[1]");
      }
    }
    return $r;
  }
  
  /**
   * An ajax handler which returns the surveys that are available for a given sharing type.
   * @param type $website_id
   * @param type $password
   * @param type $node
   */
  public static function ajax_surveys_for_sharing_type($website_id, $password, $node) {
    iform_load_helpers(array('data_entry_helper'));
    // @todo filter by the available context filters if appropriate
    $readAuth = array(
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    );
    $surveys = data_entry_helper::get_population_data(array(
      'table'=>'survey',
      'extraParams'=>$readAuth,
      'sharing'=>$_GET['sharing_type']
    ));
    $r = array();
    foreach ($surveys as $survey) 
      $r[$survey['id']]=$survey['title'];
    echo json_encode($r);
  }
  
  /**
   * Performs the download.
   * @global array $indicia_templates
   * @param type $args
   * @param type $node
   */
  private static function do_data_services_download($args, $node) {
    $format=self::get_report_format($args);
    iform_load_helpers(array('report_helper'));
    $conn = iform_get_connection_details($node);
    $params = self::build_params($args, data_entry_helper::$js_read_tokens, $format);
    global $indicia_templates;
    // let's just get the URL, not the whole anchor element
    $indicia_templates['report_download_link'] = '{link}';
    $limit = ($args['limit']==0 ? '' : $args['limit']); // unlimited or limited
    $sharing = substr($_POST['download-type'], 0, 1);
    
    $url = report_helper::report_download_link(array(
      'readAuth'=>data_entry_helper::$js_read_tokens,
      'dataSource'=>$args["report_$format"],
      'extraParams'=>$params,
      'format'=>$format,
      'sharing'=>$sharing,
      'itemsPerPage'=>$limit
    ));
    header("Location: $url");
  }
  
  /**
   * Uses the format value in the $_POST data to work out the machine-readable format
   * code, e.g. csv or nbn.
   * @return string Format specifier.
   */
  private static function get_report_format($args) {
    $formats = self::get_download_formats($args);
    $r = false;
    foreach ($formats as $code => $label) {
      if ($_POST['format']===$label) {
        $r = $code;
        break;
      }
    }
    if (!$r)
      throw new exception("Unrecognised download format $_POST[format]");
    return $r;
  }
  
  /**
   * Builds the parameters array to apply which filters the download report.
   * @param array Form parameters.
   * @param string $format Download format (e.g. csv, tsv, nbn).
   * @return array Parameters array to apply to the report.
   * @throws exception Thrown if requested download type not allowed for this user.
   */
  private static function build_params($args, $format) {
    $availableTypes = self::get_download_types($args, data_entry_helper::$js_read_tokens);
    if (!array_key_exists($_POST['download-type'], $availableTypes))
      throw new exception('Selected download type not authorised');
    $sharing = substr($_POST['download-type'], 0, 1);
    $params=array();
    // Have we got any filters to apply?
    if (strlen($_POST['download-type'])>1 || !empty($_POST['download-subfilter'])) {
      $filterData = report_filters_load_existing(data_entry_helper::$js_read_tokens, $sharing);
      if (preg_match('/^[RPVDM]+ filter (\d+)$/', $_POST['download-type'], $matches)) 
        // download type includes a context filter
        self::apply_filter_to_params($filterData, $matches[1], '_context', $params);
      if (!empty($_POST['download-subfilter'])) {
        // a download subfilter has been selected
        self::apply_filter_to_params($filterData, $_POST['download-subfilter'], '', $params);
      }
    }
    if (!empty($_POST['user-filter']) && $_POST['user-filter']==='my')
      $params['my_records']=1;
    if (!empty($_POST['survey_id']))
      $params['survey_id']=$_POST['survey_id'];
    if (!empty($_POST['date_from']) && $_POST['date_from']!==lang::get('Click here'))
      $params['date_from']=$_POST['date_from'];
    if (!empty($_POST['date_to']) && $_POST['date_to']!==lang::get('Click here'))
      $params['date_to']=$_POST['date_to'];
    return $params;
  }
  
  /**
   * Loads the definition of a saved filter onto the params we are using to filter the report.
   * @param array $filterData List of filters loaded from the db
   * @param integer $filterId ID of the filter to load
   * @param string $paramSuffix Suffix for the parameter names to build. If this is a context filter, then
   * set this to '_context'.
   * @param type $params Params array which will be updated with those loaded from the saved filter.
   */
  private static function apply_filter_to_params($filterData, $filterId, $paramSuffix, &$params) {
    foreach ($filterData as $filterDef) {
      if ($filterDef['id']===$filterId) {
        $filter = json_decode($filterDef['definition'], true);
        foreach ($filter as $field=>$value) {
          // to enforce this as the overall context, defining the maximum limit of the query results, append _context to the field names.
          // This prevents the filter negating the survey or date filter defined on the page.
          $params["$field$paramSuffix"]=$value;
        }
        break;
      }
    }
  }
}
