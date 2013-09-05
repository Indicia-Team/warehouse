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
 * @package  Client
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link   http://code.google.com/p/indicia/
 */

/**
 * Link in other required php files.
 */
require_once('lang.php');
require_once('helper_config.php');
require_once('data_entry_helper.php');
require_once('secure_msg.php');

/**
 * Provides a helper to:-
 * <ol>
 * <li>provide composite HTML widgets for use on sites</li>
 * <li>make requests to core for access control and user management.</li>
 * </ol>
 *
 * @package  Client
 */

class user_helper extends helper_base {

  /**
   * Helper function to output the HTML for a login form widget.
   * This is a composite control which presents a configurable collection of input and
   * display controls to support a login interface on a web page.
   * The control is wrapped with a <form> element but you need to specify where the login
   * credentials should be sent for processing.
   *
   * All the elements in the control are wrapped within a <form> with an id of indicia-login-control
   * so if you don't wish to specify classes on all the sub elements you can style them using
   * CSS selectors such as
   * #indicia-login-control input[type="checkbox"] or
   * #indicia-login-control a:link
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>action_url</b><br/>
   * Required. String defining the URL which the login data will be sent to for processing.</li>
   * <li><b>control_method</b><br/>
   * Optional. String defining the http method (either post or get) to be used to send the data for processing. 
   * 'post' is strongly recommended as it avoids showing the password in the browser address box. Default is 'post'.</li>
   * <li><b>control_id</b><br/>
   * Optional. String defining the id for the enclosing <form>. Default is 'indicia-login-control'.</li>
   * <li><b>show_fieldset</b><br/>
   * Optional. Boolean defining if the login fields will be contained in a fieldset. Default is false.</li>
   * <li><b>fieldset_class</b><br/>
   * Optional. Only relevant if show_fieldset is true. CSS class names to use on the fieldset element.
   * Default is to omit class attribute.</li>
   * <li><b>legend_label</b><br/>
   * Optional. Only relevant if show_fieldset is true. The text to show on the fieldset legend.
   * Default is 'Login details'. Todo: make this default language aware?</li>
   * <li><b>login_text</b><br/>
   * Optional. Explainatory text to be shown before the login fields. If 'show_fieldset' is true,
   * the text will be inside the fieldset. Default is no text.</li>
   * <li><b>name_label</b><br/>
   * Optional. Text for the username field label. Defaults to 'Username'.
   * Todo: make this default language aware?</li>
   * <li><b>name_field</b><br/>
   * Optional. Name for the username HTML input field. Defaults to 'username'.</li>
   * <li><b>name_class</b><br/>
   * Optional. CSS class names to use on the username HTML text input element.
   * Default is an empty class attribute.</li>
   * <li><b>password_label</b><br/>
   * Optional. Text for the password field label. Defaults to 'Password'.
   * Todo: make this default language aware?</li>
   * <li><b>password_field</b><br/>
   * Optional. Name for the password HTML input field. Defaults to 'password'.</li>
   * <li><b>password_class</b><br/>
   * Optional. CSS class names to use on the password HTML password input element.
   * Default is an empty class attribute.</li>
   * <li><b>show_rememberme</b><br/>
   * Optional. Boolean defining if the 'remember me' checkbox will be displayed. Default is false.</li>
   * <li><b>rememberme_label</b><br/>
   * Optional. Text for the 'remember me' field label. Defaults to 'Remember me'.
   * Todo: make this default language aware?</li>
   * <li><b>rememberme_field</b><br/>
   * Optional. Name for the 'remember me' HTML checkbox field. Defaults to 'remember_me'.</li>
   * <li><b>rememberme_class</b><br/>
   * Optional. CSS class names to use on the 'remember me' HTML checkbox element.
   * Default is an empty class attribute.</li>
   * <li><b>register_uri</b><br/>a
   * Optional. URI for the optional page for user self-registration.
   * If supplied, a registration link will be shown.
   * Default is not to display a registration link.</li>
   * <li><b>register_label</b><br/>
   * Optional. Text to display on the optional link to a page for user self-registration.
   * Ignored unless the register_uri is supplied.
   * Defaults to 'Register to use this site.'.
   * Todo: make this default language aware?</li>
   * <li><b>forgotten_uri</b><br/>a
   * Optional. URI for the optional page for forgotten password recovery.
   * If supplied, a forgotten password link will be shown.
   * Default is not to display a forgotten password link.</li>
   * <li><b>forgotten_label</b><br/>
   * Optional. Text to display on the optional link to a page for forgotten password recovery.
   * Ignored unless the forgotten_uri is supplied.
   * Defaults to 'Request forgotten password.'.
   * Todo: make this default language aware?</li>
   * <li><b>links_class</b><br/>
   * Optional. CSS class names to use on the optional register and forgotten links if required.
   * Default is to omit the class attribute.</li>
   * <li><b>button_label</b><br/>
   * Optional. Text to show on the login submit button. Defaults to 'Login'.
   * Todo: make this default language aware?</li>
   * <li><b>button_field</b><br/>
   * Optional. Name for the submit button field. Defaults to 'login'.</li>
   * <li><b>button_class</b><br/>
   * Optional. CSS class names to use on the login submit button.
   * Default is to omit the class attribute.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the login control.
   */

