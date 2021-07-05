<?php

require_once 'client_helpers/data_entry_helper.php';

class Controllers_Services_Identifier_Test extends Indicia_DatabaseTestCase {
  protected $auth;
  protected $db;

  public function getDataSet() {
    $ds1 = new PHPUnit_Extensions_Database_DataSet_YamlDataSet('modules/phpUnit/config/core_fixture.yaml');

    // Create a second website and second survey to use in testFindingRecords
    // Create a CMS User ID sample attribute
    // Create a CMS User ID sample attribute value for the sample in the
    // core fixture
    // Create a sample, occurrence and CMS User ID sample attribute value on the
    // second website.
    $ds2 = new Indicia_ArrayDataSet(
      array(
        'websites' => array(
          array(
            'title' => 'Test website 2',
            'description' => 'Second website for testFindingRecords',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
            'url' => 'http://www.indicia.org.uk',
            'password' => 'password',
            'verification_checks_enabled' => 'f',
          ),
        ),
        'surveys' => array(
          array(
            'title' => 'Test survey 2',
            'description' => 'Survey for second website.',
            'website_id' => 2,
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
          ),
        ),
        'samples' => array(
          array(
            'survey_id' => 2,
            'date_start' => '2016-07-22',
            'date_end' => '2016-07-22',
            'date_type' => 'D',
            'entered_sref' => 'SU01',
            'entered_sref_system' => 'OSGB',
            'comment' => 'Sample for unit testing',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
            'recorder_names' => 'PHPUnit',
            'record_status' => 'C',
          ),
        ),
        'occurrences' => array(
          array(
            'sample_id' => 2,
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
            'website_id' => 2,
            'comment' => 'Occurrence for unit testing - user 1',
            'taxa_taxon_list_id' => 1,
            'record_status' => 'C',
            'release_status' => 'R',
            'confidential' => 'f',
          ),
          array(
            'sample_id' => 2,
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
            'website_id' => 2,
            'comment' => 'Occurrence for unit testing - user 2',
            'taxa_taxon_list_id' => 1,
            'record_status' => 'C',
            'release_status' => 'R',
            'confidential' => 'f',
          ),
        ),
      ),
    );

    $compositeDs = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet();
    $compositeDs->addDataSet($ds1);
    $compositeDs->addDataSet($ds2);
    return $compositeDs;
  }

  public function setup() {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();

    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    $this->db = new Database();
  }

  /**
   * On login or hook_user after_update, send a get_user_id request to the warehouse to get the user's best fit ID.
   * Store in the profile. On the Warehouse, have a user merger tool which identifies possible overlapping user accounts
   */

