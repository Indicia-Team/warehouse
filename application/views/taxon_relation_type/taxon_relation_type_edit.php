<?php

/**
 * @file
 * View template for the taxon relation type edit form.
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
$id = html::initial_value($values, 'taxon_relation_type:id');
?>
<form id="entry_form" action="<?php echo url::site() . 'taxon_relation_type/save' ?>" method="post">
  <fieldset>
    <legend>Taxon relation details<?php echo $metadata; ?></legend>
    <input type="hidden" name="taxon_relation_type:id" value="<?php echo $id; ?>" />
    <?php
      echo data_entry_helper::text_input([
        'label' => 'Caption',
        'fieldname' => 'taxon_relation_type:caption',
        'validation' => ['required'],
        'default' => html::initial_value($values, 'taxon_relation_type:caption'),
      ]);
      echo data_entry_helper::text_input([
        'label' => 'Forward phrase',
        'fieldname' => 'taxon_relation_type:forward_term',
        'validation' => ['required'],
        'default' => html::initial_value($values, 'taxon_relation_type:forward_term'),
        'helpText' => 'Term or phrase used when describing the relationship in the forward direction, e.g. "predates"',
      ]);
      echo data_entry_helper::text_input([
        'label' => 'Reverse phrase',
        'fieldname' => 'taxon_relation_type:reverse_term',
        'validation' => ['required'],
        'default' => html::initial_value($values, 'taxon_relation_type:reverse_term'),
        'helpText' => 'Term or phrase used when describing the relationship in the reverse direction, e.g. "is predated by"',
      ]);
      echo data_entry_helper::select([
        'label' => 'Relation behaviour',
        'fieldname' => 'taxon_relation_type:relation_code',
        'validation' => ['required'],
        'default' => html::initial_value($values, 'taxon_relation_type:relation_code'),
        'lookupValues' => [
          '0' => 'Mutually Exclusive',
          '1' => 'At Least Partial Overlap',
          '3' => 'Same or part of',
          '7' => 'The same as',
        ],
      ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id != NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
