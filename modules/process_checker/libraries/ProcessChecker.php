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

const BATCH_LIMIT = 5000;

/**
 * Library for checking that background processes worked.
 *
 * Picks up scenarios where background processes were incomplete, e.g. due to
 * server issues/downtime. Checks batches of occurrences and samples that they
 * are:
 * * added to or removed from cache tables correctly
 * * spatially indexed
 * * JSON attributes populated in cache tables
 * * added to or removed from Elasticsearch correctly.
 */
class ProcessChecker {

  /**
   * Database connection object.
   *
   * @var Database
   */
  private Database $db;

  /**
   * Track end date of range of records processed.
   *
   * @var string
   */
  private $lastUpdatedOn;

  /**
   * Run the next batch of records through process checking.
   *
   * @param Database $db
   *   Database connection.
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   */
  public function process(Database $db, $title, array $processItem) {
    if (!$this->validProcessItem($title, $processItem)) {
      return;
    }
    $this->db = $db;
    // Set defaults.
    $processItem = array_merge([
      'ignore_recent' => '1 hour',
    ], $processItem);
    $defaultWhereClauses = $this->getDefaultWhereClauses($title, $processItem);
    $cacheMissing = $this->checkCacheTableRecordsPresent($title, $processItem, $defaultWhereClauses);
    // Don't do anything else until the cache entries are populated.
    if (!$cacheMissing) {
      $this->checkCacheTableRecordsDeleted($title, $processItem, $defaultWhereClauses, 'functional');
      $this->checkCacheTableRecordsDeleted($title, $processItem, $defaultWhereClauses, 'nonfunctional');
      $this->checkSpatialIndexing($title, $processItem, $defaultWhereClauses);
      $this->checkAttributes($title, $processItem, $defaultWhereClauses);
      $this->checkEsDocumentPresence($title, $processItem, $defaultWhereClauses);
      $this->checkEsDocumentAbsence($title, $processItem, $defaultWhereClauses);
    }
    variable::set("processChecker-$title", $this->lastUpdatedOn);
  }

