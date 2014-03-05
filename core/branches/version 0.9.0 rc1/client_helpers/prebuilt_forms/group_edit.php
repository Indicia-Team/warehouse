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
 * A page for editing or creating a group of people, such as a recording group, organisation or project.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_group_edit {
  
  private static $groupType='group';
  
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
    return array(
      array(
        'name'=>'group_type',
        'caption'=>'Group type',
        'description'=>'Type of group this form will be used to create or edit. Leave blank to let the group creator choose.',
        'type'=>'select',
        'table'=>'termlists_term',
        'valueField'=>'id',
        'captionField'=>'term',
        'extraParams'=>array('termlist_external_key'=>'indicia:group_types')
      ), array(
        'name'=>'parent_group_relationship_type',
        'caption'=>'Parent relationship type',
        'description'=>'If you are using this form to create groups which will be the children of other groups, then when you call this '.
            'page pass from_group_id=... in the URL to set the parent group\'s ID, which must of course exist. Set the parent relationship '.
            'type here to define what relationship type to create between the parent and child groups. If this is set, then the from_group_id '.
            'in the URL parameters is required.',
        'type'=>'select',
        'table'=>'termlists_term',
        'valueField'=>'id',
        'captionField'=>'term',
        'extraParams'=>array('termlist_external_key'=>'indicia:group_relationship_types')
      ), array(
        'name'=>'join_methods',
        'caption'=>'Available joining methods',
        'description'=>'Which joining methods are available for created groups? Put one option per line, with the option code ' .
            '(P, R, I, A) followed by an equals sign then the text description given. Option P is a public group which ' .
            'anyone can join, R is a group which anyone can browse to find and request to join but the admin must approve '.
            'new members, I is an invite only group and A is a group where the administrator creates the list of members '.
            'manually. The latter should only be used in cases where it is appropriate for a group membership to be setup '.
            'without explicit member approval. If you allow only one joining method, then the group creator will not need '.
            'to pick one so the options control will be hidden on the edit form.',
        'type'=>'textarea',
        'default'=>"P=Anyone can join without needing approval\nR=Anyone can request membership but administrator must approve\n" .
            "I=Membership by invite only\nA=Administrator will set up the members manually",
        'required'=>true
      ),
      array(
        'name'=>'include_code',
        'caption'=>'Include code field',
        'description'=>'Include the optional field for setting a group code?',
        'type'=>'checkbox',
        'default'=>false,
        'required'=>false
      ),
      array(
        'name'=>'include_dates',
        'caption'=>'Include date fields',
        'description'=>'Include the optional fields for setting the date range the group operates for?',
        'type'=>'checkbox',
        'default'=>false,
        'required'=>false
      ),
      array(
        'name'=>'include_report_filter',
        'caption'=>'Include report filter',
        'description'=>'Include the optional panel for defining a report filter?',
        'type'=>'checkbox',
        'default'=>true,
        'required'=>false
      ),
      array(
        'name'=>'include_private_records',
        'caption'=>'Include private records field',
        'description'=>'Include the optional field for witholding records from release?',
        'type'=>'checkbox',
        'default'=>false,
        'required'=>false
      ),
      array(
        'name'=>'include_administrators',
        'caption'=>'Include admins control',
        'description'=>'Include a control for setting up a list of the admins for this group? If not set, then the group '.
            'creator automatically gets assigned as the administrator.',
        'type'=>'checkbox',
        'default'=>false,
        'required'=>false
      ),
      array(
        'name'=>'include_members',
        'caption'=>'Include members control',
        'description'=>'Include a control for setting up a list of the members for this group? If not set, then the group '.
            'creator automatically gets assigned as the administrator. Do not use this option for group joining methods that '.
            'involve the members requesting or being invited - this is only appropriate when the group admin explicitly controls '.
            'the group membership.',
        'type'=>'checkbox',
        'default'=>false,
        'required'=>false
      ),
      array(
        'name' => 'filter_types',
        'caption'=>'Filter Types',
        'description'=>'JSON describing the filter types that are available if the include report filter option is checked.',
        'type'=>'textarea',
        'default'=>'{"":"what,where,when","Advanced":"source,quality"}',
        'required'=>false
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
    if (!hostsite_get_user_field('indicia_user_id'))
      return 'Please ensure that you\'ve filled in your surname on your user profile before creating or editing groups.';
    iform_load_helpers(array('report_helper', 'map_helper'));
    $args=array_merge(array(
      'include_code'=>false,
      'include_dates'=>false,
      'include_report_filter'=>true,
      'include_private_records'=>false,
      'include_administrators'=>false,
      'include_members'=>false, 
      'filter_types' => '{"":"what,where,when","Advanced":"source,quality"}'
    ), $args);
    $args['filter_types']=json_decode($args['filter_types'], true);
    $reloadPath = self::getReloadPath();   
    data_entry_helper::$website_id=$args['website_id'];
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    if (!empty($_GET['group_id'])) {
      self::loadExistingGroup($_GET['group_id'], $auth, $args);
    } else {
      if (!empty($args['parent_group_relationship_type']) && empty($_GET['from_group_id']))
        return 'This form should be called with a from_group_id parameter to define the parent when creating a new group';
    }
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    $r .= $auth['write'].
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= data_entry_helper::hidden_text(array('fieldname'=>'group:id'));
    if (!empty($args['group_type'])) {
      $r .= '<input type="hidden" name="group:group_type_id" value="'.$args['group_type'].'"/>';
      $response = data_entry_helper::get_population_data(array(
        'table'=>'termlists_term',
        'extraParams'=>$auth['read'] + array('id'=>$args['group_type'])
      ));
      self::$groupType=strtolower($response[0]['term']);
    }
    if (!empty(data_entry_helper::$entity_to_load['group:title']) && function_exists('drupal_set_title'))
      drupal_set_title(lang::get('Edit {1}', data_entry_helper::$entity_to_load['group:title']));
    $r .= data_entry_helper::text_input(array(
      'label' => lang::get('{1} name', ucfirst(self::$groupType)),
      'fieldname'=>'group:title',
      'validation'=>array('required'),
      'class'=>'control-width-5',
      'helpText'=>lang::get('The full title of the {1}', self::$groupType)
    ));
    if ($args['include_code'])
      $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Code'),
        'fieldname'=>'group:code',
        'class'=>'control-width-4',
        'helpText'=>lang::get('A code or abbreviation identifying the {1}', self::$groupType)
      ));
    if (empty($args['group_type'])) {
      $r .= data_entry_helper::select(array(
        'label' => lang::get('Group type'),
        'fieldname' => 'group:group_type_id',
        'required' => true,
        'table'=>'termlists_term',
        'valueField'=>'id',
        'captionField'=>'term',
        'extraParams'=>$auth['read'] + array('termlist_external_key'=>'indicia:group_types'),
        'class'=>'control-width-4'
      ));
    }
    $r .= self::joinMethodsControl($args);
    $r .= data_entry_helper::textarea(array(
      'label' => ucfirst(lang::get('{1} description', self::$groupType)),
      'fieldname' => 'group:description',
      'helpText' => lang::get('Description and notes about the {1}.', self::$groupType)
    ));
    $r .= self::dateControls($args);
    if ($args['include_private_records']) 
      $r .= data_entry_helper::checkbox(array(
        'label' => lang::get('Records are private'),
        'fieldname'=>'group:private_records',
        'helpText'=>lang::get('Tick this box if you want to withold the release of the records from this {1} until a '.
          'later point in time, e.g. when a project is completed.', self::$groupType)
      ));
    $r .= self::memberControls($args, $auth);
    $r .= self::reportFilterBlock($args, $auth, $hiddenPopupDivs);
    // auto-insert the creator as an admin of the new group, unless the admins are manually specified
    if (!$args['include_administrators'] && empty($_GET['group_id']))
      $r .= '<input type="hidden" name="groups_user:admin_user_id[]" value="' .hostsite_get_user_field('indicia_user_id'). '"/>';
    $r .= '<input type="hidden" name="groups_user:administrator" value="t"/>';
    $r .= '<input type="submit" class="indicia-button" id="save-button" value="'.
        (empty(data_entry_helper::$entity_to_load['group:id']) ? 
        lang::get('Create {1}', self::$groupType) : lang::get('Update {1} settings', self::$groupType))
        ."\" />\n";    
    $r .= '</form>';
    $r .= $hiddenPopupDivs;
    
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
   * Returns a control for picking one of the allowed joining methods. If there is only one, 
   * then this is output as a single hidden input.
   * @param array $args Form configuration arguments
   * @return string HTML to output
   */
  private static function joinMethodsControl($args) {
    $r = '';
    $joinMethods=data_entry_helper::explode_lines_key_value_pairs($args['join_methods']);
    if (count($joinMethods)===1) {
      $methods=array_keys($joinMethods);
      $r .= '<input type="hidden" name="group:joining_method" value="'.$methods[0].'"/>';
    } else {
      $r .= data_entry_helper::radio_group(array(
        'label' => ucfirst(lang::get('{1} membership', self::$groupType)),
        'fieldname' => 'group:joining_method',
        'lookupValues' => $joinMethods,
        'helpText' => lang::get('How can users join this group?'),
        'sep' => '<br/>',
        'validation'=>array('required')
      ));
    }
    return $r;
  }
  
  /**
   * Returns controls for defining the date range of a group if this option is enabled. 
   * @param array $args Form configuration arguments
   * @return string HTML to output
   */
  private static function dateControls($args) {
    $r = '';
    if ($args['include_dates']) {
      $r .= data_entry_helper::date_picker(array(
        'label' => ucfirst(lang::get('{1} active from', self::$groupType)),
        'fieldname' => 'group:from_Date',
        'suffixTemplate' => 'nosuffix'
      ));
      $r .= data_entry_helper::date_picker(array(
        'label' => lang::get('to'),
        'fieldname' => 'group:to_Date',
        'labelClass' => 'auto',
        'helpText' => lang::get('Specify the period during which the {1} was active.', self::$groupType)
      ));
    }
    return '';
  }
  
  /**
   * Returns controls for defining the list of group members and administrators if this option is enabled. 
   * @param array $args Form configuration arguments
   * @return string HTML to output
   */
  private static function memberControls($args, $auth) {
    $r = '';
    if ($args['include_administrators']) {
      $r .= data_entry_helper::sub_list(array(
        'fieldname'=>'groups_user:admin_user_id',
        'label' => ucfirst(lang::get('{1} administrators', self::$groupType)),
        'table'=>'user',
        'captionField'=>'person_name',
        'valueField'=>'id',
        'extraParams'=>$auth['read']+array('view'=>'detail'),
        'helpText'=>lang::get('Search for users to assign admin role to by typing a few characters of their surname'),
        'addToTable'=>false
      ));
    }
    if ($args['include_members']) {
      $r .= data_entry_helper::sub_list(array(
        'fieldname'=>'groups_user:user_id',
        'label' => lang::get('Other {1} members', self::$groupType),
        'table'=>'user',
        'captionField'=>'person_name',
        'valueField'=>'id',
        'extraParams'=>$auth['read']+array('view'=>'detail'),
        'helpText'=>lang::get('Search for users to give membership to by typing a few characters of their surname'),
        'addToTable'=>false
      ));
    }
    return $r;
  }
  
  /**
   * Returns controls allowing a records filter to be defined and associated with the group. 
   * @param array $args Form configuration arguments
   * @return string HTML to output
   */
  private static function reportFilterBlock($args, $auth, &$hiddenPopupDivs) {
    $r = '';
    $hiddenPopupDivs='';
    if ($args['include_report_filter']) {
      $r .= '<p>' . lang::get('LANG_Filter_Instruct') . '</p>';
      $r .= '<label>' . lang::get(ucfirst(self::$groupType).' parameters') . ':</label>';
      $r .= report_filter_panel($auth['read'], array(
        'allowLoad'=>false,
        'allowSave' => false,
        'filterTypes' => $args['filter_types'],
        'embedInExistingForm' => true
      ), $args['website_id'], $hiddenPopupDivs);
      // fields to auto-create a filter record for this group's defined set of records
      $r .= data_entry_helper::hidden_text(array('fieldname'=>'filter:id'));
      $r .= '<input type="hidden" name="filter:title" id="filter-title-val"/>';
      $r .= '<input type="hidden" name="filter:definition" id="filter-def-val"/>';
      $r .= '<input type="hidden" name="filter:sharing" value="R"/>';
    }
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
      'model' => 'group'
    );
    if (!empty($values['filter:title']))
      $struct['superModels'] = array(
        'filter' => array('fk' => 'filter_id')
      );
    if (!empty($args['parent_group_relationship_type']) && !empty($_GET['from_group_id'])) {
      $struct['subModels'] = array(
        'group_relation' => array('fk' => 'to_group_id')
      );
      $values['group_relation:from_group_id']=$_GET['from_group_id'];
      $values['group_relation:relationship_type_id']=$args['parent_group_relationship_type'];
    }
    $s = submission_builder::build_submission($values, $struct);
    // need to manually build the submission for the admins sub_list, since we are hijacking what is 
    // intended to be a custom attribute control
    self::extractUserInfoFromFormValues($s, $values, 'admin_user_id', 't');
    self::extractUserInfoFromFormValues($s, $values, 'user_id', 'f');
    return $s;
  }  
  
  private static function extractUserInfoFromFormValues(&$s, $values, $fieldname, $isAdmin) {
    $existingAdmins=preg_grep("/^groups_user\:$fieldname\:[0-9]+$/", array_keys($values));
    if (!empty($values["groups_user:$fieldname"]) || !empty($existingAdmins)) {
      if (!isset($s['subModels']))
        $s['subModels']=array();
      if (!empty($values["groups_user:$fieldname"])) {
        foreach($values["groups_user:$fieldname"] as $userId) {
          $s['subModels'][]=array('fkId' => 'group_id', 
            'model' => submission_builder::wrap(array('user_id'=>$userId, 'administrator'=>$isAdmin), 'groups_user'));
        }
      }
      // for existing, we just need to look for deletions which will have an empty value
      foreach($existingAdmins as $admin) {
        if (empty($values[$admin])) {
          $id=substr($admin, 20);
          $s['subModels'][]=array('fkId' => 'group_id', 
            'model' => submission_builder::wrap(array('id'=>$id, 'deleted'=>'t'), 'groups_user'));
        }
      }
    }
    return $s;
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
  private static function loadExistingGroup($id, $auth, $args) {
    $group = data_entry_helper::get_population_data(array(
      'table'=>'group',
      'extraParams'=>$auth['read']+array('view'=>'detail', 'id'=>$_GET['group_id']),
      'nocache'=>true
    ));
    if ($group[0]['created_by_id']!==hostsite_get_user_field('indicia_user_id')) {
      if (!function_exists('user_access') || !user_access('Iform groups admin')) {
        // user did not create group. So, check they are an admin
        $admins = data_entry_helper::get_population_data(array(
          'table'=>'groups_user',
          'extraParams'=>$auth['read']+array('group_id'=>$_GET['group_id'], 'administrator'=>'t'),
          'nocache'=>true
        ));
        $found=false;
        foreach($admins as $admin) {
          if ($admin['user_id']===hostsite_get_user_field('indicia_user_id')) {
            $found=true;
            break;
          }
        }
        if (!$found)
          throw new exception(lang::get('You are trying to edit a group you don\'t have admin rights to.'));
      }
    }
      
    data_entry_helper::$entity_to_load = array(
      'group:id' => $group[0]['id'],
      'group:title' => $group[0]['title'],
      'group:code' => $group[0]['code'],
      'group:group_type_id' => $group[0]['group_type_id'],
      'group:joining_method'=>$group[0]['joining_method'],
      'group:description'=>$group[0]['description'],
      'group:from_date'=>$group[0]['from_date'],
      'group:to_date'=>$group[0]['to_date'],
      'group:private_records'=>$group[0]['private_records'],
      'group:filter_id'=>$group[0]['filter_id'],
      'filter:id'=>$group[0]['filter_id']
    );
    if ($args['include_report_filter']) {
      $def=$group[0]['filter_definition'] ? $group[0]['filter_definition'] : '{}';
      data_entry_helper::$javascript .= 
          "indiciaData.filter.def=$def;\n";
    }
    if ($args['include_administrators'] || $args['include_members']) {
      $members = data_entry_helper::get_population_data(array(
        'table'=>'groups_user',
        'extraParams'=>$auth['read']+array('view'=>'detail', 'group_id'=>$_GET['group_id']),
        'nocache'=>true
      ));
      $admins = array();
      $others = array();
      foreach($members as $member) {
        if ($member['administrator']==='t')
          $admins[]=array(
              'fieldname'=>'groups_user:user_id:'.$member['id'],
              'caption'=>$member['person_name'],
              'default'=>$member['user_id']
          );
        else
          $others[]=array(
              'fieldname'=>'groups_user:user_id:'.$member['id'],
              'caption'=>$member['person_name'],
              'default'=>$member['user_id']
          );
      }
      data_entry_helper::$entity_to_load['groups_user:admin_user_id']=$admins;
      data_entry_helper::$entity_to_load['groups_user:user_id']=$others;
    }
  }
  
  public static function get_perms($nid, $args) {
    return array('IForm groups admin');
  }

}
