<?php

/**
 * @file
 * View template for the website edit form.
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
<p>This page allows you to specify the details of a website that will use the services provided by this Indicia
Warehouse instance.</p>
<form id="website-edit" action="<?php echo url::site() . 'website/save'; ?>" method="post">
  <?php echo $metadata; ?>
  <fieldset>
    <input type="hidden" name="website:id" value="<?php echo html::initial_value($values, 'website:id'); ?>" />
    <legend>Website details</legend>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'website:title',
      'default' => html::initial_value($values, 'website:title'),
      'validation' => ['required'],
    ]);

    echo data_entry_helper::text_input([
      'label' => 'URL',
      'fieldname' => 'website:url',
      'default' => html::initial_value($values, 'website:url'),
      'validation' => ['required'],
    ]);

    echo data_entry_helper::textarea([
      'label' => 'Description',
      'fieldname' => 'website:description',
      'default' => html::initial_value($values, 'website:description'),
    ]);

    echo data_entry_helper::password_input([
      'label' => 'Password',
      'fieldname' => 'website:password',
      'default' => html::initial_value($values, 'website:password'),
    ]);

    echo data_entry_helper::password_input([
      'label' => 'Retype password',
      'fieldname' => 'password2',
      'default' => html::initial_value($values, 'password2'),
      'helpText' => 'Password for linking Drupal Iform module implementations ' .
        'to this website registration. Also used for nonce + auth_token based ' .
        'API access, see <a href="https://indicia-docs.readthedocs.io/en/latest/developing/web-services/authentication-overview.html">documentation</a> for more info.',
    ]);

    echo data_entry_helper::textarea([
      'label' => 'Public key',
      'fieldname' => 'website:public_key',
      'default' => html::initial_value($values, 'website:public_key'),
      'helpText' => 'Paste the public key from a private/public key pair here ' .
        'to enable JWT API authentication.',
    ]);

    echo data_entry_helper::checkbox([
      'label' => 'Enable auto-verification checks',
      'fieldname' => 'website:verification_checks_enabled',
      'default' => html::initial_value($values, 'website:verification_checks_enabled'),
    ]);

    ?>
  </fieldset>
  <?php
  echo html::form_buttons(html::initial_value($values, 'website:id') != NULL);
  data_entry_helper::enable_validation('website-edit');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
