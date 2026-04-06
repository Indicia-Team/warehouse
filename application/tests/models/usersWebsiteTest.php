<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

/**
 * Tests deferred users_websites licence propagation behaviour.
 */
class Models_Users_Website_Test extends Indicia_DatabaseTestCase {

  protected $auth;

  protected $db;

  public function getDataSet() {
    $ds1 = new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  public function setup(): void {
    parent::setUp();

    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    $this->auth['write_tokens']['persist_auth'] = TRUE;

    $this->db = new Database();
    // Ensure queue assertions are not affected by leftovers from other tests.
    $this->db->query("DELETE FROM work_queue WHERE task='task_users_website_apply_licence'");
  }

  public function testNoQueueWhenLicenceUnchanged() {
    $result = $this->submitUsersWebsiteUpdate([]);
    $this->assertNotNull($result, 'users_website update should save when no licence changes.');
    $this->assertEquals(0, $this->getQueuedLicenceTaskCount(), 'No task should be queued when licences are unchanged.');
  }

  /**
   * First-time defaults should queue null-only backfill updates.
   */
  public function testFirstTimeLicenceQueuesNullOnlyUpdates() {
    [$restrictiveId, $permissiveId] = $this->prepareLicenceOrder();

    $this->db->query('UPDATE users_websites SET licence_id=NULL, media_licence_id=NULL WHERE user_id=1 AND website_id=1');
    $this->db->query('UPDATE samples SET licence_id=? WHERE id=2', [$restrictiveId]);
    $this->db->query('UPDATE cache_occurrences_functional SET licence_id=? WHERE id=2', [$restrictiveId]);

    $sampleMediaNullId = $this->createMediaRecord('sample_medium', 'sample_id', 1, 'null-sm.jpg', NULL);
    $sampleMediaSetId = $this->createMediaRecord('sample_medium', 'sample_id', 2, 'set-sm.jpg', $restrictiveId);
    $occMediaNullId = $this->createMediaRecord('occurrence_medium', 'occurrence_id', 1, 'null-om.jpg', NULL);
    $occMediaSetId = $this->createMediaRecord('occurrence_medium', 'occurrence_id', 2, 'set-om.jpg', $restrictiveId);
    $locMediaNullId = $this->createMediaRecord('location_medium', 'location_id', 1, 'null-lm.jpg', NULL);
    $locMediaSetId = $this->createMediaRecord('location_medium', 'location_id', 1, 'set-lm.jpg', $restrictiveId);

    $result = $this->submitUsersWebsiteUpdate([
      'licence_id' => $permissiveId,
      'media_licence_id' => $permissiveId,
    ]);
    $this->assertNotNull($result, 'users_website update should save when setting first-time default licences.');

    $this->assertEquals(1, $this->getQueuedLicenceTaskCount(), 'Expected a queued task after first-time licence assignment.');

    $q = new WorkQueue();
    $q->process($this->db, TRUE);

    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM samples WHERE id=1')->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM samples WHERE id=2')->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM cache_occurrences_functional WHERE id=1')->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM cache_occurrences_functional WHERE id=2')->current()->licence_id);

    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM sample_media WHERE id=?', [$sampleMediaNullId])->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM sample_media WHERE id=?', [$sampleMediaSetId])->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM occurrence_media WHERE id=?', [$occMediaNullId])->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM occurrence_media WHERE id=?', [$occMediaSetId])->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM location_media WHERE id=?', [$locMediaNullId])->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM location_media WHERE id=?', [$locMediaSetId])->current()->licence_id);
  }

  /**
   * With apply flag, changing to a more permissive licence updates all rows.
   */
  public function testApplyFlagAllowsPermissiveChangeForAllExistingData() {
    [$restrictiveId, $permissiveId] = $this->prepareLicenceOrder();

    $this->db->query('UPDATE users_websites SET licence_id=?, media_licence_id=? WHERE user_id=1 AND website_id=1', [$restrictiveId, $restrictiveId]);
    $this->db->query('UPDATE samples SET licence_id=NULL WHERE id=1');
    $this->db->query('UPDATE samples SET licence_id=? WHERE id=2', [$restrictiveId]);
    $this->db->query('UPDATE cache_occurrences_functional SET licence_id=NULL WHERE id=1');
    $this->db->query('UPDATE cache_occurrences_functional SET licence_id=? WHERE id=2', [$restrictiveId]);

    $sampleMediaId = $this->createMediaRecord('sample_medium', 'sample_id', 2, 'perm-sm.jpg', $restrictiveId);
    $occMediaId = $this->createMediaRecord('occurrence_medium', 'occurrence_id', 2, 'perm-om.jpg', $restrictiveId);
    $locMediaId = $this->createMediaRecord('location_medium', 'location_id', 1, 'perm-lm.jpg', $restrictiveId);

    $result = $this->submitUsersWebsiteUpdate([
      'licence_id' => $permissiveId,
      'media_licence_id' => $permissiveId,
    ], [
      'apply_licence_to_existing_records' => TRUE,
      'apply_licence_to_existing_media' => TRUE,
    ]);
    $this->assertNotNull($result, 'users_website update should save when applying a more permissive licence.');

    $q = new WorkQueue();
    $q->process($this->db, TRUE);

    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM samples WHERE id=1')->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM samples WHERE id=2')->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM cache_occurrences_functional WHERE id=1')->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM cache_occurrences_functional WHERE id=2')->current()->licence_id);

    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM sample_media WHERE id=?', [$sampleMediaId])->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM occurrence_media WHERE id=?', [$occMediaId])->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM location_media WHERE id=?', [$locMediaId])->current()->licence_id);
  }

  /**
   * Records flag only updates samples/occurrence cache, leaving media unchanged.
   */
  public function testRecordsApplyFlagOnlyUpdatesExistingRecords() {
    [$restrictiveId, $permissiveId] = $this->prepareLicenceOrder();

    $this->db->query('UPDATE users_websites SET licence_id=?, media_licence_id=? WHERE user_id=1 AND website_id=1', [$restrictiveId, $restrictiveId]);
    $this->db->query('UPDATE samples SET licence_id=? WHERE id=2', [$restrictiveId]);
    $this->db->query('UPDATE cache_occurrences_functional SET licence_id=? WHERE id=2', [$restrictiveId]);

    $sampleMediaId = $this->createMediaRecord('sample_medium', 'sample_id', 2, 'records-only-sm.jpg', $restrictiveId);
    $occMediaId = $this->createMediaRecord('occurrence_medium', 'occurrence_id', 2, 'records-only-om.jpg', $restrictiveId);

    $result = $this->submitUsersWebsiteUpdate([
      'licence_id' => $permissiveId,
      'media_licence_id' => $permissiveId,
    ], [
      'apply_licence_to_existing_records' => TRUE,
      'apply_licence_to_existing_media' => FALSE,
    ]);
    $this->assertNotNull($result, 'users_website update should save with records-only apply flag.');

    $q = new WorkQueue();
    $q->process($this->db, TRUE);

    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM samples WHERE id=2')->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM cache_occurrences_functional WHERE id=2')->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM sample_media WHERE id=?', [$sampleMediaId])->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM occurrence_media WHERE id=?', [$occMediaId])->current()->licence_id);
  }

  /**
   * Media flag only updates media tables, leaving record licences unchanged.
   */
  public function testMediaApplyFlagOnlyUpdatesExistingMedia() {
    [$restrictiveId, $permissiveId] = $this->prepareLicenceOrder();

    $this->db->query('UPDATE users_websites SET licence_id=?, media_licence_id=? WHERE user_id=1 AND website_id=1', [$restrictiveId, $restrictiveId]);
    $this->db->query('UPDATE samples SET licence_id=? WHERE id=2', [$restrictiveId]);
    $this->db->query('UPDATE cache_occurrences_functional SET licence_id=? WHERE id=2', [$restrictiveId]);

    $sampleMediaId = $this->createMediaRecord('sample_medium', 'sample_id', 2, 'media-only-sm.jpg', $restrictiveId);
    $occMediaId = $this->createMediaRecord('occurrence_medium', 'occurrence_id', 2, 'media-only-om.jpg', $restrictiveId);

    $result = $this->submitUsersWebsiteUpdate([
      'licence_id' => $permissiveId,
      'media_licence_id' => $permissiveId,
    ], [
      'apply_licence_to_existing_records' => FALSE,
      'apply_licence_to_existing_media' => TRUE,
    ]);
    $this->assertNotNull($result, 'users_website update should save with media-only apply flag.');

    $q = new WorkQueue();
    $q->process($this->db, TRUE);

    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM samples WHERE id=2')->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM cache_occurrences_functional WHERE id=2')->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM sample_media WHERE id=?', [$sampleMediaId])->current()->licence_id);
    $this->assertEquals($permissiveId, (int) $this->db->query('SELECT licence_id FROM occurrence_media WHERE id=?', [$occMediaId])->current()->licence_id);
  }

  /**
   * Legacy combined flag is ignored when split flags are absent.
   */
  public function testLegacyApplyFlagIgnoredWithoutSplitFlags() {
    [$restrictiveId, $permissiveId] = $this->prepareLicenceOrder();

    $this->db->query('UPDATE users_websites SET licence_id=?, media_licence_id=? WHERE user_id=1 AND website_id=1', [$restrictiveId, $restrictiveId]);
    $this->db->query('UPDATE samples SET licence_id=? WHERE id=2', [$restrictiveId]);
    $sampleMediaId = $this->createMediaRecord('sample_medium', 'sample_id', 2, 'legacy-sm.jpg', $restrictiveId);

    $result = $this->submitUsersWebsiteUpdate([
      'licence_id' => $permissiveId,
      'media_licence_id' => $permissiveId,
    ], [
      'apply_licence_to_existing_data' => TRUE,
    ]);
    $this->assertNotNull($result, 'users_website update should still save when only legacy apply flag is submitted.');

    $q = new WorkQueue();
    $q->process($this->db, TRUE);

    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM samples WHERE id=2')->current()->licence_id);
    $this->assertEquals($restrictiveId, (int) $this->db->query('SELECT licence_id FROM sample_media WHERE id=?', [$sampleMediaId])->current()->licence_id);
  }

  /**
   * With apply flag, changing to a more restrictive sample licence is blocked.
   */
  public function testApplyFlagRejectsMoreRestrictiveDefaultLicenceChange() {
    [$restrictiveId, $permissiveId] = $this->prepareLicenceOrder();
    $this->db->query('UPDATE users_websites SET licence_id=? WHERE user_id=1 AND website_id=1', [$permissiveId]);

    $result = $this->submitUsersWebsiteUpdate([
      'licence_id' => $restrictiveId,
    ], [
      'apply_licence_to_existing_records' => 't',
    ]);

    $this->assertNull($result, 'users_website update should fail when applying a more restrictive licence to existing data.');
    $this->assertEquals(0, $this->getQueuedLicenceTaskCount(), 'No task should be queued for invalid restrictive change.');
  }

  /**
   * With apply flag, changing to a more restrictive media licence is blocked.
   */
  public function testApplyFlagRejectsMoreRestrictiveDefaultMediaLicenceChange() {
    [$restrictiveId, $permissiveId] = $this->prepareLicenceOrder();
    $this->db->query('UPDATE users_websites SET media_licence_id=? WHERE user_id=1 AND website_id=1', [$permissiveId]);

    $result = $this->submitUsersWebsiteUpdate([
      'media_licence_id' => $restrictiveId,
    ], [
      'apply_licence_to_existing_media' => 1,
    ]);

    $this->assertNull($result, 'users_website update should fail when applying a more restrictive media licence to existing data.');
    $this->assertEquals(0, $this->getQueuedLicenceTaskCount(), 'No task should be queued for invalid restrictive media change.');
  }

  /**
   * Pick a restrictive->permissive licence pair based on configured ordering.
   *
   * @return int[]
   *   [restrictiveId, permissiveId]
   */
  private function prepareLicenceOrder() {
    $licences = $this->db->query('SELECT id FROM licences WHERE deleted=false ORDER BY permissiveness_sort_order LIMIT 2')->result_array(FALSE);
    $this->assertCount(2, $licences, 'At least 2 licences are required for this test.');
    $restrictiveId = (int) $licences[0]['id'];
    $permissiveId = (int) $licences[1]['id'];
    return [$restrictiveId, $permissiveId];
  }

  /**
   * Submit an update to the existing users_website record under test.
   *
   * @param array $fieldValues
   *   users_websites field values to update.
    * @param array $metaFieldValues
    *   Optional metaFields (e.g. apply_licence_to_existing_records).
   *
   * @return int|null
   *   Saved record id on success, otherwise null.
   */
  private function submitUsersWebsiteUpdate(array $fieldValues, array $metaFieldValues = []) {
    $model = ORM::factory('users_website', [
      'user_id' => 1,
      'website_id' => 1,
    ]);
    $this->assertTrue(!empty($model->id), 'Fixture users_website record not found.');

    $fields = [
      'id' => ['value' => $model->id],
      'user_id' => ['value' => 1],
      'website_id' => ['value' => 1],
      'site_role_id' => ['value' => 1],
    ];
    foreach ($fieldValues as $key => $value) {
      $fields[$key] = ['value' => $value];
    }
    $submission = [
      'id' => 'users_website',
      'fields' => $fields,
    ];
    if (!empty($metaFieldValues)) {
      $submission['metaFields'] = [];
      foreach ($metaFieldValues as $key => $value) {
        $submission['metaFields'][$key] = ['value' => $value];
      }
    }

    $model->submission = $submission;
    return $model->submit();
  }

  /**
   * Create a media row for sample/occurrence/location media tests.
   *
   * @param string $modelName
   *   ORM model name, e.g. sample_medium.
   * @param string $fkField
   *   Foreign key field name.
   * @param int $fkValue
   *   Foreign key value.
   * @param string $path
   *   Media path.
   * @param int|null $licenceId
   *   Optional licence id.
   *
   * @return int
   *   Created media row id.
   */
  private function createMediaRecord($modelName, $fkField, $fkValue, $path, $licenceId = NULL) {
    $model = ORM::factory($modelName);
    $fields = [
      'website_id' => ['value' => 1],
      $fkField => ['value' => $fkValue],
      'path' => ['value' => $path],
    ];
    if ($licenceId !== NULL) {
      $fields['licence_id'] = ['value' => $licenceId];
    }
    $model->submission = [
      'id' => $modelName,
      'fields' => $fields,
    ];
    $result = $model->submit();
    $this->assertNotNull($result, "Failed creating $modelName record for test setup.");
    return (int) $result;
  }

  /**
   * Count queued users_website licence propagation jobs.
   */
  private function getQueuedLicenceTaskCount() {
    return (int) $this->db->query(
      "SELECT count(*) FROM work_queue WHERE task='task_users_website_apply_licence'"
    )->current()->count;
  }

}
