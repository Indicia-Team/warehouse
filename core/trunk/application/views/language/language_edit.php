<form class="cmxform"  name='editList' action="<?php echo url::site().'language/save' ?>" method="POST">
<fieldset>
<legend>Language Details</legend>
<ol>
<li>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<label for="iso">ISO language code</label>
<input id="iso" name="iso" class="narrow" value="<?php echo html::specialchars($model->iso); ?>"/>
<?php echo html::error_message($model->getError('iso')); ?>
</li>
<li>
<label for="language">Language</label>
<input id="language" name="language" value="<?php echo html::specialchars($model->language); ?>" />
<?php echo html::error_message($model->getError('language')); ?>
</li>
</ol>
</fieldset>
<?php echo $metadata ?>
<input type="submit" name="submit" value="Submit" />
<input type="submit" name="submit" value="Delete" />

