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
 * List of methods that can be used for building forms that include details of the logged in CMS user in Drupal.
 * @package Client
 * @subpackage PrebuiltForms.
 */

/**
 * Returns the suggested block of form parameters required to set up the saving of the user's CMS details into the
 * correct attributes.
 */
function iform_user_get_user_parameters() {
  return array(
    array (
        'name'=>'uid_attr_id',
        'caption'=>'User ID Attribute ID',
        'description'=>'Indicia ID for the sample attribute that stores the CMS User ID.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
    ),
    array(
        'name'=>'username_attr_id',
        'caption'=>'Username Attribute ID',
        'description'=>'Indicia ID for the sample attribute that stores the user\'s username.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
    ),
    array(
        'name'=>'email_attr_id',
        'caption'=>'Email Attribute ID',
        'description'=>'Indicia ID for the sample attribute that stores the user\'s email.',
        'type'=>'smpAttr',
        'group'=>'Sample Attributes'
    )
  );
}

/**
 * Return a block of hidden inputs that embed the user's information (user id, username and email)
 * into the record.
 * @param array $args Parameters passed to the form.
 */
function iform_user_get_hidden_inputs($args) {
  global $user;
  $uid = $user->uid;
  $email = $user->mail;
  $username = $user->name;
  $uid_attr_id = $args['uid_attr_id'];
  $email_attr_id = $args['email_attr_id'];
  $username_attr_id = $args['username_attr_id'];
  $r = "<input type=\"hidden\" name=\"smpAttr:$uid_attr_id\" value=\"$uid\" />\n";
  $r .= "<input type=\"hidden\" name=\"smpAttr:$email_attr_id\" value=\"$email\" />\n";
  $r .= "<input type=\"hidden\" name=\"smpAttr:$username_attr_id\" value=\"$username\" />\n";
  return $r;
}

/**
 * Method to read a parameter from the arguments of a form that contains a list of key=value pairs on separate lines. 
 * Eavh value is checked for references to the user's data (either {user_id}, {username}, {email} or {profile_*})
 * and if found these substitutions are replaced.
 * @param string $listData Form argument data, with each key value pair on a separate line.
 * @return array Associative array.
 */
function get_options_array_with_user_data($listData) {
  global $user;
  $r = array();
  $replace=array('{user_id}', '{username}', '{email}');
  $replaceWith=array($user->uid, $user->name, $user->mail);
  $profileLoaded = false;
  if ($listData != ''){
    $params = helper_base::explode_lines($listData);
    foreach ($params as $param) {
      if (!empty($param)) {
        $tokens = explode('=', $param);
        if (count($tokens)==2) {
          // perform any replacements on the initial values and copy to the output array
          if (preg_match('/^\{(?P<field>profile_(.)+)\}$/', $tokens[1], $matches)) {
            $profileField=$matches['field'];
            // got a request for a user profile field, so copy it's value across into the report parameters
            if (!$profileLoaded) {
              profile_load_profile($user);
              $profileLoaded = true;
            }
            $r[$tokens[0]]=$user->$profileField;
          } else {
            // this handles the user id and username replacements
            $r[$tokens[0]]=trim(str_replace($replace, $replaceWith, $tokens[1]));
          }
        } else {
          throw new Exception('Some of the preset or default parameters defined for this page are not of the form param=value.');
        }
      }
    }
  }
  return $r;
}

?>