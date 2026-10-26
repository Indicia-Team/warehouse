<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests permission identities without database or HTTP services.
 *
 * Controllers are constructed without framework initialisation so the tests
 * isolate identity selection and rejection of unsigned decryption requests.
 */
class Controllers_Services_Authenticated_Identity_Test extends TestCase {

  /**
   * Load the real controllers for standalone PHPUnit or the warehouse suite.
   */
  public static function setUpBeforeClass(): void {
    $root = dirname(__DIR__, 5);
    if (!defined('SYSPATH')) {
      define('SYSPATH', $root . '/system/');
    }
    if (!class_exists('Controller')) {
      require_once SYSPATH . 'libraries/Controller.php';
      class_alias('Controller_Core', 'Controller');
    }
    require_once $root . '/application/controllers/service_base.php';
    require_once $root . '/modules/indicia_svc_data/controllers/data_service_base.php';
    require_once $root . '/modules/indicia_svc_data/controllers/services/data.php';
    require_once $root . '/application/helpers/attributeEncryption.php';
  }

  /**
   * Select verified identities without altering legacy user_id filters.
   *
   * @param array $context
   *   Controller authentication state normally populated by authenticate().
   * @param int|null $sessionUserId
   *   Warehouse session identity, if present.
   * @param int|null $expected
   *   Verified permission identity, or NULL when no user is authenticated.
   *
   * @dataProvider identityContexts
   */
  public function testPermissionIdentity(array $context, ?int $sessionUserId, ?int $expected) {
    $reflection = new ReflectionClass(Service_Base_Controller::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    foreach ($context as $property => $value) {
      $reflection->getProperty($property)->setValue($controller, $value);
    }
    $session = $_SESSION ?? [];
    $_SESSION['auth_user'] = $sessionUserId === NULL ? NULL : (object) ['id' => $sessionUserId];
    try {
      $method = $reflection->getMethod('getAuthenticatedUserId');
      $method->setAccessible(TRUE);
      $this->assertSame($expected, $method->invoke($controller));
      $this->assertSame($context['user_id'], $reflection->getProperty('user_id')->getValue($controller));
    }
    finally {
      $_SESSION = $session;
    }
  }

  /**
   * Cover legacy/signed tokens and prevent borrowing warehouse session identity.
   *
   * @return array
   *   Named cases containing controller state, session ID, and expected ID.
   */
  public static function identityContexts(): array {
    return [
      'legacy token with forged admin filter' => [['user_id' => 1, 'auth_user_id' => -1], NULL, NULL],
      'legacy token cannot borrow warehouse session' => [['user_id' => 1, 'auth_user_id' => -1], 1, NULL],
      'no token identity' => [['user_id' => 1], NULL, NULL],
      'zero token identity' => [['user_id' => 1, 'auth_user_id' => 0], NULL, NULL],
      'signed identity overrides filter' => [['user_id' => 1, 'auth_user_id' => 2], NULL, 2],
      'signed admin identity' => [['user_id' => 1, 'auth_user_id' => 1], NULL, 1],
      'warehouse nonce identity' => [['user_id' => 2, 'auth_user_id' => -1, 'in_warehouse' => TRUE], 1, 2],
      'warehouse session alone has no verified identity' => [['user_id' => NULL, 'in_warehouse' => TRUE], 2, NULL],
      'warehouse without identity' => [['user_id' => NULL, 'in_warehouse' => TRUE], NULL, NULL],
    ];
  }

  /**
   * Leave encrypted output untouched when only an unsigned user ID is supplied.
   *
   * No database is provided: rejection must occur before creator or role
   * lookups, for both buffered results and streamed rows.
   *
   * @param array|object $row
   *   Buffered result rows or a single streamed row.
   * @param string $methodName
   *   Controller output method to exercise.
   *
   * @dataProvider unsignedAttributeRows
   */
  public function testUnsignedUserFilterCannotDecrypt($row, string $methodName) {
    $reflection = new ReflectionClass(Data_Controller::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    foreach (['entity' => 'location_attribute_value', 'user_id' => 1, 'auth_user_id' => -1] as $property => $value) {
      $reflection->getProperty($property)->setValue($controller, $value);
    }
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);
    $this->assertSame($row, $method->invoke($controller, $row));
  }

  /**
   * Provide array/object representations of buffered and streamed output.
   *
   * @return array
   *   Named cases containing encrypted output and the processing method.
   */
  public static function unsignedAttributeRows(): array {
    $row = [
      'id' => 1,
      'website_id' => 1,
      'data_type' => 'T',
      'value' => 'enc:v1:sodium:test:encrypted-value',
      'raw_value' => 'enc:v1:sodium:test:encrypted-value',
    ];
    return [
      'JSON array results' => [[$row], 'decryptAttributeValueResults'],
      'object results' => [[(object) $row], 'decryptAttributeValueResults'],
      'streamed array row' => [$row, 'transformOutputRow'],
      'streamed object row' => [(object) $row, 'transformOutputRow'],
    ];
  }

}
