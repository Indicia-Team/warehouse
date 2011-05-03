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
 * Adds a tab for viewing designations for the selected taxon.
 * @todo Complete this tab
 * @return <type>
 */
function taxon_designations_extend_ui() {
  return array(array(
    'view'=>'taxa_taxon_list/taxa_taxon_list_edit', 
    'type'=>'tab',
    'controller'=>'taxa_taxon_designation/index',
    'title'=>'Designations'
  ));
}

/**
 * Create a menu item for the list of taxon designations.
 */
function taxon_designations_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin')) 
    $menu['Admin']['Taxon Designations']='taxon_designation';
  return $menu;
}

/**
 * Hook to ORM enable the relationship between taxon designations and taxa from the taxon end.
 */
function taxon_designations_extend_orm() {
  return array('taxon'=>array(
    'has_and_belongs_to_many'=>array('taxon_designations')
  ));
}

function taxon_designations_extend_data_services() {
  return array(
    'taxon_designations'=>array(),
    'taxa_taxon_designations'=>array()
  );
}

?>