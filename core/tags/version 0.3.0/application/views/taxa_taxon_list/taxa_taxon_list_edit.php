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
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

echo html::script(array(
  'media/js/jquery.ajaxQueue.js',
  'media/js/jquery.bgiframe.min.js',
  'media/js/jquery.autocomplete.js'
), FALSE); 
$id = html::initial_value($values, 'taxa_taxon_list:id'); 
?>
<script type="text/javascript" >
$(document).ready(function() {
	var $tabs=$("#tabs").tabs();
	var initTab='<?php echo array_key_exists('tab', $_GET) ? $_GET['tab'] : '' ?>';
	if (initTab!='') {
	  $tabs.tabs('select', '#' + initTab);
	}
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
      var obj = JSON.parse(data);
      $.each(obj, function(i, item) {
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
<div id="tabs">
  <ul>
    <li><a href="#details"><span>Taxon Details</span></a></li>
<?php if ($id != null) : ?>
    <li><a href="<?php echo url::site()."taxon_image/$id" ?>" title="images"><span>Images</span></a></li>
<?php if ($values['table'] != null) : ?>
    <li><a href="#subtaxa"><span>Child Taxa</span></a></li>
<?php 
endif;
endif;
?>
  </ul>
<div id="details">
<form class="cmxform" action="<?php echo url::site().'taxa_taxon_list/save' ?>" method="post">
<?php 
echo $metadata;
?>
<fieldset>
<input type="hidden" name="taxa_taxon_list:id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:id'); ?>" />
<input type="hidden" name="taxa_taxon_list:taxon_list_id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:taxon_list_id'); ?>" />
<input type="hidden" name="taxon:id" value="<?php echo html::initial_value($values, 'taxon:id'); ?>" />
<input type="hidden" name="taxon_meaning:id" value="<?php echo html::initial_value($values, 'taxon_meaning:id'); ?>" />
<input type="hidden" name="taxa_taxon_list:preferred" value="t" />
<legend>Naming</legend>
<ol>
<li>
<label for="taxon">Taxon Name:</label>
<input name="taxon:taxon" id="taxon" value="<?php echo html::initial_value($values, 'taxon:taxon'); ?>"/>
<?php echo html::error_message($model->getError('taxon:taxon')); ?>
</li>
<li>
<label for="authority">Authority:</label>
<input id="authority" name="taxon:authority" value="<?php echo html::initial_value($values, 'taxon:authority'); ?>"/>
<?php echo html::error_message($model->getError('taxon:authority')); ?>
</li>
<li>
<label for="language_id">Language:</label>
<select name="taxon:language_id" id="language_id">
  <option value=''>&lt;Please select&gt;</option>
<?php
  $languages = ORM::factory('language')->orderby('language','asc')->find_all();
  $selected = html::initial_value($values, 'taxon:language_id');
  foreach ($languages as $lang) {
    echo '	<option value="'.$lang->id.'" ';
    if ($lang->id==$selected) {
      echo 'selected="selected" ';
    }
    echo '>'.$lang->language.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('taxon:language_id')); ?>
</li>
<li>
<label for="commonNames">Common Names:
<span class="ui-state-highlight ui-widget-content ui-corner-all" title="Enter common names one per line. Optionally follow each name by a | character then the 3 character code for the language, e.g. 'Lobworm | eng'.">?</span></label>
<textarea rows="3" cols="40" id="commonNames" name="metaFields:commonNames"><?php echo html::initial_value($values, 'metaFields:commonNames'); ?></textarea>
</li>
<li>
<label for="synonyms" >Synonyms:
<span class="ui-state-highlight ui-widget-content ui-corner-all" title="Enter synonyms one per line. Optionally follow each name by a | character then the taxon's authority, e.g. 'Zygaena viciae argyllensis | Tremewan. 1967'.">?</span></label>
<textarea rows="3"  cols="40" id="synonyms" name="metaFields:synonyms"><?php echo html::initial_value($values, 'metaFields:synonyms'); ?></textarea>
</li>
</ol>
</fieldset>
<fieldset>
<legend>Other Details</legend>
<ol>
<li>
<label for="taxon_group_id">Taxon Group:</label>
<select id="taxon_group_id" name="taxon:taxon_group_id">
  <option value=''>&lt;Please select&gt;</option>
<?php
  $taxon_groups = ORM::factory('taxon_group')->orderby('title','asc')->where('deleted', 'f')->find_all();
  $selected = html::initial_value($values, 'taxon:taxon_group_id');
  foreach ($taxon_groups as $group) {
    echo '	<option value="'.$group->id.'" ';
    if ($group->id==$selected) {
      echo 'selected="selected" ';
    }
    echo '>'.$group->title.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('taxon:taxon_group_id')); ?>
</li>
<li>
<label for="description">General description:</label>
<textarea rows="3"  cols="40" id="description" name="taxon:description"><?php echo html::initial_value($values, 'taxon:description'); ?></textarea>
</li>
<li>
<label for="description">Description specific to this list:</label>
<textarea rows="3"  cols="40" id="list_description" name="taxa_taxon_list:description"><?php echo html::initial_value($values, 'taxa_taxon_list:description'); ?></textarea>
</li>
<li>
<label for="external_key">External Key:</label>
<input id="external_key" name="taxon:external_key" value="<?php echo html::initial_value($values, 'taxon:external_key'); ?>"/>
<?php echo html::error_message($model->getError('taxon:external_key')); ?>
</li>
<li>
<input type="hidden" name="taxa_taxon_list:parent_id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:parent_id'); ?>" />
<label for="parent">Parent Taxon:</label>
<input id="parent" name="taxa_taxon_list:parent" value="<?php 
$parent_id = html::initial_value($values, 'taxa_taxon_list:parent_id'); 
echo ($parent_id != null) ? html::specialchars(ORM::factory('taxa_taxon_list', $parent_id)->taxon->taxon) : ''; 
?>" />
</li>
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
</ol>
</fieldset>
<?php
echo html::form_buttons(html::initial_value($values, 'taxa_taxon_list:id')!=null); 
?>
</form>
</div>
<?php if ($id != null && $values['table'] != null) : ?>
  <div id="subtaxa">
  <?php echo $values['table']; ?>
  <form class="cmxform" action="<?php echo url::site(); ?>taxa_taxon_list/create/<?php echo html::initial_value($values, 'taxa_taxon_list:taxon_list_id') ?>" method="post">
    <input type="hidden" name="taxa_taxon_list:parent_id" value=<?php echo html::initial_value($values, 'taxa_taxon_list:id') ?> />
    <input type="submit" value="New Child Taxon" class="ui-corner-all ui-state-default button" />
  </form>
  </div>
<?php endif; ?>
