<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

/**
 * Integration tests for the cache builder module and its queue workers.
 *
 * These tests use the shared core fixture so the cache-builder SQL is executed
 * against PostgreSQL tables, rather than being checked as PHP strings only.
 */
class CacheBuilderModuleTest extends Indicia_DatabaseTestCase {

  /**
   * Database connection used by the cache-builder operations under test.
   *
   * @var Database
   */
  private $db;

  /**
   * Load the shared core database fixture used by the module tests.
   *
   * @return DbUDataSetYamlDataSet
   *   The database fixture.
   */
  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  /**
   * Open the database and ensure no queue tasks leak between tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->db = new Database();
    $this->db->query('DELETE FROM work_queue');
  }

  /**
   * Remove queue tasks created by a test before restoring the fixture.
   */
  public function tearDown(): void {
    if ($this->db !== NULL) {
      $this->db->query('DELETE FROM work_queue');
      foreach ([
        'cache_samples_functional',
        'cache_samples_nonfunctional',
        'cache_samples_sensitive',
        'cache_occurrences_functional',
        'cache_occurrences_nonfunctional',
      ] as $table) {
        $this->db->query("DELETE FROM $table WHERE id BETWEEN 900001 AND 910004");
      }
    }
    parent::tearDown();
  }

  /**
   * Verify the scheduled build executes the taxonomy rank update query.
   *
   * This covers the locked-occurrence SQL in the Ranks extra update, which is
   * not reached by inserting or updating one cache record directly.
   */
  public function testScheduledTaskBuildsAllCacheTables() {
    require_once 'modules/cache_builder/plugins/cache_builder.php';
    $tables = ['termlists_terms', 'taxa_taxon_lists', 'taxon_searchterms', 'samples', 'occurrences'];
    $originalPopulation = [];
    foreach ($tables as $table) {
      $name = "populated-$table";
      $originalPopulation[$name] = $this->db->query(
        'SELECT value FROM variables WHERE name=?',
        [$name]
      )->current();
    }
    try {
      foreach ($tables as $table) {
        variable::set("populated-$table", TRUE);
      }
      $this->db->query('DELETE FROM cache_taxon_searchterms');
      variable::set('populated-taxon_searchterms', FALSE);

      cache_builder_scheduled_task('1900-01-01', $this->db);

      $functional = $this->db->query(
        'SELECT taxa_taxon_list_external_key, taxon_path FROM cache_occurrences_functional WHERE id=1'
      )->current();
      $this->assertSame('TESTKEY', (string) $functional->taxa_taxon_list_external_key);
      $this->assertSame('{10000}', (string) $functional->taxon_path);
    }
    finally {
      foreach ($originalPopulation as $name => $row) {
        variable::delete($name);
        if ($row) {
          variable::set($name, json_decode($row->value)[0]);
        }
      }
    }
  }

  /**
   * Verify user privacy changes update both occurrence and sample caches.
   *
   * The occurrence assertion exercises the materialized locked-occurrences
   * relation used by the queue worker.
   */
  public function testUserPrivacyWorkerUpdatesLockedOccurrenceCache() {
    $this->db->query(<<<SQL
      UPDATE users
      SET allow_share_for_reporting=false,
        allow_share_for_peer_review=true,
        allow_share_for_verification=true,
        allow_share_for_data_flow=true,
        allow_share_for_moderation=true,
        allow_share_for_editing=true
      WHERE id=1
    SQL);
    $this->db->query(<<<SQL
      INSERT INTO work_queue
        (task, entity, record_id, cost_estimate, priority, created_on)
      VALUES
        ('task_cache_builder_user_privacy', 'user', 1, 100, 2, now())
    SQL);

    $queue = new WorkQueue();
    $queue->process($this->db, TRUE);

    $occurrence = $this->db->query(
      'SELECT blocked_sharing_tasks FROM cache_occurrences_functional WHERE id=1'
    )->current();
    $sample = $this->db->query(
      'SELECT blocked_sharing_tasks FROM cache_samples_functional WHERE id=1'
    )->current();
    $this->assertSame('{R}', (string) $occurrence->blocked_sharing_tasks);
    $this->assertSame('{R}', (string) $sample->blocked_sharing_tasks);
  }

