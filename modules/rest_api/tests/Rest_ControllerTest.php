<?php

/**
 * Unit test class for the REST api controller.
 * @todo Test sharing mode on project filters is respected.
 *
 */
class Rest_ControllerTest extends Indicia_DatabaseTestCase {

  private static $privateKey = <<<KEY
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC8kGa1pSjbSYZVebtTRBLxBz5H4i2p/llLCrEeQhta5kaQu/Rn
vuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t0tyazyZ8JXw+KgXTxldMPEL9
5+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4ehde/zUxo6UvS7UrBQIDAQAB
AoGAb/MXV46XxCFRxNuB8LyAtmLDgi/xRnTAlMHjSACddwkyKem8//8eZtw9fzxz
bWZ/1/doQOuHBGYZU8aDzzj59FZ78dyzNFoF91hbvZKkg+6wGyd/LrGVEB+Xre0J
Nil0GReM2AHDNZUYRv+HYJPIOrB0CRczLQsgFJ8K6aAD6F0CQQDzbpjYdx10qgK1
cP59UHiHjPZYC0loEsk7s+hUmT3QHerAQJMZWC11Qrn2N+ybwwNblDKv+s5qgMQ5
5tNoQ9IfAkEAxkyffU6ythpg/H0Ixe1I2rd0GbF05biIzO/i77Det3n4YsJVlDck
ZkcvY3SK2iRIL4c9yY6hlIhs+K9wXTtGWwJBAO9Dskl48mO7woPR9uD22jDpNSwe
k90OMepTjzSvlhjbfuPN1IdhqvSJTDychRwn1kIJ7LQZgQ8fVz9OCFZ/6qMCQGOb
qaGwHmUK6xzpUbbacnYrIM6nLSkXgOAwv7XXCojvY614ILTK3iXiLBOxPu5Eu13k
eUz9sHyD6vkgZzjtxXECQAkp4Xerf5TGfQXGXhxIX52yH+N2LtujCdkQZjXAsGdm
B2zNzvrlgRmgBrklMTrMYgm1NPcW+bRLGcwgW2PTvNM=
-----END RSA PRIVATE KEY-----
KEY;

