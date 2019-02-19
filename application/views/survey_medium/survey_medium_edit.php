<?php

/**
 * @file
 * View template for the survey media edit form.
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
$id = html::initial_value($values, 'survey_medium:id');
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<p>This page allows you to specify the details of an survey media file.</p>
<form action="<?php echo url::site() . 'survey_medium/save'; ?>" method="post"
      enctype="multipart/form-data" id="survey-medium-edit">
  <fieldset>
    <legend>Media file details<?php echo $metadata; ?></legend>
    <?php
    echo data_entry_helper::hidden_text([
      'fieldname' => 'survey_medium:id',
      'default' => $id,
    ]);
    echo data_entry_helper::hidden_text([
      'fieldname' => 'survey_medium:survey_id',
      'default' => html::initial_value($values, 'survey_medium:survey_id'),
    ]);
    ?>

    <?php
    $mediaTypeId = html::initial_value($values, 'survey_medium:media_type_id');
    $mediaType = $mediaTypeId ? $other_data['media_type_terms'][$mediaTypeId] : 'Image:Local';
    if ($mediaType === 'Image:Local') {
      if (html::initial_value($values, 'survey_medium:path')) {
        echo '<label>Image:</label>';
        echo html::sized_image(html::initial_value($values, 'survey_medium:path')) . '</br>';
      }
      echo data_entry_helper::hidden_text([
        'fieldname' => 'survey_medium:path',
        'default' => html::initial_value($values, 'survey_medium:path'),
      ]);
      echo data_entry_helper::image_upload([
        'label' => 'Upload image file',
        'fieldname' => 'image_upload',
        'default' => html::initial_value($values, 'survey_medium:path'),
      ]);
    }
    else {
      echo data_entry_helper::text_input([
        'label' => 'Path or URL',
        'fieldname' => 'survey_medium:path',
        'default' => html::initial_value($values, 'survey_medium:path'),
      ]);
    }

    echo data_entry_helper::text_input([
      'label' => 'Caption',
      'fieldname' => 'survey_medium:caption',
      'default' => html::initial_value($values, 'survey_medium:caption'),
    ]);
    echo data_entry_helper::select(array(
      'label' => 'Licence',
      'helpText' => 'Licence which applies to this photo if set.',
      'fieldname' => 'survey_medium:licence_id',
      'default' => html::initial_value($values, 'survey_medium:licence_id'),
      'table' => 'licence',
      'valueField' => 'id',
      'captionField' => 'title',
      'blankText' => '<Please select>',
      'extraParams' => $readAuth,
    ));
    if ($mediaTypeId && $mediaType !== 'Image:Local') {
      echo data_entry_helper::select([
        'label' => 'Media type',
        'fieldname' => 'survey_medium:media_type_id',
        'default' => $mediaTypeId,
        'lookupValues' => $other_data['media_type_terms'],
        'blankText' => '<Please select>',
        'validation' => ['required'],
      ]);
    }
    ?>

  </fieldset>
  <?php
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('survey-medium-edit');
  echo data_entry_helper::dump_javascript();
  ?>
</form>
