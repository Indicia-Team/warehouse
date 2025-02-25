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
$id = html::initial_value($values, 'occurrence_association:id');
?>
<p>This page allows you to specify the details of an association between 2 occurrences.</p>
<form id="occurrence_association-edit" action="<?php echo url::site() . 'occurrence_association/save'; ?>" method="post">
  <input type="hidden" name="occurrence_association:id" value="<?php echo $id; ?>" />
  <fieldset>
    <legend>Association details<?php echo $metadata ?></legend>
    <p>Association from <?php echo $other_data['from_taxon']; ?> to <?php echo $other_data['to_taxon']; ?></p>
    <?php
    if ($other_data['type_terms']) {
      echo data_entry_helper::select([
        'label' => 'Association',
        'fieldname' => 'occurrence_association:association_type_id',
        'default' => html::initial_value($values, 'occurrence_association:association_type_id'),
        'lookupValues' => $other_data['type_terms'],
      ]);
    }
    if ($other_data['part_terms']) {
      echo data_entry_helper::select([
        'label' => 'Part',
        'fieldname' => 'occurrence_association:part_id',
        'default' => html::initial_value($values, 'occurrence_association:part_id'),
        'lookupValues' => $other_data['part_terms'],
      ]);
    }
    if ($other_data['position_terms']) {
      echo data_entry_helper::select([
        'label' => 'Position',
        'fieldname' => 'occurrence_association:position_id',
        'default' => html::initial_value($values, 'occurrence_association:position_id'),
        'lookupValues' => $other_data['position_terms'],
      ]);
    }
    if ($other_data['impact_terms']) {
      echo data_entry_helper::select([
        'label' => 'Impact',
        'fieldname' => 'occurrence_association:impact_id',
        'default' => html::initial_value($values, 'occurrence_association:impact_id'),
        'lookupValues' => $other_data['impact_terms'],
      ]);
    }
    echo data_entry_helper::textarea([
      'label' => 'Comment',
      'fieldname' => 'occurrence_association:comment',
      'default' => html::initial_value($values, 'occurrence_association:comment'),
    ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL);
  data_entry_helper::enable_validation('occurrence_association-edit');
  ?>
</form>
