<p>This page allows you to specify the details of a survey in which samples and records can be organised.</p>
<form class="cmxform" action="<?php echo url::site().'survey/save'; ?>" method="post">
<?php echo $metadata ?>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<fieldset>
<legend>Survey details</legend>
<ol>
<li>
<label for="title">Title</label>
<input id="title" name="title" value="<?php echo html::specialchars($model->title); ?>" />
<?php echo html::error_message($model->getError('title')); ?>
</li>
<li>
<label for="description">Description</label>
<textarea rows="7" id="description" name="description"><?php echo html::specialchars($model->description); ?></textarea>
<?php echo html::error_message($model->getError('description')); ?>
</li>
<li>
<label for="website_id">Website</label>
<select id="website_id" name="website_id">
	<option>&lt;Please select&gt;</option>
<?php
	if (!is_null($this->auth_filter))
		$websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->orderby('title','asc')->find_all();
	else
		$websites = ORM::factory('website')->orderby('title','asc')->find_all();
	foreach ($websites as $website) {
		echo '	<option value="'.$website->id.'" ';
		if ($website->id==$model->website_id)
			echo 'selected="selected" ';
		echo '>'.$website->title.'</option>';
	}
?>
</select>
<?php echo html::error_message($model->getError('website_id')); ?>
</li>
</ol>
</fieldset>
<input type="submit" name="submit" value="Save" />
<input type="submit" name="submit" value="Delete" />
</form>
