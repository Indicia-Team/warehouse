<?php

/**
 * @file
 * View template for the sample comment edit form.
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

warehouse::loadHelpers(['data_entry_helper']);
$id = html::initial_value($values, 'sample_comment:id');
?>
<p>This page allows you to specify the details of a sample comment.</p>
<form id="entry_form" action="<?php echo url::site() . 'sample_comment/save'; ?>" method="post" enctype="multipart/form-data">
  <fieldset>
    <input type="hidden" name="sample_comment:id" value="<?php echo $id ?>" />
    <input type="hidden" name="sample_comment:sample_id" value="<?php echo html::initial_value($values, 'sample_comment:sample_id'); ?>" />
    <legend>Sample Comment<?php echo $metadata; ?></legend>
    <?php
    echo data_entry_helper::textarea(array(
      'label' => 'Comment',
      'fieldname' => 'sample_comment:comment',
      'default' => html::initial_value($values, 'sample_comment:comment'),
      'validation' => ['required'],
    ));
    ?>
  </fieldset>
  <?php echo html::form_buttons($id !== NULL, FALSE, FALSE); ?>
</form>
