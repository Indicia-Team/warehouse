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

?>
<script type="text/javascript">
/*<![CDATA[*/
$('.grid-action-delete').click(function(a) {
  // perform the deletion
  $.ajax({
    url: a.target.href,
    success: function() {
      // remove the row
      $(a.target.parentNode.parentNode).remove();
    }
  });
  
  return false;
});

add_taxon_group = function() {
  // ask the warehouse to link the taxon group to this checklist
  $.post('<?php echo url::site('taxon_groups_taxon_list/add_taxon_group'); ?>', {
      taxon_list_id: <?php echo $this->taxon_list_id; ?>,
      taxon_group_id: $('#add-group').val()
    }, function(data, textStatus) {
      if (isNaN(parseInt(data)))
        // if text returned, it is a message to display
        alert(data);
      else
        // if OK, it returns the new record ID. Add it to the grid.
        if (data!==0)
          grid_taxon_groups_taxon_list.addRecords('id', data);
    }
  );
}

/*]]>*/
</script>
<?php
  require_once(DOCROOT.'client_helpers/data_entry_helper.php');
  $readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
  echo '<div class="linear-form">';
  echo data_entry_helper::autocomplete(array(
    'label'=>'Add taxon group',
    'fieldname'=>'add-group',
    'helpText'=>'Search for taxon groups to quickly add them into this list.',
    'table' => 'taxon_group',
    'captionField' => 'title',
    'valueField' => 'id',
    'extraParams' => $readAuth,
    'afterControl' => '<input type="button" value="Add" onclick="add_taxon_group();" />'
  ));
  echo '</div>';
echo $grid;
if (request::is_ajax()) {
  // When viewing as an AJAX loaded tab, don't reload jQuery as it is already on the page.
  data_entry_helper::$dumped_resources[] = 'jquery';
}
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript(true); 
?>