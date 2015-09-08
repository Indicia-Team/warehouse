<?php

/**
 * Created by PhpStorm.
 * User: john
 * Date: 08/09/2015
 * Time: 08:58
 */
class Rest_ControllerTest extends PHPUnit_Framework_TestCase {

  private static $userId;
  private static $config;

  public static function setUpBeforeClass() {
    // grab the clients registered on this system
    $clients = Kohana::config('rest.clients');
    // just test the first client
    foreach ($clients as $userId => $config) {
      self::$userId = $userId;
      self::$config = $config;
      break;
    }
  }

  protected function setUp() {

  }

  protected function tearDown() {

  }

  public function testProjects_getUnauthorised() {
    // deliberately incorrect shared secret
    $response = $this->callService('projects', self::$userId, '---');
    $this->assertTrue($response['httpCode']===401, 'Incorrect shared secret passed to /projects but request authorised. ' .
      "Http response $response[httpCode].");
    $this->assertTrue($response['response']==='Unauthorized', 'Incorrect shared secret passed to /projects but data still returned. '.
      var_export($response, true));
    $response = $this->callService('projects', '---', self::$config['shared_secret']);
    $this->assertTrue($response['httpCode']===401, 'Incorrect userId passed to /projects but request authorised. ' .
      "Http response $response[httpCode].");
    $this->assertTrue($response['response']==='Unauthorized', 'Incorrect userId passed to /projects but data still returned. '.
      var_export($response, true));
  }

  public function testProjects_get() {
    $response = $this->callService('projects', self::$userId, self::$config['shared_secret']);
    $this->assertTrue($response['curlErrno']===0 && $response['httpCode']===200,
      "Invalid response from call to /projects. HTTP Response $response[httpCode]. Curl error " .
      "$response[curlErrno] ($response[errorMessage]).");
    $viaApi = json_decode($response['response']);
    $viaConfig = self::$config['projects'];
    $this->assertEquals(count($viaConfig), count($viaApi->data), 'Incorrect number of projects returned from /projects.');
    foreach ($viaApi->data as $projDef) {
      $this->assertArrayHasKey($projDef->id, $viaConfig, "Unexpected project $projDef->id returned by /projects.");
      $this->assertEquals($projDef->Title, $viaConfig[$projDef->id]['Title'],
          "Unexpected title $projDef->Title returned for project $projDef->id by /projects.");
      $this->assertEquals($projDef->Description, $viaConfig[$projDef->id]['Description'],
        "Unexpected description $projDef->Description returned for project $projDef->id by /projects.");
    }
  }

  public function testProjects_get_id() {
    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("projects/$projDef[id]", self::$userId, self::$config['shared_secret']);
      $this->assertTrue($response['curlErrno']===0 && $response['httpCode']===200,
        "Invalid response from call to /projects/$projDef[id]. HTTP Response $response[httpCode]. Curl error " .
        "$response[curlErrno] ($response[errorMessage]).");
      $fromApi = json_decode($response['response']);
      $this->assertEquals($fromApi->Title, $projDef['Title'],
          "Unexpected title $fromApi->Title returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($fromApi->Title, $projDef['Title'],
          "Unexpected title $fromApi->Title returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($fromApi->Description, $projDef['Description'],
          "Unexpected description $fromApi->Description returned for project $projDef[id] by /projects/$projDef[id].");
    }
  }

  public function testTaxon_observations_get_incorrect_params() {
    $response = $this->callService('taxon-observations', self::$userId, '---');
    $this->assertTrue($response['httpCode']===401,
      'Incorrect shared secret passed to /taxon-observations but request authorised. ' .
      "Http response $response[httpCode].");
    $this->assertTrue($response['response']==='Unauthorized',
      'Incorrect shared secret passed to /taxon-observations but data still returned. '.
      var_export($response, true));
    $response = $this->callService('taxon-observations', '---', self::$config['shared_secret']);
    $this->assertTrue($response['httpCode']===401,
      'Incorrect userId passed to /taxon-observations but request authorised. ' .
      "Http response $response[httpCode].");
    $this->assertTrue($response['response']==='Unauthorized',
      'Incorrect userId passed to /taxon-observations but data still returned. '.
      var_export($response, true));
    $response = $this->callService("taxon-observations", self::$userId, self::$config['shared_secret']);
    $this->assertEquals($response['httpCode'], 400,
        'Requesting taxon observations without params should be a bad request');
    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("taxon-observations", self::$userId, self::$config['shared_secret'],
          array('proj_id' => $projDef['id']));
      $this->assertEquals($response['httpCode'], 400,
          'Requesting taxon observations without edited_date_from should be a bad request');
      $response = $this->callService("taxon-observations", self::$userId, self::$config['shared_secret'],
        array('edited_date_from' => '2015-01-01'));
      $this->assertEquals($response['httpCode'], 400,
        'Requesting taxon observations without proj_id should be a bad request');
      // only test a single project
      break;
    }
  }

  /**
   * Test the /taxon-observations endpoint in valid use.
   * @todo Test the pagination responses
   */
  public function testTaxon_observations_get() {
    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("taxon-observations", self::$userId, self::$config['shared_secret'],
        array('proj_id' => $projDef['id'], 'edited_date_from' => '2015-01-01'));
      $this->assertTrue($response['curlErrno']===0 && $response['httpCode']===200,
        "Invalid response from call to /taxon-observations. HTTP Response $response[httpCode]. Curl error " .
        "$response[curlErrno] ($response[errorMessage]).");
      $apiResponse = json_decode($response['response'], true);
      $this->assertArrayHasKey('paging', $apiResponse, 'Paging missing from response to call to taxon-observations');
      $this->assertArrayHasKey('data', $apiResponse, 'Data missing from response to call to taxon-observations');
      $data = $apiResponse['data'];
      foreach ($data as $occurrence)
        $this->checkValidTaxonOccurrence($occurrence);
      // only test a single project
      break;
    }
  }

  /**
   * Checks that an array retrieved from the API is a valid taxon-occurrence resource.
   * @param $data Array to be tested as a taxon occurrence resource
   */
  private function checkValidTaxonOccurrence($data) {
    $mustHave = array('id', 'href', 'TaxonVersionKey', 'TaxonName', 'StartDate', 'EndDate', 'DateType',
        'Projection', 'Precision', 'Recorder', 'lasteditdate');
    foreach ($mustHave as $key) {
      $this->assertArrayHasKey($key, $data,
          "Missing $key from taxon-observation resource. " . var_export($data, true));
      $this->assertNotEmpty($data[$key],
          "Empty $key in taxon-observation resource" . var_export($data, true));
    }
  }

  private function callService($method, $userId, $sharedSecret, $query=false) {
    $url = url::base(true) . "/services/rest/$method";
    if ($query)
      $url .= '?' . http_build_query($query);
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $hmac = hash_hmac("sha1", $url, $sharedSecret, $raw_output=FALSE);
    curl_setopt($session, CURLOPT_HTTPHEADER, array("Authorization: USER:$userId:HMAC:$hmac"));
    // Do the POST and then close the session
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    $message = curl_error($session);
    return array(
      'errorMessage' => $message ? $message : 'curl ok',
      'curlErrno' => $curlErrno,
      'httpCode' => $httpCode,
      'response' => $response
    );
  }
}
