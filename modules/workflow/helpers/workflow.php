<?php

/**
 * @file
 * Helper class for workflow code.
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
 * @subpackage Summary_builder
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Helper class for workflow functionality.
 */
class workflow {

  private static $configForEntities = [];

  public static function getEntityConfig($entity) {
    if (!isset(self::$configForEntities[$entity])) {
      $config = Kohana::config('workflow');
      if (!isset($config['entities'])) {
        throw new exception('Incorrect workflow configuration - missing entities.');
      }
      self::$configForEntities[$entity] = isset($config['entities'][$entity]) ? $config['entities'][$entity] : NULL;
    }
    return self::$configForEntities[$entity];
  }

  /**
   * Applies undo data to rewind records to their originally posted state.
   *
   * This occurs when a record has been modified by the workflow system because
   * of a particular key value linking it to a workflow event record, then the
   * key value is changed so the workflow event is no longer relevant.
   *
   * @param object $db
   *   Database connection.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param object $oldRecord
   *   ORM object containing the old record details.
   * @param object $newRecord
   *   ORM Validation object containing the new record details. Will be updated
   *   with the rewind changes.
   *
   * @return object
   *   Clone of the old record object, but with the rewinds applied.
   */
  public static function getRewoundRecord($db, $entity, $oldRecord, &$newRecord) {
    $entityConfig = self::getEntityConfig($entity);
    // Clone the old record data to apply our rewind changes to.
    $rewoundRecord = (object) $oldRecord->as_array();
    // Can't rewind a new record, or if the config does not define keys to filter on.
    if (!isset($entityConfig['keys']) || empty($oldRecord->id)) {
      return $rewoundRecord;
    }
    $eventTypes = [];
    foreach ($entityConfig['keys'] as $keyDef) {
      $keyChanged = FALSE;
      // We need to know if the key has changed to decide whether to wind back.
      // If the key is in the main entity, we can directly compare the old and new keys.
      if ($keyDef['table'] === $entity) {
        $keyCol = $keyDef['column'];
        $keyChanged = (string) $oldRecord->column !== (string) $newRecord->column;
      }
      else {
        // Find the definintion of the extra data table that contains the
        // column we need to look for changes in. We can then look to see if
        // the foreign key pointing to that table has changed.
        foreach ($entityConfig['extraData'] as $extraDataDef) {
          if ($extraDataDef['table'] === $keyDef['table']) {
            $column = $extraDataDef['originating_table_column'];
            $keyChanged = (string) $oldRecord->$column !== (string) $newRecord->$column;
          }
        }
      }
      if ($keyChanged) {
        $eventTypes[] = 'S';
      }
    }
    if ($entity === 'occurrence'
        && $oldRecord->record_status !== $newRecord->record_status) {
      // Remove previuos verification and rejection workflow changes as the
      // record status is changing.
      $eventTypes[] = 'V';
      $eventTypes[] = 'R';
    }
    if (count($eventTypes) > 0) {
      // Must rewind as the field value which triggered a rule has been changed.
      $fieldRewinds = self::getRewindChangesForRecords($db, $entity, [$oldRecord->id], $eventTypes);
      if (isset($fieldRewinds["$entity.$oldRecord->id"])) {
        foreach ($fieldRewinds["$entity.$oldRecord->id"] as $field => $value) {
          // Apply the rewind changes to a clone of the old record as well as
          // the new record so we have the record in 3 states:
          // 1) original
          // 2) original after rewind
          // 3) updated after rewind.
          $newRecord->$field = $value;
          $rewoundRecord->$field = $value;
        }
      }
    }
    return $rewoundRecord;
  }

  /**
   * Retrieves the record changes required when rewinding a set of event types against a set of records.
   *
   * @param object $db
   *   Database connection.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param array $entityIdList
   *   List of primary keys for records in the table identied by entity.
   * @param array $eventTypes
   *   List of event types to rewind ('S', 'V', 'R').
   *
   * @return array
   *   Associate array keyed by entity.entity_id, containing an array of the
   *   fields with undo values to apply.
   */
  public static function getRewindChangesForRecords($db, $entity, array $entityIdList, array $eventTypes) {
    $r = [];
    $undoRecords = $db
      ->select('DISTINCT workflow_undo.id, workflow_undo.entity_id, workflow_undo.original_values')
      ->from('workflow_undo')
      ->where([
        'workflow_undo.entity' => $entity,
        'workflow_undo.active' => 't',
      ])
      ->in('event_type', $eventTypes)
      ->in('entity_id', $entityIdList)
      ->orderby('workflow_undo.id', 'DESC')
      ->get();
    foreach ($undoRecords as $undoRecord) {
      kohana::log('debug', "Applying rewind to $entity.$undoRecord->entity_id for undo $undoRecord->id");
      $unsetColumns = json_decode($undoRecord->original_values, TRUE);
      if (!isset($r["$entity.$undoRecord->entity_id"])) {
        $r["$entity.$undoRecord->entity_id"] = $unsetColumns;
      }
      else {
        $r["$entity.$undoRecord->entity_id"] = array_merge($r["$entity.$undoRecord->entity_id"], $unsetColumns);
      }
      // As this is a hard rewind, disable the undo data.
      $db->update('workflow_undo', ['active' => 'f'], ['id' => $undoRecord->id]);
    }
    return $r;
  }

