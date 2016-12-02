<?php

/**
 * Unit test class for the REST api controller.
 * @todo Test sharing mode on project filters is respected.
 *
 */
class Rest_ControllerTest extends Indicia_DatabaseTestCase {

  private static $userId;
  private static $config;

  public function getDataSet()
  {
    $ds1 =  new PHPUnit_Extensions_Database_DataSet_YamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  
    /* Create a filter for the test project defined in config/rest.php.travis.
     * Create an occurrence comment for annotation testing.
     */
    $ds2 = new Indicia_ArrayDataSet(
      array(
        'filters' => array(
          array(
            'title' => 'Test filter',
            'description' => 'Filter for unit testing',
            'definition' => '{"quality":"!R"}',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
          ),
        ),
        'occurrence_comments' => array(
          array(
            'comment' => 'Occurrence comment for unit testing',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
            'occurrence_id' => 1,
          ),
        ),
      )
    );
    
    $compositeDs = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet();
    $compositeDs->addDataSet($ds1);
    $compositeDs->addDataSet($ds2); 
    return $compositeDs;
  }

  public static function setUpBeforeClass() {
    // grab the clients registered on this system
    $userIds = array_keys(Kohana::config('rest.clients'));
    $clientConfigs = array_values(Kohana::config('rest.clients'));

    // just test the first client
    self::$userId = $userIds[0];
    self::$config = $clientConfigs[0];
  }

  protected function setUp() {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();
  }

  protected function tearDown() {

  }

  public function testProjects_getUnauthorised() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_getUnauthorised");
    
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
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_get");
    
