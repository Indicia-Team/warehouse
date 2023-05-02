<?php

/**
 * @file
 * Library with functionality for the Process Checker module.
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

const BATCH_LIMIT = 1000;

class ProcessChecker {

  private Database $db;

  /**
   * Run the next batch of records through process checking.
   *
   * @param Database $db
   *   Database connection.
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param bool $esEnabled
   *   Set to true if Elasticsearch documents are to be checked.
   */
  function process($db, $title, array $processItem, $esEnabled) {
    $this->db = new Database();
    if (empty($processItem['entity']) || !in_array($processItem['entity'], ['sample', 'occurrence'])) {
      kohana::log('error', "Missing or invalid entity value for Process Checker config item $title");
      return;
    }
    $allowedChecks = ['cache_presence', 'cache_absence', 'cache_spatial_index', 'cache_attributes', 'es_presence', 'es_absence'];
    if (empty($processItem['checks']) || count(array_diff($processItem['checks'], $allowedChecks)) > 0) {
      kohana::log('error', "Missing or invalid checks value for Process Checker config item $title");
      return;
    }
    if ((in_array('es_presence', $processItem['checks']) || in_array('es_absence', $processItem['checks'])) && empty($processItem['esEndpoint'])) {
      kohana::log('error', "Missing esEndpoint for Process Checker config item $title");
      return;
    }
    // Find the record at the top limit of our range.
    $baseQuery = $this->getBaseQuery($title, $processItem);
    $checkTail = $this->db->query("SELECT greatest(t.updated_on, v.updated_on) as updated_on $baseQuery ORDER BY greatest(t.updated_on, v.updated_on) OFFSET " . BATCH_LIMIT . " LIMIT 1")->current();
    if ($checkTail) {
      // We can't split within a set of records with the same updated_on. So an
      // updated_on date filter ensures we get all records up to the division
      // between 2 updated_on values.
      $defaultWheres = ["greatest(t.updated_on, v.updated_on)<='$checkTail->updated_on'"];
      $lastUpdatedOn = $checkTail->updated_on;
    }
    else {
      $defaultWheres = [];
      // Scanning to the end of the current set of records. Capture time - 1
      // second to ensure all records checked on next run with no potential
      // gaps.
      $date = new DateTime("now - 1 second", new DateTimeZone(date_default_timezone_get()));
      $lastUpdatedOn = $date->format('Y-m-d H:i:s');
    }
    $cacheMissing = $this->checkCacheTableRecordsPresent($title, $processItem, $defaultWheres);
    // Don't do anything else until the cache entries are populated.
    if (!$cacheMissing) {
      $this->checkCacheTableRecordsDeleted($title, $processItem, $defaultWheres, 'functional');
      $this->checkCacheTableRecordsDeleted($title, $processItem, $defaultWheres, 'nonfunctional');
      $this->checkSpatialIndexing($title, $processItem, $defaultWheres);
      $this->checkAttributes($title, $processItem, $defaultWheres);
      $this->checkEsDocumentPresence($title, $processItem, $defaultWheres);
      $this->checkEsDocumentAbsence($title, $processItem, $defaultWheres);
    }
    variable::set("processChecker-$title", $lastUpdatedOn);
  }

  /**
   * Returns the core part of the records selection query.
   *
   * Returns the FROM, JOIN and WHERE part of a query for selecting the records
   * (samples or occurrences) to be processed.
   *
   * @param string $title
   *   Configuration item title used to retrieve the progress variable.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param array $joinList
   *   List of any additional joins for the query.
   * @param array $whereList
   *   List of any additional where clauses for the query.
   */
  private function getBaseQuery($title, array $processItem, array $joinList = [], array $whereList = []) {
    $table = inflector::plural($processItem['entity']);
    if ($table === 'samples' && !empty($processItem['website_id'])) {
      $joinList[] = "JOIN surveys srv ON srv.id=s.survey_id AND srv.website_id=$processItem[website_id]";
    }
    if ($table === 'occurrences' && !empty($processItem['survey_id'])) {
      $joinList[] = "JOIN samples s ON s.id=o.sample_id AND s.survey_id=$processItem[survey_id]";
    }
    $joinList[] = "LEFT JOIN cache_{$table}_functional f ON f.id=t.id";
    $joinList[] = "LEFT JOIN cache_{$table}_nonfunctional nf ON nf.id=t.id";
    $joins = implode("\n", $joinList);
    if (!empty($processItem['start_record_id'])) {
      $whereList[] = "t.id >= $processItem[start_record_id]";
    }
    if (!empty($processItem['ignore_recent'])) {
      $whereList[] = "t.updated_on < now() - '$processItem[ignore_recent]'::interval";
    }
    $updatedOnFrom = variable::get("processChecker-$title");
    if ($updatedOnFrom) {
      $whereList[] = "t.updated_on > '$updatedOnFrom'";
    }
    // Need at least 1 where to keep valid query structure.
    if (empty($whereList)) {
      $whereList[] = '1=1';
    }
    $wheres = implode("\n AND ", $whereList);
    return <<<SQL
FROM $table t
LEFT JOIN $processItem[entity]_attribute_values v ON v.$processItem[entity]_id=t.id
$joins
WHERE $wheres
SQL;
  }

  /**
   * Checks presence of cache table records.
   *
   * Re-queues any missing cache_* table entries for the entity.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param array $defaultWheres
   *   Array of SQL where clauses to apply, e.g. a limit on updated_on if
   *   checking a range of records.
   *
   * @return bool
   *   True if some cache entries were found to be missing.
   */
  private function checkCacheTableRecordsPresent($title, array $processItem, array $defaultWheres) {
    if (in_array('cache_presence', $processItem['checks'])) {
      // Add a left join to skip if already queued.
      $joins = ["LEFT JOIN work_queue q ON q.task='task_cache_builder_update' AND q.entity='$processItem[entity]' AND q.record_id=t.id"];
      $wheres = array_merge($defaultWheres, [
        // Not already queued.
        'q.id IS NULL',
        // Not deleted.
        't.deleted=false',
        // Cache entry missing.
        '(f.id IS NULL OR nf.id IS NULL)',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, $joins, $wheres);
      $qry = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT DISTINCT 'task_cache_builder_update', '$processItem[entity]', t.id, 100, 3, now()
$baseQuery
SQL;
      $count = $this->db->query($qry)->count();
      kohana::log('debug', $qry);
      if ($count) {
        kohana::log('alert', "$count cache entries for $processItem[entity] were missing and have been re-queued for creation.");
      }
      return $count > 0;
    }
    return FALSE;
  }

  /**
   * Checks that the cache_* records are deleted if a record's is deleted.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param array $defaultWheres
   *   Array of SQL where clauses to apply, e.g. a limit on updated_on if
   *   checking a range of records.
   * @param string $cacheTableType
   *   Either functional or nonfunctional - table to check.
   */
  private function checkCacheTableRecordsDeleted($title, array $processItem, array $defaultWheres, $cacheTableType) {
    if (in_array('cache_absence', $processItem['checks'])) {
      $checkTableAlias = $cacheTableType === 'functional' ? 'f' : 'nf';
      $wheres = array_merge($defaultWheres, [
        // Cache entry present.
        "$checkTableAlias.id IS NOT NULL",
        // Record should be deleted.
        't.deleted=true',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, [], $wheres);
      // Now delete any cache entries that are present for records deleted in the raw data.
      $table = inflector::plural($processItem['entity']);
      $qry = <<<SQL
DELETE FROM cache_{$table}_{$cacheTableType}
WHERE id IN (
  SELECT t.id
  $baseQuery
)
SQL;
      $count = $this->db->query($qry)->count();
      kohana::log('debug', $qry);
      if ($count) {
        kohana::log('alert', "$count cache_{$table}_{$cacheTableType} entries for $processItem[entity] were incorrectly present and have been deleted.");
      }
    }
  }

  /**
   * Checks that cache_*_functional.location_ids is populated.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param array $defaultWheres
   *   Array of SQL where clauses to apply, e.g. a limit on updated_on if
   *   checking a range of records.
   */
  private function checkSpatialIndexing($title, array $processItem, array $defaultWheres) {
    if (in_array('cache_spatial_index', $processItem['checks'])) {
      // Add a left join to skip if already queued.
      $joins = ["LEFT JOIN work_queue q ON q.task='task_spatial_index_builder_$processItem[entity]' AND q.entity='$processItem[entity]' AND q.record_id=t.id"];
      $wheres = array_merge($defaultWheres, [
        // Not already queued.
        'q.id IS NULL',
        // Not spatially indexed.
        'f.location_ids is null',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, $joins, $wheres);
      $qry = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT DISTINCT 'task_spatial_index_builder_$processItem[entity]', '$processItem[entity]', t.id, 100, 3, now()
$baseQuery
SQL;
      $count = $this->db->query($qry)->count();
      kohana::log('debug', $qry);
      if ($count) {
        kohana::log('alert', "$count spatial index entries for $processItem[entity] were missing and have been re-queued.");
      }
    }
  }

  /**
   * Checks that cache_*_functional.attrs_json is populated.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param array $defaultWheres
   *   Array of SQL where clauses to apply, e.g. a limit on updated_on if
   *   checking a range of records.
   */
  private function checkAttributes($title, array $processItem, array $defaultWheres) {
    if (in_array('cache_attributes', $processItem['checks'])) {
      // Add a left join to skip if already queued.
      $joins = ["LEFT JOIN work_queue q ON q.task='task_cache_builder_attrs_$processItem[entity]' AND q.entity='$processItem[entity]' AND q.record_id=t.id"];
      $wheres = array_merge($defaultWheres, [
        // Not already queued.
        'q.id IS NULL',
        // Attributes not filled in.
        // @todo this could be more refined and check the attributes against the
        // actual data.
        'nf.attrs_json IS NULL',
        // Has at least 1 attribute value.
        'v.id IS NOT NULL',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, $joins, $wheres);
      $qry = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_$processItem[entity]', '$processItem[entity]', t.id, 100, 3, now()
$baseQuery
SQL;
      $count = $this->db->query($qry)->count();
      kohana::log('debug', $qry);
      if ($count) {
        kohana::log('alert', "$count attrs_json values for $processItem[entity] were missing and have been re-queued.");
      }
    }
  }

  private function checkEsDocumentPresence($title, array $processItem, array $defaultWheres) {
    if (in_array('es_presence', $processItem['checks'])) {
      $es = new RestApiElasticsearch($processItem['esEndpoint']);
      // @todo $es->proxyToEs();
    }
  }

  private function checkEsDocumentAbsence($title, array $processItem, array $defaultWheres) {
    if (in_array('es_absence', $processItem['checks'])) {
      $es = new RestApiElasticsearch($processItem['esEndpoint']);
      // @todo $es->proxyToEs();
    }
  }

}