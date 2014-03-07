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
$id = html::initial_value($values, 'user_identifier:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<p>This page allows you to specify the details of an identifier for a user, such as a Twitter or Facebook account. 
Identifiers are used to ensure that Indicia recognises an individual across all websites sharing the warehouse.</p>
<form class="cmxform" action="<?php echo url::site().'user_identifier/save'; ?>" method="post" enctype="multipart/form-data">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="user_identifier:id" value="<?php echo $id ?>" />
<input type="hidden" name="user_identifier:user_id" value="<?php echo html::initial_value($values, 'user_identifier:user_id'); ?>" />
<legend>Identifier</legend>
<?php
echo data_entry_helper::text_input(array(
  'label' => 'Identifier',
  'fieldname' => 'user_identifier:identifier',
  'default' => html::initial_value($values, 'user_identifier:identifier'),
  'helpText' => 'The externally provided identifier, e.g. a Twitter account ID or OpenID URL.'
));
echo data_entry_helper::select(array(
  'label' => 'Identifier Type',
  'fieldname' => 'user_identifier:type_id',
  'default' => html::initial_value($values, 'user_identifier:type_id'),
  'lookupValues' => $other_data['identifier_types'],
  'blankText' => '<Please select>',
  'helpText' => 'Select the type of identifier, i.e. the external system that the identifier is registered with.'
));
?>
</fieldset>
<?php 
echo html::form_buttons($id!=null, false, false);
data_entry_helper::link_default_stylesheet();
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
echo data_entry_helper::dump_javascript();
?>
</form>