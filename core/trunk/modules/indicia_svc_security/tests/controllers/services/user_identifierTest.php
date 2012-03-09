

<?php

require_once 'client_helpers/data_entry_helper.php';

class Controllers_Services_Identifier_Test extends PHPUnit_Framework_TestCase {
  protected $auth;
  protected $db;
  
  function setup() {
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
  function testGetUserID() {
    $response = $this->callGetUserIdService($this->auth, array(
      array('type'=>'email','identifier'=>'test@test.com'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount')
    ), 9998, '?', 'autotest');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output=json_decode($response['output']);
    // response should definitely include a user id
    $this->assertTrue(isset($output->userId), 'The response from createUser call was invalid: '.$response['output']);
    $uid1 = $output->userId;
    // there should now be a user that matches the response
    $user = ORM::factory('user')->where(array('username'=>'?_autotest'))->find();
    $this->assertNotEquals(0, $user->id, 'A user record was not found in the database');
    $this->assertEquals($uid1, $user->id, 'The user record stored in the db had a different ID ('.$user->id.') to the returned id from the service call ('.$uid1.').');
    $this->assertNull($user->core_role_id, 'The created user must not have warehouse access.');
    // the user should belong to just the demo website
    $qry = $this->db->select('website_id')->from('users_websites')->where(array('user_id'=>$uid1))->get()->result_array(false);
    $this->assertEquals(1, count($qry), 'The created user must be joined to a single website.');
    $this->assertEquals(1 /* website_id */, $qry[0]['website_id'], 'The user must be joined to the demo website used in the service call.');
    
    // request for the same twitter account should return the same user id even though email is different
    $response = $this->callGetUserIdService($this->auth, array(
      array('type'=>'email','identifier'=>'othertest@test.com'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount')
    ), 9997, '?', 'autotest');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output=json_decode($response['output']);
    $this->assertEquals($uid1, $output->userId, 'A repeat request for same identifiers did not return the same user ID');
    
    // clean up user identifiers, user websites, person and user records.
    $this->db->query('delete from user_identifiers where user_id='.$user->id);
    $this->db->query('delete from users_websites where user_id='.$user->id);
    $person_id=$user->person_id;
    $this->db->query('delete from users where id='.$user->id);
    $this->db->query('delete from people where id='.$person_id);
  }
  
  /**
   * Test the case where an identifier is submitted with an unknown type. The type should get automatically
   * stored in the termlist.
   */
  function testInvalidType() {
    $randomType = substr(base64_encode(rand(1000000000,9999999999)),0,10);
    $response = $this->callGetUserIdService($this->auth, array(
      array('type'=>'email','identifier'=>'test@test.com'),
      array('type'=>$randomType,'identifier'=>'dummylinkedinaccount')
    ), 9996, '?', 'autotest');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed when sending a random type string.');
    $output=json_decode($response['output']);
    // response should definitely include a user id
    $this->assertTrue(isset($output->userId), 'The response from createUser call was invalid: '.$response['output']);
    $uid1 = $output->userId;
    // check the term now exists
    $qry = $this->db->select('id, term_id')
        ->from('list_termlists_terms')
        ->where(array('term'=>$randomType, 'termlist_external_key'=>'indicia:user_identifier_types'))
        ->get()->result_array(false);
    $this->assertEquals(1, count($qry), 'Submitting a random type term did not result in exactly one instance of that term in the termlist. '.$randomType);
    // clean up the person created
    $this->db->query('delete from user_identifiers where user_id='.$uid1);
    $this->db->query('delete from users_websites where user_id='.$uid1);
    $user = ORM::factory('user', $uid1);
    $person_id=$user->person_id;
    $this->db->query('delete from users where id='.$user->id);
    $this->db->query('delete from people where id='.$person_id);
    // cleanup the inserted term
    $this->db->query('delete from termlists_terms where id='.$qry[0]['id']);
    $this->db->query('delete from terms where id='.$qry[0]['term_id']);
  }
  
  function testFirstNameInsert() {
    $response = $this->callGetUserIdService($this->auth, array(
      array('type'=>'email','identifier'=>'test2@test.com'),
      array('type'=>'twitter','identifier'=>'anothertwitteraccount')
    ), 9995, 'test', 'autotest');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output=json_decode($response['output']);
    $uid1=$output->userId;
    // load the new person and check firstname
    $user = ORM::factory('user')->where(array('username'=>'test_autotest'))->find();
    $this->assertNotEquals(0, $user->id, 'A user record was not found in the database');
    $person_id=$user->person_id;
    $person = ORM::factory('person', $person_id);
    $this->assertEquals('test', $person->first_name, 'Creating a person with known first name did not insert the correct first name.');
    // clean up user identifiers, user websites, person and user records.
    $this->db->query('delete from user_identifiers where user_id='.$user->id);
    $this->db->query('delete from users_websites where user_id='.$user->id);
    $this->db->query('delete from users where id='.$user->id);
    $this->db->query('delete from people where id='.$person_id);
  }

  /**
   * A substantial test designed to test a real world scenario of usage.
   */
  function testFindingRecords() {
    // find a taxon we can submit against - any will do
    $ttlId = $this->db->select('id')->from('taxa_taxon_lists')->limit(1)->get()->current()->id;
    // Find the CMS User ID attribute
    $cmsUserAttrId = $this->db->select('id')->from('sample_attributes')->where(array('caption'=>'CMS User ID'))->limit(1)->get()->current()->id;
    // create a website
    $website1 = ORM::factory('website');
    $website1->title='Unit test finding records 1';
    $website1->url='http://www.example.com';
    $website1->password='password';
    $website1->set_metadata();
    $website1->save();
    $auth1 = data_entry_helper::get_read_write_auth($website1->id, 'password');
    // we need an extra website, so we can test finding records across 2 sites.
    $website2 = ORM::factory('website');
    $website2->title='Unit test finding records 2';
    $website2->url='http://www.example.com';
    $website2->password='password';
    $website2->set_metadata();
    $website2->save();
    $auth2 = data_entry_helper::get_read_write_auth($website2->id, 'password');
    // Need some surveys to test posting data into and finding again later
    $survey1 = ORM::factory('survey');
    $survey1->title='Website 1 test';
    $survey1->website_id=$website1->id;
    $survey1->set_metadata();
    $survey1->save();
    $survey2 = ORM::factory('survey');
    $survey2->title='Website 2 test';
    $survey2->website_id=$website2->id;
    $survey2->set_metadata();
    $survey2->save();
    
    // Create an occurrence on the first website
    $occ1 = $this->createOccurrence($website1->id, $survey1->id, $cmsUserAttrId, 9999, $ttlId);
    
    // Call the service, simulating a user on the first website.
    $response = $this->callGetUserIdService($auth1, array(
        array('type'=>'email','identifier'=>'tracking1@test.com'),
        array('type'=>'twitter','identifier'=>'twittertracking1')
      ), 9999, 'u1', 'autotest');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output=json_decode($response['output']);
    // response should definitely include a positive whole number for the user id
    $this->assertTrue(isset($output->userId), 'The response from createUser call was invalid: '.$response['output']);
    $uid1 = $output->userId;
    // This user should "own" the record posted earlier, since it was posted with the same CMS User ID to the same website.
    $this->assertEquals(1, $this->db->select('id')->from('occurrences')->where(array('created_by_id'=>$uid1))
        ->get()->count(), 'Occurrence not owned by user');
    // Plus the user should be a member of $website1.
    $this->assertEquals(1, $this->db->select('id')->from('users_websites')->where(array('website_id'=>$website1->id, 'user_id'=>$uid1))
        ->get()->count(), 'Created user has not been added to the website members list.');
        
    // Create an occurrence on the second website.
    $occ2 = $this->createOccurrence($website2->id, $survey2->id, $cmsUserAttrId, 9998, $ttlId);
    
    // Call the service, simulating a user on the second website.
    $response = $this->callGetUserIdService($auth2, array(
        array('type'=>'email','identifier'=>'tracking2@test.com'),
        array('type'=>'facebook','identifier'=>'fbtracking2')
      ), 9998, 'u1', 'autotest');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output=json_decode($response['output']);
    // response should definitely include a user id
    $this->assertTrue(isset($output->userId), 'The response from createUser call was invalid: '.$response['output']);
    $uid2 = $output->userId;
    // This user should "own" the record posted earlier, since it was posted with the same CMS User ID to the same website.
    $this->assertEquals(1, $this->db->select('id')->from('occurrences')->where(array('created_by_id'=>$uid2))
        ->get()->count(), 'Occurrence not owned by user');
    // Plus the user should be a member of $website1.
    $this->assertEquals(1, $this->db->select('id')->from('users_websites')->where(array('website_id'=>$website2->id, 'user_id'=>$uid2))
        ->get()->count(), 'Created user has not been added to the website members list.');
        
    // Now the crux - we have 2 diff users on 2 websites. What happens if they turn out to be the same person?
    // This request should return an array of the 2 possible users.
    $response = $this->callGetUserIdService($auth2, array(
        array('type'=>'email','identifier'=>'tracking2@test.com'),
        array('type'=>'facebook','identifier'=>'fbtracking2'),
        array('type'=>'twitter','identifier'=>'twittertracking1')
      ), 9998, 'u1', 'autotest');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertTrue(isset($output->possibleMatches), "Response should include the list of possible users.\n".$response['output']);
    $this->assertTrue(is_array($output->possibleMatches), "Response should include an array of possible users.\n".$response['output']);
    $this->assertEquals(2, count($output->possibleMatches), '2 possible users should have been found');
    
    // Can we limit the searched list of users and only find one?
    $response = $this->callGetUserIdService($auth2, array(
        array('type'=>'email','identifier'=>'tracking2@test.com'),
        array('type'=>'facebook','identifier'=>'fbtracking2'),
        array('type'=>'twitter','identifier'=>'twittertracking1')
      ), 9998, 'u1', 'autotest', 'users_to_merge=['.$uid2.']');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertEquals($uid2, $output->userId, 'Failed to limit users to check using users_to_merge');
    
    // Can we split the searched list of users and only find one?
    $response = $this->callGetUserIdService($auth2, array(
        array('type'=>'email','identifier'=>'tracking2@test.com'),
        array('type'=>'facebook','identifier'=>'fbtracking2'),
        array('type'=>'twitter','identifier'=>'twittertracking1')
      ), 9998, 'u1', 'autotest', 'force=split');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $output = json_decode($response['output']);
    $this->assertEquals($uid2, $output->userId, 'Failed to split users and retreive the correct user ID');
    
    // Recall the service, this time forcing a merge of the 2 possible users.
    $response = $this->callGetUserIdService($auth2, array(
        array('type'=>'email','identifier'=>'tracking2@test.com'),
        array('type'=>'facebook','identifier'=>'fbtracking2'),
        array('type'=>'twitter','identifier'=>'twittertracking1')
      ), 9998, 'u1', 'autotest', 'force=merge');
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id merge service failed.');
    $output = json_decode($response['output']);
    $uid3=$output->userId;
    $this->assertEquals($uid2, $uid3, 'Merge request did not return the correct user');
    // This user should "own" both the records posted earlier
    $this->assertEquals(2, $this->db->select('id')->from('occurrences')->where(array('created_by_id'=>$uid3))
        ->get()->count(), 'Occurrence not owned by user');
    
    // cleanup
    $this->db->query('delete from occurrences where website_id in ('.$website1->id.', '.$website2->id.')');
    $this->db->query('delete from sample_attribute_values where sample_id in ('.
        'select id from samples where survey_id in ('.$survey1->id.', '.$survey2->id.'))');
    $this->db->query('delete from samples where survey_id in ('.$survey1->id.', '.$survey2->id.')');
    $survey1->delete();
    $survey2->delete();
    $this->db->query('delete from user_identifiers where user_id in ('.$uid1.', '.$uid2.')');
    $this->db->query('delete from users_websites where user_id in ('.$uid1.', '.$uid2.')');
    $this->db->query('delete from users where id in ('.$uid1.', '.$uid2.')');
    $website1->delete();
    $website2->delete();
  }
  
  /**
   * Creates an occurrence record using ORM and returns the ORM object.
   */
  private function createOccurrence($websiteId, $surveyId, $cmsUserAttrId, $cmsUserId, $ttlId) {
    $r = ORM::factory('occurrence');
    $r->set_submission_data(array(
      'website_id' => $websiteId,
      'sample:date_start'=>'2012-02-01',
      'sample:date_end'=>'2012-02-01',
      'sample:date_type'=>'D',
      'sample:entered_sref'=>'SU0101',
      'sample:entered_sref_system'=>'OSGB',
      'sample:survey_id' => $surveyId,
      'occurrence:taxa_taxon_list_id' => $ttlId,
      "smpAttr:$cmsUserAttrId" => $cmsUserId
    ));
    $r->submit();
    return $r;
  }  
  
  /**
   * Private helper function to call the get_user_id service.
   */
  private function callGetUserIdService($auth, $identifiers, $cmsUserId, $firstName, $surname, $extras='') {
    $url = data_entry_helper::$base_url.'index.php/services/user_identifier/get_user_id';
    $url .= '?nonce='.$auth['write_tokens']['nonce'].'&auth_token='.$auth['write_tokens']['auth_token'];
    $identifiers = urlencode(json_encode($identifiers));
    $params = "cms_user_id=$cmsUserId&first_name=$firstName&surname=$surname&identifiers=$identifiers";
    if (!empty($extras))
      $params .= "&$extras";
    return data_entry_helper::http_post($url, $params);  
  }
  
}

?>
