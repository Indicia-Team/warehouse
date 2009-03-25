<?php defined('SYSPATH') or die('No direct script access.');

class html extends html_Core {

 	/* Outputs an error message in a span, but only if there is something to output */
	public static function error_message($message)
	{
		if ($message) echo '<span class="form_error">'.$message.'</span>';
	}

	/* Outputs a list of columns as an html drop down, loading the columns from a model that are
	 * available to import data into (excluding the id and metadata)
	 */
	 public static function dropdown_model_fields($model, $name, $default)
	 {
	 	echo '<select id="'.$name.'" name="'.$name.'">';
	 	$skipped = array('id', 'created_by_id', 'created_on', 'updated_by_id', 'updated_on');
	 	if ($default) {
	 		echo '<option>'.html::specialchars($default).'</option>';
	 	}
		foreach ($model->getSubmittableFields(true) as $name => $dbtype) {
			if (!in_array($name, $skipped)) {
				echo '<option value="'.$name.'">';
				if (substr($name, 0, 3)=='fk_') {
					echo substr($name,3);
					// if the foreign key name does not match its table, also output the table name
					if (array_key_exists(substr($name,3), $model->belongs_to)) {
						echo ' ('.$model->belongs_to[substr($name,3)].')';
					}
				} else {
					echo $name;
				}
				echo '</option>';
			}
		}
		echo '</select>';
	 }

}
?>
