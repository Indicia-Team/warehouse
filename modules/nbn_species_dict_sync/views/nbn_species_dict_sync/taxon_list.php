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
 * @package	NBN Species Dict Sync
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * View for the tab which appears on the taxon groups list for sync with
 * reporting categories.
 */
?>
<script type='text/javascript'>

handle_response = function(data) {
  data = eval("(" + data + ")");
  if (typeof data.complete==="undefined") {
    alert('A problem occurred with the synchronisation and an invalid response was received from the server.' + data);
  } else {
    if (typeof data.errors !== "undefined") {
      $('#errors').html(data.errors.join("<br/>"));
    }
    if (data.complete) {
      $('#progress-text').html('Synchronisation complete');
      $('#progress-bar').progressbar ('option', 'value', 100);
      alert('Synchronisation complete');
    } else {
      $('#progress-text').html(data.statusText);
      $('#progress-bar').progressbar ('option', 'value', data.progress);
      var url="<?php echo url::site(); ?>nbn_species_dict_sync/taxon_list_sync/<?php echo $taxon_list_id; ?>?task_id=" + data.task_id;  
      $.ajax({
        url: url,
        success: handle_response
      });
    }
  }
}

$('#submit-sync').click(function(e) {
  $('#progress-bar').progressbar ({value: 0});
  e.preventDefault();
  var url="<?php echo url::site(); ?>nbn_species_dict_sync/taxon_list_sync/<?php echo $taxon_list_id; ?>";
  url += '?mode=' + $('#mode').val();
  $.ajax({
    url: url,
    success: handle_response
  });
});
</script>
<p>This facility allows you to import the names from the NBN Species Dictionary web services into this list. Because of the current nature
of the web services this process will be very slow, though it is safe to cancel at any time by refreshing the web page. </p>
<?php if (in_array(MODPATH.'taxon_groups_taxon_lists', kohana::config('config.modules'))) : ?>
<p>The names imported will be only those which belong to the reporting categories which correspond to the taxon groups associated with this
checklist on the Taxon Groups tab.</p>
<?php endif; ?>
<p>Before using this facility you should import the <a href="<?php echo url::site(); ?>taxon_designation?tab=NBN_Sync">taxon designations</a>
and <a href="<?php echo url::site(); ?>taxon_group?tab=NBN_Sync">taxon groups</a>.</p>
<form action="<?php echo url::site(); ?>nbn_species_dict_sync/taxon_list_sync/<?php echo $taxon_list_id; ?>" method="post" class="iform">
<label for="mode" class="auto">Taxon selection:</label>
<select name="mode" id="mode">
<option value="all">Update all taxa</option>
<option value="new">Fetch new taxa only</option>
<option value="existing">Update only existing taxa</option>
<option value="designations">Update only designations for existing taxa</option>
</select>
<input type="Submit" value="Synchronise" id="submit-sync" />
</form>
<div id="progress" class="ui-widget ui-widget-content ui-corner-all">
<div id="progress-bar" style="width: 100%; height: 15px;"></div>
<div id="progress-text"></div>
<div id="errors"></div>
</div>