  /**
   * Retrieves workflow events applicable to a particular list of records for a given set of event types.
   *
   * @param object $db
   *   Database connection.
   * @param int $websiteId
   *   ID of the website the update is associated with.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param array $entityIdList
   *   List of IDs for records in the entity table.
   * @param array $eventTypes
   *   List of event types to include in the results.
   *
   * @return array
   *   List of records with events attached (keyed by entity.id), with each
   *   entry containing an array of the events associated with that record.
   */
  public static function getEventsForRecords($db, $websiteId, $entity, array $entityIdList, array $eventTypes) {
    $r = [];
    $entityConfig = self::getEntityConfig($entity);
    if (empty($entityConfig)) {
      // Entity not configured for workflow.
      return $r;
    }
    $groupCodes = self::getGroupCodesForThisWebsite($websiteId);
    if (empty($groupCodes)) {
      // Operation's website does not belong to a workflow group.
      return $r;
    }
    $table = inflector::plural($entity);
    foreach ($entityConfig['keys'] as $keyDef) {
      $qry = $db
        ->select('workflow_events.key_value, workflow_events.event_type, workflow_events.mimic_rewind_first, ' .
          "workflow_events.values, $table.id as {$entity}_id")
        ->from('workflow_events')
        ->where([
          'workflow_events.deleted' => 'f',
          'key' => $keyDef['db_store_value'],
        ])
        ->in('group_code', $groupCodes)
        ->in('workflow_events.event_type', $eventTypes);
      if ($keyDef['table'] === $entity) {
        $column = $keyDef['column'];
        $qry->join($table, "$table.$column", 'workflow_events.key_value');
      }
      else {
        $qry->join($keyDef['table'], "$keyDef[table].$keyDef[column]", 'workflow_events.key_value');
        // Cross reference to the extraData for the same table to find the
        // field name which matches $newRecord->column.
        foreach ($entityConfig['extraData'] as $extraDataDef) {
          if ($extraDataDef['table'] === $keyDef['table']) {
            $qry->join(
              $table,
              "$table.$extraDataDef[originating_table_column]",
              "$extraDataDef[table].$extraDataDef[target_table_column]"
            );
          }
        }
      }
      $qry->in("$table.id", $entityIdList);
      $events = $qry->get();
      kohana::log('debug', 'getEventsForRecords query: ' . $db->last_query());
      foreach ($events as $event) {
        $idField = "{$entity}_id";
        if (!isset($r["$entity.{$event->$idField}"])) {
          $r["$entity.{$event->$idField}"] = [];
        }
        $r["$entity.{$event->$idField}"][] = $event;
      }
    }
    return $r;
  }

  /**
   * Applies workflow event record value changes applicable to this record.
   *
   * @param object $db
   *   Database connection.
   * @param int $websiteId
   *   ID of the website the update is associated with.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param object $oldRecord
   *   ORM object containing the old record details.
   * @param object $newRecord
   *   ORM Validation object containing the new record details.
   */
  public static function applyWorkflow($db, $websiteId, $entity, $oldRecord, $rewoundRecord, &$newRecord) {
    $state = [];
    $groupCodes = self::getGroupCodesForThisWebsite($websiteId);
    $entityConfig = self::getEntityConfig($entity);
    $rewoundValues = (array) $rewoundRecord;
    if (empty($groupCodes)) {
      // Operation's website does not belong to a workflow group so abort.
      return $state;
    }
    foreach ($entityConfig['keys'] as $keyDef) {
      // Apply state changes in 2 steps as order is important
      // First state change, oldRecord to rewoundRecord. Not necessary if
      // oldRecord and rewoundRecord are the same.
      if ($oldRecord->as_array() != $rewoundValues) {
        $qry = self::buildEventQueryForKey($db, $groupCodes, $entity, $oldRecord, $rewoundRecord, $keyDef);
        self::applyEventsQueryToRecord($qry, $entity, $rewoundValues, $newRecord, $state);
        kohana::log('error', 'EventsQuery oldToRewound: ' . $db->last_query());
      }
      // Second state change, rewoundRecord to newRecord.
      $qry = self::buildEventQueryForKey($db, $groupCodes, $entity, $rewoundRecord, $newRecord, $keyDef);
      self::applyEventsQueryToRecord($qry, $entity, $rewoundValues, $newRecord, $state);
    }
    return $state;
  }

