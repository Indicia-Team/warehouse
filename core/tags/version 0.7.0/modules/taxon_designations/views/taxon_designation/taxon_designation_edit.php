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
 * @package	Taxon Designations
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

require_once(DOCROOT.'client_helpers/data_entry_helper.php');
if (isset($_POST))
  data_entry_helper::dump_errors(array('errors'=>$this->model->getAllErrors()));
?>
<form class="iform" action="<?php echo url::site(); ?>taxon_designation/save" method="post">
<fieldset>
<legend>Taxon designation details</legend>
<?php
data_entry_helper::link_default_stylesheet();
if (isset($values['taxon_designation:id'])) : ?>
  <input type="hidden" name="taxon_designation:id" value="<?php echo html::initial_value($values, 'taxon_designation:id'); ?>" />
<?php endif;
echo data_entry_helper::text_input(array(
  'label'=>'Title',
  'fieldname'=>'taxon_designation:title',
  'class'=>'control-width-4',
  'default'=> html::initial_value($values, 'taxon_designation:title')
));
echo data_entry_helper::text_input(array(
  'label'=>'Code',
  'fieldname'=>'taxon_designation:code',
  'class'=>'control-width-4',
  'default'=> html::initial_value($values, 'taxon_designation:code')
));
echo data_entry_helper::textarea(array(
  'label'=>'Description',
  'fieldname'=>'taxon_designation:description',
  'class'=>'control-width-6',
  'default'=>html::initial_value($values, 'taxon_designation:description')
));
echo data_entry_helper::select(array(
  'label' => 'Category',
  'fieldname' => 'taxon_designation:category_id',
  'lookupValues' => $other_data['category_terms'],
  'default'=>html::initial_value($values, 'taxon_designation:category_id')
));
echo $metadata;
echo html::form_buttons(html::initial_value($values, 'taxon_designation:id')!=null, false, false);
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>