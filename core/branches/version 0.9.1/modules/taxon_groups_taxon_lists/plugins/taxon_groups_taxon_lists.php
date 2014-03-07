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
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Adds a tab for the taxon groups that are associated with a taxon list. 
 * @return array List of user interface extensions.
 */
function taxon_groups_taxon_lists_extend_ui() {
  return array(array(
    'view'=>'taxon_list/taxon_list_edit', 
    'type'=>'tab',
    'controller'=>'taxon_groups_taxon_list/index',
    'title'=>'Taxon Groups'
  ));
}

/**
 * Hook to ORM enable the relationship between taxon groups and lists from the taxon lists end.
 */
function taxon_groups_taxon_lists_extend_orm() {
  return array('taxon_list'=>array(
    'has_and_belongs_to_many'=>array('taxon_groups')
  ));
}

// Provide access via the data services
function taxon_groups_taxon_lists_extend_data_services() {
  return array(
    'taxon_groups_taxon_lists'=>array()
  );
}

?>