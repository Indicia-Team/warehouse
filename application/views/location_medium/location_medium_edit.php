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

$id = html::initial_value($values, 'location_medium:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
require_once(DOCROOT.'client_helpers/form_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<p>This page allows you to specify the details of an location media file.</p>
<form class="cmxform" action="<?php echo url::site().'location_medium/save'; ?>" method="post" 
      enctype="multipart/form-data" id="location-medium-edit">
<?php echo $metadata; ?>
<fieldset>
<?php 
echo data_entry_helper::hidden_text(array(
  'fieldname'=>'location_medium:id',
  'default'=>$id
));
echo data_entry_helper::hidden_text(array(
  'fieldname'=>'location_medium:location_id',
  'default'=>html::initial_value($values, 'location_medium:location_id')
));
?>
<legend>Media file details</legend>
<?php 
$mediaTypeId=html::initial_value($values, 'location_medium:media_type_id');
$mediaType = $mediaTypeId ? $other_data['media_type_terms'][$mediaTypeId] : 'Image:Local';
if ($mediaType==='Image:Local') {
  if (html::initial_value($values, 'location_medium:path')) {
    echo '<label>Image:</label>';
    echo html::sized_image(html::initial_value($values, 'location_medium:path')) . '</br>';
  }
  echo data_entry_helper::hidden_text(array(
    'fieldname'=>'location_medium:path',
    'default'=>html::initial_value($values, 'location_medium:path')
  ));
  echo data_entry_helper::image_upload(array(
    'label'=>'Upload image file',
    'fieldname'=>'image_upload',
    'default'=>html::initial_value($values, 'location_medium:path')
  ));  
} else {
  echo data_entry_helper::text_input(array(
    'label'=>'Path or URL',
    'fieldname'=>'location_medium:path',
    'default'=>html::initial_value($values, 'location_medium:path'),
    'class' => 'control-width-5'
  ));
}

echo data_entry_helper::text_input(array(
  'label'=>'Caption',
  'fieldname'=>'location_medium:caption',
  'default'=>html::initial_value($values, 'location_medium:caption'),
  'class' => 'control-width-5'
));
if ($mediaTypeId && $mediaType!=='Image:Local') {
  echo data_entry_helper::select(array(
    'label' => 'Media type',
    'fieldname' => 'location_medium:media_type_id',
    'default' => $mediaTypeId,
    'lookupValues' => $other_data['media_type_terms'],
    'blankText' => '<Please select>',
    'class' => 'control-width-5'
  ));
}
?>

</fieldset>
<?php 
echo html::form_buttons($id!=null, false, false); 
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('location-medium-edit');
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>