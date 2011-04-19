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
$id = html::initial_value($values, 'taxon_relation:id');
$relations = ORM::factory('taxon_relation_type')->orderby('id','asc')->find_all();
?>
<script type="text/javascript" >
var subTypes = [
<?php
  foreach ($relations as $relation) {
    echo '	{id: '.$relation->id.', forward_term: "'.$relation->forward_term.'", reverse_term: "'.$relation->reverse_term.'"},';
  }
?>
];

$(document).ready(function() {
  $("input#from_taxon,input#to_taxon").autocomplete("<?php echo url::site() ?>services/data/taxa_taxon_list", {
    minChars : 1,
//    mustMatch : true,
    extraParams : {
      orderby : "taxon",
      mode : "json",
      deleted : 'false',
      view : 'detail',
      qfield : 'taxon'
    },
    parse: function(data) {
      var results = [];
      $.each(data, function(i, item) {
        results[results.length] = {
          'data' : item,
          'value' : item.taxon_meaning_id,
          'result' : item.taxon };
      });
      return results;
    },
    formatItem: function(item) {
      return item.taxon;
    },
    formatResult: function(item) {
      return item.taxon_meaning_id;
    }
  });
  $("input#from_taxon").result(function(event, data){
    $("input#from_taxon_meaning_id").attr('value', data.taxon_meaning_id);
  });
  $("input#to_taxon").result(function(event, data){
    $("input#to_taxon_meaning_id").attr('value', data.taxon_meaning_id);
  });
  $("#taxon_relation_type_id").change(function(){
    for (var i = 0; i < subTypes.length; i++){
      if (subTypes[i].id == jQuery(this).val()) {
        jQuery("#term").val(subTypes[i].forward_term);
      }
    }
  });
});
</script>
<p>This page allows you to specify the details of a taxon relationship.</p>
<form class="cmxform" action="<?php echo url::site().'taxon_relation/save'; ?>" method="post" enctype="multipart/form-data">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="taxon_relation:id" value="<?php echo $id ?>" />
<input type="hidden" id="from_taxon_meaning_id" name="taxon_relation:from_taxon_meaning_id" value="<?php echo html::initial_value($values, 'taxon_relation:from_taxon_meaning_id'); ?>" />
<input type="hidden" id="to_taxon_meaning_id" name="taxon_relation:to_taxon_meaning_id" value="<?php echo html::initial_value($values, 'taxon_relation:to_taxon_meaning_id'); ?>" />
<input type="hidden" name="taxa_taxon_list:id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:id'); ?>" />
<legend>Relationship details</legend>
<ol>
<li>
<label for="taxon_relation_type_id">Relationship</label>
<select name="taxon_relation:taxon_relation_type_id" id="taxon_relation_type_id">
  <option value=''>&lt;Please select&gt;</option>
<?php
  $selected = html::initial_value($values, 'taxon_relation:taxon_relation_type_id');
  foreach ($relations as $relation) {
    echo '	<option value="'.$relation->id.'" ';
    if ($relation->id==$selected) {
      echo 'selected="selected" ';
    }
    echo '>'.$relation->caption.'</option>';
  }
?>
</select>
<?php echo html::error_message($model->getError('taxon_relation:taxon_relation_type_id')); ?>
</li>
<li>
<input type="button" value="Swap Taxa" onclick="var x = jQuery('#from_taxon').val(); jQuery('#from_taxon').val(jQuery('#to_taxon').val()); jQuery('#to_taxon').val(x); x = jQuery('#from_taxon_meaning_id').val(); jQuery('#from_taxon_meaning_id').val(jQuery('#to_taxon_meaning_id').val()); jQuery('#to_taxon_meaning_id').val(x)" class="ui-corner-all ui-state-default button" />
</li>
<li>
<label for="from_taxon">Taxon Name:</label>
<input name="taxon:from_taxon" id="from_taxon" value="<?php echo html::initial_value($values, 'taxon:from_taxon'); ?>"/>
<?php echo html::error_message($model->getError('taxon:taxon')); ?>
</li>
<li>
<label for="term"></label>
<input name="relation:term" id="term" value="<?php echo html::initial_value($values, 'relation:term'); ?>" disabled="disabled" />
<?php echo html::error_message($model->getError('taxon:taxon')); ?>
</li>
<li>
<label for="to_taxon">Taxon Name:</label>
<input name="taxon:to_taxon" id="to_taxon" value="<?php echo html::initial_value($values, 'taxon:to_taxon'); ?>"/>
<?php echo html::error_message($model->getError('taxon:taxon')); ?>
</li>

</ol>
</fieldset>
<?php echo html::form_buttons($id!=null, false, false); ?>
</form>