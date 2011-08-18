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
<p>This page allows you to specify a persons details.</p>
<form class="cmxform" action="<?php echo url::site().'person/save'; ?>" method="post">
<?php echo $metadata; ?>
<fieldset>
<?php if (isset($values['return_url'])) echo $values['return_url']; ?>
<input type="hidden" name="person:id" value="<?php echo html::initial_value($values, 'person:id'); ?>" />
<legend>Person's Details</legend>
<ol>
<li>
<label for="title_id">Title</label>
<select id="title_id" name="person:title_id">
  <option>&lt;Please select&gt;</option>
<?php
  $titles = ORM::factory('title')->orderby('id','asc')->find_all();
  $title_id = html::initial_value($values, 'person:title_id');
  foreach ($titles as $title) {
    echo '	<option value="'.$title->id.'" ';
    if ($title->id==$title_id)
      echo 'selected="selected" ';
    echo '>'.$title->title.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('person:title_id')); ?>
</li>
<li>
<label for="first_name">First name</label>
<input id="first_name" name="person:first_name" value="<?php echo html::initial_value($values, 'person:first_name'); ?>" />
<?php echo html::error_message($model->getError('person:first_name')); ?>
</li>
<li>
<label for="surname">Surname</label>
<input id="surname" name="person:surname" value="<?php echo html::initial_value($values, 'person:surname'); ?>" />
<?php echo html::error_message($model->getError('person:surname')); ?>
</li>
<li>
<label for="initials">Initials</label>
<input id="initials" name="person:initials" value="<?php echo html::initial_value($values, 'person:initials'); ?>" />
<?php echo html::error_message($model->getError('person:initials')); ?>
</li>
<li>
<label for="address">Address</label>
<textarea rows="4" id="address" name="person:address"><?php echo html::initial_value($values, 'person:address'); ?></textarea>
<?php echo html::error_message($model->getError('person:address')); ?>
</li>
<li>
<label for="email_address">Email Address</label>
<input id="email_address" name="person:email_address" value="<?php echo html::initial_value($values, 'person:email_address');?>" />
<?php echo html::error_message($model->getError('person:email_address')); ?>
</li>
<li>
<label for="website_url">Personal Website</label>
<input id="website_url" name="person:website_url" value="<?php echo html::initial_value($values, 'person:website_url');?>" />
<?php echo html::error_message($model->getError('person:website_url')); ?>
</li>
<li>
<label for="external_key">External key</label>
<input id="external_key" name="person:external_key" value="<?php echo html::initial_value($values, 'person:external_key'); ?>" />
<?php echo html::error_message($model->getError('person:external_key')); ?>
</li>
</ol>
</fieldset>
<?php 
echo html::form_buttons(html::initial_value($values, 'person:id')!=null)
?>
</form>