  /**
   * Construct a query to retrieve workflow events.
   *
   * Constructs a query object which will find all the events applicable to the
   * current record for a given key in the entity's configuration.
   *
   * @param object $db
   *   Database connection.
   * @param array $groupCodes
   *   List of workflow groups to get events for.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param object $oldRecord
   *   ORM object containing the old record details.
   * @param object $newRecord
   *   ORM Validation object containing the new record details.
   * @param array $keyDef
   *   Configuration for the key we are building the query for.
   *
   * @return object
   *   Query object.
   */
  private static function buildEventQueryForKey($db, array $groupCodes, $entity, $oldRecord, $newRecord, array $keyDef) {
    $entityConfig = self::getEntityConfig($entity);
    $eventTypes = [];
    $qry = $db
      ->select('workflow_events.id, workflow_events.event_type, workflow_events.mimic_rewind_first, workflow_events.values, ' .
          'workflow_events.attrs_filter_term, workflow_events.location_ids_filter')
      ->from('workflow_events')
      ->where([
        'workflow_events.deleted' => 'f',
        'key' => $keyDef['db_store_value'],
      ])
      ->in('group_code', $groupCodes);
    if ($keyDef['table'] === $entity) {
      $column = $keyDef['column'];
      $qry->where('workflow_events.key_value', $newRecord->$column);
      // It's a set event if the key is changing in the main entity table.
      if ((string) $newRecord->$column !== (string) $oldRecord->$column) {
        $eventTypes[] = 'S';
      }
    }
    else {
      $qry->join($keyDef['table'], "$keyDef[table].$keyDef[column]", 'workflow_events.key_value');
      // Cross reference to the extraData for the same table to find the field
      // name which matches $newRecord->column.
      foreach ($entityConfig['extraData'] as $extraDataDef) {
        if ($extraDataDef['table'] === $keyDef['table']) {
          $originatingColumn = $extraDataDef['originating_table_column'];
          $qry->where(
            "$extraDataDef[table].$extraDataDef[target_table_column]",
            $newRecord->$originatingColumn
          );
          // It's a set event if the foreign key in the main data table which
          // points to the extraData record holding the key is changing.
          if ((string) $newRecord->$originatingColumn !== (string) $oldRecord->$originatingColumn) {
            $eventTypes[] = 'S';
          }
        }
      }

      // It's a set event if the record is being inserted.
      if (empty($newRecord->id)) {
        $eventTypes[] = 'S';
      }
      // Occurrence specific record status change events.
      if ($entity === 'occurrence' && isset($newRecord->record_status)
        && $newRecord->record_status !== $oldRecord->record_status) {
        if ($newRecord->record_status === 'V') {
          $eventTypes[] = 'V';
        }
        elseif ($newRecord->record_status === 'R') {
          $eventTypes[] = 'R';
        }
        // @todo Consider unverifying? Should rewind just the verification?
      }
      // Occurrence specific record status change events.
      if ($entity === 'occurrence' && isset($newRecord->release_status)
        && $newRecord->release_status !== $oldRecord->release_status) {
        // Translate Released to Fully released event type - other codes are the same.
        $eventTypes[] = ($newRecord->release_status === 'R') ? 'F' : $newRecord->release_status;
      }
      $qry->in('workflow_events.event_type', $eventTypes);
    }
    return $qry;
  }

  /**
   * Finds configured groups which the current operation's website uses the workflow for.
   *
   * @param int $websiteId
   *   Website ID.
   *
   * @return array
   *   List of group names.
   */
  private static function getGroupCodesForThisWebsite($websiteId) {
    $config = kohana::config('workflow_groups', FALSE, FALSE);
    $r = [];
    if ($config) {
      foreach ($config['groups'] as $group => $groupDef) {
        if (in_array($websiteId, $groupDef['member_website_ids'])) {
          $r[] = $group;
        }
      }
    }
    return $r;
  }

  /**
   * Applies the events query results to a record.
   *
   * Applies the field value changes determined by a query against the
   * workflow_events table to the contents of a record that is about to be
   * saved.
   *
   * @param object $qry
   *   Query object set up to retrieve the events to apply.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param array $oldValues
   *   Array containing the old record values, to allow undo state data to be
   *   retrieved.
   * @param object $newRecord
   *   ORM Validation object containing the new record details.
   * @param array $state
   *   State data to pass through to the post-process hook, containing undo
   *   data.
   */
  private static function applyEventsQueryToRecord($qry, $entity, array $oldValues, &$newRecord, array &$state) {
    $events = $qry->get();
    foreach ($events as $event) {
      kohana::log('debug', 'Processing event: ' . var_export($event, TRUE));
      $needsFilterCheck = !empty($event->attrs_filter_term) || !empty($event->location_ids_filter);
      $valuesToApply = self::processEvent(
        $event,
        $needsFilterCheck,
        $entity,
        $oldValues,
        $newRecord->as_array(),
        $state
      );
      foreach ($valuesToApply as $column => $value) {
        $newRecord->$column = $value;
      }
    }
  }

