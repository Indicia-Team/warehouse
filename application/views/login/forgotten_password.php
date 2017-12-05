<?php

/**
 * @file
 * View template for the forgotten password form.
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
 * @link https://github.com/indicia-team/warehouse
 */
?>
<p>Please enter either your username or your email address in the field below and an email will be sent to you
allowing you to reset your password.</p>
<?php
warehouse::loadHelpers(['data_entry_helper']);
if (!empty($error_message)) {
  echo html::error_message($error_message);
}
?>
<form name = "login" action="<?php echo url::site(); ?>forgotten_password" method="post">
  <fieldset>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Username or email address',
      'fieldname' => 'UserID',
    ]);
    ?>
  </fieldset>
  <a class="btn btn-default" href="<?php echo url::site(); ?>login">Back</a>
  <input class="btn btn-primary" type="submit" value="Request forgotten password email">
</form>
