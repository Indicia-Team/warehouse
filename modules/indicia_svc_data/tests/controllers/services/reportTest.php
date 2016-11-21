<?php

require_once 'client_helpers/report_helper.php';

class Controllers_Services_Report_Test extends Indicia_DatabaseTestCase {

  protected $auth;

  public function getDataSet()
  {
    $ds1 =  new PHPUnit_Extensions_Database_DataSet_YamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  public static function setUpBeforeClass() {
    // The indicia_report_user is used when querying for reports and needs
    // adequate permissions to work. These cannot be established until
    // the application has created the schema.
    $db = new Database();
    $db->query('GRANT USAGE ON SCHEMA indicia TO indicia_report_user;');
    $db->query('ALTER DEFAULT PRIVILEGES IN SCHEMA indicia GRANT SELECT ON TABLES TO indicia_report_user;');
    $db->query('GRANT SELECT ON ALL TABLES IN SCHEMA indicia TO indicia_report_user;');
  }

  public function setup() {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();
    
    $this->auth = report_helper::get_read_write_auth(1, 'password');
    // make the tokens re-usable
    $this->auth['write_tokens']['persist_auth']=true;    
  }
  
  private function getResponse($url, $post = FALSE, $params = array()) {
    Kohana::log('debug', "Making request to $url");
    $session = curl_init();
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if ($post) {
      Kohana::log('debug', "with params " . print_r($params, TRUE));
      curl_setopt ($session, CURLOPT_POST, true);
      curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
    }
    $response = curl_exec($session);
    Kohana::log('debug', "Received response " . print_r($response, TRUE));
    return $response;
  }
  
  public function testRequestReportGetJson() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testRequestReportGetJson");
    $params = array(
      'report' => 'library/websites/species_and_occurrence_counts.xml',
      'reportSource' => 'local',
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    // valid json response will decode
    $response = json_decode($response, true);
    $this->assertFalse(isset($response['error']), "testRequestReportGetJson returned error. See log for details");
    $this->assertNotCount(0, $response, "Database contains no records to report on");
    $this->assertTrue(isset($response[0]['title']), 'Report get JSON response not as expected');
  }
  
  public function testRequestReportPostJson() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testRequestReportPostJson");
    $params = array(
      'report' => 'library/websites/species_and_occurrence_counts.xml',
      'reportSource' => 'local',
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    $response = self::getResponse($url, TRUE, $params);
    // valid json response will decode
    $response = json_decode($response, true);
    $this->assertFalse(isset($response['error']), "testRequestReportPostJson returned error. See log for details");
    $this->assertTrue(isset($response[0]['title']), 'Report post JSON response not as expected');
  }
  
  public function testRequestReportGetXML() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testRequestReportGetXML");
    $params = array(
      'report' => 'library/websites/species_and_occurrence_counts.xml',
      'reportSource' => 'local',
      'mode' => 'xml',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport?'.http_build_query($params, '', '&');
    $response = self::getResponse($url);
    // valid xml response will decode
    $response = new SimpleXmlElement($response, true);    
    $this->assertFalse(isset($response->error), "testRequestReportGetXML returned error. See log for details");
    $this->assertTrue(isset($response->record[0]->title), 'Report get XML response not as expected');
  }
  
  public function testRequestReportPostXML() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testRequestReportPostXML");
    $params = array(
      'report' => 'library/websites/species_and_occurrence_counts.xml',
      'reportSource' => 'local',
      'mode' => 'xml',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    $response = self::getResponse($url, TRUE, $params);
    // valid xml response will decode
    $response = new SimpleXmlElement($response, true);    
    $this->assertFalse(isset($response->error), "testRequestReportPostXML returned error. See log for details");
    $this->assertTrue(isset($response->record[0]->title), 'Report post XML response not as expected');
  }
  
  /**
   * A small test for a report with advanced features. 
   */
  public function testAdvancedReport() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testAdvancedReport");
    $params = array(
      'report' => 'library/locations/locations_list.xml',
      'reportSource' => 'local',
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce'],
      'params' => json_encode(array('locattrs' => 'Test text', 'location_type_id' => 2))
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    $response = self::getResponse($url, TRUE, $params);
    // valid json response will decode
    $response = json_decode($response, true);
    $this->assertFalse(isset($response['error']), "testAdvancedReport returned error. See log for details");
    $this->assertCount(1, $response, 'Advanced report response should only include 1 record');
    $this->assertTrue(isset($response[0]['name']), 'Advanced report did not return a name column');
    $this->assertEquals('Test location', $response[0]['name'], 'Advanced report should return location called \'Test location\'');
    $this->assertTrue(array_key_exists('attr_location_test_text', $response[0]), 'Advanced report should return column for test_text');
  }
  
  /**
   * Repeat check for advanced report output, this time requesting an attribute by ID rather than name.
   */
  public function testAdvancedReportByAttrId() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testAdvancedReportByAttrId");
    $params = array(
      'report' => 'library/locations/locations_list.xml',
      'reportSource' => 'local',
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce'],
      'params' => json_encode(array('locattrs' => 1, 'location_type_id' => 2))
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    $response = self::getResponse($url, TRUE, $params);
    // valid json response will decode
    $response = json_decode($response, true);
    $this->assertFalse(isset($response['error']), "testAdvancedReportByAttrId returned error. See log for details");
    $this->assertTrue(array_key_exists('attr_location_1', $response[0]), 'Advanced report should return column for test_text by ID');
  }

  public function testReportRequestsParams() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testReportRequestsParams");
    $params = array(
      'report' => 'library/locations/locations_list.xml',
      'reportSource' => 'local',
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    $response = self::getResponse($url, TRUE, $params);
    // valid json response will decode
    $response = json_decode($response, true);
    $this->assertFalse(isset($response['error']), "testReportRequestsParams returned error. See log for details");
    $this->assertTrue(isset($response['parameterRequest']), 'Report should request parameters');
  }

  public function testInvalidReportRequest() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testInvalidReportRequest");
    $params = array(
      'report' => 'invalid.xml',
      'reportSource' => 'local',
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce']
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    $response = self::getResponse($url, TRUE, $params);
    // valid json response will decode
    $response = json_decode($response, true);
    $this->assertTrue(isset($response['error']), 'Invalid report request should return error');
  }
  
  public function testLookupCustomAttrs() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Report_Test::testLookupCustomAttrs");
    $response = $this->getReportResponse(
      'library/locations/locations_list.xml', array('locattrs' => 'Test lookup', 'location_type_id' => 2));
    $this->assertFalse(isset($response['error']), "testLookupCustomAttrs returned error. See log for details");
    $this->assertCount(1, $response, 'Report response should only include 1 record');    
    $this->assertTrue(array_key_exists('attr_location_test_lookup', $response[0]), 'Locations report should return column for test_lookup');
    $this->assertTrue(array_key_exists('attr_location_term_test_lookup', $response[0]), 'Locations report should return column for test_lookup term');   
    $this->assertEquals('Test term', $response[0]['attr_location_term_test_lookup'], 'Locations report did not return correct attribute value');
  }

