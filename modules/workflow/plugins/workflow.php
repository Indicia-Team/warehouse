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

// TODO add tab to occurrences to display workflow_undo data
// TODO Build in ability to handle default?

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
  return array('workflow_events'=>array(),
               'workflow_metadata'=>array());
}

/*
 * 2 hooks: one that fires before the record is created/updated, and one after.
 * potential problem when a record matches multiple events, and they change the same columns..
 * so we are making the assumption that each record will only fire one alert key/key_value combination
 * undo record would require more details on firing event (key and key_value) if this is changed in future
 * In following code, entity means the orm entity, e.g. 'occurrence'
 */
function workflow_orm_pre_save_processing($db, $entity, &$record) {
  // check if it no longer matches first.
  // $record holds an array of the new values being set. This may be a subset.
  $config = kohana::config('workflow');
  $state = array();
  if(!isset($config['entities'][$entity]))
    return $state;

  $recordSet = workflow_getData($db, $config, $entity, $record);

  $combinations = $db->
    select('distinct key, key_value')
      ->from('workflow_events')
      ->where('entity', $entity)
      ->where('deleted', 'f')
      ->get()->as_array();
  foreach($combinations as $combination) {
    $state[$combination->key.':'.$combination->key_value] = array(); // holds a list of undo records to create
    if(!isset($record['id']))
      continue;
    if(workflow_isThisRecord($db, $config, $entity, 'U', $recordSet,  (object) array("key"=>$combination->key, "key_value"=>$combination->key_value))) {
      kohana::log('info', 'Workflow triggered Unset event '.$entity.' Key '.($combination->key).' Value '.($combination->key_value));
      workflow_rewindRecord($db, $entity, $record);
    }
  }

  foreach($combinations as $combination) {
    foreach($config['entities'][$entity]['event_types'] as $event_type) { // - occurrence order is 'Set', 'Validated', 'Rejected'
      $events = $db->
        select('*')
          ->from('workflow_events')
          ->where('entity', $entity)
          ->where('key', $combination->key)
          ->where('key_value', $combination->key_value)
          ->where('event_type', $event_type['code'])
          ->where('deleted', 'f')
          ->get()->as_array();
      // This should be unique, so at max 1 record.
      foreach($events as $event) {
        // $record currently holds the changes to be made from the user, overlaid by any other events, with undo data in $state
        if(workflow_isThisRecord($db, $config, $entity, $event_type['code'], $recordSet, $event)) {
          kohana::log('info', 'Workflow triggered event '.$event_type['title'].': Key '.$combination->key.', Value '.$combination->key_value);
          $columnDeltaList = array();
          $newUndoRecord= array();
          if($event->mimic_rewind_first === 't') {
            for($i = count($state[$combination->key.':'.$combination->key_value])-1; $i >= 0; $i--) {
              foreach($state[$combination->key.':'.$combination->key_value][$i]['old_data'] as $unsetColumn => $unsetValue) {
                  $columnDeltaList[$unsetColumn] = $unsetValue;
              }
            }
            if(isset($record['id'])) {
              $undoRecords = ORM::factory('workflow_undo')
                  ->where(array('entity' => $entity, 'entity_id' => $record['id'], 'active'=>'t'))
                  ->orderby('id','DESC')->find_all();
              foreach($undoRecords as $undoRecord) {
                $unsetColumns = json_decode($undoRecord->original_values);
                foreach($unsetColumns as $unsetColumn => $unsetValue) {
                  $columnDeltaList[$unsetColumn] = $unsetValue;
                }
              }
            }
          }
          $setColumns = json_decode($event->values);
          foreach($setColumns as $setColumn => $setValue) {
            $columnDeltaList[$setColumn] = $setValue;
          }
          foreach($columnDeltaList as $deltaColumn => $deltaValue) {
            if(isset($record[$deltaColumn])) {
              $undo_value = $record[$deltaColumn];
            } else if(isset($record['id'])) {
              $undo_value = $recordSet['previous'][$entity][$deltaColumn];
            } else if(isset($config[$entity]['defaults'][$deltaColumn])) {
              $undo_value = $config[$entity]['defaults'][$deltaColumn];
            } else {
              $undo_value = null;
            }
            if($deltaValue !== $undo_value) {
              $newUndoRecord[$deltaColumn] = $undo_value;
              $record[$deltaColumn] = $deltaValue;
            }
          }
          $state[$combination->key.':'.$combination->key_value][] =
            array('event_type'=>$event_type, 'old_data'=>$newUndoRecord);
        }
      }
    }
  }
  return $state;
}

