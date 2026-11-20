<?php

/**
 * @file
 * Work queue worker for reversing an insert-only import.
 */

defined('SYSPATH') or die('No direct script access.');

class task_import_reversal {

  public const BATCH_SIZE = 1;
  public const SELF_CLEANUP = TRUE;

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
