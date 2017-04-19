<?php

/**
 * Unit test class for the REST api controller.
 * @todo Test sharing mode on project filters is respected.
 *
 */
class Rest_ControllerTest extends Indicia_DatabaseTestCase {

  private static $clientUserId;
  private static $config;
  private static $websiteId=1;
  private static $websitePassword='password';
  private static $userId=1;
  private static $userPassword='password';
  // In the fixture, the 2nd filter is the one we linked to a user.
  private static $userFilterId=2;
  private static $oAuthAccessToken;

  private $authMethod = 'hmacClient';

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
            'defines_permissions' => 'f',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1
          ),
          array(
            'title' => 'Test user permission filter',
            'description' => 'Filter for unit testing',
            'definition' => '{"quality":"!R","occurrence_id":2}',
            'defines_permissions' => 't',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
          ),
        ),
        'filters_users' => array(
          array(
            'filter_id' => 2,
            'user_id' => 1,
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1
          )
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

    // Dependencies prevent us adding a user with known password, so we'll update the
    // existing one with the hash for 'password'.
    $db = new Database();
    $db->update(
      'users',
      array('password' => '18d025c6c8809e34371e2ec7d84215bd3eb6031dcd804006f4'),
      array('id' => 1)
    );

    return $compositeDs;
  }

  public static function setUpBeforeClass() {
    // grab the clients registered on this system
    $clientUserIds = array_keys(Kohana::config('rest.clients'));
    $clientConfigs = array_values(Kohana::config('rest.clients'));

    // just test the first client
    self::$clientUserId = $clientUserIds[0];
    self::$config = $clientConfigs[0];
  }

  protected function setUp() {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();
  }

  protected function tearDown() {

  }

  public function testoAuth2() {
    $url = url::base(true) . "services/rest/token";
    $session = curl_init();
    // Set the cUrl options.
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    // try a request with no post data
    $r = $this->getCurlResponse($session);
    $this->assertEquals(400, $r['httpCode'], 'Token request without parameters should be a bad request');

    // Now try some post data, but use an invalid password
    curl_setopt($session, CURLOPT_POST, 1);
    $post = 'grant_type=password&username=admin&password=sunnyday&client_id=website_id:1';
    curl_setopt($session, CURLOPT_POSTFIELDS, $post);
    $r = $this->getCurlResponse($session);
    $this->assertEquals(401, $r['httpCode'], 'Incorrect password in token request should result in Unauthorised');

    // Try again with the correct password
    curl_setopt($session, CURLOPT_POST, 1);
    $post = 'grant_type=password&username=admin&password=password&client_id=website_id:1';
    curl_setopt($session, CURLOPT_POSTFIELDS, $post);
    $r = $this->getCurlResponse($session, true);
    $this->assertEquals(200, $r['httpCode'], 'Valid request to /token failed.');
    self::$oAuthAccessToken = $r['response']->access_token;
    $this->assertNotEmpty(self::$oAuthAccessToken, 'No oAuth access token returned');

    // Check oAuth2 doesn't allow access to incorrect resources
    $this->authMethod = 'oAuth2User';
    $response = $this->callService('projects');
    $this->assertEquals(401, $response['httpCode'], 'Invalid authentication method oAuth2 for projects but response still OK. ' .
      "Http response $response[httpCode].");

    // Now try a valid request with the access token
    $response = $this->callService('taxon-observations', array('edited_date_from' => '2015-01-01'));
    var_export($response);
    $this->assertEquals(200, $response['httpCode'], 'oAuth2 request to taxon-observations failed.');

      // Now try a bad access token
    self::$oAuthAccessToken = '---';
    $response = $this->callService('taxon-observations', array('edited_date_from' => '2015-01-01'));
    $this->assertEquals(401, $response['httpCode'], 'Invalid token oAuth2 request to taxon-observations should fail.');
  }

  public function testProjects_authentication() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_clientAuthentication");

    $this->authMethod = 'hmacClient';
    $this->checkResourceAuthentication('projects');
    $this->authMethod = 'directClient';
    $this->checkResourceAuthentication('projects');
    // user and website authentications don't allow access to projects
    $this->authMethod = 'hmacUser';
    $response = $this->callService('projects');
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method hmacUser for projects but response still OK. ' .
      "Http response $response[httpCode].");
    $this->authMethod = 'directUser';
    $response = $this->callService('projects');
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method directUser for projects but response still OK. ' .
      "Http response $response[httpCode].");
    $this->authMethod = 'hmacWebsite';
    $response = $this->callService('projects');
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method hmacWebsite for projects but response still OK. ' .
      "Http response $response[httpCode].");
    $this->authMethod = 'directWebsite';
    $response = $this->callService('projects');
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method directWebsite for projects but response still OK. ' .
      "Http response $response[httpCode].");

    $this->authMethod = 'hmacClient';
  }

  public function testProjects_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_get");
    
    $response = $this->callService('projects');
    $this->assertResponseOk($response, '/projects');
    $viaApi = json_decode($response['response']);
    $viaConfig = self::$config['projects'];
    $this->assertEquals(count($viaConfig), count($viaApi->data), 'Incorrect number of projects returned from /projects.');
    foreach ($viaApi->data as $projDef) {
      $this->assertArrayHasKey($projDef->id, $viaConfig, "Unexpected project $projDef->id returned by /projects.");
      $this->assertEquals($viaConfig[$projDef->id]['title'], $projDef->title,
        "Unexpected title $projDef->title returned for project $projDef->id by /projects.");
      $this->assertEquals($viaConfig[$projDef->id]['description'], $projDef->description,
        "Unexpected description $projDef->description returned for project $projDef->id by /projects.");
    }
  }

  public function testProjects_get_id() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_get_id");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("projects/$projDef[id]");
      $this->assertResponseOk($response, "/projects/$projDef[id]");
      $fromApi = json_decode($response['response']);
      $this->assertEquals($projDef['title'], $fromApi->title,
          "Unexpected title $fromApi->title returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($projDef['title'], $fromApi->title,
          "Unexpected title $fromApi->title returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($projDef['description'], $fromApi->description,
          "Unexpected description $fromApi->description returned for project $projDef[id] by /projects/$projDef[id].");
    }
  }

  public function testTaxon_observations_authentication() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_clientAuthentication");
    $proj_id = self::$config['projects'][array_keys(self::$config['projects'])[0]]['id'];
    $queryWithProj = array('proj_id' => $proj_id, 'edited_date_from' => '2015-01-01');
    $query = array('edited_date_from' => '2015-01-01');

    $this->authMethod = 'hmacClient';
    $this->checkResourceAuthentication('taxon-observations', $queryWithProj);
    $this->authMethod = 'directClient';
    $this->checkResourceAuthentication('taxon-observations', $queryWithProj);
    $this->authMethod = 'directUser';
    $this->checkResourceAuthentication('taxon-observations', $query);
    $this->authMethod = 'directUserWithFilter';
    $this->checkResourceAuthentication('taxon-observations', $query);
    $this->authMethod = 'hmacWebsite';
    $this->checkResourceAuthentication('taxon-observations', $query);
    $this->authMethod = 'directWebsite';
    $this->checkResourceAuthentication('taxon-observations', $query);

    $this->authMethod = 'hmacClient';
  }

  public function testTaxon_observations_get_incorrect_params() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testTaxon_observations_get_incorrect_params");
    $response = $this->callService("taxon-observations");
    $this->assertEquals(400, $response['httpCode'],
        'Requesting taxon observations without params should be a bad request');
    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("taxon-observations", array('proj_id' => $projDef['id']));
      $this->assertEquals(400, $response['httpCode'],
          'Requesting taxon observations without edited_date_from should be a bad request');
      $response = $this->callService("taxon-observations", array('edited_date_from' => '2015-01-01'));
      $this->assertEquals(400, $response['httpCode'],
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
      $response = $this->callService("taxon-observations", array(
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

  /**
   * Test the /annotations endpoint in valid use.
   * @todo Test the pagination responses
   * @todo Test the annotations/id endpoint
   */
  public function testAnnotations_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testAnnotations_get");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("annotations", array('proj_id' => $projDef['id'], 'edited_date_from' => '2015-01-01'));
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
   * Test for accessing the reports hierarchy.
   */
  public function testReportsHierarchy_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportsHierarchy_get");

    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports", array('proj_id' => $projDef['id'])
    );
    $this->assertResponseOk($response, '/reports');
    $response = json_decode($response['response'], true);
    // Check some folders and reports that should definitely exist.
    $this->checkReportFolderInReponse($response, 'featured');
    $this->checkReportFolderInReponse($response, 'library');
    $this->checkReportInReponse($response, 'demo');
    $response = $this->callService("reports/featured", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/featured');
    $response = json_decode($response['response'], true);
    $this->checkReportInReponse($response, 'library/occurrences/filterable_explore_list');
    $response = $this->callService("reports/library", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library');
    $response = json_decode($response['response'], true);
    $this->checkReportFolderInReponse($response, 'occurrences');
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $response = json_decode($response['response'], true);
    $this->checkReportInReponse($response, 'filterable_explore_list');
  }

  public function testReportParams_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportParams_get");

    // First grab a list of reports so we can use the links to get the correct params URL
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $response = json_decode($response['response'], true);
    $reportDef = $response['filterable_explore_list'];
    $this->assertArrayHasKey('params', $reportDef, 'Report response does not define parameters');
    $this->assertArrayHasKey('href', $reportDef['params'], 'Report parameters missing href');
    // Now grab the params URL output and check it
    $response = $this->callUrl($reportDef['params']['href'], self::$clientUserId, self::$config['shared_secret']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml/params');
    $response = json_decode($response['response'], true);
    $this->assertArrayHasKey('data', $response);
    $this->assertArrayHasKey('smpattrs', $response['data']);
    $this->assertArrayHasKey('occurrence_id', $response['data']);
  }

  public function testReportColumns_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportColumns_get");

    // First grab a list of reports so we can use the links to get the correct columns URL
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $response = json_decode($response['response'], true);
    $reportDef = $response['filterable_explore_list'];
    $this->assertArrayHasKey('columns', $reportDef, 'Report response does not define columns');
    $this->assertArrayHasKey('href', $reportDef['columns'], 'Report columns missing href');
    // Now grab the columns URL output and check it
    $response = $this->callUrl($reportDef['columns']['href'], self::$clientUserId, self::$config['shared_secret']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml/columns');
    $response = json_decode($response['response'], true);
    $this->assertArrayHasKey('data', $response);
    $this->assertArrayHasKey('occurrence_id', $response['data']);
    $this->assertArrayHasKey('taxon', $response['data']);
  }

  public function testReportOutput_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportOutput_get");

    // First grab a list of reports so we can use the links to get the correct columns URL
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $response = json_decode($response['response'], TRUE);
    $reportDef = $response['filterable_explore_list'];
    $this->assertArrayHasKey('href', $reportDef, 'Report response missing href');
    // Now grab the columns URL output and check it
    $response = $this->callUrl($reportDef['href'], self::$clientUserId, self::$config['shared_secret']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml');
    $response = json_decode($response['response'], TRUE);
    $this->assertArrayHasKey('data', $response);
    $this->assertCount(1, $response['data'], 'Report call returns incorrect record count');
    $this->assertEquals(1, $response['data'][0]['occurrence_id'], 'Report call returns incorrect record');
  }

  /**
   * Tests authentication against a resource, by passing incorrect user or secret, then
   * finally passing the correct details to check a valid response returns.
   * @param $resource
   * @param string $user User identifier, either client system ID, user ID or website ID.
   * @param string $secret Secret or password to go with the $user.
   * @param array $query Query parameters to pass in the URL
   */
  private function checkResourceAuthentication($resource, $query = array()) {
    $correctClientUserId = self::$clientUserId;
    $correctWebsiteId = self::$websiteId;
    $correctUserId = self::$userId;
    $correctClientSecret = self::$config['shared_secret'];
    $correctWebsitePassword = self::$websitePassword;
    $correctUserPassword = self::$userPassword;

    // break the secrets/passwords
    self::$clientUserId = $correctClientUserId;
    self::$websiteId = $correctWebsiteId;
    self::$userId = $correctUserId;
    self::$config['shared_secret'] = '---';
    self::$websitePassword = '---';
    self::$userPassword = '---';

    $response = $this->callService($resource, $query);
    $this->assertEquals(401, $response['httpCode'], "Incorrect secret or password passed to /$resource but request authorised. " .
      "Http response $response[httpCode].");
    $this->assertEquals('Unauthorized', $response['response'], "Incorrect secret or password passed to /$resource but data still returned. ".
      var_export($response, true));
    self::$config['shared_secret'] = $correctClientSecret;
    self::$websitePassword = $correctWebsitePassword;
    self::$userPassword = $correctUserPassword;

    // break the user IDs
    self::$clientUserId = '---';
    self::$websiteId = '---';
    self::$userId = '---';
    self::$config['shared_secret'] = $correctClientSecret;
    self::$websitePassword = $correctWebsitePassword;
    self::$userPassword = $correctUserPassword;
    $response = $this->callService($resource, $query);
    $this->assertEquals(401, $response['httpCode'], "Incorrect userId passed to /$resource but request authorised. " .
      "Http response $response[httpCode].");
    $this->assertEquals('Unauthorized', $response['response'], "Incorrect userId passed to /$resource but data still returned. " .
      var_export($response, true));

    // now test with everything correct
    self::$clientUserId = $correctClientUserId;
    self::$websiteId = $correctWebsiteId;
    self::$userId = $correctUserId;
    self::$config['shared_secret'] = $correctClientSecret;
    self::$websitePassword = $correctWebsitePassword;
    self::$userPassword = $correctUserPassword;
    $response = $this->callService($resource, $query);
    $this->assertResponseOk($response, "/$resource");
  }

  /**
   * An assertion that the response object returned by a call to getCurlResponse
   * indicates a successful request.
   * @param array $response Response data returned by getCurlReponse().
   * @param string $apiCall Name of the API method being called, e.g. /projects
   */
  private function assertResponseOk($response, $apiCall) {
    $this->assertEquals(200, $response['httpCode'],
      "Invalid response from call to $apiCall. HTTP Response $response[httpCode]. Curl error " .
      "$response[curlErrno] ($response[errorMessage]).");
    $this->assertEquals(0, $response['curlErrno'],
      "Invalid response from call to $apiCall. HTTP Response $response[httpCode]. Curl error " .
      "$response[curlErrno] ($response[errorMessage]).");
    $decoded = json_decode($response['response']);
    $this->assertNotNull($decoded, 'JSON response could not be decoded');
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
    $session = $this->initCurl($data['taxonObservation']['href'], self::$clientUserId, self::$config['shared_secret']);
    $response = $this->getCurlResponse($session);
    $this->assertResponseOk($response, '/taxon-observations/id');
    $apiResponse = json_decode($response['response'], true);
    $this->checkValidTaxonObservation($apiResponse);
  }

  /**
   * Assert that a folder exists in the response from a call to /reports.
   * @param array $response
   * @param string $folder
   */
  private function checkReportFolderInReponse($response, $folder) {
    $this->assertArrayHasKey($folder, $response);
    $this->assertArrayHasKey('type', $response[$folder]);
    $this->assertEquals('folder', $response[$folder]['type']);
  }

  /**
   * Assert that a folder exists in the response from a call to /reports.
   * @param array $response
   * @param string $folder
   */
  private function checkReportInReponse($response, $reportFile) {
    $this->assertArrayHasKey($reportFile, $response);
    $this->assertArrayHasKey('type', $response[$reportFile]);
    $this->assertEquals('report', $response[$reportFile]['type']);
  }

  private function setAuthHeader($session, $url) {
    switch ($this->authMethod) {
      case 'hmacUser':
        $user = self::$userId;
        $website = self::$websiteId;
        $hmac = hash_hmac("sha1", $url, self::$userPassword, $raw_output = FALSE);
        $authString = "USER_ID:$user:WEBSITE_ID:$website:HMAC:$hmac";
        break;
      case 'hmacClient':
        $user = self::$clientUserId;
        $hmac = hash_hmac("sha1", $url, self::$config['shared_secret'], $raw_output = FALSE);
        $authString = "USER:$user:HMAC:$hmac";
        break;
      case 'hmacWebsite':
        $user = self::$websiteId;
        $hmac = hash_hmac("sha1", $url, self::$websitePassword, $raw_output = FALSE);
        $authString = "WEBSITE_ID:$user:HMAC:$hmac";
        break;
      case 'directUser':
        $user = self::$userId;
        $website = self::$websiteId;
        $password = self::$userPassword;
        $authString = "USER_ID:$user:WEBSITE_ID:$website:SECRET:$password";
        break;
      case 'directUserWithFilter':
        $user = self::$userId;
        $website = self::$websiteId;
        $password = self::$userPassword;
        $filterId = self::$userFilterId;
        $authString = "USER_ID:$user:WEBSITE_ID:$website:FILTER_ID:$filterId:SECRET:$password";
        break;
      case 'directClient':
        $user = self::$clientUserId;
        $password = self::$websitePassword;
        $authString = "USER:$user:SECRET:$password";
        break;
      case 'directWebsite':
        $user = self::$websiteId;
        $password = self::$websitePassword;
        $authString = "WEBSITE_ID:$user:SECRET:$password";
        break;
      case 'oAuth2User':
        $authString = "Bearer " . self::$oAuthAccessToken;
        break;
      default:
        $this->fail("$this->authMethod test not implemented");
        break;
    }

    curl_setopt($session, CURLOPT_HTTPHEADER, array("Authorization: $authString"));
  }

  private function initCurl($url) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    $this->setAuthHeader($session, $url);
    return $session;
  }

  private function getCurlResponse($session, $json = FALSE) {
    // Do the POST and then close the session
    $response = curl_exec($session);
    if ($json)
      $response = json_decode($response);
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

  private function callUrl($url) {
    $session = $this->initCurl($url);
    Kohana::log('debug', "Making request to $url");
    $response = $this->getCurlResponse($session);
    return $response;
  }

  /**
   * A generic method to call the REST Api's web services.
   * @param $method
   * @param $user
   * @param $sharedSecret
   * @param mixed|FALSE $query
   * @return array
   */
  private function callService($method, $query=false) {
    $url = url::base(true) . "services/rest/$method";
    if ($query)
      $url .= '?' . http_build_query($query);
    return $this->callUrl($url);
  }
}
