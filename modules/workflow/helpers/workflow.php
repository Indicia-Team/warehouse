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
 * @link http://code.google.com/p/indicia/
 */

/**
 * Helper class for workflow functionality.
 */
class workflow {

  /**
   * Retrieve the before and after data associated with a record being saved.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Kohana configuration data for this module.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param array|object $record
   *   Save data.
   *
   * @return array
   *   Array containing the record data being saved, along with any other associated records implied in the
   *   configuration, along with the previous versions of these when an existing record is being updated.
   */
  public static function getData($db, array $config, $entity, $record) {
    $retVal = array('record' => array($entity => ($record->as_array())), 'previous' => array($entity => FALSE));
    if (isset($record['id'])) {
      $old = $db->select('*')
        ->from(inflector::plural($entity))
        ->where('id', $record['id'])
        ->where('deleted', 'f')
        ->get()->as_array();
      if (count($old) === 1) {
        $retVal['previous'][$entity] = get_object_vars($old[0]);
      }
    }
    if (isset($config['entities'][$entity]) && isset($config['entities'][$entity]['extraData'])) {
      foreach ($config['entities'][$entity]['extraData'] as $tableDefn) {
        $new = $db->select('*')
          ->from($tableDefn['table'])
          ->where($tableDefn['target_table_column'], $record[$tableDefn['originating_table_column']])
          ->get()->as_array();
        $retVal['record'][$tableDefn['table']] = (count($new) === 1 ? get_object_vars($new[0]) : FALSE);
        if ($retVal['previous'][$entity]) {
          $old = $db->select('*')
            ->from($tableDefn['table'])
            ->where($tableDefn['target_table_column'], $retVal['previous'][$entity][$tableDefn['originating_table_column']])
            ->get()->as_array();
          $retVal['previous'][$tableDefn['table']] = (count($old) === 1 ? get_object_vars($old[0]) : FALSE);
        }
        else {
          $retVal['previous'][$tableDefn['table']] = FALSE;
        }
      }
    }
    return $retVal;
  }

  /**
   * Applies undo data to rewind a record to it's originally posted state.
   *
   * This occurs when a record is no longer of interest to the alert system, e.g. when redetermined to another species.
   *
   * @param object $db
   *   Database connection.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param array|object $record
   *   Save data.
   */
  public static function rewindRecord($db, $entity, &$record) {
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
        $record[$unsetColumn] = $unsetValue;
      }
      $db->update('workflow_undo', array('active' => 'f'), array('id' => $undoRecord->id));
    }
  }

  /**
   * Determins if a record matches the event's key data.
   *
   * @param object $db
   *   Database connection.
   * @param array $config
   *   Kohana configuration data for this module.
   * @param string $entity
   *   Name of the database entity being saved, e.g. occurrence.
   * @param string $event_type
   *   Type of event - U (unset), 'S' (set), 'V' (verify), 'R' (reject)
   * @param array $recordSet
   *   Record data being applied and previous record state information.
   * @param object $event
   *   Event information loaded from db.
   *
   * @return bool
   *   True if the record matches otherwise false.
   */
  public static function isThisRecord($db, array $config, $entity, $event_type, array $recordSet, $event) {
    $keyDefn = NULL;
    for ($i = 0; $i < count($config['entities'][$entity]['keys']); $i++) {
      if ($config['entities'][$entity]['keys'][$i]['db_store_value'] === $event->key) {
        $keyDefn = $config['entities'][$entity]['keys'][$i];
      }
    }
    if ($keyDefn === NULL) {
      kohana::log('error', 'KeyDefn not found');
      return FALSE;
    }
    // Set and Unset events are not entity specific.
    // Currently all events are triggered on transition.
    switch ($event_type) {
      // Unset.
      case 'U':
        if ($recordSet['previous'][$keyDefn['table']] !== FALSE &&
            $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] === $event->key_value &&
            $recordSet['record'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value) {
          return TRUE;
        }
        break;

      case 'S':
        // Set.
        if (($recordSet['previous'][$keyDefn['table']] === FALSE ||
            $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value) &&
            $recordSet['record'][$keyDefn['table']][$keyDefn['column']] === $event->key_value) {
          return TRUE;
        }
        break;

      case 'V':
        // Validated, occurrence specific.
        if ($entity === 'occurrence') {
          if ($recordSet['record'][$keyDefn['table']][$keyDefn['column']] === $event->key_value &&
              isset($recordSet['record'][$entity]['record_status']) &&
              $recordSet['record'][$entity]['record_status'] === 'V' &&
              ($recordSet['previous'][$entity] === FALSE ||
                $recordSet['previous'][$entity]['record_status'] !== 'V' ||
                $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value)) {
            return TRUE;
          }
        }
        break;

      case 'R':
        // Rejected, occurrence specific.
        if ($entity === 'occurrence') {
          if ($recordSet['record'][$keyDefn['table']][$keyDefn['column']] === $event->key_value &&
              isset($recordSet['record'][$entity]['record_status']) &&
              $recordSet['record'][$entity]['record_status'] === 'R' &&
              ($recordSet['previous'][$entity] === FALSE ||
                $recordSet['previous'][$entity]['record_status'] !== 'R' ||
                $recordSet['previous'][$keyDefn['table']][$keyDefn['column']] !== $event->key_value)) {
            return TRUE;
          }
        }
        break;
    }
    return FALSE;
  }

}
