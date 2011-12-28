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
 
 
echo $grid;
?>
<form action="<?php echo url::site().'taxon_designation/create'; ?>">
<input type="submit" value="New taxon designation" class="ui-corner-all ui-state-default button" />
</form>
<?php echo $upload_csv_form ?>
<form enctype="multipart/form-data" class="linear-form" method="post" action="<?php echo url::site().'taxon_designation/upload_csv'; ?>">
<fieldset>
<label for="csv_upload" class="auto">Upload a Designations Spreadsheet (CSV) file into this list:</label>
<input type="file" name="csv_upload" id="csv_upload" size="40" />
<input type="submit" value="Upload Designations File" />
<p>This lets you import designations including links for any existing taxon, identified by the external key. 
  To use this facility, create a spreadsheet with the following columns, or columns matching the JNCC Conservation Designations spreadsheet:</p>
<ol>
  <li>designation title</li>
  <li>designation code</li>
  <li>designation abbr</li>
  <li>designation description</li>
  <li>designation category</li>
  <li>taxon</li>
  <li>taxon external key</li>
  <li>start date</li>
  <li>source</li>
  <li>geographic constraint</li>
</ol>

</fieldset>
</form>