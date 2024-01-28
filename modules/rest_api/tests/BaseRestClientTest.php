<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

/**
 * Base class for tests against REST Clients.
 */
class BaseRestClientTest extends Indicia_DatabaseTestCase {

  /**
   * Private key for use in tests.
   *
   * @var string
   */
  protected static $privateKey = <<<KEY
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

  /**
   * Incorrect private key for use in tests.
   *
   * @var string
   */
  protected static $wrongPrivateKey = <<<KEY
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

  /**
   * Punlic key for use in tests.
   *
   * @var string
   */
  protected static $publicKey = <<<KEY
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8kGa1pSjbSYZVebtTRBLxBz5H
4i2p/llLCrEeQhta5kaQu/RnvuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t
0tyazyZ8JXw+KgXTxldMPEL95+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4
ehde/zUxo6UvS7UrBQIDAQAB
-----END PUBLIC KEY-----
KEY;

  protected static $auth;

  protected $authMethod = 'hmacClient';
  protected static $clientUserId;
  protected static $clientSecret = 'mysecret';
  protected static $config;
  protected static $db;
  // Access tokens.
  protected static $jwt;

  protected static $userId = 1;
  protected static $userPassword = 'password';
  protected static $websiteId = 1;
  protected static $websitePassword = 'password';

  public function getDataSet() {
    $ds1 = new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  /**
   * Sets the http header before a request.
   *
   * This includes the Authorization string and can also include additional
   * header data when required.
   *
   * @param \CurlHandle $session
   *   CURL session.
   * @param string $url
   *   URL which is required to generate HMAC.
   * @param array $additionalRequestHeader
   *   Additional headers to add.
   */
  protected function setRequestHeader($session, $url, array $additionalRequestHeader = []) {
    switch ($this->authMethod) {
      case 'hmacUser':
        $user = self::$userId;
        $website = self::$websiteId;
        $hmac = hash_hmac('sha1', $url, self::$userPassword);
        $authString = "USER_ID:$user:WEBSITE_ID:$website:HMAC:$hmac";
        break;

      case 'hmacClient':
        $user = self::$clientUserId;
        $hmac = hash_hmac('sha1', $url, self::$config['shared_secret']);
        $authString = "USER:$user:HMAC:$hmac";
        break;

      case 'hmacWebsite':
        $user = self::$websiteId;
        $hmac = hash_hmac('sha1', $url, self::$websitePassword);
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
        $password = self::$clientSecret;
        $authString = "USER:$user:SECRET:$password";
        break;

      case 'directWebsite':
        $user = self::$websiteId;
        $password = self::$websitePassword;
        $authString = "WEBSITE_ID:$user:SECRET:$password";
        break;

      case 'jwtUser':
        $authString = "Bearer " . self::$jwt;
        break;

      case 'jwtClient':
        $authString = "Bearer " . self::$jwt;
        break;

      case 'none':
        break;

      default:
        $this->fail("$this->authMethod auth method not implemented");
        break;
    }
    if (isset($authString)) {
      $additionalRequestHeader[] = "Authorization: $authString";
    }
    if (count($additionalRequestHeader) > 0) {
      curl_setopt($session, CURLOPT_HTTPHEADER, $additionalRequestHeader);
    }
  }

  /**
   * Parse a response header string to a key/value associative array.
   *
   * @param string $string
   *   Headers as a string.
   *
   * @return array
   *   Headers as key/value pairs.
   */
  protected function parseHeaders($string) {
    $rows = explode("\n", trim($string));
    // Skip response code at the top.
    array_shift($rows);
    $array = [];
    foreach ($rows as $row) {
      [$key, $value] = explode(': ', $row, 2);
      $array[$key] = trim($value);
    }
    return $array;
  }

  /**
   * Set up a CURL session.
   */
  protected function initCurl($url, $postData = NULL, $additionalRequestHeader = [], $customMethod = NULL, $files = FALSE) {
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HEADER, TRUE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    if ($customMethod) {
      curl_setopt($session, CURLOPT_CUSTOMREQUEST, $customMethod);
    }
    if ($postData) {
      if (is_array($postData) && !$files) {
        $postData = json_encode($postData);
        $additionalRequestHeader[] = 'Content-Type: application/json';
        $additionalRequestHeader[] = 'Content-Length: ' . strlen($postData);
      }
      curl_setopt($session, CURLOPT_POST, TRUE);
      curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
    }
    $this->setRequestHeader($session, $url, $additionalRequestHeader);
    return $session;
  }

  /**
   * Perform a CURL request and get response data.
   */
  protected function getCurlResponse($session, $additionalRequestHeader = []) {
    // Do the POST.
    $response = curl_exec($session);
    $headerSize = curl_getinfo($session, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    // Auto decode the JSON, unless the test is checking the Accept request
    // header in which case format could be something else.
    if (!empty($body) && (empty($additionalRequestHeader) || strpos(implode(',', $additionalRequestHeader), 'Accept:') === FALSE)) {
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

  protected function callUrl($url, $postData = NULL, $additionalRequestHeader = [], $customMethod = NULL, $files = FALSE) {
    $session = $this->initCurl($url, $postData, $additionalRequestHeader, $customMethod, $files);
    $response = $this->getCurlResponse($session, $additionalRequestHeader);
    curl_close($session);
    return $response;
  }

  /**
   * A generic method to call the REST Api's web services.
   *
   * @param string $resource
   *   REST API resouce.
   * @param mixed|false $query
   *   Additional query string parameters.
   * @param string $postData
   *   Additional data for POST.
   * @param array $additionalRequestHeader
   *   Additional request headers.
   * @param string $customMethod
   *   HTTP method if not GET.
   * @param array|bool $files
   *   Files to include if relevant.
   *
   * @return array
   *   Service response.
   */
  protected function callService($resource, $query = FALSE, $postData = NULL, array $additionalRequestHeader = [], $customMethod = NULL, $files = FALSE) {
    $url = url::base(TRUE) . "services/rest/$resource";
    if ($query) {
      $url .= '?' . http_build_query($query);
    }
    return $this->callUrl($url, $postData, $additionalRequestHeader, $customMethod, $files);
  }

}
