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
$('#submit-sync').click(function(e) {
  e.preventDefault();
  $.ajax({
    url: "<?php echo url::site(); ?>nbn_species_dict_sync/taxon_groups_sync",
    success: function(data) {
      alert(data);
    }
  });
});
</script>
<form action="<?php echo url::site(); ?>nbn_species_dict_sync/taxon_groups_sync" method="post">
<input type="Submit" value="Synchronise" id="submit-sync" />
</form>
