<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the ordered locking contract and work queue entry points.
 */
class Helper_Work_Queue_Cache_Tasks_Test extends TestCase {

  /**
   * Direct task workers which update the occurrence functional cache.
   *
   * @var string[]
   */
  private const OCCURRENCE_WORKERS = [
    'application/helpers/task_group_delete.php',
    'application/helpers/task_group_update_title.php',
    'application/helpers/task_users_website_apply_licence.php',
    'modules/cache_builder/helpers/task_cache_builder_attr_value_occurrence.php',
    'modules/cache_builder/helpers/task_cache_builder_attrs_occurrence.php',
    'modules/cache_builder/helpers/task_cache_builder_path_occurrence.php',
    'modules/cache_builder/helpers/task_cache_builder_post_move.php',
    'modules/cache_builder/helpers/task_cache_builder_taxonomy_occurrence.php',
    'modules/cache_builder/helpers/task_cache_builder_user_privacy.php',
    'modules/spatial_index_builder/helpers/task_spatial_index_builder_location.php',
    'modules/spatial_index_builder/helpers/task_spatial_index_builder_location_delete.php',
    'modules/spatial_index_builder/helpers/task_spatial_index_builder_occurrence.php',
    'modules/spatial_index_builder/helpers/task_spatial_index_builder_sample.php',
  ];

  public function testOccurrenceWorkersUseOrderedCacheLocks() {
    foreach (self::OCCURRENCE_WORKERS as $worker) {
      $source = file_get_contents($worker);
      $this->assertStringContainsString(
        'WITH target_occurrences AS MATERIALIZED',
        $source,
        "$worker must materialize occurrence targets"
      );
      $this->assertStringContainsString(
        'ORDER BY',
        $source,
        "$worker must order occurrence locks"
      );
      $this->assertMatchesRegularExpression(
        '/ORDER BY\s+\w+\.id/',
        $source,
        "$worker must order occurrence locks by ID"
      );
      $this->assertStringContainsString(
        'FOR UPDATE OF',
        $source,
        "$worker must lock occurrence rows before updating them"
      );
    }
  }

}

/**
 * Integration coverage for the queue and scheduled task entry points.
 */
class Helper_Work_Queue_Cache_Tasks_Integration_Test extends Indicia_DatabaseTestCase {

  /**
   * Database connection used by the application code.
   *
   * @var Database
   */
  private $db;

  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  public function setUp(): void {
    parent::setUp();
    $this->db = new Database();
    $this->db->query('DELETE FROM work_queue');
  }

  public function tearDown(): void {
    if ($this->db !== NULL) {
      $this->db->query('DELETE FROM work_queue');
    }
    parent::tearDown();
  }

  public function testAttributeWorkerProcessesAnUnorderedBatch() {
    $this->db->query(<<<SQL
      INSERT INTO occurrence_attribute_values
        (occurrence_id, occurrence_attribute_id, text_value,
         created_on, created_by_id, updated_on, updated_by_id)
      VALUES
        (1, 1, 'First value', now(), 1, now(), 1),
        (2, 1, 'Second value', now(), 1, now(), 1)
    SQL);
    $this->db->query(<<<SQL
      INSERT INTO work_queue
        (task, entity, record_id, cost_estimate, priority, created_on)
      VALUES
        ('task_cache_builder_attrs_occurrence', 'occurrence', 2, 30, 2, now()),
        ('task_cache_builder_attrs_occurrence', 'occurrence', 1, 30, 2, now())
    SQL);

    $queue = new WorkQueue();
    $queue->process($this->db, TRUE);

    $result = $this->db->query(<<<SQL
      SELECT count(*) AS count
      FROM work_queue
      WHERE task='task_cache_builder_attrs_occurrence'
      AND error_detail IS NOT NULL
    SQL)->current();
    $this->assertSame('0', (string) $result->count);

    $claimed = $this->db->query(<<<SQL
      SELECT count(*) AS count
      FROM work_queue
      WHERE task='task_cache_builder_attrs_occurrence'
      AND claimed_by IS NOT NULL
    SQL)->current();
    $this->assertSame('0', (string) $claimed->count);

    $remaining = $this->db->query(<<<SQL
      SELECT count(*) AS count
      FROM work_queue
      WHERE task='task_cache_builder_attrs_occurrence'
    SQL)->current();
    $this->assertSame('0', (string) $remaining->count);
  }

  public function testTaxonomyWorkerProcessesQueuedTaxonUpdates() {
    $this->db->query(<<<SQL
      INSERT INTO work_queue
        (task, entity, record_id, cost_estimate, priority, created_on)
      VALUES
        ('task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', 1, 30, 2, now())
    SQL);

    $queue = new WorkQueue();
    $queue->process($this->db, TRUE);

    $result = $this->db->query(<<<SQL
      SELECT count(*) AS count
      FROM work_queue
      WHERE task='task_cache_builder_taxonomy_occurrence'
      AND (claimed_by IS NOT NULL OR error_detail IS NOT NULL)
    SQL)->current();
    $this->assertSame('0', (string) $result->count);
  }

  public function testSpatialOccurrenceWorkerCopiesSampleLocations() {
    $this->db->query("UPDATE cache_samples_functional SET location_ids=ARRAY[1] WHERE id=1");
    $this->db->query("UPDATE cache_occurrences_functional SET location_ids=NULL WHERE id=1");
    $this->db->query(<<<SQL
      INSERT INTO work_queue
        (task, entity, record_id, cost_estimate, priority, created_on)
      VALUES
        ('task_spatial_index_builder_occurrence', 'occurrence', 1, 30, 2, now())
    SQL);

    $queue = new WorkQueue();
    $queue->process($this->db, TRUE);

    $locationIds = $this->db->query(
      'SELECT location_ids FROM cache_occurrences_functional WHERE id=1'
    )->current();
    $this->assertSame('{1}', (string) $locationIds->location_ids);

    $result = $this->db->query(<<<SQL
      SELECT count(*) AS count
      FROM work_queue
      WHERE task='task_spatial_index_builder_occurrence'
      AND (claimed_by IS NOT NULL OR error_detail IS NOT NULL)
    SQL)->current();
    $this->assertSame('0', (string) $result->count);
  }

  public function testScheduledTaskWorkQueueEntryPointCompletes() {
    if (!class_exists('Scheduled_Tasks_Controller', FALSE)) {
      require_once 'application/controllers/scheduled_tasks.php';
    }
    $previousTasks = $_GET['tasks'] ?? NULL;
    $_GET['tasks'] = 'work_queue';
    try {
      $controller = (new ReflectionClass('Scheduled_Tasks_Controller'))
        ->newInstanceWithoutConstructor();
      $controller->index();
      $remaining = $this->db->query(<<<SQL
        SELECT count(*) AS count
        FROM work_queue
        WHERE claimed_by IS NOT NULL OR error_detail IS NOT NULL
      SQL)->current();
      $this->assertSame('0', (string) $remaining->count);
    }
    finally {
      if ($previousTasks === NULL) {
        unset($_GET['tasks']);
      }
      else {
        $_GET['tasks'] = $previousTasks;
      }
    }
  }

}
