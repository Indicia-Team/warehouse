<?php echo form::open($controllerpath.'/upload/'.$returnPage, array('class'=>'cmxform')); ?>
<p>Please map each column in the CSV file you are uploading to the associated attribute in the destination list.</p>
<br />
<table><thead><th>Column in CSV File</th><th>Maps to attribute</th></thead>
<tbody>
<?php foreach ($columns as $col): ?>
	<tr>
		<td><?php echo $col; ?></td>
		<td><?php echo html::dropdown_model_fields($model, $col, '<please select>'); ?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="Submit" value="Upload Data" />
<?php 
// We stick these at the bottom so that all the other things will be parsed first
foreach ($this->input->post() as $a => $b) {
	print form::hidden($a, $b);
}
?>
</form>

