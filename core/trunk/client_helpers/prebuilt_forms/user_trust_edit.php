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
 * A page for editing or creating a user trust for verification.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_user_trust_edit {
  
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_user_trust_edit_definition() {
    return array(
      'title'=>'Create or edit a user trust',
      'category' => 'Verification',
      'description'=>'A form for creating or editing user trusts.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array(      
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
    global $indicia_templates;
    iform_load_helpers(array('map_helper','report_helper'));
    // apply defaults
    $args=array_merge(array(
    ), $args);
    $reloadPath = self::getReloadPath();   
    data_entry_helper::$website_id=$args['website_id'];
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    if (!empty($_GET['user_trust_id'])) {
      self::loadExistingUserTrust($_GET['user_trust_id'], $auth, $args);
    }
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    $r .= $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= data_entry_helper::hidden_text(array('fieldname'=>'user_trust:id'));
    $r .= data_entry_helper::autocomplete(array(
      'label'=>lang::get('Recorder to trust'),
      'fieldname'=>'user_trust:user_id',
      'table'=>'user',
      'valueField'=>'id',
      'captionField'=>'person_name',
      'extraParams'=>$auth['read'] + array('view'=>'detail'),
      'class'=>'control-width-4'
    ));
    $col1 = '<p>Define the combination of survey, taxon group and/or location that this recorder is trusted for below.</p>';
    $col1 .= '<fieldset><legend>'.lang::get('Trust settings').'</legend>';
    $col1 .= data_entry_helper::autocomplete(array(
      'label'=>lang::get('Trust records in this survey'),
      'fieldname'=>'user_trust:survey_id',
      'table'=>'survey',
      'valueField'=>'id',
      'captionField'=>'title',
      'blankText'=>'<'.lang::get('any').'>',
      'extraParams'=>$auth['read'] + array('sharing' => 'verification'),
      'class'=>'control-width-4'
    ));
    $col1 .= data_entry_helper::autocomplete(array(
      'label'=>lang::get('Trust records in this taxon group'),
      'fieldname'=>'user_trust:taxon_group_id',
      'table'=>'taxon_group',
      'valueField'=>'id',
      'captionField'=>'title',
      'blankText'=>'<'.lang::get('any').'>',
      'extraParams'=>$auth['read'],
      'class'=>'control-width-4'
    ));
    $col1 .= data_entry_helper::autocomplete(array(
      'label'=>lang::get('Trust records in this location'),
      'fieldname'=>'user_trust:location_id',
      'table'=>'location',
      'valueField'=>'id',
      'captionField'=>'name',
      'blankText'=>'<'.lang::get('any').'>',
      'extraParams'=>$auth['read'] + array('location_type_id'=>variable_get('indicia_profile_location_type_id', '')),
      'class'=>'control-width-4'
    ));
    $col2 = '<p>'.lang::get('Review this recorder\'s experience in the tabs below').'</p>';
    $col2 .= '<div id="summary-tabs">';
    $col2 .= data_entry_helper::tab_header(array(
      'tabs' => array(
        '#tab-surveys'=>lang::get('Surveys'),
        '#tab-taxon-groups'=>lang::get('Taxon groups'),
        '#tab-locations'=>lang::get('Locations'),
      )
    ));
    data_entry_helper::enable_tabs(array(
      'divId'=>'summary-tabs'
    ));
    $col2 .= '<div id="tab-surveys">';
    $col2 .= report_helper::report_grid(array(
      'id'=>'surveys-summary',
      'readAuth'=>$auth['read'],
      'dataSource' => 'library/surveys/filterable_surveys_verification_breakdown',
      'ajax'=>TRUE,
      'autoloadAjax'=>FALSE,
      'extraParams'=>array('my_records'=>1)
    ));
    $col2 .= '</div>';
    $col2 .= '<div id="tab-taxon-groups">';
    $col2 .= report_helper::report_grid(array(
      'id'=>'taxon-groups-summary',
      'readAuth'=>$auth['read'],
      'dataSource' => 'library/taxon_groups/filterable_taxon_groups_verification_breakdown',
      'ajax'=>TRUE,
      'autoloadAjax'=>FALSE,
      'extraParams'=>array('my_records'=>1)
    ));
    $col2 .= '</div>';
    $col2 .= '<div id="tab-locations">';
    $col2 .= report_helper::report_grid(array(
      'id'=>'locations-summary',
      'readAuth'=>$auth['read'],
      'dataSource' => 'library/locations/filterable_locations_verification_breakdown',
      'ajax'=>TRUE,
      'autoloadAjax'=>FALSE,
      'extraParams'=>array('my_records'=>1, 'location_type_id'=>variable_get('indicia_profile_location_type_id', ''))
    ));
    $col2 .= '</div>';
    $col2 .= '</div>';
    $r .= str_replace(array('{col-1}', '{col-2}'), array($col1, $col2), $indicia_templates['two-col-50']);
    $r .= '</fieldset>';
    $r .= '<input type="submit" class="indicia-button" id="save-button" value="'.
        (empty(data_entry_helper::$entity_to_load['user_trust_id:id']) ? 
        lang::get('Grant trust') : lang::get('Update trust settings'))
        ."\" />\n";    
    if (!empty($_GET['user_trust_id'])) {
      $r .= '<input type="submit" class="indicia-button" id="delete-button" name="delete-button" value="'.lang::get('Revoke this trust')."\" />\n";
      data_entry_helper::$javascript .= "$('#delete-button').click(function(e) {
        if (!confirm(\"Are you sure you want to revoke this trust?\")) {
          e.preventDefault();
          return false;
        }
      });\n";
    }
    $r .= '</form>';
    data_entry_helper::enable_validation('entry_form');
    return $r;
  }
  
  /**
   * Converts the posted form values for a group into a warehouse submission.
   * @param array $values Form values
   * @param array $args Form configuration arguments
   * @return array Submission data
   */
  public static function get_submission($values, $args) {
    $struct=array(
      'model' => 'user_trust'
    );
    return submission_builder::build_submission($values, $struct);
  }
  
  /** 
   * Retrieve the path to the current page, so the form can submit to itself.
   * @return string 
   */
  private static function getReloadPath () {
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
    if(count($reload['params'])) {
      // decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?'.http_build_query($reload['params']);
    }
    return $reloadPath;
  }
  
  /**
   * Fetch an existing user trust's information from the database when editing.
   * @param integer $id User trust ID
   * @param array $auth Authorisation tokens
   */
  private static function loadExistingUserTrust($id, $auth) {
    $trust = data_entry_helper::get_population_data(array(
      'table'=>'user_trust',
      'extraParams'=>$auth['read']+array('view'=>'detail', 'id'=>$_GET['user_trust_id']),
      'nocache'=>true
    ));
      
    data_entry_helper::$entity_to_load = array(
      'user_trust:id' => $trust[0]['id'],
      'user_trust:user_id' => $trust[0]['user_id'],
      'user_trust:user_id:person_name' => $trust[0]['person'],
      'user_trust:survey_id' => $trust[0]['survey_id'],
      'user_trust:survey_id:title' => $trust[0]['survey'],
      'user_trust:taxon_group_id' => $trust[0]['taxon_group_id'],
      'user_trust:taxon_group_id:title' => $trust[0]['taxon_group'],
      'user_trust:location_id' => $trust[0]['location_id'],
      'user_trust:location_id:name' => $trust[0]['location'],
      
    );
  }

}
