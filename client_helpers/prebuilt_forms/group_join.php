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
 * A page allowing a user to join a group. Takes a group_id parameter. If the group is public, then joining is immediate, else the
 * user is added to the pending queue. Example use would be to link to this page using the actions column of a report listing 
 * available recording groups.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_group_join {
   
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_group_join_definition() {
    return array(
      'title'=>'Join a group',
      'category' => 'Recording groups',
      'description'=>'A page for joining or requesting membership of a group.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array(array(
      'name'=>'groups_page_path',
      'caption'=>'Path to main groups page',
      'description'=>'Path to the Drupal page which my groups are listed on.',
      'type'=>'text_input'
    ), array(
      'name'=>'group_home_path',
      'caption'=>'Path to the group home page',
      'description'=>'Path to the Drupal page which hosts group home pages.',
      'type'=>'text_input',
      'required'=>false
    ));
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
    if (!$user_id=hostsite_get_user_field('indicia_user_id'))
      return self::abort('Please ensure that you\'ve filled in your surname on your user profile before joining a group.', $args);
    if (empty($_GET['group_id']))
      return self::abort('This form must be called with a group_id in the URL parameters.', $args);
    $r = '';
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $group = data_entry_helper::get_population_data(array(
      'table'=>'group',
      'extraParams' => $auth['read']+array('id'=>$_GET['group_id']),
      'nocache'=>true
    ));
    if (count($group)!==1)
      return self::abort('The group you\'ve requested membership of does not exist.', $args);
    iform_load_helpers(array('submission_builder'));
    $group = $group[0];
    // Check for an existing group user record
    $existing = data_entry_helper::get_population_data(array(
      'table'=>'groups_user',
      'extraParams' => $auth['read']+array('group_id'=>$_GET['group_id'], 'user_id'=>$user_id),
      'nocache'=>true
    ));
    if (count($existing)) {
      if ($existing[0]['pending']==='true') {
        // if a previous request was made and unapproved when the group was request only, but the group is now public, we can approve their existing
        // groups_user record.
        if ($group['joining_method']==='P') {
          $data = array('groups_user:id' => $existing[0]['id'], 'groups_user:pending' => 'f');
          $wrap = submission_builder::wrap($data, 'groups_user');
          $r = data_entry_helper::forward_post_to('groups_user', $wrap, $auth['write_tokens']);
          return self::success($auth, $group, $args);
        } else
          return self::abort("You've already got a membership request for $group[title] pending approval.", $args);
      } else {
        return self::abort("You're already a member of $group[title].", $args);
      }
    } else {
      $data = array('groups_user:group_id' => $group['id'], 'groups_user:user_id' => $user_id);
      // request only, so make the groups_user record pending approval
      if ($group['joining_method']==='R') 
        $data['groups_user:pending'] = 't';
      $wrap = submission_builder::wrap($data, 'groups_user');
      $r = data_entry_helper::forward_post_to('groups_user', $wrap, $auth['write_tokens']);
      if (!isset($r['success'])) 
        return self::abort('An error occurred whilst trying to update your group membership.');
      elseif ($group['joining_method']==='R') 
        return self::abort("Your request to join $group[title] is now awaiting approval.", $args);
      else
        return self::success($auth, $group, $args);
    }
    return $r;
  }
  
  private static function abort($msg, $args) {
    hostsite_show_message($msg);
    // if there is a main page for groups, and this page was deliberately called (i.e. not just a cron indexing scan) then 
    // we can go back.
    if (!empty($_GET['group_id']) && !empty($args['groups_page_path']))
      hostsite_goto_page($args['groups_page_path']);  
  }
  
  private static function success($auth, $group, $args) {
    hostsite_set_page_title("Welcome to $group[title]!");
    $pageData = data_entry_helper::get_population_data(array(
      'table'=>'group_page',
      'extraParams' => $auth['read'] + array('group_id' => $group['id'], 'query' => json_encode(array('in'=>array('administrator'=>array('', 'f')))))
    ));
    $r = '<p>'.lang::get("You've successfully joined $group[title]. You can").':</p>';
    $r .= '<ul>';
    if (!empty($args['group_home_path']))
      $r .= '<li><a href="'.hostsite_get_url($args['group_home_path'], array('group_id'=>$group['id'])).'">'.lang::get("Visit the $group[title] home page").'<a></li>';
    foreach ($pageData as $page) 
      $r .= '<li><a href="'.hostsite_get_url($page['path'], array('group_id'=>$group['id'])).'">'.lang::get($page['caption']).'<a></li>';
    $r .= '<li><a href="'.hostsite_get_url($args['groups_page_path']).'">'.lang::get("Return to your recording groups list").'<a></li>';
    $r .= '</ul>';
    return $r;
  }
}
