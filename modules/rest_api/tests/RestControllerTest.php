<?php

/**
 * Unit test class for the REST api controller.
 *
 * @todo Test sharing mode on project filters is respected.
 */
class RestControllerTest extends BaseRestClientTest {

  /**
   * In the fixture, the 2nd filter is the one we linked to a user.
   *
   * @var int
   */
  private static $userFilterId = 2;

  /**
   * Setup before tests are run.
   */
  public static function setUpBeforeClass(): void {
    // Grab the clients registered on this system.
    $clientUserIds = array_keys(Kohana::config('rest.clients'));
    $clientConfigs = array_values(Kohana::config('rest.clients'));

    // Just test the first client.
    self::$clientUserId = $clientUserIds[0];
    self::$config = $clientConfigs[0];

    // Dependencies prevent us adding a user with known password, so we'll
    // update the existing one with the hash for 'password'.
    $db = new Database();
    $db->update(
      'users',
      ['password' => '18d025c6c8809e34371e2ec7d84215bd3eb6031dcd804006f4'],
      ['id' => 1]
    );
  }

  protected function setUp(): void {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();
    // Remove created users from previous run if using PHP unit locally, so
    // they don't accumulate. Users and people can't be added by fixture due to
    // circular foreign key constraints on user 1, so this isn't automatic when
    // the fixture is built.
    $db = new Database();
    $db->query('DELETE FROM users WHERE id>3');
    $db->query('DELETE FROM people WHERE id>3');
    // Make sure public key stored.
    $db = new Database();
    $db->update(
      'websites',
      ['public_key' => self::$publicKey],
      ['id' => 1]
    );
  }

  /**
   * Get user associated JWT.
   */
  private function getJwt($privateKey, $iss, $userId, $exp, $scope = NULL) {
    require_once 'vendor/autoload.php';
    $payload = [
      'iss' => $iss,
      'http://indicia.org.uk/user:id' => $userId,
      'exp' => $exp,
    ];
    if ($scope) {
      $payload['scope'] = $scope;
    }
    return \Firebase\JWT\JWT::encode($payload, $privateKey, 'RS256');
  }

  /**
   * Get anonymous JWT.
   */
  private function getAnonJwt($privateKey, $iss, $exp) {
    require_once 'vendor/autoload.php';
    $payload = [
      'iss' => $iss,
      'exp' => $exp,
    ];
    return \Firebase\JWT\JWT::encode($payload, $privateKey, 'RS256');
  }

  public function testJwt() {
    $this->authMethod = 'jwtUser';
    $cache = Cache::instance();
    $cacheKey = 'website-by-url-' . preg_replace('/[^0-9a-zA-Z]/', '', 'http://www.indicia.org.uk');
    // Make sure there is no public key stored.
    $db = new Database();
    $db->update(
      'websites',
      ['public_key' => NULL],
      ['id' => 1]
    );
    $cache->delete($cacheKey);
    // Make an otherwise valid call - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 401);
    // Make sure there is invalid public key stored.
    $db->update(
      'websites',
      ['public_key' => 'INVALID!!!'],
      ['id' => 1]
    );
    $cache->delete($cacheKey);
    // Make an otherwise valid call - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 500);
    // Store the public key so Indicia can check signed requests.
    $db->update(
      'websites',
      ['public_key' => self::$publicKey],
      ['id' => 1]
    );
    $cache->delete($cacheKey);
    // Make a valid call - should be authorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertEquals(200, $response['httpCode']);
    // Make a bogus call - should be unauthorised.
    self::$jwt = base64_encode('abcdefg1234.123456789.zyx');
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertEquals(401, $response['httpCode']);
    // Make a valid call with wrong iss - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.ukx', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertEquals(401, $response['httpCode']);
    // Make an expired call - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() - 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertEquals(401, $response['httpCode']);
    // Make a valid call with wrong user - should be forbidden.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 2, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertEquals(403, $response['httpCode']);
    // Make an call with wrong key.
    self::$jwt = $this->getJwt(self::$wrongPrivateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertEquals(401, $response['httpCode']);
  }

  /**
   * Check use of anonymous website based JWT tokens.
   */
  public function testAnonJwt() {
    $this->authMethod = 'jwtUser';
    $cache = Cache::instance();
    $cacheKey = 'website-by-url-' . preg_replace('/[^0-9a-zA-Z]/', '', 'http://www.indicia.org.uk');
    // Store the public key so Indicia can check signed requests.
    $db = new Database();
    $db->update(
      'websites',
      ['public_key' => self::$publicKey, 'allow_anon_jwt_post' => 'f'],
      ['id' => 1]
    );
    $cache->delete($cacheKey);
    self::$jwt = $this->getAnonJwt(self::$privateKey, 'http://www.indicia.org.uk', time() + 120);
    // PUT samples should be rejected.
    $response = $this->callService(
      'samples/1',
      FALSE,
      [
        'values' => [
          'date_start' => NULL,
          'date_end' => NULL,
          'date_type' => 'U',
        ],
      ],
      [], 'PUT'
    );
    $this->assertTrue($response['httpCode'] === 400);
    // POST samples should be rejected (website flag to allow anon
    // submissions is off).
    $response = $this->callService(
      'samples',
      FALSE,
      [
        'values' => [
          'survey_id' => 1,
          'entered_sref' => 'SU1234',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
          'comment' => 'A sample comment test',
        ],
      ]
    );
    $this->assertTrue($response['httpCode'] === 400);
    // POST samples should be accepted (website flag to allow anon
    // submissions is on).
    $db->update(
      'websites',
      ['allow_anon_jwt_post' => 't'],
      ['id' => 1]
    );
    $response = $this->callService(
      'samples',
      FALSE,
      [
        'values' => [
          'survey_id' => 1,
          'entered_sref' => 'SU1234',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
          'comment' => 'A sample create test',
        ],
      ]
    );
    $this->assertTrue($response['httpCode'] === 201);
  }

  public function testJwtHeaderCaseInsensitive() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // First POST to create.
    $response = $this->callService(
      'samples',
      FALSE,
      [
        'values' => [
          'survey_id' => 1,
          'entered_sref' => 'SU1234',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
          'comment' => 'A sample to delete',
        ]
      ]
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    // Now GET to check values stored OK using manually set auth header in
    // lowercase.
    $this->authMethod = 'none';
    $storedObj = $this->callService("samples/$id", FALSE, NULL, ['authorization: bearer ' . self::$jwt]);
    $this->assertResponseOk($storedObj, "/samples/$id GET");
  }

  /**
   * A generic test for POST end-points.
   *
   * Includes checking validation if required field missing.
   *
   * @param string $table
   *   End-point (table) name.
   * @param array $exampleData
   *   Example values to post.
   * @param string $requiredFieldToTest
   *   A field which is mandatory that can be used to check validation.
   * @param int $userId
   *   Warehouse user ID to authenticate as. Default is 1.
   */
  private function postTest($table, array $exampleData, $requiredFieldToTest, $userId = 1) {
    $entity = inflector::singular($table);
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120);
    // First a submission with a validation failure.
    $invalidData = array_merge($exampleData);
    unset($invalidData[$requiredFieldToTest]);
    $response = $this->callService(
      $table,
      FALSE,
      ['values' => $invalidData]
    );
    $this->assertEquals(400, $response['httpCode']);
    // Check missing required field reported as validation failure.
    $this->assertArrayHasKey('message', $response['response']);
    $this->assertArrayHasKey("$entity:$requiredFieldToTest", $response['response']['message']);
    // Now post a valid record to create it.
    $response = $this->callService(
      $table,
      FALSE,
      ['values' => $exampleData]
    );
    $this->assertEquals(201, $response['httpCode']);
    $this->assertArrayHasKey('values', $response['response']);
    $this->assertArrayHasKey('id', $response['response']['values']);
    $id = $response['response']['values']['id'];
    $storedObj = $this->callService("$table/$id");
    foreach ($exampleData as $field => $value) {
      $this->assertTrue(isset($storedObj['response']['values'][$field]), "Stored info in $table does not include value for $field");
      $storedValue = $storedObj['response']['values'][$field];
      // Tolerate response as 't' or 'f' for TRUE/FALSE.
      if (($storedValue === 't' && $exampleData[$field] === TRUE) || ($storedValue === 'f' && $exampleData[$field] === FALSE)) {
        $exampleData[$field] = $storedValue;
      }
      $this->assertEquals($exampleData[$field], $storedValue, "Stored info in $table does not match value for $field");
    }
    return $id;
  }

  /**
   * A generic test for entity end-points with a PUT method.
   *
   * @param string $table
   *   End-point (table) name.
   * @param array $exampleData
   *   Example values to post.
   * @param array $updateData
   *   Example values to update.
   */
  private function putTest($table, array $exampleData, array $updateData) {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService(
      $table,
      FALSE,
      ['values' => $exampleData]
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    // Now PUT to update.
    $response = $this->callService(
      "$table/$id",
      FALSE,
      ['values' => $updateData],
      [], 'PUT'
    );
    $this->assertResponseOk($response, "/$table/$id PUT");
    $storedObj = $this->callService("$table/$id");
    $expectedValues = array_merge($exampleData, $updateData);
    foreach ($expectedValues as $field => $value) {
      $this->assertTrue(
        isset($storedObj['response']['values'][$field]),
        "Stored info in $table does not include value for $field"
      );
      $this->assertEquals(
        $value, $storedObj['response']['values'][$field],
        "Stored info in $table does not match value for $field"
      );
    }
    return $id;
  }

  /**
   * A generic test for entity end-points with a GET method for single items.
   *
   * @param string $table
   *   End-point (table) name.
   * @param array $exampleData
   *   Example values to POST then GET to check.
   * @param string $scope
   *   Optional JWT token scope claim.
   *
   * @return $id
   *   ID of the returned row.
   */
  private function getTest($table, $exampleData, $scope = NULL) {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120, $scope);
    // First POST to create.
    $response = $this->callService(
      $table,
      FALSE,
      ['values' => $exampleData]
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    $headers = $this->parseHeaders($response['headers']);
    $this->assertTrue(array_key_exists('ETag', $headers), "$table POST does not return ETag.");
    $insertedRecordETag = $headers['ETag'];
    // Now GET to check values stored OK.
    $storedObj = $this->callService("$table/$id");
    $this->assertResponseOk($storedObj, "/$table/$id GET");
    $headers = $this->parseHeaders($storedObj['headers']);
    $this->assertTrue(array_key_exists('ETag', $headers), "$table GET does not return ETag.");
    $this->assertEquals($insertedRecordETag, $headers['ETag'], 'GET returns ETag which does not match expected');
    foreach ($exampleData as $field => $value) {
      $this->assertTrue(
        isset($storedObj['response']['values'][$field]),
        "Stored info in $table does not include value for $field"
      );
      $this->assertEquals(
        $exampleData[$field], $storedObj['response']['values'][$field],
        "Stored info in $table does not match value for $field"
      );
    }
    return $id;
  }

  /**
   * A generic test for entity end-points with a GET method for a list.
   *
   * @param string $table
   *   End-point (table) name.
   * @param array $exampleData
   *   Example values to POST then GET to check.
   */
  private function getListTest($table, $exampleData) {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // First POST to create.
    $response = $this->callService(
      $table,
      FALSE,
      ['values' => $exampleData]
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    // Now GET to check values stored OK.
    $storedList = $this->callService("$table");
    $this->assertResponseOk($storedList, "/$table GET");
    // Search for the one we posted.
    $found = FALSE;
    foreach ($storedList['response'] as $storedItem) {
      // Typecast as ID returned from warehouse may be a string datatype.
      $allMatch = (integer) $storedItem['values']['id'] === (integer) $id;
      foreach ($exampleData as $field => $value) {
        $allMatch = $allMatch && ((string) $value === (string) $storedItem['values'][$field]);
      }
      if ($allMatch) {
        $found = TRUE;
        // From foreach.
        break;
      }
    }
    $this->assertTrue($found, "POSTed $table not found in retrieved list using GET.");
    // Repeat with a filter.
    $filterField = array_keys($exampleData)[1];
    $storedList = $this->callService($table, [$filterField => $exampleData[$filterField]]);
    $this->assertResponseOk($storedList, "/$table GET");
    // Search for the one we posted.
    $found = FALSE;
    foreach ($storedList['response'] as $storedItem) {
      // Typecast as ID returned from warehouse may be a string datatype.
      $allMatch = (integer) $storedItem['values']['id'] === (integer) $id;
      foreach ($exampleData as $field => $value) {
        $allMatch = $allMatch && ((string) $value === (string) $storedItem['values'][$field]);
      }
      if ($allMatch) {
        $found = TRUE;
        // From foreach.
        break;
      }
    }
    $this->assertTrue($found, "POSTed $table not found in filtered retrieved list using GET.");
    // Repeat with a filter that should exclude the record.
    $surveys = $this->callService($table, [$filterField => microtime(TRUE)]);
    $this->assertResponseOk($surveys, "/$table GET");
    // Search for the one we posted.
    $found = FALSE;
    foreach ($surveys['response'] as $survey) {
      $allMatch = $survey['values']['id'] === $id;
      foreach ($exampleData as $field => $value) {
        $allMatch = $allMatch && ($value === $survey['values'][$field]);
      }
      if ($allMatch) {
        $found = TRUE;
        // From foreach.
        break;
      }
    }
    $this->assertFalse(
      $found,
      'POSTed survey found in filtered retrieved list using GET which it should be excluded from.'
    );
  }

  /**
   * A generic test for DELETE from an entity.
   *
   * @param string $table
   *   End-point (table) name.
   * @param array $exampleData
   *   Example values to POST then DELETE to check.
   */
  private function deleteTest($table, $exampleData) {
    // First post a record.
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService(
      $table,
      FALSE,
      ['values' => $exampleData]
    );
    $this->assertEquals(201, $response['httpCode'], "Failed to create record before $table deletion");
    $id = $response['response']['values']['id'];
    // Check it exists.
    $response = $this->callService("$table/$id");
    $this->assertResponseOk($response, "/$table/$id GET");
    // Delete it.
    $response = $this->callService("$table/$id", FALSE, NULL, [], 'DELETE');
    $this->assertEquals(204, $response['httpCode']);
    // Check it doesn't exist.
    $response = $this->callService("$table/$id");
    $this->assertEquals(404, $response['httpCode']);
    // Delete it again - it should be not found.
    $response = $this->callService("$table/$id", FALSE, NULL, [], 'DELETE');
    $this->assertEquals(404, $response['httpCode']);
    // Delete an incorrect ID.
    $response = $this->callService("$table/999999", FALSE, NULL, [], 'DELETE');
    $this->assertEquals(404, $response['httpCode']);
  }

  /**
   * A generic test for the OPTIONS method for an entity.
   */
  private function optionsTest($table) {
    $this->authMethod = 'none';
    $response = $this->callService($table, FALSE, NULL, [], 'OPTIONS');
    $this->assertResponseOk($response, "/$table OPTIONS");
    $headers = $this->parseHeaders($response['headers']);
    $this->assertTrue(array_key_exists('Allow', $headers),
      'OPTIONS request does not return Allow in header.');
    $this->assertTrue(count(array_diff(
      ['GET', 'PUT', 'POST', 'OPTIONS', 'DELETE'],
      explode(', ', $headers['Allow']))) === 0,
      'OPTIONS request returns incorrect methods');
  }

  /**
   * A generic test for updating an entity with ETags checks.
   */
  private function eTagsTest($table, $exampleData) {
    // First post a record.
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService(
      $table,
      FALSE,
      ['values' => $exampleData]
    );
    $this->assertEquals(201, $response['httpCode']);
    $headers = $this->parseHeaders($response['headers']);
    $this->assertTrue(array_key_exists('ETag', $headers), "$table POST does not return ETag.");
    $initialETag = $headers['ETag'];
    // Now update it with incorrect ETag.
    $id = $response['response']['values']['id'];
    $data = ['comment' => 'Update fails'];
    $response = $this->callService(
      "$table/$id",
      FALSE,
      ['values' => $exampleData],
      ["If-Match: xx$initialETag"],
      'PUT'
    );
    $this->assertEquals(412, $response['httpCode'],
      'Update with incorrect precondition does not return precondition failed.');
    // Try with correct If-Match.
    $data = ['comment' => 'Update works'];
    $response = $this->callService(
      "$table/$id",
      FALSE,
      ['values' => $data],
      ["If-Match: $initialETag"],
      'PUT'
    );
    $this->assertResponseOk($response, "/$table/$id PUT");
    $headers = $this->parseHeaders($response['headers']);
    $this->assertTrue(array_key_exists('ETag', $headers),
      "PUT to update does not return new ETag for $table.");
    $this->assertNotEquals($initialETag, $headers['ETag'],
      "ETag not changed after update for $table.");
    // Repeat request should now fail.
    $response = $this->callService(
      "$table/$id",
      FALSE,
      ['values' => $data],
      ["If-Match: $initialETag"],
      'PUT'
    );
    $this->assertEquals(412, $response['httpCode']);
  }

  /**
   * A basic test of samples POST.
   */
  public function testJwtSamplePost() {
    $this->postTest('samples', [
      'survey_id' => 1,
      'entered_sref' => 'SU1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'comment' => 'A sample comment test',
    ], 'survey_id');
  }

  /**
   * Create additional user for testing auth.
   *
   * @param Database $db
   *   Database connection object.
   */
  private function createExtraUser($db) {
    $tm = microtime(TRUE);
    $db->query("insert into people(first_name, surname, created_on, created_by_id, updated_on, updated_by_id) " .
      "values ('test', 'extrauser', now(), 1, now(), 1)");
    $db->query("insert into users (username, person_id,  created_on, created_by_id, updated_on, updated_by_id) " .
    "values ('test_extrauser$tm', (select max(id) from people), now(), 1, now(), 1)");
    return [
      'user_id' => $db->query('select max(id) from users')->current()->max,
      'person_id' => $db->query('select max(id) from people')->current()->max,
    ];
  }

  /**
   * A test of samples POST with user checks.
   */
  public function testJwtSamplePostUserAuth() {
    $db = new Database();
    // Create a different user to post with.
    $userId = $this->createExtraUser($db)['user_id'];
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120);
    // Post a sample should fail until we give website access.
    $response = $this->callService(
      'samples',
      FALSE,
      [
        'values' => [
          'survey_id' => 1,
          'entered_sref' => 'SU1234',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
          'comment' => 'A sample comment test',
        ],
      ]
    );
    $this->assertEquals(403, $response['httpCode']);
    // Grant website access.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userId, 1, 3, 1, now(), 1, now())");
    // Try again.
    $response = $this->callService(
      'samples',
      FALSE,
      [
        'values' => [
          'survey_id' => 1,
          'entered_sref' => 'SU1234',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
          'comment' => 'A sample comment test',
        ],
      ]
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    // Check created_by_id.
    $response = $this->callService("samples/$id");
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(
      $userId, $response['response']['values']['created_by_id'],
      'Created_by_id not set correctly for sample'
    );
    // Set up a normal user to test against.
    $userInfo = $this->createExtraUser($db);
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      "VALUES ($userInfo[user_id], 1, 3, 1, now(), 1, now())");
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userInfo['user_id'], time() + 120);
    // They shouldn't have access.
    $response = $this->callService("samples/$id");
    $this->assertEquals(403, $response['httpCode']);
  }

