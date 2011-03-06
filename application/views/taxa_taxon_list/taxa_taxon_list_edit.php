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
 /* For lumping, if there is a taxon_relation record already existing with this taxon in the from_ field, then this has already been lumped.
  * Similar for splitting.
  * Can only lump or split existing records, as need the taxon_meaning_id.
  * Can only split a record that already exists and which has not already been split. Can split something further which was created by splitting something else.
  * Similar for lumping
  */
  $can_split = false;
  $can_lump = false;
  $lumpRecord = ORM::factory('taxon_relation_type', array('special' => 'L'));
  $splitRecord = ORM::factory('taxon_relation_type', array('special' => 'R'));
  if($id != null) {
    $split = ORM::factory('taxon_relation')->where(array('deleted' => 'f', 'from_taxon_meaning_id' => html::initial_value($values, 'taxon_meaning:id'), 'taxon_relation_type_id' => $splitRecord->id))->find_all();
    if (count($split) == 0) $can_split = true;
    $lumped = ORM::factory('taxon_relation')->where(array('deleted' => 'f', 'from_taxon_meaning_id' => html::initial_value($values, 'taxon_meaning:id'), 'taxon_relation_type_id' => $lumpRecord->id))->find_all();
    if (count($lumped) == 0) $can_lump = true;
  }
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
  jQuery("#LStaxon").autocomplete("<?php echo url::site() ?>services/data/taxa_taxon_list", {
    minChars : 1,
    extraParams : {
      taxon_list_id : "<?php echo html::initial_value($values, 'taxa_taxon_list:taxon_list_id'); ?>",
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
  jQuery("#LStaxon").result(function(event, data){
	  jQuery("#LStaxon_meaning_id").attr('value', data.taxon_meaning_id);
  });
});
removeMe = function(elem){
  jQuery(elem).parent().parent().remove(); // go from a tag, to td, to whole tr
};
addLStaxon = function(){
	if(jQuery('#LStaxon_meaning_id').val() == '') return;
	existing = jQuery('#LS_Taxon_Body').find('[name='+jQuery('#LStaxon_meaning_id').val()+']');
	if(existing.length >= 1){
		alert('Taxon already added under "'+existing.data('name')+'"');
		return;
	}
	var can_be_lumped = true;
	var can_be_split_into = true;
    jQuery.ajax({ 
      type: "GET", 
      url: "<?php echo url::site() ?>services/data/taxon_relation?mode=json&view=list" +
           "&to_taxon_meaning_id=" + jQuery('#LStaxon_meaning_id').val() + "&callback=?", 
      data: {}, 
      success: function(trdata) {
        if(!(trdata instanceof Array)){
          // do nothing
        } else if (trdata.length>0) {
          for(i=0; i< trdata.length; i++){
            if(trdata[i].taxon_relation_type_id == <?php echo $lumpRecord->id; ?>)
              can_be_lumped = false;
            else if(trdata[i].taxon_relation_type_id == <?php echo $splitRecord->id; ?>)
              can_be_split_into = false;
          }
        }},
      dataType: 'json', 
      async: false  
    });
    if(jQuery('#LS_Taxon_Body').children().length % 2 == 1)
        cls = 'evenRow';
    else
        cls = 'oddRow';
    jQuery('<tr class="'+cls+'" ><td>' + jQuery('#LStaxon').val() +'</td>' +
      <?php if($can_lump){ ?>'<td>' + (can_be_lumped ? 'No' : 'Yes') + '</td>' + <?php } ?>
      <?php if($can_split){ ?>'<td>' + (can_be_split_into ? 'No' : 'Yes') + '</td>' + <?php } ?>
      '<td><a onClick="removeMe(this)" >Remove</a></td></tr>').attr('name',jQuery('#LStaxon_meaning_id').val()).data('meaning',jQuery('#LStaxon_meaning_id').val()).data('name', jQuery('#LStaxon').val()).data('can_be_lumped', can_be_lumped).data('can_be_split_into', can_be_split_into).appendTo('#LS_Taxon_Body');
};
performLump = function(){
  // first loop through each item in list to ensure that they have not already been lumped into something.
  // If all OK, create the relation record, then loop through the taxa in the list and set their allow_data_entry to false.
  num_cant_lump = 0;
  num_can_lump = 0;
  jQuery('#LS_Taxon_Body').children().each(function(index, elem){
    if(!jQuery(elem).data('can_be_lumped')) num_cant_lump++;
    else num_can_lump++;
  });
  if(num_cant_lump > 0)
    if(!confirm("There are several taxa in the list which are already lumped into another taxon. Do you wish to carry on? Any such previously lumped taxa will NOT be included in this lump.")) return;
  if(num_can_lump == 0){
    alert("There are no taxa to lump.");
    return;
  }
  sa = {id : 'multiple',
        submission_list : {entries : []}};
  
  jQuery('#LS_Taxon_Body').children().each(function(index, elem){
    // Build the taxon_relation submission
    sa.submission_list.entries.push({id : 'taxon_relation',
        fields : { from_taxon_meaning_id : {value : "<?php echo html::initial_value($values, 'taxon_meaning:id'); ?>" },
                   to_taxon_meaning_id : {value : jQuery(elem).data('meaning') },
                   taxon_relation_type_id : {value : <?php echo $lumpRecord->id; ?> }}});
    // next loop through all taxa_taxon_lists with the meaning id and set allow_data_entry to false
    jQuery.ajax({ 
        type: "GET", 
        url: "<?php echo url::site() ?>services/data/taxa_taxon_list?mode=json&view=detail&taxon_meaning_id=" + jQuery(elem).data('meaning') + "&callback=?", 
        data: {}, 
        success: function(ttlData) {
          if(ttlData instanceof Array){
            for (j=0; j< ttlData.length; j++){
              sa.submission_list.entries.push({id : 'taxa_taxon_list',
                fields : { id : {value : ttlData[j].id},
                           taxon_list_id : {value : ttlData[j].taxon_list_id},
                           taxon_id : {value : ttlData[j].taxon_id},
                           taxon_meaning_id : {value : ttlData[j].taxon_meaning_id},
                           preferred : {value : ttlData[j].preferred}}}); // the very fact that allow_data_entry is not in the submission should set it to 'false'
          }}
        },
        dataType: 'json', 
        async: false  
    });
  });
  // send all changes as a single save transaction.
  jQuery.ajax({ 
      type: "POST", 
      url: "<?php echo url::site() ?>services/data/save?mode=json",
      data: {submission : JSON.stringify(sa)}, 
      success: function(attrdata) {
      },
      dataType: 'json', 
      async: false  
  });
  window.location.reload();
}
performSplit = function(){
	  // first loop through each item in list to ensure that they have not already been split from something else.
	  // If all OK, create the relation record, then set the allow_data_entry flag for this record to false and submit.
	  num_cant_split = 0;
	  num_can_split = 0;
	  jQuery('#LS_Taxon_Body').children().each(function(index, elem){
	    if(!jQuery(elem).data('can_be_split_into')) num_cant_split++;
	    else num_can_split++;
	  });
	  if(num_cant_split > 0)
	    if(!confirm("There are several taxa in the list which another taxon has already been split into. Do you wish to carry on? Any such taxa which are the result of a previous split will NOT be included in this split.")) return;
	  if(num_can_split == 0){
	    alert("There are no taxa in the list to split this taxon into.");
	    return;
	  }
	  sa = {id : 'multiple',
	        submission_list : {entries : []}};
	  
	  jQuery('#LS_Taxon_Body').children().each(function(index, elem){
	    // Build the taxon_relation submission
	    sa.submission_list.entries.push({id : 'taxon_relation',
	        fields : { from_taxon_meaning_id : {value : "<?php echo html::initial_value($values, 'taxon_meaning:id'); ?>" },
	                   to_taxon_meaning_id : {value : jQuery(elem).data('meaning') },
	                   taxon_relation_type_id : {value : <?php echo $splitRecord->id; ?> }}});
	  });
      jQuery.ajax({ 
        type: "GET", 
        url: "<?php echo url::site() ?>services/data/taxa_taxon_list?mode=json&view=detail&taxon_meaning_id=<?php echo html::initial_value($values, 'taxon_meaning:id'); ?>&callback=?", 
        data: {}, 
        success: function(ttlData) {
          if(ttlData instanceof Array){
            for (j=0; j< ttlData.length; j++){
              sa.submission_list.entries.push({id : 'taxa_taxon_list',
                fields : { id : {value : ttlData[j].id},
                           taxon_list_id : {value : ttlData[j].taxon_list_id},
                           taxon_id : {value : ttlData[j].taxon_id},
                           taxon_meaning_id : {value : ttlData[j].taxon_meaning_id},
                           preferred : {value : ttlData[j].preferred}}}); // the very fact that allow_data_entry is not in the submission should set it to 'false'
          }}
        },
        dataType: 'json', 
        async: false  
      });
	  // send all changes as a single save transaction.
	  jQuery.ajax({ 
	      type: "POST", 
	      url: "<?php echo url::site() ?>services/data/save?mode=json",
	      data: {submission : JSON.stringify(sa)}, 
	      success: function(attrdata) {
	      },
	      dataType: 'json', 
	      async: false  
	  });
	  window.location.reload();
	}

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
endif; ?>
    <li><a href="<?php echo url::site()."taxon_relation/$id" ?>" title="relations"><span>Relationships</span></a></li>
    <li><a href="#lumpandsplit"><span>Lumping and Splitting</span></a></li>
<?php 
endif;
?>
  </ul>
<div id="details">
<form id="ttlMainForm" class="cmxform" action="<?php echo url::site().'taxa_taxon_list/save' ?>" method="post">
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
<li>
<label for="allow_data_entry">Allow Data Entry:</label>
<?php echo form::checkbox(array('id' => 'allow_data_entry', 'name' => 'taxa_taxon_list:allow_data_entry'), TRUE, array_key_exists('taxa_taxon_list:allow_data_entry', $values) AND ($values['taxa_taxon_list:allow_data_entry'] == 't') ) ?>
</li>
</ol>
</fieldset>
<?php
echo html::form_buttons(html::initial_value($values, 'taxa_taxon_list:id')!=null); 
?>
</form>
</div>
<?php if($id != null) { ?>
<div id="lumpandsplit">
<form class="cmxform" >
  Lumping and splitting are special cases of Relationships. They implement a means to implement changes in the taxon list over time, as individual taxa are split up into a set of taxa, or the opposite, where several taxa are lumped together into another.<br/><br/>
<?php 
  if($can_split == false){
    echo html::initial_value($values, 'taxon:taxon').' '.$splitRecord->forward_term.' ';
    $first = true;
    foreach($split as $target){
      $t1 = ORM::factory('taxa_taxon_list', array('preferred'=>'t', 'taxon_meaning_id'=>$target->to_taxon_meaning_id));
      $t2 = ORM::factory('taxon', $t1->taxon_id);
      echo ($first == true ? '' : ', ').$t2->taxon;
      $first = false;
    }
    echo '.<br/><br/>';
  }
  if($can_lump == false){
    echo html::initial_value($values, 'taxon:taxon').' '.$lumpRecord->forward_term.' ';
    $first = true;
    foreach($lumped as $target){
      $t1 = ORM::factory('taxa_taxon_list', array('preferred'=>'t', 'taxon_meaning_id'=>$target->to_taxon_meaning_id));
      $t2 = ORM::factory('taxon', $t1->taxon_id);
      echo ($first == true ? '' : ', ').$t2->taxon;
      $first = false;
    }
    echo '.<br/><br/>';
  }
  if ($can_split || $can_lump){
?>
    <input id="LStaxon" name="LStaxon" value="" />
    <input type="hidden" id="LStaxon_meaning_id" />
    <input type="button" value="Add to List" onClick="addLStaxon()" class="ui-corner-all ui-state-default button" /><br/>
    <table id="LS_taxa_table" class="ui-widget ui-widget-content"><thead class="ui-widget-header" ><tr class="headingRow" >
        <th id="LS_Taxon">Taxon</th>
        <?php if($can_lump){ ?><th id="Already_Lumped">Already Lumped</th><?php } ?>
        <?php if($can_split){ ?><th id="Already_Split">Already part of a Split</th><?php } ?>
        <th id="LS_Task">Task</th></tr></thead><tbody id="LS_Taxon_Body"></tbody></table><br/>
    <div id="Link_and_Split_taxa_list"></div><br/>
    <?php if($can_lump){ ?><input type="button" value="Perform Lump" onClick="performLump()" class="ui-corner-all ui-state-default button" /><br />Performing a lump will tag all the taxa in the list above as being lumped into this taxon. The flag to allow the taxa in the list to be used for data entry will be set to false. A check will be made to ensure that each of the taxa in the list have not already been lumped into another taxon. Please save any other changes to this taxon before carrying out this action.<br/><br/><?php } ?>
    <?php if($can_split){ ?><input type="button" value="Perform Split" onClick="performSplit()" class="ui-corner-all ui-state-default button" /><br />Performing a split will tag all the taxa in the above list as being the created by splitting this taxon. The flag to allow this taxon to be used for data entry will be set to false. A check will be made to ensure that each of the taxa in the list have not already been tagged as being the result of splitting another taxon. Please save any other changes to this taxon before carrying out this action.<br/><?php } ?>
<?php } ?>
</form>
</div>
</div>
<?php } ?>
<?php if ($id != null && $values['table'] != null) : ?>
  <div id="subtaxa">
  <?php echo $values['table']; ?>
  <form class="cmxform" action="<?php echo url::site(); ?>taxa_taxon_list/create/<?php echo html::initial_value($values, 'taxa_taxon_list:taxon_list_id') ?>" method="post">
    <input type="hidden" name="taxa_taxon_list:parent_id" value=<?php echo html::initial_value($values, 'taxa_taxon_list:id') ?> />
    <input type="submit" value="New Child Taxon" class="ui-corner-all ui-state-default button" />
  </form>
  </div>
<?php endif; ?>

