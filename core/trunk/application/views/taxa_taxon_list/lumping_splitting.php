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
 * @package  Core
 * @subpackage Views
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

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