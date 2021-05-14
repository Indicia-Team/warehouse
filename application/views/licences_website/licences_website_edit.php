<?php

/**
 * @file
 * View template for the licence website edit form.
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
$id = html::initial_value($values, 'licences_website:id');
?>
<form action="<?php echo url::site() . 'licences_website/save' ?>" method="post" id="entry_form">
  <fieldset>
    <input type="hidden" name="licences_website:id" value="<?php echo html::initial_value($values, 'licences_website:id'); ?>" />
    <input type="hidden" name="licences_website:website_id" value="<?php echo html::initial_value($values, 'licences_website:website_id'); ?>" />
    <legend>Licence Details<?php echo $metadata; ?></legend>
    <?php
    echo data_entry_helper::select(array(
      'id' => 'licence-select',
      'label' => 'Lience',
      'helpText' => 'Select the licence to make available on this website',
      'fieldname' => 'licences_website:licence_id',
      'default' => html::initial_value($values, 'licences_website:licence_id'),
      'lookupValues' => $other_data['licences'],
      'blankText' => '<please select>',
      'validation' => ['required'],
    ));
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
