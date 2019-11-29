<?php

/**
 * @file
 * View template for user edit form.
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
$id = html::initial_value($values, 'user:id');
?>
<p>This page allows you to specify a users details.</p>
<form id="user-edit" action="<?php echo url::site() . 'user/save'; ?>" method="post">
  <fieldset>
    <legend>User's Details<?php echo $metadata; ?></legend>
    <input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
    <input type="hidden" name="person_id" id="person_id" value="<?php echo html::specialchars($model->person_id); ?>" />
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Username',
      'fieldname' => 'user:username',
      'default' => html::initial_value($values, 'user:username'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Interests',
      'fieldname' => 'user:interests',
      'default' => html::initial_value($values, 'user:interests'),
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Location name',
      'fieldname' => 'user:location_name',
      'default' => html::initial_value($values, 'user:location_name'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Email visible',
      'fieldname' => 'user:email_visible',
      'default' => html::initial_value($values, 'user:email_visible'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'View common names',
      'fieldname' => 'user:view_common_names',
      'default' => html::initial_value($values, 'user:view_common_names'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'This user allows records to be shared for reporting',
      'fieldname' => 'user:allow_share_for_reporting',
      'default' => html::initial_value($values, 'user:allow_share_for_reporting'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'This user allows records to be shared for peer review',
      'fieldname' => 'user:allow_share_for_peer_review',
      'default' => html::initial_value($values, 'user:allow_share_for_peer_review'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'This user allows records to be shared for verification',
      'fieldname' => 'user:allow_share_for_verification',
      'default' => html::initial_value($values, 'user:allow_share_for_verification'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'This user allows records to be shared for data flow',
      'fieldname' => 'user:allow_share_for_data_flow',
      'default' => html::initial_value($values, 'user:allow_share_for_data_flow'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'This user allows records to be shared for moderation',
      'fieldname' => 'user:allow_share_for_moderation',
      'default' => html::initial_value($values, 'user:allow_share_for_moderation'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'This user allows records to be shared for editing',
      'fieldname' => 'user:allow_share_for_editing',
      'default' => html::initial_value($values, 'user:allow_share_for_editing'),
    ]);
    if ($this->auth->logged_in('CoreAdmin')) {
      $roles = ORM::factory('core_role')->orderby('title', 'asc')->find_all();
      $lookupValues = [];
      foreach ($roles as $role) {
        $lookupValues[$role->id] = $role->title;
      }
      echo data_entry_helper::select([
        'label' => 'Role within Warehouse',
        'fieldname' => 'user:core_role_id',
        'default' => html::initial_value($values, 'user:core_role_id'),
        'lookupValues' => $lookupValues,
        'blankText' => '<none>',
      ]);
    }

    if (isset($password_field) and $password_field != '') {
      echo $password_field;
      echo html::error_message($model->getError('user:password'));
    } ?>
  </fieldset>
  <fieldset>
    <legend>Website roles</legend>
      <?php
      foreach ($model->users_websites as $website) {
        $otherOptionList = [];
        $site_roles = ORM::factory('site_role')->orderby('title', 'asc')->find_all();
        foreach ($site_roles as $siteRole) {
          $selected = $siteRole->id == $website['value'] ? ' selected="selected"' : '';
          $otherOptionList[] = "<option value=\"$siteRole->id\" $selected>$siteRole->title</option>";
        }
        $otherOptions = implode("\n        ", $otherOptionList);
        echo <<<HTML
<div class="form-group">
  <label for="$website[name]" class="col-sm-3 control-label">$website[title]:</label>
  <div class="col-sm-9">
      <select class="form-control" name="$website[name]" id="$website[name]">
        <option>None</option>
        $otherOptions
      </select>
    </div>
</div>

HTML;
      }
      ?>
  </fieldset>
  <?php
  echo html::form_buttons($id != NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('user-edit');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
