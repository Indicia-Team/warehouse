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

if (!empty($parent_list_id)) : ?>
<script type="text/javascript">
/*<![CDATA[*/
add_parent_term = function() {
  // ask the warehouse to copy the term from the parent list to the child list
  $.post('<?php echo url::site('termlists_term/add_parent_term'); ?>', {
      termlist_id: <?php echo $termlist_id; ?>,
      termlists_term_id: $('#add-from-parent').val()
    }, function(data, textStatus) {
      if (isNaN(parseInt(data)))
        // if text returned, it is a message to display
        alert(data);
      else
        // if OK, it returns the new record ID. Add it to the grid, using the global var created
        // when the grid was created.
        grid_termlists_term.addRecords('id', data);
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
    'label'=>'Add term',
    'fieldname'=>'add-from-parent',
    'helpText'=>'Search for terms in the parent list to quickly add them into this list.',
    'table' => 'termlists_term',
    'captionField' => 'term',
    'valueField' => 'id',
    'extraParams' => $readAuth + array('termlist_id'=>$parent_list_id),
    'afterControl' => '<input type="button" value="Add" onclick="add_parent_term();" />'
  ));
  echo '</div>';
endif;

echo $grid;
?>
<br/>
<form action="<?php echo url::site().'termlists_term/create/'.$termlist_id; ?>" method="post">
<?php if (isset($parent_id)): ?>
<input type="hidden" value="<?php echo $parent_id; ?>" name="termlists_term:parent_id"/>
<?php endif; ?>
<input type="submit" value="New term" class="ui-corner-all ui-state-default button" />
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
</div>
