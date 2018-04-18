<?php

/**
 * @file
 * View template for the metadata panels for each data item edit form.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */
?>
<script type='text/javascript'>
$(document).ready(function(){
  $('div#metadata').hide();
  $('#metadata_toggle span').show();
  $('#metadata_toggle span').click(function() {
    $('#metadata_toggle span').html($('div#metadata:visible').length > 0 ? 'Show metadata' : 'Hide metadata')
    $('div#metadata').toggle();
  });
});
</script>
<div id="metadata_toggle" class="pull-right"><span class="btn btn-info">Show metadata</span></div>
<div id="metadata" class="panel panel-info">
  <div class="panel-heading">Metadata</div>
  <input type="hidden" id="created_by_id" name="created_by_id" value="<?php echo html::specialchars($model->created_by_id); ?>" />
  <?php if (isset($model->updated_on)) : ?>
  <input type="hidden" name="updated_by_id" id="updated_by_id" value="<?php echo html::specialchars($model->updated_by_id); ?>" />
  <?php endif; ?>
  <ul class="list-group">
    <li class="list-group-item">
      Record ID is <?php echo ($model->id) ? $model->id : '<new record>'; ?>
    </li>
    <li class="list-group-item">
    Created on <?php echo html::specialchars($model->created_on); ?>
    by <?php echo (($model->created_by_id != NULL) ? (html::specialchars($model->created_by->person->surname)) : ''); ?>.
    </li>
    <?php if (isset($model->updated_on)) : ?>
    <li class="list-group-item">
      Updated on <?php echo html::specialchars($model->updated_on); ?>
      by <?php echo (($model->updated_by_id != NULL) ? (html::specialchars($model->updated_by->person->surname)) : ''); ?>.
    </li>
    <?php endif; ?>
  </ul>
</div>
