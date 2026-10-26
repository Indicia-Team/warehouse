<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../helpers/logRedaction.php';

/**
 * Verify credential removal without HTTP, database, or framework services.
 */
class LogRedactionTest extends TestCase {

  /**
   * Keep useful diagnostics and leave the actual request unmodified.
   */
  public function testParametersAreRedactedRecursively() {
    $parameters = [
      'auth_token' => 'secret-token',
      'nonce' => 'secret-nonce',
      'query' => 'butterfly',
      'user_id' => 12,
      'nested' => ['AUTH_TOKEN' => 'nested-token', 'id' => 5],
      'object' => (object) ['nonce' => 'nested-nonce', 'count' => 3],
      'params' => '{"auth_token":"json-token","survey_id":7}',
      'submission' => '{"fields":{"nonce":"nested-json-nonce","id":8}}',
    ];
    $result = logRedaction::parameters($parameters);
    $this->assertSame('secret-token', $parameters['auth_token']);
    $this->assertSame('nested-nonce', $parameters['object']->nonce);
    $this->assertSame('butterfly', $result['query']);
    $this->assertSame(12, $result['user_id']);
    $this->assertSame(['id' => 5], $result['nested']);
    $this->assertEquals((object) ['count' => 3], $result['object']);
    $this->assertSame('{"survey_id":7}', $result['params']);
    $this->assertSame('{"fields":{"id":8}}', $result['submission']);
    $this->assertStringNotContainsString('token', json_encode($result));
    $this->assertStringNotContainsString('nonce', json_encode($result));
  }

  /**
   * Remove raw-body credentials without changing non-secret diagnostic values.
   *
   * @param string $body
   *   Raw body containing credentials or unsupported data.
   * @param string $contentType
   *   Declared request content type, if available.
   * @param string $expected
   *   Sanitised body or explicit omission marker.
   *
   * @dataProvider requestBodies
   */
  public function testRequestBodies(string $body, string $contentType, string $expected) {
    $this->assertSame($expected, logRedaction::body($body, [], $contentType));
  }

  /**
   * Cover raw JSON, forms, malformed input, and unsupported media.
   *
   * @return array
   *   Named body, content type, and expected output cases.
   */
  public static function requestBodies(): array {
    return [
      'JSON object' => ['{"auth_token":"secret","nonce":"secret","id":4}', 'application/json', '{"id":4}'],
      'JSON array' => ['[{"auth_token":"secret","id":4}]', 'application/json', '[{"id":4}]'],
      'JSON type suffix' => ['{"nonce":"secret","id":4}', 'application/problem+json', '{"id":4}'],
      'JSON with charset' => ['{"nonce":"secret","id":4}', 'application/json; charset=utf-8', '{"id":4}'],
      'empty JSON object' => ['{"nonce":"secret"}', '', '{}'],
      'empty JSON array' => ['[]', '', '[]'],
      'encoded credentials and repeated keys' => ['%61uth_token=first&auth_token=second&nonce[]=secret&q=red+fox', '', 'q=red%20fox'],
      'nested form parameters' => ['params[auth_token]=secret&params[id]=4', 'application/x-www-form-urlencoded', 'params%5Bid%5D=4'],
      'JSON inside form field' => ['params=%7B%22nonce%22%3A%22secret%22%2C%22id%22%3A4%7D', '', 'params=%7B%22id%22%3A4%7D'],
      'form with charset' => ['nonce=secret&id=4', 'application/x-www-form-urlencoded; charset=UTF-8', 'id=4'],
      'invalid JSON' => ['{"auth_token":"secret"', 'application/json', '[request body omitted: invalid or unsupported JSON]'],
      'invalid detected JSON' => ['{"nonce":"secret"=', '', '[request body omitted: invalid or unsupported JSON]'],
      'unsupported multipart raw body' => ["--boundary\r\nnonce=secret", 'multipart/form-data; boundary=boundary', '[request body omitted: unsupported content type]'],
      'unsupported plaintext' => ['secret', 'text/plain', '[request body omitted: unsupported content type]'],
      'JSON scalar' => ['"secret"', 'application/json', '[request body omitted: invalid or unsupported JSON]'],
      'empty body' => ['', '', ''],
    ];
  }

  /**
   * Preserve the request path and non-secret query parameters in error logs.
   */
  public function testRequestUri() {
    $this->assertSame('/services/data/sample', logRedaction::uri('/services/data/sample'));
    $this->assertSame(
      '/services/data/sample?id=4',
      logRedaction::uri('/services/data/sample?nonce=secret&auth_token=secret&id=4')
    );
  }

  /**
   * Apply existing request-log exclusions as well as credential redaction.
   */
  public function testRequestLoggerStructuredParameters() {
    $this->loadRequestLogger();
    $method = new ReflectionMethod(request_logging::class, 'stripUnloggedParams');
    $method->setAccessible(TRUE);
    $this->assertSame(['id' => 4, 'options' => ['mode' => 'trial']], $method->invoke(NULL, [
      'auth_token' => 'secret',
      'nonce' => 'secret',
      'paramsFormExcludes' => 'unused',
      'callback' => 'unused',
      'reportSource' => 'local',
      'mode' => 'json',
      'id' => 4,
      'options' => ['mode' => 'trial', 'nonce' => 'nested-secret'],
    ]));
  }

  /**
   * Verify the exact JSON value stored after POST redaction.
   *
   * @param array|object|string $post
   *   Parsed POST parameters, a raw body, or replacement response data.
   * @param string $expected
   *   Expected JSON-column value, preserving raw strings versus objects.
   *
   * @dataProvider storedPostBodies
   */
  public function testRequestLoggerPostEncoding($post, string $expected) {
    $this->loadRequestLogger();
    $method = new ReflectionMethod(request_logging::class, 'preparePostForLogging');
    $method->setAccessible(TRUE);
    $this->assertSame($expected, $method->invoke(NULL, $post));
  }

  /**
   * Ensure parsed, raw, and override data all pass through redaction.
   *
   * @return array
   *   Named POST data and expected stored JSON cases.
   */
  public static function storedPostBodies(): array {
    return [
      'parsed form' => [['nonce' => 'secret', 'auth_token' => 'secret', 'mode' => 'json', 'id' => 4], '{"id":4}'],
      'object override' => [(object) ['auth_token' => 'secret', 'id' => 4], '{"id":4}'],
      'raw JSON or string override' => ['{"nonce":"secret","id":4}', '"{\"id\":4}"'],
      'raw form' => ['nonce=secret&auth_token=secret&mode=json&id=4', '"id=4"'],
      'ordinary override' => [['success' => 4], '{"success":4}'],
      'list override' => [[1, 2], '[1,2]'],
    ];
  }

  /**
   * Load the module helper without bootstrapping the warehouse application.
   */
  private function loadRequestLogger(): void {
    if (!defined('SYSPATH')) {
      define('SYSPATH', dirname(__DIR__, 3) . '/system/');
    }
    require_once __DIR__ . '/../../../modules/request_logging/helpers/request_logging.php';
  }

}
