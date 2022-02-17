<?php

/**
 * @file
 * View template for the upload a spreadsheet file forms added below lists of data.
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

$returnPath = url::site() . "$controllerpath/importer/$returnPage";
?>
<form enctype="multipart/form-data" class="form-inline" method="post" action="<?php echo $returnPath; ?>">
  <?php
  if ($staticFields != NULL) {
    foreach ($staticFields as $a => $b) {
      print form::hidden($a, $b);
    }
  }
  ?>
  <label for="csv_upload">Upload a CSV or Excel file into this list:</label>
  <input type="file" name="csv_upload" id="csv_upload" class="form-control" />
  <input type="submit" value="Upload file" class="btn btn-default" />
  <p class="helpText">To support the full range of special characters, CSV
    files must be UTF-8 encoded with BOM. For a limited range of special 
    characters, ISO 8859-1 is acceptable. ASCII will work if you have no 
    accented characters.
  </p>
</form>