  /**
   * Rebuild an occurrence when its live taxon cache row has been removed.
   */
  public function testOccurrenceInsertUsesSourceTaxonAsFallback() {
    require_once 'modules/cache_builder/helpers/cache_builder.php';
    $this->db->query('DELETE FROM cache_occurrences_functional WHERE id=1');
    $this->db->query('DELETE FROM cache_occurrences_nonfunctional WHERE id=1');
    $this->db->query('DELETE FROM cache_taxa_taxon_lists WHERE id=1');

    cache_builder::insert($this->db, 'occurrences', [1]);

    $functional = $this->db->query(
      'SELECT taxa_taxon_list_id, taxon_meaning_id, taxa_taxon_list_external_key,
        taxon_group_id, freshwater_flag, terrestrial_flag, non_native_flag
       FROM cache_occurrences_functional WHERE id=1'
    )->current();
    $this->assertSame('1', (string) $functional->taxa_taxon_list_id);
    $this->assertSame('10000', (string) $functional->taxon_meaning_id);
    $this->assertSame('TESTKEY', (string) $functional->taxa_taxon_list_external_key);
    $this->assertSame('1', (string) $functional->taxon_group_id);
    $this->assertNotNull($functional->freshwater_flag);
    $this->assertNotNull($functional->terrestrial_flag);
    $this->assertNotNull($functional->non_native_flag);
  }

  /**
   * Update an occurrence when its live taxon cache row has been removed.
   */
  public function testOccurrenceUpdateUsesSourceTaxonAsFallback() {
    require_once 'modules/cache_builder/helpers/cache_builder.php';
    $this->db->query('DELETE FROM cache_taxa_taxon_lists WHERE id=1');
    $this->db->query(
      "UPDATE cache_occurrences_functional
       SET taxon_meaning_id=NULL, taxa_taxon_list_external_key=NULL
       WHERE id=1"
    );

    cache_builder::update($this->db, 'occurrences', [1]);

    $functional = $this->db->query(
      'SELECT taxon_meaning_id, taxa_taxon_list_external_key
       FROM cache_occurrences_functional WHERE id=1'
    )->current();
    $this->assertSame('10000', (string) $functional->taxon_meaning_id);
    $this->assertSame('TESTKEY', (string) $functional->taxa_taxon_list_external_key);
  }

  /**
   * Verify deleting a sample removes cache rows for its entire subtree.
   */
  public function testDeletingSampleRemovesSubtreeCacheRows() {
    require_once 'modules/cache_builder/helpers/cache_builder.php';
    // Reuse the fixture records as a root/child/grandchild hierarchy.
    $this->db->query(
      'UPDATE samples SET parent_id=CASE id WHEN 2 THEN 1 WHEN 3 THEN 2 ELSE parent_id END
       WHERE id IN (1, 2, 3)'
    );
    // The sensitive cache is separate from the functional/nonfunctional pair.
    $this->db->query(
      'INSERT INTO cache_samples_sensitive (id) VALUES (1), (2), (3)'
    );

    // Deletion must remain immediate even when normal cache updates are queued.
    $originalDelayCacheUpdates = cache_builder::$delayCacheUpdates;
    cache_builder::$delayCacheUpdates = TRUE;
    try {
      cache_builder::delete($this->db, 'samples', [1]);
    }
    finally {
      cache_builder::$delayCacheUpdates = $originalDelayCacheUpdates;
    }

    foreach ([
      'cache_samples_functional',
      'cache_samples_nonfunctional',
      'cache_samples_sensitive',
    ] as $table) {
      $rows = $this->db->query(
        "SELECT count(*) AS count FROM $table WHERE id IN (1, 2, 3)"
      )->current();
      $this->assertSame('0', (string) $rows->count, $table);
    }
    foreach (['cache_occurrences_functional', 'cache_occurrences_nonfunctional'] as $table) {
      $rows = $this->db->query(
        "SELECT count(*) AS count FROM $table WHERE id IN (1, 2, 3)"
      )->current();
      $this->assertSame('0', (string) $rows->count, $table);
    }
  }