function workflow_getData($db, $config, $entity, $record)
{
  $retVal = array('record'=>array($entity=>($record->as_array())), 'previous'=>array($entity=>false));
  if(isset($record['id'])) {
    $old = $db->select('*')
      ->from(inflector::plural($entity))
      ->where('id', $record['id'])
      ->where('deleted', 'f')
      ->get()->as_array();
    if(count($old)===1)
      $retVal['previous'][$entity] = get_object_vars($old[0]);
  }
  if(isset($config['entities'][$entity]) && isset($config['entities'][$entity]['extraData'])) {
    foreach($config['entities'][$entity]['extraData'] as $tableDefn) {
      $new = $db->select('*')
          ->from($tableDefn['table'])
          ->where($tableDefn['target_table_column'], $record[$tableDefn['originating_table_column']]) // ?TODO fall back to old
          ->get()->as_array();
      $retVal['record'][$tableDefn['table']] = (count($new)===1 ? get_object_vars($new[0]) : false);
      if($retVal['previous'][$entity]) {
        $old = $db->select('*')
            ->from($tableDefn['table'])
            ->where($tableDefn['target_table_column'], $retVal['previous'][$entity][$tableDefn['originating_table_column']]) // ?TODO fall back to old
            ->get()->as_array();
        $retVal['previous'][$tableDefn['table']] = (count($old)===1 ? get_object_vars($old[0]) : false);
      } else $retVal['previous'][$tableDefn['table']] = false;
    }
  }
  return $retVal;
}

function workflow_orm_post_save_processing($db, $entity, $record, $state, $id) {
  $combinations = $db->select('distinct key, key_value')
      ->from('workflow_events')
      ->where('entity', $entity)
      ->where('deleted', 'f')
      ->get()->as_array();

  // At this point we determine the id of the logged in user,
  // and use this in preference to the default id if possible.
  if (isset($_SESSION['auth_user']))
    $userId = $_SESSION['auth_user']->id;
  else {
    global $remoteUserId;
    if (isset($remoteUserId))
      $userId = $remoteUserId;
    else {
      $defaultUserId = Kohana::config('indicia.defaultPersonId');
      $userId = ($defaultUserId ? $defaultUserId : 1);
    }
  }

  foreach($combinations as $combination) {
    if(!isset($state[$combination->key.':'.$combination->key_value]) ||
        count($state[$combination->key.':'.$combination->key_value]) === 0)
      continue;
    foreach($state[$combination->key.':'.$combination->key_value] as $undoDetails) {
      $db->insert('workflow_undo', array('entity' => $entity,
                                         'entity_id' => $id,
                                         'event_type' => $undoDetails['event_type']['code'],
                                         'created_on' => date("Ymd H:i:s"),
                                         'created_by_id' => $userId,
                                         'original_values' => json_encode($undoDetails['old_data'])));
    }
  }
  return true;
}

function workflow_rewindRecord($db, $entity, &$record)
{
  $undoRecords = ORM::factory('workflow_undo')
      ->where(array('entity' => $entity, 'entity_id' => $record['id'], 'active'=>'t'))
      ->orderby('id','DESC')->find_all();
  foreach($undoRecords as $undoRecord) {
    $unsetColumns = json_decode($undoRecord->original_values);
    foreach($unsetColumns as $unsetColumn => $unsetValue) {
       $record[$unsetColumn] = $unsetValue;
    }
    $db->update('workflow_undo', array('active'=>'f'), array('id'=>$undoRecord->id));
  }
}

function workflow_isThisRecord($db, $config, $entity, $event_type, $recordSet, $event)
{
  $keyDefn = null;
  for($i=0; $i<count($config['entities'][$entity]['keys']); $i++) {
    if($config['entities'][$entity]['keys'][$i]['db_store_value'] === $event->key)
      $keyDefn = $config['entities'][$entity]['keys'][$i];
  }
  if($keyDefn === null) {
    kohana::log('error', 'KeyDefn not found');
    return false;
  }
  // Set and Unset events are not entity specific.
  // Currently all events are triggered on transition
  switch($event_type) {
    case 'U': // Unset
      if($recordSet['previous'][$keyDefn['table']] !== false &&
          $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] === $event->key_value &&
          $recordSet['record'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value)
        return true;
      break;
    case 'S': // Set
      if(($recordSet['previous'][$keyDefn['table']] === false ||
          $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value) &&
          $recordSet['record'][$keyDefn['table']][$keyDefn['column']] === $event->key_value)
        return true;
      break;
    case 'V': // Validated, occurrence specific
      if($entity==='occurrence') {
        if($recordSet['record'][$keyDefn['table']][$keyDefn['column']] === $event->key_value &&
            isset($recordSet['record'][$entity]['record_status']) &&
            $recordSet['record'][$entity]['record_status'] === 'V' &&
            ($recordSet['previous'][$entity]===false ||
              $recordSet['previous'][$entity]['record_status'] !== 'V' ||
              $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value))
          return true;
      }
      break;
    case 'R': // Rejected, occurrence specific
      if($entity==='occurrence') {
        if($recordSet['record'][$keyDefn['table']][$keyDefn['column']] === $event->key_value &&
            isset($recordSet['record'][$entity]['record_status']) &&
            $recordSet['record'][$entity]['record_status'] === 'R' &&
            ($recordSet['previous'][$entity]===false ||
              $recordSet['previous'][$entity]['record_status'] !== 'R' ||
              $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value))
          return true;
      }
      break;
  }
  return false;
}