  private static $wrongPrivateKey = <<<KEY
-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEAsTlOczkGR9lSFJLQvXS8pdU8bVM0nnGbEch8j0Nw04hR3n6t
QF2nDkBofhYGTc5mSDhY+XGDuVE8mqG1DbeMlIL8BOR3V7oNZlrew8BhI2Cr8MDE
SI/Z2Ry+oJLjbrmEdMl0AOwOTnl8V6+cpKIo4OtsZBMMhsPPb0Hj1DKiLjt9uxUo
Qmi+fpNVjodS3ETpGcrGnH/gj98kScau5ahDAeeb0+zRN6ih3SQQPiKU45P8YqzL
2OGnjV1u5f1N30hvJhUeVJjC7RDKLe+JTC1g5599Jt0nlosD6liKJidWgzVj1GT6
QgNoOgMyEUaYy+tRv4st8C5c3+11GVh3az3hDQIDAQABAoIBAC3gXMt89oBA5HuI
6doxTuhKw8K1KEjftbmrwXrAhYNspWzINAcWdzk8ORBymR0pEdceJwIjfWrKebq5
o4myewSyx5Roo/AkrHVTjpjzwvGKg9flvqnd+xG13C7q907hXUVyJMJcWPO9hQ+Z
2R3REG3w43UgbTyxkZAGaXizxsHanAUPJA2NKnyAyeR3nPlxIo94d/bXV57Jlgzd
DHCFDde60UWIjBs2rOrZsHbfJcS2y0/d0NjzdFZ/qHHFEe3T7NrUkr5p3fSLYnyQ
LTaT5LkAtnHRZYU+0iTW4tRFfBPv3BbVjinFtX68odShO0oeESnuR0/rPPS4gD/3
9S6+CyECgYEA5UXwF+1vqoalZtagPcNjYaDziKyxQvLTxRUoINX6muBzKR14+Ai7
cwhdLFn3c9tKa/siqx4cv0g7zQ60sFFi0krURdiHjc0ryB2qEp16FWr7VfL2ocRY
VhDMsCDGKDYpsCbaob1dZHKrAGVseiqo4mw8T53xv4YLj8jevq2rIcUCgYEAxeIX
ZAKQUsrtXkJMrQ0zIqHay+2N18h0ddlDf3nQLU1fIEV2UD8D/zMUXz4gCxvdlJbn
oQ65ik5WFeQOmW+wbPb/RXqnlnp1fkjJvrXOXJH4xT8KCgtK/4V65UaUCypTJvBo
ubzFDPYyWuhzvGeQwyJ8UMZrrpG9AF0KzwSJnqkCgYEAmwbxU5yO9wVYbfMOIvUt
C+SjB3WN3rEHFKo7mghWDcda1yBAnaZ56UxshALJWaOb7OvBA2e3FHgpR3x8HQTL
B1rlsdy5u95RjlzQlQm6dSUDkZhZwARdnsR5Q1bF5obJJX0ANIEw1yzaB8iM0wZp
b8Cz/znTLyfaRX0TcGdJ4ekCgYEAk7HLiY7MT688ebT8a9FFUF0D5F97Fgp8uhUe
Zv/xXE66aGjQBNbz0b87PlctLX1v5d64JaLK4yrS3+Xm66jMQpgcMax5dzwRg98q
DRi/XKJqzjXd9V82a/8hmg0SpD7D73VShQcbADClpuqGr6GRD8Qmi1d9ub73FVVc
ouUdHnkCgYEAr8XI6BO45s2WGwRR7rQu9gD6yiiMKU0yxh5SiCfw4t7ozHAC9qn+
jC+OwtS/Q73xTjlggYovtXy/mXj7w5PW0QlTbpAWbRlSgHlLef/RKI7mBbOe3poK
zuU4nn90WJxLocAJYXoU37xhvUXI1sYU2SSu2E4ANrngT3ZuoktXgCc=
-----END RSA PRIVATE KEY-----
KEY;

  private static $publicKey = <<<KEY
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8kGa1pSjbSYZVebtTRBLxBz5H
4i2p/llLCrEeQhta5kaQu/RnvuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t
0tyazyZ8JXw+KgXTxldMPEL95+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4
ehde/zUxo6UvS7UrBQIDAQAB
-----END PUBLIC KEY-----
KEY;

  private static $clientUserId;
  private static $config;
  private static $websiteId = 1;
  private static $websitePassword = 'password';
  private static $userId = 1;
  private static $userPassword = 'password';
  // In the fixture, the 2nd filter is the one we linked to a user.
  private static $userFilterId = 2;

  private $authMethod = 'hmacClient';

  private $additionalRequestHeader = [];

  // Access tokens.
  private static $jwt;
  private static $oAuthAccessToken;

  public function getDataSet() {
    $ds1 = new PHPUnit_Extensions_Database_DataSet_YamlDataSet('modules/phpUnit/config/core_fixture.yaml');

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
            'updated_by_id' => 1,
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
    // Make sure public key stored.
    $db = new Database();
    $db->update(
      'websites',
      ['public_key' => self::$publicKey],
      ['id' => 1]
    );
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
    $r = $this->getCurlResponse($session);
    $this->assertEquals(200, $r['httpCode'], 'Valid request to /token failed.');
    self::$oAuthAccessToken = $r['response']['access_token'];
    $this->assertNotEmpty(self::$oAuthAccessToken, 'No oAuth access token returned');

    // Check oAuth2 doesn't allow access to incorrect resources
    $this->authMethod = 'oAuth2User';
    $response = $this->callService('projects');
    $this->assertEquals(401, $response['httpCode'], 'Invalid authentication method oAuth2 for projects  ' .
        "but response still OK. Http response $response[httpCode].");

    // Now try a valid request with the access token
    $response = $this->callService('taxon-observations', array('edited_date_from' => '2015-01-01', 'proj_id' => 'BRC1'));
    $this->assertEquals(200, $response['httpCode'], 'oAuth2 request to taxon-observations failed.');

    // Now try a valid request with the access token for the reports endpoint
    $response = $this->callService('reports', array());
    $this->assertEquals(200, $response['httpCode'], 'oAuth2 request to reports failed.');

    $response = $this->callService('reports/library/occurrences/filterable_explore_list.xml', array());
    $this->assertEquals(200, $response['httpCode'], 'oAuth2 request to the filterable_explore_list report failed.');

    // Now try a bad access token
    self::$oAuthAccessToken = '---';
    $response = $this->callService('taxon-observations', array('edited_date_from' => '2015-01-01'));
    $this->assertEquals(401, $response['httpCode'], 'Invalid token oAuth2 request to taxon-observations should fail.');
  }

