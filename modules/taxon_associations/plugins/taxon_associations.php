<?php

/**
 * @file
 * Plugin methods for the taxon associations module.
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

/**
 * Hook to ORM enable the relationship between taxon_meanings and associations.
 */
function taxon_associations_extend_orm() {
  return [
    'taxon_meaning' => [
      'has_and_belongs_to_many' => ['taxon_associations'],
    ],
  ];
}

function taxon_associations_extend_data_services() {
  return array(
    'taxon_associations' => [],
  );
}

/**
 * Extend the UI to include a tab on taxa for the associations.
 *
 * @return array
 *   UI extensions.
 */
function taxon_associations_extend_ui() {
  return [
    [
      'view' => 'taxa_taxon_list/taxa_taxon_list_edit',
      'type' => 'tab',
      'controller' => 'taxon_association',
      'title' => 'Associations',
      'allowForNew' => FALSE,
    ],
  ];
}
