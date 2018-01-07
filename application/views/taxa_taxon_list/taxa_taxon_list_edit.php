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

warehouse::loadHelpers(['data_entry_helper', 'map_helper']);
$id = html::initial_value($values, 'taxa_taxon_list:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

?>
<script type="text/javascript" >
$(document).ready(function() {
  $("input#parent").autocomplete("<?php echo url::site() ?>services/data/taxa_taxon_list", {
    minChars : 1,
    mustMatch : true,
    extraParams : {
      taxon_list_id : "<?php echo html::initial_value($values, 'taxa_taxon_list:taxon_list_id'); ?>",
      orderby : "taxon",
      mode : "json",
      qfield : "taxon",
      preferred : 'true'
    },
    parse: function(data) {
      var results = [];
      $.each(data, function(i, item) {
        results[results.length] = {
          'data' : item,
          'value' : item.id,
          'result' : item.taxon };
      });
      return results;
    },
    formatItem: function(item) {
      return item.taxon;
    },
    formatResult: function(item) {
      return item.id;
    }
  });
  $("input#parent").result(function(event, data){
    $("input#parent_id").attr('value', data.id);
  });
});
</script>

<?php
echo html::error_message($model->getError('deleted'));
?>
<form id="taxa-taxon-list-edit" action="<?php echo url::site() . 'taxa_taxon_list/save' ?>" method="post">
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
      'helpText' => "Enter common names one per line. Optionally follow each name by a | character then the 3 " .
        "character code for the language, e.g. 'Lobworm | eng'.",
    ]);
    echo data_entry_helper::textarea([
      'fieldname' => 'metaFields:synonyms',
      'label' => 'Synonyms',
      'default' => html::initial_value($values, 'metaFields:synonyms'),
      'helpText' => "Enter synonyms one per line. Optionally follow each name by a | character then the taxon's " .
        "authority, e.g. 'Zygaena viciae argyllensis | Tremewan. 1967'.",
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
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon:external_key',
      'label' => 'External key',
      'default' => html::initial_value($values, 'taxon:external_key'),
      'helpText' => 'Unique key for this taxon concept as defined by an external source. For example in the UK ' .
        'this field is typically used to store an NBN Taxon Version Key.',
    ]);
    echo data_entry_helper::text_input([
      'fieldname' => 'taxon_meaning:id',
      'label' => 'Taxon meaning ID',
      'default' => html::initial_value($values, 'taxon_meaning:id'),
      'helpText' => 'Unique ID assigned to this taxomic concept by Indicia.',
      'disabled' => TRUE,
    ]);
    echo data_entry_helper::species_autocomplete([
      'fieldname' => 'taxa_taxon_list:parent_id',
      'default' => html::initial_value($values, 'taxa_taxon_list:parent_id'),
      'extra_params' => $readAuth + [
        'taxon_list_id' => $values['taxa_taxon_list:taxon_list_id'],
      ],
    ]);
    ?>
<ol>
<li>
<label for="taxonomic_sort_order">Sort Order in List:</label>
<input id="taxonomic_sort_order" name="taxa_taxon_list:taxonomic_sort_order" class="narrow" value="<?php echo html::initial_value($values, 'taxa_taxon_list:taxonomic_sort_order'); ?>" />
<?php echo html::error_message($model->getError('taxa_taxon_list:taxonomic_sort_order')); ?>
</li>
<li>
<label for="search_code">Search Code:</label>
<input id="search_code" name="taxon:search_code" class="narrow" value="<?php echo html::initial_value($values, 'taxon:search_code'); ?>"/>
<?php echo html::error_message($model->getError('taxon:search_code')); ?>
</li>
<li>
<label for="allow_data_entry">Allow Data Entry:</label>
<?php // ensure that an unchecked checkbox still sends the value ?>
<input type="hidden" name="taxa_taxon_list:allow_data_entry" value="0" />
<?php echo form::checkbox(array('id' => 'allow_data_entry', 'name' => 'taxa_taxon_list:allow_data_entry'), TRUE, array_key_exists('taxa_taxon_list:allow_data_entry', $values) AND ($values['taxa_taxon_list:allow_data_entry'] == 't') ) ?>
</li>
</ol>
</fieldset>
<fieldset>
 <legend>Taxon Attributes</legend>
 <ol>
 <?php
 foreach ($values['attributes'] as $attr) {
	$name = 'taxAttr:'.$attr['taxa_taxon_list_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
	switch ($attr['data_type']) {
    case 'D':
    case 'V':
      echo data_entry_helper::date_picker(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
      break;
    case 'L':
      echo data_entry_helper::select(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['raw_value'],
        'lookupValues' => $values['terms_'.$attr['termlist_id']],
        'blankText' => '<Please select>'
      ));
      break;
    case 'B':
      echo data_entry_helper::checkbox(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
      break;
    case 'G':
      echo '<input type="hidden" name="'.$name.'" value="'.$attr['value'].'" id="imp-geom"/>';
      echo '<label>'.$attr['caption'].':</label>';
      echo map_helper::map_panel(array(
        'presetLayers' => array('osm'),
        'editLayer' => true,
        'clickForSpatialRef'=>false,
        'layers' => array(),
        'initial_lat'=>55,
        'initial_long'=>-2,
        'initial_zoom'=>4,
        'width'=>870,
        'height'=>400,
        'standardControls' => array('panZoomBar','layerSwitcher','hoverFeatureHighlight','drawPolygon','modifyFeature','clearEditLayer')
      ));
      break;
    default:
      echo data_entry_helper::text_input(array(
        'label' => $attr['caption'],
        'fieldname' => $name,
        'default' => $attr['value']
      ));
  }

}
 ?>
 </ol>
  </fieldset>
  <?php
  // some script to handle drawn polygons. Only allow 1 polygon on the layer
  data_entry_helper::$javascript .= "mapInitialisationHooks.push(function(div) {
    function featureChangeEvent(evt) {
      var featuresToRemove=[];
      $.each(evt.feature.layer.features, function(idx, feature) {
        if (feature.id !== evt.feature.id) {
          featuresToRemove.push(feature);
        }
      });
      evt.feature.layer.removeFeatures(featuresToRemove);
      $('#imp-geom').val(evt.feature.geometry.toString());
    }
    div.map.editLayer.events.on({'featureadded': featureChangeEvent, 'afterfeaturemodified': featureChangeEvent});
  });
  ";
  echo html::form_buttons(html::initial_value($values, 'taxa_taxon_list:id') !== NULL);
  data_entry_helper::enable_validation('taxa_taxon_list-edit');
  echo data_entry_helper::dump_javascript();
  ?>
</form>


