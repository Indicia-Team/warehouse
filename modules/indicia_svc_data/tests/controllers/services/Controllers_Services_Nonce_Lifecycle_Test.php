<?php

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for authentication, nonce lifecycle, and bulk-delete ordering.
 *
 * Run standalone with --no-configuration. Set INDICIA_TEST_PG_DSN to enable
 * bulk-delete SQL checks against temporary tables, never warehouse fixtures.
 */
class Controllers_Services_Nonce_Lifecycle_Test extends TestCase {

  /**
   * Run a scenario with real controllers/cache in an isolated PHP process.
   *
   * @param array $changes
   *   Request, cache, or operation overrides for the default signed write call.
   *
   * @return array
   *   Authentication state, cache operations, response, and optional SQL results.
   */
  private function runScenario(array $changes = []): array {
    $nonce = str_repeat('a', 40);
    $scenario = array_replace([
      'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2'],
      'entries' => [[$nonce, '1', 'write']],
    ], $changes);
    $directory = sys_get_temp_dir() . '/indicia-auth-' . bin2hex(random_bytes(12));
    $this->assertTrue(mkdir($directory));
    $scenario['directory'] = $directory;
    try {
      $process = proc_open(
        [PHP_BINARY, dirname(__DIR__, 2) . '/fixtures/authentication.php'],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
        $pipes
      );
      $this->assertIsResource($process);
      fwrite($pipes[0], json_encode($scenario, JSON_THROW_ON_ERROR));
      fclose($pipes[0]);
      $output = stream_get_contents($pipes[1]);
      $errors = stream_get_contents($pipes[2]);
      fclose($pipes[1]);
      fclose($pipes[2]);
      $this->assertSame(0, proc_close($process), $errors . $output);
      $this->assertSame('', $errors);
      return json_decode($output, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    finally {
      foreach (glob($directory . '/*') as $file) {
        unlink($file);
      }
      rmdir($directory);
    }
  }

  /**
   * Reject malformed input before cache globbing or session fallback.
   *
   * @param array $request
   *   GET/POST credentials and controls to reject.
   *
   * @dataProvider invalidRequests
   */
  public function testInvalidRequests(array $request): void {
    $result = $this->runScenario($request);
    $this->assertSame('AuthenticationError', $result['exception']);
    $this->assertSame([], $result['cache_calls']);
    $this->assertSame(0, $result['session_calls']);
    $this->assertNull($result['context']);
  }

  /**
   * Cover arrays, cache glob/path characters, partial and conflicting credentials.
   */
  public static function invalidRequests(): array {
    $nonce = str_repeat('a', 40);
    $token = sha1("$nonce:password:2") . ':2';
    $valid = ['nonce' => $nonce, 'auth_token' => $token];
    $cases = [
      'nonce only' => ['post' => ['nonce' => $nonce]],
      'token only' => ['post' => ['auth_token' => $token]],
      'array nonce' => ['post' => array_replace($valid, ['nonce' => [$nonce]])],
      'array token' => ['post' => array_replace($valid, ['auth_token' => [$token]])],
      'conflicting nonce' => ['get' => ['nonce' => str_repeat('b', 40)]],
      'conflicting token' => ['get' => ['auth_token' => str_repeat('b', 40)]],
      'conflicting persistence' => ['post' => $valid + ['persist_auth' => 'true'], 'get' => ['persist_auth' => 'false']],
      'conflicting renewal' => ['post' => $valid + ['reset_timeout' => 'true'], 'get' => ['reset_timeout' => 'false']],
      'array persistence' => ['post' => $valid + ['persist_auth' => ['true']]],
      'invalid renewal' => ['post' => $valid + ['reset_timeout' => 'anything']],
      'zero signed identity' => ['post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:0") . ':0']],
      'overflow signed identity' => ['post' => ['nonce' => $nonce, 'auth_token' => str_repeat('b', 40) . ':' . PHP_INT_MAX . '0']],
    ];
    foreach (['*', '?', '[a-z]', '../' . $nonce, $nonce . "\n", '', str_repeat('a', 41)] as $invalid) {
      $cases['invalid nonce ' . bin2hex($invalid)] = ['post' => array_replace($valid, ['nonce' => $invalid])];
    }
    foreach (['*', str_repeat('a', 39), $token . "\n", $token . ':3'] as $invalid) {
      $cases['invalid token ' . bin2hex($invalid)] = ['post' => array_replace($valid, ['auth_token' => $invalid])];
    }
    return array_map(static function ($request) {
      return [$request];
    }, $cases);
  }

  /**
   * Missing/expired/invalid cache values must not switch to private-key auth.
   *
   * @param mixed $identity
   *   Stored nonce identity, including invalid scalar and structured values.
   * @param int $lifetime
   *   Negative lifetime produces an expired filename with no usable cache value.
   *
   * @dataProvider invalidCacheIdentities
   */
  public function testInvalidCacheIdentity($identity, int $lifetime): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:warehouse-key"), 'reset_timeout' => 'true'],
      'entries' => [[$nonce, $identity, 'write', $lifetime]],
    ]);
    $this->assertSame('AuthenticationError', $result['exception']);
    $this->assertNull($result['context']);
    $this->assertSame(0, $result['session_calls']);
  }

  /**
   * Include a genuinely expired positive website nonce and invalid cache data.
   */
  public static function invalidCacheIdentities(): array {
    $cases = [['1', -10]];
    foreach ([NULL, FALSE, TRUE, 0, '0', '', 'invalid', 1.5, ['1'], '1.0', '1e0', PHP_INT_MAX . '0', PHP_INT_MIN] as $value) {
      $cases[] = [$value, 3600];
    }
    return $cases;
  }

  /**
   * Preserve signed, legacy, GET, duplicate-source, and warehouse credentials.
   *
   * @param array $changes
   *   Valid credential/domain variations.
   * @param int $websiteId
   *   Expected authenticated website, with zero representing the warehouse.
   * @param int $userId
   *   Expected token identity; -1 represents a legacy website-only token.
   *
   * @dataProvider validRequests
   */
  public function testValidAuthentication(array $changes, int $websiteId, int $userId): void {
    $result = $this->runScenario($changes);
    $this->assertNull($result['exception']);
    $this->assertSame($websiteId, $result['website_id']);
    $this->assertSame($userId, $result['auth_user_id']);
    $this->assertSame(0, $result['session_calls']);
    $this->assertNotNull($result['context']);
    $this->assertSame($userId, $result['remote_auth_user_id']);
    $this->assertSame($result['user_id'], $result['remote_user_id']);
    $this->assertSame($websiteId === 0, $result['in_warehouse']);
    $this->assertSame($websiteId === 0, $result['core_admin']);
  }

  /**
   * Both integer and POST-issued string cache identities remain supported.
   */
  public static function validRequests(): array {
    $nonce = str_repeat('a', 40);
    $signed = ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2'];
    return [
      'signed website' => [[], 1, 2],
      'integer cache identity' => [['entries' => [[$nonce, 1, 'write']]], 1, 2],
      'legacy website' => [['post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password")]], 1, -1],
      'read GET' => [['post' => [], 'get' => $signed, 'mode' => 'read', 'entries' => [[$nonce, 1, 'read']]], 1, 2],
      'identical duplicates' => [['post' => $signed, 'get' => $signed], 1, 2],
      'warehouse nonce' => [['post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:warehouse-key")], 'entries' => [[$nonce, '-3', 'write']]], 0, 3],
      'warehouse read nonce' => [['post' => [], 'get' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:warehouse-key")], 'entries' => [[$nonce, '-3', 'read']], 'mode' => 'read'], 0, 3],
    ];
  }

  /**
   * A missing nonce, wrong mode, wrong password, or removed identity is denied.
   *
   * @param array $changes
   *   Invalid cache or token variations.
   *
   * @dataProvider unusableCredentials
   */
  public function testUnusableCredentials(array $changes): void {
    $result = $this->runScenario($changes);
    $this->assertSame('AuthenticationError', $result['exception']);
    $this->assertNull($result['context']);
  }

  /**
   * Exercise server-side lookup failures separately from input-format rejection.
   */
  public static function unusableCredentials(): array {
    $nonce = str_repeat('a', 40);
    return [
      'missing nonce' => [['entries' => []]],
      'read token cannot write' => [['entries' => [[$nonce, 1, 'read']]]],
      'wrong password' => [['post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:wrong")]]],
      'missing website' => [['entries' => [[$nonce, 99, 'write']]]],
      'missing warehouse user' => [['entries' => [[$nonce, -99, 'write']], 'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:warehouse-key")]]],
    ];
  }

  /**
   * Cookies and a logged-in or remembered session cannot replace explicit tokens.
   *
   * @param string $mode
   *   Read or write service authentication mode.
   * @param bool $loggedIn
   *   Whether Auth would accept a session immediately or via auto_login().
   *
   * @dataProvider sessionOnlyRequests
   */
  public function testSessionCannotReplaceCredentials(string $mode, bool $loggedIn): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'post' => [],
      'cookie' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2'],
      'request' => ['nonce' => $nonce, 'user_id' => 99],
      'session_logged_in' => $loggedIn,
      'mode' => $mode,
      'delete' => TRUE,
    ]);
    $this->assertSame('AuthenticationError', $result['exception']);
    $this->assertSame(0, $result['session_calls']);
    $this->assertSame([], $result['cache_calls']);
    $this->assertNull($result['context']);
    $this->assertNull($result['verified_user_id']);
    $this->assertFalse($result['in_warehouse']);
    $this->assertFalse($result['core_admin']);
  }

  /**
   * Neither read/write nor remember-me authentication may bypass nonce checking.
   */
  public static function sessionOnlyRequests(): array {
    return [['read', TRUE], ['write', TRUE], ['read', FALSE], ['write', FALSE]];
  }

  /**
   * Cookie-only media and bulk-delete calls must return 403 before side effects.
   *
   * @param string $operation
   *   Real controller endpoint to exercise without explicit credentials.
   *
   * @dataProvider sessionOnlyOperations
   */
  public function testSessionOnlyEndpointDenied(string $operation): void {
    $result = $this->runScenario([
      'post' => ['import_guid' => 'IMPORT-1'],
      $operation => TRUE,
    ]);
    $this->assertNull($result['exception']);
    $this->assertSame(403, $result['status']);
    $this->assertArrayHasKey('error', json_decode($result['output'], TRUE, 512, JSON_THROW_ON_ERROR));
    $this->assertSame(0, $result['database_calls']);
    $this->assertSame(0, $result['session_calls']);
    $this->assertSame([], $result['cache_calls']);
    $this->assertNull($result['verified_user_id']);
  }

  /**
   * Exercise both ordinary uploads and privileged import deletion.
   */
  public static function sessionOnlyOperations(): array {
    return [['bulk'], ['media']];
  }

  /**
   * Every successful utility consumes its nonce before a replay can mutate data.
   *
   * @param string $action
   *   Utility method, prefixed with rules: for the rule-import controller.
   * @param array $parameters
   *   Valid request parameters for the selected action.
   * @param bool $postgres
   *   Whether real PostgreSQL escaping requires an optional test connection.
   *
   * @dataProvider utilityActions
   */
  public function testUtilityNonceConsumption(string $action, array $parameters, bool $postgres): void {
    if ($postgres && !getenv('INDICIA_TEST_PG_DSN')) {
      $this->markTestSkipped('Set INDICIA_TEST_PG_DSN for PostgreSQL escaping tests.');
    }
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'utility_action' => $action,
      'post' => $parameters + ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2'],
      'replay' => TRUE,
    ]);
    $this->assertNull($result['exception'], $result['output']);
    $this->assertNull($result['context']);
    $this->assertSame([], $result['expiries']);
    $this->assertStringNotContainsString('"error"', $result['output']);
    $this->assertNotSame('', $result['output']);
    $this->assertSame(0, $result['replay']['additional_effects']);
    $this->assertTrue(
      $result['replay']['exception'] === 'AuthenticationError'
        || strpos($result['replay']['output'], '"error"') !== FALSE,
      json_encode($result['replay'])
    );
    $deletes = array_values(array_filter($result['cache_calls'], static function ($call) {
      return $call[0] === 'delete';
    }));
    $this->assertSame([['delete', $nonce]], $deletes);
  }

  /**
   * Cover each utility success path, including dry-run and precheck responses.
   *
   * Bulk deletion has separate tests exercising its actual temporary-table SQL.
   */
  public static function utilityActions(): array {
    $verification = ['occurrence:record_status' => 'V'];
    $report = ['report' => 'test', 'params' => '{}'];
    $move = [
      'occurrence:ids' => '1', 'precheck' => 'f',
      'datasetMappings' => '[{"src":{"survey_id":1,"website_id":1},"dest":{"survey_id":2,"website_id":1}}]',
    ];
    $rules = ['uploadedFile' => 'rules.csv', 'taxon_list_id' => 1, 'custom_verification_ruleset_id' => 1];
    return [
      ['single_verify', $verification + ['occurrence:id' => '1'], FALSE],
      ['list_verify', $verification + ['occurrence:ids' => '1'], FALSE],
      ['list_redet', ['occurrence:ids' => '1', 'occurrence:taxa_taxon_list_id' => '1', 'occurrence:determiner_id' => 2], FALSE],
      ['single_verify_sample', ['sample:id' => '1', 'sample:record_status' => 'V'], FALSE],
      ['bulk_verify', $report, FALSE],
      ['bulk_verify', $report + ['dryrun' => 'true'], FALSE],
      ['bulk_verify_samples', $report, FALSE],
      ['force_linked_location', ['occurrence_ids' => '1', 'location_id' => '1', 'location_type_id' => '1'], FALSE],
      ['bulk_move', $move, FALSE],
      ['bulk_move', array_replace($move, ['precheck' => 't']), FALSE],
      ['bulk_edit', ['occurrence:ids' => '1', 'updates' => '{}'], TRUE],
      ['test_action', [], TRUE],
      ['rules:upload_file', [], FALSE],
      ['rules:validate_structure', $rules, FALSE],
      ['rules:validate_contents', $rules, FALSE],
      ['rules:import_contents', $rules, FALSE],
    ];
  }

  /**
   * Verification and multi-step rule imports may explicitly retain credentials.
   *
   * @param string $action
   *   Utility action under test.
   * @param array $parameters
   *   Valid request parameters.
   *
   * @dataProvider reusableUtilityActions
   */
  public function testUtilityExplicitPersistence(string $action, array $parameters): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'utility_action' => $action,
      'post' => $parameters + ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2', 'persist_auth' => 'true'],
      'replay' => TRUE,
    ]);
    $this->assertNull($result['exception'], $result['output']);
    $this->assertNotNull($result['context']);
    $this->assertNotEmpty($result['expiries']);
    $this->assertNull($result['replay']['exception']);
    $this->assertStringNotContainsString('"error"', $result['replay']['output']);
    $this->assertGreaterThan(0, $result['replay']['additional_effects']);
  }

  /**
   * Exercise real updates and rule insertion rather than empty/no-op requests.
   */
  public static function reusableUtilityActions(): array {
    return [
      ['single_verify', ['occurrence:id' => '1', 'occurrence:record_status' => 'V']],
      ['rules:import_contents', ['uploadedFile' => 'rules.csv', 'taxon_list_id' => 1, 'custom_verification_ruleset_id' => 1]],
    ];
  }

  /**
   * Cleanup failure must produce only an error, never a success plus an error.
   *
   * @param string $action
   *   Utility action under test.
   * @param array $parameters
   *   Valid request parameters.
   *
   * @dataProvider reusableUtilityActions
   */
  public function testUtilityCleanupFailure(string $action, array $parameters): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'utility_action' => $action,
      'post' => $parameters + ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2'],
      'fail_delete' => TRUE,
    ]);
    $this->assertNull($result['exception']);
    $this->assertNotSame(200, $result['status']);
    $response = json_decode($result['output'], TRUE, 512, JSON_THROW_ON_ERROR);
    $this->assertArrayHasKey('error', $response);
    $this->assertNotEmpty($result['expiries']);
  }

  /**
   * An authorization rejection must not consume credentials needed for a retry.
   */
  public function testRejectedUtilityRetainsNonce(): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'utility_action' => 'single_verify',
      'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2', 'occurrence:id' => '1', 'occurrence:record_status' => 'V'],
      'deny_access' => TRUE,
    ]);
    $this->assertSame(403, $result['status']);
    $this->assertArrayHasKey('error', json_decode($result['output'], TRUE, 512, JSON_THROW_ON_ERROR));
    $this->assertNotEmpty($result['expiries']);
    $this->assertSame(0, $result['effects']);
  }

  /**
   * Warehouse nonce identity must override session identity and token suffixes.
   *
   * @param int $userId
   *   Identity bound to the negative nonce cache entry.
   * @param bool $signed
   *   Whether the token also has a deliberately different user ID suffix.
   *
   * @dataProvider warehouseIdentities
   */
  public function testWarehouseIdentityContext(int $userId, bool $signed): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'post' => [
        'nonce' => $nonce,
        'auth_token' => $signed ? sha1("$nonce:warehouse-key:99") . ':99' : sha1("$nonce:warehouse-key"),
        'user_id' => 99,
      ],
      'entries' => [[$nonce, -$userId, 'write']],
    ]);
    $this->assertNull($result['exception']);
    $this->assertSame(0, $result['session_calls']);
    $this->assertTrue($result['in_warehouse']);
    $this->assertSame(0, $result['website_id']);
    foreach (['user_id', 'auth_user_id', 'verified_user_id', 'remote_user_id', 'remote_auth_user_id'] as $key) {
      $this->assertSame($userId, $result[$key], $key);
    }
    $this->assertSame($userId === 3, $result['core_admin']);
    $this->assertSame($userId === 3 ? [] : [1, 2], $result['user_websites']);
  }

  /**
   * Cover core admins and membership-scoped users with both token formats.
   */
  public static function warehouseIdentities(): array {
    return [[3, FALSE], [3, TRUE], [4, FALSE], [4, TRUE]];
  }

  /**
   * Re-authentication cannot retain warehouse privileges or stale audit IDs.
   *
   * @param bool $valid
   *   Whether the second request has valid remote website credentials.
   *
   * @dataProvider storageFailures
   */
  public function testAuthenticationContextIsReset(bool $valid): void {
    $nonce = str_repeat('a', 40);
    $other = str_repeat('b', 40);
    $result = $this->runScenario([
      'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:warehouse-key")],
      'entries' => [[$nonce, -3, 'write'], [$other, 1, 'write']],
      'reauthenticate' => ['post' => $valid ? ['nonce' => $other, 'auth_token' => sha1("$other:password:2") . ':2'] : []],
    ]);
    $this->assertSame($valid ? NULL : 'AuthenticationError', $result['exception']);
    $this->assertFalse($result['in_warehouse']);
    $this->assertFalse($result['core_admin']);
    $this->assertSame([], $result['user_websites']);
    $this->assertSame($valid ? 1 : NULL, $result['website_id']);
    foreach (['user_id', 'auth_user_id', 'verified_user_id', 'remote_user_id', 'remote_auth_user_id'] as $key) {
      $this->assertSame($valid ? 2 : NULL, $result[$key], $key);
    }
    $this->assertSame(0, $result['session_calls']);
  }

  /**
   * Renewal must use nonce_life, not the shorter default cache lifetime.
   */
  public function testRenewalLifetime(): void {
    $nonce = str_repeat('a', 40);
    $start = time();
    $result = $this->runScenario([
      'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2', 'reset_timeout' => 'true'],
    ]);
    $this->assertNull($result['exception']);
    $sets = array_values(array_filter($result['cache_calls'], static function ($call) {
      return $call[0] === 'set';
    }));
    $this->assertSame([['set', $nonce, 1, ['write'], 7200]], $sets);
    $this->assertGreaterThanOrEqual($start + 7200, max($result['expiries']));
    $this->assertLessThanOrEqual(time() + 7200, max($result['expiries']));
  }

  /**
   * Cleanup uses the authenticated nonce even if REQUEST changes after auth.
   */
  public function testCleanupUsesAuthenticatedNonce(): void {
    $nonce = str_repeat('a', 40);
    $other = str_repeat('b', 40);
    $result = $this->runScenario([
      'entries' => [[$nonce, 1, 'write'], [$other, 1, 'write']],
      'after_request' => ['nonce' => $other, 'persist_auth' => 'true'],
      'delete' => TRUE,
    ]);
    $deletes = array_values(array_filter($result['cache_calls'], static function ($call) {
      return $call[0] === 'delete';
    }));
    $this->assertNull($result['exception']);
    $this->assertSame([['delete', $nonce]], $deletes);
    $this->assertNull($result['context']);
    $this->assertSame([], $result['expiries']);
  }

  /**
   * Media persistence and read credentials must survive write cleanup calls.
   *
   * @param array $changes
   *   Persistence or read-mode variations.
   *
   * @dataProvider persistentCredentials
   */
  public function testPersistentCredentials(array $changes): void {
    $result = $this->runScenario($changes + ['delete' => TRUE]);
    $this->assertNull($result['exception']);
    $this->assertNotNull($result['context']);
    foreach ($result['cache_calls'] as $call) {
      $this->assertNotSame('delete', $call[0]);
    }
  }

  /**
   * Accept literal and curl boolean forms without consulting REQUEST controls.
   */
  public static function persistentCredentials(): array {
    $nonce = str_repeat('a', 40);
    $post = ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2'];
    return [
      'literal persistence' => [['post' => $post + ['persist_auth' => 'true'], 'after_request' => ['persist_auth' => 'false']]],
      'curl persistence' => [['post' => $post + ['persist_auth' => '1']]],
      'equivalent boolean duplicates' => [['post' => $post + ['persist_auth' => TRUE], 'get' => ['persist_auth' => 'true']]],
      'read nonce' => [['mode' => 'read', 'entries' => [[$nonce, 1, 'read']]]],
    ];
  }

  /**
   * Storage failures must not be reported as successful renewal or cleanup.
   *
   * @param bool $renew
   *   Select failed renewal rather than failed deletion.
   *
   * @dataProvider storageFailures
   */
  public function testStorageFailure(bool $renew): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2', 'reset_timeout' => $renew ? 'true' : 'false'],
      'fail_set' => $renew,
      'fail_delete' => !$renew,
      'delete' => !$renew,
    ]);
    $this->assertSame('ServiceError', $result['exception']);
    if ($renew) {
      $this->assertNull($result['context']);
    }
  }

  /**
   * Cover both nonce lifecycle storage operations.
   */
  public static function storageFailures(): array {
    return [[TRUE], [FALSE]];
  }

  /**
   * Media cleanup errors must produce an error, not success followed by JSON.
   *
   * @param bool $failure
   *   Whether nonce deletion fails after synthetic upload processing.
   *
   * @dataProvider storageFailures
   */
  public function testMediaResponseAfterCleanup(bool $failure): void {
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'post' => ['nonce' => $nonce, 'auth_token' => sha1("$nonce:password:2") . ':2', 'name_is_guid' => 'true'],
      'media' => TRUE,
      'fail_delete' => $failure,
    ]);
    $this->assertNull($result['exception']);
    $this->assertSame($failure ? 500 : 200, $result['status']);
    if ($failure) {
      $response = json_decode($result['output'], TRUE, 512, JSON_THROW_ON_ERROR);
      $this->assertArrayHasKey('error', $response);
      $this->assertStringNotContainsString('test.jpg', $result['output']);
    }
    else {
      $this->assertSame('test.jpg', $result['output']);
      $this->assertNull($result['context']);
    }
  }

  /**
   * Bulk deletion authenticates before identity checks and reports failures.
   *
   * @param string $token
   *   Signed, legacy, or cryptographically invalid token.
   * @param int $status
   *   Expected HTTP status.
   * @param int $databaseCalls
   *   Whether the request may reach database construction.
   *
   * @dataProvider bulkAuthentication
   */
  public function testBulkAuthentication(string $token, int $status, int $databaseCalls): void {
    $result = $this->runScenario([
      'post' => ['nonce' => str_repeat('a', 40), 'auth_token' => $token, 'import_guid' => 'IMPORT-1', 'trial' => 't'],
      'bulk' => TRUE,
    ]);
    $this->assertNull($result['exception']);
    $this->assertSame($status, $result['status']);
    $this->assertSame($databaseCalls, $result['database_calls']);
    $response = json_decode($result['output'], TRUE, 512, JSON_THROW_ON_ERROR);
    $this->assertIsArray($response);
    if ($status === 200) {
      $this->assertSame('none', $response['action']);
    }
    else {
      $this->assertTrue(isset($response['error']) || isset($response['message']));
    }
  }

  /**
   * A signed token reaches SQL; legacy and invalid tokens never do.
   */
  public static function bulkAuthentication(): array {
    $nonce = str_repeat('a', 40);
    return [
      'signed' => [sha1("$nonce:password:2") . ':2', getenv('INDICIA_TEST_PG_DSN') ? 200 : 500, 1],
      'legacy' => [sha1("$nonce:password"), 400, 0],
      'invalid' => [sha1("$nonce:wrong:2") . ':2', 403, 0],
    ];
  }

  /**
   * Live/trial deletion preserves creator, website, and shared-sample boundaries.
   *
   * @param bool $trial
   *   Whether to count records without applying any mutations.
   *
   * @dataProvider bulkModes
   */
  public function testBulkDeletionSql(bool $trial): void {
    if (!getenv('INDICIA_TEST_PG_DSN') || !extension_loaded('pgsql')) {
      $this->markTestSkipped('Set INDICIA_TEST_PG_DSN for temporary-table bulk deletion tests.');
    }
    $nonce = str_repeat('a', 40);
    $result = $this->runScenario([
      'post' => [
        'nonce' => $nonce,
        'auth_token' => sha1("$nonce:password:2") . ':2',
        'import_guid' => 'IMPORT-1',
        'trial' => $trial ? 't' : '',
      ],
      'bulk' => TRUE,
    ]);
    $this->assertNull($result['exception']);
    $this->assertSame(200, $result['status']);
    $response = json_decode($result['output'], TRUE, 512, JSON_THROW_ON_ERROR);
    $this->assertSame($trial ? 'none' : 'delete', $response['action']);
    $this->assertNull($result['context']);
    $this->assertSame([], $result['expiries']);
    $this->assertSame(['occurrences' => '2', 'samples' => '1'], $response['affected']);
    $this->assertSame($trial ? [6] : [1, 4, 6], $result['records']['occurrences']);
    $this->assertSame($trial ? [] : [1], $result['records']['samples']);
    foreach (['functional', 'nonfunctional'] as $type) {
      $this->assertSame($trial ? [1, 2, 3, 4, 5, 6] : [2, 3, 5, 6], $result['records']["cache_occurrences_$type"]);
      $this->assertSame($trial ? [1, 2, 3, 4, 5] : [2, 3, 4, 5], $result['records']["cache_samples_$type"]);
    }
  }

  /**
   * Exercise both import counting and actual deletion.
   */
  public static function bulkModes(): array {
    return [[TRUE], [FALSE]];
  }
}
