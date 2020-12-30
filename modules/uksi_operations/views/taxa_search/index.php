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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

?>
<form method="post" action="<?php echo url::site(); ?>taxa_search">
  <input type="text" name="filter-param_organism_key" />
  <input type="submit" value="Search" />
</form>
<?php
warehouse::loadHelpers(['report_helper']);
$readAuth = report_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

$header = <<<HTML
<div class="panel panel-info">
  <div class="panel-heading">Taxonomy</div>
  <div class="panel-body">
    <ul>
HTML;
$footer = <<<HTML
    </ul>
  </div>
</div>
HTML;
$organismLink = url::site() . 'taxa_search?filter-param_organism_key={organism_key}';
$template = <<<HTML
      <li style="margin-left: {margin}px">
        <a href="$organismLink"><span class="{name_class}">{taxon}</span> {attribute} {authority}</a>
      </li>
HTML;
echo report_helper::freeform_report([
  'readAuth' => $readAuth,
  'dataSource' => 'library/taxa/uksi_taxa_search_parents',
  'autoParamsForm' => FALSE,
  'reportGroup' => 'filter',
  'extraParams' => ['param_taxon_list_id' => $listId],
  'header' => $header,
  'footer' => $footer,
  'bands' => [
    [
      'content' => $template,
    ],
  ],
]);

$header = <<<HTML
<div class="panel panel-info">
  <div class="panel-heading">Names</div>
  <div class="panel-body">
HTML;
$footer = <<<HTML
  </div>
</div>
HTML;
$template = <<<HTML
    <dl class="dl-horizontal">
      <dt>Name</dt>
      <dd><span class="{name_class}">{taxon}</span> {attribute} {authority}</dd>

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
  'dataSource' => 'library/taxa/uksi_taxa_search',
  'autoParamsForm' => FALSE,
  'reportGroup' => 'filter',
  'extraParams' => ['param_taxon_list_id' => $listId],
  'header' => $header,
  'footer' => $footer,
  'bands' => [
    [
      'content' => $template,
    ],
  ],
]);