  /**
   * Test the basic functionality of the user_identifier/get_user_id service call.
   */
  public function testGetUserID() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Identifier_Test::testGetUserID");
    $response = $this->callGetUserIdService($this->auth, array(
      array('type' => 'email', 'identifier' => 'test@test.com'),
      array('type' => 'twitter', 'identifier' => 'dummytwitteraccount')
    ), 'test', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    // Response should definitely include a user id.
    $this->assertObjectHasAttribute('userId', $output, 'The response from createUser call was invalid: ' . $response['output']);

    $uid1 = $output->userId;
    $userIds = [$uid1];
    // There should now be a user that matches the response.
    $user = ORM::factory('user')->where(array('username' => 'test_autotest'))->find();

    Kohana::log('debug', "New user " . print_r((new ArrayObject($user))->offsetGet("\0*\0object"), TRUE));
    $this->assertNotEquals(0, $user->id, 'A user record was not found in the database');
    $this->assertEquals($uid1, $user->id, 'The user record stored in the db had a different ID (' . $user->id . ') to the returned id from the service call ('.$uid1.').');
    $this->assertNull($user->core_role_id, 'The created user must not have warehouse access.');
    // The user should belong to just the demo website.
    $qry = $this->db->select('website_id')->from('users_websites')->where(array('user_id' => $uid1))->get()->result_array(FALSE);
    $this->assertEquals(1, count($qry), 'The created user must be joined to a single website.');
    $this->assertEquals(1 /* website_id */, $qry[0]['website_id'], 'The user must be joined to the demo website used in the service call.');

    // Request for the same twitter account should return the same user id even
    // though email is different.
    $response = $this->callGetUserIdService($this->auth, array(
      array('type' => 'email', 'identifier' => 'othertest@test.com'),
      array('type' => 'twitter', 'identifier' => 'dummytwitteraccount'),
    ), '?', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertEquals($uid1, $output->userId, 'A repeat request for same identifiers did not return the same user ID');
    $userIds[] = $output->userId;

    // Request for the same email address but with a different case.
    $response = $this->callGetUserIdService($this->auth, array(
      array('type' => 'email', 'identifier' => 'Test@test.com'),
    ), '?', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertEquals($uid1, $output->userId, 'A repeat request for same email with different case did not return the same user ID');
    $userIds[] = $output->userId;

    // Clean up user identifiers, user websites, person and user records.
    $this->cleanupUsers($userIds);
  }

  /**
   * Test the case where an identifier is submitted with an unknown type. The type should get automatically
   * stored in the termlist.
   */
  public function testInvalidType() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Identifier_Test::testInvalidType");
    $randomType = substr(base64_encode(rand(1000000000, 9999999999)), 0, 10);
    $response = $this->callGetUserIdService($this->auth, array(
      array('type' => 'email', 'identifier' => 'test@test.com'),
      array('type' => $randomType, 'identifier' => 'dummylinkedinaccount')
    ), '?', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed when sending a random type string.');
    $output = json_decode($response['output']);
    // Response should definitely include a user id.
    $this->assertObjectHasAttribute('userId', $output, 'The response from createUser call was invalid: ' . $response['output']);
    $uid1 = $output->userId;
    // Check the term now exists.
    $qry = $this->db->select('id, term_id')
      ->from('list_termlists_terms')
      ->where(array('term' => $randomType, 'termlist_external_key' => 'indicia:user_identifier_types'))
      ->get()->result_array(FALSE);
    $this->assertEquals(1, count($qry), 'Submitting a random type term did not result in exactly one instance of that term in the termlist. ' . $randomType);
    // Clean up the person created.
    $this->db->query('delete from user_identifiers where user_id=' . $uid1);
    $this->db->query('delete from users_websites where user_id=' . $uid1);
    $user = ORM::factory('user', $uid1);
    $person_id = $user->person_id;
    $this->db->query('delete from users where id=' . $user->id);
    $this->db->query('delete from people where id=' . $person_id);
    // Cleanup the inserted term.
    $this->db->query('delete from termlists_terms where id=' . $qry[0]['id']);
    $this->db->query('delete from terms where id=' . $qry[0]['term_id']);
  }

  function testFirstNameInsert() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Identifier_Test::testFirstNameInsert");
    $response = $this->callGetUserIdService($this->auth, array(
      array('type' => 'email', 'identifier' => 'test2@test.com'),
      array('type' => 'twitter', 'identifier' => 'anothertwitteraccount')
    ), 'test', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $uid1 = $output->userId;
    // Load the new person and check firstname.
    $user = ORM::factory('user')->where(array('username' => 'test_autotest'))->find();
    $this->assertNotEquals(0, $user->id, 'A user record was not found in the database');
    $person_id = $user->person_id;
    $person = ORM::factory('person', $person_id);
    $this->assertEquals('test', $person->first_name, 'Creating a person with known first name did not insert the correct first name.');
    // Clean up user identifiers, user websites, person and user records.
    $this->db->query('delete from user_identifiers where user_id=' . $user->id);
    $this->db->query('delete from users_websites where user_id=' . $user->id);
    $this->db->query('delete from users where id=' . $user->id);
    $this->db->query('delete from people where id=' . $person_id);
  }

  function testGetUserIDUnique() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Identifier_Test::testGetUserIDUnique");
    $response = $this->callGetUserIdService($this->auth, [
      ['type' => 'email', 'identifier' => 'testunique@test.com'],
    ], 'testunique', 'name');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $obj = json_decode($response['output']);
    $userId1 = $obj->userId;
    $user1 = ORM::factory('user', $userId1);
    $response = $this->callGetUserIdService($this->auth, [
      ['type' => 'email', 'identifier' => 'testunique1@test.com'],
    ], 'testunique', 'name');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $obj = json_decode($response['output']);
    $userId2 = $obj->userId;
    $user2 = ORM::factory('user', $userId2);
    $this->assertNotEquals($user2->username, $user1->username, 'get_user_id failed to generate unique usernames');
    // Deliberately mess up the case of a username, as this can cause problems.
    $user1->username = strtoupper($user1->username);
    $user1->save();
    $response = $this->callGetUserIdService($this->auth, [
      ['type' => 'email', 'identifier' => 'testunique2@test.com'],
    ], 'testunique', 'name');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $obj = json_decode($response['output']);
    $userId3 = $obj->userId;
    $user3 = ORM::factory('user', $userId3);
    $this->assertNotEquals($user3->username, $user1->username, 'get_user_id failed to generate unique usernames');
    // cleanup as makes re-runs easier.
    $this->cleanupUsers([$user1->id, $user2->id, $user3->id]);
  }

  function testGetUserIDWildcards() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Identifier_Test::testGetUserIDWildcards");
    $response = $this->callGetUserIdService($this->auth, [
      ['type' => 'email', 'identifier' => 'testwildcard@test.com'],
    ], 'testwildcard1', 'name');
    $response = $this->callGetUserIdService($this->auth, [
      ['type' => 'email', 'identifier' => 'test%card@test.com'],
    ], 'testwildcard2', 'name');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed with % wildcard.');
    $response = $this->callGetUserIdService($this->auth, [
      ['type' => 'email', 'identifier' => 'test_ildcard@test.com'],
    ], 'testwildcard3', 'name');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed with _ wildcard.');
  }

