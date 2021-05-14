<?php

/**
 * @file
 * View template for the taxon list edit form.
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
$id = html::initial_value($values, 'taxon_list:id');
$parent_id = html::initial_value($values, 'taxon_list:parent_id');
$disabled = $this->get_read_only($values) ? 'disabled="disabled" ' : '';
if (!empty($parent_id)) : ?>
  <h1>Subset of:
    <a href="<?php echo url::site() ?>taxon_list/edit/<?php echo $parent_id ?>" >
      <?php echo ORM::factory("taxon_list", $parent_id)->title ?>
    </a>
  </h1>
<?php endif; ?>
<?php if ($this->get_read_only($values)) : ?>
  <div class="alert alert-warning">You do not have the required privileges to edit this record.</div>
<?php endif; ?>
<form id="entry_form" action="<?php echo url::site() . 'taxon_list/save' ?>" method="post">
  <fieldset>
    <legend>List details<?php echo $metadata ?></legend>
    <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="parent_id" id="parent_id" value="<?php echo $parent_id; ?>" />
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'taxon_list:title',
      'default' => html::initial_value($values, 'taxon_list:title'),
      'validation' => ['required'],
      'disabled' => $disabled,
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Description',
      'fieldname' => 'taxon_list:description',
      'default' => html::initial_value($values, 'taxon_list:description'),
      'validation' => ['required'],
      'disabled' => $disabled,
    ]);
    // If we have a new child list, default the website to the parent's website.
    if (empty($id) && !empty($values['parent_website_id'])) {
      $websiteId = $values['parent_website_id'];
    }
    else {
      $websiteId = html::initial_value($values, 'taxon_list:website_id');
    }
    $options = [
      'label' => 'Owned by',
      'fieldname' => 'taxon_list:website_id',
      'blankText' => '&lt;Warehouse&gt;',
      'lookupValues' => $other_data['websites'],
      'default' => $websiteId,
      'disabled' => $disabled,
    ];
    if ($this->auth->logged_in('CoreAdmin') || (!$websiteId && $id !== NULL)) {
      // Core admin can select Warehouse as owner. Other users can only have
      // this option in the list if the list is already assigned to the
      // warehouse in which case the list is read only.
      $options['blankText'] = '<Warehouse>';
    }
    echo data_entry_helper::select($options);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons(!empty($id), $this->get_read_only($values), FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