  public static function login_control($options = array()) {
    $r = '';
    $method = (array_key_exists('control_method', $options)) ? ' method="'.$options['control_method'].'"' : ' method="post"';
    $id = (array_key_exists('control_id', $options)) ? ' id="'.$options['control_id'].'"' : ' id="indicia-login-control"';
    $action = ' action="'.$options['action_url'].'"';
    $r .= '<form'.$id.$method.$action.'>'."\n";
    $fieldset = false;
    if (array_key_exists('show_fieldset', $options) && $options['show_fieldset']) {$fieldset = true;}
    if ($fieldset) {
      $class = (array_key_exists('fieldset_class', $options)) ? ' class="'.$options['fieldset_class'].'"' : '';
      $r .= str_replace('{class}', $class, '<fieldset>{class}')."\n";
      $legend_label = (array_key_exists('legend_label', $options)) ? $options['legend_label'] : 'Login details';
      $r .= '<legend>'.$legend_label.'</legend>'."\n";
    }
    if (array_key_exists('login_text', $options)) {
      $r .= '<p>'.$options['login_text'].'</p>'."\n";
    }
    $label = (array_key_exists('name_label', $options)) ? $options['name_label'] : 'Username';
    $fieldname = (array_key_exists('name_field', $options)) ? $options['name_field'] : 'username';
    $class = (array_key_exists('name_class', $options)) ? $options['name_class'] : '';
    $r .= data_entry_helper::text_input(array(
      'label' => $label,
      'fieldname' => $fieldname,
  	  'class' => $class
    ));
    $label = (array_key_exists('password_label', $options)) ? $options['password_label'] : 'Password';
    $fieldname = (array_key_exists('password_field', $options)) ? $options['password_field'] : 'password';
    $class = (array_key_exists('password_class', $options)) ? $options['password_class'] : '';
    $r .= data_entry_helper::password_input(array(
      'label' => $label,
      'fieldname' => $fieldname,
  	  'class' => $class
    ));
    $rememberme = false;
    if (array_key_exists('show_rememberme', $options) && $options['show_rememberme']) {$rememberme = true;}
    if ($rememberme) {
      $label = (array_key_exists('rememberme_label', $options)) ? $options['rememberme_label'] : 'Remember me';
      $fieldname = (array_key_exists('rememberme_field', $options)) ? $options['rememberme_field'] : 'remember_me';
      $class = (array_key_exists('rememberme_class', $options)) ? $options['rememberme_class'] : '';
      $r .= data_entry_helper::checkbox(array(
        'label' => $label,
        'fieldname' => $fieldname,
    	'class' => $class
      ));
    }
    if (array_key_exists('register_uri', $options)) {
      $label = (array_key_exists('register_label', $options)) ? $options['register_label'] : 'Register to use this site.';
      $class = (array_key_exists('links_class', $options)) ? ' class="'.$options['links_class'].'"' : '';
      $r .= '<a href="'.$options['register_uri'].'"'.$class.'>'.$label.'</a>&nbsp;'."\n";
   	}
   	if (array_key_exists('forgotten_uri', $options)) {
   	  $label = (array_key_exists('forgotten_label', $options)) ? $options['forgotten_label'] : 'Request forgotten password.';
      $class = (array_key_exists('links_class', $options)) ? ' class="'.$options['links_class'].'"' : '';
      $r .= '<a href="'.$options['forgotten_uri'].'"'.$class.'>'.$label.'</a>'."\n";
   	}
   	if ($fieldset) {
   	  $r .= '</fieldset>'."\n";
   	}
   	$label = (array_key_exists('button_label', $options)) ? $options['button_label'] : 'Login';
   	$fieldname = (array_key_exists('button_field', $options)) ? $options['button_field'] : 'login_submit';
   	$class = (array_key_exists('button_class', $options)) ? ' class="'.$options['button_class'].'"' : '';
    $r .= '<input type="submit" name="'.$fieldname.'" id="'.$fieldname.'" value="'.$label.'"'
      .str_replace('{class}', $class, '{class} />')."\n";
     
    $r .= '</form>'."\n";
    return $r;
  }

