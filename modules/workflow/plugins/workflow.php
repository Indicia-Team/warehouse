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
 * @package    Modules
 * @subpackage Workflow
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */


/**
 * Implements the alter_menu hook.
 *
 * Determines the extra items to be added to the main Indicia menu.
 *
 * @return array
 *   List of menu items exposed by this plugin.
 */
function workflow_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin')) {
    $menu['Admin']['Workflow Events']='workflow_event';
    $menu['Admin']['Workflow Metadata']='workflow_metadata';
  }
  return $menu;
}

/**
 * Implements the extend_data_services hook.
 *
 * Determines the data entities which should be added to those available via data services.
 *
 * @return array
 *   List of database entities exposed by this plugin with configuration.
 */
function workflow_extend_data_services() {
  return array(
      'workflow_events'=>array(),
      'workflow_metadata'=>array()
  );
}

function workflow_alter_submission() {
    return array(
    );
}


/*
 * potential problem when a record matches multiple events, and they change the same columns
 * 
 * 2 hooks: one that fires before the record is created/updated, and one after.
 * Is this an update or creation?
 * If an update:
 *   Get current entity.
 *   determine event_type: this may be a list in order V,R,U,C
 *   loop through all event Entity/Key/Key_type sets
 *     move to next if entity type does not match event
 *     check if entity appears in undo: if no move to next
 *     at this point the old entity must have passed the event criteria
 *     Check if new entity passes event test:
 *     if no, revert columns to old values; tag the undo row as deleted.
 * 
 * loop through all events
 *   move to next if entity type does not match event
 *   Check if new entity passes event test:
 *   if yes, change columns to new values; create the undo row data with the old values.
 *   Undo row can't be created at this point, as we don't have the row id on INSERT
 *
 *
 * After event:
 * if old data is being saved into undo...
 *   fetch the id from the entity
 *   Populate the entity id field 
 *   create the undo record.
*/