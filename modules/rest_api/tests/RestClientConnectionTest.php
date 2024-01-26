<?php

use Firebase\JWT\JWT;

/**
 * Unit test class for the REST api clients.
 */
class RestClientConnectionTest extends BaseRestClientTest {

  /**
   * A query that resets some DB data.
   *
   * Allows tests to run sequentially without full fixture rebuild.
   *
   * @var string
   */
  private $resetQuery = <<<SQL
    update rest_api_client_connections
    set allow_confidential=false,
      allow_unreleased=false,
      allow_sensitive=true,
      full_precision_sensitive_records=false,
      filter_id=null,
      sharing='R',
      limit_to_reports=ARRAY[
        'library/occurrences/filterable_explore_list.xml',
        'library/occurrences/filterable_explore_list_mapping.xml',
        'library/groups/groups_for_app.xml'
      ]
    where proj_id='testreportconnection';

    update occurrences
    set sensitivity_precision=null,
      release_status='R'
    where id=1;

    update cache_occurrences_functional
    set sensitive=false,
      release_status='R'
    where id=1;

    update occurrences
    set sensitivity_precision=null,
      release_status='R'
    where id=3;

    update cache_occurrences_functional
    set sensitive=false,
      release_status='R'
    where id=3;
SQL;

  /**
   * Some initial setup.
   *
   * Gets auth and db connection.
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    self::$auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    self::$auth['write_tokens']['persist_auth'] = TRUE;
    self::$db = new Database();
  }

  /**
   * Get JWT to authorise as a client.
   *
   * @param string $privateKey
   *   Key to use when creating the token.
   * @param string $iss
   *   Issuer claim.
   * @param string $clientUsername
   *   Username for the client to authentitcate as.
   * @param int $exp
   *   Expiry timestamp.
   */
  private function getClientJwt($privateKey, $iss, $clientUsername, $exp) {
    require_once 'vendor/autoload.php';
    $payload = [
      'iss' => $iss,
      'http://indicia.org.uk/client:username' => $clientUsername,
      'exp' => $exp,
    ];
    return JWT::encode($payload, $privateKey, 'RS256');
  }

