<?php

/**
 * @file
 * View template for the taxa taxon list edit form.
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

warehouse::loadHelpers(['data_entry_helper', 'map_helper']);
$id = html::initial_value($values, 'taxa_taxon_list:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

echo html::error_message($model->getError('deleted'));
?>
<form id="entry_form" action="<?php echo url::site() . 'taxa_taxon_list/save' ?>" method="post">
  <fieldset>
    <legend>Naming<?php echo $metadata; ?></legend>
    <input type="hidden" name="taxa_taxon_list:id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:id'); ?>" />
    <input type="hidden" name="taxa_taxon_list:taxon_list_id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:taxon_list_id'); ?>" />
    <input type="hidden" name="taxon:id" value="<?php echo html::initial_value($values, 'taxon:id'); ?>" />
    <input type="hidden" name="taxon_meaning:id" value="<?php echo html::initial_value($values, 'taxon_meaning:id'); ?>" />
    <input type="hidden" name="taxa_taxon_list:preferred" value="t" />
    <?php
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:taxon',
      'label' => 'Taxon name',
      'default' => html::initial_value($values, 'taxon:taxon'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:attribute',
      'label' => 'Attribute',
      'default' => html::initial_value($values, 'taxon:attribute'),
      'helpText' => 'E.g. sensu stricto or leave blank',
    ]);
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:authority',
      'label' => 'Authority',
      'default' => html::initial_value($values, 'taxon:authority'),
    ]);
    echo data_entry_helper::select([
      'fieldname' => 'taxon:language_id',
      'label' => 'Language',
      'default' => html::initial_value($values, 'taxon:language_id'),
      'table' => 'language',
      'valueField' => 'id',
      'captionField' => 'language',
      'extraParams' => $readAuth + ['orderby' => 'language'],
      'validation' => ['required'],
      'blankText' => '<please select>',
    ]);
    echo data_entry_helper::textarea([
      'fieldname' => 'metaFields:commonNames',
      'label' => 'Common names',
      'default' => html::initial_value($values, 'metaFields:commonNames'),
      'helpText' => "Enter common names one per line. Optionally follow each name by a | character then the 3 character code for the language, e.g. 'Lobworm | eng'.",
    ]);
    echo data_entry_helper::textarea([
      'fieldname' => 'metaFields:synonyms',
      'label' => 'Synonyms',
      'default' => html::initial_value($values, 'metaFields:synonyms'),
      'helpText' => "Enter synonyms one per line. Optionally follow each name by a | character then the taxon's authority, e.g. 'Zygaena viciae argyllensis | Tremewan. 1967'.",
    ]);
    ?>
  </fieldset>
  <fieldset>
    <legend>Other Details</legend>
    <?php
    echo data_entry_helper::select([
      'fieldname' => 'taxon:taxon_group_id',
      'label' => 'Taxon group',
      'default' => html::initial_value($values, 'taxon:taxon_group_id'),
      'table' => 'taxon_group',
      'valueField' => 'id',
      'captionField' => 'title',
      'extraParams' => $readAuth + ['orderby' => 'title'],
      'validation' => ['required'],
      'blankText' => '<please select>',
    ]);
    echo data_entry_helper::select([
      'fieldname' => 'taxon:taxon_rank_id',
      'label' => 'Taxon rank',
      'default' => html::initial_value($values, 'taxon:taxon_rank_id'),
      'table' => 'taxon_rank',
      'valueField' => 'id',
      'captionField' => 'rank',
      'extraParams' => $readAuth + ['orderby' => 'sort_order'],
      'blankText' => '<please select>',
    ]);
    echo data_entry_helper::textarea([
      'fieldname' => 'taxon:description',
      'label' => 'Description',
      'default' => html::initial_value($values, 'taxon:description'),
      'helpText' => 'General description which applies to this taxon on all lists it is linked to.',
    ]);
    echo data_entry_helper::textarea([
      'fieldname' => 'taxa_taxon_list:description',
      'label' => 'Description on this list',
      'default' => html::initial_value($values, 'taxa_taxon_list:description'),
      'helpText' => 'Description which applies only to this taxon within the context of this list.',
    ]);
    $helpText = <<<TXT
Unique identifier for the accepted name for this taxon as defined by an external source. For example in the UK this field is
typically used to store an NBN Taxon Version Key for the accepted name.
TXT;
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:external_key',
      'label' => 'Accepted name unique identifier (external key)',
      'default' => html::initial_value($values, 'taxon:external_key'),
      'helpText' => $helpText,
    ]);
    $helpText = <<<TXT
Unique identifier for this taxon name as defined by an external source. For example in the UK this field is
typically used to store an NBN Taxon Version Key for the name, which will therefore be the same as the accepted name
unique identifier, but any synonyms and common names would have a different Taxon Version Key / taxon name unique
identifier.
TXT;
    echo data_entry_helper::text_input([
      'label' => 'Taxon name unique identifier (search code)',
      'fieldname' => 'taxon:search_code',
      'default' => html::initial_value($values, 'taxon:search_code'),
      'helpText' => $helpText,
    ]);
    $helpText = <<<TXT
Unique identifier for this taxon concept as defined by an external source. When linking to UKSI, this
field is used to store the Organism Key.
TXT;
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:organism_key',
      'label' => 'Organism unique identifier (organism key)',
      'default' => html::initial_value($values, 'taxon:organism_key'),
      'helpText' => $helpText,
    ]);
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon_meaning:id',
      'label' => 'Taxon meaning ID',
      'default' => html::initial_value($values, 'taxon_meaning:id'),
      'helpText' => 'Unique ID assigned to this taxomic concept by Indicia.',
      'disabled' => TRUE,
    ]);
    echo data_entry_helper::species_autocomplete([
      'label' => 'Parent taxon',
      'fieldname' => 'taxa_taxon_list:parent_id',
      'default' => html::initial_value($values, 'taxa_taxon_list:parent_id'),
      'extraParams' => $readAuth + [
        'taxon_list_id' => $values['taxa_taxon_list:taxon_list_id'],
      ],
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Sort order in list',
      'fieldname' => 'taxa_taxon_list:taxonomic_sort_order',
      'default' => html::initial_value($values, 'taxa_taxon_list:taxonomic_sort_order'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Marine',
      'fieldname' => 'taxon:marine_flag',
      'default' => html::initial_value($values, 'taxon:marine_flag'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Freshwater',
      'fieldname' => 'taxon:freshwater_flag',
      'default' => html::initial_value($values, 'taxon:freshwater_flag'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Terrestrial',
      'fieldname' => 'taxon:terrestrial_flag',
      'default' => html::initial_value($values, 'taxon:terrestrial_flag'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Non-native',
      'fieldname' => 'taxon:non_native_flag',
      'default' => html::initial_value($values, 'taxon:non_native_flag'),
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Allow data entry',
      'fieldname' => 'taxa_taxon_list:allow_data_entry',
      'default' => html::initial_value($values, 'taxa_taxon_list:allow_data_entry'),
    ]);
    ?>
  </fieldset>
  <fieldset>
    <legend>Taxon Attributes</legend>
    <ol>
      <?php
      foreach ($values['attributes'] as $attr) {
        $name = "taxAttr:$attr[taxa_taxon_list_attribute_id]";
        // If this is an existing attribute, tag it with the attribute value
        // record id so we can re-save it.
        if ($attr['id']) {
          $name .= ":$attr[id]";
        }
        switch ($attr['data_type']) {
          case 'D':
          case 'V':
            echo data_entry_helper::date_picker(array(
              'label' => $attr['caption'],
              'fieldname' => $name,
              'default' => $attr['value'],
            ));
            break;

          case 'L':
            echo data_entry_helper::select(array(
              'label' => $attr['caption'],
              'fieldname' => $name,
              'default' => $attr['raw_value'],
              'lookupValues' => $values["terms_$attr[termlist_id]"],
              'blankText' => '<Please select>',
            ));
            break;

          case 'B':
            echo data_entry_helper::checkbox(array(
              'label' => $attr['caption'],
              'fieldname' => $name,
              'default' => $attr['value'],
            ));
            break;

          case 'G':
            echo "<input type=\"hidden\" name=\"$name\" value=\"$attr[value]\" id=\"imp-geom\"/>";
            echo "<label>$attr[caption]:</label>";
            echo map_helper::map_panel(array(
              'presetLayers' => array('osm'),
              'editLayer' => TRUE,
              'clickForSpatialRef' => FALSE,
              'layers' => [],
              'initial_lat' => 55,
              'initial_long' => -2,
              'initial_zoom' => 4,
              'width' => '100%',
              'height' => 400,
              'standardControls' => [
                'panZoomBar',
                'layerSwitcher',
                'hoverFeatureHighlight',
                'drawPolygon',
                'modifyFeature',
                'clearEditLayer',
              ],
            ));
            break;

          default:
            echo data_entry_helper::text_input(array(
              'label' => $attr['caption'],
              'fieldname' => $name,
              'default' => $attr['value'],
            ));
        }
      }
      ?>
    </ol>
  </fieldset>
  <?php
  echo html::form_buttons(html::initial_value($values, 'taxa_taxon_list:id') !== NULL);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
