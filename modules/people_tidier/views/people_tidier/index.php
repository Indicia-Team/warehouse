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
 * @package	People tidier
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
require_once(DOCROOT.'client_helpers/data_entry_helper.php');
$readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
?>
<p>Search for people records in the database that might be the same person as the currently selected person. Use * for wildcards to help when searching:</p>
<div class="ui-helper-clearfix">
<div class="left" style="width: 45%">
<h2>User search</h2>
<?php echo data_entry_helper::autocomplete(array(
  'label' => 'Search',
  'fieldname' => 'person_id',
  'table' => 'person',
  'captionField' => 'caption',
  'valueField' => 'id',
  'extraParams' => $readAuth + array('query'=>urlencode(json_encode(array('notin'=>array('id', $personId)))))
));
data_entry_helper::$javascript .= "$('input#person_id\\:caption').change(function(event) {
  var personId=$('input#person_id').val(), table;
  $('#found-person-id').val(personId);
  jQuery.ajax({ 
    type: 'GET', 
    url: '".url::site()."people_tidier/person_panel/'+personId, 
    data: {}, 
    success: function(table) {
      $('#selected-person').html(table);
      $('#resolution input').attr('disabled','');
    },
  });
});

$('#resolution').submit(function(e){
  if (!confirm('Are you absolutely sure that the 2 person records on this page refer to the same person and that you want to merge them into 1 record?'))
    e.preventDefault();
});\n";
?>
<div id="selected-person" style="margin-left: 160px"></div>
</div>
<div class="right" style="width: 45%">
<h2>Selected user</h2>
<?php echo $currentPersonPanel; ?>
</div>
</div>
<form method="post" id="resolution" action="<?php echo url::site(); ?>people_tidier/merge_people" class="ui-helper-clearfix">
<div class="left" style="width: 45%">
<fieldset>
<p>If you are certain that the 2 people in the database are the same person, then click this button to keep the 
person you've searched for on the left hand side of this page and merge the person record on the right into it.</p>
<input type="hidden" name="found-person-id" id="found-person-id" />
<input type="submit" name="keep-found" value="Keep person on left" disabled="disabled"/>
</fieldset>
</div>
<div class="right" style="width: 45%">
<fieldset>
<p>If you are certain that the 2 people in the database are the same person, then click this button to keep the 
person you'd originally selected on the right hand side of this page and merge the person record on the left into it.</p>
<input type="hidden" name="selected-person-id" id="selected-person-id" value="<?php echo $personId; ?>" />
<input type="submit" name="keep-selected" value="Keep person on right" disabled="disabled"/>
</fieldset>
</div>
</form>
<?php
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
data_entry_helper::$dumped_resources[] = 'fancybox';
data_entry_helper::link_default_stylesheet();
echo data_entry_helper::dump_javascript();
?>