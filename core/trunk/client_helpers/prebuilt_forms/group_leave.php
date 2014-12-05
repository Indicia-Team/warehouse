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
 * A page allowing a user to leave a group. Takes a group_id parameter. Example use would be to 
 * link to this page using the actions column of a report listing a user's recording groups.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_group_leave {
   
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_group_leave_definition() {
    return array(
      'title'=>'Leave a group',
      'category' => 'Recording groups',
      'description'=>'A page for leaving the membership of a group.'
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
      return self::abort('Please ensure that you\'ve filled in your surname on your user profile before leaving a group.', $args);
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
    if (count($existing)!==1)
      return self::abort('You are not a member of this group.', $args);
    if (!empty($_POST['response']) && $_POST['response']===lang::get('Cancel')) {
      drupal_goto($args['groups_page_path']);
    }
    elseif (!empty($_POST['response']) && $_POST['response']===lang::get('Confirm')) {     
      $data = array('groups_user:id' => $existing[0]['id'], 'groups_user:group_id' => $group['id'], 'groups_user:user_id' => $user_id, 'deleted' => 't');
      $wrap = submission_builder::wrap($data, 'groups_user');
      $response = data_entry_helper::forward_post_to('groups_user', $wrap, $auth['write_tokens']);
      if (isset($response['success'])) {
        hostsite_show_message("You are no longer participating in $group[title]!");
        drupal_goto($args['groups_page_path']);
      } 
      else {
        return self::abort('An error occurred whilst trying to update your group membership.');
      }
    } else {
      // First access of the form. Let's get confirmation
      $reload = data_entry_helper::get_reload_link_parts();
      $reloadpath = $reload['path'] . '?' . data_entry_helper::array_to_query_string($reload['params']);
      $r = '<form action="' . $reloadpath .'" method="POST"><fieldset>';
      $r .= '<legend>' . lang::get('Confirmation') . '</legend>';
      $r .= '<input type="hidden" name="leave" value="1" />';
      $r .= '<p>' . lang::get('Are you sure you want to stop participating in {1}?', $group['title']) . '</p>';
      $r .= '<input type="submit" value="' . lang::get('Confirm') . '" name="response" />';
      $r .= '<input type="submit" value="' . lang::get('Cancel') . '" name="response" />';
      $r .= '</fieldset></form>';
    }
    return $r;
  }
  
  private static function abort($msg, $args) {
    hostsite_show_message(lang::get($msg));
    if (!empty($_GET['group_id']) && !empty($args['groups_page_path']))
      hostsite_goto_page($args['groups_page_path']);  
  }
}
