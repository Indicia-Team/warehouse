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
<p>This page allows you to specify a users details.</p>
<form class="cmxform" action="<?php echo url::site().'user/save'; ?>" method="post">
<?php echo $metadata; ?>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<input type="hidden" name="person_id" id="person_id" value="<?php echo html::specialchars($model->person_id); ?>" />
<fieldset>
<legend>User's Details</legend>
<ol>
<li>
<label for="username">Username</label>
<input id="username" name="username" value="<?php echo html::specialchars($model->username); ?>" />
<?php echo html::error_message($model->getError('username')); ?>
</li>
<li>
<label for="interests">Interests</label>
<textarea rows="3" id="interests" name="interests"><?php echo html::specialchars($model->interests); ?></textarea>
<?php echo html::error_message($model->getError('interests')); ?>
</li>
<li>
<label for="location_name">Location Name</label>
<textarea rows="2" id="location_name" name="location_name"><?php echo html::specialchars($model->location_name); ?></textarea>
<?php echo html::error_message($model->getError('location_name')); ?>
</li>
<li>
<label class="wide" for="email_visible">Show Email Address</label>
<?php echo form::checkbox('email_visible', TRUE, isset($model->email_visible) AND ($model->email_visible == 't') ) ?>
</li>
<li>
<label class="wide" for="view_common_names">Show Common Names</label>
<?php echo form::checkbox('view_common_names', TRUE, isset($model->view_common_names) AND ($model->view_common_names == 't') ) ?>
</li>
<?php if ($this->auth->logged_in('CoreAdmin')): ?>
<li>
<label class="wide" for="core_role_id">Role within Warehouse</label>
<select class="narrow" id="core_role_id" name="user:core_role_id" >
  <option>None</option>
<?php
  $core_roles = ORM::factory('core_role')->orderby('title','asc')->find_all();
  foreach ($core_roles as $core_role) {
    echo '	<option value="'.$core_role->id.'" ';
    if ($core_role->id==$model->core_role_id)
      echo 'selected="selected" ';
    echo '>'.$core_role->title.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('core_role_id')); ?>
</li>
<?php
endif;
if (isset($password_field) and $password_field != '') { 
  echo $password_field; 
  echo html::error_message($model->getError('user:password'));
} ?>
</ol>
</fieldset>
<fieldset>
<legend>Website Roles</legend>
<ol>
<?php
  foreach ($model->users_websites as $website) {
    echo '<li><label class="wide" for="'.$website['name'].'">'.$website['title'].'</label>';
    echo '  <select class="narrow" id="'.$website['name'].'" name="'.$website['name'].'">';
    echo '	<option>None</option>';
    $site_roles = ORM::factory('site_role')->orderby('title','asc')->find_all();
    foreach ($site_roles as $site_role) {
      echo '	<option value="'.$site_role->id.'" ';
      if ($site_role->id==$website['value'])
        echo 'selected="selected" ';
      echo '>'.$site_role->title.'</option>';
    }
    echo '</select></li>';
  }
?>
</ol>
</fieldset>
<?php echo html::form_buttons($model->id!=null); ?>
</form>
