<?php

/**
 * @file
 * View template for the list of UKSI operations.
 *
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

warehouse::loadHelpers(['data_entry_helper']);
echo $grid;

$returnPath = url::site() . "uksi_operation/importer";
?>
<form enctype="multipart/form-data" class="form-inline" method="post" action="<?php echo $returnPath; ?>">
  <label for="csv_upload">Upload a CSV file into this list:</label>
  <input type="file" name="csv_upload" id="csv_upload" class="form-control" />
  <input type="submit" value="Upload file" class="btn btn-default" />
</form>

<hr />

<h3>UKSI Master Reconciliation</h3>

<p>
  Compare the current taxon list with the UKSI master table and log any differences.
</p>


<form enctype="multipart/form-data" class="form-inline" method="post" action="<?php echo url::site('uksi_operation/master_importer'); ?>">
  <label for="csv_masterupload">Upload a master CSV file to check against :</label>
  <input type="file" name="csv_masterupload" id="csv_masterupload" class="form-control" />
  <input type="submit" value="Upload file" class="btn btn-default" />
</form>

<a
  class="btn btn-warning"
  href="<?php echo url::site('uksi_operation/reconcile_preview'); ?>"
  onclick="return confirm('Run reconciliation in preview mode? No data will be updated.');">
  Preview Reconciliation
</a>

<a
  class="btn btn-danger"
  href="<?php echo url::site('uksi_operation/reconcile_apply'); ?>"
  onclick="return confirm('Run reconciliation and APPLY updates to the taxonomy?');">
  Apply Reconciliation
</a>