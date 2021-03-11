<?php

/**
 * @file
 * View template for the taxon rank edit form.
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
$id = html::initial_value($values, 'taxon_rank:id');
?>
<p>This page allows you to specify the details of a rank in the taxon hierarchy.</p>
<form id="entry_form" action="<?php echo url::site() . 'taxon_rank/save'; ?>" method="post">
  <fieldset>
    <input type="hidden" name="taxon_rank:id" value="<?php echo $id ?>" />
    <legend>Taxon rank details<?php echo $metadata; ?></legend>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Rank',
      'fieldname' => 'taxon_rank:rank',
      'default' => html::initial_value($values, 'taxon_rank:rank'),
      'helpText' => 'The main label used for this taxon rank.',
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Short name',
      'fieldname' => 'taxon_rank:short_name',
      'default' => html::initial_value($values, 'taxon_rank:short_name'),
      'helpText' => 'The shortened label used for this taxon rank.',
      'validation' => ['required'],
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Italicise taxon',
      'fieldname' => 'taxon_rank:italicise_taxon',
      'default' => html::initial_value($values, 'taxon_rank:italicise_taxon'),
      'helpText' => 'Tick this box if latin species names of this rank are typically shown in italics.',
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Sort order',
      'fieldname' => 'taxon_rank:sort_order',
      'default' => html::initial_value($values, 'taxon_rank:sort_order'),
      'helpText' => 'The sort order of this taxon rank. Ranks higher up the taxonomic tree have a lower order.',
      'validation' => ['required', 'integer'],
    ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons(!empty($id), FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
