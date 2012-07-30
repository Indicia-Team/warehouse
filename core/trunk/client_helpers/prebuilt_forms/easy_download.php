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
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_easy_download_definition() {
    return array(
      'title'=>'Easy download',
      'category' => 'Utilities',
      'description'=>'A page for quick and easy download of the data you have access to.'
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
        'caption'=>'Permission required for downloading other people\'s data',
        'description'=>'Set to the name of a permission which is required in order to be able to download other people\'s data.',
        'type'=>'text_input',
        'required'=>true,
        'default'=>'verification'
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
   * @todo: Implement this method 
   */
  public static function get_form($args, $node, $response=null) {
    if (!empty($_POST))
      self::do_download($args);
    $conn = iform_get_connection_details($node);
    $readAuth = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    iform_load_helpers(array('data_entry_helper'));
    $reload = data_entry_helper::get_reload_link_parts();  
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.helper_base::array_to_query_string($reload['params']);
    $r = '<form method="POST" action="'.$reloadPath.'">';
    $r .= '<fieldset><legend>'.lang::get('Filters').'</legend>';
    $expert = (function_exists('user_access') && user_access($args['verification']));
    if ($expert) {
      $r .= data_entry_helper::radio_group(array(
        'label' => lang::get('User filter'),
        'fieldname'=>'user-filter',
        'lookupValues' => array(
          'mine'=>'Download my data only',
          'all'=>'Download all data I have access to as an expert'
        ),
        'default'=>(empty($_POST['user-filter']) ? 'mine' : $_POST['user-filter'])
      ));
    } elseif (function_exists('drupal_set_title')) 
      // Only allowed to download your own data, so subtly tell them 
      drupal_set_title(lang::get('Download my records'));
    else
      $r .= '<p>'.lang::get('Use this form to download your own records.').'</p>';
    // A survey picker when downloading my data
    $r .= '<div id="survey_mine">';
    $r .= data_entry_helper::select(array(
      'fieldname' => 'survey_id_mine',
      'label' => lang::get('Survey to include'),
      'table' => 'survey',
      'valueField' => 'id',
      'captionField' => 'title',
      'helpText' => 'Choose a survey, or <all> to not filter by survey.',
      'blankText' => '<all>',
      'class' => 'control-width-4',
      'extraParams' => $readAuth + array('sharing' => 'data_flow')
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
      'fieldname' => 'survey_id_expertise',
      'label' => lang::get('Survey to include'),
      'table' => 'survey',
      'valueField' => 'id',
      'captionField' => 'title',
      'helpText' => 'Choose a survey, or <all> to not filter by survey.',
      'blankText' => '<all>',
      'class' => 'control-width-4',
      'extraParams' => $readAuth + array('sharing' => 'data_flow') + $surveysFilter
    ));
    $r .= '</div>';
    // Put the available 
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
    $url = report_helper::report_download_link(array(
      'readAuth'=>$readAuth,
      'dataSource'=>$args["report_$format"],
      'extraParams'=>$filter,
      'format'=>$format
    ));
    //drupal_set_message($url);
    header("Location: $url");
  }
  
  private static function build_filter($args, $readAuth, $format) {
    require_once('includes/user.php');
    $location_expertise=hostsite_get_user_field('location_expertise');
    $taxon_groups_expertise=hostsite_get_user_field('taxon_groups_expertise');
    $taxon_groups_expertise = $taxon_groups_expertise ? unserialize($taxon_groups_expertise) : null;
    $ownData = isset($_POST['user-filter']) && $_POST['user-filter'] === 'all' ? 0 : 1;
    $surveys_expertise=hostsite_get_user_field('surveys_expertise');
    $surveys_expertise = $surveys_expertise ? unserialize($surveys_expertise) : null;
    // either a selected survey, or the surveys the user can see. The field name used will depend on which of the 2 survey selects were active.
    $surveyFieldName=$ownData ? 'survey_id_mine' : 'survey_id_expertise';
    $surveys = empty($_POST[$surveyFieldName]) ? implode(',', $surveys_expertise) : $_POST[$surveyFieldName];
    $filters = array_merge(
      array(
        'currentUser'=>hostsite_get_user_field('indicia_user_id'),
        'ownData'=>$ownData,
        'location_id'=>hostsite_get_user_field('location_expertise'),
        'ownLocality'=>!empty($location_expertise) && !$ownData ? 1 : 0,
        'taxon_groups'=>implode(',', $taxon_groups_expertise),
        'ownGroups'=>!empty($taxon_groups_expertise) && $taxon_groups_expertise && !$ownData ? 1 : 0,
        'surveys'=>$surveys,
        'ownSurveys'=>((empty($surveys_expertise) || $ownData) && empty($_POST[$surveyFieldName])) ? 0 : 1
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
