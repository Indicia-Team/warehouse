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

$id = html::initial_value($values, 'occurrence_image:id');
?>
<p>This page allows you to specify the details of an occurrence image.</p>
<form class="cmxform" action="<?php echo url::site().'occurrence_image/save'; ?>" method="post" enctype="multipart/form-data">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="occurrence_image:id" value="<?php echo $id ?>" />
<input type="hidden" name="occurrence_image:occurrence_id" value="<?php echo html::initial_value($values, 'occurrence_image:occurrence_id'); ?>" />
<input type="hidden" name="occurrence_image:path" value="<?php echo html::initial_value($values, 'occurrence_image:path'); ?>" />
<legend>Image details</legend>
<ol>
<?php 
if ($id) : ?>
<li>
<label for="image">Image:</label>
<?php echo html::sized_image(html::initial_value($values, 'occurrence_image:path'), 'med'); ?>
</li>
<?php endif; ?>
<li>
<label for="file">Upload file:</label>
<input type="file" name="image_upload" accept="png|jpg|gif|jpeg" />
<?php echo html::error_message($model->getError('occurrence_image:path')); ?>
</li>
<li>
<label for="name">Caption</label>
<input id="caption" name="occurrence_image:caption" value="<?php echo html::initial_value($values, 'occurrence_image:caption'); ?>" />
<?php echo html::error_message($model->getError('occurrence_image:caption')); ?>
</li>
</ol>
</fieldset>
<?php echo html::form_buttons($id!=null, false, false); ?>
</form>