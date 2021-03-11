<?php

/**
 * @file
 * View template for the new password form.
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

warehouse::loadHelpers(['data_entry_helper']);
?>
<form id="entry_form" action="<?php echo url::site(); ?>new_password/save" method="post">
  <input type="hidden" name="id" id="id" value="<?php echo html::specialchars($user_model->id); ?>" />
  <input type="hidden" name="email_key" id="email_key" value="<?php echo html::specialchars($email_key); ?>" />
  <fieldset>
    <legend>Set password</legend>
    <?php if (!empty($message)) : ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Username',
      'fieldname' => 'username',
      'default' => $user_model->username,
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Email',
      'fieldname' => 'email_address',
      'default' => $person_model->email_address,
      'validation' => ['email', 'required'],
    ]);
    echo data_entry_helper::password_input([
      'label' => 'Password',
      'fieldname' => 'password',
      'default' => $password,
      'validation' => ['required'],
    ]);
    echo data_entry_helper::password_input([
      'label' => 'Repeat password',
      'fieldname' => 'password2',
      'default' => $password2,
      'validation' => ['required'],
    ]);
    ?>
  </fieldset>
  <input type="submit" value="Submit new password" class="btn btn-primary" />
  <?php
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