  /**
   * Verify deleting a sample removes its nested source records and queue work.
   *
   * The queue rows cover pending, claimed and failed work. The test also
   * verifies that an unrelated sample task is retained.
   */
  public function testDeletingSampleCascadesNestedRecordsAndQueueWork() {
    $occurrenceIds = [900001, 900002, 900003];
    $timestamp = '2026-09-21 12:00:00';

    // Build a three-level tree plus an unrelated root sample.
    foreach ([
      [900001, NULL],
      [900002, 900001],
      [900003, 900002],
      [900004, NULL],
    ] as [$sampleId, $parentId]) {
      $this->db->query(
        'INSERT INTO samples
          (id, survey_id, parent_id, date_start, date_end, date_type,
           created_on, created_by_id, updated_on, updated_by_id, deleted)
         VALUES (?, 1, ?, ?, ?, ?, ?, 1, ?, 1, false)',
        [$sampleId, $parentId, '2026-09-21', '2026-09-21', 'D', $timestamp, $timestamp]
      );
    }

    // Put one occurrence on each sample so every level is covered.
    foreach ($occurrenceIds as $occurrenceId) {
      $this->db->query(
        'INSERT INTO occurrences
          (id, sample_id, created_on, created_by_id, updated_on, updated_by_id,
           website_id, taxa_taxon_list_id, record_status, deleted)
         VALUES (?, ?, ?, 1, ?, 1, 1, 1, ?, false)',
        [$occurrenceId, $occurrenceId, $timestamp, $timestamp, 'C']
      );
    }

    // Exercise pending, claimed and failed work, while retaining unrelated work.
    $queueRows = [
      ['task_cache_builder_update', 'sample', 900001, NULL, NULL],
      ['task_cache_builder_attrs_sample', 'sample', 900002, 'worker-1', NULL],
      ['task_cache_builder_attrs_occurrence', 'occurrence', 900003, NULL, 'failed'],
      ['task_cache_builder_update', 'occurrence', 900001, NULL, NULL],
      ['task_cache_builder_update', 'sample', 900004, NULL, NULL],
    ];
    foreach ($queueRows as [$task, $entity, $recordId, $claimedBy, $errorDetail]) {
      $this->db->query(
        'INSERT INTO work_queue
          (task, entity, record_id, cost_estimate, priority, created_on,
           claimed_by, error_detail)
         VALUES (?, ?, ?, 10, 1, ?, ?, ?)',
        [$task, $entity, $recordId, $timestamp, $claimedBy, $errorDetail]
      );
    }

    // The source trigger should cascade through the tree and clean its queue work.
    $this->db->query(
      'UPDATE samples SET deleted=true, updated_on=?, updated_by_id=1 WHERE id=900001',
      [$timestamp]
    );

    $deletedSamples = $this->db->query(
      'SELECT count(*) AS count FROM samples WHERE id IN (900001, 900002, 900003) AND deleted=true'
    )->current();
    $this->assertSame('3', (string) $deletedSamples->count);

    $deletedOccurrences = $this->db->query(
      'SELECT count(*) AS count FROM occurrences WHERE id IN (900001, 900002, 900003) AND deleted=true'
    )->current();
    $this->assertSame('3', (string) $deletedOccurrences->count);

    $affectedQueueRows = $this->db->query(
      "SELECT count(*) AS count
       FROM work_queue
       WHERE (entity='sample' AND record_id IN (900001, 900002, 900003))
          OR (entity='occurrence' AND record_id IN (900001, 900002, 900003))"
    )->current();
    $this->assertSame('0', (string) $affectedQueueRows->count);

    $unrelatedQueueRows = $this->db->query(
      "SELECT count(*) AS count
       FROM work_queue
       WHERE entity='sample' AND record_id=900004"
    )->current();
    $this->assertSame('1', (string) $unrelatedQueueRows->count);
  }

