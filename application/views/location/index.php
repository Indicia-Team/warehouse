<div class="location">
<?php echo $table ?>
<br/>
<form action="<?php echo url::site().'location/create'; ?>" method="post">
<input type="submit" value="New location" />
</form>
<br />
<?php echo $upload_csv_form ?>
</div>
