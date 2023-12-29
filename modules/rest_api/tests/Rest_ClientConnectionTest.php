<?php

use PHPUnit\DbUnit\DataSet\CompositeDataSet as DbUDataSetCompositeDataSet;
use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

/**
 * Unit test class for the REST api controller.
 *
 * @todo Test sharing mode on project filters is respected.
 *
 */
class Rest_ClientConnectionTest extends Indicia_DatabaseTestCase {

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

  private static $auth;

  private static $db;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    self::$auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    self::$auth['write_tokens']['persist_auth'] = TRUE;
    self::$db = new Database();
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

  public function testCreateClientAndConnection() {
    $clientData = [
      'rest_api_client:title' => 'Test client',
      'rest_api_client:website_id' => 1,
      'rest_api_client:description' => 'Test description',
      'rest_api_client:username' => 'testconnection',
      'rest_api_client:secret' => 'mysecret',
      'rest_api_client:public_key' => self::$publicKey,
    ];
    $s = submission_builder::build_submission($clientData, ['model' => 'rest_api_client']);
    $r = data_entry_helper::forward_post_to('rest_api_client', $s, self::$auth['write_tokens'] + ['secret2' => 'mysecret']);
    $this->assertTrue(isset($r['success']), 'Submitting a rest_api_client did not return success response');

    $clientId = $r['success'];
    $saved = self::$db->query('SELECT * FROM rest_api_clients WHERE id=' . $clientId)->current();
    $this->assertNotEquals($saved->secret, $clientData['rest_api_client:secret'], 'Saved secret has not been hashed.');
    $this->assertTrue(password_verify($clientData['rest_api_client:secret'], $saved->secret), 'Saved password hash does not verify against the supplied secret');
    $connectionData = [
      'rest_api_client_connection:title' => 'Test connection',
      'rest_api_client_connection:description' => 'Test description',
      'rest_api_client_connection:rest_api_client_id' => $clientId,
      'rest_api_client_connection:es_sensitivity_blur' => 'B',
      'rest_api_client_connection:read_only' => 'f',
    ];
    $s = submission_builder::build_submission($connectionData, ['model' => 'rest_api_client_connection']);
    $r = data_entry_helper::forward_post_to('rest_api_client_connection', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a rest_api_client_connection did not return success response');
  }

}