  /**
   * Verify scheduled cache cleanup removes deleted samples and occurrences.
   */
  public function testScheduledCleanupRemovesDeletedTreeAndKeepsUnrelatedRows() {
    require_once 'modules/cache_builder/helpers/cache_builder.php';
    $sampleIds = [910001, 910002, 910003, 910004];
    $occurrenceIds = [910001, 910002, 910003, 910004];
    $timestamp = '2026-09-21 12:00:00';

    // The first three records form the deleted tree; the fourth is a control.
    foreach ([
      [910001, NULL],
      [910002, 910001],
      [910003, 910002],
      [910004, NULL],
    ] as [$sampleId, $parentId]) {
      $this->db->query(
        'INSERT INTO samples
          (id, survey_id, parent_id, date_start, date_end, date_type,
           created_on, created_by_id, updated_on, updated_by_id, deleted)
         VALUES (?, 1, ?, ?, ?, ?, ?, 1, ?, 1, ?)',
        [$sampleId, $parentId, '2026-09-21', '2026-09-21', 'D', $timestamp, $timestamp, $sampleId !== 910004 ? 't' : 'f']
      );
    }
    // Mark source records as already deleted, as they would be when scheduled
    // cache cleanup receives its needs_update_samples batch.
    foreach ($occurrenceIds as $occurrenceId) {
      $this->db->query(
        'INSERT INTO occurrences
          (id, sample_id, created_on, created_by_id, updated_on, updated_by_id,
           website_id, taxa_taxon_list_id, record_status, deleted)
         VALUES (?, ?, ?, 1, ?, 1, 1, 1, ?, ?)',
        [$occurrenceId, $occurrenceId, $timestamp, $timestamp, 'C', $occurrenceId !== 910004 ? 't' : 'f']
      );
    }

    // Seed all three sample cache variants for both affected and control rows.
    foreach ($sampleIds as $sampleId) {
      $this->db->query(
        'INSERT INTO cache_samples_functional (id) VALUES (?)',
        [$sampleId]
      );
      $this->db->query(
        'INSERT INTO cache_samples_nonfunctional (id) VALUES (?)',
        [$sampleId]
      );
      $this->db->query(
        'INSERT INTO cache_samples_sensitive (id) VALUES (?)',
        [$sampleId]
      );
    }
    // Seed both occurrence cache variants at every level.
    foreach ($occurrenceIds as $occurrenceId) {
      $this->db->query(
        'INSERT INTO cache_occurrences_functional (id, sample_id) VALUES (?, ?)',
        [$occurrenceId, $occurrenceId]
      );
      $this->db->query(
        'INSERT INTO cache_occurrences_nonfunctional (id) VALUES (?)',
        [$occurrenceId]
      );
    }

    // Simulate the scheduled cache-builder batch for the deleted tree.
    $this->db->query(
      'CREATE TEMPORARY TABLE needs_update_samples (id integer PRIMARY KEY, deleted boolean)'
    );
    $this->db->query(
      'INSERT INTO needs_update_samples (id, deleted) VALUES (910001, true), (910002, true), (910003, true)'
    );
    cache_builder::makeChanges($this->db, 'samples');

    foreach ([
      'cache_samples_functional',
      'cache_samples_nonfunctional',
      'cache_samples_sensitive',
    ] as $table) {
      $rows = $this->db->query(
        "SELECT count(*) AS count FROM $table WHERE id IN (910001, 910002, 910003)"
      )->current();
      $this->assertSame('0', (string) $rows->count, $table);
      $this->assertSame('1', (string) $this->db->query(
        "SELECT count(*) AS count FROM $table WHERE id=910004"
      )->current()->count, $table);
    }
    foreach (['cache_occurrences_functional', 'cache_occurrences_nonfunctional'] as $table) {
      $rows = $this->db->query(
        "SELECT count(*) AS count FROM $table WHERE id IN (910001, 910002, 910003)"
      )->current();
      $this->assertSame('0', (string) $rows->count, $table);
      $this->assertSame('1', (string) $this->db->query(
        "SELECT count(*) AS count FROM $table WHERE id=910004"
      )->current()->count, $table);
    }
  }

}
