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
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * A quick and easy way to download data you have access to. 
 */
class iform_easy_download {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_easy_download_definition() {
    return array(
      'title'=>'Easy download',
      'category' => 'Utilities',
      'description'=>'A page for quick and easy download of the data you have access to.',
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
        'name'=>'permission',
        'caption'=>'Permission required for downloading other people\'s data if you have expertise',
        'description'=>'Set to the name of a permission which is required in order to be able to download other people\'s data. Also enables the NBN download format.',
        'type'=>'text_input',
        'required'=>true,
        'default'=>'verification'
      ),
      array(
        'name'=>'allow_my_data',
        'caption'=>'Allow my data download?',
        'description'=>'Does this page include an option to download your own personal data?',
        'type'=>'checkbox',
        'required'=>false,
        'default'=>1
      ),
      array(
        'name'=>'allow_experts_data',
        'caption'=>'Allow expert\'s data download?',
        'description'=>'Does this page include an option to download data that the user is an expert for, if they are an expert?',
        'type'=>'checkbox',
        'required'=>false,
        'default'=>1
      ),
      array(
        'name'=>'allow_all_data',
        'caption'=>'Allow all data download?',
        'description'=>'Does this page include an option to download all data that matches the filter?',
        'type'=>'checkbox',
        'required'=>false,
        'default'=>0
      ),
      array(
        'name'=>'csv_format',
        'caption'=>'CSV format download?',
        'description'=>'Is CSV format available as a download option?',
        'type'=>'select',
        'options'=>array(
          'no'=>'No',
          'yes'=>'Yes',
          'expert'=>'Yes, but only for experts'
        ),
        'required'=>true,
        'default'=>'yes'
      ),
      array(
        'name'=>'nbn_format',
        'caption'=>'NBN format download?',
        'description'=>'Is NBN format available as a download option?',
        'type'=>'select',
        'options'=>array(
          'no'=>'No',
          'yes'=>'Yes',
          'expert'=>'Yes, but only for experts'
        ),
        'required'=>true,
        'default'=>'expert'
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
        'default'=>'library/occurrences/occurrences_download_2',
        'siteSpecific'=>true
      ),
      array(
        'name'=>'report_csv',
        'caption'=>'CSV download format report',
        'description'=>'Choose the report used for CSV downloads. Report should be compatible with the explore reports.',
        'type'=>'report_helper::report_picker',
        'required'=>true,
        'default'=>'library/occurrences/occurrences_download_2'
      ),
      array(
        'name'=>'report_params_csv',
        'caption'=>'CSV Additional parameters',
        'description'=>'Additional parameters to provide to the report when doing a CSV download. One per line, param=value format.',
        'type'=>'textarea',
        'required'=>false,
        'default'=>"smpattrs=\noccattrs=\nsearchArea=\nidlist=\nquality=!R\n"
      ),
      array(
        'name'=>'report_nbn',
        'caption'=>'NBN download format report',
        'description'=>'Choose the report used for NBN downloads. Report should be compatible with the explore reports.',
        'type'=>'report_helper::report_picker',
        'required'=>true,
        'default'=>'library/occurrences/nbn_exchange'
      ),
      array(
        'name'=>'report_params_nbn',
        'caption'=>'NBN Additional parameters',
        'description'=>'Additional parameters to provide to the report when doing an NBN download. One per line, param=value format.',
        'type'=>'textarea',
        'required'=>false,
        'default'=>"smpattrs=\noccattrs=\nsearchArea=\nidlist=\nquality=V\n"
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
    // Do they have expert access?
    $expert = (function_exists('user_access') && user_access($args['permission']));
    
    // Find out which types of filters and formats are available to the user
    $filters = self::get_filters($args);
    $formats = array();
    if ($args['csv_format']==='yes' || ($args['csv_format']==='expert' && $expert))
      array_push($formats, 'csv');
    if ($args['nbn_format']==='yes' || ($args['nbn_format']==='expert' && $expert))
      array_push($formats, 'nbn');
    if (count($filters)===0)
      return 'This download page is configured so that no filter options are available.';
    if (count($formats)===0)
      return 'This download page is configured so that no download format options are available.';
    
    if (!empty($_POST))
      self::do_download($args, $filters);
    $conn = iform_get_connection_details($node);
    $readAuth = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    iform_load_helpers(array('data_entry_helper'));
    $reload = data_entry_helper::get_reload_link_parts();  
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.helper_base::array_to_query_string($reload['params']);
    $r = '<form method="POST" action="'.$reloadPath.'">';
    $r .= '<fieldset><legend>'.lang::get('Filters').'</legend>';
    if (count($filters)===0)
      return 'This download page is configured so that no filter options are available.';
    elseif (count($filters)===1) {
      $r .= '<input type="hidden" name="user-filter" value="'.implode('', array_keys($filters)).'"/>';
      // Since there is only one option, we may as well tell the user what it is.
      drupal_set_title(implode('', array_values($filters)));
      if (implode('', array_keys($filters))==='mine')
        $r .= '<p>'.lang::get('Use this form to download your own records.').'</p>';
    }
    else {
      $r .= data_entry_helper::radio_group(array(
        'label' => lang::get('User filter'),
        'fieldname'=>'user-filter',
        'lookupValues' => $filters,
        'default'=>(empty($_POST['user-filter']) ? 'mine' : $_POST['user-filter'])
      ));
    } 
    if (empty($args['survey_id'])) {
      // A survey picker when downloading my data
      $r .= '<div id="survey_all">';
      $r .= data_entry_helper::select(array(
        'fieldname' => 'survey_id_all',
        'label' => lang::get('Survey to include'),
        'table' => 'survey',
        'valueField' => 'id',
        'captionField' => 'title',
        'helpText' => 'Choose a survey, or <all> to not filter by survey.',
        'blankText' => '<all>',
        'class' => 'control-width-4',
        'extraParams' => $readAuth + array('sharing' => 'data_flow', 'orderby'=>'title')
      ));
      $r .= '</div>';
      // A survey picker when downloading data you are an expert for
      $surveys_expertise=hostsite_get_user_field('surveys_expertise');
      if ($surveys_expertise) {
        $surveys_expertise = unserialize($surveys_expertise);
        $surveysFilter = array('query'=>json_encode(array('in'=>array('id' => $surveys_expertise))));
      } else {
        // no filter as there are no specific surveys this user is an expert for
        $surveysFilter=array();
      }
      $r .= '<div id="survey_expertise">';
      $r .= data_entry_helper::select(array(
        'fieldname' => 'survey_id_expert',
        'label' => lang::get('Survey to include'),
        'table' => 'survey',
        'valueField' => 'id',
        'captionField' => 'title',
        'helpText' => 'Choose a survey, or <all> to not filter by survey.',
        'blankText' => '<all>',
        'class' => 'control-width-4',
        'extraParams' => $readAuth + array('sharing' => 'data_flow', 'orderby'=>'title') + $surveysFilter
      ));
      $r .= '</div>';
    }
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
    $r .= '</fieldset>';
    $r .= '<fieldset><legend>'.lang::get('Downloads').'</legend>';
    $r .= '<label>Download options:</label><input class="inline-control" type="submit" name="format" value="'.lang::get('Download Spreadsheet').'"/>';
    if ($expert) {
      $r .= '<input class="inline-control" type="submit" name="format" value="'.lang::get('Download NBN Format').'"/>';
      $r .= '<p class="helpText">'.lang::get('Note that the NBN format download will only include verified data and excludes records where the date or spatial reference is not compatible with the NBN Gateway.').'</p>';
    }
    $r .= '</fieldset></form>';
    return $r;
  } 
  
