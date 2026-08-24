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
        'target_occurrences AS MATERIALIZED',
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

  public function testWorkflowFilterWorkerRewindsOccurrenceAndSampleCaches() {
    if (!class_exists('task_workflow_event_check_filters', FALSE)) {
      require_once 'modules/workflow/helpers/task_workflow_event_check_filters.php';
    }
    $this->db->query(<<<SQL
      UPDATE occurrences
      SET sensitivity_precision=10000
      WHERE id=1
    SQL);
    $this->db->query(<<<SQL
      UPDATE cache_occurrences_nonfunctional
      SET sensitivity_precision=10000
      WHERE id=1
    SQL);
    $this->db->query(<<<SQL
      UPDATE cache_samples_functional
      SET sensitive=true
      WHERE id=1
    SQL);
    $eventId = $this->db->query(<<<SQL
      INSERT INTO workflow_events
        (group_code, entity, event_type, key, key_value, values,
         attrs_filter_term, attrs_filter_values, created_on, created_by_id,
         updated_on, updated_by_id)
      VALUES
        ('test', 'occurrence', 'S', 'taxa_taxon_list_external_key', 'TESTKEY',
         '{}', 'Test filter', ARRAY['matching'], now(), 1, now(), 1)
      RETURNING id
    SQL)->current()->id;
    $this->db->query(<<<SQL
      INSERT INTO workflow_undo
        (entity, entity_id, event_type, original_values, created_on, created_by_id)
      VALUES
        ('occurrence', 1, 'S', '{"sensitivity_precision":null}', now(), 1)
    SQL);
    $this->db->query(<<<SQL
      INSERT INTO work_queue
        (task, entity, record_id, cost_estimate, priority, params, claimed_by, created_on)
      VALUES
        ('task_workflow_event_check_filters', 'occurrence', 1, 30, 2,
         json_build_object('workflow_events.id', ?), 'workflow-test', now())
    SQL, [$eventId]);

    task_workflow_event_check_filters::process(
      $this->db,
      NULL,
      'workflow-test'
    );

    $occurrence = $this->db->query(<<<SQL
      SELECT o.sensitivity_precision AS occurrence_sensitivity,
        n.sensitivity_precision AS cache_sensitivity
      FROM occurrences o
      JOIN cache_occurrences_nonfunctional n ON n.id=o.id
      WHERE o.id=1
    SQL)->current();
    $sample = $this->db->query(
      'SELECT sensitive FROM cache_samples_functional WHERE id=1'
    )->current();

    $this->assertNull($occurrence->occurrence_sensitivity);
    $this->assertNull($occurrence->cache_sensitivity);
    $this->assertSame('f', (string) $sample->sensitive);
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
