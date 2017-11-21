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

warehouse::loadHelpers(['data_entry_helper']);
$id = html::initial_value($values, 'licences_website:id');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors' => $this->model->getAllErrors()));
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

?>
<form class="cmxform" action="<?php echo url::site() . 'licences_website/save' ?>" method="post" id="licences-websites-edit">
  <?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="licences_website:id" value="<?php echo html::initial_value($values, 'licences_website:id'); ?>" />
<input type="hidden" name="licences_website:website_id" value="<?php echo html::initial_value($values, 'licences_website:website_id'); ?>" />
<legend>Licence Details</legend>
<?php

echo data_entry_helper::select(array(
  'id'=>'licence-select',
  'label' => 'Lience',
  'helpText' => 'Select the licence to make available on this website',
  'fieldname' => 'licences_website:licence_id',
  'default' => html::initial_value($values, 'licences_website:licence_id'),
  'labelClass' => 'control-width-4',
  'table' => 'licence',
  'captionField' => 'title',
  'valueField' => 'id',
  'extraParams' => $readAuth + array('view' => 'gv'),
  'blankText' => '<please select>',
  'class' => 'required',
  'caching' => false
));
?>
</fieldset>
<?php
echo html::form_buttons($id!=null, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('licences-websites-edit');
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>