  /**
   * Processes a single workflow event.
   *
   * Retrieves a list of the values that need to be applied to a database
   * record given an event. The values may include the results of a mimiced
   * rewind as well as the value changes required for the event.
   *
   * @param object $event
   *   Event object loaded from the database query.
   * @param bool $needsFilterCheck
   *   If true, then the workflow event has an attribute or location filter
   *   that needs double checking via a work queue task.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param array $oldValues
   *   Array of the record values before the save operation. Used to retrieve
   *   values for undo state data.
   * @param array $newValues
   *   Array of the record values that were submitted to be saved, causing the
   *   event to fire.
   * @param array $state
   *   Array of undo state data which will be updated by this method to allow
   *   any proposed changes to be undone.
   *
   * @return array
   *   Associative array of the database fields and values which need to be
   *   applied.
   */
  public static function processEvent($event, $needsFilterCheck, $entity, array $oldValues, array $newValues, array &$state) {
    $entityConfig = self::getEntityConfig($entity);
    $columnDeltaList = [];
    $valuesToApply = [];
    $newUndoRecord = [];
    $setColumns = json_decode($event->values, TRUE);
    if ($event->mimic_rewind_first === 't' && !empty($oldValues['id'])) {
      self::mimicRewind($entity, $oldValues['id'], $columnDeltaList, $state);
    }
    $columnDeltaList = array_merge($columnDeltaList, $setColumns);
    foreach ($columnDeltaList as $deltaColumn => $deltaValue) {
      if (isset($newValues[$deltaColumn])) {
        $undo_value = $newValues[$deltaColumn];
      }
      elseif (!empty($oldValues['id'])) {
        $undo_value = isset($oldValues[$deltaColumn]) ? $oldValues[$deltaColumn] : NULL;
      }
      else {
        $undo_value = NULL;
      }
      if ($undo_value === NULL && isset($entityConfig['defaults'][$deltaColumn])) {
        $undo_value = $entityConfig['defaults'][$deltaColumn];
      }

      if ($deltaValue !== $undo_value) {
        $newUndoRecord[$deltaColumn] = $undo_value;
        $valuesToApply[$deltaColumn] = $deltaValue;
      }
    }
    $state[] = [
      'event_id' => $event->id,
      'needs_filter_check' => $needsFilterCheck,
      'event_type' => $event->event_type,
      'old_data' => $newUndoRecord,
    ];
    return $valuesToApply;
  }

  /**
   * Returns true if the user is allowed to view the workflow config pages.
   *
   * @param object $auth
   *   Kohana authorisation object.
   *
   * @return bool
   *   True or False.
   */
  public static function allowWorkflowConfigAccess($auth) {
    $workflowAvailable = $auth->logged_in('CoreAdmin');
    if (!$workflowAvailable) {
      $config = kohana::config('workflow_groups', FALSE, FALSE);
      $r = [];
      if ($config) {
        foreach ($config['groups'] as $group => $groupDef) {
          $workflowAvailable = $auth->has_website_access('admin', $groupDef['owner_website_id']);
          if ($workflowAvailable) {
            break;
          }
        }
      }
    }
    return $workflowAvailable;
  }

  /**
   * Rewind a record.
   *
   * If an event wants to mimic a rewind to reset data to its original state,
   * then undoes all changes to the record caused by workflow.
   *
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param int $entityId
   *   Primary key of the record in the entity table.
   * @param array $columnDeltaList
   *   Array containing the field values that will be changed by the rewind.
   * @param array $state
   *   Undo state change data from events applied to the record on this
   *   transaction which may need to be rewound.
   */
  private static function mimicRewind($entity, $entityId, array &$columnDeltaList, array $state) {
    for ($i = count($state) - 1; $i >= 0; $i--) {
      foreach ($state[$i]['old_data'] as $unsetColumn => $unsetValue) {
        $columnDeltaList[$unsetColumn] = $unsetValue;
      }
    }
    $undoRecords = ORM::factory('workflow_undo')
      ->where([
        'entity' => $entity,
        'entity_id' => $entityId,
        'active' => 't',
      ])
      ->orderby('id', 'DESC')->find_all();
    foreach ($undoRecords as $undoRecord) {
      kohana::log('debug', 'mimic rewind record: ' . var_export($undoRecord->as_array(), TRUE));
      $unsetColumns = json_decode($undoRecord->original_values, TRUE);
      $columnDeltaList = array_merge($columnDeltaList, $unsetColumns);
    }
  }

}
