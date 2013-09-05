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

?>
<form class="cmxform"  name = "new_password" action="<?php echo url::site(); ?>new_password/save" method="post">
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($user_model->id); ?>" />
<input type="hidden" name="email_key" id="email_key" value="<?php echo html::specialchars($email_key); ?>" />
<fieldset>
<legend>Set Password</legend>
<ol>
<?php if ( ! empty($message) )
{
    echo "<li>".$message."</li>";
}
?>
<li>
  <label for="username">Username</label>
  <input tabindex="1" type = "text" name = "username" id = "username" value="<?php echo $user_model->username; ?>" disabled="disabled"  class="narrow" >
  <?php echo html::error_message($user_model->getError('username')); ?>
</li>
<li>
  <label for="email_address">Email</label>
  <input tabindex="2" type = "text" name = "email_address" id = "email_address" value="<?php echo $person_model->email_address; ?>" class="narrow" >
  <?php echo html::error_message($person_model->getError('email_address')); ?>
</li>
<li>
  <label for="password">Password</label>
  <input tabindex="3" type = "password" name = "password" id = "password" value="<?php echo $password; ?>" class="narrow" >
  <?php echo html::error_message($user_model->getError('password')); ?>
</li>
<li>
  <label for="password2">Repeat Password</label>
  <input tabindex="4" type = "password" name = "password2" id = "password2" value="<?php echo $password2; ?>" class="narrow" >
</li>
<?php if ( is_numeric($user_model->core_role_id) ) { ?>
<li>
  <label for="remember_me" >Remember me</label>
  <input tabindex="5" type="checkbox" id="remember_me" name="remember_me" class="default" />
</li>
<?php } ?>
</ol>
</fieldset>
  <input tabindex="6" type = "submit" value = "Submit New Password" />
</form>
