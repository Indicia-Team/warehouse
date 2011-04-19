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

$id = html::initial_value($values, 'occurrence_comment:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<p>This page allows you to specify the details of an occurrence comment.</p>
<form class="cmxform" action="<?php echo url::site().'occurrence_comment/save'; ?>" method="post" enctype="multipart/form-data">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="occurrence_comment:id" value="<?php echo $id ?>" />
<input type="hidden" name="occurrence_comment:occurrence_id" value="<?php echo html::initial_value($values, 'occurrence_comment:occurrence_id'); ?>" />
<legend>Occurrence Comment</legend>
<?php
echo data_entry_helper::textarea(array(
  'label' => 'Comment',
  'fieldname' => 'occurrence_comment:comment',
  'default' => html::initial_value($values, 'occurrence_comment:comment')
)); 
?>
</fieldset>
<?php echo html::form_buttons($id!=null, false, false); ?>
</form>