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
<script type='text/javascript'>
$(document).ready(function(){
  $('div#metadata').hide();
  $('#metadata_toggle span').show();
  $('#metadata_toggle span').click(function(){
    $('div#metadata').toggle('slow');
  });
});
</script>
<div id='metadata_toggle'><span class="ui-state-default ui-corner-all button">Show/Hide Metadata</span></div>
<div id='metadata'>
<fieldset>
<legend>Metadata</legend>
<input type="hidden" id="created_by_id" name="created_by_id" value="<?php echo html::specialchars($model->created_by_id); ?>" />
<?php if (isset($model->updated_on)) : ?>
<input type="hidden" name="updated_by_id" id="updated_by_id" value="<?php echo html::specialchars($model->updated_by_id); ?>" />
<?php endif; ?>
<ol>
<li>
Record ID is <?php if ($model->id) echo $model->id; else echo '<new record>';?>
</li>
<li>
Created on <?php echo html::specialchars($model->created_on); ?> by <?php echo (($model->created_by_id != null) ? (html::specialchars($model->created_by->person->surname)) : ''); ?>.
</li>
<?php if (isset($model->updated_on)) : ?>
<li>
Updated on <?php echo html::specialchars($model->updated_on); ?> by <?php echo (($model->updated_by_id != null) ? (html::specialchars($model->updated_by->person->surname)) : ''); ?>.
</li>
<?php endif; ?>
</ol>
</fieldset>
</div>
