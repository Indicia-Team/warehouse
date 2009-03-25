<form class="cmxform"  name='editList' action="<?php echo url::site().'term/save' ?>" method="POST">
<?php echo $metadata ?>
<fieldset>
<legend>List Details</legend>
<ol>
<li>
<input type="hidden" name="id" id="id" value="<?php echo html::specialchars($model->id); ?>" />
<label for="term">Term Name</label>
<input id="term" name="term" value="<?php echo html::specialchars($model->term); ?>"/>
<?php echo html::error_message($model->getError('term')); ?>
</li>
<li>
<label for="language">Language</label>
<input id="language" readonly='readonly' value="<?php echo (($model->language_id != null) ? (html::specialchars($model->language->language)) : ''); ?>"/>
<?php echo html::error_message($model->getError('language_id')); ?>
</li>
</ol>
<input type="submit" name="submit" value="Submit" />
<input type="submit" name="submit" value="Delete" />
<?php echo html::error_message($model->getError('deleted')); ?>
</fieldset>
</form>

<?php if ($model->id != '') { ?>
<form class="cmxform" action="<?php echo url::site().'term/page/'.$model->id ?>" >
<input type="submit" value="View Terms" />
</form>
<?php if ( $table != null) { ?>
	<br />
	<h2> Child Terms </h2>
	<?php echo $table; ?>
<form class="cmxform" action="<?php echo url::site(); ?>/term/create" method="post">
	<input type="hidden" name="parent_id" value=<?php echo $model->id ?> />
	<input type="submit" value="New Child Term" />
	</form>
<?php }} ?>
