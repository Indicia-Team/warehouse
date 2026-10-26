<?php

/**
 * Isolated authentication harness using real controllers and the file cache.
 *
 * Each PHPUnit scenario runs in a child PHP process so framework doubles cannot
 * affect other tests. PostgreSQL cases use only connection-local temporary tables.
 */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $file, $line);
  }
  return FALSE;
});

$scenario = json_decode(stream_get_contents(STDIN), TRUE, 512, JSON_THROW_ON_ERROR);
$root = dirname(__DIR__, 4);
define('SYSPATH', $root . '/system/');

/**
 * Supply only the configuration and diagnostics required by real authentication.
 */
class Kohana {
  public static $directory;
  public static $logs = [];

  public static function config($key) {
    $values = [
      'cache.default' => [
        'driver' => 'Authentication',
        'params' => self::$directory,
        'lifetime' => 1800,
        'requests' => 0,
      ],
      'indicia.private_key' => 'warehouse-key',
      'indicia.nonce_life' => 7200,
      'indicia.http_status_responses' => TRUE,
      'indicia.maxUploadSize' => '1M',
      'indicia.upload_file_type' => [['jpg']],
      'upload.use_sub_directory_levels' => 0,
      'upload.directory' => self::$directory . '/',
      'indicia.auto_log_determinations' => FALSE,
      'data_utils.actions' => [
        'test_action' => ['stored_procedure' => 'test_action', 'parameters' => []],
      ],
      'data_utils.bulk_move_allowed_mappings' => [
        ['src' => ['survey_id' => 1, 'website_id' => 1], 'dest' => ['survey_id' => 2, 'website_id' => 1]],
      ],
    ];
    if (!array_key_exists($key, $values)) {
      throw new LogicException("Unexpected configuration key: $key");
    }
    return $values[$key];
  }

  public static function auto_load($class) {
    return class_exists($class, FALSE);
  }

  public static function log($level, $message) {
    self::$logs[] = [$level, $message];
  }

  public static function lang($key) {
    return $key;
  }
}

require_once SYSPATH . 'libraries/Controller.php';
class_alias('Controller_Core', 'Controller');
require_once SYSPATH . 'libraries/drivers/Cache.php';
require_once SYSPATH . 'libraries/Cache.php';
require_once $root . '/application/libraries/MY_Cache.php';
require_once SYSPATH . 'libraries/drivers/Cache/File.php';
require_once $root . '/application/controllers/service_base.php';
require_once $root . '/application/helpers/logRedaction.php';
require_once $root . '/modules/indicia_svc_data/controllers/data_service_base.php';
require_once $root . '/modules/indicia_svc_data/controllers/services/data_utils.php';
require_once $root . '/modules/indicia_svc_data/controllers/services/data.php';
require_once $root . '/modules/indicia_svc_data/controllers/services/custom_verification_rules.php';

/**
 * Accept a synthetic upload to test response/cleanup ordering without file IO.
 */
class Validation extends ArrayObject {
  public static function factory($files) {
    return new self($files);
  }

  public function add_rules(...$rules) {
    return $this;
  }

  public function validate() {
    return TRUE;
  }
}

/**
 * Stand in for upload processing; actual nonce cleanup remains unmodified.
 */
class upload {
  public static function save($field, $name, $directory) {
    return $directory . $name;
  }
}

/**
 * Avoid image conversion when only testing authentication response ordering.
 */
class Image {
  public static function create_image_files($directory, $name, $subdirectory, $websiteId) {
  }
}

/**
 * Record actual cache operations and simulate storage failures explicitly.
 */
class Cache_Authentication_Driver extends Cache_File_Driver {
  public static $calls = [];
  public static $failSet = FALSE;
  public static $failDelete = FALSE;

  public function exists($id, $tag = FALSE) {
    self::$calls[] = ['exists', $id];
    return parent::exists($id, $tag);
  }

  public function get($id) {
    self::$calls[] = ['get', $id];
    return parent::get($id);
  }

  public function set($id, $data, ?array $tags, $lifetime) {
    self::$calls[] = ['set', $id, $data, $tags, $lifetime];
    return self::$failSet ? FALSE : parent::set($id, $data, $tags, $lifetime);
  }

  public function delete($id, $tag = FALSE) {
    self::$calls[] = ['delete', $id];
    return self::$failDelete ? FALSE : parent::delete($id, $tag);
  }
}

/**
 * Known identities avoid database lookups while exercising both token domains.
 */
