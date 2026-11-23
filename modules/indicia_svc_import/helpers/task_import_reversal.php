<?php

/**
 * @file
 * Work queue worker for reversing an insert-only import.
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Work queue worker that reverses an eligible insert-only occurrence import.
 */
class task_import_reversal {

  /**
   * Process one reversal task at a time.
   *
   * @var int
   */
  public const BATCH_SIZE = 1;

  /**
   * The worker removes a task from the queue after successful processing.
   *
   * @var bool
   */
  public const SELF_CLEANUP = TRUE;

  /**
   * Reverse the import claimed by this worker process.
   *
   * The import is rechecked while locked before any records are changed. All
   * changes, including removal of the queue task, are committed together.
   *
   * @param object $db
   *   Database connection.
   * @param object $taskType
   *   Work queue task type information. The worker interface supplies this
   *   value, although this worker does not need to inspect it.
   * @param string $procId
   *   Identifier of this work queue processing run, used to find its claimed
   *   task.
   *
   * @throws Throwable
   *   If the task cannot be decoded, is no longer eligible, or a database
   *   operation fails. The transaction is rolled back before the exception is
   *   rethrown.
   */
  public static function process($db, $taskType, $procId): void {
    $db->query('BEGIN');
    try {
      $task = $db->query(<<<SQL
        SELECT id, record_id, params
        FROM work_queue
        WHERE task = 'task_import_reversal' AND claimed_by = ?
        FOR UPDATE
        SQL, [$procId])->result_array()[0] ?? NULL;
      if ($task === NULL) {
        $db->query('ROLLBACK');
        return;
      }
      $params = json_decode($task->params, TRUE, 512, JSON_THROW_ON_ERROR);
      $mode = $params['mode'];
      $userId = $params['user_id'];
      $import = self::getEligibleImport($db, $params['guid'], $userId, TRUE);
      if (!$import || (int) $import->id !== (int) $task->record_id || (int) $import->website_id !== $params['website_id']
          || !in_array($mode, ['reverse_all', 'do_not_reverse_updated'], TRUE)) {
        throw new RuntimeException('Queued import is no longer eligible for reversal.');
      }
      self::reverseOccurrences($db, $import->import_guid, $userId, $mode);
      self::reverseSamples($db, $import->import_guid, $userId, $mode);
      $db->query(<<<SQL
        UPDATE imports
        SET reversed_on = now(), reversed_by_id = ?, reversible = false
        WHERE id = ?
        SQL, [$userId, (int) $import->id]);
      $db->query('DELETE FROM work_queue WHERE id = ?', [(int) $task->id]);
      $db->query('COMMIT');
    }
    catch (Throwable $e) {
      $db->query('ROLLBACK');
      throw $e;
    }
  }

  /**
   * Find an import that is safe to reverse for the supplied user.
   *
   * Only a non-deleted, reversible, insert-only occurrence import with no
   * updates, explicit record IDs, linked duplicate imports, or update-enabled
   * configuration is eligible. When checking a queued task, the optional lock
   * also verifies that all tagged records are still present.
   *
   * @param Database $db
   *   Database connection.
   * @param string $guid
   *   Import GUID.
   * @param int $userId
   *   ID of the user who created the import.
   * @param bool $lock
   *   Whether to lock the import row and perform the final tagged-record count
   *   check. Use this when the result will immediately be reversed.
   *
   * @return object|null
   *   The eligible import, containing its ID, website ID, and import GUID, or
   *   NULL when no eligible import exists.
   */
  public static function getEligibleImport(Database $db, string $guid, int $userId, bool $lock = FALSE): ?object {
    $lockClause = $lock ? 'FOR UPDATE OF i' : '';
    // Only the worker scans the tagged records during the final eligibility check.
    $countClause = $lock ? 'AND i.inserted >= (SELECT count(*) FROM occurrences o WHERE o.import_guid = i.import_guid)' : '';
    return $db->query(<<<SQL
      SELECT i.id, i.website_id, i.import_guid
      FROM imports i
      WHERE i.import_guid = ?
        AND i.created_by_id = ?
        AND i.entity = 'occurrence'
        AND i.deleted = false
        AND i.reversible = true
        AND i.reversed_on IS NULL
        AND i.updated = 0
        AND i.inserted > 0
        AND COALESCE(i.global_values->>'config:allowUpdates', '0') NOT IN ('1', 'true')
        AND NOT jsonb_exists_any(i.global_values::jsonb, ARRAY['sample:id', 'occurrence:id'])
        AND NOT EXISTS (
          SELECT 1
          FROM jsonb_path_query(i.mappings::jsonb, '$.**.warehouseField') field
          WHERE field IN ('"sample:id"', '"occurrence:id"')
        )
        AND NOT EXISTS (
          SELECT 1 FROM imports other
          WHERE other.import_guid = i.import_guid AND other.id <> i.id
        )
        $countClause
      $lockClause
      SQL, [$guid, $userId])->result_array()[0] ?? NULL;
  }