  /**
   * Helper function to output the HTML for a forgotten password form widget.
   * This is a composite control which presents a configurable collection of input and
   * display controls to support a forgotten password request on a web page.
   * The control is wrapped with a <form> element but you need to specify where the 
   * request should be sent for processing.
   *
   * All the elements in the control are wrapped within a <form> with an id of indicia-forgotten-password-control
   * so if you don't wish to specify classes on all the sub elements you can style them using
   * CSS selectors such as
   * #indicia-forgotten-password-control input[type="text"] 
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>action_url</b><br/>
   * Required. String defining the URL which the login data will be sent to for processing.</li>
   * <li><b>control_method</b><br/>
   * Optional. String defining the http method (either post or get) to be used to send the data for processing. 
   * Default is 'post'.</li>
   * <li><b>control_id</b><br/>
   * Optional. String defining the id for the enclosing <form>. Default is 'indicia-forgotten-password-control'.</li>
   * <li><b>show_fieldset</b><br/>
   * Optional. Boolean defining if the fields will be contained in a fieldset. Default is false.</li>
   * <li><b>fieldset_class</b><br/>
   * Optional. Only relevant if show_fieldset is true. CSS class names to use on the fieldset element.
   * Default is to omit class attribute.</li>
   * <li><b>legend_label</b><br/>
   * Optional. Only relevant if show_fieldset is true. The text to show on the fieldset legend.
   * Default is 'Forgotten password user details'. Todo: make this default language aware?</li>
   * <li><b>login_text</b><br/>
   * Optional. Explainatory text to be shown before the fields. If 'show_fieldset' is true,
   * the text will be inside the fieldset. 
   * Default is <i>'You may enter either your user name or your email address in the field below, 
   * and an email will be sent to you. In this email will be a link to a webpage which will 
   * allow you to enter a new password. This ensures that only a person with access to your 
   * registered email account will be able to change your password.'</i>.  Todo: make this default language aware?</li>
   * <li><b>name_label</b><br/>
   * Optional. Text for the username field label. Defaults to 'User Name or Email Address'.
   * Todo: make this default language aware?</li>
   * <li><b>name_field</b><br/>
   * Optional. Name for the username HTML input field. Defaults to 'userid'.</li>
   * <li><b>name_class</b><br/>
   * Optional. CSS class names to use on the username HTML text input element.
   * Default is an empty class attribute.</li>
   * <li><b>login_uri</b><br/>a
   * Optional. URI to return to the user login page.
   * If supplied, a login link will be shown below the submit button.
   * Default is not to display a login link.</li>
   * <li><b>login_label</b><br/>
   * Optional. Text to display on the optional link back to the login page.
   * Ignored unless the login_uri is supplied.
   * Defaults to 'Return to the login page.'.
   * Todo: make this default language aware?</li>
   * <li><b>links_class</b><br/>
   * Optional. CSS class names to use on the optional 'return to login' link if required.
   * Default is to omit the class attribute.</li>
   * <li><b>button_label</b><br/>
   * Optional. Text to show on the form submit button. Defaults to 'Request Forgotten Password Email'.
   * Todo: make this default language aware?</li>
   * <li><b>button_field</b><br/>
   * Optional. Name for the submit button field. Defaults to 'password_email_submit'.</li>
   * <li><b>button_class</b><br/>
   * Optional. CSS class names to use on the login submit button.
   * Default is to omit the class attribute.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the login control.
   */

