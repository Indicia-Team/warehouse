<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Exception class for Indicia services.
 */
class ServiceError extends Exception {
}

/**
 * Exception class for exception that contain an array of sub-errors, such as a submission validation failure.
 */
class ArrayException extends ServiceError {

  private $errors = array();

  /**
   * Override constructor to accept an errors array
   */
  public function __construct($message, $code, $errors) {
    $this->errors = $errors;
    // Make sure everything is assigned properly.
    parent::__construct($message, $code);
  }

  public function errors() {
    return $this->errors;
  }

}

/**
 * Exception class for submission validation problems.
 */
class ValidationError extends ArrayException {
}

/**
 * Exception class for authentication failures.
 */
class AuthenticationError extends ServiceError {
}

/**
 * Exception class for authorisation failures.
 */
class AuthorisationError extends ServiceError {
}

/**
 * Exception class for inaccessible entities or view combinations.
 */
class EntityAccessError extends ServiceError {
}

/**
 * Base controller class for Indicia Service controllers.
 */
class Service_Base_Controller extends Controller {

  /**
   * Defines if the user is logged into the warehouse.
   *
   * @var bool
   */
  protected $in_warehouse = FALSE;

  /**
   * Id of the website calling the service.
   *
   * Obtained when performing read authentication and used to filter the
   * response. A value of 0 indicates the warehouse.
   *
   * @var int
   */
  protected $website_id = NULL;

  /**
   * Password used to authenticate.
   *
   * @var string
   */
  protected $website_password = NULL;

  /**
   * Id of the indicia user ID calling the service.
   *
   * Obtained when performing read authentication and can be used to filter the
   * response. May come from an unsigned request parameter, so must not be used
   * for permission checks. Null if not provided in the report call.
   *
   * @var int
   */
  protected $user_id = NULL;

  /**
   * If authorised via an auth_token, set the user ID here.
   *
   * @var int
   */
  protected $auth_user_id = NULL;

  /**
   * Validated nonce lifecycle context, populated only after authentication.
   *
   * @var array|null
   */
  protected $authenticatedNonce = NULL;

  /**
   * Flag set to true when user has core admin rights.
   *
   * Only applies when the request originates from the warehouse.
   *
   * @var bool
   */
  protected $user_is_core_admin = false;

  /**
   * List of website IDs the user is authorised to see.
   *
   * @var array
   */
  protected $userWebsites;

  /**
   * Content type, if specified for the response.
   *
   * @var string
   */
  protected $content_type;

  /**
   * If response in a file, provide the file name.
   *
   * @var string
   */
  protected $responseFile;

  /**
   * Response content.
   *
   * @var string
   */
  protected $response;

  /**
   * Get the verified user identity for permission checks.
   *
   * @return int|null
   *   User authenticated by a user-specific token or warehouse nonce.
   */
  protected function getAuthenticatedUserId(): ?int {
    if ($this->in_warehouse) {
      $userId = $this->user_id;
    }
    else {
      $userId = $this->auth_user_id;
    }
    return $userId > 0 ? (int) $userId : NULL;
  }

  /**
   * Resolve credentials and lifecycle controls without cookie/REQUEST precedence.
   *
   * Identical GET/POST duplicates are allowed, but conflicting values, partial
   * credentials, and non-scalar or malformed values fail authentication.
   * Lifecycle controls accept true/false and 1/0, including curl boolean forms.
   *
   * @return array
   *   Validated credentials and boolean lifecycle controls.
   */
  protected function getAuthenticationParameters(): array {
    $parameters = [];
    foreach (['nonce', 'auth_token', 'persist_auth', 'reset_timeout'] as $key) {
      foreach ([$_POST, $_GET] as $source) {
        if (!array_key_exists($key, $source)) {
          continue;
        }
        $value = $source[$key];
        if ($key === 'persist_auth' || $key === 'reset_timeout') {
          if (in_array($value, [TRUE, 1, '1', 'true'], TRUE)) {
            $value = TRUE;
          }
          elseif (in_array($value, [FALSE, 0, '0', 'false', ''], TRUE)) {
            $value = FALSE;
          }
          else {
            throw new AuthenticationError('Invalid authentication parameters.', 1);
          }
        }
        elseif (!is_string($value)) {
          throw new AuthenticationError('Invalid authentication parameters.', 1);
        }
        if (array_key_exists($key, $parameters) && $parameters[$key] !== $value) {
          throw new AuthenticationError('Conflicting authentication parameters.', 1);
        }
        $parameters[$key] = $value;
      }
    }
    if (isset($parameters['nonce']) !== isset($parameters['auth_token'])) {
      throw new AuthenticationError('Incomplete authentication credentials.', 1);
    }
    if (isset($parameters['nonce'])) {
      if (!preg_match('/\A[0-9a-f]{40}\z/', $parameters['nonce'])
          || !preg_match('/\A[0-9a-f]{40}(?::([1-9][0-9]*))?\z/', $parameters['auth_token'], $matches)
          || (isset($matches[1]) && filter_var($matches[1], FILTER_VALIDATE_INT) === FALSE)) {
        throw new AuthenticationError('Invalid authentication credentials.', 1);
      }
    }
    return $parameters;
  }

