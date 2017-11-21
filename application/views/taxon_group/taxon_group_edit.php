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
 * @package Core
 * @subpackage Views
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

?><p>This page allows you to specify the details of a taxon group.</p>
<form action="<?php echo url::site() . 'taxon_group/save'; ?>" method="post">
<?php echo $metadata ?>
<fieldset>
  <legend>Taxon Group details</legend>
  <input type="hidden" class="form-control" name="taxon_group:id" value="<?php echo html::initial_value($values, 'taxon_group:id'); ?>" />
  <div class="form-group">
    <label for="title">Title</label>
    <input id="title" class="form-control" name="taxon_group:title" value="<?php echo html::initial_value($values, 'taxon_group:title'); ?>" />
  </div>
  <?php echo html::error_message($model->getError('taxon_group:title')); ?>
  <div class="form-group">
    <label for="title">External key</label>
    <input id="title" class="form-control" name="taxon_group:external_key" value="<?php echo html::initial_value($values, 'taxon_group:external_key'); ?>" />
  </div>
<?php echo html::error_message($model->getError('taxon_group:external_key')); ?>
</fieldset>
<?php
echo html::form_buttons(html::initial_value($values, 'taxon_group:id')!=null);
?>
</form>
