<p>This page allows you to specify the details of a persons title.</p>
<form class="cmxform" action="<?php echo url::site().'title/save'; ?>" method="post">
<?php echo $metadata ?>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<fieldset>
<legend>Title details</legend>
<ol>
<li>
<label for="title">Title</label>
<input class="narrow" id="title" name="title" value="<?php echo html::specialchars($model->title); ?>" />
<?php echo html::error_message($model->getError('title')); ?>
</li>
</ol>
<input type="submit" value="Save" class="default" name="submit" />
<input type="submit" value="Delete" class="default" name="submit" />
</fieldset>
</form>
