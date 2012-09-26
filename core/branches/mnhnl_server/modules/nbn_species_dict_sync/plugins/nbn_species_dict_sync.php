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
 * @package	NBN Species Dict Sync
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * User interface extensions for the various NBN Sync tab.
 * @return array UI Extension details
 */
function nbn_species_dict_sync_extend_ui() {
  // tab on the taxon list edit page, allowing you to pull the Species Dictionary content into the list.
  return array(array(
      'view'=>'taxon_list/taxon_list_edit',
      'type'=>'tab',
      'controller'=>'nbn_species_dict_sync/taxon_lists',
      'title'=>'NBN Species Dict Sync',
      'actions'=>array('edit')
    ),
    // tab on the taxon groups index page, allowing you to pull the Species Dictionary reporting categories into the list.
    array(
      'view'=>'taxon_group/index',
      'type'=>'tab',
      'controller'=>'nbn_species_dict_sync/taxon_groups',
      'title'=>'NBN Sync'
    ),
    // tab on the taxon designations inedx page, allowing you to pull the Species Dictionary designations in.
    array(
      'view'=>'taxon_designation/index',
      'type'=>'tab',
      'controller'=>'nbn_species_dict_sync/taxon_designations',
      'title'=>'NBN Sync'
    )
  );
}

?>
