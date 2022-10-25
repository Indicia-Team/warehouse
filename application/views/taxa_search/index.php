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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

warehouse::loadHelpers(['data_entry_helper']);
$readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
$tvkKeyChecked = (!empty($_GET['filter-key_type']) && $_GET['filter-key_type'] === 'tvk') ? ' checked' : '';
$orgKeyChecked = empty($tvkChecked) ? ' checked' : '';
$defaultKey = empty($_GET['filter-key']) ? '' : $_GET['filter-key'];
$defaultSearchName = empty($_GET['filter-taxa_taxon_list_id:taxon']) ? '' : $_GET['filter-taxa_taxon_list_id:taxon'];
$defaultSearchNameId = empty($_GET['filter-taxa_taxon_list_id']) ? '' : $_GET['filter-taxa_taxon_list_id'];
// GET can override the configured default.
if (!empty($_GET['filter-taxon_list_id'])) {
  $defaultListId = $_GET['filter-taxon_list_id'];
}

?>
<div class="row">
  <div class="col-md-5">
    <form method="GET" action="<?php echo url::site(); ?>taxa_search">
    <div class="form-group">
      <label for="filter-taxon_list_id">Taxon list:</label>
      <select id="filter-taxon_list_id" name="filter-taxon_list_id" class="form-control">
        <option value="">-Please select-</option>
        <?php foreach ($taxonLists as $id => $title) {
          $selected = $id == $defaultListId ? ' selected' : '';
          echo "<option value=\"$id\"$selected>$title</option>";
        } ?>
      </select>
    </div>
    <div class="form-group">
      <label for="filter-key">Key:</label>
      <input type="text" name="filter-key" id="filter-key" class="form-control" placeholder="Enter the key for the taxon you are looking for" value="<?php echo $defaultKey; ?>" />
    </div>
    <div class="form-group">
      <label class="radio-inline"><input type="radio" name="filter-key_type" value="org"<?php echo $orgKeyChecked; ?>>Organism key</label>
      <label class="radio-inline"><input type="radio" name="filter-key_type" value="tvk"<?php echo $tvkKeyChecked; ?>>Taxon version key</label>
    </div>
    <?php
    echo data_entry_helper::species_autocomplete([
      'label' => 'Search',
      'fieldname' => 'filter-taxa_taxon_list_id',
      'default' => $defaultSearchNameId,
      'defaultCaption' => $defaultSearchName,
      'extraParams' => $readAuth,
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
    ]);
    ?>
    <input type="submit" class="btn btn-primary" value="Search" id="search-btn" />
  </form>
  </div>
</div>

<?php
if (trim($defaultKey . $defaultSearchNameId) !== '' && !empty($defaultListId)) {
  // Something to search for so output reports.
  warehouse::loadHelpers(['report_helper']);
  helper_base::add_resource('font_awesome');
  $organismLink = url::site() . "taxa_search?filter-taxon_list_id=$defaultListId&filter-taxa_taxon_list_id={taxa_taxon_list_id}";
  $editLink = url::site() . "taxa_taxon_list/edit/{pref_taxa_taxon_list_id}";
  echo "<article>\n";
  $header = <<<HTML
    <section class="panel panel-info">
      <div class="panel-heading">Taxonomy</div>
      <div class="panel-body">
        <ul>
  HTML;
  $footer = <<<HTML
        </ul>
      </div>
    </section>
  HTML;
  $template = <<<HTML
        <li style="margin-left: {margin}px">
          {rank}: <a href="$organismLink"><span class="{name_class}">{taxon}</span> {attribute} {authority}</a>
        </li>
  HTML;
  echo report_helper::freeform_report([
    'readAuth' => $readAuth,
    'dataSource' => 'library/taxa/taxon_search_form_parents',
    'autoParamsForm' => FALSE,
    'reportGroup' => 'filter',
    'header' => $header,
    'footer' => $footer,
    'bands' => [
      [
        'content' => $template,
      ],
    ],
  ]);

  $header = <<<HTML
    <section class="panel panel-info">
      <div class="panel-heading">Names</div>
      <div class="panel-body">
  HTML;
  $footer = <<<HTML
      </div>
    </section>
  HTML;
  $template = <<<HTML
        <dl class="dl-horizontal taxon-{name_type}">
          <dt>Name</dt>
          <dd><span class="{name_class}">{taxon}</span> {attribute} {authority} <a class="btn btn-info btn-xs" href="$editLink"><i class="fas fa-edit"></i> edit</a></dd>

          <dt>Name type</dt>
          <dd>{name_type}</dd>

          <dt>Rank</dt>
          <dd>{rank}</dd>

          <dt>TVK</dt>
          <dd>{search_code}</dd>

          <dt>Accepted name TVK</dt>
          <dd>{external_key}</dd>

          <dt>Organism Key</dt>
          <dd>{organism_key}</dd>

          <dt title="ID for the taxon concept given by the Indicia warehouse.">Taxon meaning ID</dt>
          <dd>{taxon_meaning_id}</dd>

          <dt>Redundant</dt>
          <dd>{redundant}</dd>
        </dl>
  HTML;
  echo report_helper::freeform_report([
    'readAuth' => $readAuth,
    'dataSource' => 'library/taxa/taxon_search_form_response',
    'autoParamsForm' => FALSE,
    'reportGroup' => 'filter',
    'header' => $header,
    'footer' => $footer,
    'bands' => [
      [
        'content' => $template,
      ],
    ],
  ]);

  $header = <<<HTML
    <section class="panel panel-info">
      <div class="panel-heading">Children</div>
      <div class="panel-body">
        <ul class="horizontal">
  HTML;
  $footer = <<<HTML
        </ul>
      </div>
    </section>
  HTML;
  $template = <<<HTML
          <li>
            <a href="$organismLink"><span class="{name_class}">{taxon}</span> {attribute} {authority} [{rank}]</a>
          </li>
  HTML;
  echo report_helper::freeform_report([
    'readAuth' => $readAuth,
    'dataSource' => 'library/taxa/taxon_search_form_children',
    'autoParamsForm' => FALSE,
    'reportGroup' => 'filter',
    'header' => $header,
    'footer' => $footer,
    'bands' => [
      [
        'content' => $template,
      ],
    ],
  ]);
}
echo '</article>';
echo data_entry_helper::dump_javascript();
