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

}
