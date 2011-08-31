<?php

require_once 'client_helpers/data_entry_helper.php';

class Controllers_Services_Data_Test extends PHPUnit_Framework_TestCase {

  private $readAuth;
  private $locationId;
  private $locationWebsiteId;

  public function setup() {
    $this->readAuth = data_entry_helper::get_read_auth(1, 'password');
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
    $locwebsite = ORM::Factory('locations_website');
    $locwebsite->website_id=1;
    $locwebsite->location_id=$this->locationId;
    $locwebsite->set_metadata();
    $locwebsite->save();
    $this->locationWebsiteId=$locwebsite->id;
  }
  
  public function tearDown() {
    $locwebsite = ORM::Factory('locations_website', $this->locationWebsiteId);
    $locwebsite->delete();
    $loc = ORM::Factory('location', $this->locationId);
    $loc->delete();
  }
  
  public function testRequestDataGetRecordByDirectId() {
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce']
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location/'.$this->locationId.'?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertEquals(1, count($response), 'Data services get JSON for direct ID did not return 1 record.');
    $this->assertEquals('UnitTest', ($response[0]['name']), 'Data services get JSON for direct ID did not return correct record.');
  }
  
  public function testRequestDataGetRecordByIndirectId() {
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce'],
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
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce'],
      'query'=>json_encode(array('in'=>array('id', array($this->locationId))))
    );
    $url = data_entry_helper::$base_url.'index.php/services/data/location?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    $this->assertEquals(1, count($response), 'Data services get JSON for in clause did not return 1 record.');
    $this->assertEquals('UnitTest', ($response[0]['name']), 'Data services get JSON for in clause did not return correct record.');
    // repeat test, for alternative format of in clause
    $params = array(
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce'],
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
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce'],
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
  
 
}
?>