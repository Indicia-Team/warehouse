<?php

/**
 * @file
 * Plugin file for the Summary Builder module.
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

/**
 * Plugin module which creates a calculated summary of records, including estimates.
 */
function summary_builder_orm_work_queue() {
  return [
    [
      /* It is only the presence/absence of a sample that affects the values/estimates, so this is invoked on insert or
       * deletion, but it is also possible for the UKBMS front end to modify the date.
       * There is a possibility that if the date on a sample changes to a different year, the the old year values
       * will not be updated - similar if the location is moved. Operationally, this is a very remote possibility,
       * and will be covered by allowing the warehouse front end to trigger a rebuild of a locations data.
       * In addition the UKBMS front end can not change the date or location.
       */
      'entity' => 'sample',
      'ops' => ['insert', 'delete', 'update'],
      'task' => 'task_summary_builder_sample',
      'cost_estimate' => 50,
      'priority' => 2,
    ],
    [
      /* Insert or delete of an occurrence only affects the records for that occurrence */
      'entity' => 'occurrence',
      'ops' => ['insert', 'delete'],
      'task' => 'task_summary_builder_occurrence_insert_delete',
      'cost_estimate' => 40,
      'priority' => 2,
    ],
    [
      /* On the other hand, a modification may include a change of taxon, so must recalculate all existing records */
      'entity' => 'occurrence',
      'ops' => ['update'],
      'task' => 'task_summary_builder_occurrence_update',
      'cost_estimate' => 50,
      'priority' => 2,
    ],
    [
      // only location information in the summary_occurrences data is id: only need to worry about delete
      'entity' => 'location',
      'ops' => ['delete'],
      'task' => 'task_summary_builder_location_delete',
      'cost_estimate' => 40,
      'priority' => 3,
    ],
/*
    [
      // A taxon change needs to change the cached taxon details.
      'entity' => 'taxon',
      'ops' => ['update'],
      'task' => 'task_summary_builder_location_taxon',
      'cost_estimate' => 10,
      'priority' => 2,
    ],
*/
  ];
}

// summary_builder is no longer a scheduled task.
function summary_builder_alter_menu($menu, $auth) {
    if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin'))
        $menu['Admin']['Summariser']='summariser_definition';
        return $menu;
}

function summary_builder_extend_data_services() {
    return array(
        'summariser_definitions'=>array(),
        'summary_occurrences'=>array()
    );
}
