<?php

/**
 * @file
 * View template for the license edit form.
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
$id = html::initial_value($values, 'licence:id');
?>
<p>This page allows you to specify the details of a licence that can be applied to records.</p>
<form id="entry_form" action="<?php echo url::site() . 'licence/save'; ?>" method="post">
  <?php echo $metadata; ?>
  <fieldset>
    <input type="hidden" name="licence:id" value="<?php echo $id ?>" />
    <legend>Licence details</legend>
    <?php
    echo data_entry_helper::text_input([
      'label' => 'Title',
      'fieldname' => 'licence:title',
      'default' => $values['licence:title'] ?? NULL,
      'helpText' => 'The main label used for this licence.',
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Code',
      'fieldname' => 'licence:code',
      'default' => $values['licence:code'] ?? NULL,
      'helpText' => 'The abbreviation or code used for this licence.',
      'validation' => ['required'],
    ]);
    echo data_entry_helper::textarea([
      'label' => 'Description',
      'fieldname' => 'licence:description',
      'default' => html::initial_value($values, 'licence:description'),
      'helpText' => 'A description of this licence.',
    ]);
    echo data_entry_helper::text_input([
      'label' => 'URL (readable)',
      'fieldname' => 'licence:url_readable',
      'default' => $values['licence:url_readable'] ?? NULL,
      'helpText' => 'Link to the online licence page in plain rather than legal language if available.',
      'validation' => ['url'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'URL (legal)',
      'fieldname' => 'licence:url_legal',
      'default' => $values['licence:url_legal'] ?? NULL,
      'helpText' => 'Link to the online licence page in legal rather than plain language if available.',
      'validation' => ['url'],
    ]);
    echo data_entry_helper::checkbox([
      'label' => 'Open licence',
      'fieldname' => 'licence:open',
      'default' => html::initial_value($values, 'licence:open'),
      'helpText' => 'Tick this box if the licence is considered open, having few or no restrictions on use of the data.',
    ]);
    ?>
  </fieldset>
  <?php
  echo html::form_buttons($id != NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
