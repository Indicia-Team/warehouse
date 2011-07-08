<?php

require_once 'client_helpers/report_helper.php';

class Controllers_Services_Report_Test extends PHPUnit_Framework_TestCase {

  private $readAuth;

  public function setup() {
    $this->readAuth = report_helper::get_read_auth(1, 'password');
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

}
?>