  /**
   * Returns an array of the available types of filter.
   * @param array $args Form arguments
   * @return array Associative array of filter type 
   */
  private static function get_filters($args) {
    $filters = array();
    if ($args['allow_my_data'])
      $filters['mine']=lang::get('Download my records');
    if ($args['allow_experts_data'])
      $filters['expert']=lang::get('Download all records I have access to as an expert');
    if ($args['allow_all_data'])
      $filters['all']=lang::get('Download all records');
    return $filters;
  }
  
  /** 
   * Handles a request for download. Works out which type of request it is and calls the appropriate function.
   */
  private static function do_download($args) {
    if ($_POST['format']===lang::get('Download Spreadsheet'))
      self::do_data_services_download($args, 'csv');
    elseif ($_POST['format']===lang::get('Download NBN Format'))
      self::do_data_services_download($args, 'nbn');
  }
  
  private static function do_data_services_download($args, $format) {
    iform_load_helpers(array('report_helper'));
    $conn = iform_get_connection_details($node);
    $readAuth = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    $filter = self::build_filter($args, $readAuth, $format);
    global $indicia_templates;
    // let's just get the URL, not the whole anchor element
    $indicia_templates['report_download_link'] = '{link}';
    $limit = ($args['limit']==0 ? '' : $args['limit']); // unlimited or limited
    $url = report_helper::report_download_link(array(
      'readAuth'=>$readAuth,
      'dataSource'=>$args["report_$format"],
      'extraParams'=>$filter,
      'format'=>$format,
      'sharing'=>'data_flow',
      'itemsPerPage'=>$limit
    ));
    header("Location: $url");
  }
  