  /**
   * Mark imported occurrences as deleted and clear their caches.
   *
   * In `do_not_reverse_updated` mode, occurrences and samples changed after
   * the import are retained.
   *
   * @param Database $db
   *   Database connection.
   * @param string $guid
   *   Import GUID.
   * @param int $userId
   *   ID of the user performing the reversal.
   * @param string $mode
   *   Reversal mode: `reverse_all` or `do_not_reverse_updated`.
   */
  private static function reverseOccurrences(Database $db, string $guid, int $userId, string $mode): void {
    $params = [$userId, $guid];
    $unchanged = '';
    if ($mode === 'do_not_reverse_updated') {
      $unchanged = <<<SQL
        AND t.created_on = t.updated_on
        AND NOT EXISTS (
          SELECT 1 FROM samples s WHERE s.id = t.sample_id
            AND s.import_guid = ? AND s.deleted = false
            AND (s.created_on = s.updated_on) IS NOT TRUE
        )
        SQL;
      $params[] = $guid;
    }
    $db->query(<<<SQL
      WITH reversed AS (
        UPDATE occurrences t
        SET deleted = true, updated_on = now(), updated_by_id = ?
        WHERE t.import_guid = ? AND t.deleted = false
        $unchanged
        RETURNING t.id
      ), nonfunctional AS (
        DELETE FROM cache_occurrences_nonfunctional WHERE id IN (SELECT id FROM reversed)
      )
      DELETE FROM cache_occurrences_functional WHERE id IN (SELECT id FROM reversed)
      SQL, $params);
  }

  /**
   * Mark imported samples as deleted where they have no visible dependants.
   *
   * Samples with visible occurrences or child samples are retained so that
   * reversing an import cannot hide records belonging to another import or
   * attachment made after the import.
   *
   * @param Database $db
   *   Database connection.
   * @param string $guid
   *   Import GUID.
   * @param int $userId
   *   ID of the user performing the reversal.
   * @param string $mode
   *   Reversal mode: `reverse_all` or `do_not_reverse_updated`.
   */
  private static function reverseSamples(Database $db, string $guid, int $userId, string $mode): void {
    $unchanged = $mode === 'do_not_reverse_updated' ? 'AND t.created_on = t.updated_on' : '';
    // Keep visible dependants without locking out concurrent attachments.
    $db->query(<<<SQL
      WITH reversed AS (
        UPDATE samples t
        SET deleted = true, updated_on = now(), updated_by_id = ?
        WHERE t.import_guid = ? AND t.deleted = false
          AND NOT EXISTS (
            SELECT 1 FROM occurrences o WHERE o.sample_id = t.id AND o.deleted = false
          )
          AND NOT EXISTS (
            SELECT 1 FROM samples child WHERE child.parent_id = t.id AND child.deleted = false
          )
          $unchanged
        RETURNING t.id
      ), nonfunctional AS (
        DELETE FROM cache_samples_nonfunctional WHERE id IN (SELECT id FROM reversed)
      )
      DELETE FROM cache_samples_functional WHERE id IN (SELECT id FROM reversed)
      SQL, [$userId, $guid]);
  }

}
