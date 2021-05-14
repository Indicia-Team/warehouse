<?php

/**
 * @file
 * View template for the user identifier edit form.
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
$id = html::initial_value($values, 'user_identifier:id');
?>
<p>This page allows you to specify the details of an identifier for a user, such as a Twitter or Facebook account.
Identifiers are used to ensure that Indicia recognises an individual across all websites sharing the warehouse.</p>
<form id="entry_form" action="<?php echo url::site() . 'user_identifier/save'; ?>" method="post">
  <fieldset>
    <input type="hidden" name="user_identifier:id" value="<?php echo $id ?>" />
    <input type="hidden" name="user_identifier:user_id" value="<?php echo html::initial_value($values, 'user_identifier:user_id'); ?>" />
    <legend>Identifier<?php echo $metadata; ?></legend>
    <?php
    echo data_entry_helper::text_input(array(
      'label' => 'Identifier',
      'fieldname' => 'user_identifier:identifier',
      'default' => html::initial_value($values, 'user_identifier:identifier'),
      'helpText' => 'The externally provided identifier, e.g. a Twitter account ID or OpenID URL.',
      'validation' => ['required'],
    ));
    echo data_entry_helper::select(array(
      'label' => 'Identifier Type',
      'fieldname' => 'user_identifier:type_id',
      'default' => html::initial_value($values, 'user_identifier:type_id'),
      'lookupValues' => $other_data['identifier_types'],
      'blankText' => '<Please select>',
      'helpText' => 'Select the type of identifier, i.e. the external system that the identifier is registered with.',
      'validation' => ['required'],
    ));
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id != NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
