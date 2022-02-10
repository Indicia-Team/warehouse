<?php

/**
 * @file
 * View template for the person edit form.
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

require_once 'application/views/multi_value_data_editing_support.php';
warehouse::loadHelpers(['data_entry_helper']);
$id = html::initial_value($values, 'person:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<p>This page allows you to specify a persons details.</p>
<form id="entry_form" action="<?php echo url::site() . 'person/save'; ?>" method="post">
  <fieldset>
    <legend>Person's details<?php echo $metadata; ?></legend>
    <?php
    if (isset($values['return_url'])) {
      echo $values['return_url'];
    }
    ?>
    <input type="hidden" name="person:id" value="<?php echo $id; ?>" />
    <?php
    echo data_entry_helper::select([
      'label' => 'Title',
      'fieldname' => 'person:title_id',
      'table' => 'title',
      'valueField' => 'id',
      'captionField' => 'title',
      'extraParams' => $readAuth + ['orderby' => 'title'],
      'default' => html::initial_value($values, 'person:title_id'),
      'blankText' => '<Please select>',
      'caching' => FALSE,
    ]);
    echo data_entry_helper::text_input([
      'label' => 'First name',
      'fieldname' => 'person:first_name',
      'default' => html::initial_value($values, 'person:first_name'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Surname',
      'fieldname' => 'person:surname',
      'default' => html::initial_value($values, 'person:surname'),
      'validation' => ['required'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Initials',
      'fieldname' => 'person:initials',
      'default' => html::initial_value($values, 'person:initials'),
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Email address',
      'fieldname' => 'person:email_address',
      'default' => html::initial_value($values, 'person:email_address'),
      'validation' => ['email'],
    ]);
    ?>
  </fieldset>
  <?php
  /**
   * Handle single value attributes
   * 
   * Draw single value attributes to the screen when called
   */
  function handle_single_value_attributes($attr, $values) {
    $name = "psnAttr:$attr[person_attribute_id]";
    // If this is an existing attribute, tag it with the attribute value
    // record id so we can re-save it.
    if ($attr['id']) {
      $name .= ":$attr[id]";
    }
    switch ($attr['data_type']) {
      case 'D':
        echo data_entry_helper::date_picker([
          'label' => $attr['caption'],
          'fieldname' => $name,
          'default' => $attr['value'],
        ]);
        break;

      case 'V':
        echo data_entry_helper::date_picker([
          'label' => $attr['caption'],
          'fieldname' => $name,
          'default' => $attr['value'],
          'allowVagueDates' => TRUE,
        ]);
        break;

      case 'L':
        echo data_entry_helper::select([
          'label' => $attr['caption'],
          'fieldname' => $name,
          'default' => $attr['raw_value'],
          'lookupValues' => $values["terms_$attr[termlist_id]"],
          'blankText' => '<Please select>',
        ]);
        break;

      case 'B':
        echo data_entry_helper::checkbox([
          'label' => $attr['caption'],
          'fieldname' => $name,
          'default' => $attr['value'],
        ]);
        break;

      default:
        echo data_entry_helper::text_input([
          'label' => $attr['caption'],
          'fieldname' => $name,
          'default' => htmlspecialchars($attr['value']),
        ]);
    }
  }
  ?>
  <fieldset>
    <legend>Additional info</legend>
    <?php
    echo data_entry_helper::textarea([
      'label' => 'Address',
      'fieldname' => 'person:address',
      'default' => html::initial_value($values, 'person:address'),
    ]);
    echo data_entry_helper::text_input([
      'label' => 'Personal website',
      'fieldname' => 'person:website_url',
      'default' => html::initial_value($values, 'person:website_url'),
      'validation' => ['url'],
    ]);
    echo data_entry_helper::text_input([
      'label' => 'External key',
      'fieldname' => 'person:external_key',
      'default' => html::initial_value($values, 'person:external_key'),
      'helpText' => 'Key/unique identifier of person if sourced from another system.',
    ]);
    ?>
  </fieldset>
  <?php
  if (array_key_exists('attributes', $values) && count($values['attributes']) > 0) : ?>
  <fieldset>
  <legend>Custom attributes</legend>
    <ol>
      <?php
      // The $values['attributes'] array has multi-value attributes on separate rows, so organise these into sub array
      $attrsWithMulti = organise_values_attribute_array('person_attribute', $values['attributes']);
      // Cycle through the attributes and drawn them to the screen
      foreach ($attrsWithMulti as $sampleAttributeId => $wholeAttrToDraw) {
        // Multi-attributes are in a sub array, so the caption is not present at the first level so we can detect this
        if (!empty($wholeAttrToDraw['caption'])) {
          handle_single_value_attributes($wholeAttrToDraw, $values);
        } else {
          handle_multi_value_attributes('psnAttr', $sampleAttributeId, $wholeAttrToDraw, $values);
        }
      }
      ?>
    </ol>
  </fieldset>
  <?php
  endif;
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
