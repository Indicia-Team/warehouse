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
 * A form that allows a group admin to send email invitations to a group.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * A page for send invites to a user group.
 */
class iform_group_send_invites {
  
  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_group_send_invites_definition() {
    return array(
      'title'=>'Send invites to a group',
      'category' => 'Recording groups',
      'description'=>'A form for emailing out invites to recording groups.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {   
    return array(array(
      'name'=>'accept_invite_path',
      'caption'=>'Accept Invite Path',
      'description'=>'Path to the Drupal page which invitation acceptances should be routed to.',
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
    $reloadPath = self::getReloadPath();   
    data_entry_helper::$website_id=$args['website_id'];
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $group = self::loadGroup($auth);
    if (!empty($_POST['invitee_emails'])) {
      self::sendInvites($args, $auth);
    }
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    $r .= data_entry_helper::textarea(array(
      'label' => lang::get('Send invites to'),
      'helpText' => 'Enter email addresses for the people you want to invite, one per line',
      'fieldname'=>'invitee_emails',
      'validation'=>array('required')
    ));
    $r .= data_entry_helper::textarea(array(
      'label' => lang::get('Invitation message'),
      'helpText' => 'What message would you like to send to your invitees?',
      'fieldname'=>'invite_message',
      'validation'=>array('required'),
      'default' => 'Would you like to join the ' . $group['title'] . '?'
    ));
    $r .= '<button type="submit" class="indicia-button" id="save-button">'.lang::get('Send Invites')."</button>\n";
    $r .= '<button type="button" class="indicia-button" id="not-now-button" ' .
        'onclick="window.location.href=\'' . url($args['redirect_on_success']) . '\'">'.lang::get('Not Now')."</button>\n";
    $r .= '</form>';
    data_entry_helper::enable_validation('entry_form');
    return $r;
  }
  
  /**
   * Loads the group record from the database. Also checks that the user is an admin of the group.
   * @param array $auth Authorisation tokens
   * @return array Group record loaded from the db
   */
  private static function loadGroup($auth) {
    if (empty($_GET['group_id']) && !empty($_GET['id']))
      $_GET['group_id']=$_GET['id'];
    if (empty($_GET['group_id']))
      throw new exception('Form must be called with an group_id parameter for the group.');
    // check the logged in user is admin of this group
    $response = data_entry_helper::get_population_data(array(
      'table'=>'groups_user',
      'extraParams' => $auth['read'] + array('group_id' => $_GET['group_id'], 'user_id'=>hostsite_get_user_field('indicia_user_id')),
      'nocache'=>true
    ));
    if (count($response)===0 || $response[0]['administrator']==='f')
      throw new exception('Attempt to send invites for a group you are not administrator of.');
    $response = data_entry_helper::get_population_data(array(
      'table'=>'group',
      'extraParams' => $auth['read'] + array('id' => $_GET['group_id']),
      'nocache'=>true
    ));
    return $response[0];
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
   * Performs the sending of invitation emails.
   * @param array $args Form configuration arguments
   * @param array $auth Authorisation tokens
   * @todo Integrate with notifications for logged in users.
   */
  private static function sendInvites($args, $auth) {
    $emails = helper_base::explode_lines($_POST['invitee_emails']);
    // first task is to populate the groups_invitations table
    $base = uniqid();
    $success = true;
    $failedRecipients = array();
    foreach ($emails as $idx => $email) {
      $values = array(
        'group_invitation:group_id'=>$_GET['group_id'],
        'group_invitation:email' => $email,
        'group_invitation:token' => $base.$idx,
        'website_id' => $args['website_id']
      );
      $s = submission_builder::build_submission($values, array('model' => 'group_invitation'));
      $r = data_entry_helper::forward_post_to('group_invitation', $s, $auth['write_tokens']);
      $pathParam = (function_exists('variable_get') && variable_get('clean_url', 0)=='0') ? 'q' : '';
      $rootFolder = data_entry_helper::getRootFolder() . (empty($pathParam) ? '' : "?$pathParam=");
      $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $acceptUrl = $protocol . $_SERVER['HTTP_HOST'] . $rootFolder . $args['accept_invite_path'] . (empty($pathParam) ? '?' : '&') . 'token=' . $base . $idx;
      $body = $_POST['invite_message'] . "<br/><br/>" .
          '<a href="' . $acceptUrl . '">' . lang::get('Accept this invitation') . '</a>';
      $message = array(
          'id' => 'iform_group_invite', 
          'to' => implode(',', $emails), 
          'subject' => 'Invitation to join a recording group', 
          'body' => $body, 
          'headers' => array(
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=iso-8859-1',
          )
      );
      $mimeheaders = array();
      foreach ($message['headers'] as $name => $value) {
        $mimeheaders[] = $name . ': ' . mime_header_encode($value);
      }
      $thismailsuccess = mail(
        $message['to'], mime_header_encode($message['subject']), 
        // Note: e-mail uses CRLF for line-endings, but PHP's API requires LF.
        // They will appear correctly in the actual e-mail that is sent.
        str_replace("\r", '', $message['body']), 
        // For headers, PHP's API suggests that we use CRLF normally,
        // but some MTAs incorrecly replace LF with CRLF. See #234403.
        join("\n", $mimeheaders)
      );
      if (!$thismailsuccess)
        $failedRecipients[$message['to']]=$acceptUrl;
      $success = $success && $thismailsuccess;
    }
    if ($success)
      drupal_set_message(lang::get('Invitation emails sent'));
    else {
      drupal_set_message(lang::get('The emails could not be sent due to a server configuration issue. Please contact the site admin. ' .
          'The list below gives the emails and the links you need to send to each invitee which they need to click on in order to join the group.'), 'warning');
      $list=array();
      foreach($failedRecipients as $email => $link) {
        $list[] = lang::get("Send link {1} to {2}.", $link, $email);
      }
      drupal_set_message(implode('<br/>', $list), 'warning');
    }
    drupal_goto($args['redirect_on_success']);
  }

}
