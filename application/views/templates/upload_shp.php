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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

if (function_exists('zip_open')) {
  echo "<form action=\"$controllerpath/upload_shp\" method=\"post\" class=\"form-inline\" enctype=\"multipart/form-data\">";
  if ($staticFields != NULL) {
    foreach ($staticFields as $a => $b) {
      print form::hidden($a, $b);
    }
  }
?>
  <label for="zip_upload">Upload a Zipped up SHP fileset into this list:</label>
  <input type="file" name="zip_upload" id="zip_upload" class="form-control" />
  <input type="submit" class="btn btn-default" value="Upload ZIP File" />
</form>
<?php
}
else {
  echo <<<HTML
<div class="alert alert-info">
  PHP Zip Library is not available on this server. SHP file upload disabled.
</div>
HTML;
}
