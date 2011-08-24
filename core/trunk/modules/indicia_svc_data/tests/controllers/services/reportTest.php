<?php

require_once 'client_helpers/report_helper.php';

class Controllers_Services_Report_Test extends PHPUnit_Framework_TestCase {

  private $readAuth;
  private $locationId;
  private $locationTypeId;
  private $locationAttributeId;
  private $locationWebsiteId;

  public function setup() {
    $this->readAuth = report_helper::get_read_auth(1, 'password');
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
    $this->locationWebsiteId=$locwebsite->id;
  }
  
  public function tearDown() {
    $loc = ORM::Factory('location', $this->locationId);
    $loc->delete();
    $locwebsite = ORM::Factory('location_attributes_website', $this->locationWebsiteId);
    $locwebsite->delete();
    $locattr = ORM::Factory('location_attribute', $this->locationAttributeId);
    $locattr->delete();
  }
  
  public function testRequestReportGetJson() {
    $params = array(
      'report'=>'occurrences_by_website.xml',
      'reportSource'=>'local',
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport?'.http_build_query($params, '', '&');
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // valid json response will decode
    $response = json_decode(curl_exec($session), true);
    $this->assertEquals(true, isset($response[0]['title']), 'Report get JSON response not as expected');
  }
  
  public function testRequestReportPostJson() {
    $params = array(
      'report'=>'occurrences_by_website.xml',
      'reportSource'=>'local',
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
    // valid json response will decode
    $response = json_decode(curl_exec($session), true);
    $this->assertEquals(true, isset($response[0]['title']), 'Report post JSON response not as expected');
  }
  
  public function testRequestReportGetXML() {
    $params = array(
      'report'=>'occurrences_by_website.xml',
      'reportSource'=>'local',
      'mode'=>'xml',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport?'.http_build_query($params, '', '&');
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // valid xml response will decode
    $response = json_decode(curl_exec($session), true);
    $response = new SimpleXmlElement(curl_exec($session), true);    
    $this->assertEquals(true, isset($response->record[0]->title), 'Report get XML response not as expected');
  }
  
  public function testRequestReportPostXML() {
    $params = array(
      'report'=>'occurrences_by_website.xml',
      'reportSource'=>'local',
      'mode'=>'xml',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
    // valid xml response will decode
    $response = new SimpleXmlElement(curl_exec($session), true);    
    $this->assertEquals(true, isset($response->record[0]->title), 'Report post XML response not as expected');
  }
  
  /**
   * A small test for a report with advanced features. 
   */
  public function testAdvancedReport() {
    $params = array(
      'report'=>'library/locations/locations_list.xml',
      'reportSource'=>'local',
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce'],
      'params'=>json_encode(array('locattrs'=>'UnitTest', 'location_type_id'=>$this->locationTypeId))
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
    // valid json response will decode
    $response = json_decode(curl_exec($session), true);
    $this->assertEquals(1, count($response), 'Advanced report response should only include 1 record');
    $this->assertEquals(true, isset($response[0]['name']), 'Advanced report did not return a name column');
    $this->assertEquals('UnitTest', $response[0]['name'], 'Advanced report should return location called UnitTest');
    $this->assertEquals(true, array_key_exists('attr_location_unittest', $response[0]), 'Advanced report should return column for UnitTest');
  }
  
  /**
   * Repeat check for advanced report output, this time requesting an attribute by ID rather than name.
   */
  public function testAdvancedReportByAttrId() {
    $params = array(
      'report'=>'library/locations/locations_list.xml',
      'reportSource'=>'local',
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce'],
      'params'=>json_encode(array('locattrs'=>$this->locationAttributeId, 'location_type_id'=>$this->locationTypeId))
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
    // valid json response will decode
    $response = json_decode(curl_exec($session), true);
    $this->assertEquals(true, array_key_exists('attr_location_'.$this->locationAttributeId, $response[0]), 'Advanced report should return column for UnitTest by ID');
  }

  public function testReportRequestsParams() {
    $params = array(
      'report'=>'library/locations/locations_list.xml',
      'reportSource'=>'local',
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
    // valid json response will decode
    $response = json_decode(curl_exec($session), true);
    kohana::log('debug', 'response');
    kohana::log('debug', print_r($response, true));
    $this->assertEquals(true, isset($response['parameterRequest']), 'Report should request parameters');
  }

  public function testInvalidReportRequest() {
    $params = array(
      'report'=>'invalid.xml',
      'reportSource'=>'local',
      'mode'=>'json',
      'auth_token'=>$this->readAuth['auth_token'],
      'nonce'=>$this->readAuth['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    
    $session = curl_init();
    
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
    // valid json response will decode
    $response = json_decode(curl_exec($session), true);
    $this->assertEquals(true, isset($response['error']), 'Invalid report request should return error');
  }

}
?>