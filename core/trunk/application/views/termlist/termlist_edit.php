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

$id = html::initial_value($values, 'termlist:id');
$parent_id = html::initial_value($values, 'termlist:parent_id');

if ($parent_id != null) : ?>
<h1>Subset of:
<a href="<?php echo url::site(); ?>termlist/edit/<?php echo $parent_id ;?>" >
<?php echo ORM::factory("termlist",$parent_id)->title ?>
</a>
</h1>
<?php endif; ?>
<div id="details">
<form class="cmxform" action="<?php echo url::site().'termlist/save'; ?>" method="post">
<?php echo $metadata ?>
<fieldset>
<legend>List Details</legend>
<input type="hidden" name="termlist:id" value="<?php echo $id; ?>" />
<input type="hidden" name="termlist:parent_id" value="<?php echo $parent_id; ?>" />
<ol>
<li>
<label for="title">Title</label>
<input id="title" name="termlist:title" value="<?php echo html::initial_value($values, 'termlist:title'); ?>"/>
<?php echo html::error_message($model->getError('termlist:title')); ?>
</li>
<li>
<label for="description">Description</label>
<textarea rows=7 id="description" name="termlist:description"><?php echo html::initial_value($values, 'termlist:description'); ?></textarea>
<?php echo html::error_message($model->getError('termlist:description')); ?>
</li>
<li>
<label for="website">Owned by</label>
<select id="website_id" name="termlist:website_id" 
<?php if ($parent_id != null && array_key_exists('parent_website_id', $values) && $values['parent_website_id'] !== null) {
  echo "disabled='disabled'";
  $website_id=$values['parent_website_id']; 
} else {
  $website_id = html::initial_value($values, 'termlist:website_id');
} ?> >
  <option value=''>&lt;Warehouse&gt;</option>
<?php
  if (!is_null($this->auth_filter))
    $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->orderby('title','asc')->find_all();
  else
    $websites = ORM::factory('website')->orderby('title','asc')->find_all();
  foreach ($websites as $website) {
    echo '	<option value="'.$website->id.'" ';
    if ($website->id==$website_id)
      echo 'selected="selected" ';
    echo '>'.$website->title.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('termlist:website_id')); ?>
</li>
</ol>
</fieldset>
<?php
echo html::form_buttons(html::initial_value($values, 'termlist:id')!=null && html::initial_value($values, 'termlist:id')!='', false, false);  
echo html::error_message($model->getError('deleted')); 
?>
</form>
</div>