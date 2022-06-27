<?php

/**
 * @file
 * View template for the taxon association edit form.
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
$id = html::initial_value($values, 'taxon_association:id');
?>
<p>This page allows you to specify the details of an association between 2 taxa.</p>
<form id="taxon_association-edit" action="<?php echo url::site() . 'taxon_association/save'; ?>" method="post">
  <input type="hidden" name="taxon_association:id" value="<?php echo $id; ?>" />
  <fieldset>
    <legend>Association details<?php echo $metadata ?></legend>
    <p>Association from <?php echo $other_data['from_taxon']; ?> to <?php echo $other_data['to_taxon']; ?></p>
    <?php
    if ($other_data['type_terms']) {
      echo data_entry_helper::select([
        'label' => 'Association',
        'fieldname' => 'taxon_association:association_type_id',
        'default' => html::initial_value($values, 'taxon_association:association_type_id'),
        'lookupValues' => $other_data['type_terms'],
      ]);
    }
    if ($other_data['part_terms']) {
      echo data_entry_helper::select([
        'label' => 'Part',
        'fieldname' => 'taxon_association:part_id',
        'default' => html::initial_value($values, 'taxon_association:part_id'),
        'lookupValues' => $other_data['part_terms'],
      ]);
    }
    if ($other_data['position_terms']) {
      echo data_entry_helper::select([
        'label' => 'Position',
        'fieldname' => 'taxon_association:position_id',
        'default' => html::initial_value($values, 'taxon_association:position_id'),
        'lookupValues' => $other_data['position_terms'],
      ]);
    }
    if ($other_data['impact_terms']) {
      echo data_entry_helper::select([
        'label' => 'Impact',
        'fieldname' => 'taxon_association:impact_id',
        'default' => html::initial_value($values, 'taxon_association:impact_id'),
        'lookupValues' => $other_data['impact_terms'],
      ]);
    }
    echo data_entry_helper::radio_group([
      'label' => 'Fidelity',
      'fieldname' => 'taxon_association:fidelity',
      'default' => html::initial_value($values, 'taxon_association:fidelity'),
      'lookupValues' => [
        1 => '1 (high)',
        2 => '2 (medium)',
        3 => '3 (low)',
      ],
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Comment',
      'fieldname' => 'taxon_association:comment',
      'default' => html::initial_value($values, 'taxon_association:comment'),
    ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL);
  data_entry_helper::enable_validation('taxon_association-edit');
  ?>
</form>
