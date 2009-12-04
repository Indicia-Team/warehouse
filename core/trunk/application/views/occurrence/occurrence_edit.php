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
  'media/js/thickbox-compressed.js',
  'media/js/jquery.autocomplete.js'
), FALSE); ?>
<script type="text/javascript" >
$(document).ready(function() {
  $("input#determiner").autocomplete("<?php echo url::site() ?>services/data/person", {
    minChars : 1,
    mustMatch : true,
    extraParams : {
      orderby : "caption",
      mode : "json",
      deleted : 'false'
    },
    parse: function(data) {
      var results = [];
      var obj = JSON.parse(data);
      $.each(obj, function(i, item) {
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
      deleted : 'false'
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
  $("input#taxon").result(function(event, data){
    $("input#taxa_taxon_list_id").attr('value', data.id);
  });
});
</script>
<form class="cmxform" action="<?php echo url::site().'occurrence/save' ?>" method="post">
<?php 
echo $metadata;
if (count($values['images'])>0) : ?>
  <fieldset class="imagelist">
  <legend>Images</legend>
  <?php
  foreach ($values['images'] as $image) {
    $obj = json_decode($image->external_details, true);

    if (array_key_exists('flickr', $obj)) {
      $photo = $obj['flickr'];
      echo '<a class="thickbox" href="http://farm'.$photo['farm'].'.static.flickr.com/'.$photo['server'].'/'.
                      $photo['id'].'_'.$photo['secret'].'.jpg"><img alt="'.$photo['title'].'" title="'.$photo['title'].'" src="http://farm'.$photo['farm'].'.static.flickr.com/'.$photo['server'].'/'.
                      $photo['id'].'_'.$photo['secret'].'_t.jpg" /></a>';
    }
  }
  ?>
  </fieldset>
<?php endif; ?>
<fieldset>
<?php
print form::hidden('id', html::initial_value($values, 'occurrence:id'));
print form::hidden('website_id', html::initial_value($values, 'occurrence:website_id'));
print form::hidden('sample_id', html::initial_value($values, 'occurrence:sample_id'));
?>
<legend>Occurrence Details</legend>
<ol>
<li>
<label for='taxon'>Taxon:</label>
<?php print form::input('taxon', $model->taxa_taxon_list->taxon->taxon);
print form::hidden('occurrence:taxa_taxon_list_id', html::initial_value($values, 'occurrence:taxa_taxon_list_id'));
echo html::error_message($model->getError('occurrence:taxa_taxon_list_id')); ?>
</li>
<li>
<label for='date'>Date:</label>
<?php print form::input('date', $model->sample->date);
echo html::error_message($model->taxa_taxon_list->taxon->getError('occurrence:date_start')); ?>
</li>
<li>
<label for='determiner'>Determiner:</label>
<?php
$fname = $model->determiner_id ? $model->determiner->first_name : '';
$sname = $model->determiner_id ? $model->determiner->surname : '';
print form::input('determiner', $fname.' '.$sname);
print form::hidden('occurrence:determiner_id', html::initial_value($values, 'occurrence:determiner_id'));
echo html::error_message($model->getError('determiner_id')); ?>
</li>
<li>
<label for='occurrence:confidential'>Confidential?:</label>
<?php
print form::checkbox('occurrence:confidential', 'true', html::initial_value($values, 'occurrence:confidential')=='t' ? 1 : 0);
echo html::error_message($model->getError('occurrence:confidential'));
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
print form::dropdown('occurrence:record_status', array('I' => 'In Progress', 'C' => 'Completed', 'V' => 'Verified'), 
    html::initial_value($values, 'occurrence:record_status'));
echo html::error_message($model->getError('occurrence:record_status'));
?>
</li>
<?php if (html::initial_value($values, 'occurrence:record_status') == 'V'): ?>
<li>
Verified on <?php echo html::initial_value($values, 'occurrence:verified_on') ?> by <?php echo $model->verified_by->username; ?>
</li>
<?php endif; ?>
</ol>
</fieldset>
<?php
echo $values['comments'];
echo html::form_buttons(html::initial_value($values, 'occurrence:id')!=null);
?>
