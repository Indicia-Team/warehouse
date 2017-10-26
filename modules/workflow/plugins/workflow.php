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
 * 2 hooks: one that fires before the record is created/updated, and one after.
 * potential problem when a record matches multiple events, and they change the same columns..
 * so we are making the assumption that each record will only fire one alert key/key_value combination
 * undo record would require more details on firing event (key and key_value) if this is changed in future
 * In following code, entity means the orm entity, e.g. 'occurrence'
 */
function workflow_orm_pre_save_processing($db, $record) {
  // check if it no longer matches first.
  // $record holds an array of the new values being set. This may be a subset.
  $state = array();
  if(isset($record['id']))
    $oldRecord = ORM::factory($entity, $id);
  else $oldRecord = false;

  $combinations = $this->db->
        select('distinct key, key_value')
        ->from('workflow_events')
        ->where('entity', $entity)
        ->where('deleted', 'f')
        ->get();
  foreach($combinations as $combination) {
    $state[$combination['key'].':'.$combination['key_value']] = array(); // holds a list of undo records to create
    if(!isset($record['id']))
      continue;
    $unsetEvent = $this->db->
        select('*')
        ->from('workflow_events')
        ->where('entity', $entity)
        ->where('key', $combination['key'])
        ->where('key_value', $combination['key_value'])
        ->where('event_type', 'Unset')
        ->where('deleted', 'f')
        ->get();
    if(isset($unsetEvent) && workflow_isThisRecord($entity, 'Unset', $record, $oldRecord, $unsetEvent)) {
      workflow_rewindRecord($entity, $record);
    }
  }

  foreach($combinations as $combination) {
    foreach($config[$entity]['event_types'] as $event_type) { // - occurrence order is 'Set', 'Validated', 'Rejected'
      if($event_type === 'Unset') continue;
      $event = $this->db->
          select('*')
          ->from('workflow_events')
          ->where('entity', $entity)
          ->where('key', $combination['key'])
          ->where('key_value', $combination['key_value'])
          ->where('event_type', $event_type)
          ->where('deleted', 'f')
          ->get();
      // $record currently holds the changes to be made from the user, overlaid by an other events, with undo data in $state
      if(isset($event) && workflow_isThisRecord($entity, $event_type, $record, $oldRecord, $event)) {
        $columnDeltaList = array();
        $undo_record= array();
        if($event['mimicRewindFirst']) {
            for($i = count($state[$combination['key'].':'.$combination['key_value']])-1; $i >= 0; $i--) {
                foreach($state[$combination['key'].':'.$combination['key_value']][$i]['old_data'] as $unsetColumn => $unsetValue) {
                    $columnDeltaList[$unsetColumn] = $unsetValue;
                }
            }
          $undoRecords = ORM::factory('workflow_undo')
              ->where(array('entity' => $entity,
                'entity_id' => $record['id'],
                'deleted'=>'f'))
                ->orderby('id DESC')->find_all();
          foreach($undoRecords as $undoRecord) {
            $unsetColumns = json_decode($undoRecord['values']);
            foreach($unsetColumns as $unsetColumn => $unsetValue) {
              $columnDeltaList[$unsetColumn] = $unsetValue;
            }
          }
        }
        $setColumns = json_decode($event['values']);
        foreach($setColumns as $setColumn => $setValue) {
          $columnDeltaList[$setColumn] = $setValue;
        }
        foreach($columnDeltaList as $deltaColumn => $deltaValue) {
            if(isset($record[$deltaColumn])) {
                $undo_value = $record[$deltaColumn];
            } else if(isset($record['id'])) {
                $undo_value = $old_record[$deltaColumn];
            } else if(isset($config[$entity]['defaults'][$deltaColumn])) {
                $undo_value = $config[$entity]['defaults'][$deltaColumn];
          } else {
              $undo_value = null;
          }
          if($deltaValue !== $undo_value) {
              $undo_record[$deltaColumn] == $undo_value;
              $record[$deltaColumn] = $deltaValue;
          }
        }
        $state[$combination['key'].':'.$combination['key_value']][] =
            array('event_type'=>$event_type, 'old_data'=>$undo_record);
      }
    }
  }
  return $state;
}

function workflow_orm_post_save_processing($db, $state) {
  $combinations = $db->
        select('distinct key, key_value')
        ->from('workflow_events')
        ->where('entity', $entity)
        ->where('deleted', 'f')
        ->get();
  foreach($combinations as $combination) {
    if(count($state[$combination['key'].':'.$combination['key_value']]) === 0)
      continue;
    foreach($state[$combination['key'].':'.$combination['key_value']] as $undo_record) {
      $this->db->insert('workflow_undo', array(
          'entity' => $entity,
          'entity_id' => $record['id'],
          'event_type' => $undo_record['event_type'],
          'created_on' => date("Ymd H:i:s"),
          'original_values' => $undo_record['old_data']));
    }
  }
  return true;
}

function workflow_rewindRecord($entity, $record)
{
  $undoRecords = ORM::factory('workflow_undo')
    ->where(array('entity' => $entity,
        'entity_id' => $record['id'],
        'deleted'=>'f'))
    ->orderby('id DESC')->find_all();
  foreach($undoRecords as $undoRecord) {
    $unsetColumns = json_decode($undoRecord['values']);
    foreach($unsetColumns as $unsetColumn => $unsetValue) {
       $record[$unsetColumn] = $unsetValue;
    }
    $this->db->update()
      ->from('workflow_undo')
      ->where('id', $undoRecord['id'])
      ->set('deleted', 'f');
  }
}
function workflow_isThisRecord($entity, $event_type, $record, $oldRecord, $event)
{
  // Set and Unset events are not entity specific.
  // TODO find the cttl data for occurrences -> expanded record, and expanded oldRecord
  // TODO get confirmation: are these events triggered on change of status, or are they triggered each time the record is saved, with possibly no change in event status - e.g. a verified occurrence is saved, but still as the same verified taxon.
  // TODO get confirmation what happens to the fields if they are set in the submission from the user - are they overriden by the event?

    switch($event_type) {
        case 'U': // Unset
            if($oldRecord !== false &&
                $oldRecord[$event['key']] === $event['key_value'] &&
                isset($record[$event['key']]) &&
                $record[$event['key']] !== $event['key_value'])
              return true;
            break;
        case 'S': // Set
            if(($oldRecord === false ||
                  $oldRecord[$event['key']] !== $event['key_value']) &&
                isset($record[$event['key']]) &&
                $record[$event['key']] === $event['key_value'])
              return true;
            break;
        case 'V': // Validated, occurrence specific
            if($entity==='occurrence') {
                if(((isset($record[$event['key']]) && $record[$event['key']] === $event['key_value']) ||
                     (!isset($record[$event['key']]) && $oldRecord !== false && $oldRecord[$event['key']] === $event['key_value']))
                    ((isset($record['record_status']) && $record['record_status'] = 'V') ||
                     (!isset($record['record_status']) && $old_record !== false && $old_record['record_status'] = 'V')))
                  return true;
            }
            break;
        case 'R': // Rejected, occurrence specific
            if($entity==='occurrence') {
                if(((isset($record[$event['key']]) && $record[$event['key']] === $event['key_value']) ||
                     (!isset($record[$event['key']]) && $oldRecord !== false && $oldRecord[$event['key']] === $event['key_value']))
                    ((isset($record['record_status']) && $record['record_status'] = 'R') ||
                     (!isset($record['record_status']) && $old_record !== false && $old_record['record_status'] = 'R')))
                    return true;
            }
            break;
    }
  return false;
}