  public static function forgotten_password_control($options = array()) {
    $r = '';
    $method = (array_key_exists('control_method', $options)) ? ' method="'.$options['control_method'].'"' : ' method="post"';
    $id = (array_key_exists('control_id', $options)) ? ' id="'.$options['control_id'].'"' : ' id="indicia-forgotten-password-control"';
    $action = ' action="'.$options['action_url'].'"';
    $r .= '<form'.$id.$method.$action.'>'."\n";
    $fieldset = false;
    if (array_key_exists('show_fieldset', $options) && $options['show_fieldset']) {$fieldset = true;}
    if ($fieldset) {
      $class = (array_key_exists('fieldset_class', $options)) ? ' class="'.$options['fieldset_class'].'"' : '';
      $r .= str_replace('{class}', $class, '<fieldset>{class}')."\n";
      $legend_label = (array_key_exists('legend_label', $options)) ? $options['legend_label'] : 'Forgotten password user details';
      $r .= '<legend>'.$legend_label.'</legend>'."\n";
    }
    $text = (array_key_exists('forgotten_password_text', $options)) ? $options['forgotten_password_text'] : 
    'You may enter either your user name or your email address in the field below, '.
    'and an email will be sent to you. In this email will be a link to a webpage which will '.
    'allow you to enter a new password. This ensures that only a person with access to your '.
    'registered email account will be able to change your password.';
    $r .= '<p>'.$text.'</p>'."\n";
    $label = (array_key_exists('name_label', $options)) ? $options['name_label'] : 'User Name or Email Address';
    $fieldname = (array_key_exists('name_field', $options)) ? $options['name_field'] : 'userid';
    $class = (array_key_exists('name_class', $options)) ? $options['name_class'] : '';
    $r .= data_entry_helper::text_input(array(
      'label' => $label,
      'fieldname' => $fieldname,
  	  'class' => $class
    ));
   	if ($fieldset) {
   	  $r .= '</fieldset>'."\n";
   	}
   	$label = (array_key_exists('button_label', $options)) ? $options['button_label'] : 'Request Forgotten Password Email';
   	$fieldname = (array_key_exists('button_field', $options)) ? $options['button_field'] : 'password_email_submit';
   	$class = (array_key_exists('button_class', $options)) ? ' class="'.$options['button_class'].'"' : '';
    $r .= '<input type="submit" name="'.$fieldname.'" id="'.$fieldname.'" value="'.$label.'"'
      .str_replace('{class}', $class, '{class} />')."\n";
   	if (array_key_exists('login_uri', $options)) {
   	  $label = (array_key_exists('login_label', $options)) ? $options['login_label'] : 'Return to the login page.';
      $class = (array_key_exists('links_class', $options)) ? ' class="'.$options['links_class'].'"' : '';
      $r .= '<br /><a href="'.$options['login_uri'].'"'.$class.'>'.$label.'</a>'."\n";
   	}
     
    $r .= '</form>'."\n";
    return $r;
  }
  
  /**
   * Sends a request to the indicia core module to ask if the login credentials
   * are valid for this website.
   *
   * @param string $username Required.
   * The username value entered by the authenticating user.
   * @param string $password Required.
   * The password value entered by the authenticating user.
   * @param array $readAuth Required.
   * Array containing service authentication data obtained from get_read_auth().
   * @param string $website_password Required.
   * The client website password value to be supplied by the site administrator.
   * @param array $options Optional.
   * Options array with the following possibilities:<ul>
   * <li><b>namecase</b><br/>
   * Optional. Boolean defining if the username value should be treated as case sensitive when looking
   * the user up on indicia core. Defaults to true.</li>
   * <li><b>nameormail</b><br/>
   * Optional. String defining if the username value represents the user's name or their e-mail address when looking
   * the user up on indicia core. Allowed values are 'name' or 'mail'. Defaults to 'name'.</li>
   * <li><b>getprofile</b><br/>
   * Optional. Boolean for whether to retrieve the profile data for this user if successfully authenticated.
   * If true, the profile will be returned in the 'profile' key on the response array.
   * Defaults to false.</li>
   * </ul>
   *
   * @return array containing:<ul>
   * <li>The 'user_id' key hold the user_id for the authenticated user,
   * or '0' if the login credentials are not valid for this website.</li>
   * <li>The 'profile' as an array containing:-<ul>
   * <li>title</li>
   * <li>first_name</li>
   * <li>surname</li>
   * <li>initials</li>
   * <li>email_address</li>
   * <li>website_url</li>
   * <li>address</li>
   * <li>home_entered_sref</li>
   * <li>home_entered_sref_system</li>
   * <li>interests</li>
   * <li>location_name</li>
   * <li>email_visible</li>
   * <li>view_common_names</li>
   * <li>username</li>
   * <li>default_digest_mode</li>
   * <li>activated</li>
   * <li>banned</li>
   * <li>site_role</li>
   * <li>registration_datetime</li>
   * <li>last_login_datetime</li>
   * <li>preferred_sref_system</li>
   * </ul> 
   * This is only returned if the 'getprofile' option is true in the request options.</li>
   * </ul>
   */