  /**
   * Authenticate a data services request.
   *
   * Explicit GET/POST credentials are required, including for warehouse users.
   * Session and remember-me cookies never substitute for a nonce/token pair.
   *
   * @param string $mode
   *   Whether the authentication token is required to have read or write
   *   access. Possible values are 'read' and 'write'. Defaults to 'write'.
   */
  protected function authenticate($mode = 'write') {
    global $remoteAuthUserId;
    global $remoteUserId;
    $this->in_warehouse = FALSE;
    $this->website_id = NULL;
    $this->website_password = NULL;
    $this->user_id = NULL;
    $this->auth_user_id = NULL;
    $this->user_is_core_admin = FALSE;
    $this->userWebsites = [];
    $remoteAuthUserId = NULL;
    $remoteUserId = NULL;
    $this->authenticatedNonce = NULL;
    $array = $this->getAuthenticationParameters();
    $authentic = FALSE;
    if (array_key_exists('nonce', $array) && array_key_exists('auth_token', $array)) {
      $nonce = $array['nonce'];
      $cache = new Cache();
      // Get all cache entries that match this nonce.
      $paths = $cache->exists($nonce);
      foreach ($paths as $path) {
        // Find the parts of each file name, which is the cache entry ID, then
        // the mode.
        $tokens = explode('~', basename($path));
        // Check this cached nonce is for the correct read or write operation.
        if (count($tokens) === 3 && $tokens[0] === $nonce && $mode === $tokens[1]) {
          $cachedId = $cache->get($nonce);
          // A stale filename can survive expiry; absent data must never select
          // private-key authentication instead of website authentication.
          if (!is_int($cachedId) && !is_string($cachedId)) {
            continue;
          }
          $id = filter_var($cachedId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => -PHP_INT_MAX, 'max_range' => PHP_INT_MAX],
          ]);
          if ($id === FALSE || $id === 0) {
            continue;
          }
          $password = NULL;
          if ($id > 0) {
            // Normal state, the ID is positive, which means we are
            // authenticating a remote website.
            $website = ORM::factory('website', $id);
            if ($website->id) {
              $password = $website->password;
            }
          }
          else {
            $user = ORM::factory('user', -$id);
            if (!$user->id) {
              continue;
            }
            $password = kohana::config('indicia.private_key');
          }
          // Calculate the auth token from the nonce and the password. Does it
          // match the request's auth token? The auth_token can optionally be
          // user specific in which case the user ID is both appended and
          // embedded in the hash.
          if (isset($password) && preg_match('/:(?<userId>\d+)$/', $array['auth_token'], $matches)
              && sha1("$nonce:$password:$matches[userId]") . ':' . $matches['userId'] === $array['auth_token']) {
            // Store the authorised user ID.
            $this->auth_user_id = (int) $matches['userId'];
            $authentic = TRUE;
          }
          elseif (isset($password) && sha1("$nonce:$password") === $array['auth_token']) {
            // Disable anything that requires elevated user permissions.
            $this->auth_user_id = -1;
            $authentic = TRUE;
          }
          if ($authentic) {
            if (!empty($array['reset_timeout'])) {
              if (!$cache->set($nonce, $id, $mode, Kohana::config('indicia.nonce_life'))) {
                throw new ServiceError('Unable to renew authentication nonce.', 1);
              }
              Kohana::log('info', "Nonce timeout reset.");
            }
            Kohana::log('info', "Authentication successful.");
            // Cache website_password for subsequent use by controllers.
            $this->website_password = $password;

            if ($id > 0) {
              $this->website_id = (int) $id;
              if ($this->auth_user_id !== -1) {
                $this->user_id = (int) $this->auth_user_id;
              }
              elseif (!empty($_REQUEST['user_id']) && preg_match('/^\d+$/', $_REQUEST['user_id'])) {
                $this->user_id = (int) $_REQUEST['user_id'];
              }
            }
            else {
              $this->in_warehouse = TRUE;
              // 0 is the Warehouse.
              $this->website_id = 0;
              // User id was passed as a negative number to differentiate from
              // a website id.
              $this->user_id = 0 - $id;
              $this->auth_user_id = $this->user_id;
              // Get a list of the websites this user can see.
              $this->user_is_core_admin = ($user->core_role_id === 1);
              if (!$this->user_is_core_admin) {
                $this->userWebsites = [];
                $userWebsites = ORM::Factory('users_website')->where([
                  'user_id' => $this->user_id,
                  'site_role_id is not' => NULL,
                  'banned' => 'f',
                ])->find_all();
                foreach ($userWebsites as $userWebsite) {
                  $this->userWebsites[] = $userWebsite->website_id;
                }
              }
            }
            // Store authenticated context for code outside the service classes.
            $remoteAuthUserId = $this->auth_user_id;
            $remoteUserId = $this->user_id;
            $this->authenticatedNonce = [
              'nonce' => $nonce,
              'mode' => $mode,
              'persist_auth' => $array['persist_auth'] ?? FALSE,
            ];
            break;
          }
        }
      }
    }
    if (!$authentic) {
      Kohana::log('info', "Unable to authenticate.");
      throw new AuthenticationError("unauthorised", 1);
    };
  }

  /**
   * Set the content type and then issue the response.
   *
   * Response data can be a string in $this->response, or the contents of a
   * temporary file in $this->responseFile. The temporary file will be
   * deleted once the contents are returned.
   */
  protected function send_response() {
    // Last thing we do is set the output.
    if (isset($this->content_type)) {
      header($this->content_type);
    }
    if (!empty($this->responseFile)) {
      readfile($this->responseFile);
      // Tidy up the temporary file.
      if (!unlink($this->responseFile)) {
        kohana::log('alert', "Could not delete temporary file $this->responseFile");
      }
    }
    else {
      echo $this->response;
    }
  }

  /**
   * Return an error XML or json document to the client.
   *
   * @param Throwable $e
   *   The exception.
   * @param string $transaction_id
   *   Id of the transaction calling the service. Optional. Returned to the
   *   calling code.
   */
  protected function handle_error($e, $transaction_id = NULL) {
    $message = $e->getMessage();
    if ($e instanceof ValidationError || $e instanceof InvalidArgumentException) {
      $statusCode = 400;
    }
    elseif (strpos($message, 'imagecreatefrom') !== FALSE) {
      // Special case for errors during image resizing.
      $statusCode = 400;
      $message = 'Image processing error: ensure the image is a valid format and not corrupted. The image has been saved but thumbnail generation failed.';
      error_logger::log_error('Image processing error in data services', $e);
    }
    elseif ($e instanceof AuthenticationError || $e instanceof AuthorisationError) {
      // Not 401 as not using browser or official digest authentication.
      $statusCode = 403;
    }
    elseif ($e instanceof EntityAccessError) {
      $statusCode = 404;
    }
    elseif ($e->getCode() >= 400 && $e->getCode() < 600) {
      // If the exception code is a valid HTTP status code, use it.
      $statusCode = $e->getCode();
    }
    else {
      $statusCode = 500;
      error_logger::log_error('Internal Service Error response from data services', $e);
      $message = 'An internal error occurred. More information is in the warehouse logs (' . date("Y-m-d H:i:s") . ').';
    }
    // Give a chance to localise the message.
    $translated = kohana::lang('general_errors.' . $message);
    if (substr($translated, 0, 15) !== 'general_errors.') {
      $message = $translated;
    }
    $mode = $this->get_output_mode();
    // Set the HTTP response code only if configured to do so and not JSONP.
    // JSONP will need to check the response error instead.
    if (kohana::config('indicia.http_status_responses') === TRUE && empty($_GET['callback'])) {
      header(' ', TRUE, $statusCode);
    }
    if ($mode === 'xml') {
      $view = new View("services/error");
      $view->message = $message;
      $view->code = $e->getCode();
      $view->render(TRUE);
    }
    elseif ($mode === 'csv' || $mode === 'tsv') {
      echo "status: $statusCode\n";
      echo "$message\n";
    }
    else {
      header("Content-Type: application/json");
      $response = [
        'error' => $message,
        'code' => $e->getCode(),
      ];
      if ($transaction_id) {
        $response['transaction_id'] = $transaction_id;
      }
      if ($e instanceof ArrayException) {
        $response['errors'] = $e->errors();
      }
      elseif (!$e instanceof ServiceError) {
        $response['file'] = $e->getFile();
        $response['line'] = $e->getLine();
        $response['trace'] = array();
      }
      $a = json_encode($response);
      if (array_key_exists('callback', $_GET)) {
        $a = "$_GET[callback]($a)";
      }
      echo $a;
    }
  }

  /**
   * Retrieve the output mode for a RESTful request from the GET or POST data.
   * Defaults to json. Other options are xml and csv, or a view loaded from the views folder.
   */
  protected function get_output_mode() {
    if (isset($_REQUEST['mode']))
      return $_REQUEST['mode'];
    else
      return 'json';
  }

  /**
   * Retrieve the input mode for a RESTful request from the POST data.
   * Defaults to json. Other options not yet implemented.
   */
  protected function get_input_mode() {
    if (array_key_exists('mode', $_POST)){
      $result = $_POST['mode'];
    } else {
      $result = 'json';
    }
    return $result;
  }

}