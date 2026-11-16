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
    require_once SYSPATH . 'libraries/ORM.php';
    require_once $root . '/application/libraries/MY_ORM.php';
    if (!class_exists('Validation')) {
      require_once SYSPATH . 'libraries/Validation.php';
      class_alias('Validation_Core', 'Validation');
    }
    require_once $root . '/application/models/user.php';
    require_once $root . '/application/models/users_website.php';
    if (!class_exists('inflector')) {
      require_once SYSPATH . 'helpers/inflector.php';
      class_alias('inflector_Core', 'inflector');
    }
    if (!class_exists('Service_User_Write_Test_Model', FALSE)) {
      // Keep the real nested submission pipeline, replacing only persistence.
      $model = new class extends User_Model {
        public static $writes = [];

        public function __construct($id = NULL) {
          $this->object_name = 'user';
          $this->object = ['id' => $id ?? 0];
          $this->table_columns = array_fill_keys([
            'id', 'allow_share_for_reporting', 'username',
          ], ['type' => 'string']);
        }

        protected function validateAndSubmit() {
          self::$writes[] = $this->submission['fields'];
          $this->object['id'] = $this->submission['fields']['id']['value'];
          return $this->id;
        }
      };
      class_alias(get_class($model), 'Service_User_Write_Test_Model');
    }
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

  /**
   * Exercise the model guard used by both direct and nested service submissions.
   *
   * @dataProvider userWrites
   */
  public function testUserServiceWrites(array $fields, ?int $authenticatedUserId, bool $allowed, array $extra = [], int $loadedId = 0): void {
    $reflection = new ReflectionClass(User_Model::class);
    $model = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('object')->setValue($model, ['id' => $loadedId]);
    $reflection->getProperty('table_columns')->setValue($model, array_fill_keys(array_keys($fields), ['type' => 'string']));
    $model->setIdentifiers(['service_auth_user_id' => $authenticatedUserId]);
    $model->submission = ['fields' => $fields] + $extra;
    $model->preSubmit();
    $errors = $reflection->getProperty('errors')->getValue($model);
    $this->assertSame($allowed, $errors === [], implode(' ', $errors));
  }

  public static function userWrites(): array {
    $preferences = [
      'id' => ['value' => '2'],
      'allow_share_for_reporting' => ['value' => 't'],
      'allow_share_for_peer_review' => ['value' => 'f'],
      'allow_share_for_verification' => ['value' => 't'],
      'allow_share_for_data_flow' => ['value' => 'f'],
      'allow_share_for_moderation' => ['value' => 't'],
      'allow_share_for_editing' => ['value' => 'f'],
    ];
    $creation = ['username' => ['value' => 'new-user'], 'person_id' => ['value' => 3]];
    $cases = [
      'own preferences' => [$preferences, 2, TRUE],
      'other user' => [$preferences, 3, FALSE],
      'unsigned authentication' => [$preferences, NULL, FALSE],
      'scalar field values' => [['id' => 2, 'allow_share_for_reporting' => FALSE], 2, TRUE],
      'invalid ID' => [array_replace($preferences, ['id' => ['value' => '2.0']]), 2, FALSE],
      'non-scalar ID' => [array_replace($preferences, ['id' => ['value' => [2]]]), 2, FALSE],
      'create without user identity' => [$creation, NULL, TRUE],
      'create with zero ID' => [$creation + ['id' => ['value' => 0]], NULL, TRUE],
      'create with preferences' => [$creation + ['allow_share_for_reporting' => ['value' => 't']], NULL, TRUE],
      'parent-inferred own ID' => [['allow_share_for_reporting' => ['value' => 'f']], 2, TRUE, [], 2],
      'parent-inferred other ID' => [['allow_share_for_reporting' => ['value' => 'f']], 3, FALSE, [], 2],
      'parent-inferred update cannot rename' => [$creation, 2, FALSE, [], 2],
    ];
    foreach (['username', 'password', 'person_id', 'core_role_id', 'deleted', 'updated_by_id', 'website_1', 'allow_share_for_unknown'] as $field) {
      $cases["update rejects $field"] = [$preferences + [$field => ['value' => 1]], 2, FALSE];
    }
    foreach (['core_role_id', 'password', 'deleted', 'created_by_id', 'website_1'] as $field) {
      $cases["create rejects $field"] = [$creation + [$field => ['value' => 1]], NULL, FALSE];
    }
    foreach (['fkFields', 'superModels', 'subModels', 'joinsTo', 'metaFields'] as $key) {
      $cases["update rejects $key"] = [$preferences, 2, FALSE, [$key => ['untrusted']]];
    }
    $cases['create rejects joins'] = [$creation, NULL, FALSE, ['joinsTo' => ['website' => [1]]]];
    return $cases;
  }

  /**
   * Service-only guards must not restrict the warehouse administration form.
   */
  public function testWarehouseUserAdministrationIsUnchanged(): void {
    $reflection = new ReflectionClass(User_Model::class);
    $model = $reflection->newInstanceWithoutConstructor();
    $model->submission = ['fields' => [
      'username' => ['value' => 'admin-user'],
      'core_role_id' => ['value' => 1],
      'website_1' => ['value' => 1],
    ]];
    $reflection->getProperty('table_columns')->setValue($model, ['username' => [], 'core_role_id' => []]);
    $model->preSubmit();
    $this->assertSame([], $reflection->getProperty('errors')->getValue($model));
    $this->assertArrayHasKey('website_1', $reflection->getProperty('droppedFields')->getValue($model));
  }

  /**
   * A site's user view is not the authorisation boundary for preference writes.
   */
  public function testUserUpdatesUseModelPermissions(): void {
    $reflection = new ReflectionClass(Data_Controller::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('check_update_access');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invoke($controller, 'user', ['fields' => ['id' => ['value' => 2]]]));
  }

  /**
   * Verify propagation to a nested model, including an ID inferred by the ORM.
   *
   * @dataProvider nestedUserWrites
   */
  public function testNestedUserWrites(?int $authenticatedUserId, bool $inferId, bool $rename, bool $allowed): void {
    require_once SYSPATH . 'core/Kohana.php';
    $configuration = new ReflectionProperty(Kohana::class, 'configuration');
    $configuration->setAccessible(TRUE);
    $original = $configuration->getValue();
    $config = [];
    require SYSPATH . 'config/inflector.php';
    $configuration->setValue(NULL, [
      'core' => ['log_threshold' => 0],
      'config' => ['log_threshold' => 0],
      'inflector' => $config,
    ]);
    try {
      Service_User_Write_Test_Model::$writes = [];
      $reflection = new ReflectionClass(ORM::class);
      $parent = $reflection->newInstanceWithoutConstructor();
      $parent->setIdentifiers(['service_auth_user_id' => $authenticatedUserId]);
      $reflection->getProperty('object_name')->setValue($parent, 'person');
      $reflection->getProperty('db')->setValue($parent, new class {
        public function select($fields) { return $this; }
        public function from($table) { return $this; }
        public function where($field, $value) { return $this; }
        public function get() { return $this; }
        public function current() { return (object) ['fk' => 2]; }
      });
      $fields = ['allow_share_for_reporting' => ['value' => 't']];
      if (!$inferId) {
        $fields['id'] = ['value' => 2];
      }
      if ($rename) {
        $fields['username'] = ['value' => 'forbidden-rename'];
      }
      $parent->submission = [
        'fields' => ['id' => ['value' => 9]],
        'superModels' => [[
          'fkId' => 'created_by_id',
          'model' => ['id' => 'service_user_write_test', 'fields' => $fields],
        ]],
      ];
      $method = $reflection->getMethod('createParentRecords');
      $method->setAccessible(TRUE);
      $this->assertSame($allowed, $method->invoke($parent));
      $this->assertCount($allowed ? 1 : 0, Service_User_Write_Test_Model::$writes);
    }
    finally {
      $configuration->setValue(NULL, $original);
    }
  }

  public static function nestedUserWrites(): array {
    return [
      'own preferences' => [2, FALSE, FALSE, TRUE],
      'other user' => [3, FALSE, FALSE, FALSE],
      'unsigned identity' => [NULL, FALSE, FALSE, FALSE],
      'extra field' => [2, FALSE, TRUE, FALSE],
      'inferred own user' => [2, TRUE, FALSE, TRUE],
      'inferred other user' => [3, TRUE, FALSE, FALSE],
      'inferred update cannot rename' => [2, TRUE, TRUE, FALSE],
    ];
  }

  /**
   * Reject roles and membership transfers before ordinary validation or saving.
   *
   * @dataProvider privilegedMembershipWrites
   */
  public function testServicesCannotGrantWebsiteRoles(array $fields, array $existing, string $errorField): void {
    $reflection = new ReflectionClass(Users_website_Model::class);
    $model = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('object')->setValue($model, $existing + ['site_role_id' => NULL]);
    $reflection->getProperty('loaded')->setValue($model, !empty($existing['id']));
    $model->setIdentifiers(['service_auth_user_id' => NULL]);
    $this->assertFalse($model->validate(new Validation($fields), TRUE));
    $this->assertArrayHasKey($errorField, $reflection->getProperty('errors')->getValue($model));
  }

  public static function privilegedMembershipWrites(): array {
    return [
      'new admin membership' => [['site_role_id' => 1], [], 'site_role_id'],
      'existing member becomes admin' => [['site_role_id' => 1], ['id' => 1, 'site_role_id' => 3], 'site_role_id'],
      'existing admin membership transferred' => [
        ['user_id' => 2, 'site_role_id' => 1],
        ['id' => 1, 'user_id' => 1, 'site_role_id' => 1],
        'user_id',
      ],
      'existing admin membership moved to another site' => [
        ['website_id' => 2, 'site_role_id' => 1],
        ['id' => 1, 'website_id' => 1, 'site_role_id' => 1],
        'website_id',
      ],
    ];
  }

}
