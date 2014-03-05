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
 * Each value is checked for references to the user's data (either {user_id}, {username}, {email} or {profile_*})
 * and if found these substitutions are replaced.
 * @param string $listData Form argument data, with each key value pair on a separate line.
 * @return array Associative array.
 */
function get_options_array_with_user_data($listData) {
  global $user;
  $r = array();
  if ($listData != ''){
    $params = helper_base::explode_lines($listData);
    foreach ($params as $param) {
      if (!empty($param)) {
        $tokens = explode('=', $param, 2);
        if (count($tokens)==2) {
          $tokens[1] = apply_user_replacements($tokens[1]);
        } else {
          throw new Exception('Some of the preset or default parameters defined for this page are not of the form param=value.');
        }
        $r[$tokens[0]]=$tokens[1];
      }
    }
  }
  return $r;
}

/**
 * Takes a piece of configuration text and replaces tokens with the relevant user profile information. The following
 * replacements are applied:
 * {user_id} - the content management system User ID.
 * {username} - the content management system username.
 * {email} - the email address stored for the user in the content management system.
 * {profile_*} - the respective field from the user profile stored in the content management system.
 */
function apply_user_replacements($text) {
  global $user;
  if (!is_string($text))
    return $text;
  $replace=array('{user_id}', '{username}', '{email}');
  $replaceWith=array(
      $user->uid, 
      isset($user->name) ? $user->name : '', 
      isset($user->mail) ? $user->mail : ''
  );
  // Do basic replacements and trim the data
  $text=trim(str_replace($replace, $replaceWith, $text));  
  // Look for any profile field replacments
  if (preg_match_all('/{([^}]*)}/', $text, $matches) && function_exists('hostsite_get_user_field')) {
    $profileLoaded=false;
    foreach($matches[1] as $profileField) {
      // got a request for a user profile field, so copy it's value across into the report parameters
      $fieldName = preg_replace('/^profile_/', '', $profileField);
      $value = hostsite_get_user_field($fieldName);
      if ($value) {
        // unserialise the data if it is serialised, e.g. when using profile_checkboxes to store a list of values.
        $value = @unserialize($value);
        // arrays are returned as a comma separated list
        if (is_array($value))
          $value = implode(',',$value);
        else 
          $value = $value ? $value : hostsite_get_user_field($fieldName);
        // nulls must be passed as empty string params.
        $value = ($value===null ? '' : $value);
      } else
        $value='';
      $text=str_replace('{'.$profileField.'}', $value, $text);  
    }
  }
  // convert booleans to true booleans
  $text = ($text==='false') ? false : (($text==='true') ? true : $text);
    
  return $text;
}

/**
 * Function similar to get_options_array_with_user_data, but accepts input data in a format read from the form structure
 * definition for a block of attributes in a dynamic form and returns data in a format ready for passing to the code which
 * builds the attribute html.
 */
function get_attr_options_array_with_user_data($listData) {
  $r = array();
  $data=get_options_array_with_user_data($listData);
  foreach ($data as $key=>$value) {
    $tokens = explode('|', $key);
    $r[$tokens[0]][$tokens[1]] = $value;
  }
  return $r;
}

?>