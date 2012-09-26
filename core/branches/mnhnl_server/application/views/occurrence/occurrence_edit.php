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

?>
<?php echo html::script(array(
  'media/js/jquery.ajaxQueue.js',
  'media/js/jquery.bgiframe.min.js',
  'media/js/jquery.autocomplete.js'
), FALSE); 
$id = html::initial_value($values, 'occurrence:id'); 
$sample = $model->sample;
$website_id = $sample->survey->website_id;
?>
<script type="text/javascript" >
$(document).ready(function() {
  $("input#determiner").autocomplete("<?php echo url::site() ?>services/data/person", {
    minChars : 1,
    mustMatch : true,
    extraParams : {
      orderby : "caption",
      mode : "json",
      deleted : 'false',
      website_id : '<?php echo $website_id ?>',
      qfield : 'caption'
    },
    parse: function(data) {
      var results = [];
      $.each(data, function(i, item) {
        results[results.length] = {
          'data' : item,
          'value' : item.id,
          'result' : item.caption };
      });
      return results;
    },
    formatItem: function(item) {
      return item.caption;
    },
    formatResult: function(item) {
      return item.id;
    }
  });
  $("input#determiner").result(function(event, data){
    $("input#determiner_id").attr('value', data.id);
  });
  $("input#taxon").autocomplete("<?php echo url::site() ?>services/data/taxa_taxon_list", {
    minChars : 1,
    mustMatch : true,
    extraParams : {
      orderby : "taxon",
      mode : "json",
      deleted : 'false',
      website_id : '<?php echo $website_id ?>',
      qfield: "taxon"
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
  $("input#taxon").result(function(event, data){
    $("input#taxa_taxon_list_id").attr('value', data.id);
  });
  jQuery('.vague-date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});    
  jQuery('.date-picker').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: false});
});
</script>
<form class="cmxform" action="<?php echo url::site().'occurrence/save' ?>" method="post">
<?php 
echo $metadata; 
?>
<fieldset class="readonly">
<legend>Sample summary</legend>
<ol>
<li>
<label>Sample link:</label>
<a href="<?php echo url::site(); ?>sample/edit/<?php echo $sample->id; ?>">ID <?php echo $sample->id;?></a>
</li>
<li>
<label>Survey:</label>
<input readonly="readonly" type="text" value="<?php echo $sample->survey->title; ?>"/>
</li>
<li>
<label>Date:</label>
<input readonly="readonly" type="text" value="<?php echo $sample->date; ?>"/>
</li>
<li>
<label>Spatial reference:</label>
<input readonly="readonly" type="text" value="<?php echo $sample->entered_sref; ?>"/>
</li>
</ol>
</fieldset>
<fieldset>
<?php
print form::hidden('occurrence:id', $id);
print form::hidden('occurrence:website_id', html::initial_value($values, 'occurrence:website_id'));
print form::hidden('occurrence:sample_id', html::initial_value($values, 'occurrence:sample_id'));
?>
<legend>Occurrence Details</legend>
<ol>
<li>
<label for='taxon'>Taxon:</label>
<?php 
print form::input('taxon', $model->taxa_taxon_list->taxon->taxon);
print form::hidden('occurrence:taxa_taxon_list_id', html::initial_value($values, 'occurrence:taxa_taxon_list_id'));
echo html::error_message($model->getError('occurrence:taxa_taxon_list_id')); ?>
</li>
<li>
<label for='occurrence:comment'>Comment:</label>
<?php
print form::textarea('occurrence:comment', html::initial_value($values, 'occurrence:comment'));
echo html::error_message($model->getError('occurrence:comment'));
?>
</li>
<li>
<label for='determiner'>Determiner:</label>
<?php
$fname = $model->determiner_id ? $model->determiner->first_name : '';
$sname = $model->determiner_id ? $model->determiner->surname : '';
print form::input('determiner', $fname.' '.$sname);
print form::hidden('occurrence:determiner_id', html::initial_value($values, 'occurrence:determiner_id'));
echo html::error_message($model->getError('occurrence:determiner_id')); ?>
</li>
<li>
<label for='occurrence:confidential'>Confidential:</label>
<?php
print form::checkbox('occurrence:confidential', 't', html::initial_value($values, 'occurrence:confidential')=='t' ? 1 : 0);
echo html::error_message($model->getError('occurrence:confidential'));
?>
</li>
<li>
<label for='occurrence:zero_abundance'>Zero abundance:</label>
<?php
print form::checkbox('occurrence:zero_abundance', 't', html::initial_value($values, 'occurrence:zero_abundance')=='t' ? 1 : 0);
echo html::error_message($model->getError('occurrence:zero_abundance'));
?>
</li>
<li>
<label for='occurrence:external_key'>External Key:</label>
<?php
print form::input('occurrence:external_key', html::initial_value($values, 'occurrence:external_key'));
echo html::error_message($model->getError('occurrence:external_key'));
?>
</li>
<li>
<label for='occurrence:record_status'>Record Status:</label>
<?php
print form::dropdown('occurrence:record_status', array('I' => 'In Progress', 'C' => 'Completed', 'S' => 'Sent for verification', 'V' => 'Verified', 
    'R' => 'Rejected', 'T' => 'Test'), 
    html::initial_value($values, 'occurrence:record_status'));
echo html::error_message($model->getError('occurrence:record_status'));
?>
</li>
<?php if (html::initial_value($values, 'occurrence:record_status') == 'V'): ?>
<li>
Verified on <?php echo html::initial_value($values, 'occurrence:verified_on') ?> by <?php echo $model->verified_by->username; ?>
</li>
<?php endif; ?>
<li>
<label for='occurrence:downloaded_flag'>Download Status:</label>
<?php
print form::dropdown('occurrence:downloaded_flag', array('N' => 'Not Downloaded', 'I' => 'Trial Downloaded', 'F' => 'Downloaded - Read Only'), 
    html::initial_value($values, 'occurrence:downloaded_flag'), 'disabled="disabled"');
echo html::error_message($model->getError('occurrence:downloaded_flag'));
?>
</li>
<?php if (html::initial_value($values, 'occurrence:downloaded_flag') == 'I' || html::initial_value($values, 'occurrence:downloaded_flag') == 'F'): ?>
<li>
Downloaded on <?php echo html::initial_value($values, 'occurrence:downloaded_on') ?>
</li>
<?php endif; ?>
</ol>
</fieldset>
<fieldset>
 <legend>Survey Specific Attributes</legend>
 <ol>
 <?php
foreach ($values['attributes'] as $attr) {
  $name = 'occAttr:'.$attr['occurrence_attribute_id'];
  // if this is an existing attribute, tag it with the attribute value record id so we can re-save it
  if ($attr['id']) $name .= ':'.$attr['id'];
  echo '<li><label for="">'.$attr['caption']."</label>\n";
  switch ($attr['data_type']) {
    case 'D':
      echo form::input($name, $attr['value'], 'class="date-picker"');
      break;
    case 'V':
      echo form::input($name, $attr['value'], 'class="vague-date-picker"');
      break;
    case 'L':
      echo form::dropdown($name, $values['terms_'.$attr['termlist_id']], $attr['raw_value']);	  
      break;
    case 'B':
      echo form::dropdown($name, array(''=>'','0'=>'false','1'=>'true'), $attr['value']);
      break;
    default:
      echo form::input($name, $attr['value']);
  }
  echo '<br/>'.html::error_message($model->getError($name)).'</li>';
  
}
 ?>
 </ol>
 </fieldset>
 
<?php echo html::form_buttons(html::initial_value($values, 'occurrence:id')!=null, false, false); ?>
</form>

