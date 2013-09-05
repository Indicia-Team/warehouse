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

if (function_exists('dbase_open') && function_exists('zip_open')) {
  print form::open($controllerpath.'/upload_shp', array('ENCTYPE'=>'multipart/form-data'));
  if ($staticFields != null) {
    foreach ($staticFields as $a => $b) {
      print form::hidden($a, $b);
    }
  }
?>
<fieldset>
<label for="zip_upload" class="auto">Upload a Zipped up SHP fileset into this list:</label>
<input type="file" name="zip_upload" id="zip_upload" size="40" />
<input type="submit" value="Upload ZIP File" />
</fieldset>
</form>
<?php 
} else {
  print "<p><i>";
  if (!function_exists('dbase_open'))
    print "PHP dBase Library is not loaded. ";
  if (!function_exists('zip_open'))
    print "PHP Zip Library is not loaded. ";
  print "Shape file upload disabled.</i></p>";
}
?>
