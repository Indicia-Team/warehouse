<?php

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Controllers_Services_Data_Test extends PHPUnit_Framework_TestCase {

  protected $auth;
  protected $locationId;
  protected $locationTypeId;
  protected $locationAttributeId;
  protected $locationAttrWebsiteId;
  protected $locationAttributeId2;
  protected $locationAttrWebsiteId2;

  public function setup() {
    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    // make the tokens re-usable
    $this->auth['write_tokens']['persist_auth']=true;
    // set up a tiny bit of data. First pick a termlists_term_id to use which is never going to be a valid location type,
    // so we can filter for just our test location.
    $this->db = new Database();
    $qry = $this->db->select('id')
        ->from('list_termlists_terms')
        ->where(array('termlist_external_key'=>'indicia:dafor','term'=>'Frequent'))
        ->get()->result_array(false);
    
    $this->locationTypeId=$qry[0]['id'];
    $loc = ORM::Factory('location');
    $loc->name='UnitTest';
    $loc->centroid_sref='SU01';
    $loc->centroid_sref_system='OSGB';
    $loc->location_type_id=$this->locationTypeId;
    $loc->set_metadata();
    $loc->save();
    $this->locationId=$loc->id;
    $locattr = ORM::Factory('location_attribute');
    $locattr->caption='UnitTest';
    $locattr->data_type='T';
    $locattr->public='f';
    $locattr->set_metadata();
    $locattr->save();
    $this->locationAttributeId=$locattr->id;
    $locwebsite = ORM::Factory('location_attributes_website');
    $locwebsite->website_id=1;
    $locwebsite->location_attribute_id=$this->locationAttributeId;
    $locwebsite->set_metadata();
    $locwebsite->save();
    $this->locationAttrWebsiteId=$locwebsite->id;
    $locattr = ORM::Factory('location_attribute');
    $locattr->caption='UnitTestInt';
    $locattr->data_type='I';
    $locattr->public='f';
    $locattr->set_metadata();
    $locattr->save();
    $this->locationAttributeId2=$locattr->id;
    $locwebsite = ORM::Factory('location_attributes_website');
    $locwebsite->website_id=1;
    $locwebsite->location_attribute_id=$this->locationAttributeId2;
    $locwebsite->set_metadata();
    $locwebsite->save();
    $this->locationAttrWebsiteId2=$locwebsite->id;
  }
  
  public function tearDown() {
    $loc = ORM::Factory('location', $this->locationId);
    $loc->delete();
    $locwebsite = ORM::Factory('location_attributes_website', $this->locationAttrWebsiteId);
    $locwebsite->delete();
    $locwebsite = ORM::Factory('location_attributes_website', $this->locationAttrWebsiteId2);
    $locwebsite->delete();
    $locattr = ORM::Factory('location_attribute', $this->locationAttributeId);
    $locattr->delete();
    $locattr = ORM::Factory('location_attribute', $this->locationAttributeId2);
    $locattr->delete();
  }
  
  public function testRequestDataGetRecordByDirectId() {
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce']
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location/'.$this->locationId.'?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertEquals(1, count($response), 'Data services get JSON for direct ID did not return 1 record.');
    $this->assertEquals('UnitTest', ($response[0]['name']), 'Data services get JSON for direct ID did not return correct record.');
  }
  
  public function testRequestDataGetRecordByIndirectId() {
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'id'=>$this->locationId
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertEquals(1, count($response), 'Data services get JSON for indirect ID did not return 1 record.');
    $this->assertEquals('UnitTest', ($response[0]['name']), 'Data services get JSON for indirect ID did not return correct record.');
  }
  
  public function testRequestDataGetRecordByQueryIn() {
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'query'=>json_encode(array('in'=>array('id', array($this->locationId))))
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertEquals(1, count($response), 'Data services get JSON for in clause did not return 1 record.');
    $this->assertEquals('UnitTest', ($response[0]['name']), 'Data services get JSON for in clause did not return correct record.');
    // repeat test, for alternative format of in clause
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'query'=>json_encode(array(
        'in'=>array('id'=>array($this->locationId), 'name'=>array('UnitTest')),
      ))
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertEquals(1, count($response), 'Data services get JSON for in clause did not return 1 record.');
    $this->assertEquals('UnitTest', ($response[0]['name']), 'Data services get JSON for in clause did not return correct record.');
    // Another test- which should return 0 records because we look for the wrong name
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->auth['read']['auth_token'],
      'nonce'=>$this->auth['read']['nonce'],
      'query'=>json_encode(array(
        'in'=>array('id'=>array($this->locationId), 'name'=>array('UnitTests')),
      ))
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertEquals(0, count($response), 'Data services get JSON for in clause did not return 0 records.');    
  }
  
  /**
   * Rapidly repeat some calls
   */
  public function testRepeat50() {
    for ($i=0; $i<50; $i++) {
      self::testRequestDataGetRecordByIndirectId();
    }
  }
  
  private function getResponse($url) {
    $session = curl_init();    
    kohana::log('debug', $url);
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // valid json response will decode    
    $response = curl_exec($session);
    kohana::log('debug', $response);
    $response = json_decode($response, true);
    return $response;
  }
  
  public function testSave() {
    // post a location with an attribute value.
    $array = array(
      'location:name' => 'UnitTest2',
      'location:centroid_sref'=>'SU0101',
      'location:centroid_sref_system'=>'osgb',
      'locAttr:'.$this->locationAttributeId=>'saveTestAttr'
    );
    $s = submission_builder::build_submission($array, array('model'=>'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);
    // check we got the required output
    $this->assertTrue(isset($r['success']), 'Submitting a location did not return success response');
    $locId = $r['success'];
    $loc = ORM::Factory('location', $locId);
    $this->assertEquals('UnitTest2', $loc->name, 'Saved location is not as expected');
    $locAttr = ORM::Factory('location_attribute_value')->where(array('location_id'=>$locId))->find();
    $this->assertEquals('saveTestAttr', $locAttr->text_value, 'Saved attribute value is not as expected');
    // repost the same location, with a new name and attribute value
    $array = array(
      'location:id' => $locId,
      'location:name' => 'UnitTest2-update',
      'location:centroid_sref'=>'SU0101',
      'location:centroid_sref_system'=>'osgb',
      'locAttr:'.$this->locationAttributeId.':'.$locAttr->id=>'saveTestAttr-update'
    );
    $s = submission_builder::build_submission($array, array('model'=>'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Re-submitting a location did not return success response');
    $loc->reload();
    $this->assertEquals('UnitTest2-update', $loc->name, 'Re-submitted location is not as expected');
    $locAttr->reload();
    $this->assertEquals('saveTestAttr-update', $locAttr->text_value, 'Saved attribute value is not as expected');
    // repost the same location, with a deleted attribute value
    $array = array(
      'location:id' => $locId,
      'location:name' => 'UnitTest2',
      'location:centroid_sref'=>'SU0101',
      'location:centroid_sref_system'=>'osgb',
      'locAttr:'.$this->locationAttributeId.':'.$locAttr->id=>''
    );
    $s = submission_builder::build_submission($array, array('model'=>'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);
    $locAttr->reload();
    $this->assertEquals(true, $locAttr->deleted, 'Submitting a blank attribute value did not delete it');
    // now cleanup
    $locAttr->delete();
    ORM::Factory('location', $locId)->delete();
  }  
  
  public function testSaveInt() {
    // post a location with an attribute value.
    $array = array(
      'location:name' => 'UnitTest2',
      'location:centroid_sref'=>'SU0101',
      'location:centroid_sref_system'=>'osgb',
      'locAttr:'.$this->locationAttributeId2=>'not an int'
    );
    $s = submission_builder::build_submission($array, array('model'=>'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);
    // check we got the required output - showing an error on the int field
    $this->assertTrue(isset($r['error']), 'Submitting a location with invalid int attr did not return error response');
    $this->assertTrue(isset($r['errors']['locAttr:'.$this->locationAttributeId2]), 'Submitting a location with invalid int attr did not return error for field');
    // now submit with a valid value
    $array = array(
      'location:name' => 'UnitTest2',
      'location:centroid_sref'=>'SU0101',
      'location:centroid_sref_system'=>'osgb',
      'locAttr:'.$this->locationAttributeId2=>0
    );
    $s = submission_builder::build_submission($array, array('model'=>'location'));
    $r = data_entry_helper::forward_post_to('location', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location with int attr did not return success response');
    $locId = $r['success'];
    $locAttr = ORM::Factory('location_attribute_value')->where(array('location_id'=>$locId))->find();
    $this->assertEquals(0, $locAttr->int_value, 'Submitting a location with zero int attr did not save');
    // cleanup
    $locAttr->delete();
    ORM::Factory('location', $locId)->delete();
  }
 
}
?>