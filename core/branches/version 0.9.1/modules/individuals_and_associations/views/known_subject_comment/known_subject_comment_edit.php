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
 * @package	Individuals and associations
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

$id = html::initial_value($values, 'known_subject_comment:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<p>This page allows you to specify the details of a known subject comment.</p>
<form class="cmxform" action="<?php echo url::site().'known_subject_comment/save'; ?>" method="post" enctype="multipart/form-data">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="known_subject_comment:id" value="<?php echo $id ?>" />
<input type="hidden" name="known_subject_comment:known_subject_id" value="<?php echo html::initial_value($values, 'known_subject_comment:known_subject_id'); ?>" />
<legend>Known Subject Comment</legend>
<?php
echo data_entry_helper::textarea(array(
  'label' => 'Comment',
  'fieldname' => 'known_subject_comment:comment',
  'default' => html::initial_value($values, 'known_subject_comment:comment')
)); 
?>
</fieldset>
<?php echo html::form_buttons($id!=null, false, false); ?>
</form>