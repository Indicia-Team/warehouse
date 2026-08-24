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

}