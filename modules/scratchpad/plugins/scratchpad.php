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
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

// @todo Scheduled tasks hook to clean expired lists.

/**
 * Implements hook_orm_extend().
 *
 * Establishes a one-to-many relationship where scratchpad_list has many
 * scratchpad_list_entries, and each entry belongs to a scratchpad_list.
 */
function scratchpad_extend_orm() {
  return [
    'scratchpad_list' => ['has_many' => ['scratchpad_list_entries']],
    'scratchpad_list_entry' => ['belongs_to' => ['scratchpad_list']],
    'group' => ['has_many' => ['groups_scratchpad_lists']],
    'groups_scratchpad_list' => ['belongs_to' => ['group']],
    'location' => ['has_many' => ['locations_scratchpad_lists']],
    'locations_scratchpad_list' => ['belongs_to' => ['location']],
  ];
}

/**
 * Implements hook_data_services_extend().
 *
 * Exposes scratchpad entities to the data services API.
 */
function scratchpad_extend_data_services() {
  return [
    'scratchpad_lists' => [],
    'scratchpad_list_entries' => [],
    'groups_scratchpad_lists' => [],
    'locations_scratchpad_lists' => [],
  ];
}