class ORM {
  public static function factory($entity, $id = NULL) {
    if ($entity === 'website') {
      return (object) ['id' => $id === 1 ? 1 : NULL, 'password' => 'password'];
    }
    if ($entity === 'user') {
      return (object) ['id' => in_array($id, [3, 4], TRUE) ? $id : NULL, 'core_role_id' => $id === 3 ? 1 : NULL];
    }
    if ($entity === 'users_website') {
      return new class {

        public function where(array $conditions) {
          if ($conditions !== ['user_id' => 4, 'site_role_id is not' => NULL, 'banned' => 'f']) {
            throw new LogicException('Unexpected warehouse membership filter');
          }
          return $this;
        }

        public function find_all() {
          return [(object) ['website_id' => 1], (object) ['website_id' => 2]];
        }
      };
    }
    throw new LogicException("Unexpected ORM entity: $entity");
  }
}

/**
 * Detect unwanted fallback for logged-in and remember-me-only sessions.
 */
class Auth {
  public static $calls = 0;
  public static $loggedIn = TRUE;

  public function __construct() {
    self::$calls++;
  }

  public function logged_in($role = NULL) {
    return self::$loggedIn;
  }

  public function auto_login() {
    return TRUE;
  }
}

/**
 * Capture errors rather than creating application log files.
 */
class error_logger {
  public static function log_error($context, $exception) {
    Kohana::log('error', $context . ': ' . $exception->getMessage());
  }
}

/**
 * Minimal PostgreSQL adapter for the unchanged bulk-delete query.
 *
 * Without an explicit test DSN, reaching the database raises a visible error.
 */
class Authentication_Bulk_Delete_Database {
  public static $calls = 0;
  public static $link;

  public function __construct() {
    self::$calls++;
    $dsn = getenv('INDICIA_TEST_PG_DSN');
    if (!$dsn) {
      throw new RuntimeException('Database reached');
    }
    self::$link = pg_connect(str_replace(';', ' ', preg_replace('/^pgsql:/', '', $dsn)), PGSQL_CONNECT_FORCE_NEW);
    if (self::$link === FALSE) {
      throw new RuntimeException('Cannot connect to test PostgreSQL');
    }
    $this->query(<<<SQL
      CREATE TEMP TABLE occurrences (
        id int, sample_id int, import_guid text, created_by_id int, website_id int,
        deleted boolean, updated_on timestamp, updated_by_id int
      );
      INSERT INTO occurrences VALUES
        (1,1,'IMPORT-1',2,1,false,NULL,NULL),
        (2,2,'IMPORT-1',3,1,false,NULL,NULL),
        (3,3,'IMPORT-1',2,2,false,NULL,NULL),
        (4,4,'IMPORT-1',2,1,false,NULL,NULL),
        (5,4,'OTHER',2,1,false,NULL,NULL),
        (6,5,'IMPORT-1',2,1,true,NULL,NULL);
      CREATE TEMP TABLE samples (id int, deleted boolean, updated_on timestamp, updated_by_id int);
      INSERT INTO samples SELECT generate_series(1,5), false, NULL, NULL;
      CREATE TEMP TABLE cache_occurrences_functional AS SELECT id FROM occurrences;
      CREATE TEMP TABLE cache_occurrences_nonfunctional AS SELECT id FROM occurrences;
      CREATE TEMP TABLE cache_samples_functional AS SELECT id FROM samples;
      CREATE TEMP TABLE cache_samples_nonfunctional AS SELECT id FROM samples;
      SQL);
  }

  public function getLink() {
    return self::$link;
  }

  public function query($sql) {
    $result = pg_query(self::$link, $sql);
    if ($result === FALSE) {
      throw new RuntimeException(pg_last_error(self::$link));
    }
    return $result;
  }

  public function select($columns) {
    if ($columns !== 'COUNT(distinct id) AS occurrences, COUNT(distinct sample_id) AS samples') {
      throw new LogicException('Unexpected count query');
    }
    return $this;
  }

  public function from($table) {
    if ($table !== 'to_delete') {
      throw new LogicException('Unexpected count table');
    }
    return $this;
  }

  public function get() {
    return $this;
  }

  public function current() {
    return pg_fetch_object($this->query('SELECT COUNT(distinct id) AS occurrences, COUNT(distinct sample_id) AS samples FROM to_delete'));
  }
}

if (!empty($scenario['utility_action'])) {
  require_once __DIR__ . '/utility_actions.php';
  class_alias('Utility_Action_Database', 'Database');
}
else {
  class_alias('Authentication_Bulk_Delete_Database', 'Database');
}