  /**
   * A substantial test designed to test a real world scenario of usage.
   */
  function testFindingRecords() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Identifier_Test::testFindingRecords");
    $auth1 = data_entry_helper::get_read_write_auth(1, 'password');
    $auth2 = data_entry_helper::get_read_write_auth(2, 'password');

    // Call the service, simulating a user on the first website.
    $response = $this->callGetUserIdService($auth1, array(
      array('type' => 'email', 'identifier' => 'tracking1@test.com'),
      array('type' => 'twitter', 'identifier' => 'twittertracking1'),
    ), 'u1', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    // Response should definitely include a positive whole number for the user id.
    $this->assertObjectHasAttribute('userId', $output, 'The response from createUser call was invalid: '.$response['output']);
    $uid1 = $output->userId;
    // This user should be a member of website1.
    $this->assertEquals(1, $this->db->select('id')->from('users_websites')->where(array('website_id' => 1, 'user_id' => $uid1))
      ->get()->count(), 'Created user has not been added to the website 1 members list.');

    // Call the service, simulating a user on the second website.
    $response = $this->callGetUserIdService($auth2, array(
      array('type' => 'email', 'identifier' => 'tracking2@test.com'),
      array('type' => 'facebook', 'identifier' => 'fbtracking2'),
    ), 'u1', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    // Response should definitely include a user id.
    $this->assertObjectHasAttribute('userId', $output, 'The response from createUser call was invalid: '.$response['output']);
    $uid2 = $output->userId;
    // This user should be a member of website2.
    $this->assertEquals(1, $this->db->select('id')->from('users_websites')->where(array('website_id' => 2, 'user_id' => $uid2))
      ->get()->count(), 'Created user has not been added to the website 2 members list.');

    // Now the crux - we have 2 different users on 2 websites. What happens if
    // they turn out to be the same person?
    // This request should return an array of the 2 possible users.
    $response = $this->callGetUserIdService($auth2, array(
      array('type' => 'email', 'identifier' => 'tracking2@test.com'),
      array('type' => 'facebook', 'identifier' => 'fbtracking2'),
      array('type' => 'twitter', 'identifier' => 'twittertracking1'),
    ), 'u1', 'autotest');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertObjectHasAttribute('possibleMatches', $output, "Response should include the list of possible users.\n$response[output]");
    $this->assertInternalType('array', $output->possibleMatches, "Response should include an array of possible users.\n$response[output]");
    $this->assertCount(2, $output->possibleMatches, '2 possible users should have been found');

    // Can we limit the searched list of users and only find one?
    $response = $this->callGetUserIdService($auth2, array(
      array('type' => 'email', 'identifier' => 'tracking2@test.com'),
      array('type' => 'facebook', 'identifier' => 'fbtracking2'),
      array('type' => 'twitter', 'identifier' => 'twittertracking1'),
    ), 'u1', 'autotest', 'users_to_merge=[' . $uid2 . ']');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertEquals($uid2, $output->userId, 'Failed to limit users to check using users_to_merge');

    // Can we split the searched list of users and only find one?
    $response = $this->callGetUserIdService($auth2, array(
      array('type' => 'email', 'identifier' => 'tracking2@test.com'),
      array('type' => 'facebook', 'identifier' => 'fbtracking2'),
      array('type' => 'twitter', 'identifier' => 'twittertracking1'),
    ), 'u1', 'autotest', 'force=split');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertEquals($uid2, $output->userId, 'Failed to split users and retreive the correct user ID');

    // Recall the service, this time forcing a merge of the 2 possible users.
    // We'll allocate an occurrence to each of the 2 users first to ensure
    // that merging picks up the records.
    $this->db->query("update occurrences set created_by_id=$uid1 where comment='Occurrence for unit testing - user 1'");
    $this->db->query("update occurrences set created_by_id=$uid2 where comment='Occurrence for unit testing - user 2'");
    $response = $this->callGetUserIdService($auth2, array(
      array('type' => 'email', 'identifier' => 'tracking2@test.com'),
      array('type' => 'facebook', 'identifier' => 'fbtracking2'),
      array('type' => 'twitter', 'identifier' => 'twittertracking1'),
    ), 'u1', 'autotest', 'force=merge');
    $this->assertEquals(1, $response['result'], 'The request to the user_identifier/get_user_id merge service failed.');
    $output = json_decode($response['output']);
    $uid3 = $output->userId;
    $this->assertEquals($uid2, $uid3, 'Merge request did not return the correct user');
    // This user should "own" the 2 records that we linked to uid1 and uid2 earlier.
    $this->assertEquals(2, $this->db->select('id')->from('occurrences')->where(['created_by_id' => $uid3])
      ->get()->count(), 'Occurrence not owned by user');
    $this->assertEquals(1, $this->db->select('id')->from('users')
      ->where(['deleted' => 't', 'id' => $uid1])
      ->get()->count(), 'Merged user was not deleted');
    $this->assertEquals(1, $this->db->select('id')->from('users')
      ->where(['deleted' => 'f', 'id' => $uid3])
      ->get()->count(), 'Kept user was incorrectly deleted');
    // Cleanup.
    $pid1 = $this->db->query("select person_id from users where id=$uid1")->current()->person_id;
    $pid2 = $this->db->query("select person_id from users where id=$uid1")->current()->person_id;
    $this->db->query('delete from occurrences where website_id in (1, 2)');
    $this->db->query("delete from user_identifiers where user_id in ($uid1, $uid2)");
    $this->db->query("delete from users_websites where user_id in ($uid1, $uid2)");
    // Delete 1 at a time to avoid integrity violations.
    $this->db->query("delete from users where id=$uid1");
    $this->db->query("delete from people where id=$pid1");
    $this->db->query("delete from users where id=$uid2");
    $this->db->query("delete from people where id=$pid2");
  }

  /**
   * Test the basic functionality of the user_identifier/get_user_id service call.
   */
  public function testGetLongUserID() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Identifier_Test::testGetLongUserID");
    $response = $this->callGetUserIdService($this->auth, [
        ['type' => 'email', 'identifier' => 'thisisaverylongfirstnamethisisaverylongsurname@test.com'],
      ],
      'thisisaverylongfirstname',
      'thisisaverylongsurname'
    );
    $this->assertEquals(1, $response['result'],
      'The request to the user_identifier/get_user_id merge service failed for long username test.');
    $output = json_decode($response['output']);
    // Response should definitely include a user id.
    $this->assertObjectHasAttribute('userId', $output, 'The response from createUser call was invalid: ' . $response['output']);
  }