  /**
   * Check the process item configuration is valid.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry read from the config file.
   *
   * @return bool
   *   True if valid, else false.
   */
  private function validProcessItem($title, array $processItem) {
    if (empty($processItem['entity']) ||
        !in_array($processItem['entity'], ['sample', 'occurrence'])) {
      kohana::log('error', "Missing or invalid entity value for Process Checker config item $title");
      return FALSE;
    }
    $allowedChecks = [
      'cache_presence',
      'cache_absence',
      'cache_spatial_index',
      'cache_attributes',
      'es_presence',
      'es_absence',
    ];
    if (empty($processItem['checks']) || count(array_diff($processItem['checks'], $allowedChecks)) > 0) {
      kohana::log('error', "Missing or invalid checks value for Process Checker config item $title");
      return FALSE;
    }
    if (in_array('es_presence', $processItem['checks']) || in_array('es_absence', $processItem['checks'])) {
      if (empty($processItem['esEndpoint'])) {
        kohana::log('error', "Missing esEndpoint for Process Checker config item $title");
        return FALSE;
      }
      if (empty($processItem['esIdPrefix'])) {
        kohana::log('error', "Missing esIdPrefix for Process Checker config item $title");
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Build the where clauses that will apply to all records selection queries.
   *
   * E.g. the clauses that limit to a range of updated_on dates.
   *
   * @param string $title
   *   Configuration item title used to retrieve the progress variable.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   *
   * @return array
   *   SQL where clauses to be joined by AND.
   */
  private function getDefaultWhereClauses($title, array $processItem) {
    // Find the record at the top limit of our range.
    $updatedOnFrom = variable::get("processChecker-$title", FALSE, FALSE);
    $tailCheckWheres = $updatedOnFrom
      ? ["updated_on BETWEEN '$updatedOnFrom' AND now()-'$processItem[ignore_recent]'::interval"]
      : ["updated_on<=now()-'$processItem[ignore_recent]'::interval"];
    $baseQuery = $this->getBaseQuery($title, $processItem, [], [], $tailCheckWheres);
    $batchLimit = BATCH_LIMIT;
    $qry = <<<SQL
SELECT t.updated_on as updated_on
$baseQuery
ORDER BY t.updated_on
OFFSET $batchLimit
LIMIT 1
SQL;
    $checkTail = $this->db->query($qry)->current();
    if ($checkTail) {
      // We can't split within a set of records with the same updated_on. So an
      // updated_on date filter ensures we get all records up to the division
      // between 2 updated_on values.
      $this->lastUpdatedOn = $checkTail->updated_on;
    }
    else {
      // Scanning to the end of the current set of records. Capture time - 1
      // second to ensure all records checked on next run with no potential
      // gaps.
      $date = new DateTime("now - $processItem[ignore_recent]");
      $this->lastUpdatedOn = $date->format('Y-m-d H:i:s');
    }
    $updatedOnFrom = variable::get("processChecker-$title", FALSE, FALSE);
    if ($updatedOnFrom) {
      $defaultWhereClauses = ["t.updated_on BETWEEN '$updatedOnFrom' AND '$this->lastUpdatedOn'"];
    }
    else {
      $defaultWhereClauses = ["t.updated_on<='$this->lastUpdatedOn'"];
    }
    if (!empty($processItem['start_record_id'])) {
      $defaultWhereClauses[] = "t.id >= $processItem[start_record_id]";
    }
    return $defaultWhereClauses;
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
   * @param array $cacheJoins
   *   Array containing 'functional' to include a left join to
   *   cache_*_functional and 'nonfunctional' to include a left join to
   *   cache_*_nonfunctional.
   * @param array $joinList
   *   List of any additional joins for the query.
   * @param array $whereList
   *   List of any additional where clauses for the query.
   */
  private function getBaseQuery($title, array $processItem, array $cacheJoins = [], array $joinList = [], array $whereList = []) {
    $table = inflector::plural($processItem['entity']);
    if ($table === 'samples' && !empty($processItem['website_id'])) {
      $joinList[] = "JOIN surveys srv ON srv.id=s.survey_id AND srv.website_id=$processItem[website_id]";
    }
    if ($table === 'occurrences' && !empty($processItem['survey_id'])) {
      $joinList[] = "JOIN samples s ON s.id=o.sample_id AND s.survey_id=$processItem[survey_id]";
    }
    if (in_array('functional', $cacheJoins)) {
      $joinList[] = "LEFT JOIN cache_{$table}_functional f ON f.id=t.id";
    }
    if (in_array('nonfunctional', $cacheJoins)) {
      $joinList[] = "LEFT JOIN cache_{$table}_nonfunctional nf ON nf.id=t.id";
    }
    $joins = implode("\n", $joinList);
    // Need at least 1 where to keep valid query structure.
    if (empty($whereList)) {
      $whereList[] = '1=1';
    }
    $wheres = implode("\n AND ", $whereList);
    return <<<SQL
FROM $table t
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
      $baseQuery = $this->getBaseQuery(
        $title,
        $processItem,
        ['functional', 'nonfunctional'],
        $joins,
        $wheres
      );
      $qry = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT DISTINCT 'task_cache_builder_update', '$processItem[entity]', t.id, 100, 3, now()
$baseQuery
SQL;
      $count = $this->db->query($qry)->count();
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
      $baseQuery = $this->getBaseQuery($title, $processItem, [$cacheTableType], [], $wheres);
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
        // Not a deleted record.
        't.deleted=false',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, ['functional'], $joins, $wheres);
      $qry = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT DISTINCT 'task_spatial_index_builder_$processItem[entity]', '$processItem[entity]', t.id, 100, 3, now()
$baseQuery
SQL;
      $count = $this->db->query($qry)->count();
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
      $joins = [
        // Add a left join to skip if already queued.
        "LEFT JOIN work_queue q ON q.task='task_cache_builder_attrs_$processItem[entity]' AND q.entity='$processItem[entity]' AND q.record_id=t.id",
        // Has at least one attribute value.
        "JOIN $processItem[entity]_attribute_values v ON v.$processItem[entity]_id=t.id AND v.deleted=false",
      ];
      $wheres = array_merge($defaultWheres, [
        // Not already queued.
        'q.id IS NULL',
        // Attributes not filled in.
        // @todo this could be more refined and check the attributes against the
        // actual data.
        'nf.attrs_json IS NULL',
        // Not a deleted record.
        't.deleted=false',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, ['nonfunctional'], $joins, $wheres);
      $qry = <<<SQL
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_$processItem[entity]', '$processItem[entity]', t.id, 100, 3, now()
$baseQuery
SQL;
      $count = $this->db->query($qry)->count();
      if ($count) {
        kohana::log('alert', "$count attrs_json values for $processItem[entity] were missing and have been re-queued.");
      }
    }
  }

  /**
   * Checks the range of records to ensure they are on Elasticsearch.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param array $defaultWheres
   *   Array of SQL where clauses to apply, e.g. a limit on updated_on if
   *   checking a range of records.
   */
  private function checkEsDocumentPresence($title, array $processItem, array $defaultWheres) {
    if (in_array('es_presence', $processItem['checks'])) {
      $wheres = array_merge($defaultWheres, [
        't.deleted=false',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, [], [], $wheres);
      $qry = <<<SQL
SELECT DISTINCT t.id, t.updated_on
$baseQuery
ORDER BY t.updated_on
SQL;
      $idRows = $this->db->query($qry);
      $idBatch = [];
      foreach ($idRows as $idRow) {
        $idBatch[] = '"' . $processItem['esIdPrefix'] . $idRow->id . '"';
        if (count($idBatch) >= 100) {
          $this->checkBatchInEs($title, $idBatch, $processItem);
          $idBatch = [];
        }
      }
      if (!empty($idBatch)) {
        $this->checkBatchInEs($title, $idBatch, $processItem);
      }
    }
  }

  /**
   * Checks a range of deleted records to ensure they are not on Elasticsearch.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   * @param array $defaultWheres
   *   Array of SQL where clauses to apply, e.g. a limit on updated_on if
   *   checking a range of records.
   */
  private function checkEsDocumentAbsence($title, array $processItem, array $defaultWheres) {
    if (in_array('es_absence', $processItem['checks'])) {
      $wheres = array_merge($defaultWheres, [
        't.deleted=true',
      ]);
      $baseQuery = $this->getBaseQuery($title, $processItem, [], [], $wheres);
      $qry = <<<SQL
SELECT DISTINCT t.id, t.updated_on
$baseQuery
ORDER BY t.updated_on
SQL;
      $idRows = $this->db->query($qry);
      $idBatch = [];
      foreach ($idRows as $idRow) {
        $idBatch[] = '"' . $processItem['esIdPrefix'] . $idRow->id . '"';
        if (count($idBatch) >= 100) {
          $this->checkBatchInEs($title, $idBatch, $processItem);
          $idBatch = [];
        }
      }
      if (!empty($idBatch)) {
        $this->checkBatchNotInEs($title, $idBatch, $processItem);
      }
    }
  }

  /**
   * Retrieves doc IDs for a batch from ES.
   *
   * Used to check if present or absent and compare with PG deleted flag.
   *
   * @param array $idBatch
   *   List of ES document _id values to search for, each wrapped in double
   *   quotes.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   *
   * @return array
   *   ES response for the list of hits.
   */
  private function getDocIdsFromEs(array $idBatch, array $processItem) {
    $ids = implode(',', $idBatch);
    $esConfig = kohana::config('rest.elasticsearch');
    $thisProxyCfg = $esConfig[$processItem['esEndpoint']];
    $url = "$thisProxyCfg[url]/$thisProxyCfg[index]/_search?_source=_id";
    $request = <<<JSON
{
  "size": 100,
  "query": {
    "terms": {
      "_id": [$ids]
    }
  }
}
JSON;
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_POST, 1);
    curl_setopt($session, CURLOPT_POSTFIELDS, $request);
    curl_setopt($session, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
      $error = curl_error($session);
      kohana::log('error', 'ES process request failed: ' . $error . ': ' . json_encode($error));
      kohana::log('error', 'URL: ' . $url);
      kohana::log('error', 'Query: ' . $request);
      kohana::log('error', 'Response: ' . $response);
      curl_close($session);
      return [];
    }
    curl_close($session);
    $responseObj = json_decode($response);
    return $responseObj->hits->hits;
  }

  /**
   * Checks that a batch of records are present in ES.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $idBatch
   *   List of ES document _id values to search for, each wrapped in double
   *   quotes.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   */
  private function checkBatchInEs($title, $idBatch, $processItem) {
    $hits = $this->getDocIdsFromEs($idBatch, $processItem);

    foreach ($hits as $doc) {
      unset($idBatch[array_search('"' . $doc->_id . '"', $idBatch)]);
    }
    // Any remaining items in batch are missing docs. So update the tracking to
    // force them into ES.
    if (count($idBatch) > 0) {
      $toUpdate = array_map(fn($value): string => trim(str_replace($processItem['esIdPrefix'], '', $value), '"'), $idBatch);
      $updateStr = implode(',', $toUpdate);
      $table = inflector::plural($processItem['entity']);
      $qry = <<<SQL
  UPDATE cache_{$table}_functional SET website_id=website_id WHERE id IN ($updateStr);
  SQL;
      $this->db->query($qry);
      kohana::log('alert', count($toUpdate) . " missing documents were requeued for Elasticsearch for $title");
    }
  }

  /**
   * Checks that a batch of deleted records are not in ES.
   *
   * @param string $title
   *   Configuration item title used for log messages.
   * @param array $idBatch
   *   List of ES document _id values to search for, each wrapped in double
   *   quotes.
   * @param array $processItem
   *   Configuration entry for the set of records to check processing on.
   */
  private function checkBatchNotInEs($title, $idBatch, $processItem) {
    $hits = $this->getDocIdsFromEs($idBatch, $processItem);
    foreach ($hits as $doc) {
      if (array_search('"' . $doc->_id . '"', $idBatch)) {
        kohana::log('alert', "Deleting $doc->_id for process check $title");
        $this->deleteFromEs($doc->_id, $processItem);
        // Also delete the sensitive copy.
        $this->deleteFromEs("$doc->_id!", $processItem);
      }
    }
  }

  /**
   * Deletes a document from the ES index.
   *
   * @param string $_id
   *   Document _id.
   * @param array $processItem
   *   Configuration with details of ES connection.
   */
  private function deleteFromEs($_id, array $processItem) {
    $esConfig = kohana::config('rest.elasticsearch');
    $thisProxyCfg = $esConfig[$processItem['esEndpoint']];
    $url = "$thisProxyCfg[url]/$thisProxyCfg[index]/_doc/$_id";
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($session, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
      $error = curl_error($session);
      kohana::log('error', 'ES delete request failed: ' . $error . ': ' . json_encode($error));
      kohana::log('error', 'URL: ' . $url);
      kohana::log('error', 'Response: ' . $response);
    }
    curl_close($session);
  }

}
