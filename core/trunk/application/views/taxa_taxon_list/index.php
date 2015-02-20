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

if (isset($parent_list_id)) : ?>
<script type="text/javascript">
/*<![CDATA[*/
jQuery(document).ready(function($) {
  $('#ctrl-wrap-search_method').hide();

});
add_parent_taxon = function() {
  // ask the warehouse to copy the taxon from the parent list to the child list
  $.post('<?php echo url::site('taxa_taxon_list/add_parent_taxon'); ?>', {
      taxon_list_id: <?php echo $taxon_list_id; ?>,
      taxa_taxon_list_id: $('#add-from-parent').val()
    }, function(data) {
      if (isNaN(parseInt(data))) {
        // if text returned, it is a message to display
        alert(data);
      } else { 
        // if OK, it returns the new record ID. Add it to the grid, using the global var created
        // when the grid was created.
        console.log(indiciaData);
        indiciaData.reports.taxa_taxon_list.grid_taxa_taxon_list.addRecords('id', data);
      }
    }
  );
};
convert_to_list_mode = function() {
  $('#add-from-parent\\:taxon').hide();
  $('#pasted_taxa').show();
  $('#ctrl-wrap-add-from-parent').find(':button').hide();
  $('#add-list').show();
  $('#ctrl-wrap-search_method').show();
  $('#ctrl-wrap-add-from-parent .helpText').html(
      'Paste a list of taxon names or external keys into the box above then click Add to add them all');
};
/*]]>*/
</script>
<form action="<?php echo url::site().'taxa_taxon_list/paste_childlist/'.$taxon_list_id; ?>" method="post">
  <div>
  <?php
    require_once(DOCROOT.'client_helpers/data_entry_helper.php');
    $readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
    echo '';
    echo data_entry_helper::select(array(
      'label' => 'Search for taxa using',
      'fieldname' => 'search_method',
      'helpText' => 'When adding a list of taxa, which field in the taxon data will your input values be looked up against?',
      'lookupValues' => array(
          'taxon' => 'Species or other taxon name',
          'external_key' => 'External key'
      )
    ));
    echo data_entry_helper::species_autocomplete(array(
      'label'=>'Add species',
      'fieldname'=>'add-from-parent',
      'helpText'=>'Search for taxa in the parent list to quickly add them into this list.',
      'cacheLookup' => TRUE,
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
      'extraParams' => $readAuth + array('taxon_list_id'=>$parent_list_id),
      'afterControl' => '<textarea style="display: none" id="pasted_taxa" name="pasted_taxa" rows="10" cols="40"/>' .
          '<input type="button" value="Add" onclick="add_parent_taxon();" '.
          'title="Click this button to add the taxa searched for in the Add species box to your child list"/>' .
          '<input type="button" value="Add many..." onclick="convert_to_list_mode();" ' .
          'title="Click this button to be able to paste in a list of taxa to add"/>' .
          '<input type="submit" style="display: none" value="Add list" id="add-list" />'
    ));
  ?>
  </div>
</form>
<?php
endif;
echo $grid;
?>
<br/>
<form action="<?php echo url::site().'taxa_taxon_list/create/'.$taxon_list_id; ?>" method="post">
<?php if (isset($parent_id)): ?>
<input type="hidden" value="<?php echo $parent_id; ?>" name="taxa_taxon_list:parent_id"/>
<?php endif; ?>
<input type="submit" value="New taxon" class="ui-corner-all ui-state-default button" />
</form>
<br />
<?php
echo $upload_csv_form;
if (isset($parent_list_id)) {
  if (request::is_ajax()) {
    // When viewing as an AJAX loaded tab, don't reload jQuery as it is already on the page.
    data_entry_helper::$dumped_resources[] = 'jquery';
  }
  data_entry_helper::link_default_stylesheet();
  echo data_entry_helper::dump_javascript(true);
}
?>
