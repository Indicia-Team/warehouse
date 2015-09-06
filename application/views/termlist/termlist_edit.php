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
// apply permissions
$disabled_input=html::initial_value($values, 'metaFields:disabled_input');
$disabled = ($disabled_input==='YES') ? 'disabled="disabled"' : '';

$id = html::initial_value($values, 'termlist:id');
$parent_id = html::initial_value($values, 'termlist:parent_id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');

if ($parent_id != null) : ?>
<h1>Subset of:
<a href="<?php echo url::site(); ?>termlist/edit/<?php echo $parent_id ;?>" >
<?php echo ORM::factory("termlist",$parent_id)->title ?>
</a>
</h1>
<?php endif; ?>
<div id="details">
<form class="cmxform" action="<?php echo url::site().'termlist/save'; ?>" method="post">
<?php echo $metadata ?>
<fieldset>
<legend>List Details</legend>
<input type="hidden" name="termlist:id" value="<?php echo $id; ?>" />
<input type="hidden" name="termlist:parent_id" value="<?php echo $parent_id; ?>" />
<?php
if ($disabled_input==='YES') : ?>
  <p>The termlist is available to all websites so you don't have permission to change it.
  Please contact the warehouse owner to request changes.</p>
<?php
endif;
echo data_entry_helper::text_input(array(
  'label' => 'Titlde',
  'fieldname' => 'termlist:title',
  'default' => html::initial_value($values, 'termlist:title'),
  'validation' => 'required',
  'disabled' => $disabled,
  ));
echo data_entry_helper::textarea(array(
  'label' => 'Description',
  'fieldname' => 'termlist:description',
  'default' => html::initial_value($values, 'termlist:description'),
  'disabled' => $disabled,
));
// prevent changing of owner if this is a child termlist
if ($parent_id != null && array_key_exists('parent_website_id', $values) && $values['parent_website_id'] !== null) {
  $disabled = 'disabled="disabled';
  $website_id = $values['parent_website_id'];
} else {
  $website_id = html::initial_value($values, 'termlist:website_id');
}
$options = array();
if (!is_null($this->auth_filter))
  $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->orderby('title','asc')->find_all();
else
  $websites = ORM::factory('website')->orderby('title','asc')->find_all();
foreach ($websites as $website) {
  $options[$website->id] = $website->title;
}
echo data_entry_helper::select(array(
  'label' => 'Owned by',
  'fieldname' => "termlist:website_id",
  'default' => $website_id,
  'disabled' => $disabled,
  'blankText' => '<Warehouse>',
  'lookupValues' => $options
));
?>
</fieldset>
<?php
echo html::form_buttons(html::initial_value($values, 'termlist:id')!=null && html::initial_value($values, 'termlist:id')!='', false, false);
echo html::error_message($model->getError('deleted'));
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('termlist-edit');
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>
</div>