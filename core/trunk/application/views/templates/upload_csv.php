<?php print form::open($controllerpath.'/upload_mappings/'.$returnPage, array('ENCTYPE'=>'multipart/form-data')); ?>
<?php 
if ($staticFields != null) {
	foreach ($staticFields as $a => $b) {
		print form::hidden($a, $b);
	}
}
?>
<label for="csv_upload">Upload a CSV file into this list:</label>
<input type="file" name="csv_upload" id="csv_upload" size="40" />
<input type="submit" value="Upload CSV File" />
</form>
