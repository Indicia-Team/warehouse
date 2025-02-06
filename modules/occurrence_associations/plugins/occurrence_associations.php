<?php

/**
 * @file
 * Plugin for the occurreance associations module.
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
 * Hook to ORM enable the relationship between occurrences and associations.
 */
function occurrence_associations_extend_orm() {
  return [
    'occurrence' => [
      'has_and_belongs_to_many' => ['occurrence_associations'],
    ],
  ];
}

function occurrence_associations_extend_data_services() {
  return array(
    'occurrence_associations' => [],
  );
}

function occurrence_associations_extend_ui() {
  return [
    [
      'view' => 'occurrence/occurrence_edit',
      'type' => 'tab',
      'controller' => 'occurrence_association',
      'title' => 'Associations',
      'allowForNew' => FALSE,
    ],
  ];
}
