<script type='text/javascript'>
$(document).ready(function(){
	$('div#metadata').hide();
	$('p#metadata_toggle').show();
	$('p#metadata_toggle').click(function(){
		$('div#metadata').toggle('slow');
	});
});
</script>
<p id='metadata_toggle'>Show/Hide Metadata</p>
<div id='metadata'>
<fieldset>
<legend>Metadata</legend>
<input type="hidden" id="created_by_id" name="created_by_id" value="<?php echo html::specialchars($model->created_by_id); ?>" />
<input type="hidden" name="updated_by_id" id="updated_by_id" value="<?php echo html::specialchars($model->updated_by_id); ?>" />
<ol>
<li>
Record ID is <?php if ($model->id) echo $model->id; else echo '<new record>';?>
</li>
<li>
Created on <?php echo html::specialchars($model->created_on); ?> by <?php echo (($model->created_by_id != null) ? (html::specialchars($model->created_by->person->surname)) : ''); ?>.
</li>
<li>
Updated on <?php echo html::specialchars($model->updated_on); ?> by <?php echo (($model->updated_by_id != null) ? (html::specialchars($model->updated_by->person->surname)) : ''); ?>.
</li>
</ol>
</fieldset>
</div>