  private static function build_filter($args, $readAuth, $format) {
    require_once('includes/user.php');
    $location_expertise = hostsite_get_user_field('location_expertise');
    $taxon_groups_expertise = hostsite_get_user_field('taxon_groups_expertise');
    $taxon_groups_expertise = $taxon_groups_expertise ? unserialize($taxon_groups_expertise) : null;
    $filters = self::get_filters($args);
    $filterToApply = $_POST['user-filter'];
    if (!array_key_exists($filterToApply, $filters))
      throw new exception('Selected filter type not authorised');
    // get the surveys the user could have picked from
    if ($filterToApply==='expert') {
      $surveys_expertise = hostsite_get_user_field('surveys_expertise');
      $available_surveys = $surveys_expertise ? unserialize($surveys_expertise) : null;
    } else {
      // set to blank - in the report filter this will act as not filtered
      $available_surveys = '';
    }
    // We are downloading either a configured survey, a selected single survey, or the surveys the 
    // user can see. The field name used will depend on which of the survey selects were active - 
    // either we are selecting from a list of surveys the user is an expert for, or a list of
    // all surveys.
    $surveyFieldName='survey_id_'.($filterToApply==='expert' ? 'expert' : 'all');
    if (empty($args['survey_id'])) {
      $surveys = empty($_POST[$surveyFieldName]) ? implode(',', $available_surveys) : $_POST[$surveyFieldName];
    } else 
      // survey to load is preconfigured for the form
      $surveys = $args['survey_id'];
    $ownData=$filterToApply==='mine' ? 1 : 0;
    $filters = array_merge(
      array(
        'currentUser'=>hostsite_get_user_field('indicia_user_id'),
        'ownData'=>$ownData,
        'location_id'=>hostsite_get_user_field('location_expertise'),
        'ownLocality'=>!empty($location_expertise) && !$ownData ? 1 : 0,
        'taxon_groups'=>!empty($taxon_groups_expertise) ? implode(',', $taxon_groups_expertise) : '',
        'ownGroups'=>!empty($taxon_groups_expertise) && $taxon_groups_expertise && !$ownData ? 1 : 0,
        'surveys'=>$surveys,
        'ownSurveys'=>empty($surveys) ? 0 : 1
      ), get_options_array_with_user_data($args["report_params_$format"])
    );
    if (!empty($_POST['date_from']) && $_POST['date_from']!==lang::get('Click here'))
      $filters['date_from']=$_POST['date_from'];
    else
      $filters['date_from']='';
    if (!empty($_POST['date_to']) && $_POST['date_to']!==lang::get('Click here'))
      $filters['date_to']=$_POST['date_to'];
    else
      $filters['date_to']='';
    return $filters;
  }

}
