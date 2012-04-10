<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

?>
<form class="cmxform" action="<?php echo url::site().'taxon_relation_type/save' ?>" method="post">
<?php echo $metadata; ?>
<fieldset>
<legend>Taxon Relation Details</legend>
<ol>
<li>
<input type="hidden" name="taxon_relation_type:id" value="<?php echo html::initial_value($values, 'taxon_relation_type:id'); ?>" />
<label for="caption">Caption</label>
<input id="caption" name="taxon_relation_type:caption" value="<?php echo html::initial_value($values, 'taxon_relation_type:caption'); ?>" />
<?php echo html::error_message($model->getError('taxon_relation_type:caption')); ?>
</li>
<li>
<label for="forward_term">Forward Term</label>
<input id="forward_term" name="taxon_relation_type:forward_term" value="<?php echo html::initial_value($values, 'taxon_relation_type:forward_term'); ?>" />
<?php echo html::error_message($model->getError('taxon_relation_type:forward_term')); ?>
</li>
<li>
<label for="reverse_term">Reverse Term</label>
<input id="reverse_term" name="taxon_relation_type:reverse_term" value="<?php echo html::initial_value($values, 'taxon_relation_type:reverse_term'); ?>" />
<?php echo html::error_message($model->getError('taxon_relation_type:reverse_term')); ?>
</li>
<li>
<label for="relation_code">Relation Code</label>
<select id="data_type" name="taxon_relation_type:relation_code">
		<option value=''>&lt;Please Select&gt;</option>
		<?php
		$optionlist = array(
		 '0' => 'Mutually Exclusive'
		,'1' => 'At Least Partial Overlap'
		,'3' => 'Same or part of'
		,'7' => 'The same as'
		);
		foreach ($optionlist as $key => $option) {
		  echo '	<option value="'.$key.'" ';
		  if ($key==html::initial_value($values, 'taxon_relation_type:relation_code'))
		  echo 'selected="selected" ';
		  echo '>'.$option.'</option>';
		}
		?>
	</select> <?php echo html::error_message($model->getError('taxon_relation_type:relation_code')); ?>
	</li>
</ol>
</fieldset>
<?php 
echo html::form_buttons(html::initial_value($values, 'taxon_relation_type:id')!=null)
?>
</form>

