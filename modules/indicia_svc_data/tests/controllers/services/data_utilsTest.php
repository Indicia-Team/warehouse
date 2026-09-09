<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Controllers_Services_Data_Utils_Test extends Indicia_DatabaseTestCase {

  protected $auth;

  public function getDataSet() {
    $ds1 = new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  /**
   * Ensure report user has permissions.
   *
   * Since the Bulk verify test uses the Report Engine, ensure that the report
   * user has permissions to select from the tables.
   */
  public static function setUpBeforeClass(): void {
    $db = new Database();
    $db->query('GRANT USAGE ON SCHEMA indicia TO indicia_report_user;');
    $db->query('ALTER DEFAULT PRIVILEGES IN SCHEMA indicia GRANT SELECT ON TABLES TO indicia_report_user;');
    $db->query('GRANT SELECT ON ALL TABLES IN SCHEMA indicia TO indicia_report_user;');
  }

  public function setup(): void {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();

    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    $this->auth['write_tokens']['persist_auth'] = TRUE;
  }

  private function getResponse($url, $decodeJson = TRUE) {
    Kohana::log('debug', "Making request to $url");
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($session);
    // Valid json response will decode.
    if ($decodeJson) {
      $response = json_decode($response, TRUE);
    }
    Kohana::log('debug', "Received response " . print_r($response, TRUE));
    return $response;
  }

  private function createOccurrence() {
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => ['fk' => 'sample_id'],
      ],
    ];
    $submission = submission_builder::build_submission($array, $structure);
    $response = data_entry_helper::forward_post_to('sample', $submission, $this->auth['write_tokens']);
    $this->assertTrue(isset($response['success']), 'Submitting a sample did not return success response');
    return (int) $response['success'];
  }

  private function bulkVerify($params, array $extra = []) {
    return helper_base::http_post(
      helper_base::$base_url . 'index.php/services/data_utils/bulk_verify',
      array_merge([
        'report' => 'library/occurrences/filterable_explore_list',
        'params' => json_encode($params),
        'user_id' => 1,
      ], $extra, $this->auth['write_tokens'])
    );
  }


  private function bulkVerifyError(array $params) {
    try {
      $response = helper_base::http_post(
        helper_base::$base_url . 'index.php/services/data_utils/bulk_verify',
        array_merge($params, $this->auth['write_tokens']),
        FALSE
      );
      return $response['output'];
    }
    catch (\IForm\WarehouseRequestException $e) {
      return $e->responseBody;
    }
  }

  private function bulkEdit(array $updates, $occurrenceIds, array $options = []) {
    $response = helper_base::http_post(
      helper_base::$base_url . 'index.php/services/data_utils/bulk_edit',
      array_merge([
        'updates' => json_encode($updates),
        'options' => json_encode($options),
        'occurrence:ids' => is_array($occurrenceIds) ? implode(',', $occurrenceIds) : $occurrenceIds,
        'user_id' => 1,
      ], $this->auth['write_tokens'])
    );
    return json_decode($response['output'], TRUE);
  }

  private function bulkEditError(array $params) {
    try {
      $response = helper_base::http_post(
        helper_base::$base_url . 'index.php/services/data_utils/bulk_edit',
        array_merge($params, $this->auth['write_tokens']),
        FALSE
      );
      return $response['output'];
    }
    catch (\IForm\WarehouseRequestException $e) {
      return $e->responseBody;
    }
  }

  public function testVerifyOccurrence() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data__Utils_Test::testVerifyOccurrence");
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => ['fk' => 'sample_id'],
      ],
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to sample 1 save " . print_r($r, TRUE));
    $this->assertTrue(
      isset($r['success']),
      'Submitting a sample did not return success response'
    );

    $occId = (int) $r['success'];
    $r = helper_base::http_post(
      helper_base::$base_url . 'index.php/services/data_utils/single_verify',
      array_merge([
        'occurrence:id' => $occId,
        'occurrence:record_status' => 'V',
        'occurrence_comment:comment' => 'Automated test verification',
        'user_id' => 1,
      ], $this->auth['write_tokens'])
    );
    $occ = ORM::factory('occurrence', $occId);
    $this->assertEquals('V', $occ->record_status, 'Saved status incorrect for verification');
    $comment = ORM::factory('occurrence_comment', ['occurrence_id' => $occId]);
    $this->assertEquals(
      'Automated test verification',
      $comment->comment,
      'Saved comment incorrect for verification'
    );
    $this->assertEquals(
      'V',
      $comment->record_status,
      'Saved comment status incorrect for verification'
    );
    // Now test the cache has been updated.
    $sql = <<<SQL
      SELECT o.record_status, o.record_substatus, o.verified_on, onf.verifier
      FROM cache_occurrences_functional o
      JOIN cache_occurrences_nonfunctional onf on onf.id=o.id
      WHERE o.id=?
    SQL;
    $db = new Database();
    $c = $db->query($sql, [$occId])->result_array(FALSE);
    $this->assertEquals(1, count($c), 'Wrong number of cached occurrences found.');
    $this->assertEquals('V', $c[0]['record_status']);
    $this->assertEquals(NULL, $c[0]['record_substatus']);
    $this->assertEquals('admin, core', $c[0]['verifier']);
    $this->assertNotEquals(NULL, $c[0]['verified_on']);
  }

  public function testBulkVerifyOccurrence() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data__Utils_Test::testBulkVerifyOccurrence");
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => ['fk' => 'sample_id'],
      ],
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to sample 1 save " . print_r($r, TRUE));
    $this->assertTrue(
      isset($r['success']),
      'Submitting a sample did not return success response'
    );

    $occId = (int) $r['success'];
    // First, do a dry run.
    $r = helper_base::http_post(
      helper_base::$base_url . 'index.php/services/data_utils/bulk_verify',
      array_merge([
        'report' => 'library/occurrences/filterable_explore_list',
        'params' => json_encode([
          'occurrence_id' => $occId,
        ]),
        'occurrence:record_status' => 'V',
        'user_id' => 1,
        'dryrun' => 'true',
      ], $this->auth['write_tokens'])
    );
    // Check the dry run reports the correct record count and does no update.
    $this->assertEquals('1', $r['output']);
    $occ = ORM::factory('occurrence', $occId);
    $this->assertEquals(
      'C',
      $occ->record_status,
      'Saved status should not be changed for verification dry run.'
    );

    // Now, do a live run.
    $r = helper_base::http_post(
      helper_base::$base_url . 'index.php/services/data_utils/bulk_verify',
      array_merge([
        'report' => 'library/occurrences/filterable_explore_list',
        'params' => json_encode([
          'occurrence_id' => $occId,
        ]),
        'occurrence:record_status' => 'V',
        'user_id' => 1,
      ], $this->auth['write_tokens'])
    );
    $this->assertEquals('1', $r['output']);
    $occ = ORM::factory('occurrence', $occId);
    $this->assertEquals('V', $occ->record_status, 'Saved status incorrect for verification');
    $comment = ORM::factory('occurrence_comment', ['occurrence_id' => $occId]);
    $this->assertEquals(
      'This record is accepted',
      $comment->comment,
      'Saved comment incorrect for verification'
    );
    $this->assertEquals(
      'V',
      $comment->record_status,
      'Saved comment status incorrect for verification'
    );
    // Now test the cache has been updated.
    $sql = <<<SQL
      SELECT o.record_status, o.record_substatus, o.verified_on, onf.verifier
      FROM cache_occurrences_functional o
      JOIN cache_occurrences_nonfunctional onf on onf.id=o.id
      WHERE o.id=?
    SQL;
    $db = new Database();
    $c = $db->query($sql, [$occId])->result_array(FALSE);
    $this->assertEquals(1, count($c), 'Wrong number of cached occurrences found.');
    $this->assertEquals('V', $c[0]['record_status']);
    $this->assertEquals(NULL, $c[0]['record_substatus']);
    $this->assertEquals('admin, core', $c[0]['verifier']);
    $this->assertNotEquals(NULL, $c[0]['verified_on']);
  }

  public function testBulkVerifyRequiresParams() {
    $response = $this->bulkVerifyError([
      'report' => 'library/occurrences/filterable_explore_list',
      'user_id' => 1,
    ]);
    $this->assertContains('Missing params parameter.', $response);
  }

  public function testBulkVerifyRejectsInvalidParamsJson() {
    $response = $this->bulkVerifyError([
      'report' => 'library/occurrences/filterable_explore_list',
      'params' => '{',
      'user_id' => 1,
    ]);
    $this->assertContains('valid JSON object', $response);
  }

  public function testBulkVerifyDoesNotReverifyAlreadyVerifiedOccurrence() {
    $occurrenceId = $this->createOccurrence();
    $params = ['occurrence_id' => $occurrenceId];

    $response = $this->bulkVerify($params);
    $this->assertEquals('1', $response['output']);

    $response = $this->bulkVerify($params);
    $this->assertEquals('0', $response['output']);

    $db = new Database();
    $commentCount = $db->query(
      'SELECT count(*) FROM occurrence_comments WHERE occurrence_id=?',
      [$occurrenceId]
    )->current()->count;
    $this->assertEquals('1', $commentCount);
  }

  public function testBulkVerifyAppliesSubstatus() {
    $occurrenceId = $this->createOccurrence();
    $response = $this->bulkVerify(
      ['occurrence_id' => $occurrenceId],
      ['record_substatus' => '2']
    );
    $this->assertEquals('1', $response['output']);

    $occurrence = ORM::factory('occurrence', $occurrenceId);
    $this->assertEquals('V', $occurrence->record_status);
    $this->assertEquals('2', (string) $occurrence->record_substatus);
    $comment = ORM::factory('occurrence_comment', ['occurrence_id' => $occurrenceId]);
    $this->assertEquals('This record is accepted as considered correct', $comment->comment);
    $this->assertEquals('2', (string) $comment->record_substatus);
  }

  public function testBulkEditRejectsMalformedInputs() {
    $response = $this->bulkEditError([
      'updates' => '{',
      'options' => '{}',
      'occurrence:ids' => '1',
      'user_id' => 1,
    ]);
    $this->assertContains('updates parameter must contain a valid JSON object', $response);

    $response = $this->bulkEditError([
      'updates' => '{}',
      'options' => '[true]',
      'occurrence:ids' => '1',
      'user_id' => 1,
    ]);
    $this->assertContains('options parameter must contain a valid JSON object', $response);

    $response = $this->bulkEditError([
      'updates' => '{}',
      'options' => '{}',
      'occurrence:ids' => 'not-an-id',
      'user_id' => 1,
    ]);
    $this->assertContains('Invalid format for occurrence:ids parameter', $response);
  }

  public function testBulkEditUpdatesSampleAndResetsVerification() {
    $occurrenceId = $this->createOccurrence();
    $db = new Database();
    $db->query("UPDATE occurrences SET record_status='V', verified_by_id=1, verified_on=now() WHERE id=?", [$occurrenceId]);

    $response = $this->bulkEdit(['location_name' => 'Bulk edit test location'], $occurrenceId);
    $this->assertEquals('records edited', $response['action']);
    $this->assertEquals(1, $response['affected']['occurrences']);

    $occurrence = ORM::factory('occurrence', $occurrenceId);
    $this->assertEquals('C', $occurrence->record_status);
    $sample = ORM::factory('sample', $occurrence->sample_id);
    $this->assertEquals('Bulk edit test location', $sample->location_name);
  }

  public function testBulkEditCanSkipVerificationReset() {
    $occurrenceId = $this->createOccurrence();
    $db = new Database();
    $db->query("UPDATE occurrences SET record_status='V', verified_by_id=1, verified_on=now() WHERE id=?", [$occurrenceId]);

    $response = $this->bulkEdit(
      ['location_name' => 'Bulk edit skip reverify', 'skip_reverify' => TRUE],
      $occurrenceId
    );
    $this->assertEquals('records edited', $response['action']);

    $this->assertEquals('V', ORM::factory('occurrence', $occurrenceId)->record_status);
  }

}