  /**
   * Test that a client and connection can be saved to the DB.
   *
   * Also results in a client and connection that can be used for later tests.
   */
  private function createClientAndConnectionTest() {
    Kohana::log('debug', "Running unit test, RestClientConnectionTest::createClientAndConnectionTest");
    // Create a client.
    $clientData = [
      'rest_api_client:title' => 'Test client',
      'rest_api_client:website_id' => 1,
      'rest_api_client:description' => 'Test description',
      'rest_api_client:username' => 'createtestuser',
      'rest_api_client:secret' => 'mysecret',
      'rest_api_client:public_key' => self::$publicKey,
    ];
    $s = submission_builder::build_submission($clientData, ['model' => 'rest_api_client']);
    $r = data_entry_helper::forward_post_to('rest_api_client', $s, self::$auth['write_tokens'] + ['secret2' => 'mysecret']);
    $this->assertTrue(isset($r['success']), 'Submitting a rest_api_client did not return success response');
    $clientId = $r['success'];
    // Repost same record should fail on a unique index violation.
    $r = data_entry_helper::forward_post_to('rest_api_client', $s, self::$auth['write_tokens'] + ['secret2' => 'mysecret']);
    $this->assertNotTrue(isset($r['success']), 'Submitting a rest_api_client with a duplicate username should not succeed');
    // Secret should be hashed in DB.
    $saved = self::$db->query('SELECT * FROM rest_api_clients WHERE id=' . $clientId)->current();
    $this->assertNotEquals($saved->secret, $clientData['rest_api_client:secret'], 'Saved secret has not been hashed.');
    $this->assertTrue(password_verify($clientData['rest_api_client:secret'], $saved->secret), 'Saved password hash does not verify against the supplied secret');

    // Create a client connection.
    $connectionData = [
      'rest_api_client_connection:title' => 'Test connection',
      'rest_api_client_connection:description' => 'Test description',
      'rest_api_client_connection:proj_id' => 'testreportconnection',
      'rest_api_client_connection:rest_api_client_id' => $clientId,
      'rest_api_client_connection:sharing' => 'R',
      'rest_api_client_connection:full_precision_sensitive_records' => 'f',
      'rest_api_client_connection:read_only' => 'f',
      'rest_api_client_connection:allow_reports' => 't',
      'rest_api_client_connection:limit_to_reports' => [
        'library/occurrences/filterable_explore_list.xml',
        'library/occurrences/filterable_explore_list_mapping.xml',
        // Plus a non-featured one which should fail.
        'library/groups/groups_for_app.xml',
      ],
      'rest_api_client_connection:limit_to_data_resources' => [
        'occurrences',
      ],
    ];
    $s = submission_builder::build_submission($connectionData, ['model' => 'rest_api_client_connection']);
    $r = data_entry_helper::forward_post_to('rest_api_client_connection', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a rest_api_client_connection did not return success response');
    // Repost same record should fail on a unique index violation.
    $r = data_entry_helper::forward_post_to('rest_api_client_connection', $s, self::$auth['write_tokens']);
    $this->assertNotTrue(isset($r['success']), 'Submitting a rest_api_client with a duplicate proj_id should not succeed');
  }

  /**
   * Tests on report access and permissions via a REST API connection.
   */
  private function doSomeReportPermissionTests() {
    // Test rest_api_client_connections.limit_to_reports.
    $r = $this->callService('reports/library/months/filterable_species_counts.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(401, $r['httpCode'], "$this->authMethod request with unauthorised report did not return 401 unauthorised.");
    // Test that proj_id is checked.
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'wrongconnection']);
    $this->assertEquals(401, $r['httpCode'], "$this->authMethod request with incorrect proj_id succeeded");
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(200, $r['httpCode'], "$this->authMethod request with correct secret and report failed");
    $this->assertEquals(2, $r['response']['count'], "$this->authMethod request did not return expected records");
    // Same request, but for a non-feature report should fail due to auth
    // method config.
    $r = $this->callService('library/groups/groups_for_app.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(404, $r['httpCode'], "$this->authMethod request for non-featured report without permission should return 404 Not Found");
    // Test allow_confidential (initially off).
    self::$db->query("update rest_api_client_connections set allow_confidential=true where proj_id='testreportconnection'");
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(3, $r['response']['count'], "$this->authMethod request including confidential did not return expected records");
    // Test allow_sensitive (initially on).
    self::$db->query('update occurrences set sensitivity_precision=1000 where id=3');
    self::$db->query('update cache_occurrences_functional set sensitive=true where id=3');
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(3, $r['response']['count'], "$this->authMethod request including sensitive did not return sensitive records");
    self::$db->query("update rest_api_client_connections set allow_sensitive=false where proj_id='testreportconnection'");
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(2, $r['response']['count'], "$this->authMethod request for report excluding sensitive did return sensitive records");
    // Test allow_unreleased (initially off).
    self::$db->query("update occurrences set sensitivity_precision=null, release_status='U' where id=3");
    self::$db->query("update cache_occurrences_functional set sensitive=false, release_status='U' where id=3");
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(2, $r['response']['count'], "$this->authMethod request for report excluding unreleased did return unreleased records");
    self::$db->query("update rest_api_client_connections set allow_unreleased=true where proj_id='testreportconnection'");
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(3, $r['response']['count'], "$this->authMethod request for report including unreleased did not return unreleased records");
    // Test website limits according to sharing mode.
    self::$db->query("update rest_api_client_connections set allow_confidential=false, allow_unreleased=false, allow_sensitive=true, sharing='V' where proj_id='testreportconnection'");
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(1, $r['response']['count'], "$this->authMethod request for verification reports did not return expected records");
    // Test that a restricted report cannot be loaded without explicit
    // permission.
    self::$db->query("update rest_api_client_connections set limit_to_reports=null where proj_id='testreportconnection'");
    $r = $this->callService('reports/library/occurrences/list_for_elastic_all.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(404, $r['httpCode'], "$this->authMethod request for restricted report without permission should return 404 Not Found");
    self::$db->query("update rest_api_client_connections set limit_to_reports=ARRAY['library/occurrences/list_for_elastic_all.xml'] where proj_id='testreportconnection'");
    $r = $this->callService('reports/library/occurrences/list_for_elastic_all.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(200, $r['httpCode'], "$this->authMethod request for restricted report with permission should return 200 OK");
    // Reset.
    self::$db->query($this->resetQuery);
    // Test filter_id causes the filter to be applied to returned data.
    // In the fixture, filter ID 2 restricts to occurrence ID 2.
    $query = <<<SQL
update rest_api_client_connections
set filter_id=2, allow_confidential=true
where proj_id='testreportconnection'
SQL;
    self::$db->query($query);
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(1, $r['response']['count'], "$this->authMethod request for report with filter did not return expected record count");
    $this->assertEquals(2, (integer) $r['response']['data'][0]['occurrence_id'], "$this->authMethod request for report with filter did not return expected records");

    // Reset.
    self::$db->query($this->resetQuery);
  }

  /**
   * Tests on data resource access and permissions via a REST API connection.
   */
  private function doSomeDataResourcesPermissionTests() {
    // Test rest_api_client_connections.limit_to_data_resources.
    $r = $this->callService('locations', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(401, $r['httpCode'], "$this->authMethod resource request for disallowed data resource did not return 401 unauthorised.");
    // Test that proj_id is checked.
    $r = $this->callService('occurrences', ['proj_id' => 'wrongconnection']);
    $this->assertEquals(401, $r['httpCode'], "$this->authMethod resource request with incorrect proj_id succeeded");
    // General check on allowed resource.
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(200, $r['httpCode'], "$this->authMethod resource request with correct secret and resource failed");
    $this->assertEquals(1, count($r['response']), "$this->authMethod resource request did not return expected records");
    // Test allow_confidential (initially off).
    // Try to update 2nd occurrence in fixture which is confidential.
    $r = $this->callService('occurrences/2', ['proj_id' => 'testreportconnection'], [
      'values' => ['Comment' => 'Attempt to update confidential record'],
    ], [], NULL, 'PUT');
    $this->assertEquals(404, $r['httpCode'], "Attempt to overwrite confidential record didn't return 404 Not found.");
    // Now set allow_confidential.
    self::$db->query("update rest_api_client_connections set allow_confidential=true where proj_id='testreportconnection'");
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(2, count($r['response']), "$this->authMethod request including confidential did not return expected records");
    // Try update again as should now be allowed.
    $r = $this->callService('occurrences/2', ['proj_id' => 'testreportconnection'], [
      'values' => ['Comment' => 'Attempt to update confidential record'],
    ], [], NULL, 'PUT');
    $this->assertEquals(200, $r['httpCode'], "Attempt to overwrite confidential record when allowed didn't return 200 OK.");
    // Test allow_sensitive (initially on)
    self::$db->query('update occurrences set sensitivity_precision=1000 where id=1');
    self::$db->query('update cache_occurrences_functional set sensitive=true where id=1');
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(2, count($r['response']), "$this->authMethod request including sensitive did not return sensitive records");
    $r = $this->callService('occurrences/1', ['proj_id' => 'testreportconnection'], [
      'values' => ['Comment' => 'Attempt to update sensitive record'],
    ], [], NULL, 'PUT');
    $this->assertEquals(200, $r['httpCode'], "Attempt to overwrite sensitive record when allowed didn't return 200 OK.");
    // Turn off allow_sensitive.
    self::$db->query("update rest_api_client_connections set allow_sensitive=false where proj_id='testreportconnection'");
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(1, count($r['response']), "$this->authMethod request excluding sensitive did return sensitive records");
    $r = $this->callService('occurrences/1', ['proj_id' => 'testreportconnection'], [
      'values' => ['Comment' => 'Attempt to update sensitive record'],
    ], [], NULL, 'PUT');
    $this->assertEquals(404, $r['httpCode'], "Attempt to overwrite sensitive record didn't return 404 Not found.");
    // Test allow_unreleased.
    self::$db->query("update occurrences set sensitivity_precision=null, release_status='U' where id=1");
    self::$db->query("update cache_occurrences_functional set sensitive=false, release_status='U' where id=1");
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(1, count($r['response']), "$this->authMethod request excluding unreleased did return unreleased records");
    $r = $this->callService('occurrences/1', ['proj_id' => 'testreportconnection'], [
      'values' => ['Comment' => 'Attempt to update unreleased record'],
    ], [], NULL, 'PUT');
    $this->assertEquals(404, $r['httpCode'], "Attempt to overwrite unreleased record when disallowed didn't return 404 Not Found.");
    // Turn on allow_unreleased.
    self::$db->query("update rest_api_client_connections set allow_unreleased=true where proj_id='testreportconnection'");
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(2, count($r['response']), "$this->authMethod request including unreleased did not return unreleased records");
    $r = $this->callService('occurrences/1', ['proj_id' => 'testreportconnection'], [
      'values' => ['Comment' => 'Attempt to update unreleased record'],
    ], [], NULL, 'PUT');
    $this->assertEquals(200, $r['httpCode'], "Attempt to overwrite unreleased record when allowed didn't return 200 OK.");

    // Reset.
    self::$db->query($this->resetQuery);
  }

  /**
   * Test reports access using directClient auth.
   */
  private function connectDirectClientReportsTest() {
    Kohana::log('debug', "Running unit test, RestClientConnectionTest::connectDirectClientReportsTest");
    $this->authMethod = 'directClient';
    self::$clientUserId = 'createtestuser';
    self::$websitePassword = 'wrong';
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(401, $r['httpCode'], 'directClient request with wrong secret did not return 401 unauthorised.');
    self::$websitePassword = 'mysecret';
    $this->doSomeReportPermissionTests();
  }

  /**
   * Test reports access using jwtClient auth.
   */
  private function connectJwtClientReportsTest() {
    Kohana::log('debug', "Running unit test, RestClientConnectionTest::connectJwtClientReportsTest");
    $date = new DateTimeImmutable();
    $exp = $date->modify('+6 minutes')->getTimestamp();
    $this->authMethod = 'jwtClient';
    // Build an incorrect JWT.
    self::$jwt = $this->getClientJwt(self::$wrongPrivateKey, 'http://www.indicia.org.uk', 'createtestuser', $exp);
    $r = $this->callService('reports/library/occurrences/filterable_explore_list.xml', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(401, $r['httpCode'], 'jwtClient request with wrong key did not return 401 Unauthorised.');
    // Build a correct JWT.
    self::$jwt = $this->getClientJwt(self::$privateKey, 'http://www.indicia.org.uk', 'createtestuser', $exp);
    $this->doSomeReportPermissionTests();
  }

  /**
   * Test data resource access using directClient auth.
   */
  private function connectDirectClientDataResourcesTest() {
    Kohana::log('debug', "Running unit test, RestClientConnectionTest::connectDirectClientDataResourcesTest");
    $this->authMethod = 'directClient';
    self::$clientUserId = 'createtestuser';
    self::$websitePassword = 'wrong';
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(401, $r['httpCode'], 'directClient request with wrong password did not return 401 Unauthorised.');
    self::$websitePassword = 'mysecret';
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(200, $r['httpCode'], 'directClient request with correct password did not return 200 OK.');
    $this->doSomeDataResourcesPermissionTests();
  }

  /**
   * Test data resource access using jwtClient auth.
   */
  private function connectJwtClientDataResourcesTest() {
    Kohana::log('debug', "Running unit test, RestClientConnectionTest::connectJwtClientDataResourcesTest");
    $date = new DateTimeImmutable();
    $exp = $date->modify('+6 minutes')->getTimestamp();
    $this->authMethod = 'jwtClient';
    // Build an incorrect JWT.
    self::$jwt = $this->getClientJwt(self::$wrongPrivateKey, 'http://www.indicia.org.uk', 'createtestuser', $exp);
    $r = $this->callService('occurrences', ['proj_id' => 'testreportconnection']);
    $this->assertEquals(401, $r['httpCode'], 'jwtClient request with wrong key did not return 401 Unauthorised.');
    // Build a correct JWT.
    self::$jwt = $this->getClientJwt(self::$privateKey, 'http://www.indicia.org.uk', 'createtestuser', $exp);
    $this->doSomeDataResourcesPermissionTests();
  }

  /**
   * Checks the application of ES query filters in a query.
   *
   * @param string $responseStr
   *   Response containing the query (because requested in debug mode).
   * @param array $expectedWebsiteIds
   *   Array of website IDs that should be filtered to.
   * @param bool $expectAllowConfidential
   *   True to check that confidential data are included, false to check they
   *   are excluded.
   * @param bool $expectAllowUnreleased
   *   True to check that unreleased data are included, false to check they are
   *   excluded.
   * @param bool $expectAllowSensitive
   *   True to check that sensitive data are included, false to check they are
   *   excluded.
   * @param string $expectedBlurFlag
   *   Either 'B' or 'F' - expected sensitivity_blur flag filter.
   */
  private function checkEsQueryFilters($responseStr, array $expectedWebsiteIds, $expectAllowConfidential, $expectAllowUnreleased, $expectAllowSensitive, $expectedBlurFlag) {
    // Because we use debug mode, response is the query not the data.
    $responseObj = json_decode($responseStr, TRUE);
    $this->assertTrue(isset($responseObj['query']) && isset($responseObj['query']['bool']) && isset($responseObj['query']['bool']['must']),
      'ES query does not contain expected bool filter section.');
    $correctWebsiteFilterFound = FALSE;
    $confidentialAllowed = TRUE;
    $unreleasedAllowed = TRUE;
    $sensitiveAllowed = TRUE;
    $blurFlagChecked = FALSE;
    foreach ($responseObj['query']['bool']['must'] as $mustFilter) {
      $correctWebsiteFilterFound = $correctWebsiteFilterFound
        || (isset($mustFilter['terms']) && isset($mustFilter['terms']['metadata.website.id']) && $mustFilter['terms']['metadata.website.id'] === $expectedWebsiteIds);
      if (isset($mustFilter['query_string']) && isset($mustFilter['query_string']['query'])) {
        $query = $mustFilter['query_string']['query'];
        $confidentialAllowed = $confidentialAllowed && (strpos($query, 'metadata.confidential:false') === FALSE);
        $unreleasedAllowed = $unreleasedAllowed && (strpos($query, 'metadata.release_status:R') === FALSE);
        $sensitiveAllowed = $sensitiveAllowed && (strpos($query, 'metadata.sensitive:false') === FALSE);
        $blurFlagChecked = $blurFlagChecked || (strpos($query, "((metadata.sensitivity_blur:$expectedBlurFlag) OR (!metadata.sensitivity_blur:*))") !== FALSE);
      }
    }
    $this->assertTrue($correctWebsiteFilterFound, 'ES query does not contain expected bool filter section.');
    $this->assertEquals($expectAllowConfidential, $confidentialAllowed, $expectAllowConfidential ?
      'Confidential records blocked even though should be allowed' : 'Confidential records allowed even though should be blocked');
    $this->assertEquals($expectAllowUnreleased, $unreleasedAllowed, $expectAllowUnreleased ?
      'Unreleased records blocked even though should be allowed' : 'Unreleased records allowed even though should be blocked');
    $this->assertEquals($expectAllowSensitive, $sensitiveAllowed, $expectAllowSensitive ?
      'Sensitive records blocked even though should be allowed' : 'Sensitive records allowed even though should be blocked');
    $this->assertTrue($blurFlagChecked, 'The sensitivity_blur flag was not checked or had the wrong value');
  }

  /**
   * Test Elasticsearch requests using directClient auth.
   */
  private function connectDirectClientEsTest() {
    Kohana::log('debug', "Running unit test, RestClientConnectionTest::connectDirectClientEsTest");
    $this->authMethod = 'directClient';
    self::$clientUserId = 'createtestuser';
    self::$websitePassword = 'mysecret';
    $r = $this->callService('es', ['proj_id' => 'testreportconnection', 'debug' => 'true']);
    $this->assertEquals(400, $r['httpCode'], 'Invalid ES request should return 400 Bad Request');
    $r = $this->callService('es/_search', ['proj_id' => 'testreportconnection', 'debug' => 'true']);
    $this->assertEquals(200, $r['httpCode'], 'Valid ES request should return 200 OK');
    $this->checkEsQueryFilters($r['response'], ['1', '2'], FALSE, FALSE, TRUE, 'B');
    $query = <<<SQL
update rest_api_client_connections
set allow_confidential=true,
  allow_unreleased=true,
  allow_sensitive=false,
  sharing='V'
where proj_id='testreportconnection'
SQL;
    self::$db->query($query);
    $r = $this->callService('es/_search', ['proj_id' => 'testreportconnection', 'debug' => 'true']);
    $this->assertEquals(200, $r['httpCode'], 'Valid ES request should return 200 OK');
    // Should still be blurred even though for verification.
    $this->checkEsQueryFilters($r['response'], ['1'], TRUE, TRUE, FALSE, 'B');
    $query = <<<SQL
update rest_api_client_connections
set full_precision_sensitive_records=true
where proj_id='testreportconnection'
SQL;
    self::$db->query($query);
    $r = $this->callService('es/_search', ['proj_id' => 'testreportconnection', 'debug' => 'true']);
    $this->assertEquals(200, $r['httpCode'], 'Valid ES request should return 200 OK');
    // Should now be full precision.
    $this->checkEsQueryFilters($r['response'], ['1'], TRUE, TRUE, FALSE, 'F');
    // In the fixture, filter ID 2 restricts to occurrence ID 2.
    $query = <<<SQL
update rest_api_client_connections
set filter_id=2
where proj_id='testreportconnection'
SQL;
    self::$db->query($query);
    $r = $this->callService('es/_search', ['proj_id' => 'testreportconnection', 'debug' => 'true']);
    $this->assertEquals(200, $r['httpCode'], 'Valid ES request should return 200 OK');
    $this->assertNotEquals(FALSE, strpos($r['response'], '{"terms":{"id":["2"]}}'), 'jwtClient ES request with filter ID did not apply the required occ_id filter.');
    $this->assertNotEquals(FALSE, strpos($r['response'], '{"bool":{"must_not":[{"term":{"identification.verification_status":"R"}}]}}'), 'jwtClient ES request with filter ID did not apply the required status filter.');
    // Reset.
    self::$db->query($this->resetQuery);
  }

  /**
   * Test a JWT client's access to Elasticsearch.
   */
  private function connectJwtClientEsTest() {
    Kohana::log('debug', "Running unit test, RestClientConnectionTest::connectJwtClientEsTest");
    $date = new DateTimeImmutable();
    $exp = $date->modify('+6 minutes')->getTimestamp();
    $this->authMethod = 'jwtClient';
    // Build an incorrect JWT.
    self::$jwt = $this->getClientJwt(self::$wrongPrivateKey, 'http://www.indicia.org.uk', 'createtestuser', $exp);
    $r = $this->callService('es/_search', ['proj_id' => 'testreportconnection', 'debug' => 'true']);
    $this->assertEquals(401, $r['httpCode'], 'jwtClient ES request with wrong key did not return 401 Unauthorised.');
    // Build a correct JWT.
    self::$jwt = $this->getClientJwt(self::$privateKey, 'http://www.indicia.org.uk', 'createtestuser', $exp);
    $r = $this->callService('es/_search', ['proj_id' => 'testreportconnection', 'debug' => 'true']);
    $this->assertEquals(200, $r['httpCode'], 'jwtClient ES request with correct key did not return 200 OK.');
  }

  /**
   * Single test method to save rebuilding db fixture repetitively.
   */
  public function testAllClientConnections() {
    $this->createClientAndConnectionTest();
    $this->connectDirectClientReportsTest();
    $this->connectJwtClientReportsTest();
    $this->connectDirectClientDataResourcesTest();
    $this->connectJwtClientDataResourcesTest();
    $this->connectDirectClientEsTest();
    $this->connectJwtClientEsTest();

    // @todo Test we haven't broken sensitive records at full precision for normal verification.
    // @todo Ensure that normal reports also respect full_precision_sensitive_records settings.
    // @todo Filter ID control on edit view.
  }

}
