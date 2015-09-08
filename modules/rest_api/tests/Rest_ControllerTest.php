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

  private function callService($method, $userId, $sharedSecret) {
    $url = url::base(true) . "/services/rest/$method";
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
