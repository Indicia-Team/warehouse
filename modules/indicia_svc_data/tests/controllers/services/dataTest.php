<?php

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Controllers_Services_Data_Test extends Indicia_DatabaseTestCase {

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
    $this->auth['write_tokens']['persist_auth']=true;
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

  public function testRequestDataGetRecordByDirectId() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataGetRecordByDirectId");
    $params = array(
      'mode' => 'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce']
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location/1?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertFalse(isset($response['error']), "testRequestDataGetRecordByDirectId returned error. See log for details");
    $this->assertCount(1, $response, 'Data services get JSON for direct ID did not return 1 record.');
    $this->assertEquals('Test location', ($response[0]['name']), 'Data services get JSON for direct ID did not return correct record.');
  }

  public function testRequestDataGetRecordByIndirectId() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataGetRecordByIndirectId");
    $params = array(
      'mode' => 'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'id'=>1
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertFalse(isset($response['error']), "testRequestDataGetRecordByIndirectId returned error. See log for details");
    $this->assertCount(1, $response, 'Data services get JSON for indirect ID did not return 1 record.');
    $this->assertEquals('Test location', ($response[0]['name']), 'Data services get JSON for indirect ID did not return correct record.');
  }

  public function testRequestDataGetRecordByQueryIn() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataGetRecordByQueryIn");
    $params = array(
      'mode' => 'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'query'=>json_encode(array('in'=>array('id', array(1))))
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertFalse(isset($response['error']), "testRequestDataGetRecordByQueryIn returned error. See log for details");
    $this->assertCount(1, $response, 'Data services get JSON for in clause did not return 1 record.');
    $this->assertEquals('Test location', ($response[0]['name']), 'Data services get JSON for in clause did not return correct record.');

    // repeat test, for alternative format of in clause
    $params = array(
      'mode' => 'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'query'=>json_encode(array(
        'in'=>array('id'=>array(1), 'name'=>array('Test location')),
      ))
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertFalse(isset($response['error']), "testRequestDataGetRecordByQueryIn returned error. See log for details");
    $this->assertCount(1, $response, 'Data services get JSON for in clause did not return 1 record.');
    $this->assertEquals('Test location', ($response[0]['name']), 'Data services get JSON for in clause did not return correct record.');

    // Another test- which should return 0 records because we look for the wrong name
    $params = array(
      'mode' => 'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'query'=>json_encode(array(
        'in'=>array('id'=>array(1), 'name'=>array('UnitTests')),
      ))
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertFalse(isset($response['error']), "testRequestDataGetRecordByQueryIn returned error. See log for details");
    $this->assertCount(0, $response, 'Data services get JSON for in clause did not return 0 records.');
  }

  /**
   * Rapidly repeat some calls.
   */
  public function testRepeat50() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRepeat50");
    for ($i = 0; $i < 50; $i++) {
      self::testRequestDataGetRecordByIndirectId();
    }
  }

  /**
   * Utility function for simplifying a call to the taxa_search data services end-point in test cases.
   *
   * @param array $params
   *   Parameters to pass to the service.
   * @param int $count
   *   Expected record count.
   *
   * @return array
   *   Web service response;
   */
  private function checkTaxonSearchCount(array $params, $count) {
    $url = data_entry_helper::$base_url . 'index.php/services/data/taxa_search?' . http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertFalse(isset($response['error']), "testRequestDataTaxaSearchTaxonGroup returned error. See log for details");
    $this->assertCount($count, $response, 'Data services get JSON for taxa_search did not return correct record count.');
    return $response;
  }

  /**
   * Tests requests to the taxa_search data services endpoint.
   */
  public function testRequestDataTaxaSearch() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataTaxaSearch");
    $params = array(
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce'],
      'q' => 'test',
      'taxon_list_id' => 1,
    );
    $response = $this->checkTaxonSearchCount($params, 2);
    // Test filtering against preferred names.
    $params['preferred'] = 't';
    $this->checkTaxonSearchCount($params, 2);
    $params['preferred'] = 'true';
    $this->checkTaxonSearchCount($params, 2);
    $params['preferred'] = 'f';
    $this->checkTaxonSearchCount($params, 0);
    $params['preferred'] = 'false';
    $this->checkTaxonSearchCount($params, 0);
  }

  /**
   * Tests requests to the taxa_search data services endpoint which filter by taxon group.
   */
  public function testRequestDataTaxaSearchTaxonGroup() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataTaxaSearchTaxonGroup");
    $params = array(
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce'],
      'q' => 'test',
      'taxon_list_id' => 1,
      'taxon_group' => json_encode(['Test taxon group'])
    );
    $this->checkTaxonSearchCount($params, 2);
    // Test filtering against an incorrect group.
    $params['taxon_group'] = 'Wrong group';
    $this->checkTaxonSearchCount($params, 0);
    // Test filtering against an incorrect group, as above but using JSON array.
    $params['taxon_group'] = json_encode(['Wrong group']);
    $this->checkTaxonSearchCount($params, 0);
    // Test filtering against an incorrect group, as above but using JSON array to pass a bad group and a good group.
    $params['taxon_group'] = json_encode(['Wrong group', 'Test taxon group']);
    $this->checkTaxonSearchCount($params, 2);
  }

  public function testRequestDataTaxaSearchRank() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataTaxaSearch");
    $params = array(
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce'],
      'q' => 'test',
      'taxon_list_id' => 1
    );
    $params['min_taxon_rank_sort_order'] = 290;
    $response = $this->checkTaxonSearchCount($params, 2);
    $params['min_taxon_rank_sort_order'] = 300;
    $response = $this->checkTaxonSearchCount($params, 1);
    $params['min_taxon_rank_sort_order'] = 310;
    $response = $this->checkTaxonSearchCount($params, 0);
    unset($params['min_taxon_rank_sort_order']);
    $params['max_taxon_rank_sort_order'] = 280;
    $response = $this->checkTaxonSearchCount($params, 0);
    $params['max_taxon_rank_sort_order'] = 290;
    $response = $this->checkTaxonSearchCount($params, 1);
    $params['max_taxon_rank_sort_order'] = 300;
    $response = $this->checkTaxonSearchCount($params, 2);
  }

  public function testSave() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testSave");
    // Post a location with an attribute value.
    $array = array(
      'location:name' => 'UnitTest2',
      'location:centroid_sref' => 'SU0101',
      'location:centroid_sref_system' => 'osgb',
      'locAttr:1' => 'saveTestAttr'
    );
    $s = submission_builder::build_submission($array, array('model' => 'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to location save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');

    $locId = $r['success'];
    $loc = ORM::Factory('location', $locId);
    $locAttr = ORM::Factory('location_attribute_value')->where(array('location_id'=>$locId))->find();

    Kohana::log('debug', "Saved location " . print_r((new ArrayObject($loc))->offsetGet("\0*\0object"), TRUE));
    $this->assertEquals('UnitTest2', $loc->name, 'Saved location is not as expected');
    Kohana::log('debug', "Saved location attribute " . print_r((new ArrayObject($locAttr))->offsetGet("\0*\0object"), TRUE));
    $this->assertEquals('saveTestAttr', $locAttr->text_value, 'Saved attribute value is not as expected');

    // Re-post the same location, with a new name and attribute value.
    $array = array(
      'location:id' => $locId,
      'location:name' => 'UnitTest2-update',
      'location:centroid_sref' => 'SU0101',
      'location:centroid_sref_system' => 'osgb',
      'locAttr:1:' . $locAttr->id => 'saveTestAttr-update'
    );
    $s = submission_builder::build_submission($array, array('model' => 'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to location re-submit " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Re-submitting a location did not return success response');

    $loc->reload();
    $locAttr->reload();

    Kohana::log('debug', "Updated location " . print_r((new ArrayObject($loc))->offsetGet("\0*\0object"), TRUE));
    $this->assertEquals('UnitTest2-update', $loc->name, 'Re-submitted location is not as expected');
    Kohana::log('debug', "Updated location attribute " . print_r((new ArrayObject($locAttr))->offsetGet("\0*\0object"), TRUE));
    $this->assertEquals('saveTestAttr-update', $locAttr->text_value, 'Saved attribute value is not as expected');

    // Reepost the same location, with a deleted attribute value.
    $array = array(
      'location:id' => $locId,
      'location:name' => 'UnitTest2',
      'location:centroid_sref' => 'SU0101',
      'location:centroid_sref_system' => 'osgb',
      'locAttr:1:' . $locAttr->id => ''
    );
    $s = submission_builder::build_submission($array, array('model' => 'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to location attribute delete " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Deleting a location attribute did not return success response');

    $locAttr->reload();

    Kohana::log('debug', "Deleted location attribute " . print_r((new ArrayObject($locAttr))->offsetGet("\0*\0object"), TRUE));
    $this->assertEquals('t', $locAttr->deleted, 'Submitting a blank attribute value did not delete it');
    // now cleanup
    $locAttr->delete();
    ORM::Factory('location', $locId)->delete();
  }

  public function testSaveInt() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testSaveInt");
    // post a location with an invalid attribute value.
    $array = array(
      'location:name' => 'UnitTest2',
      'location:centroid_sref' => 'SU0101',
      'location:centroid_sref_system' => 'osgb',
      'locAttr:3' => 'not an int',
    );
    $s = submission_builder::build_submission($array, array('model' => 'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);
    Kohana::log('debug', "Submission response to invalid location attribute save " . print_r($r, TRUE));

    // check we got the required output - showing an error on the int field
    $this->assertTrue(isset($r['error']), 'Submitting a location with invalid int attr did not return error response');
    $this->assertTrue(isset($r['errors']['locAttr:3']), 'Submitting a location with invalid int attr did not return error for field');

    // now submit with a valid value
    $array = array(
      'location:name' => 'UnitTest2',
      'location:centroid_sref' => 'SU0101',
      'location:centroid_sref_system' => 'osgb',
      'locAttr:3' => 0,
    );
    $s = submission_builder::build_submission($array, array('model' => 'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to valid location attribute save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a location with int attr did not return success response');

    $locId = $r['success'];
    $locAttr = ORM::Factory('location_attribute_value')
      ->where([
        'location_id' => $locId,
        'location_attribute_id' => 3,
        'deleted' => 'f',
      ])
      ->find();

    Kohana::log('debug', "Valid location attribute " . print_r((new ArrayObject($locAttr))->offsetGet("\0*\0object"), TRUE));
    $this->assertEquals(0, $locAttr->int_value, 'Submitting a location with zero int attr did not save');

    // Set attr to empty to check it deletes the attribute value.
    $array = array(
      'location:id' => $locId,
      'location:name' => 'UnitTest2',
      'location:centroid_sref' => 'SU0101',
      'location:centroid_sref_system' => 'osgb',
      "locAttr:3:$locAttr->id" => '',
    );
    $s = submission_builder::build_submission($array, array('model' => 'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to location attribute delete " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a location with blank attr did not return success response');

    $db = ORM::Factory('location_attribute_value')
      ->where([
        'location_id' => $locId,
        'location_attribute_id' => 3,
        'deleted' => 'f',
      ]);
    $this->assertEquals(0, $db->count_all(), 'Submitting a location with blank attr did not delete the attr');
    // Cleanup.
    $locAttr->delete();
    ORM::Factory('location', $locId)->delete();
  }

  public function testCreateUser() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateUser");
    $array=array(
      'person:first_name' => 'Test',
      'person:surname' => 'Person',
      'person:email_address' => 'test123abc@example.com'
    );
    $s = submission_builder::build_submission($array, array('model' => 'person'));
    $r = data_entry_helper::forward_post_to('person', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to person save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a new person did not work');

    $personId = $r['success'];
    $array = [
      'user:person_id' => $personId,
      'user:core_role_id' => 1,
      'user:username' => 'testUser',
    ];
    $s = submission_builder::build_submission($array, array('model' => 'user'));
    $r = data_entry_helper::forward_post_to('user', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to user save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a new user did not work');
    // cleanup
    $userId = $r['success'];
    ORM::Factory('user', $userId)->delete();
    ORM::Factory('person', $personId)->delete();
  }

  public function testCreateFilter() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateFilter");
    // post a filter
    $filterData=array(
      'filter:title' => 'Test',
      'filter:description' => 'Test descrtiption',
      'filter:definition' => '{"testfield":"testvalue"}',
      'filter:sharing' => 'V',
      'filter:defines_permissions' => 't',
      'filter:website_id' => 1,
    );
    $s = submission_builder::build_submission($filterData, array('model' => 'filter'));
    $r = data_entry_helper::forward_post_to('filter', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to filter save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a new filter did not work');

    $filterId = $r['success'];
    $filter = ORM::factory('filter', $filterId);
    $this->assertEquals($filter->title, $filterData['filter:title']);
    $this->assertEquals($filter->description, $filterData['filter:description']);
    $this->assertEquals($filter->definition, $filterData['filter:definition']);
    $this->assertEquals($filter->sharing, $filterData['filter:sharing']);
    $this->assertEquals($filter->defines_permissions, 't');
    $this->assertEquals($filter->website_id, $filterData['filter:website_id']);
  }

  public function testCreateGroup() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateGroup");
    // Post a group.
    $groupData = array(
      'group:title' => 'Test',
      'group:description' => 'Test descrtiption',
      'group:joining_method' => 'P',
      'group:code' => 'ABC123',
      'group:website_id' => 1,
      // Set to test group type term.
      'group:group_type_id' => 6,
    );
    $s = submission_builder::build_submission($groupData, array('model' => 'group'));
    $r = data_entry_helper::forward_post_to('group', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to group save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a new group did not work');

    $groupId = $r['success'];
    $group = ORM::factory('group', $groupId);
    $this->assertEquals($groupData['group:title'], $group->title);
    $this->assertEquals($groupData['group:description'], $group->description);
    $this->assertEquals($groupData['group:joining_method'], $group->joining_method);
    $this->assertEquals($groupData['group:code'], $group->code);
    $this->assertEquals($groupData['group:website_id'], $group->website_id);
  }

  public function testCreateSample() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateSample");
    // Post a location with an attribute value.
    $array = array(
      'sample:survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017'
    );
    $s = submission_builder::build_submission($array, array('model' => 'sample'));
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to sample 1 save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a sample did not return success response');

    $smpId = $r['success'];
    $smp = ORM::Factory('sample', $smpId);
    // Ensure date format correct way round.
    $this->assertEquals('2017-09-02', $smp->date_start, 'Saved sample 1 is not as expected');

    // Save another sample, setting the date via the vague date constituent parts.
    $array = array(
      'sample:survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date_start' => '03/09/2017',
      'sample:date_end' => '03/09/2017',
      'sample:date_type' => 'D'
    );
    $s = submission_builder::build_submission($array, array('model' => 'sample'));
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to sample 2 save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a sample 2 did not return success response');

    $smpId = $r['success'];
    $smp = ORM::Factory('sample', $smpId);
    // Ensure date format correct way round.
    $this->assertEquals('2017-09-03', $smp->date_start, 'Saved sample 2 is not as expected');

    // Save another sample, setting a broken vague date.
    $array = array(
      'sample:survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date_start' => '03/09/2016',
      'sample:date_end' => '02/09/2017',
      'sample:date_type' => 'Y'
    );
    $s = submission_builder::build_submission($array, array('model' => 'sample'));
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to sample 3 save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['error']), 'Submitting a sample wth bad vague date did not fail');
  }

  public function testCreateSampleComment() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateSampleComment");
    // Post a location with an attribute value.
    $array = array(
      'sample:survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'sample_comment:comment' => 'This is a test comment',
    );
    $s = submission_builder::build_submission($array, [
      'model' => 'sample',
      'subModels' => [
        'sample_comment' => ['fk' => 'sample_id'],
      ],
    ]);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a sample did not return success response');
    $sc = ORM::factory('sample_comment', ['sample_id' => $r['success']]);
    $this->assertTrue($sc->loaded, 'Record not found after submitting a comment');
    $this->assertEquals('This is a test comment', $sc->comment, 'Sample comment saved but incorrect comment saved');
  }

  public function testCreateOccurrence() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateOccurrence");
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
        'occurrence' => array('fk' => 'sample_id')
      )
    );
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    Kohana::log('debug', "Submission response to sample 1 save " . print_r($r, TRUE));
    $this->assertTrue(isset($r['success']), 'Submitting a sample did not return success response');
  }

  public function testCreateOccurrenceComment() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateOccurrenceComment");
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU123456',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
    ];
    $s = submission_builder::build_submission($array, array('model' => 'sample'));
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);
    $smpId = $r['success'];
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'occurrence:sample_id' => $smpId,
      'occurrence:taxa_taxon_list_id' => 1,
      // Deliberately test some special characters.
      'occurrence_comment:comment' => 'This is a test occurrence comment ëēœû',
    ];
    $structure = [
      'model' => 'occurrence',
      'subModels' => [
        'occurrence_comment' => [
          'fk' => 'occurrence_id',
        ],
      ],
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('occurrence', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting an occurrence with comment did not return success response');
    $oc = ORM::factory('occurrence_comment', ['occurrence_id' => $r['success']]);
    $this->assertTrue($oc->loaded, 'Comment not found after submitting a sample with occurrence and comment');
    $this->assertEquals('This is a test occurrence comment ëēœû', $oc->comment, 'Sample comment saved but incorrect comment saved');
  }

  public function testCreateTermlistTerm() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateTermlistTerm");
    $array = [
      'website_id' => 1,
      'term:term' => 'Added term',
      'term:language_id' => 1,
      'termlists_term:termlist_id' => 1,
      'termlists_term:preferred' => 't',
      'termlists_term:sort_order' => 123,
    ];
    $s = submission_builder::build_submission($array, [
      'model' => 'termlists_term',
      'superModels' => [
        'meaning' => ['fk' => 'meaning_id'],
        'term' => ['fk' => 'term_id'],
      ],
    ]);
    $r = data_entry_helper::forward_post_to('termlists_term', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a termlists_term did not return success response');
    $termlistsTermId = $r['success'];
    $termlistsTerm = ORM::Factory('termlists_term', $termlistsTermId);
    $this->assertEquals('Added term', $termlistsTerm->term->term);
    $this->assertEquals(123, $termlistsTerm->sort_order);
    $array['sort_order'] = NULL;
    $array['termlists_term:id'] = $termlistsTermId;
    $array['term:id'] = $termlistsTerm->term->id;
    $s = submission_builder::build_submission($array, [
      'model' => 'termlists_term',
      'superModels' => [
        'meaning' => ['fk' => 'meaning_id'],
        'term' => ['fk' => 'term_id'],
      ],
    ]);
    $r = data_entry_helper::forward_post_to('termlists_term', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Updating a termlists_term did not return success response');
    $termlistsTerm->reload();
    $this->assertEmpty($termlistsTerm->sort_order);
  }

  /**
   * Test updating existing occurrence data with required values.
   *
   * If an existing occurrence has a value for a required attribute, it should
   * be possible to submit an update to that occurrence with a change to a
   * value that does not include re-submitting all the existing required
   * values.
   */
  public function testUpdateOccurrenceWithAttr() {
    // First, create an attribute using ORM.
    $attr = ORM::factory('occurrence_attribute');
    $array = array(
      'occurrence_attribute:caption' => 'Test required attribute',
      'occurrence_attribute:data_type' => 'T',
      'occurrence_attribute:public' => 'f',
      'occurrence_attribute:validation_rules' => 'required',
    );
    $attr->set_submission_data($array);
    $attr->submit();
    // Link our attribute to the survey.
    $aw = ORM::factory('occurrence_attributes_website');
    $array = array(
      'occurrence_attributes_website:website_id' => 1,
      'occurrence_attributes_website:restrict_to_survey_id' => 1,
      'occurrence_attributes_website:occurrence_attribute_id' => $attr->id,
    );
    $aw->set_submission_data($array);
    $aw->submit();
    // Clear the cache to ensure that our required field is used.
    $cache = Cache::instance();
    $cache->delete_tag('required-fields');
    // Now, submitting an occurrence without the attribute filled in should
    // fail.
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
        'occurrence' => array('fk' => 'sample_id')
      ),
    );
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);
    // Check an error exists on the attribute field.
    $this->assertArrayHasKey('errors', $r);
    $this->assertArrayHasKey("occAttr:$attr->id", $r['errors']);
    // Resubmit with the attribute filled in .
    $array["occAttr:$attr->id"] = 'A value';
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);
    $this->assertArrayHasKey('success', $r);
    $occId = $r['success'];
    $occ = ORM::factory('occurrence', $occId);
    // Now, we should be able to submit a partial update.
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:id' => $occ->sample_id,
      'occurrence:id' => $occId,
      'occurrence:comment' => 'This has been partially updated',
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);
    $this->assertArrayHasKey('success', $r, 'Partial update of an existing occurrence failed.');
    $occ->reload();
    $this->assertEquals('This has been partially updated', $occ->comment);
    $this->assertEquals(1, $occ->taxa_taxon_list_id);
    // Clean up.
    $db = new Database();
    $db->query("delete from occurrence_attribute_values where occurrence_attribute_id=$attr->id");
    $aw->delete();
    $attr->delete();
    // Remove the required field from the cache so it doesn't impact other tests.
    $cache = Cache::instance();
    $cache->delete_tag('required-fields');
  }

  private function getSampleAsCsv($id, $regexExpected) {
    $params = array(
      'mode' => 'csv',
      'view' => 'detail',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce'],
    );
    $url = data_entry_helper::$base_url . "index.php/services/data/sample/$id?" . http_build_query($params, '', '&');
    $response = self::getResponse($url, FALSE);
    $this->assertFalse(isset($response['error']), "testRequestDataGetRecordByDirectId returned error. See log for details");
    // spoof the CSV data as a file, so we can use fgetcsv which understands line breaks in content
    $fp = fopen("php://temp", 'r+');
    // Fire regexpressions at the raw CSV data so we can check for things like missing escaping which
    // should really be present by fgetcsv might tolerate
    foreach ($regexExpected as $regex)
      $this->assertRegExp($regex, $response);
    fputs($fp, $response);
    rewind($fp);
    $data = [];
    while ( ($row = fgetcsv($fp) ) !== FALSE ) {
      $data[] = $row;
    }
    $this->assertCount(2, $data, 'Data services get CSV for direct ID did not return 1 record.');
    // combine headers and data into an assoc array response
    return array_combine($data[0], $data[1]);
  }

  public function testRequestDataCsvResponseDoubleQuote() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataCsvResponseDoubleQuote");
    $sample = $this->getSampleAsCsv(1, array('/"Sample for unit testing/'));
    $this->assertEquals('Sample for unit testing with a " double quote', $sample['comment'],
      'Data services CSV format response not encoded correctly for quotes.');
  }

  public function testRequestDataCsvResponseLineFeed() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testRequestDataCsvResponseLineFeed");
    $sample = $this->getSampleAsCsv(2, array('/"Sample for unit testing/'));
    $this->assertEquals("Sample for unit testing with a \nline break", $sample['comment'],
      'Data services CSV format response not encoded correctly for new lines.');
  }
}