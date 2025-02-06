<?php

/**
 * @file
 * View template for the list of taxon designations.
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
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

echo $grid;
?>
<form action="<?php echo url::site() . 'taxon_designation/create'; ?>">
<input type="submit" value="New taxon designation" class="ui-corner-all ui-state-default button" />
</form>
<h2>Upload Indicia CSV format file</h2>
<?php echo $upload_csv_form ?>
<h2>Upload Conservation Designations spreadsheet</h2>
<form id="cons-desig-upload" enctype="multipart/form-data" method="post"
  action="<?php echo url::site() . 'taxon_designation/upload_csv'; ?>">
  <fieldset>
    <div class="row">
      <div class="col-md-6">
        <label for="csv_upload">Upload a Designations Spreadsheet (CSV) file:</label>
        <input type="file" name="csv_upload" class="form-control" required />
        <?php
        echo data_entry_helper::select([
          'label' => 'Taxon list',
          'fieldname' => 'taxon_list_id',
          'table' => 'taxon_list',
          'valueField' => 'id',
          'captionField' => 'title',
          'extraParams' => $readAuth,
          'default' => warehouse::getMasterTaxonListId(),
          'validation' => ['required'],
          'helpText' => 'Choose the taxon list you would like to search for taxa in when linking imported designations.',
        ]);
        ?>
        <input type="submit" value="Upload Designations File" />
      </div>
    </div>
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
<?php
data_entry_helper::enable_validation('cons-desig-upload');
echo data_entry_helper::dump_javascript();
?>