$_GET = $scenario['get'] ?? [];
$_POST = $scenario['post'] ?? [];
$_REQUEST = $scenario['request'] ?? array_merge($_GET, $_POST);
$_SESSION = ['auth_user' => (object) ['id' => 99, 'core_role_id' => 1]];
$_COOKIE = $scenario['cookie'] ?? [];
Auth::$loggedIn = $scenario['session_logged_in'] ?? TRUE;
$_SERVER['REQUEST_URI'] = '/services/data_utils/bulk_delete_occurrences';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
Kohana::$directory = $scenario['directory'];
$seed = new Cache_File_Driver(Kohana::$directory);
foreach ($scenario['entries'] ?? [] as $entry) {
  if (!$seed->set($entry[0], $entry[1], [$entry[2]], $entry[3] ?? 3600)) {
    throw new RuntimeException('Cannot seed test nonce');
  }
}
Cache_Authentication_Driver::$failSet = $scenario['fail_set'] ?? FALSE;
Cache_Authentication_Driver::$failDelete = $scenario['fail_delete'] ?? FALSE;
$controllerClass = !empty($scenario['media']) ? Data_Controller::class : Data_utils_Controller::class;
if (str_starts_with($scenario['utility_action'] ?? '', 'rules:')) {
  $controllerClass = Custom_verification_rules_Controller::class;
}
$reflection = new ReflectionClass($controllerClass);
$controller = $reflection->newInstanceWithoutConstructor();
$invoke = static function ($name, ...$arguments) use ($reflection, $controller) {
  $method = $reflection->getMethod($name);
  $method->setAccessible(TRUE);
  return $method->invoke($controller, ...$arguments);
};
$exception = NULL;
$replay = NULL;
ob_start();
try {
  if (!empty($scenario['utility_action'])) {
    $action = str_replace('rules:', '', $scenario['utility_action']);
    $controller->$action();
  }
  elseif (!empty($scenario['bulk'])) {
    $controller->bulk_delete_occurrences();
  }
  elseif (!empty($scenario['media'])) {
    $_FILES = ['media_upload' => ['name' => 'test.jpg']];
    $controller->handle_media();
  }
  else {
    $invoke('authenticate', $scenario['mode'] ?? 'write');
    if (isset($scenario['reauthenticate'])) {
      $_GET = $scenario['reauthenticate']['get'] ?? [];
      $_POST = $scenario['reauthenticate']['post'] ?? [];
      $_REQUEST = array_merge($_GET, $_POST);
      $invoke('authenticate', $scenario['mode'] ?? 'write');
    }
    if (isset($scenario['after_request'])) {
      $_REQUEST = $scenario['after_request'];
    }
    if (!empty($scenario['delete'])) {
      $invoke('delete_nonce');
    }
  }
}
catch (Exception $e) {
  $exception = get_class($e);
}
$output = ob_get_clean();
if (!empty($scenario['replay'])) {
  $before = Utility_Action_Database::$effects;
  ob_start();
  $replayException = NULL;
  try {
    $controller->$action();
  }
  catch (Exception $e) {
    $replayException = get_class($e);
  }
  $replay = [
    'output' => ob_get_clean(),
    'exception' => $replayException,
    'additional_effects' => Utility_Action_Database::$effects - $before,
  ];
}
$result = [
  'exception' => $exception,
  'output' => $output,
  'status' => http_response_code() ?: 200,
  'cache_calls' => Cache_Authentication_Driver::$calls,
  'session_calls' => Auth::$calls,
  'database_calls' => Database::$calls,
  'context' => $reflection->getProperty('authenticatedNonce')->getValue($controller),
  'website_id' => $reflection->getProperty('website_id')->getValue($controller),
  'user_id' => $reflection->getProperty('user_id')->getValue($controller),
  'auth_user_id' => $reflection->getProperty('auth_user_id')->getValue($controller),
  'in_warehouse' => $reflection->getProperty('in_warehouse')->getValue($controller),
  'core_admin' => $reflection->getProperty('user_is_core_admin')->getValue($controller),
  'user_websites' => $reflection->getProperty('userWebsites')->getValue($controller),
  'verified_user_id' => $invoke('getAuthenticatedUserId'),
  'remote_user_id' => $GLOBALS['remoteUserId'] ?? NULL,
  'remote_auth_user_id' => $GLOBALS['remoteAuthUserId'] ?? NULL,
  'logs' => Kohana::$logs,
  'expiries' => [],
  'replay' => $replay,
  'effects' => !empty($scenario['utility_action']) ? Utility_Action_Database::$effects : NULL,
];
foreach ($seed->exists(str_repeat('a', 40)) as $path) {
  $result['expiries'][] = (int) substr($path, strrpos($path, '~') + 1);
}
if (Database::$link && empty($scenario['utility_action'])) {
  foreach (['occurrences', 'samples', 'cache_occurrences_functional', 'cache_occurrences_nonfunctional', 'cache_samples_functional', 'cache_samples_nonfunctional'] as $table) {
    $sql = "SELECT id FROM $table" . (in_array($table, ['occurrences', 'samples']) ? ' WHERE deleted' : '') . ' ORDER BY id';
    $result['records'][$table] = array_map('intval', array_column(pg_fetch_all(pg_query(Database::$link, $sql)), 'id'));
  }
}
echo json_encode($result, JSON_THROW_ON_ERROR);