    $response = $this->callService('projects', self::$userId, self::$config['shared_secret']);
    $this->assertResponseOk($response, '/projects');
    $viaApi = json_decode($response['response']);
    $viaConfig = self::$config['projects'];
    $this->assertEquals(count($viaConfig), count($viaApi->data), 'Incorrect number of projects returned from /projects.');
    foreach ($viaApi->data as $projDef) {
      $this->assertArrayHasKey($projDef->id, $viaConfig, "Unexpected project $projDef->id returned by /projects.");
      $this->assertEquals($projDef->title, $viaConfig[$projDef->id]['title'],
          "Unexpected title $projDef->title returned for project $projDef->id by /projects.");
      $this->assertEquals($projDef->description, $viaConfig[$projDef->id]['description'],
        "Unexpected description $projDef->description returned for project $projDef->id by /projects.");
    }
  }

  public function testProjects_get_id() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_get_id");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("projects/$projDef[id]", self::$userId, self::$config['shared_secret']);
      $this->assertResponseOk($response, "/projects/$projDef[id]");
      $fromApi = json_decode($response['response']);
      $this->assertEquals($fromApi->title, $projDef['title'],
          "Unexpected title $fromApi->title returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($fromApi->title, $projDef['title'],
          "Unexpected title $fromApi->title returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($fromApi->description, $projDef['description'],
          "Unexpected description $fromApi->description returned for project $projDef[id] by /projects/$projDef[id].");
    }
  }

  public function testTaxon_observations_get_incorrect_params() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testTaxon_observations_get_incorrect_params");

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
   * @todo Test the /taxon-observations/id endpoint
   */
  public function testTaxon_observations_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testTaxon_observations_get");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("taxon-observations", self::$userId, self::$config['shared_secret'],
        array(
          'proj_id' => $projDef['id'],
          'edited_date_from' => '2015-01-01',
          'edited_date_to' => date("Y-m-d\TH:i:s")
        )
      );
      $this->assertResponseOk($response, '/taxon-observations');
      $apiResponse = json_decode($response['response'], true);
      $this->assertArrayHasKey('paging', $apiResponse, 'Paging missing from response to call to taxon-observations');
      $this->assertArrayHasKey('data', $apiResponse, 'Data missing from response to call to taxon-observations');
      $data = $apiResponse['data'];
      $this->assertInternalType('array', $data, 'Taxon-observations data invalid. ' . var_export($data, true));
      $this->assertNotCount(0, $data, 'Taxon-observations data absent. ' . var_export($data, true));
      foreach ($data as $occurrence)
        $this->checkValidTaxonObservation($occurrence);
      // only test a single project
      break;
    }
  }

  public function testAnnotations_get_incorrect_params() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testAnnotations_get_incorrect_params");

    $response = $this->callService('annotations', self::$userId, '---');
    $this->assertTrue($response['httpCode'] === 401,
      'Incorrect shared secret passed to /annotations but request authorised. ' .
      "Http response $response[httpCode].");
    $this->assertTrue($response['response'] === 'Unauthorized',
      'Incorrect shared secret passed to /annotations but data still returned. ' .
      var_export($response, TRUE));
    $response = $this->callService('annotations', '---', self::$config['shared_secret']);
    $this->assertTrue($response['httpCode'] === 401,
      'Incorrect userId passed to /annotations but request authorised. ' .
      "Http response $response[httpCode].");
    $this->assertTrue($response['response'] === 'Unauthorized',
      'Incorrect userId passed to /annotations but data still returned. ' .
      var_export($response, TRUE));
  }

  /**
   * Test the /annotations endpoint in valid use.
   * @todo Test the pagination responses
   * @todo Test the annotations/id endpoint
   */
  public function testAnnotations_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testAnnotations_get");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("annotations", self::$userId, self::$config['shared_secret'],
        array('proj_id' => $projDef['id'], 'edited_date_from' => '2015-01-01'));
      $this->assertResponseOk($response, '/annotations');
      $apiResponse = json_decode($response['response'], true);
      $this->assertArrayHasKey('paging', $apiResponse, 'Paging missing from response to call to annotations');
      $this->assertArrayHasKey('data', $apiResponse, 'Data missing from response to call to annotations');
      $data = $apiResponse['data'];
      $this->assertInternalType('array', $data, 'Annotations data invalid. ' . var_export($data, true));
      $this->assertNotCount(0, $data, 'Annotations data absent. ' . var_export($data, true));
      foreach ($data as $annotation)
        $this->checkValidAnnotation($annotation);
      // only test a single project
      break;
    }
  }

  /**
   * An assertion that the response object returned by a call to getCurlResponse
   * indicates a successful request.
   * @param array $response Response data returned by getCurlReponse().
   * @param string $apiCall Name of the API method being called, e.g. /projects
   */
  private function assertResponseOk($response, $apiCall) {
    $this->assertEquals($response['httpCode'], 200,
      "Invalid response from call to $apiCall. HTTP Response $response[httpCode]. Curl error " .
      "$response[curlErrno] ($response[errorMessage]).");
    $this->assertEquals($response['curlErrno'], 0,
      "Invalid response from call to $apiCall. HTTP Response $response[httpCode]. Curl error " .
      "$response[curlErrno] ($response[errorMessage]).");
  }

  /**
   * Checks that an array retrieved from the API is a valid taxon-occurrence resource.
   * @param $data Array to be tested as a taxon occurrence resource
   */
  private function checkValidTaxonObservation($data) {
    $this->assertInternalType('array', $data, 'Taxon-observation object invalid. ' . var_export($data, true));
    $mustHave = array('id', 'href', 'datasetName', 'taxonVersionKey', 'taxonName',
        'startDate', 'endDate', 'dateType', 'projection', 'precision', 'recorder', 'lastEditDate');
    foreach ($mustHave as $key) {
      $this->assertArrayHasKey($key, $data,
          "Missing $key from taxon-observation resource. " . var_export($data, true));
      $this->assertNotEmpty($data[$key],
          "Empty $key in taxon-observation resource" . var_export($data, true));
    }
    // @todo Format tests
  }

  /**
   * Checks that an array retrieved from the API is a valid annotation resource.
   * @param $data Array to be tested as an annotation resource
   */
  private function checkValidAnnotation($data) {
    $this->assertInternalType('array', $data, 'Annotation object invalid. ' . var_export($data, true));
    $mustHave = array('id', 'href', 'taxonObservation', 'taxonVersionKey', 'comment',
        'question', 'authorName', 'dateTime');
    foreach ($mustHave as $key) {
      $this->assertArrayHasKey($key, $data,
        "Missing $key from annotation resource. " . var_export($data, true));
      $this->assertNotEmpty($data[$key],
        "Empty $key in annotation resource" . var_export($data, true));
    }
    if (!empty($data['statusCode1']))
      $this->assertRegExp('/[AUN]/', $data['statusCode1'], 'Invalid statusCode1 value for annotation');
    if (!empty($data['statusCode2']))
      $this->assertRegExp('/[1-6]/', $data['statusCode2'], 'Invalid statusCode2 value for annotation');
    // We should be able to request the taxon observation associated with the occurrence
    $session = $this->initCurl($data['taxonObservation']['href'], self::$userId, self::$config['shared_secret']);
    $response = $this->getCurlResponse($session);
    $this->assertResponseOk($response, '/taxon-observations/id');
    $apiResponse = json_decode($response['response'], true);
    $this->checkValidTaxonObservation($apiResponse);
  }

  private function initCurl($url, $userId, $sharedSecret) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $hmac = hash_hmac("sha1", $url, $sharedSecret, $raw_output=FALSE);
    curl_setopt($session, CURLOPT_HTTPHEADER, array("Authorization: USER:$userId:HMAC:$hmac"));
    return $session;
  }

  private function getCurlResponse($session) {
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

  /**
   * A generic method to call the REST Api's web services.
   * @param $method
   * @param $userId
   * @param $sharedSecret
   * @param mixed|FALSE $query
   * @return array
   */
  private function callService($method, $userId, $sharedSecret, $query=false) {
    $url = url::base(true) . "/services/rest/$method";
    if ($query)
      $url .= '?' . http_build_query($query);
    Kohana::log('debug', "Making request to $url");
    $session = $this->initCurl($url, $userId, $sharedSecret);
    $response = $this->getCurlResponse($session);
    Kohana::log('debug', "Received response " . print_r($response, TRUE));
    return $response;
  }
}