  private function cleanupUsers($userIds) {
    $idList = implode(',', $userIds);
    $cleanupSql = <<<SQL
drop table if exists people_ids;
delete from user_identifiers where user_id in ($idList);
delete from users_websites where user_id in ($idList);
select person_id into temporary people_ids from users where id in ($idList);
delete from users where id in ($idList);
delete from people where id in (select person_id from people_ids);
SQL;
    $this->db->query($cleanupSql);
  }

  /**
   * Private helper function to call the get_user_id service.
   */
  private function callGetUserIdService($auth, $identifiers, $firstName, $surname, $extras = '') {
    $url = data_entry_helper::$base_url . 'index.php/services/user_identifier/get_user_id';
    $url .= '?nonce=' . $auth['write_tokens']['nonce'] . '&auth_token=' . $auth['write_tokens']['auth_token'];
    $identifiers = urlencode(json_encode($identifiers));
    $params = "first_name=$firstName&surname=$surname&identifiers=$identifiers";
    if (!empty($extras)) {
      $params .= "&$extras";
    }
    Kohana::log('debug', "Making request to $url");
    Kohana::log('debug', "with params " . print_r($params, TRUE));
    $r = data_entry_helper::http_post($url, $params);
    Kohana::log('debug', "Received response " . print_r($r, TRUE));
    return $r;
  }

}
