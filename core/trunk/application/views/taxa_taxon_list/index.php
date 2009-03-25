<div class="termlist">
<?php echo $table ?>
<br/>
<form action="<?php echo url::site().'taxa_taxon_list/create/'.$taxon_list_id; ?>" method="post">
<input type="submit" value="New taxon" />
</form>
<br />
<?php echo $upload_csv_form ?>
</div>
