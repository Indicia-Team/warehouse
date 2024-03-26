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
$id = html::initial_value($values, 'rest_api_client:id');
?>
<p>This page allows you to specify the details of a REST API client.</p>
<form id="entry_form" action="<?php echo url::site() . 'rest_api_client/save'; ?>" method="post">
  <?php echo $metadata; ?>
  <fieldset>
    <input type="hidden" name="rest_api_client:id" value="<?php echo $id ?>" />
    <legend>REST API client details</legend>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'rest_api_client:title',
      'default' => html::initial_value($values, 'rest_api_client:title'),
      'validation' => ['required'],
    ]);

    echo data_entry_helper::textarea([
      'label' => 'Description',
      'fieldname' => 'rest_api_client:description',
      'default' => html::initial_value($values, 'rest_api_client:description'),
    ]);

    ?>
    <div class="row">
      <div class="col-md-6">
        <?php

        echo data_entry_helper::select([
          'label' => 'Website',
          'fieldname' => 'rest_api_client:website_id',
          'default' => html::initial_value($values, 'rest_api_client:website_id'),
          'lookupValues' => $other_data['websites'],
          'helpText' => 'The website the REST API client is associated with.',
          'validation' => ['required'],
        ]);

        echo data_entry_helper::text_input([
          'label' => 'Username',
          'fieldname' => 'rest_api_client:username',
          'default' => html::initial_value($values, 'rest_api_client:username'),
          'validation' => ['required'],
        ]);

        $config = kohana::config('rest');
        if (array_key_exists('directClient', $config['authentication_methods'])) {
          echo data_entry_helper::password_input([
            'label' => 'Secret',
            'fieldname' => 'rest_api_client:secret',
          ]);

          $secret2Config = [
            'label' => 'Retype secret',
            'fieldname' => 'secret2',
          ];
          if (empty(html::initial_value($values, 'rest_api_client:secret'))) {
            $secret2Config['helpText'] = <<<TXT
      Enter a secret if you wish to enable authentication using the username and secret combination for
      this client's connections. Please ensure that you keep a copy of the secret you enter into the
      fields above as it cannot be retrieved.
      TXT;
          }
          else {
            $secret2Config['helpText'] = <<<TXT
      Leave the above Secret fields blank to keep the existing secret. If you choose to enter a new
      secret, then please ensure that you keep a copy of the secret you enter into the fields above as it
      cannot be retrieved.
      TXT;
          }
          echo data_entry_helper::password_input($secret2Config);
        }
        else {
          echo '<p class="alert alert-info">The directClient authentication method is currently disabled in the modules/rest_api/config/rest.php configuration file. Enable it then set a secret here to allow directClient authentication.</p>';
          echo data_entry_helper::text_input([
            'label' => 'Secret',
            'fieldname' => 'foo',
            'attributes' => ['disabled' => 'disabled'],
          ]);
        }

        ?>
      </div>
    </div>
    <?php
    if (array_key_exists('jwtClient', $config['authentication_methods'])) {
      echo data_entry_helper::textarea([
        'label' => 'Public key',
        'fieldname' => 'rest_api_client:public_key',
        'default' => html::initial_value($values, 'rest_api_client:public_key'),
        'helpText' => "Enter a public key if you wish to enable authentication using JWT for this client's connections. Paste the public key from a private/public key pair here.",
      ]);
    }
    else {
      echo '<p class="alert alert-info">The jwtClient authentication method is currently disabled in the modules/rest_api/config/rest.php configuration file. Enable it then set a public key here to allow jwtClient authentication.</p>';
      echo data_entry_helper::textarea([
        'label' => 'Public key',
        'fieldname' => 'foo',
        'disabled' => 'disabled',
      ]);
    }

    ?>
  </fieldset>
  <?php
  echo html::form_buttons(html::initial_value($values, 'rest_api_client:id') != NULL);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