  public static function authenticate_user($username, $password, $readAuth, $website_password, $options=array()) {
    // encrypt and seal the sensitive data
    $secrets = array("username" => $username, "password" => $password, "options" => $options);
    $sealed = secure_msg::seal($secrets, $website_password);
     
    // send authentication request to indicia core
    $url = self::$base_url."index.php/services/site_user/authenticate_user";
    $postargs = array(secure_msg::SEALED => $sealed, "auth_token" => $readAuth['auth_token'],
        "nonce" => $readAuth['nonce']);
    $response = self::http_post($url, $postargs);
    // decrypt response
    $output = secure_msg::unseal_response($response['output'], $website_password);
     
    // return result
    return $output;
  }
    
  /**
   * Sends a request to the indicia core module to send an email to the user 
   * directing them to the password reset process.
   *
   * @param string $userid Required.
   * The username or email value entered by the web site user.
   * @param array $readAuth Required.
   * Array containing service authentication data obtained from get_read_auth().
   * @param array $options Optional.
   * Options array with the following possibilities:<ul>
   * <li><b>namecase</b><br/>
   * Optional. Boolean defining if the username value should be treated as case sensitive when looking
   * the user up on indicia core. Defaults to true.</li>
   * </ul>
   *
   * @return array containing:<ul>
   * <li>The 'user_id' key hold the user_id for the authenticated user,
   * or '0' if the login credentials are not valid for this website.</li>
   * <li>The 'site_role' as a string 'User', 'Editor' or 'Admin'. 
   * This is only returned if the 'getrole' option is true in the request options.</li>
   * </ul>
   */
  public static function request_password_reset($userid, $readAuth, $options=array()) {

    // send reset password request to indicia core
    $url = self::$base_url."index.php/services/site_user/request_password_reset";
    $postargs = 'userid='.$userid.'&options='.json_encode($options).
    '&auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'];
    $response = self::http_post($url, $postargs);
     
    // return result
    $result = json_decode($response['output'], TRUE);
    return $result;
  }
    
  /**
   * Sends a request to the indicia core module to get profile data for 
   * supplied user on requesting website.
   *
   * @param integer $user_id Required.
   * The numeric user_id of web site user.
   * @param array $readAuth Required.
   * Array containing service authentication data obtained from get_read_auth().
   *
   * @return array containing:<ul>
   * <li>title</li>
   * <li>first_name</li>
   * <li>surname</li>
   * <li>initials</li>
   * <li>email_address</li>
   * <li>website_url</li>
   * <li>address</li>
   * <li>home_entered_sref</li>
   * <li>home_entered_sref_system</li>
   * <li>interests</li>
   * <li>location_name</li>
   * <li>email_visible</li>
   * <li>view_common_names</li>
   * <li>username</li>
   * <li>default_digest_mode</li>
   * <li>activated</li>
   * <li>banned</li>
   * <li>site_role</li>
   * <li>registration_datetime</li>
   * <li>last_login_datetime</li>
   * <li>preferred_sref_system</li>
   * </ul>
   */

  public static function get_user_profile($user_id, $readAuth) {

    // send get profile request to indicia core
    $url = self::$base_url."index.php/services/site_user/get_user_profile/".$user_id;
    $postargs = 'auth_token='.$readAuth['auth_token'].'&nonce='.$readAuth['nonce'];
    $response = self::http_post($url, $postargs);
     
    // return result
    $result = json_decode($response['output'], TRUE);
    return $result;
  }
  
}

