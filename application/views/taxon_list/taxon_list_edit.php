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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */
?>
<?php
$id = html::initial_value($values, 'taxon_list:id');
$parent_id = html::initial_value($values, 'taxon_list:parent_id');
$disabled = $this->get_read_only($values) ? 'disabled="disabled" ' : '';
if ($parent_id != null) : ?>
<h1>Subset of:
<a href="<?php echo url::site() ?>taxon_list/edit/<?php echo $parent_id ?>" >
<?php echo ORM::factory("taxon_list", $parent_id)->title ?>
</a>
</h1>
<?php endif; ?>
<div id="details">
<?php if ($this->get_read_only($values)) : ?>
<div class="page-notice ui-state-highlight ui-corner-all">You do not have the required privileges to edit this record.</div>
<?php endif; ?>
<form class="cmxform" action="<?php echo url::site().'taxon_list/save' ?>" method="post" >
<?php echo $metadata ?>
<fieldset>
  <legend>List Details</legend>
  <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
  <input type="hidden" name="parent_id" id="parent_id" value="<?php echo $parent_id; ?>" />
  <div class="form-group">
    <label for="title">Title</label>
    <div class="input-group">
      <input id="title" name="taxon_list:title" class="form-control" <?php echo $disabled; ?>
        value="<?php echo html::initial_value($values, 'taxon_list:title'); ?>"/>
      <div class="input-group-addon"><span class="deh-required">*</span></div>
    </div>
    <?php echo html::error_message($model->getError('taxon_list:title')); ?>
  </div>
  <div class="form-group">
    <label for="description">Description</label>
    <textarea rows="7" <?php echo $disabled; ?> class="form-control" id="description" name="taxon_list:description">
      <?php echo html::initial_value($values, 'taxon_list:description'); ?></textarea>
    <?php echo html::error_message($model->getError('taxon_list:description')); ?>
  </div>
  <div class="form-group">
    <label for="website">Owned by</label>
    <select id="website_id" name="taxon_list:website_id" class="form-control" <?php echo $disabled; ?>>
    <?php
    // if we have a new child list, default the website to the parent's website
    if (empty($id) && !empty($values['parent_website_id']))
      $website_id=$values['parent_website_id'];
    else
      $website_id = html::initial_value($values, 'taxon_list:website_id');

    if ($this->auth->logged_in('CoreAdmin') || (!$website_id && $id !== null)) {
      // Core admin can select Warehouse as owner. Other users can only have this option in the list if the
      // list is already assigned to the warehouse in which case the list is read only.
      echo '<option value="">&lt;Warehouse&gt;</option>';
    }
    foreach ($other_data['websites'] as $website) {
      echo '  <option value="'.$website->id.'" ';
      if ($website->id==$website_id)
        echo 'selected="selected" ';
      echo '>'.$website->title.'</option>';
    }
    ?>
    </select>
    <?php echo html::error_message($model->getError('taxon_list:website_id')); ?>
  </div>
</fieldset>
<?php
  echo html::form_buttons(html::initial_value($values, 'taxon_list:id')!=null, $this->get_read_only($values));
?>
</form>
</div>
