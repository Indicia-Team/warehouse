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
<form class="iform" action="<?php echo url::site(); ?>taxa_taxon_designation/save" method="post">
<fieldset>
<legend>Taxon designation details</legend>
<label for="taxon-name">Taxon Name:</label> <span id="taxon_name"><strong><?php echo $other_data['taxon_name']; ?></strong></span><br/>
<input type="hidden" value="<?php echo html::initial_value($values, "taxa_taxon_designation:taxon_id"); ?>"
       name="taxa_taxon_designation:taxon_id"/>
<input type="hidden" value="<?php echo $other_data["taxon_list_id"]; ?>" name="taxon_list_id" />
<?php
data_entry_helper::link_default_stylesheet();
if (isset($values['taxa_taxon_designation:id'])) : ?>
  <input type="hidden" name="taxa_taxon_designation:id" value="<?php echo html::initial_value($values, 'taxa_taxon_designation:id'); ?>" />
<?php endif;
echo data_entry_helper::select(array(
  'label' => 'Designation',
  'fieldname' => 'taxa_taxon_designation:taxon_designation_id',
  'lookupValues' => $other_data['designations'],
  'default'=> html::initial_value($values, 'taxa_taxon_designation:taxon_designation_id')
));
echo data_entry_helper::date_picker(array(
  'label'=>'Start Date',
  'fieldname'=>'taxa_taxon_designation:start_date',
  'default'=> html::initial_value($values, 'taxa_taxon_designation:start_date')
));
echo data_entry_helper::textarea(array(
  'label'=>'Source',
  'fieldname'=>'taxa_taxon_designation:source',
  'class'=>'control-width-6',
  'default'=> html::initial_value($values, 'taxa_taxon_designation:source')
));
echo data_entry_helper::textarea(array(
  'label'=>'Geographic Constraint',
  'fieldname'=>'taxa_taxon_designation:geographical_constraint',
  'class'=>'control-width-6',
  'default'=> html::initial_value($values, 'taxa_taxon_designation:geographical_constraint')
));
echo $metadata;
echo html::form_buttons(html::initial_value($values, 'taxa_taxon_designation:id')!=null, false, false);
echo data_entry_helper::dump_javascript();
?>
</fieldset>
</form>