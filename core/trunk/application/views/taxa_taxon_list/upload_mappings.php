<?php echo form::open($controllerpath.'/upload', array('class'=>'cmxform')); ?>
<p>Please map each column in the CSV file you are uploading to the associated attribute in the destination list.</p>
<br />
<table><thead><th>Column in CSV File</th><th>Maps to attribute</th></thead>
<tbody>
<?php foreach ($columns as $col): ?>
	<tr>
		<td><?php echo $col; ?></td>
		<td><select id="<?php echo $col; ?>" name="<?php echo $col; ?>">
		<option>Please Select</option>
		<?php foreach ($mappings as $map => $name) {
			echo "<option value='".$map."'>".$name."</option>";
		} ?>
		</select>
		</td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="Submit" value="Upload Data" />
</form>

