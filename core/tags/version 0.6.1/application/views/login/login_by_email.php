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

echo $introduction;
?>

<form class="cmxform" name = "login" action="<?php echo url::site(); ?>login/login_by_email" method="post">
<fieldset>
<legend>Login details</legend>
<?php if ( ! empty($error_message) )
{
    echo html::error_message($error_message);
}
?>
<ol>
<li>
  <label for="Email">Email</label>
  <input tabindex="1" type = "text" name = "Email" id = "Email" value="" class="narrow" />
</li>
<li>
  <label for="Password">Password</label>
  <input tabindex="2" type = "password" name = "Password" id = "Password" value="" class="narrow" />
</li>
<li>
  <label for="remember_me" >Remember me</label>
  <input tabindex="3" type="checkbox" id="remember_me" name="remember_me" class="default" />
</li>
</ol>
</fieldset>
<input tabindex="4" type = "submit" value = "Login" />
</form>
<?php if ( ! empty($link_to_username) )
{ ?>
  <br />You may <a href="<?php echo url::site(); ?>login">click here to log in using your Username</a>.
<?php } ?>
<br />If you have forgotten your password, <a href="<?php echo url::site(); ?>forgotten_password">click here to request an email allowing you to reset your password</a>.