  private function getJwt($privateKey, $iss, $userId, $exp) {
    require_once 'vendor/autoload.php';
    $payload = [
      'iss' => $iss,
      'http://indicia.org.uk/user:id' => $userId,
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
      array('public_key' => NULL),
      array('id' => 1)
    );
    $cache->delete($cacheKey);
    // Make an otherwise valid call - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 401);
    // Store the public key so Indicia can check signed requests.
    $db = new Database();
    $db->update(
      'websites',
      array('public_key' => self::$publicKey),
      array('id' => 1)
    );
    $cache->delete($cacheKey);
    // Make a valid call - should be authorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 200);
    // Make a bogus call - should be unauthorised.
    self::$jwt = base64_encode('abcdefg1234.123456789.zyx');
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 401);
    // Make a valid call with wrong iss - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.ukx', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 401);
    // Make an expired call - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() - 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 401);
    // Make a valid call with wrong user - should be unauthorised.
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 2, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 401);
    // Make an call with wrong key
    self::$jwt = $this->getJwt(self::$wrongPrivateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService('reports/library/months/filterable_species_counts.xml');
    $this->assertTrue($response['httpCode'] === 401);
  }

  public function testJwtSamplePostInvalid() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
    $response = $this->callService(
      'samples',
      FALSE,
      [
        'values' => [
          // Omit survey ID.
          'entered_sref' => 'SU1234',
          'entered_sref_system' => 'OSGB',
          'date' => '01/08/2020',
        ]
      ]
    );
    $this->assertEquals(400, $response['httpCode']);
    $this->assertTrue(array_key_exists('message', $response['response'])
      && array_key_exists('sample:survey_id', $response['response']['message']));
  }

  private function parseHeaders($string) {
    $rows = explode("\n", trim($string));
    // Skip response code at the top.
    array_shift($rows);
    $array = [];
    foreach ($rows as $row) {
      list($key, $value) = explode(': ', $row, 2);
      $array[$key] = trim($value);
    }
    return $array;
  }

  public function testJwtSamplePost1() {
    $this->authMethod = 'jwtUser';
    self::$jwt = $this->getJwt(self::$privateKey, 'http://www.indicia.org.uk', 1, time() + 120);
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
      [
        'values' => $data
      ]
    );
    $this->assertEquals(201, $response['httpCode']);
    $headers = $this->parseHeaders($response['headers']);
    $this->assertTrue(array_key_exists('Location', $headers),
      'POST samples does not return Location in header.');
    $this->assertTrue(array_key_exists('Access-Control-Allow-Origin', $headers),
      'POST samples does not return Access-Control-Allow-Origin in header.');
    $this->assertEquals('*', $headers['Access-Control-Allow-Origin'],
      'CORS not enabled correctly');
    $this->assertTrue(array_key_exists('values', $response['response']),
      'POST samples response does not contain values.');
    $this->assertTrue(array_key_exists('id', $response['response']['values']),
      'POST samples response does not contain id in values.');
    $id = $response['response']['values']['id'];
    $this->assertTrue(array_key_exists('href', $response['response']),
      'POST samples response does not contain href.');
    $this->assertEquals($response['response']['href'], $headers['Location'],
      'POST samples response href does not match header Location.');
    $this->assertEquals(url::base() . "index.php/services/rest/samples/$id", $response['response']['href'],
      'POST samples response href inforrect');

    // GET the posted data;
    $response = $this->callService("samples/$id");
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals('A sample comment test', $response['response']['values']['comment']);
    // POST a bad update with ID mismatch
    $data = [
      'id' => $id + 1,
      'entered_sref' => 'SU121341',
    ];
    $response = $this->callService(
      "samples/$id",
      FALSE,
      [
        'values' => $data
      ]
    );
    $this->assertEquals(400, $response['httpCode']);
    // POST an update.
    $data = [
      'entered_sref' => 'SU121341',
    ];
    $response = $this->callService(
      "samples/$id",
      FALSE,
      [
        'values' => $data
      ]
    );
    $this->assertEquals(200, $response['httpCode']);
    // Check update worked.
    $response = $this->callService("samples/$id");
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals('SU121341', $response['response']['values']['entered_sref']);
    // Existing values not removed.
    $this->assertEquals('A sample comment test', $response['response']['values']['comment']);
    // Update sample's user ID and try to fetch - ensure not found.
    $db = new Database();
    $db->query('update samples set created_by_id=2 where id=' . $response['response']['values']['id']);
    $response = $this->callService('samples/' . $response['response']['values']['id']);
    $this->assertEquals(404, $response['httpCode']);
    // POST update should also fail.
    $data = [
      'entered_sref' => 'SU121342',
    ];
    $response = $this->callService(
      "samples/$id",
      FALSE,
      [
        'values' => $data
      ]
    );
    $this->assertEquals(404, $response['httpCode']);
    // Do a test for missing sample.
    $response = $this->callService('samples/99999');
    $this->assertEquals(404, $response['httpCode']);
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
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method hmacUser for projects ' .
        "but response still OK. Http response $response[httpCode].");
    $this->authMethod = 'directUser';
    $response = $this->callService('projects');
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method directUser for projects ' .
        "but response still OK. Http response $response[httpCode].");
    $this->authMethod = 'hmacWebsite';
    $response = $this->callService('projects');
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method hmacWebsite for projects ' .
        "but response still OK. Http response $response[httpCode].");
    $this->authMethod = 'directWebsite';
    $response = $this->callService('projects');
    $this->assertTrue($response['httpCode']===401, 'Invalid authentication method directWebsite for projects ' .
        "but response still OK. Http response $response[httpCode].");

    $this->authMethod = 'hmacClient';
  }

  public function testProjects_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_get");

    $response = $this->callService('projects');
    $this->assertResponseOk($response, '/projects');
    $viaConfig = self::$config['projects'];
    $this->assertEquals(count($viaConfig), count($response['response']['data']),
        'Incorrect number of projects returned from /projects.');
    foreach ($response['response']['data'] as $projDef) {
      $this->assertArrayHasKey($projDef['id'], $viaConfig, "Unexpected project $projDef[id]returned by /projects.");
      $this->assertEquals($viaConfig[$projDef['id']]['title'], $projDef['title'],
        "Unexpected title $projDef[title] returned for project $projDef[id] by /projects.");
      $this->assertEquals($viaConfig[$projDef['id']]['description'], $projDef['description'],
        "Unexpected description $projDef[description] returned for project $projDef[id] by /projects.");
      // Some project keys are supposed to be removed
      $this->assertNotContains('filter_id', $projDef, 'Project definition should not contain filter_id');
      $this->assertNotContains('sharing', $projDef, 'Project definition should not contain sharing');
    }
  }

  public function testProjects_get_id() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testProjects_get_id");

    foreach (self::$config['projects'] as $projDef) {
      $response = $this->callService("projects/$projDef[id]");
      $this->assertResponseOk($response, "/projects/$projDef[id]");
      $this->assertEquals($projDef['title'], $response['response']['title'],
          "Unexpected title " . $response['response']['title'] .
          " returned for project $projDef[id] by /projects/$projDef[id].");
      $this->assertEquals($projDef['description'], $response['response']['description'],
          "Unexpected description " . $response['response']['description'] .
          " returned for project $projDef[id] by /projects/$projDef[id].");
      // Some project keys are supposed to be removed
      $this->assertNotContains('filter_id', $projDef, 'Project definition should not contain filter_id');
      $this->assertNotContains('sharing', $projDef, 'Project definition should not contain sharing');
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
    // @todo The following test needs to check filtered response rather than authentication
    $this->authMethod = 'directUser';
    $this->checkResourceAuthentication('taxon-observations', $query + array('filter_id' => self::$userFilterId));
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
      $this->assertArrayHasKey('paging', $response['response'],
          'Paging missing from response to call to taxon-observations');
      $this->assertArrayHasKey('data', $response['response'],
          'Data missing from response to call to taxon-observations');
      $data = $response['response']['data'];
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
      $response = $this->callService(
        "annotations",
        array('proj_id' => $projDef['id'], 'edited_date_from' => '2015-01-01')
      );
      $this->assertResponseOk($response, '/annotations');
      $this->assertArrayHasKey('paging', $response['response'], 'Paging missing from response to call to annotations');
      $this->assertArrayHasKey('data', $response['response'], 'Data missing from response to call to annotations');
      $data = $response['response']['data'];
      $this->assertInternalType('array', $data, 'Annotations data invalid. ' . var_export($data, true));
      $this->assertNotCount(0, $data, 'Annotations data absent. ' . var_export($data, true));
      foreach ($data as $annotation)
        $this->checkValidAnnotation($annotation);
      // only test a single project
      break;
    }
  }

  public function testTaxaSearch_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testTaxaSearch_get");

    $response = $this->callService('taxa/search');
    $this->assertEquals(400, $response['httpCode'],
          'Requesting taxa/search without search_term should be a bad request');
    $response = $this->callService('taxa/search', array(
      'searchQuery' => 'test'
    ));
    $this->assertEquals(400, $response['httpCode'],
          'Requesting taxa/search without taxon_list_id should be a bad request');
    $response = $this->callService('taxa/search', array(
      'searchQuery' => 'test',
      'taxon_list_id' => 1
    ));
    $this->assertResponseOk($response, '/taxa/search');
    $this->assertArrayHasKey('paging', $response['response'], 'Paging missing from response to call to taxa/search');
    $this->assertArrayHasKey('data', $response['response'], 'Data missing from response to call to taxa/search');
    $data = $response['response']['data'];
    $this->assertInternalType('array', $data, 'taxa/search data invalid.');
    $this->assertCount(2, $data, 'Taxa/search data wrong count returned.');
    $response = $this->callService('taxa/search', array(
      'searchQuery' => 'test taxon 2',
      'taxon_list_id' => 1
    ));
    $this->assertResponseOk($response, '/taxa/search');
    $this->assertArrayHasKey('paging', $response['response'], 'Paging missing from response to call to taxa/search');
    $this->assertArrayHasKey('data', $response['response'], 'Data missing from response to call to taxa/search');
    $data = $response['response']['data'];
    $this->assertInternalType('array', $data, 'taxa/search data invalid.');
    $this->assertCount(1, $data, 'Taxa/search data wrong count returned.');
    $response = $this->callService('taxa/search', array(
      'taxon_list_id' => 1
    ));
    $this->assertResponseOk($response, '/taxa/search');
    $data = $response['response']['data'];
    $this->assertCount(2, $data, 'Taxa/search data wrong count returned.');
    $response = $this->callService('taxa/search', array(
      'taxon_list_id' => 1,
      'min_taxon_rank_sort_order' => 300
    ));
    $this->assertResponseOk($response, '/taxa/search');
    $data = $response['response']['data'];
    $this->assertCount(1, $data, 'Taxa/search data wrong count returned.');
  }

  /**
   * Test for accessing the reports hierarchy.
   */
  public function testReportsHierarchy_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportsHierarchy_get");

    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports');
    // Check a folder that should definitely exist.
    $this->checkReportFolderInReponse($response['response'], 'library');
    // The demo report is not featured, so should not exist
    $this->assertFalse(array_key_exists('demo', $response['response']));

    // Repeat with an authMethod that allows access to non-featured reports. There
    // should be an additional featured folder at the top level with shortcuts
    // to favourite reports.
    $this->authMethod = 'hmacWebsite';
    $response = $this->callService("reports", array('proj_id' => $projDef['id']));
    $this->checkReportFolderInReponse($response['response'], 'featured');
    $this->checkReportInReponse($response['response'], 'demo');

    // now check some folder contents
    $this->authMethod = 'hmacClient';
    $response = $this->callService("reports/featured", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/featured');
    $this->checkReportInReponse($response['response'], 'library/occurrences/filterable_explore_list');
    $response = $this->callService("reports/library", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library');
    $this->checkReportFolderInReponse($response['response'], 'occurrences');
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $this->checkReportInReponse($response['response'], 'filterable_explore_list');
  }

  public function testReportParams_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportParams_get");

    // First grab a list of reports so we can use the links to get the correct params URL
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
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
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportColumns_get");

    // First grab a list of reports so we can use the links to get the correct columns URL
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $reportDef = $response['response']['filterable_explore_list'];
    $this->assertArrayHasKey('columns', $reportDef, 'Report response does not define columns');
    $this->assertArrayHasKey('href', $reportDef['columns'], 'Report columns missing href');
    // Now grab the columns URL output and check it
    $response = $this->callUrl($reportDef['columns']['href']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml/columns');
    $this->assertArrayHasKey('data', $response['response']);
    $this->assertArrayHasKey('occurrence_id', $response['response']['data']);
    $this->assertArrayHasKey('taxon', $response['response']['data']);
  }

  public function testReportOutput_get() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testReportOutput_get");

    // First grab a list of reports so we can use the links to get the correct columns URL
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']));
    $this->assertResponseOk($response, '/reports/library/occurrences');
    $reportDef = $response['response']['filterable_explore_list'];
    $this->assertArrayHasKey('href', $reportDef, 'Report response missing href');
    // Now grab the columns URL output and check it
    $response = $this->callUrl($reportDef['href']);
    $this->assertResponseOk($response, '/reports/library/occurrences/filterable_explore_list.xml');
    $this->assertArrayHasKey('data', $response['response']);
    $this->assertCount(1, $response['response']['data'], 'Report call returns incorrect record count');
    $this->assertEquals(1, $response['response']['data'][0]['occurrence_id'], 'Report call returns incorrect record');
  }

  public function testAcceptHeader() {
    Kohana::log('debug', "Running unit test, Rest_ControllerTest::testAcceptHeader");
    $projDef = self::$config['projects']['BRC1'];
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']), NULL, ['Accept: application/json']);
    $decoded = json_decode($response['response'], TRUE);
    $this->assertNotEquals(NULL, $decoded, 'JSON response could not be decoded: ' . $response['response']);
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(0, $response['curlErrno']);
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']), NULL, ['Accept: text/html']);
    $this->assertRegexp('/^<!DOCTYPE HTML>/', $response['response']);
    $this->assertRegexp('/<html>/', $response['response']);
    $this->assertRegexp('/<\/html>$/', $response['response']);
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(0, $response['curlErrno']);
    // try requesting an invalid content type as first preference - response should select the second.
    $response = $this->callService("reports/library/occurrences", array('proj_id' => $projDef['id']), NULL, ['Accept: image/png, application/json']);
    $decoded = json_decode($response['response'], TRUE);
    $this->assertNotEquals(NULL, $decoded, 'JSON response could not be decoded: ' . $response['response']);
    $this->assertEquals(200, $response['httpCode']);
    $this->assertEquals(0, $response['curlErrno']);
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

    $response = $this->callService($resource, $query, TRUE);
    $this->assertEquals(401, $response['httpCode'],
      "Incorrect secret or password passed to /$resource but request authorised. Http response $response[httpCode].");
    $this->assertEquals('Unauthorized', $response['response']['status'],
        "Incorrect secret or password passed to /$resource but data still returned. ".
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
    $response = $this->callService($resource, $query, TRUE);
    $this->assertEquals(401, $response['httpCode'],
        "Incorrect userId passed to /$resource but request authorised. Http response $response[httpCode].");
    $this->assertEquals('Unauthorized', $response['response']['status'],
        "Incorrect userId passed to /$resource but data still returned. " . var_export($response, true));

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
    $session = $this->initCurl($data['taxonObservation']['href']);
    $response = $this->getCurlResponse($session);
    $this->assertResponseOk($response, $data['taxonObservation']['href']);
    $this->checkValidTaxonObservation($response['response']);
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

  /**
   * Sets the http header before a request. This includes the Authorization string and can also include additional
   * header data when required.
   *
   * @param $session
   * @param $url
   */
  private function setRequestHeader($session, $url, $additionalRequestHeader) {
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
      case 'jwtUser':
        $authString = "Bearer " . self::$jwt;
        break;
      default:
        $this->fail("$this->authMethod test not implemented");
        break;
    }
    curl_setopt($session, CURLOPT_HTTPHEADER, array_merge(
      $additionalRequestHeader,
      array("Authorization: $authString")
    ));
  }

  /**
   * Set up a CURL session.
   */
  private function initCurl($url, $postData = NULL, $additionalRequestHeader = []) {
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    if ($postData) {
      if (is_array($postData)) {
        $postData = json_encode($postData);
        $additionalRequestHeader[] = 'Content-Type: application/json';
        $additionalRequestHeader[] = 'Content-Length: ' . strlen($postData);
        kohana::log('debug', 'POST: ' . $postData);
      }
      curl_setopt ($session, CURLOPT_POST, TRUE);
      curl_setopt ($session, CURLOPT_POSTFIELDS, $postData);
    }
    $this->setRequestHeader($session, $url, $additionalRequestHeader);
    return $session;
  }

  /**
   * Perform a CURL request and get response data.
   */
  private function getCurlResponse($session, $additionalRequestHeader = []) {
    // Do the POST.
    $response = curl_exec($session);
    $headerSize = curl_getinfo($session, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    // Auto decode the JSON, unless the test is checking the Accept request
    // header in which case format could be something else.
    if (empty($additionalRequestHeader)
        || strpos(implode(',', $additionalRequestHeader), 'Accept:') === FALSE) {
      $decoded = json_decode($body, TRUE);
      $this->assertNotEquals(NULL, $decoded, 'JSON response could not be decoded: ' . $response);
      $body = $decoded;
    }
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    $message = curl_error($session);
    return [
      'errorMessage' => $message ? $message : 'curl ok',
      'curlErrno' => $curlErrno,
      'httpCode' => $httpCode,
      'response' => $body,
      'headers' => $header,
    ];
  }

  private function callUrl($url, $postData = NULL, $additionalRequestHeader = []) {
    $session = $this->initCurl($url, $postData, $additionalRequestHeader);
    $response = $this->getCurlResponse($session, $additionalRequestHeader);
    curl_close($session);
    return $response;
  }

  /**
   * A generic method to call the REST Api's web services.
   *
   * @param $method
   * @param mixed|FALSE $query
   * @param string $postData
   * @return array
   */
  private function callService($method, $query = FALSE, $postData = NULL, $additionalRequestHeader = []) {
    $url = url::base(true) . "services/rest/$method";
    if ($query)
      $url .= '?' . http_build_query($query);
    return $this->callUrl($url, $postData, $additionalRequestHeader);
  }
}
