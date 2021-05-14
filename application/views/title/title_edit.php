<?php

/**
 * @file
 * View template for the title edit form page.
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
$id = html::initial_value($values, 'title:id');
?>
<p>This page allows you to specify the details of a person's title.</p>
<form id="entry_form" action="<?php echo url::site(); ?>title/save" method="post">
  <?php echo $metadata ?>
  <fieldset>
  <legend>Title details</legend>
    <input type="hidden" name="title:id" value="<?php echo $id; ?>" />
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'title:title',
      'default' => html::initial_value($values, 'title:title'),
      'validation' => ['required'],
    ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id != NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
