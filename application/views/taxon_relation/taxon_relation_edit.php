<?php

/**
 * @file
 * View template for the taxon relations edit form.
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
$id = html::initial_value($values, 'taxon_relation:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$relations = ORM::factory('taxon_relation_type')->orderby('id', 'asc')->find_all();
$relationsList = [];
foreach ($relations as $relation) {
  $relationsList[] = [
    'id' => $relation->id,
    'forward_term' => $relation->forward_term,
    'reverse_term' => $relation->reverse_term,
  ];
}
$relationsJson = json_encode($relationsList);
data_entry_helper::$javascript .= "indiciaData.subTypes = $relationsJson;\n";
?>
<p>This page allows you to specify the details of a taxon relationship.</p>
<form id="entry_form" action="<?php echo url::site() . 'taxon_relation/save'; ?>" method="post">
  <fieldset>
    <legend>Relationship details<?php echo $metadata; ?></legend>
    <input type="hidden" name="taxon_relation:id" value="<?php echo $id ?>" />
    <input type="hidden" id="from_taxon_meaning_id" name="taxon_relation:from_taxon_meaning_id" value="<?php echo html::initial_value($values, 'taxon_relation:from_taxon_meaning_id'); ?>" />
    <input type="hidden" id="to_taxon_meaning_id" name="taxon_relation:to_taxon_meaning_id" value="<?php echo html::initial_value($values, 'taxon_relation:to_taxon_meaning_id'); ?>" />
    <input type="hidden" name="taxa_taxon_list:id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:id'); ?>" />
    <?php
    echo data_entry_helper::select([
      'label' => 'Relationship',
      'blankText' => '<Please select>',
      'fieldname' => 'taxon_relation:taxon_relation_type_id',
      'default' => 'taxon_relation:taxon_relation_type_id',
      'table' => 'taxon_relation_type',
      'valueField' => 'id',
      'captionField' => 'caption',
      'extraParams' => $readAuth,
      'validation' => ['required'],
    ])
    ?>
    <input type="button" id="swap-taxa" value="Swap Taxa" class="btn btn-default" />
    <?php


    // @todo Problem with getting the taxon_list_id after a validation failure.
    $speciesLookupOptions = [
      'valueField' => 'taxon_meaning_id',
      'extraParams' => $readAuth + [
        'taxon_list_id' => $values['taxa_taxon_list:taxon_list_id'],
      ],
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
    ];


    echo data_entry_helper::species_autocomplete($speciesLookupOptions + [
      'label' => 'From taxon',
      'fieldname' => 'taxon_relation:from_taxon_meaning_id',
      'default' => html::initial_value($values, 'taxon_relation:from_taxon_meaning_id'),
      'defaultCaption' => html::initial_value($values, 'taxon:from_taxon'),
    ]);
    ?>
    <div class="alert alert-info" id="term"><?php echo html::initial_value($values, 'relation:term'); ?></div>
    <?php
    echo data_entry_helper::species_autocomplete($speciesLookupOptions + [
      'fieldname' => 'taxon:to_taxon',
      'valueField' => 'taxon_meaning_id',
      'default' => html::initial_value($values, 'taxon_relation:to_taxon_meaning_id'),
      'defaultCaption' => html::initial_value($values, 'taxon:to_taxon'),
    ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
