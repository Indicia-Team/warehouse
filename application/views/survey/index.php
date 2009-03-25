<div class="survey">
<?php echo $table ?>
<br/>
<form action="<?php echo url::site().'survey/create'; ?>" method="post">
<input type="submit" value="New survey" />
</form>
<br />
<?php echo $upload_csv_form ?>
</div>