  public function testReportLibraryLocationsFilterableRecordCountsLeague() {
    Kohana::log('debug',
        "Running unit test, Controllers_Services_Report_Test::testReportLibraryLocationsFilterableRecordCountsLeague");
    $response = $this->getReportResponse(
        'library/locations/filterable_record_counts_league.xml', array('location_type_id' => 2));
    // Simply testing that the report parses and the SQL runs
    $this->assertFalse(isset($response['error']),
        "testReportLibraryLocationsFilterableRecordCountsLeague returned error. See log for details");
  }

  public function testReportLibraryLocationsFilterableRecordCountsLeagueLinked() {
    Kohana::log('debug',
      "Running unit test, Controllers_Services_Report_Test::testReportLibraryLocationsFilterableRecordCountsLeagueLinked");
    $response = $this->getReportResponse(
      'library/locations/filterable_record_counts_league_linked.xml', array('location_type_id' => 2));
    // Simply testing that the report parses and the SQL runs
    $this->assertFalse(isset($response['error']),
      "testReportLibraryLocationsFilterableRecordCountsLeague returned error. See log for details");
  }

  public function testReportLibraryLocationsFilterableSpeciesCountsLeague() {
    Kohana::log('debug',
      "Running unit test, Controllers_Services_Report_Test::testReportLibraryLocationsFilterableSpeciesCountsLeague");
    $response = $this->getReportResponse(
      'library/locations/filterable_species_counts_league.xml', array('location_type_id' => 2));
    // Simply testing that the report parses and the SQL runs
    $this->assertFalse(isset($response['error']),
      "testReportLibraryLocationsFilterableRecordCountsLeague returned error. See log for details");
  }

  public function testReportLibraryLocationsFilterableSpeciesCountsLeagueLinked() {
    Kohana::log('debug',
      "Running unit test, Controllers_Services_Report_Test::testReportLibraryLocationsFilterableSpeciesCountsLeagueLinked");
    $response = $this->getReportResponse(
      'library/locations/filterable_species_counts_league_linked.xml', array('location_type_id' => 2));
    // Simply testing that the report parses and the SQL runs
    $this->assertFalse(isset($response['error']),
      "testReportLibraryLocationsFilterableRecordCountsLeague returned error. See log for details");
  }

  private function getReportResponse($report, $params = []) {
    $params = array(
      'report' => $params,
      'reportSource' => 'local',
      'mode' => 'json',
      'auth_token' => $this->auth['read']['auth_token'],
      'nonce' => $this->auth['read']['nonce'],
      'params' => json_encode($params)
    );
    $url = report_helper::$base_url.'index.php/services/report/requestReport';
    $response = self::getResponse($url, TRUE, $params);
    // valid json response will decode
    return json_decode($response, true);
  }

}
?>