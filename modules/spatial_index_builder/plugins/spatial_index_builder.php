<?php

/**
 * @file
 * Plugin file for the Spatial Index Builder module.
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
 *
 * @todo Limit to surveys if defined in config.
 */

/**
 * Plugin module which creates an indexed array of overlapping location IDs in
 * the cache tables.
 */

/**
 * Declare which ORM CRUD operations should generate work queue tasks.
 *
 * @return array
 *   List of operations.
 */
function spatial_index_builder_orm_work_queue() {
  return [
    [
      'entity' => 'sample',
      'ops' => ['insert', 'update'],
      'task' => 'task_spatial_index_builder_sample',
      'cost_estimate' => 70,
      'priority' => 2,
    ],
    [
      'entity' => 'occurrence',
      'ops' => ['insert'],
      'task' => 'task_spatial_index_builder_occurrence',
      'cost_estimate' => 70,
      'priority' => 2,
    ],
    [
      'entity' => 'location',
      'ops' => ['insert', 'update'],
      'task' => 'task_spatial_index_builder_location',
      'cost_estimate' => 70,
      'priority' => 2,
    ],
    [
      'entity' => 'location',
      'ops' => ['delete'],
      'task' => 'task_spatial_index_builder_location_delete',
      'cost_estimate' => 40,
      'priority' => 3,
    ],
  ];
}
