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
<p>This page allows you to specify the details of a survey in which samples and records can be organised.</p>
<form class="cmxform" action="<?php echo url::site().'survey/save'; ?>" method="post">
<?php echo $metadata ?>
<input type="hidden" name="survey:id" value="<?php echo html::initial_value($values, 'survey:id'); ?>" />
<fieldset>
<legend>Survey details</legend>
<ol>
<li>
<label for="title">Title</label>
<input id="title" name="survey:title" value="<?php echo html::initial_value($values, 'survey:title'); ?>" />
<?php echo html::error_message($model->getError('survey:title')); ?>
</li>
<li>
<label for="description">Description</label>
<textarea rows="7" id="description" name="survey:description"><?php echo html::initial_value($values, 'survey:description'); ?></textarea>
<?php echo html::error_message($model->getError('survey:description')); ?>
</li>
<li>
<label for="website_id">Website</label>
<select id="website_id" name="survey:website_id">
  <option value="">&lt;Please select&gt;</option>
<?php
  if (!is_null($this->auth_filter))
    $websites = ORM::factory('website')->where(array('deleted'=>'f'))->in('id',$this->auth_filter['values'])->orderby('title','asc')->find_all();
  else
    $websites = ORM::factory('website')->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
  $selected = html::initial_value($values, 'survey:website_id'); 
  foreach ($websites as $website) {
    echo '	<option value="'.$website->id.'" ';
    if ($website->id==$selected)
      echo 'selected="selected" ';
    echo '>'.$website->title.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('survey:website_id')); ?>
</li>
</ol>
</fieldset>
<?php 
echo html::form_buttons(html::initial_value($values, 'survey:id')!=null);
?>
</form>
