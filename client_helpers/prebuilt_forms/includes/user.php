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

?>