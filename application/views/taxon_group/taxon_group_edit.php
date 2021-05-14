<?php

/**
 * @file
 * View template for the taxon group edit form.
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
$id = html::initial_value($values, 'taxon_group:id');
?>
<p>This page allows you to specify the details of a taxon group.</p>
<form id="entry_form" action="<?php echo url::site() . 'taxon_group/save'; ?>" method="post">
  <input type="hidden" name="taxon_group:id" value="<?php echo $id; ?>" />
  <fieldset>
    <legend>Taxon Group details<?php echo $metadata ?></legend>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'taxon_group:title',
      'default' => html::initial_value($values, 'taxon_group:title'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'External key',
      'fieldname' => 'taxon_group:external_key',
      'default' => html::initial_value($values, 'taxon_group:external_key'),
    ]);
    ?>
    <?php echo html::error_message($model->getError('taxon_group:external_key')); ?>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
