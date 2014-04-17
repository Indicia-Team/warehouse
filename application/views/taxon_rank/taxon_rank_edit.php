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

$id = html::initial_value($values, 'taxon_rank:id');
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
?>
<p>This page allows you to specify the details of a rank in the taxon hierarchy.</p>
<form class="cmxform" id="rank-edit" action="<?php echo url::site().'taxon_rank/save'; ?>" method="post">
<?php echo $metadata; ?>
<fieldset>
<input type="hidden" name="taxon_rank:id" value="<?php echo $id ?>" />
<legend>Taxon rank details</legend>
<?php
echo data_entry_helper::text_input(array(
  'label' => 'Rank',
  'fieldname' => 'taxon_rank:rank',
  'default' => html::initial_value($values, 'taxon_rank:rank'),
  'helpText' => 'The main label used for this taxon rank.',
  'validation' => array('required')
));
echo data_entry_helper::text_input(array(
  'label' => 'Short name',
  'fieldname' => 'taxon_rank:short_name',
  'default' => html::initial_value($values, 'taxon_rank:short_name'),
  'helpText' => 'The shortened label used for this taxon rank.',
  'validation' => array('required')
));
echo data_entry_helper::checkbox(array(
  'label' => 'Italicise taxon',
  'fieldname' => 'taxon_rank:italicise_taxon',
  'default' => html::initial_value($values, 'taxon_rank:italicise_taxon'),
  'helpText' => 'Tick this box if latin species names of this rank are typically shown in italics.'
));
echo data_entry_helper::text_input(array(
  'label' => 'Sort order',
  'fieldname' => 'taxon_rank:sort_order',
  'default' => html::initial_value($values, 'taxon_rank:sort_order'),
  'helpText' => 'The sort order of this taxon rank. Ranks higher up the taxonomic tree have a lower order.',
  'validation' => array('required', 'integer')
));
?>
</fieldset>
<?php 
echo html::form_buttons($id!=null, false, false);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::enable_validation('rank-edit');
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>
</form>