<?php

/**
 * @file
 * Login view when logging in via username.
 *
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
 * @package Core
 * @subpackage Views
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */
?>
<div class="row">
  <div class="col-md-6"><?php echo $introduction; ?></div>
  <div class="col-md-6 panel panel-primary">
    <form name="login" action="<?php echo url::site(); ?>login" method="post">
      <fieldset>
        <legend>Login details</legend>
        <?php
        if (! empty($error_message)) {
          echo html::error_message($error_message);
        }
        ?>
        <div class="form-group">
          <label for="user">Username or email address:</label>
          <input type="text" name="user" id="user" class="form-control" />
        </div>
        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" name="password" id="password" class="form-control" />
        </div>
        <div class="checkbox">
          <label><input type="checkbox" name="remember_me" />Remember me</label>
        </div>
      </fieldset>
      <input type="submit" value="Login" class="btn btn-primary" />
    </form>
    <br /><a href="<?php echo url::site(); ?>forgotten_password">Request an email allowing you to reset your password</a>.
  </div>
</div>