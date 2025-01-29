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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

warehouse::loadHelpers(['data_entry_helper']);
$id = html::initial_value($values, 'rest_api_client_connection:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<p>This page allows you to specify the details of a REST API client.</p>
<form id="entry_form" action="<?php echo url::site() . 'rest_api_client_connection/save'; ?>" method="post">
  <?php echo $metadata; ?>
  <fieldset>
    <input type="hidden" name="rest_api_client_connection:id" value="<?php echo $id ?>" />
    <input type="hidden" name="rest_api_client_connection:rest_api_client_id"
           value="<?php echo html::initial_value($values, 'rest_api_client_connection:rest_api_client_id'); ?>" />
    <legend>REST API client details</legend>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'rest_api_client_connection:title',
      'default' => html::initial_value($values, 'rest_api_client_connection:title'),
      'validation' => ['required'],
      'description' => 'Title of the connection, for admin use only.',
    ]);

    echo data_entry_helper::text_input([
      'label' => 'Proj ID',
      'fieldname' => 'rest_api_client_connection:proj_id',
      'default' => html::initial_value($values, 'rest_api_client_connection:proj_id'),
      'validation' => ['required'],
      'description' => 'Identifier of the connection, passed in API requests.',
    ]);

    echo data_entry_helper::textarea([
      'label' => 'Description',
      'fieldname' => 'rest_api_client_connection:description',
      'default' => html::initial_value($values, 'rest_api_client_connection:description'),
    ]);

    ?>
    <div class="row">
      <div class="col-md-6">

      <?php

      echo data_entry_helper::select([
        'label' => 'Sharing mode',
        'fieldname' => 'rest_api_client_connection:sharing',
        'default' => html::initial_value($values, 'rest_api_client_connection:sharing') ?? 'R',
        'helpText' => 'Determines how other websites share their records to this website for reporting.',
        'lookupValues' => [
          'D' => 'Data flow (downloads)',
          'M' => 'Moderation',
          'P' => 'Peer review',
          'R' => 'Reporting',
          'V' => 'Verification',
        ],
      ]);

      echo data_entry_helper::autocomplete([
        'label' => 'Filter',
        'fieldname' => 'rest_api_client_connection:filter_id',
        'default' => html::initial_value($values, 'rest_api_client_connection:filter_id'),
        'defaultCaption' => html::initial_value($values, 'filter:title'),
        'helpText' => 'Select a filter which limits the records available for reporting and Elasticsearch requests using this connection.',
        'table' => 'filter',
        'captionField' => 'title',
        'valueField' => 'id',
        'extraParams' => $readAuth + ['created_by_id' => $_SESSION['auth_user']->id],
        'afterControl' => '<label>Limit to my filters: <input type="checkbox" id="filters-limit-user" checked /></label>',
      ]);

      echo data_entry_helper::select([
        'label' => 'Elasticsearch endpoint',
        'fieldname' => 'rest_api_client_connection:es_endpoint',
        'default' => html::initial_value($values, 'rest_api_client_connection:es_endpoint') ?? 'R',
        'helpText' => "REST API endpoint for Elasticsearch access. Must be configured in the REST API module's rest.php config file.",
        'lookupValues' => array_combine($other_data['esEndpoints'], $other_data['esEndpoints']),
        'blankText' => '-Select to enable Elasticsearch access-',
      ]);

      ?>
      </div>
    </div>

    <?php

    echo data_entry_helper::checkbox([
      'label' => 'Allow reports',
      'fieldname' => 'rest_api_client_connection:allow_reports',
      'default' => html::initial_value($values, 'rest_api_client_connection:allow_reports'),
      'helpText' => 'Tick to enable access to reports via the REST API.',
    ]);

    echo data_entry_helper::textarea([
      'label' => 'Limit to reports',
      'fieldname' => 'rest_api_client_connection:limit_to_reports',
      'default' => html::initial_value($values, 'rest_api_client_connection:limit_to_reports'),
      'helpText' => 'If empty, then all reports are allowed. Otherwise list the path to each allowed report XML file, one per line.',
    ]);

    echo data_entry_helper::checkbox([
      'label' => 'Allow data resources',
      'fieldname' => 'rest_api_client_connection:allow_data_resources',
      'default' => html::initial_value($values, 'rest_api_client_connection:allow_data_resources'),
      'helpText' => 'Tick to enable access to data resources via the REST API.',
    ]);

    echo data_entry_helper::textarea([
      'label' => 'Limit to data resources',
      'fieldname' => 'rest_api_client_connection:limit_to_data_resources',
      'default' => html::initial_value($values, 'rest_api_client_connection:limit_to_data_resources'),
      'helpText' => 'If empty, then all data resource endpoints are allowed. Otherwise list the allowed endpoints (e.g. occurrences, samples or locations) one per line.',
    ]);

    echo data_entry_helper::checkbox([
      'label' => 'Allow confidential records',
      'fieldname' => 'rest_api_client_connection:allow_confidential',
      'default' => html::initial_value($values, 'rest_api_client_connection:allow_confidential'),
      'helpText' => 'Tick to include confidential records in those available to this connection. Will only affect reports if they support standard filters.',
    ]);

    echo data_entry_helper::checkbox([
      'label' => 'Allow sensitive records',
      'fieldname' => 'rest_api_client_connection:allow_sensitive',
      'default' => html::initial_value($values, 'rest_api_client_connection:allow_sensitive'),
      'helpText' => 'Tick to include sensitive records in those available to this connection. Will only affect reports if they support standard filters.',
    ]);

    echo data_entry_helper::checkbox([
      'label' => 'Allow unreleased records',
      'fieldname' => 'rest_api_client_connection:allow_unreleased',
      'default' => html::initial_value($values, 'rest_api_client_connection:allow_unreleased'),
      'helpText' => 'Tick to include unreleased records in those available to this connection. Will only affect reports if they support standard filters.',
    ]);

    echo data_entry_helper::checkbox([
      'label' => 'Full precision sensitive records',
      'fieldname' => 'rest_api_client_connection:full_precision_sensitive_records',
      'default' => html::initial_value($values, 'rest_api_client_connection:full_precision_sensitive_records'),
      'helpText' => 'Tick to show PostgreSQL and Elasticsearch data for sensitive records at full precision. If unticked sensitive records will be blurred. Note that this does not affect access to occurrences and samples resources directly via the data resources.',
    ]);

    ?>
  </fieldset>
  <?php
  echo html::form_buttons(html::initial_value($values, 'rest_api_client_connection:id') != NULL);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
