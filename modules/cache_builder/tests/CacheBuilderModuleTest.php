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
   * Preserve entered grid notation without bypassing precision restrictions.
   *
   * @dataProvider provideOutputSpatialReferences
   */
  public function testOutputSpatialReference($entity, $operation, array $scenario, $expectedSref) {
    require_once 'modules/cache_builder/helpers/cache_builder.php';
    require_once 'modules/sref_osgb/helpers/osgb.php';
    $scenario += [
      'sref' => 'SU67NW',
      'system' => 'OSGB',
      'geometry_sref' => 'SU67NW',
      'privacy' => NULL,
      'sensitivity' => NULL,
      'confidential' => 'f',
      'sibling_sensitivity' => NULL,
      'sibling_confidential' => 'f',
      'precision' => NULL,
      'precision_type' => 'I',
      'latlong' => FALSE,
      'location_only' => FALSE,
      'no_occurrences' => FALSE,
    ];
    $wkt = osgb::sref_to_wkt($scenario['geometry_sref']);
    $this->db->query(
      'UPDATE samples
       SET entered_sref=?, entered_sref_system=?, privacy_precision=?,
         geom=ST_Transform(ST_GeomFromText(?, 27700), 900913), location_id=NULL
       WHERE id=1',
      [$scenario['sref'], $scenario['system'], $scenario['privacy'], $wkt]
    );
    $roundTrip = $this->db->query(
      'SELECT ST_HausdorffDistance(ST_Transform(geom, 27700),
         ST_GeomFromText(?, 27700)) AS error_metres
       FROM samples WHERE id=1',
      [$wkt]
    )->current();
    $this->assertLessThan(
      0.1,
      (float) $roundTrip->error_metres,
      'Fixture OSGB geometry must survive the 27700/900913 round trip within 0.1 m. '
        . 'Check the test database spatial_ref_sys definitions and PROJ datum/grid configuration.'
    );
    $this->db->query(
      'UPDATE occurrences SET sensitivity_precision=?, confidential=? WHERE id=1',
      [$scenario['sensitivity'], $scenario['confidential']]
    );
    $this->db->query(
      'UPDATE occurrences SET sensitivity_precision=?, confidential=? WHERE id=2',
      [$scenario['sibling_sensitivity'], $scenario['sibling_confidential']]
    );
    if ($scenario['latlong']) {
      $this->db->query(
        "UPDATE samples
         SET geom=ST_Centroid(geom), entered_sref_system='4326',
           entered_sref=ST_Y(ST_Transform(ST_Centroid(geom), 4326))::text
             || ', ' || ST_X(ST_Transform(ST_Centroid(geom), 4326))::text
         WHERE id=1"
      );
    }
    if ($scenario['location_only']) {
      $this->db->query(
        'UPDATE locations
         SET centroid_geom=(SELECT geom FROM samples WHERE id=1),
           centroid_sref=?, centroid_sref_system=?
         WHERE id=1',
        [$scenario['sref'], $scenario['system']]
      );
      $this->db->query(
        'UPDATE samples
         SET location_id=1, geom=NULL, entered_sref=NULL, entered_sref_system=NULL
         WHERE id=1'
      );
    }
    if ($scenario['no_occurrences']) {
      $this->db->query('UPDATE occurrences SET sample_id=2 WHERE sample_id=1');
    }
    if ($scenario['precision'] !== NULL) {
      $this->db->query(
        "UPDATE sample_attributes
         SET system_function='sref_precision', data_type=?, termlist_id=?
         WHERE id=1",
        [$scenario['precision_type'], $scenario['precision_type'] === 'L' ? 1 : NULL]
      );
      $intValue = $scenario['precision_type'] === 'I' ? $scenario['precision'] : NULL;
      $floatValue = $scenario['precision_type'] === 'F' ? $scenario['precision'] : NULL;
      if ($scenario['precision_type'] === 'L') {
        $this->db->query(
          'UPDATE cache_termlists_terms SET sort_order=? WHERE id=1',
          [$scenario['precision']]
        );
        $intValue = 1;
      }
      $this->db->query(
        'INSERT INTO sample_attribute_values
          (sample_id, sample_attribute_id, int_value, float_value,
           created_on, created_by_id, updated_on, updated_by_id)
         VALUES (1, 1, ?, ?, now(), 1, now(), 1)',
        [$intValue, $floatValue]
      );
    }

    $table = "cache_{$entity}_nonfunctional";
    if ($operation === 'insert') {
      $this->db->query("DELETE FROM $table WHERE id=1");
      cache_builder::insert($this->db, $entity, [1]);
    }
    else {
      $this->db->query(
        "UPDATE $table SET output_sref='stale', output_sref_system='stale' WHERE id=1"
      );
      cache_builder::update($this->db, $entity, [1]);
    }

    $cached = $this->db->query(
      "SELECT output_sref, output_sref_system FROM $table WHERE id=1"
    )->current();
    $this->assertNotFalse($cached);
    if (is_array($expectedSref)) {
      $this->assertContains(
        $cached->output_sref,
        $expectedSref,
        'The output must be a 10 m cell adjoining the reduced 1 km square centre.'
      );
    }
    else {
      $this->assertSame($expectedSref, $cached->output_sref);
    }
    $this->assertSame('OSGB', $cached->output_sref_system);
  }

  /**
   * Exercise each rule independently on both cache tables and write paths.
   */
  public static function provideOutputSpatialReferences() {
    $cases = [
      'north-west quadrant' => [[], 'SU67NW'],
      'north-east quadrant' => [
        ['sref' => 'SU67NE', 'geometry_sref' => 'SU67NE'], 'SU67NE',
      ],
      'south-west quadrant' => [
        ['sref' => 'SU67SW', 'geometry_sref' => 'SU67SW'], 'SU67SW',
      ],
      'south-east quadrant' => [
        ['sref' => 'SU67SE', 'geometry_sref' => 'SU67SE'], 'SU67SE',
      ],
      'tetrad' => [
        ['sref' => 'SU67A', 'geometry_sref' => 'SU67A'], 'SU67A',
      ],
      '10 metre square' => [
        ['sref' => 'SU60007500', 'geometry_sref' => 'SU60007500'], 'SU60007500',
      ],
      '1 metre square' => [
        ['sref' => 'SU6000075000', 'geometry_sref' => 'SU6000075000'], 'SU60007500',
      ],
      'latitude longitude point' => [
        ['geometry_sref' => 'SU6000075000', 'latlong' => TRUE], 'SU60007500',
      ],
      'different notation in same projection' => [
        ['sref' => '460000, 175000', 'system' => '27700',
          'geometry_sref' => 'SU60007500'],
        'SU60007500',
      ],
      'private' => [['privacy' => 10000], 'SU67'],
      'fully private' => [['privacy' => 0], 'SU67'],
      'sensitive occurrence' => [['sensitivity' => 10000], 'SU67'],
      'confidential with null sensitivity' => [['confidential' => 't'], 'SU67'],
      'confidential 10 metre square' => [
        ['sref' => 'SU60007500', 'geometry_sref' => 'SU60007500',
          'confidential' => 't'],
        // The 1 km centre is a 10 m grid intersection; projection and centroid
        // rounding can select any of the four adjoining cells.
        ['SU60507550', 'SU60497550', 'SU60507549', 'SU60497549'],
      ],
      'missing entered reference' => [['sref' => NULL], 'SU67'],
      'empty entered reference' => [['sref' => ''], 'SU67'],
      'location only' => [
        ['sref' => 'SU60007500', 'geometry_sref' => 'SU60007500',
          'location_only' => TRUE],
        'SU60007500',
      ],
      'coarser precision attribute' => [['precision' => 5001], 'SU'],
      'coarser floating point precision attribute' => [
        ['precision' => 5001.0, 'precision_type' => 'F'], 'SU',
      ],
      'coarser lookup precision attribute' => [
        ['precision' => 5001, 'precision_type' => 'L'], 'SU',
      ],
      'finer precision attribute preserves quadrant' => [['precision' => 500], 'SU67NW'],
      'finer floating point precision attribute preserves quadrant' => [
        ['precision' => 500.0, 'precision_type' => 'F'], 'SU67NW',
      ],
      'finer lookup precision attribute preserves quadrant' => [
        ['precision' => 500, 'precision_type' => 'L'], 'SU67NW',
      ],
      'precision attribute just too coarse for quadrant' => [['precision' => 501], 'SU67'],
      'equal precision attribute preserves 10 metre square' => [
        ['sref' => 'SU60007500', 'geometry_sref' => 'SU60007500', 'precision' => 5],
        'SU60007500',
      ],
      'precision attribute just too coarse for 10 metre square' => [
        ['sref' => 'SU60007500', 'geometry_sref' => 'SU60007500', 'precision' => 6],
        'SU600750',
      ],
      'sensitive sibling' => [['sibling_sensitivity' => 10000], 'SU67', 'SU67NW'],
      'confidential sibling' => [['sibling_confidential' => 't'], 'SU67', 'SU67NW'],
      'no occurrences' => [['no_occurrences' => TRUE], 'SU67NW'],
    ];
    $data = [];
    foreach ($cases as $name => $case) {
      foreach (['samples', 'occurrences'] as $entity) {
        if ($entity === 'occurrences' && !empty($case[0]['no_occurrences'])) {
          continue;
        }
        $expectedSref = $entity === 'occurrences' && isset($case[2]) ? $case[2] : $case[1];
        foreach (['insert', 'update'] as $operation) {
          $data["$entity $operation: $name"] = [$entity, $operation, $case[0], $expectedSref];
        }
      }
    }
    return $data;
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
