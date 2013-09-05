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

require_once('includes/report_filters.php');

/**
 * A page for editing or creating a recording group.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_group_edit {
  
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_group_edit_definition() {
    return array(
      'title'=>'Create or edit a group',
      'category' => 'Recording groups',
      'description'=>'A form for creating or editing groups of recorders.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    return array();
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
    iform_load_helpers(array('report_helper', 'map_helper'));
    $reloadPath = self::getReloadPath();   
    data_entry_helper::$website_id=$args['website_id'];
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    if (isset($_GET['group_id'])) 
      self::loadExistingGroup($_GET['group_id'], $auth);
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    $r .= $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= data_entry_helper::hidden_text(array('fieldname'=>'group:id'));
    $r .= data_entry_helper::text_input(array(
      'label' => lang::get('Group name'),
      'fieldname'=>'group:title',
      'validation'=>array('required'),
      'class'=>'control-width-5'
    ));
    $r .= data_entry_helper::radio_group(array(
      'label' => lang::get('Group membership'),
      'fieldname' => 'group:joining_method',
      'lookupValues' => array('P'=>lang::get('Anyone can join'), 
          'R'=>lang::get('Anyone can request membership but group administrator must approve'), 
          'I'=>lang::get('Group membership by invite only')),
      'helpText' => lang::get('How can users join this group?'),
      'sep' => '<br/>',
      'validation'=>array('required')
    ));
    $r .= data_entry_helper::textarea(array(
      'label' => lang::get('Group description'),
      'fieldname' => 'group:description',
      'helpText' => lang::get('Tell us about your group.')
    ));
    $r .= '<p>' . lang::get('LANG_Filter_Instruct') . '</p>';
    $r .= '<label>' . lang::get('Group parameters') . ':</label>';
    $r .= report_filter_panel($auth['read'], array(
      'allowLoad'=>false,
      'allowSave' => false,
      'filterTypes' => array('' => 'what,where,when', lang::get('Advanced') => 'source,quality'),
      'embedInExistingForm' => true
    ), $args['website_id'], $hiddenStuff);
    // fields to auto-create a filter record for this group's defined set of records
    $r .= data_entry_helper::hidden_text(array('fieldname'=>'filter:id'));
    $r .= '<input type="hidden" name="filter:title" id="filter-title-val"/>';
    $r .= '<input type="hidden" name="filter:definition" id="filter-def-val"/>';
    $r .= '<input type="hidden" name="filter:sharing" value="R"/>';
    // auto-insert the creator as an admin of the new group
    $r .= '<input type="hidden" name="groups_user:user_id" value="' .hostsite_get_user_field('indicia_user_id'). '"/>';
    $r .= '<input type="hidden" name="groups_user:administrator" value="t"/>';
    $r .= '<input type="submit" class="indicia-button" id="save-button" value="'.
        (empty(data_entry_helper::$entity_to_load['filter:id']) ? lang::get('Create group') : lang::get('Update group settings'))
        ."\" />\n";    
    $r .= '</form>';
    $r .= $hiddenStuff;
    data_entry_helper::enable_validation('entry_form');
    // JavaScript to grab the filter definition and store in the form for posting when the form is submitted
    data_entry_helper::$javascript .= "
$('#entry_form').submit(function() {
  $('#filter-title-val').val('" . lang::get('Filter for user group') . " ' + $('#group\\\\:title').val());
  $('#filter-def-val').val(JSON.stringify(indiciaData.filter.def));
});\n";
    return $r;
  }
  
  /**
   * Converts the posted form values for a group into a warehouse submission.
   * @param array $values Form values
   * @param array $args Form configuration arguments
   * @return array Submission data
   */
  public static function get_submission($values, $args) {
    return submission_builder::build_submission($values, array(
      'model' => 'group',
      'superModels' => array(
        'filter' => array('fk' => 'filter_id')
      ),
      'subModels' => array(
        'groups_user' => array('fk' => 'group_id')
      )
    )); 
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
   * Fetch an existing group's information from the database when editing.
   * @param integer $id Group ID
   * @param array $auth Authorisation tokens
   */
  private static function loadExistingGroup($id, $auth) {
    $group = data_entry_helper::get_population_data(array(
      'table'=>'group',
      'extraParams'=>$auth['read']+array('view'=>'detail', 'id'=>$_GET['group_id']),
      'nocache'=>true
    ));
    data_entry_helper::$entity_to_load = array(
      'group:id' => $group[0]['id'],
      'group:title' => $group[0]['title'],
      'group:joining_method'=>$group[0]['joining_method'],
      'group:description'=>$group[0]['description'],
      'group:filter_id'=>$group[0]['filter_id'],
      'filter:id'=>$group[0]['filter_id']
    );
    data_entry_helper::$javascript .= 
        "indiciaData.filter.def={$group[0][definition]};\n";
  }

}