  /**
   * More comprehensive tests of samples POST.
   */
  public function testJwtSamplePostMoreTests() {
    $isoDateRegex = '/\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d([+-][0-2]\d:[0-5]\d|Z)/';
    $db = new Database();
    $userInfo = $this->createExtraUser($db);
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      "VALUES ($userInfo[user_id], 1, 3, 1, now(), 1, now())");
    $this->authMethod = 'jwtUser';
    // Using user 2 so not admin.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userInfo['user_id'], time() + 120);
    $data = [
      'survey_id' => 1,
      'entered_sref' => 'SU1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'comment' => 'A sample comment test',
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(201, $response['httpCode']);
    $headers = $this->parseHeaders($response['headers']);
    $this->assertTrue(array_key_exists('Location', $headers),
      'POST samples does not return Location in header.');
    $this->assertTrue(array_key_exists('Access-Control-Allow-Origin', $headers),
      'POST samples does not return Access-Control-Allow-Origin in header.');
    $this->assertTrue(array_key_exists('Access-Control-Allow-Methods', $headers),
      'POST samples does not return Access-Control-Allow-Methods in header.');
    $this->assertTrue(array_key_exists('Access-Control-Allow-Headers', $headers),
      'POST samples does not return Access-Control-Allow-Headers in header.');
    $this->assertEquals('*', $headers['Access-Control-Allow-Origin'],
      'CORS not enabled correctly - incorrect Access-Control-Allow-Origin');
    $this->assertTrue(count(array_diff(
      ['GET', 'PUT', 'POST', 'OPTIONS', 'DELETE'],
      explode(', ', $headers['Access-Control-Allow-Methods']))) === 0,
      'CORS not enabled correctly - incorrect Access-Control-Allow-Methods');
    $this->assertTrue(count(array_diff(
      ['Content-Type', 'Authorization'],
      explode(', ', $headers['Access-Control-Allow-Headers']))) === 0,
      'CORS not enabled correctly - incorrect Access-Control-Allow-Headers');
    $this->assertTrue(array_key_exists('values', $response['response']),
      'POST samples response does not contain values.');
    $this->assertTrue(array_key_exists('id', $response['response']['values']),
      'POST samples response does not contain id in values.');
    $this->assertTrue(array_key_exists('created_on', $response['response']['values']),
      'POST samples response does not contain created_on in values.');
    $this->assertTrue(preg_match($isoDateRegex, $response['response']['values']['created_on']) === 1);
    $id = $response['response']['values']['id'];
    $this->assertTrue(array_key_exists('href', $response['response']),
      'POST samples response does not contain href.');
    $this->assertEquals($response['response']['href'], $headers['Location'],
      'POST samples response href does not match header Location.');
    $this->assertEquals(url::base() . "index.php/services/rest/samples/$id", $response['response']['href'],
      'POST samples response href incorrect');
    // Can't overwrite by re-posting.
    $data['id'] = $response['response']['values']['id'];
    $data['comment'] = 'Updated comment';
    $response = $this->callService(
      'samples',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(400, $response['httpCode']);
    // GET the posted data.
    $response = $this->callService("samples/$id");
    $this->assertResponseOk($response, "/samples GET");
    $this->assertTrue(array_key_exists('comment', $response['response']['values']),
      'GET samples response does not contain comment in values.');
    $this->assertEquals('A sample comment test', $response['response']['values']['comment']);
    $this->assertTrue(array_key_exists('created_on', $response['response']['values']),
      'GET samples response does not contain created_on in values.');
    $this->assertEquals('POLYGON', substr($response['response']['values']['geom'], 0, 7), 'Geometry not returned as WKT');
    $this->assertTrue(preg_match($isoDateRegex, $response['response']['values']['created_on']) === 1);
    $this->assertTrue(array_key_exists('date', $response['response']['values']),
      'GET samples response does not contain processed vague date output.');
    $this->assertEquals('01/08/2020', $response['response']['values']['date']);
    $this->assertTrue(array_key_exists('lat', $response['response']['values']),
      'GET samples response does not contain processed lat output.');
      $this->assertTrue(array_key_exists('lon', $response['response']['values']),
      'GET samples response does not contain processed lon output.');
    // PUT a bad update with ID mismatch.
    $data = [
      'id' => $id + 1,
      'entered_sref' => 'SU121341',
    ];
    $response = $this->callService(
      "samples/$id",
      FALSE,
      ['values' => $data],
      [],
      'PUT'
    );
    $this->assertEquals(400, $response['httpCode']);
    // PUT an update.
    $data = [
      'entered_sref' => 'SU121341',
    ];
    $response = $this->callService(
      "samples/$id",
      FALSE,
      ['values' => $data],
      [],
      'PUT'
    );
    $this->assertResponseOk($response, "/samples/$id PUT");
    // Check update worked.
    $response = $this->callService("samples/$id");
    $this->assertResponseOk($response, "/samples/$id GET");
    $this->assertEquals('SU121341', $response['response']['values']['entered_sref']);
    // Existing values not removed.
    $this->assertEquals('A sample comment test', $response['response']['values']['comment']);
    // Update sample's user ID and try to fetch - ensure forbidden.
    $db = new Database();
    $db->query('update samples set created_by_id=1 where id=?', [$response['response']['values']['id']]);
    $response = $this->callService('samples/' . $response['response']['values']['id']);
    $this->assertEquals(403, $response['httpCode']);
    // PUT update should also fail.
    $data = [
      'entered_sref' => 'SU121342',
    ];
    $response = $this->callService(
      "samples/$id",
      FALSE,
      ['values' => $data],
      [],
      'PUT'
    );
    $this->assertEquals(403, $response['httpCode']);
    // Do a test for missing sample.
    $response = $this->callService('samples/99999');
    $this->assertEquals(404, $response['httpCode']);
  }

  public function testJwtSamplePostWithOccurrence() {
    $this->authMethod = 'jwtUser';
    $db = new Database();
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      'occurrences' => [
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    $occCount = $db->query("select count(*) from occurrences where sample_id=?", [$id])
      ->current()->count;
    $this->assertEquals(1, $occCount, 'No occurrence created when submitted with a sample.');
  }

  public function testJwtSamplePostWithZeroOccurrence() {
    $this->authMethod = 'jwtUser';
    $db = new Database();
    Cache::instance()->delete('survey-auto-zero-abundance-1');
    $query = <<<SQL
      INSERT INTO occurrence_attributes (caption, data_type, created_on, created_by_id, updated_on, updated_by_id, system_function)
      VALUES ('abundance', 'T', now(), 1, now(), 1, 'sex_stage_count');
      INSERT INTO occurrence_attributes_websites (occurrence_attribute_id, website_id, created_on, created_by_id, restrict_to_survey_id, auto_handle_zero_abundance)
      VALUES ((SELECT max(id) FROM occurrence_attributes), 1, now(), 1, 1, true);
      SELECT max(id) as attr_id FROM occurrence_attributes;
    SQL;
    $attrId = $db->query($query)->current()->attr_id;
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      'occurrences' => [
        // Add 3 that are absent in varying ways.
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
            "occAttr:$attrId" => 'absent',
          ],
        ],
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
            "occAttr:$attrId" => 0,
          ],
        ],
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
            "occAttr:$attrId" => 'none',
          ],
        ],
        // Add one that's present.
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
            "occAttr:$attrId" => 4,
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    $occCount = $db->query("select count(*) from occurrences where sample_id=?", [$id])
      ->current()->count;
    $this->assertEquals(4, $occCount, 'Incorrect number of occurrences created when submitted with a sample.');
    $occCount = $db->query("select count(*) from occurrences where sample_id=? and zero_abundance=true", [$id])
      ->current()->count;
    $this->assertEquals(3, $occCount, 'Incorrect number of zero abundance occurrences created when submitted with a sample.');
    // Cleanup so the attribute isn't linked to the survey any more.
    $db->query('DELETE FROM occurrence_attributes_websites WHERE id=(SELECT max(id) FROM occurrence_attributes_websites)');
  }

  public function testJwtSamplePostUserDeletionCheck() {
    $db = new Database();

    // Set up a user to test against.
    $userInfo = $this->createExtraUser($db);
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      "VALUES ($userInfo[user_id], 1, 3, 1, now(), 1, now())");

    $this->authMethod = 'jwtUser';
    $db = new Database();
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userInfo['user_id'], time() + 120);
    // Check we can post a record with our created user.
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      'occurrences' => [
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    $sampleId = (int) $response['response']['values']['id'];
    $occCount = $db->query("select count(*) from occurrences where sample_id=?", [$sampleId])
      ->current()->count;
    $this->assertEquals(1, $occCount, 'No occurrence created when submitted with a sample.');

    // Call the delete user service.
    $response = user_identifier::delete_user($userInfo['user_id'], 1);

    // Test that the user account can no longer post data.
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    // Should be forbidden.
    $this->assertEquals(403, $response['httpCode']);

    // Process the queue so anonymisation is done.
    $db->query("UPDATE work_queue SET priority=1 WHERE task='task_indicia_svc_security_delete_user_account'");
    $queue = new WorkQueue();
    $queue->process($db, TRUE);

    // Get the anonymous user ID.
    $anonUserId = $db->query("SELECT id FROM users WHERE username='anonymous'")->current()->id;

    // No occurrences should remain linked to this user.
    $occs = $db->query("SELECT count(*) as occ_count FROM occurrences WHERE created_by_id=$userInfo[user_id] OR updated_by_id=$userInfo[user_id]")->current();
    $this->assertEquals(0, $occs->occ_count, 'Anonymised user ID still points to some occurrence data.');

    // Test occurrence now points to anonymous user.
    $occs = $db->query("SELECT created_by_id, updated_by_id FROM occurrences WHERE sample_id=?", [$sampleId]);
    $this->assertEquals(1, $occs->count());
    foreach ($occs as $occ) {
      $this->assertEquals($anonUserId, $occ->created_by_id, 'Anonymised occurrence created_by_id is incorrect');
      $this->assertEquals($anonUserId, $occ->updated_by_id, 'Anonymised occurrence updated_by_id is incorrect');
    }

    // Test existing sample now has a recorder name and points to anonymous
    // user.
    $sample = $db->query("SELECT recorder_names, created_by_id, updated_by_id FROM samples WHERE id=?", [$sampleId])->current();
    $this->assertEquals('extrauser, test', $sample->recorder_names);
    $this->assertEquals($anonUserId, $sample->created_by_id, 'Anonymised sample created_by_id is incorrect');
    $this->assertEquals($anonUserId, $sample->updated_by_id, 'Anonymised sample updated_by_id is incorrect');

    // Test person email address is anonymised.
    $person = $db->query("SELECT email_address FROM people WHERE id=?", [$userInfo['person_id']])->current();
    $this->assertEquals(1, preg_match('/@anonymous\.anonymous$/', $person->email_address), 'Person email address not anonymised correctly');

  }

  public function testJwtSamplePostList() {
    $this->authMethod = 'jwtUser';
    $db = new Database();
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      [
        'values' => [
          'survey_id' => 1,
          'entered_sref' => 'SU1234',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
        ],
        'occurrences' => [
          [
            'values' => [
              'taxa_taxon_list_id' => 1,
            ],
          ],
        ],
      ], [
        'values' => [
          'survey_id' => 1,
          'entered_sref' => 'SU2345',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
        ],
        'occurrences' => [
          [
            'values' => [
              'taxa_taxon_list_id' => 2,
            ],
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(
      400, $response['httpCode'],
      'POSTing a list to normal endpoint should fail'
    );
    $response = $this->callService(
      'samples/list',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    foreach ($response['response'] as $idx => $item) {
      $this->assertTrue(
        is_numeric($idx),
        'Response from list post should be a simple list array'
      );
      $id = $item['values']['id'];
      $occCount = $db->query("select count(*) from occurrences where sample_id=?", [$id])
        ->current()->count;
      $this->assertEquals(
        1, $occCount,
        'No occurrence created when submitted with a sample in a list.'
      );
    }
  }

  /**
   * Test behaviour around duplicate check with external key.
   */
  public function testJwtSamplePostExtKey() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'survey_id' => 1,
      'entered_sref' => 'ST1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'external_key' => 123,
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      ['values' => $data]
    );
    $id = $response['response']['values']['id'];
    $response = $this->callService(
      'samples',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(
      409, $response['httpCode'],
      'Duplicate external key did not return 409 Conflict response.'
    );
    $this->assertArrayHasKey('duplicate_of', $response['response']);
    $this->assertArrayHasKey('id', $response['response']['duplicate_of']);
    $this->assertArrayHasKey('href', $response['response']['duplicate_of']);
    $this->assertEquals($id, $response['response']['duplicate_of']['id']);
    $db = new Database();
    $smpCount = $db->query("select count(*) from samples where external_key='123'")
      ->current()->count;
    $this->assertEquals(1, $smpCount, 'Inserting duplicate sample external key succeeded when it should have failed.');
    // PUT with same external key should be OK.
    $response = $this->callService(
      "samples/$id",
      FALSE,
      ['values' => ['comment' => 'Updated', 'external_key' => 123]],
      [],
      'PUT'
    );
    $this->assertResponseOk($response, "/samples/$id PUT");
    // Create a sample we can clash extKey against.
    $data = [
      'survey_id' => 1,
      'entered_sref' => 'ST1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'external_key' => 124,
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      ['values' => $data]
    );
    // PUT with clashing external key should fail.
    $response = $this->callService(
      "samples/$id",
      FALSE,
      ['values' => ['comment' => 'Updated', 'external_key' => 124]],
      [],
      'PUT'
    );
    $this->assertEquals(409, $response['httpCode']);
  }

  /**
   * Test posting a parent/child sample where the child inherits date etc.
   */
  public function testJwtSamplePostParentWithPartialChild() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref_system' => 4326,
        'entered_sref' => '51.2, 1.1',
        'date' => '01/08/2020',
      ],
      'samples' => [
        [
          'values' => [
            'entered_sref' => '51.1, 1.11',
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data,
    );
    $this->assertEquals(201, $response['httpCode'], 'Submitting a partial sample child failed.');
    $parent = ORM::factory('sample', $response['response']['values']['id']);
    $this->assertEquals(1, count($parent->children), 'Submitting a parent sample with partial child sample did not result in 1 child being saved.');
    $child = $parent->children[0];
    $this->assertEquals(1, $child->survey_id, "Partial child sample did not inherit parent's survey_id.");
    $this->assertEquals('2020-08-01', $child->date_start, "Partial child sample did not inherit parent's date_start.");
    $this->assertEquals('2020-08-01', $child->date_end, "Partial child sample did not inherit parent's date_end.");
    $this->assertEquals('D', $child->date_type, "Partial child sample did not inherit parent's date_type.");
  }

  /**
   * Test submission of a single attribute value.
   */
  public function testJwtSamplePostAttr() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'survey_id' => 1,
      'entered_sref' => 'ST1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'smpAttr:1' => 100
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(201, $response['httpCode']);
    $db = new Database();
    $id = (int) $response['response']['values']['id'];
    $storedAltitude = $db
      ->query("select int_value from sample_attribute_values where sample_id=?", [$id])
      ->current()->int_value;
    $this->assertEquals(100, $storedAltitude);
    // Update via PUT should overwrite attribute, not create new, as single value.
    $response = $this->callService(
      "samples/$id",
      FALSE,
      ['values' => ['smpAttr:1' => 150]],
      [],
      'PUT'
    );
    $attrValCount = $db
      ->query("select count(*) from sample_attribute_values where sample_id=?", [$id])
      ->current()->count;
    $this->assertEquals(1, $attrValCount);
    $storedAltitude = $db
      ->query("select int_value from sample_attribute_values where sample_id=?", [$id])
      ->current()->int_value;
    $this->assertEquals(150, $storedAltitude);
    // Do a GET to check we can read the stored altitude.
    $response = $this->callService("samples/$id");
    $this->assertEquals(150, $response['response']['values']['smpAttr:1']);
    // Redo the call, this time in verbose mode for attribute details.
    $response = $this->callService("samples/$id?verbose");
    $this->assertArrayHasKey('smpAttr:1', $response['response']['values']);
    $attrVal = $response['response']['values']['smpAttr:1'];
    $this->assertArrayHasKey('attribute_id', $attrVal);
    $this->assertArrayHasKey('value_id', $attrVal);
    $this->assertArrayHasKey('value', $attrVal);
    $this->assertEquals(150, $attrVal['value']);
  }

  private function doSiteRoleBasedPermissionsGetCheck($table, $id) {
    $db = new Database();
    $userId = $this->createExtraUser($db)['user_id'];
    $userIdAdmin = $this->createExtraUser($db)['user_id'];
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120);
    $response = $this->callService("$table/$id");
    // Added user has no access to website.
    $this->assertEquals(403, $response['httpCode'], "Access to $table should be forbidden if user not linked to website.");
    // Grant access, but not to other people's data.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userId, 1, 3, 1, now(), 1, now())");
    // Admin user can have admin rights to website.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userIdAdmin, 1, 1, 1, now(), 1, now())");
    $response = $this->callService("$table/$id");
    // UserId has access to website but only their own data.
    $this->assertEquals(403, $response['httpCode'], "Access to $table should be forbidden if user does not own record.");
    // Switch to admin.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userIdAdmin, time() + 120);
    $response = $this->callService("$table/$id");
    // User 2 has admin access to website.
    $this->assertEquals(200, $response['httpCode'], "Access to $table should be allowed if user is site admin.");
  }

  /**
   * A basic test of /sample_media/id GET.
   */
  public function testJwtSampleMediaGet() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $id = $this->getTest('sample_media', [
      'path' => 'xyz.jpg',
      'sample_id' => $sampleId,
      // The following won't actually be posted, but should be in the response.
      'media_type' => 'Image:Local',
    ]);
    $this->doSiteRoleBasedPermissionsGetCheck('sample_media', $id);
  }

  /**
   * A basic test of /sample_media GET.
   */
  public function testJwtSampleMediaGetList() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $this->getListTest('sample_media', [
      'path' => 'a123.jpg',
      'sample_id' => $sampleId,
    ]);
  }

  /**
   * Test /sample_media POST in isolation.
   */
  public function testJwtSampleMediaPost() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $this->postTest('sample_media', [
      'path' => 'abc.jpg',
      'sample_id' => $sampleId,
    ], 'path');
  }

  /**
   * Test /sample_media PUT in isolation.
   */
  public function testJwtSampleMediaPut() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $id = $this->putTest('sample_media', [
      'path' => 'abc.jpg',
      'sample_id' => $sampleId,
    ], [
      'path' => 'cde.jpg',
    ]);
    $this->doSiteRoleBasedPermissionsPutCheck('sample_media', $id, ['path' => 'test.jpg']);
  }

  /**
   * Test DELETE for an sample_media.
   */
  public function testJwtSampleMediaDelete() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $exampleSampleMedia = [
      'path' => 'b123.jpg',
      'sample_id' => $sampleId,
    ];
    $this->deleteTest('sample_media', $exampleSampleMedia);
    $this->doSiteRoleBasedPermissionsDeleteCheck('sample_media', $exampleSampleMedia);
  }

  /**
   * Testing fetching OPTIONS for sample_media end-point.
   */
  public function testJwtSampleMediaOptions() {
    $this->optionsTest('sample_media');
  }

  /**
   * Test attempt to upload JS script into media queue.
   */
  public function testJwtMediaQueueInvalid() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Try uploading a script.
    $rootFolder = dirname(dirname(dirname(dirname(__FILE__))));
    $file = "$rootFolder/media/js/addRowToGrid.js";
    $response = $this->callService(
      "media-queue",
      FALSE,
      [
        'file' => curl_file_create(
          $file,
          'application/javascript',
          basename($file)
        ),
      ],
      [], NULL, TRUE
    );
    $this->assertEquals(400, $response['httpCode']);
    $this->assertArrayHasKey('message', $response['response']);
    $this->assertArrayHasKey('file', json_decode($response['response']['message'], TRUE));
  }

  /**
   * Testing upload of media into queue then subsequent attach to sample.
   */
  public function testJwtSamplePostWithMedia() {
    $this->authMethod = 'jwtUser';
    $db = new Database();
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Post into the media queue.
    $rootFolder = dirname(dirname(dirname(dirname(__FILE__))));
    $fileA = "$rootFolder/media/images/warehouse-banner.jpg";
    $fileB = "$rootFolder/media/images/report_piechart.png";
    // Submit 2 files with deliberate mix of by field array and single field value.
    $response = $this->callService(
      "media-queue",
      FALSE,
      [
        'file[]' => curl_file_create(
          $fileA,
          'image/jpg',
          basename($fileA)
        ),
        'singlefile' => curl_file_create(
          $fileB,
          'image/png',
          basename($fileB)
        ),
      ],
      [], NULL, TRUE
    );
    $this->assertArrayHasKey('file[0]', $response['response']);
    $this->assertArrayHasKey('singlefile', $response['response']);
    $this->assertArrayHasKey('name', $response['response']['file[0]']);
    $this->assertArrayHasKey('tempPath', $response['response']['file[0]']);
    $uploadedFileName = $response['response']['file[0]']['name'];
    // Post a sample which refers to one of the files.
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      'media' => [
        [
          'values' => [
            'queued' => $uploadedFileName,
            'caption' => 'Sample image',
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    $smpMediaCount = $db->query("select count(*) from sample_media where sample_id=? and path=?", [$id, $uploadedFileName])
      ->current()->count;
    $this->assertEquals(
      1, $smpMediaCount,
      'No media created when submitted with a sample.'
    );
    $this->assertFileExists(
      DOCROOT . 'upload/' . $uploadedFileName,
      'Uploaded media file does not exist in destination'
    );
    $this->assertFileExists(
      DOCROOT . 'upload/thumb-' . $uploadedFileName,
      'Uploaded media thumbnail does not exist in destination'
    );
    // Post a sample which refers to an incorrect file.
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      'media' => [
        [
          'values' => [
            'queued' => '123.jpg',
            'caption' => 'Sample image',
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(400, $response['httpCode']);
    // Check validation response tells me queued file missing.
    $this->assertArrayHasKey('message', $response['response']);
    $this->assertArrayHasKey('sample_medium:queued', $response['response']['message']);
  }

  /**
   * Test posting a nested sample/occurrence/media submission.
   */
  public function testJwtSampleOccurrenceMediaPost() {
    $this->authMethod = 'jwtUser';
    $db = new Database();
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Post into the media queue.
    $rootFolder = dirname(dirname(dirname(dirname(__FILE__))));
    $file = "$rootFolder/media/images/warehouse-banner.jpg";
    // Submit 3 files with deliberate mix of by field array and single field value.
    $response = $this->callService(
      "media-queue",
      FALSE,
      [
        'file[]' => curl_file_create(
          $file,
          'image/jpg',
          basename($file)
        ),
      ],
      [], NULL, TRUE
    );
    $uploadedFileName = $response['response']['file[0]']['name'];
    // Post a sample and occurrence which refers to the queued file.
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      'occurrences' => [
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
          ],
          'media' => [
            [
              'values' => [
                'queued' => $uploadedFileName,
                'caption' => 'Occurrence image',
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = (int) $response['response']['values']['id'];
    $occurrences = $db->query("select id from occurrences where sample_id=?", [$id]);
    $this->assertEquals(
      1, count($occurrences),
      'Posting a sample with occurrence did not create the occurrence'
    );
    $occurrenceId = $occurrences->current()->id;
    $occurrences = $db->query("select id from occurrence_media where occurrence_id=$occurrenceId");
    $this->assertEquals(
      1, count($occurrences),
      'Posting a sample with occurrence and media did not create the media'
    );
    // Check occurrence exists.
    $response = $this->callService("occurrences/$occurrenceId");
    $this->assertResponseOk($response, "/occurrences/$occurrenceId GET");
  }

  public function testJwtSampleOccurrenceClassifiedMediaPost() {
    $this->authMethod = 'jwtUser';
    $db = new Database();
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Post into the media queue.
    $rootFolder = dirname(dirname(dirname(dirname(__FILE__))));
    $file = "$rootFolder/media/images/warehouse-banner.jpg";
    // Submit 3 files with deliberate mix of by field array and single field
    // value.
    $response = $this->callService(
      "media-queue",
      FALSE,
      [
        'file[]' => curl_file_create(
          $file,
          'image/jpg',
          basename($file)
        ),
      ],
      [], NULL, TRUE
    );
    $uploadedFileName = $response['response']['file[0]']['name'];
    $readAuth = data_entry_helper::get_read_auth(self::$websiteId, self::$websitePassword);
    $classifierTerms = data_entry_helper::get_population_data([
      'table' => 'termlists_term',
      'extraParams' => $readAuth + [
        'termlist_external_key' => 'indicia:classifiers',
        'term' => 'Unknown',
      ],
    ]);
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      'occurrences' => [
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
            'machine_involvement' => 3,
          ],
          'media' => [
            [
              'values' => [
                'queued' => $uploadedFileName,
                'caption' => 'Occurrence image',
              ],
            ],
          ],
          'classification_event' => [
            'values' => [
              'created_by_id' => self::$userId,
            ],
            'classification_results' => [
              [
                'values' => [
                  'classifier_id' => $classifierTerms[0]['id'],
                  'classifier_version' => '1.0',
                ],
                'classification_suggestions' => [
                  [
                    'values' => [
                      'taxon_name_given' => 'A suggested name',
                      'taxa_taxon_list_id' => 1,
                      'probability' => 0.9,
                    ],
                    'values' => [
                      'taxon_name_given' => 'An alternative name',
                      'taxa_taxon_list_id' => 2,
                      'probability' => 0.4,
                    ],
                  ],
                ],
                'metaFields' => [
                  'mediaPaths' => [$uploadedFileName],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    $sampleId = (int) $response['response']['values']['id'];
    $sql = <<<SQL
select s.id as sample_id,
  o.id as occurrence_id,
  o.machine_involvement,
  om.id as occurrence_medium_id,
  ce.id as classification_event_id,
  cr.id as classification_result_id,
  cs.id as classification_suggestion_id,
  crom.id as classification_results_occurrence_medium_id,
  crom.occurrence_media_id as crom_om_id
from samples s
left join occurrences o on o.sample_id=s.id and o.deleted=false
left join occurrence_media om on om.occurrence_id=o.id and om.deleted=false
left join classification_events ce on ce.id=o.classification_event_id and ce.deleted=false
left join classification_results cr on cr.classification_event_id=ce.id and cr.deleted=false
left join classification_suggestions cs on cs.classification_result_id=cr.id and cs.deleted=false
left join classification_results_occurrence_media crom on crom.classification_result_id=cr.id
where s.id=?;
SQL;
    $checkData = $db->query($sql, [$sampleId])->current();
    $this->assertTrue(!empty($checkData->occurrence_id), 'REST Classification submission occurrence not created.');
    $this->assertEquals(3, $checkData->machine_involvement, 'REST Classification submission machine_involvement saved incorrectly.');
    $this->assertTrue(!empty($checkData->occurrence_medium_id), 'REST Classification submission occurrence_medium not created.');
    $this->assertTrue(!empty($checkData->classification_event_id), 'REST Classification submission classification_event not created.');
    $this->assertTrue(!empty($checkData->classification_result_id), 'REST Classification submission classification_result not created.');
    $this->assertTrue(!empty($checkData->classification_suggestion_id), 'REST Classification submission classification_suggestion not created.');
    $this->assertTrue(!empty($checkData->classification_results_occurrence_medium_id), 'REST Classification submission classification_results_occurrence_medium not created.');
    $this->assertTrue(!empty($checkData->crom_om_id), 'REST Classification submission classification_results_occurrence_medium not linked to media file.');
    $this->assertEquals($checkData->occurrence_medium_id, $checkData->crom_om_id, 'REST Classification submission mediaPaths linking incorrect.');
  }

  /**
   * A basic test of /samples/id GET.
   */
  public function testJwtSampleGet() {
    $id = $this->getTest('samples',  [
      'survey_id' => 1,
      'entered_sref' => 'SU1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'comment' => 'A sample to delete',
    ]);
    $this->doSiteRoleBasedPermissionsGetCheck('samples', $id);
  }

  /**
   * A basic test of /samples GET.
   */
  public function testJwtSampleGetList() {
    $this->getListTest('samples', [
      'survey_id' => 1,
      'entered_sref' => 'SU2345',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'comment' => 'A sample to delete for list get test.',
    ]);
  }

  public function testJwtSamplePut() {
    $id = $this->putTest('samples', [
      'survey_id' => 1,
      'entered_sref' => 'SU1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
    ], [
      'entered_sref' => 'SU123456',
    ]);
    $this->doSiteRoleBasedPermissionsPutCheck('samples', $id, ['comment' => 'updated comment']);
  }

  /**
   * Testing delete of a sample.
   */
  public function testJwtSampleDelete() {
    $exampleSample = [
      'survey_id' => 1,
      'entered_sref' => 'SU1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'comment' => 'A sample to delete',
    ];
    $this->deleteTest('samples', $exampleSample);
    $this->doSiteRoleBasedPermissionsDeleteCheck('samples', $exampleSample);
  }

  /**
   * Testing fetching OPTIONS of samples end-point.
   */
  public function testJwtSampleOptions() {
    $this->optionsTest('samples');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtSampleETags() {
    $this->eTagsTest('samples', [
      'survey_id' => 1,
      'entered_sref' => 'SU1234',
      'entered_sref_system' => 'OSGB',
      'date' => '01/08/2020',
      'comment' => 'A sample to delete',
    ]);
  }

  public function testJwtLocationPost() {
    $id = $this->postTest('locations', [
      'name' => 'Test location',
      'centroid_sref' => 'ST1234',
      'centroid_sref_system' => 'OSGB',
    ], 'name');
    $db = new Database();
    $locationsWebsitesCount = $db->query("select count(*) from locations_websites where location_id=?", [$id])
      ->current()->count;
    $this->assertEquals(
      1, $locationsWebsitesCount,
      'No locations_websites record created for a location POST.'
    );
  }

  /**
   * Test /locations PUT behaviour.
   */
  public function testJwtLocationPut() {
    $id = $this->putTest('locations', [
      'name' => 'Location test',
      'centroid_sref' => 'ST1234',
      'centroid_sref_system' => 'OSGB',
    ], [
      'name' => 'Location test updated',
    ]);
    $this->doSiteRoleBasedPermissionsPutCheck('locations', $id, ['comment' => 'updated comment']);
  }

  public function testJwtLocationDuplicateCheck() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Post an initial location to check duplicates against.

    // Need a sub-model locations_websites.website_id to cause duplicate to trigger.
    $response = $this->callService(
      'locations',
      FALSE,
      [
        'values' => [
          'name' => 'Test location 1',
          'centroid_sref' => 'ST1234',
          'centroid_sref_system' => 'OSGB',
          'external_key' => 'textexternalkey',
        ],
      ],
    );
    $this->assertEquals(201, $response['httpCode']);
    $response = $this->callService(
      'locations',
      FALSE,
      [
        'values' => [
          'name' => 'Test location 2',
          'centroid_sref' => 'SU345678',
          'centroid_sref_system' => 'OSGB',
          'external_key' => 'textexternalkey',
        ],
      ],
    );
    // Check a conflict in the response.
    $this->assertEquals(409, $response['httpCode']);
  }

  /**
   * A basic test of /locations/id GET.
   */
  public function testJwtLocationGet() {
    $id = $this->getTest('locations', [
      'name' => 'Location GET test',
      'centroid_sref' => 'ST1234',
      'centroid_sref_system' => 'OSGB',
    ]);
    $this->doSiteRoleBasedPermissionsGetCheck('samples', $id);
  }

  /**
   * A basic test of /locations GET.
   */
  public function testJwtLocationGetList() {
    $this->getListTest('locations', [
      'name' => 'Test Location ' . microtime(TRUE),
      'centroid_sref' => 'ST1234',
      'centroid_sref_system' => 'OSGB',
    ]);
  }

  /**
   * Test DELETE for a location.
   */
  public function testJwtLocationDelete() {
    $exampleLocation = [
      'name' => 'Location GET test',
      'centroid_sref' => 'ST1234',
      'centroid_sref_system' => 'OSGB',
    ];
    $this->deleteTest('locations', $exampleLocation);
    $this->doSiteRoleBasedPermissionsDeleteCheck('locations', $exampleLocation);
  }

  /**
   * Testing fetching OPTIONS for locations end-point.
   */
  public function testJwtLocationOptions() {
    $this->optionsTest('locations');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtLocationETags() {
    $this->eTagsTest('locations', [
      'name' => 'Location GET test',
      'centroid_sref' => 'ST1234',
      'centroid_sref_system' => 'OSGB',
    ]);
  }

  /**
   * Test for GET /location_media/{id}.
   */
  public function testJwtLocationMediaGet() {
    $id = $this->getTest('location_media', [
      'path' => 'xyz.jpg',
      'location_id' => 1,
      // The following won't actually be posted, but should be in the response.
      'media_type' => 'Image:Local',
    ]);
    $this->doSiteRoleBasedPermissionsGetCheck('location_media', $id);
  }

  /**
   * A basic test of GET /location_media.
   */
  public function testJwtLocationMediaGetList() {
    $this->getListTest('location_media', [
      'path' => 'a123.jpg',
      'location_id' => 1,
    ]);
  }

  /**
   * Test POST /location_media in isolation.
   */
  public function testJwtLocationMediaPost() {
    $this->postTest('location_media', [
      'path' => 'abc.jpg',
      'location_id' => 1,
    ], 'path');
  }

  /**
   * Test /location_media PUT in isolation.
   */
  public function testJwtLocationMediaPut() {
    $id = $this->putTest('location_media', [
      'path' => 'abc.jpg',
      'location_id' => 1,
    ], [
      'path' => 'cde.jpg',
    ]);
    $this->doSiteRoleBasedPermissionsPutCheck('location_media', $id, ['path' => 'test.jpg']);
  }

  /**
   * Test DELETE for an location_media.
   */
  public function testJwtLocationMediaDelete() {
    $exampleLocationMedia = [
      'path' => 'b123.jpg',
      'location_id' => 1,
    ];
    $this->deleteTest('location_media', $exampleLocationMedia);
    $this->doSiteRoleBasedPermissionsDeleteCheck('location_media', $exampleLocationMedia);
  }

  public function testJwtSurveyPost() {
    $this->postTest('surveys', [
      'title' => 'Test survey',
      'description' => 'A test',
    ], 'title');
  }

  /**
   * Test /surveys PUT behaviour.
   */
  public function testJwtSurveyPut() {
    $this->putTest('surveys', [
      'title' => 'Test survey',
      'description' => 'A test',
    ], [
      'title' => 'Survey test updated',
    ]);
  }

  /**
   * A basic test of /surveys GET.
   */
  public function testJwtSurveyGet() {
    $this->getTest('surveys', [
      'title' => 'Test survey',
      'description' => 'A test',
    ]);
  }

  /**
   * A basic test of /surveys GET.
   */
  public function testJwtSurveysGetList() {
    $this->getListTest('surveys', [
      'title' => 'Test survey ' . microtime(TRUE),
      'description' => 'A test',
    ]);
  }

  /**
   * Test DELETE for a survey.
   */
  public function testJwtSurveyDelete() {
    $this->deleteTest('surveys', [
      'title' => 'Test survey',
      'description' => 'A test',
    ]);
  }

  public function testJwtSurveyPostPermissions() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'title' => 'Test survey',
      'description' => 'A test',
    ];
    $db = new Database();
    // Should fail if we are not an admin.
    $db->query('UPDATE users SET core_role_id=null WHERE id=1');
    // Should succeed if we are a site admin.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      ' VALUES (1, 1, 3, 1, now(), 1, now())');
    $response = $this->callService(
      'surveys',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(201, $response['httpCode']);
    $response = $this->callService(
      'surveys',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(201, $response['httpCode']);
    $db->query('DELETE FROM users_websites WHERE user_id=1 AND website_id=1');
    // Should succeed if we are a core admin.
    $db->query('UPDATE users SET core_role_id=1 WHERE id=1');
    $response = $this->callService(
      'surveys',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    // PUT should succeed initially.
    $response = $this->callService(
      "surveys/$id",
      FALSE,
      ['values' => $data],
      [], 'PUT'
    );
    $this->assertEquals(200, $response['httpCode']);
    // PUT should fail if we first hack the website ID to a different website.
    $sql = <<<SQL
INSERT INTO websites (title, created_on, created_by_id, updated_on, updated_by_id, url, password)
VALUES ('additional', now(), 1, now(), 1, 'http://example.com', '1234567');
UPDATE surveys SET website_id=(select max(id) FROM websites) WHERE id=?;
SQL;
    $db->query($sql, [$id]);
    $response = $this->callService(
      "surveys/$id",
      FALSE,
      ['values' => $data],
      [], 'PUT'
    );
    $this->assertEquals(403, $response['httpCode']);
  }

  /**
   * Testing fetching OPTIONS for surveys end-point.
   */
  public function testJwtSurveyOptions() {
    $this->optionsTest('surveys');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtSurveyETags() {
    $this->eTagsTest('surveys', [
      'title' => 'Test survey',
      'description' => 'A test',
    ]);
  }

  public function testJwtSampleAttributePost() {
    $this->postTest('sample_attributes', [
      'caption' => 'Test sample attribute',
      'data_type' => 'T',
    ], 'caption');
  }

  /**
   * Test /sample_attributes PUT behaviour.
   */
  public function testJwtSampleAttributePut() {
    $this->putTest('sample_attributes', [
      'caption' => 'Test sample attribute',
      'data_type' => 'T',
    ], [
      'caption' => 'Test sample attribute updated',
    ]);
  }

  /**
   * A basic test of /sample_attributes GET.
   */
  public function testJwtSampleAttributeGet() {
    $this->getTest('sample_attributes', [
      'caption' => 'Test sample attribute',
      'data_type' => 'T',
    ]);
  }

  /**
   * A basic test of /sample_attributes GET.
   */
  public function testJwtSampleAttributeGetList() {
    $this->getListTest('sample_attributes', [
      'caption' => 'Test sample attribute ' . microtime(TRUE),
      'data_type' => 'T',
    ]);
  }

  /**
   * Test DELETE for a sample_attribute.
   */
  public function testJwtSampleAttributeDelete() {
    $this->deleteTest('sample_attributes', [
      'caption' => 'Test sample attribute',
      'data_type' => 'T',
    ]);
  }

  /**
   * Testing fetching OPTIONS for sample_attributes end-point.
   */
  public function testJwtSampleAttributeOptions() {
    $this->optionsTest('sample_attributes');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtSampleAttributeETags() {
    $this->eTagsTest('sample_attributes', [
      'caption' => 'Test sample attribute',
      'data_type' => 'T',
    ]);
  }

  private function getSampleCommentExampleData() {
    return [
      'sample_id' => 1,
      'comment' => 'A test comment for a sample.',
      'person_name' => 'Foo bar',
    ];
  }

  /**
   * Test /sample_comments PUT behaviour.
   */
  public function sample_comments() {
    $this->putTest('sample_comments', $this->getSampleCommentExampleData(), [
      'comment' => 'Test sample comment updated',
    ]);
  }

  /**
   * A basic test of /sample_comments GET.
   *
   * @todo Need to test that you can GET comments belonging to other users for your own records.
   */
  public function testJwtSampleCommentGet() {
    $this->getTest('sample_comments', $this->getSampleCommentExampleData());
  }

  /**
   * A basic test of /sample_comments GET.
   *
   * @todo Need to test that you can GET comments belonging to other users for your own records.
   */
  public function testJwtSampleCommentGetList() {
    $this->getListTest('sample_comments',  $this->getSampleCommentExampleData());
  }

  /**
   * Test /sample_comment POST in isolation.
   */
  public function testJwtSampleCommentPost() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $values = $this->getSampleCommentExampleData();
    $values['sample_id'] = $sampleId;
    $this->postTest('sample_comments', $values, 'comment');
  }

  /**
   * Test DELETE for an sample_comment.
   *
   * @todo Need to test that you can DELETE comments belonging to other users for your own records.
   */
  public function testJwtSampleCommentDelete() {
    $this->deleteTest('sample_comments', $this->getSampleCommentExampleData());
  }

  /**
   * Testing fetching OPTIONS for sample_comments end-point.
   */
  public function testJwtSampleCommentOptions() {
    $this->optionsTest('sample_comments');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtSampleCommentETags() {
    $this->eTagsTest('sample_comments', $this->getSampleCommentExampleData());
  }

  /**
   * A basic test of /notifications GET.
   */
  public function testJwtNotificationGet() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService("notifications/1");
    $this->assertEquals(200, $response['httpCode']);
    $this->assertArrayHasKey('values', $response['response']);
    $this->assertArrayHasKey('id', $response['response']['values']);
    $this->assertEquals('1', $response['response']['values']['id']);
    $response = $this->callService("notifications/2");
    $this->assertEquals(404, $response['httpCode']);
  }

  /**
   * A basic test of /notifications GET.
   */
  public function testJwtNotificationsGetList() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService("notifications");
    $this->assertEquals(200, $response['httpCode']);
    // There are 2 notifications in the fixture, but only one for this user.
    $this->assertEquals(1, count($response['response']));
  }

  /**
   * Test /notification POST in isolation.
   *
   * POST is not supported by the REST API, so this test checks for a 405
   * Method Not Allowed response.
   */
  public function testJwtNotificationPost() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Attempt to delete notification. Should return 405 Method Not Allowed.
    $response = $this->callService("notifications", FALSE, [
      'user_id' => 1,
      'source' => 'Test',
      'source_type' => 'T',
      'data' => 'test',
      'linked_id' => 1,
    ]);
    $this->assertEquals(405, $response['httpCode']);
  }

  /**
   * Test /occurrence_comments PUT behaviour.
   */
  public function testJwtNotificationPut() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $db = new Database();
    // The PUT method is used to acknowledge a notification. Response should be
    // 200 OK.
    $response = $this->callService(
      "notifications/1",
      FALSE,
      ['values' => ['acknowledged' => TRUE]],
      [], 'PUT'
    );
    $this->assertEquals(200, $response['httpCode']);
    $updatedAcknowledged = $db->query('SELECT acknowledged FROM notifications WHERE id=1')->current()->acknowledged;
    $this->assertEquals('t', $updatedAcknowledged, 'Notification acknowledged status should have changed.');
    // A GET request should not return the acknowledged entry.
    $response = $this->callService("notifications");
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(0, count($response['response']));
    // A GET request should return the acknowledged entry if requested to do so.
    $response = $this->callService("notifications?acknowledged=true");
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(1, count($response['response']));
    $response = $this->callService(
      "notifications/1",
      FALSE,
      ['values' => ['acknowledged' => FALSE]],
      [], 'PUT'
    );
    $this->assertEquals(200, $response['httpCode']);
    $updatedAcknowledged = $db->query('SELECT acknowledged FROM notifications WHERE id=1')->current()->acknowledged;
    $this->assertEquals('f', $updatedAcknowledged, 'Notification acknowledged status should have changed.');
    // Attempt to update another user's notification. Should return 404 Not
    // Found.
    $response = $this->callService(
      "notifications/2",
      FALSE,
      ['values' => ['acknowledged' => TRUE]],
      [], 'PUT'
    );
    $this->assertEquals(404, $response['httpCode']);
    // Attempt to update a field that can't be changed. Should return 400 Bad
    // Request.
    $response = $this->callService(
      "notifications/1",
      FALSE,
      ['values' => ['user_id' => 1]],
      [], 'PUT'
    );
    $this->assertEquals(400, $response['httpCode']);
    // Attempt to update a missing notification should return 404 Not Found.
    $response = $this->callService(
      "notifications/12345",
      FALSE,
      ['values' => ['acknowledged' => FALSE]],
      [], 'PUT'
    );
    $this->assertEquals(404, $response['httpCode']);
  }

  /**
   * Test /notification DELETE in isolation.
   *
   * DELETE is not supported by the REST API, so this test checks for a 405
   * Method Not Allowed response.
   */
  public function testJwtNotificationDelete() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Attempt to delete notification. Should return 405 Method Not Allowed.
    $response = $this->callService("notifications/1", FALSE, NULL, [], 'DELETE');
    $this->assertEquals(405, $response['httpCode']);
  }

  public function testJwtOccurrenceAttributePost() {
    $this->postTest('occurrence_attributes', [
      'caption' => 'Test occurrence attribute',
      'data_type' => 'T',
    ], 'caption');
  }

  /**
   * Test /occurrence_attributes PUT behaviour.
   */
  public function testJwtOccurrenceAttributePut() {
    $this->putTest('occurrence_attributes', [
      'caption' => 'Test occurrence attribute',
      'data_type' => 'T',
    ], [
      'caption' => 'Test occurrence attribute updated',
    ]);
  }

  /**
   * A basic test of /occurrence_attributes GET.
   */
  public function testJwtOccurrenceAttributeGet() {
    $this->getTest('occurrence_attributes', [
      'caption' => 'Test occurrence attribute',
      'data_type' => 'T',
    ]);
  }

  /**
   * A basic test of /occurrence_attributes GET.
   */
  public function testJwtOccurrenceAttributeGetList() {
    $this->getListTest('occurrence_attributes',  [
      'caption' => 'Test occurrence attribute ' . microtime(TRUE),
      'data_type' => 'T',
    ]);
  }

  /**
   * Test DELETE for a occurrence_attribute.
   */
  public function testJwtOccurrenceAttributeDelete() {
    $this->deleteTest('occurrence_attributes', [
      'caption' => 'Test occurrence attribute',
      'data_type' => 'T',
    ]);
  }

  /**
   * Testing fetching OPTIONS for occurrence_attributes end-point.
   */
  public function testJwtOccurrenceAttributeOptions() {
    $this->optionsTest('occurrence_attributes');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtOccurrenceAttributeETags() {
    $this->eTagsTest('occurrence_attributes', [
      'caption' => 'Test occurrence attribute',
      'data_type' => 'T',
    ]);
  }

  private function getOccurrenceCommentExampleData() {
    return [
      'occurrence_id' => 1,
      'comment' => 'A test comment.',
      'person_name' => 'Foo bar',
    ];
  }

  /**
   * A basic test of /occurrence_comments GET.
   *
   * @todo Need to test that you can GET comments belonging to other users for your own records.
   */
  public function testJwtOccurrenceCommentGet() {
    $this->getTest('occurrence_comments', $this->getOccurrenceCommentExampleData());
  }

  /**
   * A basic test of /occurrence_comments GET.
   *
   * @todo Need to test that you can GET comments belonging to other users for your own records.
   */
  public function testJwtOccurrenceCommentGetList() {
    $this->getListTest('occurrence_comments',  $this->getOccurrenceCommentExampleData());
  }

  /**
   * Test /occurrence_comment POST in isolation.
   */
  public function testJwtOccurrenceCommentPost() {
    $values = $this->getOccurrenceCommentExampleData();
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $values['occurrence_id'] = $occurrenceId;
    $this->postTest('occurrence_comments', $values, 'comment');
  }

  /**
   * A comment without a person name defaults to the person who created it.
   */
  public function testJwtOccurrenceCommentGetWithoutPerson() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $values = $this->getOccurrenceCommentExampleData();
    unset($values['person_name']);
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $values['occurrence_id'] = $occurrenceId;
    $response = $this->callService(
      'occurrence_comments',
      FALSE,
      ['values' => $values]
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    $response = $this->callService("occurrence_comments/$id");
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals('core admin', $response['response']['values']['person_name']);
  }

  /**
   * Test /occurrence_comments PUT behaviour.
   */
  public function testJwtOccurrenceCommentPut() {
    $this->putTest('occurrence_comments', $this->getOccurrenceCommentExampleData(), [
      'comment' => 'Test occurrence comment updated',
    ]);
  }


  /**
   * Test DELETE for an occurrence_comment.
   *
   * @todo Need to test that you can not DELETE comments belonging to other users for your own records.
   */
  public function testJwtOccurrenceCommentDelete() {
    $this->deleteTest('occurrence_comments', $this->getOccurrenceCommentExampleData());
  }

  /**
   * Testing fetching OPTIONS for occurrence_comments end-point.
   */
  public function testJwtOccurrenceCommentOptions() {
    $this->optionsTest('occurrence_comments');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtOccurrenceCommentETags() {
    $this->eTagsTest('occurrence_comments', $this->getOccurrenceCommentExampleData());
  }

  private function getDnaOccurrenceExampleData() {
    return [
      'occurrence_id' => 1,
      'associated_sequences' => 'https://www.ncbi.nlm.nih.gov/nuccore/U34853.1;https://www.ncbi.nlm.nih.gov/nuccore/U53564.2',
      'dna_sequence' => 'GTGGGTTTGGAGCACCGCCAAGTCCTTAGAGTTTTAAGCGTTTGTGCTCGTAGTTCTCAGGCGAATACTTTGGTGGGGAGAAGTATTTAGATTTAAGGCCAA',
      'target_gene' => 'CO1',
      'pcr_primer_reference' => 'https://doi.org/10.1186/1742-9994-10-34',
      'env_medium' => 'liquid water [ENVO:00002006]',
      'env_broad_scale' => 'terrestrial biome [ENVO:00000446]',
      'otu_db' => 'NCBI',
      'otu_seq_comp_appr' => 'blast version 2.12.0+',
      'otu_class_appr' => 'standard Linux tools',
      'env_local_scale' => 'alpine biome',
      'target_subfragment' => 'V5',
      'pcr_primer_name_forward' => 'Riaz_12S_V5F',
      'pcr_primer_forward' => 'TAGAACAGGCTCCTCTAG',
      'pcr_primer_name_reverse' => 'Riaz_12S_V5R',
      'pcr_primer_reverse' => 'pcr_primer_reverse',
    ];
  }

  /**
   * A basic test of /dna_occurrences GET.
   */
  public function testJwtDnaOccurrenceGet() {
    $this->getTest('dna_occurrences', $this->getDnaOccurrenceExampleData());
  }

  /**
   * A basic test of /dna_occurrences GET.
   *
   * @todo Need to test that you can GET comments belonging to other users for your own records.
   */
  public function testJwtDnaOccurrenceGetList() {
    $this->getListTest('dna_occurrences',  $this->getDnaOccurrenceExampleData());
  }

  /**
   * Test /dna_occurrences POST in isolation.
   */
  public function testJwtDnaOccurrencePost() {
    $values = $this->getDnaOccurrenceExampleData();
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $values['occurrence_id'] = $occurrenceId;
    $this->postTest('dna_occurrences', $values, 'dna_sequence');
  }

  public function testJwtDnaOccurrenceDuplicatePost() {
    $values = $this->getDnaOccurrenceExampleData();
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $values['occurrence_id'] = $occurrenceId;
    $this->postTest('dna_occurrences', $values, 'dna_sequence');
    $response = $this->callService(
      'dna_occurrences',
      FALSE,
      ['values' => $values]
    );
    // Check we got a conflict response.
    $this->assertEquals(409, $response['httpCode']);
  }

  /**
   * Test /dna_occurrences POST inside a sample.
   */
  public function testJwtDnaOccurrenceInSamplePost() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
      // 3 occurrences, 2 with DNA.
      'occurrences' => [
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
          ],
        ],
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
          ],
          'dna_occurrences' => [
            [
              'values' => $this->getDnaOccurrenceExampleData(),
            ],
          ],
        ],
        [
          'values' => [
            'taxa_taxon_list_id' => 2,
          ],
          'dna_occurrences' => [
            [
              'values' => $this->getDnaOccurrenceExampleData(),
            ],
          ],
        ],

      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    $id = $response['response']['values']['id'];
    $db = new Database();
    $occCount = $db->query("select count(*) from occurrences where sample_id=?", [$id])
      ->current()->count;
    $this->assertEquals(3, $occCount, 'Incorrect number of occurrences created when submitted with a sample.');
    $occCount = $db->query("select count(*) from occurrences where sample_id=? and dna_derived=true", [$id])
      ->current()->count;
    $this->assertEquals(2, $occCount, 'Incorrect number of DNA occurrences created when submitted with a sample.');
    $occCount = $db->query("select count(distinct dnao.id) from dna_occurrences dnao join occurrences o on o.id=dnao.occurrence_id where o.sample_id=?", [$id])
      ->current()->count;
    $this->assertEquals(2, $occCount, 'Incorrect number of DNA occurrences created when submitted with a sample.');
  }

  /**
   * Test /dna_occurrences PUT behaviour.
   */
  public function testJwtDnaOccurrencePut() {
    $this->putTest('dna_occurrences', $this->getDnaOccurrenceExampleData(), [
      'dna_sequence' => 'GATTTAGTTTGGAGCACCGCCAAGTCCTTAGAGTTTTAAGCGTTTGTGCTCGTAGTTCTCAGGCGAATACTTTGGTGGGGAGAAGTATTTAGATTd',
    ]);
  }


  /**
   * Test DELETE for a dna_occurrence.
   *
   * @todo Need to test that you can not DELETE comments belonging to other users for your own records.
   */
  public function testJwtDnaOccurrenceDelete() {
    $this->deleteTest('dna_occurrences', $this->getDnaOccurrenceExampleData());
  }

  /**
   * Testing fetching OPTIONS for dna_occurrence end-point.
   */
  public function testJwtDnaOccurrenceOptions() {
    $this->optionsTest('dna_occurrences');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtDnaOccurrenceETags() {
    $this->eTagsTest('dna_occurrences', $this->getDnaOccurrenceExampleData());
  }

  /**
   * A basic test of /occurrence_media/id GET.
   */
  public function testJwtOccurrenceMediaGet() {
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $id = $this->getTest('occurrence_media', [
      'path' => 'xyz.jpg',
      'occurrence_id' => $occurrenceId,
      // The following won't actually be posted, but should be in the response.
      'media_type' => 'Image:Local',
    ]);
    $this->doSiteRoleBasedPermissionsGetCheck('occurrence_media', $id);
  }

  /**
   * A basic test of /occurrence_media GET.
   */
  public function testJwtOccurrenceMediaGetList() {
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $this->getListTest('occurrence_media', [
      'path' => 'a123.jpg',
      'occurrence_id' => $occurrenceId,
    ]);
  }

  /**
   * Test /sample_media POST in isolation.
   */
  public function testJwtOccurrenceMediaPost() {
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $this->postTest('occurrence_media', [
      'path' => 'abc.jpg',
      'occurrence_id' => $occurrenceId,
    ], 'path');
  }

  /**
   * Test /sample_media PUT in isolation.
   */
  public function testJwtOccurrenceMediaPut() {
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $id = $this->putTest('occurrence_media', [
      'path' => 'abc.jpg',
      'occurrence_id' => $occurrenceId,
    ], [
      'path' => 'cde.jpg',
    ]);
    $this->doSiteRoleBasedPermissionsPutCheck('occurrence_media', $id, ['path' => 'test.jpg']);
  }

  /**
   * Test DELETE for an occurrence_media.
   */
  public function testJwtOccurrenceMediaDelete() {
    $occurrenceId = $this->postOccurrenceToAddStuffTo();
    $this->deleteTest('occurrence_media', [
      'path' => 'b123.jpg',
      'occurrence_id' => $occurrenceId,
    ]);
    $this->doSiteRoleBasedPermissionsDeleteCheck('occurrence_media', [
      'path' => 'b123.jpg',
      'occurrence_id' => $occurrenceId,
    ]);
  }

  /**
   * Testing fetching OPTIONS for occurrence_media end-point.
   */
  public function testJwtOccurrenceMediaOptions() {
    $this->optionsTest('occurrence_media');
  }

  /**
   * Create a sample we can add occurrences to.
   *
   * @return int
   *   Sample ID.
   */
  private function postSampleToAddOccurrencesTo() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // POST a sample we can add occurrences to.
    $data = [
      'values' => [
        'survey_id' => 1,
        'entered_sref' => 'SU1234',
        'entered_sref_system' => 'OSGB',
        'date' => '01/08/2020',
      ],
    ];
    $response = $this->callService(
      'samples',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    return $response['response']['values']['id'];
  }

  private function postOccurrenceToAddStuffTo() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $data = [
      'values' => [
        'taxa_taxon_list_id' => 1,
        'sample_id' => $sampleId,
      ],
    ];
    $response = $this->callService(
      'occurrences',
      FALSE,
      $data
    );
    $this->assertEquals(201, $response['httpCode']);
    return $response['response']['values']['id'];
  }

  /**
   * Test /occurrences POST in isolation.
   */
  public function testJwtOccurrencePostTtlId() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $this->postTest('occurrences', [
      'taxa_taxon_list_id' => 1,
      'sample_id' => $sampleId,
      'zero_abundance' => false,
    ], 'taxa_taxon_list_id');
  }

  /**
   * Check that site admin in users_websites works for PUT.
   *
   * @param string $table
   *   Table to test.
   * @param int $id
   *   ID of record to attempt to update.
   * @param array $values
   *   Values to attempt to PUT.
   */
  private function doSiteRoleBasedPermissionsPutCheck($table, $id, array $values) {
    // Add user to test PUT permissions.
    $db = new Database();
    $userId = $this->createExtraUser($db)['user_id'];
    $userIdAdmin = $this->createExtraUser($db)['user_id'];
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120);
    // Put to overwrite record - fails.
    $response = $this->callService(
      "$table/$id",
      FALSE,
      ['values' => $values],
      [], 'PUT'
    );
    $this->assertEquals(403, $response['httpCode'], "Access to PUT $table should be forbidden if user not linked to website.");
    // Grant access, but not to other people's data.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userId, 1, 3, 1, now(), 1, now())");
    // Put to overwrite record - fails.
    $response = $this->callService(
      "$table/$id",
      FALSE,
      ['values' => $values],
      [], 'PUT'
    );
    $this->assertEquals(403, $response['httpCode'], "Access to PUT $table should be forbidden if user does not own record.");
    // Update to site admin.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userIdAdmin, 1, 1, 1, now(), 1, now())");
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userIdAdmin, time() + 120);
    $response = $this->callService(
      "$table/$id",
      FALSE,
      ['values' => $values],
      [], 'PUT'
    );
    // User 2 has admin access to website.
    $this->assertEquals(200, $response['httpCode'], "Access to PUT $table should be allowed if user is site admin.");
  }

  /**
   * Test /occurrences PUT in isolation.
   */
  public function testJwtOccurrencePut() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $id = $this->putTest('occurrences', [
      'taxa_taxon_list_id' => 1,
      'sample_id' => $sampleId,
    ], [
      'taxa_taxon_list_id' => 2,
    ]);
    $this->doSiteRoleBasedPermissionsPutCheck('occurrences', $id, ['comment' => 'updated comment']);
  }

  /**
   * A basic test of /occurrences/id GET.
   */
  public function testJwtOccurrenceGet() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $id = $this->getTest('occurrences', [
      'taxa_taxon_list_id' => 1,
      'sample_id' => $sampleId,
    ]);
    $this->doSiteRoleBasedPermissionsGetCheck('occurrences', $id);
  }

  /**
   * A basic test of /occurrences/id GET.
   */
  public function testJwtOccurrenceGetScope() {
    // Create a sample using the default user ID.
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $db = new Database();
    // Create a different user to query with with.
    $userId = $this->createExtraUser($db)['user_id'];
    // Grant website access.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userId, 1, 3, 1, now(), 1, now())");
    // Authenticate as the added user.
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120);
    // Try to GET the sample.
    $response = $this->callService("samples/$sampleId");
    $this->assertEquals(
      403, $response['httpCode'],
      "Request for another user's sample does not return 403."
    );
    // Authenticate as the original user.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Try to GET the sample.
    $response = $this->callService("samples/$sampleId");
    $this->assertEquals(
      200, $response['httpCode'],
      "Request for a user's sample does not return 200."
    );
    // Authenticated scope should default to user's own records, so expect 403
    // if requesting for the created user.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120, 'authenticated');
    // Try to GET the sample.
    $response = $this->callService("samples/$sampleId");
    $this->assertEquals(
      403, $response['httpCode'],
      "Request for another user's sample does not return 404 with authenticated scope."
    );
    // Same for user scope.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120, 'user');
    // Try to GET the sample.
    $response = $this->callService("samples/$sampleId");
    $this->assertEquals(
      403, $response['httpCode'],
      "Request for another user's sample does not return 404 with user scope."
    );
  }

  /**
   * A basic test of /occurrences GET.
   */
  public function testJwtOccurrenceGetList() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $this->getListTest('occurrences', [
      // Sample first as it makes a better filter test.
      'sample_id' => $sampleId,
      'taxa_taxon_list_id' => 1,
    ]);
  }

  /**
   * Check that site admin in users_websites works for DELETE.
   *
   * @param string $table
   *   Table to test.
   * @param int $id
   *   ID of record to attempt to update.
   * @param array $values
   *   Values to attempt to PUT.
   */
  private function doSiteRoleBasedPermissionsDeleteCheck($table, array $values) {
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Add a record to test deletes against.
    $data = ['values' => $values];
    $response = $this->callService(
      $table,
      FALSE,
      $data
    );
    $id = $response['response']['values']['id'];
    // Add user to test PUT permissions.
    $db = new Database();
    $userId = $this->createExtraUser($db)['user_id'];
    $userIdAdmin = $this->createExtraUser($db)['user_id'];
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120);
    // Delete record - fails.
    $response = $this->callService("$table/$id", FALSE, [], [], 'DELETE');
    $this->assertEquals(403, $response['httpCode'], "Access to DELETE $table should be forbidden if user not linked to website.");
    // Grant access, but not to other people's data.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userId, 1, 3, 1, now(), 1, now())");
    // Also make an admin user with full rights to website data.
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userIdAdmin, 1, 1, 1, now(), 1, now())");
    // Delete record - fails.
    $response = $this->callService("$table/$id", FALSE, [], [], 'DELETE');
    $this->assertEquals(403, $response['httpCode'], "Access to PUT $table should be forbidden if user does not own record.");
    // Update to site admin.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userIdAdmin, time() + 120);
    $response = $this->callService("$table/$id", FALSE, [], [], 'DELETE');
    $this->assertEquals(204, $response['httpCode'], "Access to PUT $table should be allowed if user is site admin.");
  }

  /**
   * Test DELETE for an occurrence.
   */
  public function testJwtOccurrenceDelete() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $exampleOccurrence = [
      'taxa_taxon_list_id' => 1,
      'sample_id' => $sampleId,
    ];
    $this->deleteTest('occurrences', $exampleOccurrence);
    $this->doSiteRoleBasedPermissionsDeleteCheck('occurrences', $exampleOccurrence);
  }

  /**
   * Testing fetching OPTIONS for occurrences end-point.
   */
  public function testJwtOccurrenceOptions() {
    $this->optionsTest('occurrences');
  }

  /**
   * Test behaviour around REST support for ETags.
   */
  public function testJwtOccurrenceETags() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $this->eTagsTest('occurrences', [
      'taxa_taxon_list_id' => 1,
      'sample_id' => $sampleId,
    ]);
  }

  /**
   * Test behaviour around duplicate check with external key.
   */
  public function testJwtOccurrencePostExtKey() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $data = [
      'taxa_taxon_list_id' => 1,
      'sample_id' => $sampleId,
      'external_key' => 123,
    ];
    $response = $this->callService(
      'occurrences',
      FALSE,
      ['values' => $data]
    );
    $id = $response['response']['values']['id'];
    $response = $this->callService(
      'occurrences',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(
      409, $response['httpCode'],
      'Duplicate external key did not return 409 Conflict response.'
    );
  }

  /**
   * Test /occurrences POST in isolation.
   */
  public function testJwtOccurrencePostDeletedSample() {
    $sampleId = $this->postSampleToAddOccurrencesTo();
    $db = new Database();
    $db->query("update samples set deleted=true where id=?", [$sampleId]);
    $data = [
      'taxa_taxon_list_id' => 1,
      'sample_id' => $sampleId,
    ];
    $response = $this->callService(
      'occurrences',
      FALSE,
      ['values' => $data]
    );
    $this->assertEquals(
      400, $response['httpCode'],
      'Adding occurrence to deleted sample did not return 400 Bad request response.'
    );
  }

  public function testProjects_authentication() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testProjects_clientAuthentication");

    $this->authMethod = 'hmacClient';
    $this->checkResourceAuthentication('projects');
    $this->authMethod = 'directClient';
    $this->checkResourceAuthentication('projects');
    // User and website authentications don't allow access to projects.
    $this->authMethod = 'hmacUser';
    $response = $this->callService('projects');
    $this->assertTrue(
      $response['httpCode'] === 401,
      'Invalid authentication method hmacUser for projects ' .
      "but response still OK. Http response $response[httpCode]."
    );
    $this->authMethod = 'directUser';
    $response = $this->callService('projects');
    $this->assertTrue(
      $response['httpCode'] === 401,
      'Invalid authentication method directUser for projects ' .
      "but response still OK. Http response $response[httpCode]."
    );
    $this->authMethod = 'hmacWebsite';
    $response = $this->callService('projects');
    $this->assertTrue(
      $response['httpCode'] === 401,
      'Invalid authentication method hmacWebsite for projects ' .
      "but response still OK. Http response $response[httpCode]."
    );
    $this->authMethod = 'directWebsite';
    $response = $this->callService('projects');
    $this->assertTrue(
      $response['httpCode'] === 401,
      'Invalid authentication method directWebsite for projects ' .
      "but response still OK. Http response $response[httpCode]."
    );

    $this->authMethod = 'hmacClient';
  }

  public function testProjects_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testProjects_get");

    $response = $this->callService('projects');
    $this->assertResponseOk($response, '/projects');
    $viaConfig = self::$config['projects'];
    $this->assertEquals(count($viaConfig), count($response['response']['data']),
        'Incorrect number of projects returned from /projects.');
    foreach ($response['response']['data'] as $projDef) {
      $this->assertArrayHasKey(
        $projDef['id'], $viaConfig,
        "Unexpected project $projDef[id]returned by /projects."
      );
      $this->assertEquals($viaConfig[$projDef['id']]['title'], $projDef['title'],
        "Unexpected title $projDef[title] returned for project $projDef[id] by /projects.");
      $this->assertEquals($viaConfig[$projDef['id']]['description'], $projDef['description'],
        "Unexpected description $projDef[description] returned for project $projDef[id] by /projects.");
      // Some project keys are supposed to be removed.
      $this->assertNotContains(
        'filter_id', $projDef,
        'Project definition should not contain filter_id'
      );
      $this->assertNotContains(
        'sharing', $projDef,
        'Project definition should not contain sharing'
      );
    }
  }

  public function testProjects_get_id() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testProjects_get_id");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("projects/$projDef[id]");
      $this->assertResponseOk($response, "/projects/$projDef[id]");
      $this->assertEquals($projDef['title'], $response['response']['title'],
          "Unexpected title " . $response['response']['title'] .
          " returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($projDef['description'], $response['response']['description'],
          "Unexpected description " . $response['response']['description'] .
          " returned for project $projDef[id] by /projects/$projDef[id].");
      // Some project keys are supposed to be removed.
      $this->assertNotContains(
        'filter_id', $projDef,
        'Project definition should not contain filter_id'
      );
      $this->assertNotContains(
        'sharing', $projDef,
        'Project definition should not contain sharing'
      );
    }
  }

  public function testTaxon_observations_authentication() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testProjects_clientAuthentication");
    $proj_id = self::$config['projects'][array_keys(self::$config['projects'])[0]]['id'];
    $queryWithProj = ['proj_id' => $proj_id, 'edited_date_from' => '2015-01-01'];
    $query = ['edited_date_from' => '2015-01-01'];

    $this->authMethod = 'hmacClient';
    $this->checkResourceAuthentication('taxon-observations', $queryWithProj);
    $this->authMethod = 'directClient';
    $this->checkResourceAuthentication('taxon-observations', $queryWithProj);
    $this->authMethod = 'directUser';
    $this->checkResourceAuthentication('taxon-observations', $query);
    // @todo The following test needs to check filtered response rather than authentication
    $this->authMethod = 'directUser';
    $this->checkResourceAuthentication('taxon-observations', $query + ['filter_id' => self::$userFilterId]);
    $this->authMethod = 'hmacWebsite';
    $this->checkResourceAuthentication('taxon-observations', $query);
    $this->authMethod = 'directWebsite';
    $this->checkResourceAuthentication('taxon-observations', $query);

    $this->authMethod = 'hmacClient';
  }

  public function testTaxon_observations_get_incorrect_params() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testTaxon_observations_get_incorrect_params");
    $response = $this->callService("taxon-observations");
    $this->assertEquals(400, $response['httpCode'],
        'Requesting taxon observations without params should be a bad request');
    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("taxon-observations", ['proj_id' => $projDef['id']]);
      $this->assertEquals(400, $response['httpCode'],
          'Requesting taxon observations without edited_date_from should be a bad request');
      $response = $this->callService("taxon-observations", ['edited_date_from' => '2015-01-01']);
      $this->assertEquals(400, $response['httpCode'],
        'Requesting taxon observations without proj_id should be a bad request');
      // only test a single project
      break;
    }
  }

  /**
   * Test the /taxon-observations endpoint in valid use.
   *
   * @todo Test the pagination responses
   * @todo Test the /taxon-observations/id endpoint
   */
  public function testTaxon_observations_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testTaxon_observations_get");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("taxon-observations", [
          'proj_id' => $projDef['id'],
          'edited_date_from' => '2015-01-01',
          'edited_date_to' => date("Y-m-d\TH:i:s")
      ]);
      $this->assertResponseOk($response, '/taxon-observations');
      $this->assertArrayHasKey('paging', $response['response'],
          'Paging missing from response to call to taxon-observations');
      $this->assertArrayHasKey('data', $response['response'],
          'Data missing from response to call to taxon-observations');
      $data = $response['response']['data'];
      $this->assertIsArray($data, 'Taxon-observations data invalid. ' . var_export($data, true));
      $this->assertNotCount(0, $data, 'Taxon-observations data absent. ' . var_export($data, true));
      foreach ($data as $occurrence) {
        $this->checkValidTaxonObservation($occurrence);
      }
      // Only test a single project.
      break;
    }
  }

  /**
   * Test the /annotations endpoint in valid use.
   *
   * @todo Test the pagination responses
   * @todo Test the annotations/id endpoint
   */
  public function testAnnotations_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testAnnotations_get");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService(
        "annotations",
        ['proj_id' => $projDef['id'], 'edited_date_from' => '2015-01-01']
      );
      $this->assertResponseOk($response, '/annotations');
      $this->assertArrayHasKey('paging', $response['response'], 'Paging missing from response to call to annotations');
      $this->assertArrayHasKey('data', $response['response'], 'Data missing from response to call to annotations');
      $data = $response['response']['data'];
      $this->assertIsArray($data, 'Annotations data invalid. ' . var_export($data, TRUE));
      $this->assertNotCount(0, $data, 'Annotations data absent. ' . var_export($data, TRUE));
      foreach ($data as $annotation) {
        $this->checkValidAnnotation($annotation);
      }
      // Only test a single project.
      break;
    }
  }

  public function testTaxaSearch_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testTaxaSearch_get");

    $response = $this->callService('taxa/search');
    $this->assertEquals(400, $response['httpCode'],
          'Requesting taxa/search without search_term should be a bad request');
    $response = $this->callService('taxa/search', [
      'searchQuery' => 'test',
    ]);
    $this->assertEquals(400, $response['httpCode'],
          'Requesting taxa/search without taxon_list_id should be a bad request');
    $response = $this->callService('taxa/search', [
      'searchQuery' => 'test',
      'taxon_list_id' => 1,
    ]);
    $this->assertResponseOk($response, '/taxa/search');
    $this->assertArrayHasKey(
      'paging', $response['response'],
      'Paging missing from response to call to taxa/search'
    );
    $this->assertArrayHasKey(
      'data', $response['response'],
      'Data missing from response to call to taxa/search'
    );
    $data = $response['response']['data'];
    $this->assertIsArray($data, 'taxa/search data invalid.');
    $this->assertCount(2, $data, 'Taxa/search data wrong count returned.');
    $response = $this->callService('taxa/search', [
      'searchQuery' => 'test taxon 2',
      'taxon_list_id' => 1,
    ]);
    $this->assertResponseOk($response, '/taxa/search');
    $this->assertArrayHasKey(
      'paging', $response['response'],
      'Paging missing from response to call to taxa/search'
    );
    $this->assertArrayHasKey(
      'data', $response['response'],
      'Data missing from response to call to taxa/search'
    );
    $data = $response['response']['data'];
    $this->assertIsArray($data, 'taxa/search data invalid.');
    $this->assertCount(1, $data, 'Taxa/search data wrong count returned.');
    $response = $this->callService('taxa/search', [
      'taxon_list_id' => 1,
    ]);
    $this->assertResponseOk($response, '/taxa/search');
    $data = $response['response']['data'];
    $this->assertCount(2, $data, 'Taxa/search data wrong count returned.');
    $response = $this->callService('taxa/search', [
      'taxon_list_id' => 1,
      'min_taxon_rank_sort_order' => 300,
    ]);
    $this->assertResponseOk($response, '/taxa/search');
    $data = $response['response']['data'];
    $this->assertCount(1, $data, 'Taxa/search data wrong count returned.');
  }

  /**
   * Test for accessing the reports hierarchy.
   */
  public function testReportsHierarchy_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testReportsHierarchy_get");

    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports", ['proj_id' => $projDef['id']]);
    $this->assertResponseOk($response, '/reports');
    // Check a folder that should definitely exist.
    $this->checkReportFolderInReponse($response['response'], 'library');
    // The demo report is not featured, so should not exist.
    $this->assertFalse(array_key_exists('demo', $response['response']));

    // Repeat with an authMethod that allows access to non-featured reports.
    // There should be an additional featured folder at the top level with shortcuts
    // shortcuts to favourite reports.
    $this->authMethod = 'hmacWebsite';
    $response = $this->callService("reports", ['proj_id' => $projDef['id']]);
    $this->checkReportFolderInReponse($response['response'], 'featured');
    $this->checkReportInReponse($response['response'], 'demo');

    // Now check some folder contents.
    $this->authMethod = 'hmacClient';
    $response = $this->callService("reports/featured", ['proj_id' => $projDef['id']]);
    $this->assertResponseOk($response, '/reports/featured');
    $this->checkReportInReponse($response['response'], 'library/occurrences/filterable_explore_list');
    $response = $this->callService("reports/library", ['proj_id' => $projDef['id']]);
    $this->assertResponseOk($response, '/reports/library');
    $this->checkReportFolderInReponse($response['response'], 'occurrences');
    $response = $this->callService("reports/library/occurrences", ['proj_id' => $projDef['id']]);
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $this->checkReportInReponse($response['response'], 'filterable_explore_list');
  }

  public function testMissingReportFile() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/some_random_report_name.xml', []);
    $this->assertEquals(
      404, $response['httpCode'],
      'Request for a missing report does not return 404.'
    );
  }

  public function testReportParams_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testReportParams_get");

    // First grab a list of reports so we can use the links to get the correct
    // params URL.
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", ['proj_id' => $projDef['id']]);
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $reportDef = $response['response']['filterable_explore_list'];
    $this->assertArrayHasKey('params', $reportDef, 'Report response does not define parameters');
    $this->assertArrayHasKey('href', $reportDef['params'], 'Report parameters missing href');
    // Now grab the params URL output and check it
    $response = $this->callUrl($reportDef['params']['href']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml/params');
    $this->assertArrayHasKey('data', $response['response']);
    $this->assertArrayHasKey('smpattrs', $response['response']['data']);
    $this->assertArrayHasKey('occurrence_id', $response['response']['data']);
  }

  public function testReportColumns_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testReportColumns_get");

    // First grab a list of reports so we can use the links to get the correct
    // columns URL.
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", ['proj_id' => $projDef['id']]);
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $reportDef = $response['response']['filterable_explore_list'];
    $this->assertArrayHasKey('columns', $reportDef, 'Report response does not define columns');
    $this->assertArrayHasKey('href', $reportDef['columns'], 'Report columns missing href');
    // Now grab the columns URL output and check it.
    $response = $this->callUrl($reportDef['columns']['href']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml/columns');
    $this->assertArrayHasKey('data', $response['response']);
    $this->assertArrayHasKey('occurrence_id', $response['response']['data']);
    $this->assertArrayHasKey('taxon', $response['response']['data']);
  }

  public function testReportOutput_get() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testReportOutput_get");

    // First grab a list of reports so we can use the links to get the correct
    // columns URL.
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", ['proj_id' => $projDef['id']]);
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $reportDef = $response['response']['filterable_explore_list'];
    $this->assertArrayHasKey('href', $reportDef, 'Report response missing href');
    // Now grab the columns URL output and check it.
    $response = $this->callUrl($reportDef['href']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml');
    $this->assertArrayHasKey('data', $response['response']);
    $this->assertCount(1, $response['response']['data'], 'Report call returns incorrect record count');
    $this->assertEquals(
      1, $response['response']['data'][0]['occurrence_id'],
      'Report call returns incorrect record'
    );
  }

  /**
   * Test REST GET for the list of groups.
   */
  public function testGroups_get() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService("groups", []);
    $this->assertResponseOk($response, 'groups');
    // Not a member, so default response empty.
    $this->assertEquals(0, count($response['response']), 'Fetching groups when not a member should return 0.');
    $response = $this->callService("groups", ['view' => 'all_available']);
    $this->assertEquals(2, count($response['response']), 'Fetching all_available groups when not a member should return 2 groups (public + by request).');
    $this->assertNotEquals('private group 2', $response['response'][0]['values']['title'], 'Wrong group returned');
    $this->assertNotEquals('private group 2', $response['response'][1]['values']['title'], 'Wrong group returned');
    $response = $this->callService("groups", ['view' => 'joinable']);
    $this->assertEquals(2, count($response['response']), 'Fetching joinable groups when not a member should return 2.');
    // Make user a member of group 1 (public).
    $db = new Database();
    $db->query("insert into groups_users(group_id, user_id, created_by_id, created_on, updated_by_id, updated_on) values (1, 1, 1, now(), 1, now())");
    $response = $this->callService("groups", []);
    // Should now return 1 group in default request.
    $this->assertEquals(1, count($response['response']), 'Fetching groups when a member should return 1.');
    // No pages as didn't ask for verbose.
    $this->assertArrayNotHasKey('pages', $response['response'][0]['values'], 'Request for groups without verbose should not have returned pages');
    $response = $this->callService("groups", ['verbose' => '1']);
    // Pages as did ask for verbose.
    $this->assertArrayHasKey('pages', $response['response'][0]['values'], 'Request for groups with verbose should have returned pages');
    $response = $this->callService("groups", ['page' => 'record/list']);
    // Should now return 1 group in default request.
    $this->assertEquals(1, count($response['response']), 'Fetching groups when a member should return 1 if page parameter correct.');
    $response = $this->callService("groups", ['page' => 'record/other-list']);
    // Should now return 1 group in default request.
    $this->assertEquals(0, count($response['response']), 'Fetching groups when a member should return 0 if page parameter incorrect.');
    // Add to the private group.
    $db->query("insert into groups_users(group_id, user_id, created_by_id, created_on, updated_by_id, updated_on) values (2, 1, 1, now(), 1, now())");
    $response = $this->callService("groups", []);
    // Should now return 2 groups in default request.
    $this->assertEquals(2, count($response['response']), 'Fetching groups when a member of both should return 2 groups.');
    // Plus 2 in the all_available request.
    $response = $this->callService("groups", ['view' => 'all_available']);
    $this->assertEquals(3, count($response['response']), 'Fetching all_available groups when a member of the private group should return 3 (all).');
    // Plus 0 in the joinable request.
    $response = $this->callService("groups", ['view' => 'joinable']);
    $this->assertEquals(1, count($response['response']), 'Fetching joinable groups when a member of both should return 1.');
    // Change membership of private group to pending.
    $db->query('update groups_users set pending=true where user_id=1 and group_id=2');
    // Should now return 1 groups in default request.
    $response = $this->callService("groups", []);
    $this->assertEquals(1, count($response['response']), 'Fetching groups when a member of one and pending another should return 1 group.');
    $this->assertEquals(1, $response['response'][0]['values']['id'], 'Incorrect group returned when requesting groups user is a member of');
    $response = $this->callService("groups", ['view' => 'pending']);
    $this->assertEquals(1, count($response['response']), 'Fetching pending groups when pending membership of only one should return 1 group.');
    $this->assertEquals(2, $response['response'][0]['values']['id'], 'Incorrect group returned when requesting groups user is pending membership of');
    // Check invalid view value.
    $response = $this->callService("groups", ['view' => 'foo']);
    $this->assertTrue($response['httpCode'] === 400);
  }

  /**
   * Test REST GET for the a single group.
   */
  public function testGroups_getId() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService("groups/2", []);
    $this->assertTrue($response['httpCode'] === 404);
    $response = $this->callService("groups/1", []);
    $this->assertResponseOk($response, 'groups');
    $this->assertArrayNotHasKey('pages', $response['response']['values'], 'Request for groups without verbose should not have returned pages');
    $this->assertEquals('public group 1', $response['response']['values']['title'], 'Wrong group returned');
    $response = $this->callService("groups/1", ['verbose' => '1']);
    $this->assertResponseOk($response, 'groups');
    $this->assertArrayHasKey('pages', $response['response']['values'], 'Request for verbose groups should have returned pages');
    $this->assertEquals(1, count($response['response']['values']['pages']), 'Verbose groups request should have returned 1 page');
    $this->assertEquals('record/list', $response['response']['values']['pages'][0]['path'], 'Verbose groups request returned incorrect page data');
  }

  /**
   * Test REST GET for the a single group's locations.
   */
  public function testGroups_getLocations() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService("groups/1/locations", []);
    $this->assertEquals(1, count($response['response']), 'Group 1 should have 1 location');
    $this->assertEquals(1, $response['response'][0]['values']['location_id'], 'Location ID 1 should be linked to group 1.');
  }

  public function testGroups_postLocation() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $values = [
      'name' => 'Test location',
      'centroid_sref' => 'ST1234',
      'centroid_sref_system' => 'OSGB',
    ];
    $response = $this->callService("groups/1/locations", [], ['values' => $values]);
    $this->assertEquals(403, $response['httpCode'], 'Posting a group location when not a member did not return 403 Forbidden');

    // Make a new user a member of group 1.
    $db = new Database();
    $userId = $this->createExtraUser($db)['user_id'];
    // Make a group member and a user of the website.
    $db->query("insert into groups_users(group_id, user_id, created_by_id, created_on, updated_by_id, updated_on) values (1, $userId, 1, now(), 1, now())");
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      " VALUES ($userId, 1, 3, 1, now(), 1, now())");
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId, time() + 120);
    $response = $this->callService("groups/1/locations", [], ['values' => $values]);
    $this->assertEquals(201, $response['httpCode'], 'Posting a group location did not return a success response');

    $response = $this->callService("groups/1/locations", []);
    $this->assertEquals(2, count($response['response']), 'Group 1 should have 2 locations after adding one.');
    // POST a location to test adding separately.
    $response = $this->callService("locations", [], ['values' => $values]);
    $newLocationId = $response['response']['values']['id'];
    $values = [
      'id' => $newLocationId,
    ];
    $response = $this->callService("groups/1/locations", [], ['values' => $values]);
    $this->assertEquals(201, $response['httpCode'], 'Posting a group an existing location did not return a success response');
    $response = $this->callService("groups/1/locations", [], ['values' => $values]);
    $this->assertEquals(409, $response['httpCode'], 'Posting a group an existing location twice did not return a conflict');
    $this->assertEquals(3, count($response['response']), 'Group 1 should have 3 locations after adding two.');
  }

  public function testGroups_getPostDeleteUser() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    // Get initial count.
    $response = $this->callService("groups/1/users");
    $initialGroupUserCount = count($response['response']);
    $db = new Database();
    $userId2 = $this->createExtraUser($db)['user_id'];
    $db->query('INSERT INTO users_websites (user_id, website_id, site_role_id, created_by_id, created_on, updated_by_id, updated_on) ' .
      "VALUES ($userId2, 1, 3, 1, now(), 1, now())");
    $values = [
      'id' => 1,
    ];
    $response = $this->callService("groups/1/users", [], ['values' => $values]);
    $this->assertEquals(201, $response['httpCode'], 'User could not join public group');
    $response = $this->callService("groups/1/users");
    $this->assertEquals($initialGroupUserCount + 1, count($response['response']), 'User was not self-added to group');
    $response = $this->callService("groups/2/users", [], ['values' => $values]);
    $this->assertEquals(403, $response['httpCode'], 'User joining private group should be forbidden');
    $values = [
      'id' => $userId2,
    ];
    $response = $this->callService("groups/1/users", [], ['values' => $values]);
    $this->assertEquals(403, $response['httpCode'], 'User should not be able to add another user to group');
    $db->query('UPDATE groups_users SET administrator=true WHERE user_id=1 AND group_id=1');
    $response = $this->callService("groups/1/users", [], ['values' => $values]);
    $this->assertEquals(201, $response['httpCode'], 'Admin user should be able to add other members to group');
    $response = $this->callService("groups/1/users");
    $this->assertEquals($initialGroupUserCount + 2, count($response['response']), 'User was not added to group by admin');
    // Auth as 2nd user as they aren't admin.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', $userId2, time() + 120);
    $response = $this->callService("groups/1/users");
    $this->assertEquals(1, count($response['response']), 'Non admin user should only see self in group');
    $this->assertEquals($userId2, $response['response'][0]['values']['user_id'], 'Non admin user seeing incorrect user details in group');
    $response = $this->callService("groups/1/users/1", FALSE, [], [], 'DELETE');
    $this->assertEquals(403, $response['httpCode'], 'Non admin user should not be able to remove other users');
    $response = $this->callService("groups/1/users/$userId2", FALSE, [], [], 'DELETE');
    $this->assertEquals(204, $response['httpCode'], 'Non admin user should be able to remove self from group');
    $response = $this->callService("groups/1/users");
    $this->assertEquals(0, count($response['response']), 'User was not deleted from group by self');
    // Add user 2 back.
    $response = $this->callService("groups/1/users", [], ['values' => $values]);
    $this->assertEquals(201, $response['httpCode']);
    // Add to a by request group - test the added user is pending.
    $response = $this->callService("groups/3/users", [], ['values' => $values]);
    $this->assertEquals(201, $response['httpCode']);
    $response = $this->callService("groups/3/users");
    $this->assertEquals(1, count($response['response']), 'Non admin user should be able to add themselves to a by request group');
    $this->assertEquals('t', $response['response'][0]['values']['pending'], 'User added to by request group not set to pending.');
    // Should be able to confirm this by GETting the group.
    $response = $this->callService("groups/3");
    $this->assertEquals('t', $response['response']['values']['user_is_pending'], 'User added to by request group not set to pending.');
    $this->assertEquals('f', $response['response']['values']['user_is_member'], 'User added to by request group immediately made member.');
    $this->assertEquals('f', $response['response']['values']['user_is_administrator'], 'User added to by request group incorrectly made admin.');
    // Auth back as admin user.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService("groups/1/users/999", FALSE, [], [], 'DELETE');
    $this->assertEquals(404, $response['httpCode'], 'DELETE invalid group user ID should return 404');
    $response = $this->callService("groups/1/users/$userId2", FALSE, [], [], 'DELETE');
    $this->assertEquals(204, $response['httpCode'], 'DELETE returned incorrect response');
    $response = $this->callService("groups/1/users");
    $this->assertEquals($initialGroupUserCount + 1, count($response['response']), 'User was not deleted from group by admin');
  }

  public function testAcceptHeader() {
    Kohana::log('debug', "Running unit test, RestControllerTest::testAcceptHeader");
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService(
      "reports/library/occurrences",
      ['proj_id' => $projDef['id']],
      NULL,
      ['Accept: application/json']
    );
    $decoded = json_decode($response['response'], TRUE);
    $this->assertNotEquals(NULL, $decoded, 'JSON response could not be decoded: ' . $response['response']);
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(0, $response['curlErrno']);
    $response = $this->callService(
      "reports/library/occurrences",
      ['proj_id' => $projDef['id']],
      NULL,
      ['Accept: text/html']
    );
    $this->assertMatchesRegularExpression('/^<!DOCTYPE HTML>/', $response['response']);
    $this->assertMatchesRegularExpression('/<html>/', $response['response']);
    $this->assertMatchesRegularExpression('/<\/html>$/', $response['response']);
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(0, $response['curlErrno']);
    // Try requesting an invalid content type as first preference - response
    // should select the second.
    $response = $this->callService(
      "reports/library/occurrences",
      ['proj_id' => $projDef['id']],
      NULL,
      ['Accept: image/png, application/json']
    );
    $decoded = json_decode($response['response'], TRUE);
    $this->assertNotEquals(
      NULL, $decoded,
      'JSON response could not be decoded: ' . $response['response']
    );
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(0, $response['curlErrno']);
  }

  /**
   * Tests authentication against a resource, by passing incorrect user or
   * secret, then finally passing the correct details to check a valid response
   * returns.
   *
   * @param string $resource
   *   Resource path.
   * @param array $query
   *   Query parameters to pass in the URL
   */
  private function checkResourceAuthentication($resource, $query = []) {
    $correctClientUserId = self::$clientUserId;
    $correctWebsiteId = self::$websiteId;
    $correctUserId = self::$userId;
    $correctClientSecret = self::$clientSecret;
    $correctWebsitePassword = self::$websitePassword;
    $correctUserPassword = self::$userPassword;

    // break the secrets/passwords
    self::$clientUserId = $correctClientUserId;
    self::$websiteId = $correctWebsiteId;
    self::$userId = $correctUserId;
    self::$clientSecret = '---';
    self::$websitePassword = '---';
    self::$userPassword = '---';

    $response = $this->callService($resource, $query);
    $this->assertEquals(
      401, $response['httpCode'],
      "Incorrect secret or password passed to /$resource but request " .
      "authorised. Http response $response[httpCode]."
    );
    $this->assertEquals(
      'Unauthorized', $response['response']['status'],
      "Incorrect secret or password passed to /$resource but data still returned. " . var_export($response, TRUE)
    );
    self::$clientSecret = $correctClientSecret;
    self::$websitePassword = $correctWebsitePassword;
    self::$userPassword = $correctUserPassword;

    // Break the user IDs.
    self::$clientUserId = '---';
    self::$websiteId = '---';
    self::$userId = '---';
    self::$clientSecret = $correctClientSecret;
    self::$websitePassword = $correctWebsitePassword;
    self::$userPassword = $correctUserPassword;
    $response = $this->callService($resource, $query);
    $this->assertEquals(
      401, $response['httpCode'],
      "Incorrect userId passed to /$resource but request authorised. Http " .
      "response $response[httpCode]."
    );
    $this->assertEquals(
      'Unauthorized', $response['response']['status'],
      "Incorrect userId passed to /$resource but data still returned. " . var_export($response, TRUE)
    );

    // Now test with everything correct.
    self::$clientUserId = $correctClientUserId;
    self::$websiteId = $correctWebsiteId;
    self::$userId = $correctUserId;
    self::$clientSecret = $correctClientSecret;
    self::$websitePassword = $correctWebsitePassword;
    self::$userPassword = $correctUserPassword;
    $response = $this->callService($resource, $query);
    $this->assertResponseOk($response, "/$resource");
  }

  /**
   * An assertion that the response object returned by a call to getCurlResponse
   * indicates a successful request.
   *
   * @param array $response
   *   Response data returned by getCurlReponse().
   * @param string $apiCall
   *   Name of the API method being called, e.g. /projects
   */
  private function assertResponseOk($response, $apiCall) {
    $this->assertEquals(200, $response['httpCode'],
      "Invalid response from call to $apiCall. HTTP Response $response[httpCode]. Curl error " .
      "$response[curlErrno] ($response[errorMessage]).");
    $this->assertEquals(0, $response['curlErrno'],
      "Invalid response from call to $apiCall. HTTP Response $response[httpCode]. Curl error " .
      "$response[curlErrno] ($response[errorMessage]).");
  }

  /**
   * Checks that an array retrieved from the API is a valid taxon-occurrence resource.
   *
   * @param array $data
   *   Array to be tested as a taxon occurrence resource
   */
  private function checkValidTaxonObservation(array $data) {
    $this->assertIsArray($data, 'Taxon-observation object invalid. ' . var_export($data, TRUE));
    $mustHave = ['id', 'href', 'datasetName', 'taxonVersionKey', 'taxonName',
        'startDate', 'endDate', 'dateType', 'projection', 'precision', 'recorder', 'lastEditDate'];
    foreach ($mustHave as $key) {
      $this->assertArrayHasKey($key, $data,
          "Missing $key from taxon-observation resource. " . var_export($data, TRUE));
      $this->assertNotEmpty($data[$key],
          "Empty $key in taxon-observation resource" . var_export($data, TRUE));
    }
    // @todo Format tests
  }

  /**
   * Checks that an array retrieved from the API is a valid annotation resource.
   *
   * @param array $data
   *   Array to be tested as an annotation resource
   */
  private function checkValidAnnotation(array $data) {
    $this->assertIsArray($data, 'Annotation object invalid. ' . var_export($data, TRUE));
    $mustHave = ['id', 'href', 'taxonObservation', 'taxonVersionKey', 'comment',
        'question', 'authorName', 'dateTime'];
    foreach ($mustHave as $key) {
      $this->assertArrayHasKey($key, $data,
        "Missing $key from annotation resource. " . var_export($data, TRUE));
      $this->assertNotEmpty($data[$key],
        "Empty $key in annotation resource" . var_export($data, TRUE));
    }
    if (!empty($data['statusCode1'])) {
      $this->assertMatchesRegularExpression(
        '/[AUN]/', $data['statusCode1'],
        'Invalid statusCode1 value for annotation'
      );
    }
    if (!empty($data['statusCode2'])) {
      $this->assertMatchesRegularExpression(
        '/[1-6]/', $data['statusCode2'],
        'Invalid statusCode2 value for annotation'
      );
    }
    // We should be able to request the taxon observation associated with the
    // occurrence.
    $session = $this->initCurl($data['taxonObservation']['href']);
    $response = $this->getCurlResponse($session);
    $this->assertResponseOk($response, $data['taxonObservation']['href']);
    $this->checkValidTaxonObservation($response['response']);
  }

  /**
   * Assert that a folder exists in the response from a call to /reports.
   *
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
   *
   * @param array $response
   * @param string $reportFile
   */
  private function checkReportInReponse($response, $reportFile) {
    $this->assertArrayHasKey($reportFile, $response);
    $this->assertArrayHasKey('type', $response[$reportFile]);
    $this->assertEquals('report', $response[$reportFile]['type']);
  }

}
