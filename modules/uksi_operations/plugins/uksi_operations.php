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
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Create a menu item for the list of UKSI operations.
 */
function uksi_operations_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin') || $auth->logged_in('UKSIAdmin')) {
    $menu['Taxonomy']['UKSI operations'] = 'uksi_operation';
    $menu['Taxonomy']['UKSI taxa search'] = 'taxa_search';
  }
  return $menu;
}

/**
 * Hook to ORM to expose the UKSI operations entity.
 */
function uksi_operations_extend_orm() {
  return [
    'uksi_operation' => [],
  ];
}

function uksi_operations_extend_data_services() {
  return array(
    'uksi_operations' => [],
  );
}
