<?php

/**
 * @file
 * View template for the occurrence association edit form.
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
?>
<p>Use this form to configure the default email notification settings that will
  be given to users signing up to this website. </p>
<p class="alert alert-info">Important - if using this feature, ensure that the terms and conditions
  that users sign up to for this website clarify exactly which emails the users
  will receive.</p>

<div class="alert alert-warning" id="notification-settings-save-notice" style="display: none">
  Changes have been made. Click Save to store them in the database.
  <button type="button" class="btn btn-primary">Save</button>
</div>

<div id="notification-settings-form">
<?php

$frequencyOptions = [
  'IH' => 'immediate',
  'D' => 'daily',
  'W' => 'weekly',
];

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:V',
  'label' => 'Verification notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['V']) ? NULL : $data['V'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:Q',
  'label' => 'Query notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['Q']) ? NULL : $data['Q'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:RD',
  'label' => 'Redetermination notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['RD']) ? NULL : $data['RD'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:C',
  'label' => 'Other comment notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['C']) ? NULL : $data['C'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:A',
  'label' => 'Automated record check notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['A']) ? NULL : $data['A'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:T',
  'label' => 'Trigger notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['T']) ? NULL : $data['T'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:S',
  'label' => 'Species alert notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['S']) ? NULL : $data['S'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:VT',
  'label' => 'Verifier task notification emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['VT']) ? NULL : $data['VT'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:M',
  'label' => 'Milestone achievement emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['M']) ? NULL : $data['M'],
]);

echo data_entry_helper::select([
  'fieldname' => 'notification_source_type:PT',
  'label' => 'Pending records to moderate task emails',
  'lookupValues' => $frequencyOptions,
  'blankText' => '- no emails -',
  'default' => empty($data['PT']) ? NULL : $data['PT'],
]);

?>
</div>

<script>
jQuery('document').ready(function($) {
  $('#notification-settings-form select').change(() => {
    $('#notification-settings-save-notice').slideDown();
  });
  $('#notification-settings-save-notice button').click(() => {
    const s = {
      website_id: <?php echo $website_id; ?>,
      V: $('#notification_source_type\\:V').val(),
      Q: $('#notification_source_type\\:Q').val(),
      RD: $('#notification_source_type\\:RD').val(),
      C: $('#notification_source_type\\:C').val(),
      A: $('#notification_source_type\\:A').val(),
      T: $('#notification_source_type\\:T').val(),
      S: $('#notification_source_type\\:S').val(),
      VT: $('#notification_source_type\\:VT').val(),
      M: $('#notification_source_type\\:M').val(),
      PT: $('#notification_source_type\\:PT').val(),
    }
    $.ajax({
      type: 'POST',
      url: '<?php echo url::site() ?>website_email_notification_setting/save',
      data: s,
      dataType: 'json',
    }).done(() => {
      $('#notification-settings-save-notice').slideUp();
    }).fail(() => {
      alert('An error occurred when saving the settings.');
    });
  });
});
</script>
