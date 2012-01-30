

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
    $url = data_entry_helper::$base_url.'index.php/services/user_identifier/get_user_id';
    $identifiers = urlencode(json_encode(array(
      array('type'=>'email','identifier'=>'test@test.com'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount')
    )));
    $url .= '?nonce='.$this->auth['write_tokens']['nonce'].'&auth_token='.$this->auth['write_tokens']['auth_token'];
    $response = data_entry_helper::http_post($url, 'surname=autotest&identifiers='.$identifiers);
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $uid1=$response['output'];
    // response should definitely be a positive whole number
    $this->assertTrue(preg_match('/^[0-9]+$/', $uid1)===1, 'The response from createUser call was invalid: '.$uid1);
    // there should now be a user that matches the response
    $user = ORM::factory('user')->where(array('username'=>'?_autotest'))->find();
    $this->assertNotEquals(0, $user->id, 'A user record was not found in the database');
    $this->assertEquals($uid1, $user->id, 'The user record stored in the db had a different ID ('.$user->id.') to the returned id from the service call ('.$uid1.').');
    $this->assertNull($user->core_role_id, 'The created user must not have warehouse access.');
    // the user should belong to just the demo website
    $qry = $this->db->select('website_id')->from('users_websites')->where(array('user_id'=>$uid1))->get()->result_array(false);
    $this->assertEquals(1, count($qry), 'The created user must be joined to a single website.');
    $this->assertEquals(1 /* website_id */, $qry[0]['website_id'], 'The user must be joined to the demo website used in the service call.');
    
    // request for the same twitter account should return the same user id
    $identifiers = urlencode(json_encode(array(
      array('type'=>'twitter','identifier'=>'dummytwitteraccount')
    )));
    $response = data_entry_helper::http_post($url, 'surname=autotest&identifiers='.$identifiers);
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $this->assertTrue($response['output']===$uid1, 'A repeat request for same identifiers did not return the same user ID');
    
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
    $url = data_entry_helper::$base_url.'index.php/services/user_identifier/get_user_id';
    $randomType = substr(base64_encode(rand(1000000000,9999999999)),0,10);
    $identifiers = urlencode(json_encode(array(
      array('type'=>'email','identifier'=>'test@test.com'),
      array('type'=>$randomType,'identifier'=>'dummylinkedinaccount')
    )));
    $url .= '?nonce='.$this->auth['write_tokens']['nonce'].'&auth_token='.$this->auth['write_tokens']['auth_token'];
    $response = data_entry_helper::http_post($url, 'surname=autotest&identifiers='.$identifiers);    
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed when sending a random type string.');
    $uid1=$response['output'];
    $this->assertTrue(preg_match('/^[0-9]+$/', $uid1)===1, 'The response from createUser call was invalid when sending a random type string: '.$uid1);
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
    $url = data_entry_helper::$base_url.'index.php/services/user_identifier/get_user_id';
    $identifiers = urlencode(json_encode(array(
      array('type'=>'email','identifier'=>'test2@test.com'),
      array('type'=>'twitter','identifier'=>'anothertwitteraccount')
    )));
    $url .= '?nonce='.$this->auth['write_tokens']['nonce'].'&auth_token='.$this->auth['write_tokens']['auth_token'];
    $response = data_entry_helper::http_post($url, 'surname=autotest&first_name=test&identifiers='.$identifiers);
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $uid1=$response['output'];
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
   * Test the case where 2 people end up on the system for the same physical person, and then
   * a list of identifiers causes the system to realise that they should be joined. The warehouse
   * should return the "best fit" leaving the merging for the warehouse admininstrator.
   */
  function testFindBestFit() {
    $url = data_entry_helper::$base_url.'index.php/services/user_identifier/get_user_id';
    $url .= '?nonce='.$this->auth['write_tokens']['nonce'].'&auth_token='.$this->auth['write_tokens']['auth_token'];
    $identifiers = urlencode(json_encode(array(
      array('type'=>'email','identifier'=>'test@test.com'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount')
    )));
    $response = data_entry_helper::http_post($url, 'first_name=u1&surname=autotest&identifiers='.$identifiers);
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $uid1=$response['output'];
    $this->assertTrue(preg_match('/^[0-9]+$/', $uid1)===1, 'The response from createUser call was invalid: '.$uid1);
    $identifiers = urlencode(json_encode(array(
      array('type'=>'email','identifier'=>'test2@test.com'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount2')
    )));    
    $response = data_entry_helper::http_post($url, 'first_name=u2&surname=autotest&identifiers='.$identifiers);
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $uid2=$response['output'];
    $this->assertTrue(preg_match('/^[0-9]+$/', $uid1)===1, 'The response from createUser call was invalid: '.$uid1);
    $this->assertNotEquals($uid1, $uid2, 'Call to create 2 separate people resulted in 1 user ID');
    // submit information that implies the users are the same. Should match user 2 because it fits
    // 2/3 of the identifiers.
    $identifiers = urlencode(json_encode(array(
      array('type'=>'email','identifier'=>'test2@test.com'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount2')
    )));
    $response = data_entry_helper::http_post($url, 'surname=autotest&identifiers='.$identifiers);
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $uid3=$response['output'];
    $this->assertEquals($uid2, $uid3, 'Request to get best fit did not find the best fit user.');
    // submit information that implies the users are the same. Should match user 1 because it fits
    // the first and surnames.
    $identifiers = urlencode(json_encode(array(
      array('type'=>'email','identifier'=>'test2@test.com'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount'),
      array('type'=>'twitter','identifier'=>'dummytwitteraccount2')
    )));
    $response = data_entry_helper::http_post($url, 'first_name=u1&surname=autotest&identifiers='.$identifiers);
    $this->assertTrue($response['result']==1, 'The request to the user_identifier/get_user_id service failed.');
    $uid4=$response['output'];
    $this->assertEquals($uid1, $uid4, 'Request to get best fit did not find the best fit user.');   
    $user1 = ORM::factory('user', $uid1);
    $p1 = $user1->person_id;
    $user2 = ORM::factory('user', $uid2);
    $p2 = $user2->person_id;
    $this->db->query('delete from user_identifiers where user_id in ('.$uid1.','.$uid2.')');
    $this->db->query('delete from users_websites where user_id in ('.$uid1.','.$uid2.')');
    $this->db->query('delete from users where id in ('.$uid1.','.$uid2.')');
    $this->db->query('delete from people where id in ('.$p1.','.$p2.')');
  }
  
  
  
  
}

?>
