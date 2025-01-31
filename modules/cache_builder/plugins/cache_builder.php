<?php

/**
 * @file
 * Plugin for the cache builder.
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
 * @link https://github.com/Indicia-Team/warehouse
 */

/**
 * Returns plugin metadata, in particular to set the running order.
 *
 * @return array
 *  Metadata.
 */
function cache_builder_metadata() {
  return [
    'weight' => 1,
  ];
}

/**
 * Hook into the task scheduler.
 *
 * This uses the queries defined in the cache_builder.php file to create and
 * populate cache tables. The tables are not always up to date as they are only
 * updated when the scheduler runs, but they have the advantage of simplifying
 * the data model for reporting as well as reducing the need to join in queries,
 * therefore significantly improving report performance.
 *
 * @param string $last_run_date
 *   Date last run, or null if never run.
 * @param object $db
 *   Database object.
 */
function cache_builder_scheduled_task($last_run_date, $db) {
  if (isset($_GET['force_cache_rebuild'])) {
    $last_run_date = date('Y-m-d', time() - 60 * 60 * 24 * 365 * 200);
  }
  elseif ($last_run_date === NULL) {
    // First run, so get all records changed in last day. Query will
    // automatically gradually pick up the rest.
    $last_run_date = date('Y-m-d', time() - 60 * 60 * 24);
  }
  try {
    foreach (kohana::config('cache_builder') as $table => $queries) {
      cache_builder::populate_cache_table($db, $table, $last_run_date);
      if (!variable::get("populated-$table", FALSE, FALSE)) {
        // Table population incomplete. Don't bother populating the next table,
        // as there can be dependencies.
        break;
      }
    }
  }
  catch (Exception $e) {
    echo "<br/>" . $e->getMessage();
    error_logger::log_error('Building cache', $e);
    throw $e;
  }
}

/**
 * Hook into the warehouse menu system.
 *
 * Adds a menu item for the cache builder status page.
 *
 * @param array $menu
 *   Menu array structure.
 * @param object $auth
 *   Authorisation Kohana object.
 *
 * @return array
 *   Altered menu structure.
 */
function cache_builder_alter_menu(array $menu, $auth) {
  if ($auth->logged_in('CoreAdmin')) {
    $menu['Admin']['Cache builder'] = 'cache_builder_status';
  }
  return $menu;
}

/**
 * Hook into the work queue system for CRUD operations.
 *
 * Adds work queue entries for insert or update of samples and occurrences
 * which populate the attrs_json fields in the cache tables.
 *
 * @return array
 *   List of tables to queue tasks for with configuration.
 */
function cache_builder_orm_work_queue() {
  return [
    [
      'entity' => 'sample',
      'ops' => ['insert', 'update'],
      'task' => 'task_cache_builder_attrs_sample',
      'cost_estimate' => 30,
      'priority' => 2,
    ],
    [
      'entity' => 'occurrence',
      'ops' => ['insert', 'update'],
      'task' => 'task_cache_builder_attrs_occurrence',
      'cost_estimate' => 30,
      'priority' => 2,
    ],
    [
      'entity' => 'taxa_taxon_list',
      'ops' => ['insert', 'update'],
      'task' => 'task_cache_builder_attrs_taxa_taxon_list',
      'cost_estimate' => 30,
      'priority' => 2,
    ],
    // To trap direct updates to attribute values tables.
    [
      'entity' => 'occurrence_attribute_value',
      'ops' => ['insert', 'update', 'delete'],
      'task' => 'task_cache_builder_attr_value_occurrence',
      'cost_estimate' => 30,
      'priority' => 2,
    ],
    [
      'entity' => 'sample_attribute_value',
      'ops' => ['insert', 'update', 'delete'],
      'task' => 'task_cache_builder_attr_value_sample',
      'cost_estimate' => 30,
      'priority' => 2,
    ],
    [
      'entity' => 'taxa_taxon_list_attribute_value',
      'ops' => ['insert', 'update', 'delete'],
      'task' => 'task_cache_builder_attr_value_taxa_taxon_list',
      'cost_estimate' => 30,
      'priority' => 2,
    ],
    [
      'entity' => 'user',
      'ops' => ['update'],
      'limit_to_field_changes' => [
        'allow_share_for_reporting',
        'allow_share_for_peer_review',
        'allow_share_for_verification',
        'allow_share_for_data_flow',
        'allow_share_for_moderation',
        'allow_share_for_editing',
      ],
      'task' => 'task_cache_builder_user_privacy',
      'cost_estimate' => 100,
      'priority' => 2,
    ],
  ];
}
