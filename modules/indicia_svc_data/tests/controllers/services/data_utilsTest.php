<?php

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Controllers_Services_Data_Utils_Test extends Indicia_DatabaseTestCase {

  protected $auth;

  public function getDataSet() {
    $ds1 =  new PHPUnit_Extensions_Database_DataSet_YamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  public function setup() {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();

    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    $this->auth['write_tokens']['persist_auth'] = true;
  }

  private function getResponse($url, $decodeJson = true) {
    Kohana::log('debug', "Making request to $url");
    $session = curl_init();
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($session);
    // valid json response will decode
    if ($decodeJson)
      $response = json_decode($response, true);
    Kohana::log('debug', "Received response " . print_r($response, TRUE));
    return $response;
  }

  public function testVerifyOccurrence() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data__Utils_Test::testVerifyOccurrence");
    $array = array(
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
    );
    $structure = array(
      'model' => 'sample',
      'subModels' => array(
        'occurrence' => array('fk' => 'sample_id'),
      ),
    );
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to sample 1 save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a sample did not return success response');

    $occId = $r['success'];
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
    $this->assertEquals('Automated test verification', $comment->comment, 'Saved comment incorrect for verification');
    $this->assertEquals('V', $comment->record_status, 'Saved comment status incorrect for verification');

  }

}
