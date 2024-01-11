<?php

/**
 * @file
 * View template for the language edit form.
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
$id = html::initial_value($values, 'language:id');
?>
<form id="entry_form" action="<?php echo url::site() . 'language/save' ?>" method="post">
  <fieldset>
  <legend>Language details <?php echo $metadata; ?></legend>
    <input type="hidden" name="language:id" value="<?php echo $id; ?>" />
    <?php
    echo data_entry_helper::text_input([
      'label' => 'ISO language code',
      'fieldname' => 'language:iso',
      'default' => $values['language:iso'] ?? NULL,
      'helpText' => 'The ISO standard code for this language.',
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Language name',
      'fieldname' => 'language:language',
      'default' => $values['language:language'] ?? NULL,
      'helpText' => 'The display name for this language.',
      'validation' => ['required'],
    ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons(!empty($id), FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
