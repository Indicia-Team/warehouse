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
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * A page for receiving invitation responses from invited users.
 */
class iform_group_receive_invite_response {
  
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_group_receive_invite_response_definition() {
    return array(
      'title'=>'Receive responses from invites',
      'category' => 'Recording groups',
      'description'=>'A page that is hit when the user clicks on a link to accept an email invite to a group. Use the Drupal Blocks '.
          'system to ensure that a login block is present on this page for non-logged in users.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
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
      'type'=>'text_input'
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
    if (empty($_GET['token'])) {
      return self::fail_message("You've arrived at a page intended for accepting invitations to a recording ".
          "group but without the correct information allowing you to join a group", $args);
    }
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $invite = data_entry_helper::get_population_data(array(
      'table' => 'group_invitation',
      'extraParams' => $auth['read'] + array('token'=>$_GET['token']),
      'nocache'=>true
    ));
    if (empty($invite))
      return self::fail_message("You've followed a link to accept an invite to a recording group but unfortunately ".
          "the invite is no longer valid.", $args);
    $invite = $invite[0];
    if ($_POST && !empty($_POST['accept'])) 
      return self::accept($args, $invite, $auth);
    elseif ($_POST && !empty($_POST['reject'])) 
      return self::reject($args, $invite, $auth);
    elseif ($_POST && !empty($_POST['maybe'])) 
      return self::maybe($args, $invite, $auth);
    global $user;
    if ($user->uid) 
      return self::logged_in_page($invite, $auth);
    else 
      return self::logged_out_page($invite, $auth);
  }
  
  /**
   * Display a simple failure message and return to groups page button.
   * @param string $msg Message string
   * @param array $args Form args
   * @return string HTML to output
   */
  private static function fail_message($msg, $args) {
    $r = '<p>' . lang::get($msg) . '</p>';  
    if (hostsite_get_user_field('id'))
      $r .= '<a class="button" href="' . url($args['groups_page_path']) . '">' . lang::get('Return to Groups page') . '</a>';
    return $r;
  }
  
  private static function logged_in_page($invite, $auth) {
    global $user;
    $reloadPath = self::getReloadPath();
    $r = '<p>'.lang::get('You are logged in to {1} as {2} and have been invited to join the recording group {3}.', 
        variable_get('site_name', ''), $user->name, $invite['group_title']) . '</p>';
    $r .= '<form id="entry_form" action="'.$reloadPath.'" method="POST">';
    $r .= '<input type="hidden" name="token" value="'.$_GET['token'].'"/>';
    $r .= '<input type="submit" id="btn-accept" name="accept" value="'.lang::get('Accept invitation').'"/>';
    $r .= '<input type="submit" id="btn-reject" name="reject" value="'.lang::get('Reject invitation').'"/>';
    $r .= '<input type="submit" id="btn-maybe" name="maybe" value="'.lang::get('Maybe later').'"/>';
    hostsite_set_page_title(lang::get('Invitation to join {1}', $invite['group_title']));
    return $r;
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
   * A page displayed on following an invite link when logged out. Prompts login. If a login block is on the 
   * page, then the user can log in whilst on the page and the user can then immediately accept the invite.
   * @param array $invite Invitation record
   * @param array $auth Authorisation tokens
   * @return string HTML to add to the page.
   */
  private static function logged_out_page($invite, $auth) {
    $r = '<p>'.lang::get('If you would like to join the {1} group called {2} then please log in or register an account for {3} then '.
            'follow the link in your invitation email again once registered.', 
        variable_get('site_name', ''), $invite['group_title'], variable_get('site_name', '')) . '</p>';
    hostsite_set_page_title(lang::get('Invitation to join {1}', $invite['group_title']));
    return $r;
  
  }
  
  /**
   * Performs the action of accepting an invite.
   * @param array $args Form configuration arguments
   * @param array $invite Invitation record
   * @param array $auth Authorisation tokens
   * @return type 
   */
  private static function accept($args, $invite, $auth) {
    // insert a groups_users record
    $values = array(
      'group_id'=>$invite['group_id'],
      'user_id'=>hostsite_get_user_field('indicia_user_id'),
      'administrator'=>'f'
    );
    $auth['write_tokens']['persist_auth']=true;
    $s = submission_builder::build_submission($values, array('model' => 'groups_user'));
    $r = data_entry_helper::forward_post_to('groups_user', $s, $auth['write_tokens']);
    // either a success, or already a member (2004=unique key violation)
    if (!isset($r['success']) && (!isset($r['code']) || $r['code']!==2004)) {
      if (function_exists('watchdog'))
        watchdog('iform', 'An internal error occurred whilst trying to accept an invite: '.print_r($r, true));
      return self::fail_message('An internal error occurred whilst trying to accept the invite', $args);
    } elseif (isset($r['code']) && $r['code']===2004) {
      hostsite_show_message(lang::get('You are already a member of the group!'));
      hostsite_goto_page($args['groups_page_path']);
    } else {
      // delete the invitation
      $values = array(
        'id'=>$invite['id'],
        'deleted'=>'t'
      );
      $s = submission_builder::build_submission($values, array('model' => 'group_invitation'));
      $r = data_entry_helper::forward_post_to('group_invitation', $s, $auth['write_tokens']);
      $group = data_entry_helper::get_population_data(array(
        'table' => 'group',
        'extraParams' => $auth['read'] + array('id'=>$invite['group_id'])
      ));
      if (!isset($r['success'])) {
        if (function_exists('watchdog'))
          watchdog('iform', 'An internal error occurred whilst trying to delete an accepted invite: '.print_r($r, true));
        // probably no point telling the user, as the invite accept worked OK
      }
      hostsite_goto_page($args['group_home_path'], array('group_id'=>$invite['group_id']));
    }
  }
  
  /**
   * Given a reject response, delete the invite, and redirect to the groups home page.
   * @param array $args Form config arguments
   * @param array $invite Invitation record
   */
  private static function reject($args, $invite, $auth) {
    $values = array(
      'id'=>$invite['id'],
      'deleted'=>'t'
    );
    $s = submission_builder::build_submission($values, array('model' => 'group_invitation'));
    $r = data_entry_helper::forward_post_to('group_invitation', $s, $auth['write_tokens']);
    hostsite_show_message(lang::get("OK, thanks anyway. We've removed your invitation to join this group."));
    hostsite_goto_page($args['groups_page_path']);
  }
  
  /**
   * Given a maybe response, leave the invite alone, and redirect to the groups home page.
   * @param array $args Form config arguments
   * @param array $invite Invitation record
   * @param array $auth Authorisation tokens
   */
  private static function maybe($args, $invite, $auth) {
    hostsite_show_message(lang::get('Just follow the link in your invitation email if and when you are ready to join.'));
    hostsite_goto_page($args['groups_page_path']); 
  }
}
