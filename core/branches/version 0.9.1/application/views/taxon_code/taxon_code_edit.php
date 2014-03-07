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

$id = html::initial_value($values, 'taxon_code:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<p>This page allows you to specify the details of a taxon code. Different types of code can be made available by adding terms 
to the taxon codes termlist.</p>
<form class="cmxform" action="<?php echo url::site().'taxon_code/save'; ?>" method="post">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="taxon_code:id" value="<?php echo $id ?>" />
<input type="hidden" name="taxon_code:taxon_meaning_id" value="<?php echo html::initial_value($values, 'taxon_code:taxon_meaning_id'); ?>" />
<input type="hidden" name="taxa_taxon_list:id" value="<?php echo html::initial_value($values, 'taxa_taxon_list:id'); ?>" />
<legend>Code details</legend>
<?php
echo data_entry_helper::text_input(array(
  'label' => 'Code',
  'fieldname' => 'taxon_code:code',
  'default' => html::initial_value($values, 'taxon_code:code'),
  'helpText' => 'The code or identifier to associate with the taxon',
  'validation' => array('required')
));
echo data_entry_helper::select(array(
  'label' => 'Code Type',
  'fieldname' => 'taxon_code:code_type_id',
  'default' => html::initial_value($values, 'taxon_code:code_type_id'),
  'lookupValues' => $other_data['code_type_terms'],
  'blankText' => '<Please select>',
  'validation' => array('required'),
  'helpText' => 'Select the type of code you are specifying for the taxon'
));
?>
</fieldset>
<?php 
echo html::form_buttons($id!=null, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('code-edit');
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>