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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm loctools and IForm Proxy.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_sample_occurrence.php');

class iform_mnhnl_dynamic_1 extends iform_dynamic_sample_occurrence {

  public static function get_perms($nid, $args) {
    $perms = array();
    if(isset($args['permission_name']) && $args['permission_name']!='') $perms[] = $args['permission_name'];
    if(isset($args['edit_permission']) && $args['edit_permission']!='') $perms[] = $args['edit_permission'];
    if(isset($args['ro_permission'])   && $args['ro_permission']!='')   $perms[] = $args['ro_permission'];
    return $perms;
  }

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_mnhnl_dynamic_1_definition() {
    return array(
      'title'=>'MNHNL Dynamic 1 - dynamically generated data entry form',
      'category' => 'MNHNL forms',
      'helpLink'=>'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description'=>'Derived from the Dynamic Sample Occurrence Form with custom headers and footers.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {    
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'headerAndFooter',
          'caption'=>'Use Header and Footer',
          'description'=>'Include MNHNL header and footer html.',
          'type'=>'boolean',
          'group' => 'User Interface',
          'default' => false,
          'required' => false
        ),
      )
    );
    return $retVal;
 }
  
  protected static function get_form_html($args, $auth, $attributes) {
    if($args['includeLocTools'] && function_exists('iform_loctools_listlocations')){
  		$squares = iform_loctools_listlocations(self::$node);
  		if($squares != "all" && count($squares)==0)
  			return lang::get('Error: You do not have any squares allocated to you. Please contact your manager.');
  	}
    $r = call_user_func(array(self::$called_class, 'getHeaderHTML'), $args);
    $r .= parent::get_form_html($args, $auth, $attributes);
    $r .= call_user_func(array(self::$called_class, 'getTrailerHTML'), $args);
    return $r;
  }
  
  protected static function getGrid($args, $node, $auth) {
    $r = call_user_func(array(self::$called_class, 'getHeaderHTML'), $args);
    $r .= parent::getGrid($args, $node, $auth);
    $r .= call_user_func(array(self::$called_class, 'getTrailerHTML'), $args);
    return $r;  
  }
  
  protected static function getHeaderHTML($args) {
    $base = base_path();
    if(substr($base, -1)!='/') $base.='/';
    return (isset($args['headerAndFooter']) && $args['headerAndFooter'] ?
      '<div id="iform-header">
        <div id="iform-logo-left"><a href="http://www.environnement.public.lu" target="_blank"><img border="0" class="government-logo" alt="'.lang::get('Gouvernement').'" src="'.$base.'sites/all/files/gouv.png"></a></div>
        <div id="iform-logo-right"><a href="http://www.crpgl.lu" target="_blank"><img border="0" class="gabriel-lippmann-logo" alt="'.lang::get('Gabriel Lippmann').'" src="'.$base.drupal_get_path('module', 'iform').'/client_helpers/prebuilt_forms/images/mnhnl-gabriel-lippmann-logo.jpg"></a></div>
        </div>' : '');
  }

  protected static function getTrailerHTML($args) {
    return (isset($args['headerAndFooter']) && $args['headerAndFooter'] ?
      '<p id="iform-trailer">'.lang::get('LANG_Trailer_Text').'</p>' : '');
  }
  
  /*
   * Hide a control if a user is not a member of a particular group.
   * 
   * $options Options array with the following possibilities:<ul>
   * <li><b>controlId</b><br/>
   * The control to hide. ID used as a jQuery selector.</li>
   * <li><b>groupId</b><br/>
   * Group to check the user is a member of.</li>
   */
  protected static function get_control_hideControlForNonGroupMembers($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $currentUserId=hostsite_get_user_field('indicia_user_id');
    if (empty($options['controlId'])) {
      drupal_set_message('The option to hide a control based on group has been specified, but no option to indicate which control has been provided.');
      return false;
    }
    if (empty($options['groupId'])) {
      drupal_set_message('The option to hide a control based on group has been specified, but no group id has been provided.');
      return false;
    }
    $reportOptions = array(
      'dataSource'=>'library/groups/group_members',
      'readAuth'=>$auth['read'],
      'mode'=>'report',
      'extraParams' => array('group_id'=>$options['groupId'])
    );
    $usersInGroup = report_helper::get_report_data($reportOptions);
    //Check all members in the group, if the current user is a member, then there is no need to hide the control.
    $userFoundInGroup=false;
    foreach ($usersInGroup as $userInGroup) {
      //User role must be Member so that we don't show the control for administrators
      if ($userInGroup['id']===$currentUserId && $userInGroup['role']==='Member')
        $userFoundInGroup=true;
    }
    if ($userFoundInGroup!==true)
      data_entry_helper::$javascript .= "$('#".$options['controlId']."').parent().hide();\n";
  } 
}