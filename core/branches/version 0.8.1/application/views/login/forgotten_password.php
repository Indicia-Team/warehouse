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
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

?>This page is used when you have forgotten your password. You may enter either your Username or your email address in the field below, and an email will be sent to you. In this email will be a link to a webpage which will allow you to enter a new password.
This ensures that only the person at the registered email account for a user will be able to change the password. <br /><br />
<?php if ( ! empty($error_message) )
{
    echo html::error_message($error_message);
}
?>
<form class="cmxform"  name = "login" action="<?php echo url::site(); ?>forgotten_password" method="post">
<fieldset>
<legend>User ID</legend>
<ol>
<li>
  <label for="UserID">User Name or Email Address</label>
  <input type = "text" name = "UserID" id = "UserID" value="" class="narrow" >
</li>
</ol>
</fieldset>
  <input type = "submit" value = "Request Forgotten Password Email" >
</form>
<br />If you have now remembered your password and would like to log on as normal, <a href="<?php echo url::site(); ?>login">click here to return to the log on page</a>.
