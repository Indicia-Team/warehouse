<?php

/**
 * @file
 * Plugin for the workflow module.
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
 * @package Modules
 * @subpackage Workflow
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/
 */

// @todo add tab to occurrences to display workflow_undo data
// @todo Build in ability to handle default?

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
    $menu['Admin']['Workflow Events'] = 'workflow_event';
    $menu['Admin']['Workflow Metadata'] = 'workflow_metadata';
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
    'workflow_events' => array(),
    'workflow_metadata' => array()
  );
}

/**
 * Pre-record save processing hook.
 *
 * Potential problem when a record matches multiple events, and they change the same columns,
 * so we are making the assumption that each record will only fire one alert key/key_value combination
 * undo record would require more details on firing event (key and key_value) if this is changed in future
 * In following code, entity means the orm entity, e.g. 'occurrence'
 *
 * @param object $db
 *   Database connection.
 * @param string $entity
 *   Name of the database entity being saved, e.g. occurrence.
 * @param array|object $record
 *   Save data.
 *
 * @return array
 *   State data to pass to the post save processing hook.
 */
function workflow_orm_pre_save_processing($db, $entity, &$record) {
  // Check if it no longer matches first.
  // $record holds an array of the new values being set. This may be a subset.
  $config = kohana::config('workflow');
  $state = array();
  if (!isset($config['entities'][$entity])) {
    return $state;
  }

  $recordSet = workflow::getData($db, $config, $entity, $record);

  $combinations = $db
    ->select('distinct key, key_value')
    ->from('workflow_events')
    ->where('entity', $entity)
    ->where('deleted', 'f')
    ->get()->as_array();
  foreach ($combinations as $combination) {
    // Hold a list of undo records to create.
    $state[$combination->key . ':' . $combination->key_value] = array();
    if (!isset($record['id'])) {
      continue;
    }
    if (workflow::isThisRecord($db, $config, $entity, 'U', $recordSet, (object) array(
      "key" => $combination->key,
      "key_value" => $combination->key_value,
    ))) {
      kohana::log('info', 'Workflow triggered Unset event ' . $entity .
        ' Key ' . ($combination->key) . ' Value ' . ($combination->key_value));
      workflow::rewindRecord($db, $entity, $record);
    }
  }

  foreach ($combinations as $combination) {
    // Occurrence order is 'Set', 'Validated', 'Rejected'.
    foreach ($config['entities'][$entity]['event_types'] as $event_type) {
      $events = $db
        ->select('*')
        ->from('workflow_events')
        ->where('entity', $entity)
        ->where('key', $combination->key)
        ->where('key_value', $combination->key_value)
        ->where('event_type', $event_type['code'])
        ->where('deleted', 'f')
        ->get()->as_array();
      // This should be unique, so at max 1 record.
      foreach ($events as $event) {
        // $record currently holds the changes to be made from the user, overlaid by any other events, with undo data in $state
        if (workflow::isThisRecord($db, $config, $entity, $event_type['code'], $recordSet, $event)) {
          kohana::log('info', 'Workflow triggered event ' . $event_type['title'] .
            ': Key ' . $combination->key . ', Value ' . $combination->key_value);
          $columnDeltaList = array();
          $newUndoRecord = array();
          if ($event->mimic_rewind_first === 't') {
            for ($i = count($state[$combination->key . ':' . $combination->key_value]) - 1; $i >= 0; $i--) {
              foreach ($state[$combination->key . ':' . $combination->key_value][$i]['old_data'] as $unsetColumn => $unsetValue) {
                $columnDeltaList[$unsetColumn] = $unsetValue;
              }
            }
            if (isset($record['id'])) {
              $undoRecords = ORM::factory('workflow_undo')
                ->where(array(
                  'entity' => $entity,
                  'entity_id' => $record['id'],
                  'active' => 't',
                ))
                ->orderby('id', 'DESC')->find_all();
              foreach ($undoRecords as $undoRecord) {
                $unsetColumns = json_decode($undoRecord->original_values);
                foreach ($unsetColumns as $unsetColumn => $unsetValue) {
                  $columnDeltaList[$unsetColumn] = $unsetValue;
                }
              }
            }
          }
          $setColumns = json_decode($event->values);
          foreach ($setColumns as $setColumn => $setValue) {
            $columnDeltaList[$setColumn] = $setValue;
          }
          foreach ($columnDeltaList as $deltaColumn => $deltaValue) {
            if (isset($record[$deltaColumn])) {
              $undo_value = $record[$deltaColumn];
            }
            elseif (isset($record['id'])) {
              $undo_value = $recordSet['previous'][$entity][$deltaColumn];
            }
            elseif (isset($config[$entity]['defaults'][$deltaColumn])) {
              $undo_value = $config[$entity]['defaults'][$deltaColumn];
            }
            else {
              $undo_value = NULL;
            }
            if ($deltaValue !== $undo_value) {
              $newUndoRecord[$deltaColumn] = $undo_value;
              $record[$deltaColumn] = $deltaValue;
            }
          }
          $state[$combination->key . ':' . $combination->key_value][] =
            array('event_type' => $event_type, 'old_data' => $newUndoRecord);
        }
      }
    }
  }
  return $state;
}

/**
 * Post record save processing hook.
 *
 * @param object $db
 *   Database connection.
 * @param string $entity
 *   Name of the database entity being saved, e.g. occurrence.
 * @param array|object $record
 *   Save data.
 * @param array $state
 *   State data returned by the pre-save hook.
 * @param int $id
 *   ID of saved record.
 *
 * @return bool
 *   Returns TRUE to imply success.
 */
function workflow_orm_post_save_processing($db, $entity, $record, array $state, $id) {
  $combinations = $db->select('distinct key, key_value')
    ->from('workflow_events')
    ->where('entity', $entity)
    ->where('deleted', 'f')
    ->get()->as_array();

  // At this point we determine the id of the logged in user,
  // and use this in preference to the default id if possible.
  if (isset($_SESSION['auth_user'])) {
    $userId = $_SESSION['auth_user']->id;
  }
  else {
    global $remoteUserId;
    if (isset($remoteUserId)) {
      $userId = $remoteUserId;
    }
    else {
      $defaultUserId = Kohana::config('indicia.defaultPersonId');
      $userId = ($defaultUserId ? $defaultUserId : 1);
    }
  }

  foreach ($combinations as $combination) {
    if (!isset($state[$combination->key . ':' . $combination->key_value]) ||
        count($state[$combination->key . ':' . $combination->key_value]) === 0) {
      continue;
    }
    foreach ($state[$combination->key . ':' . $combination->key_value] as $undoDetails) {
      $db->insert('workflow_undo', array(
        'entity' => $entity,
        'entity_id' => $id,
        'event_type' => $undoDetails['event_type']['code'],
        'created_on' => date("Ymd H:i:s"),
        'created_by_id' => $userId,
        'original_values' => json_encode($undoDetails['old_data'])
      ));
    }
  }
  return TRUE;
}
