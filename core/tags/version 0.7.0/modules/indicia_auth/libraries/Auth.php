<?php defined('SYSPATH') or die('No direct script access.');
/**
 * User authorization library. Handles user login and logout, as well as secure
 * password hashing.
 *
 * @package    Auth
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Auth_Core {

  // Session instance
  protected $session;

  // Configuration
  protected $config;

  /**
   * Create an instance of Auth.
   *
   * @return  object
   */
  public static function factory($config = array())
  {
    return new Auth($config);
  }

  /**
   * Return a static instance of Auth.
   *
   * @return  object
   */
  public static function instance($config = array())
  {
    static $instance;

    // Load the Auth instance
    empty($instance) and $instance = new Auth($config);

    return $instance;
  }

  /**
   * Loads Session and configuration options.
   *
   * @return  void
   */
  public function __construct($config = array())
  {
    // Append default auth configuration
    $config += Kohana::config('auth');

    // Clean up the salt pattern and split it into an array
    $config['salt_pattern'] = preg_split('/,\s*/', Kohana::config('auth.salt_pattern'));

    // Save the config in the object
    $this->config = $config;

    // Set the driver class name
    $temp = $driver = 'Auth_'.$config['driver'].'_Driver';

    if ( ! Kohana::auto_load($driver))
      throw new Kohana_Exception('core.driver_not_found', $config['driver'], get_class($this));

    // Load the driver
    $driver = new $driver($config);

    if ( ! ($driver instanceof Auth_Driver))
      throw new Kohana_Exception('core.driver_implements', $config['driver'], get_class($this), 'Auth_Driver');
      
    // Load the driver for access
    $this->driver = $driver;

    Kohana::log('debug', 'Auth Library loaded');
  }

  /**
   * Check if there is an active session. Optionally allows checking for a
   * specific role.
   *
   * @param   string   role name
   * @return  boolean
   */
  public function logged_in($role = NULL)
  {
    return $this->driver->logged_in($role);
  }

  /**
   * Attempt to log in a user by using an ORM object and plain-text password.
   *
   * @param   string   username to log in
   * @param   string   password to check against
   * @param   boolean  enable auto-login
   * @return  boolean
   */
  public function login($username, $password, $remember = FALSE)
  {
//    if (empty($password))
//      return FALSE;

    if (is_string($password))
    {
      // Get the salt from the stored password
      $salt = $this->find_salt($this->driver->password($username));

      // Create a hashed password using the salt from the stored password
      $password = $this->hash_password($password, $salt);
    }

    return $this->driver->login($username, $password, $remember);
  }

  /**
   * Attempt to automatically log a user in.
   *
   * @return  boolean
   */
  public function auto_login()
  {
    return $this->driver->auto_login();
  }

  /**
   * Force a login for a specific username.
   *
   * @param   mixed    username
   * @return  boolean
   */
  public function force_login($username)
  {
    return $this->driver->force_login($username);
  }

  /**
   * Log out a user by removing the related session variables.
   *
   * @param   boolean   completely destroy the session
   * @return  boolean
   */
  public function logout($destroy = FALSE)
  {
    return $this->driver->logout($destroy);
  }

  /**
   * Attempt to log in a remote website user by using
   * a username, plain-text password and remote website id.
   * 
   * To be authenticated, ALL of the following must be true.
   * 
   * 1) the user identifier (username or email address) must exist in the users table
   * 2) the user must not be marked as deleted
   * 3) the user must be associated with the requesting website
   * 4) the user must not be banned from the requesting website
   * 5) the user must have an allocated role on the requesting website
   * 6) the supplied password should produce a matching hash to that stored for this user
   *
   * @param   string   remote username to log in
   * @param   string   password to check against
   * @param   array    options Optional.
   * Options array with the following possibilities:<ul>
   * <li><b>namecase</b><br/>
   * Optional. Boolean defining if the username value should be treated as case sensitive when looking
   * the user up on indicia core. Defaults to true.</li>
   * <li><b>nameormail</b><br/>
   * Optional. String defining if the username value represents the user's name or their e-mail address when looking
   * the user up on indicia core. Allowed values are 'name' or 'mail'. Defaults to 'name'.</li>
   * @param   integer  authenticated id for the requesting website
   *
   * @return  integer  user_id if authenticated, else 0
   */
  public function site_login($username, $password, $options, $website_id)
  {
    Kohana::log('debug', 'Entering Auth_Core->site_login');

    // we will return 0 indicates not authenticated
    $user_id = 0;

    // unpack options parameters
    $namecase = (array_key_exists('namecase', $options)) ? $options['namecase'] : true;
    $nameormail = (array_key_exists('nameormail', $options)) ? $options['nameormail'] : 'name';

    // load user by supplied unique identifier
    $user = null;
    if ('name' == $nameormail) {
      // Load the user by name
      if ($namecase) {
        $user = ORM::factory('user')->where(
        array('username' => $username))->find();
      } else {
        $user = ORM::factory('user')->like(
        array('username' => $username))->find();
      }
    } else {
      // Load the user by email address - never case sensitive
      $person = ORM::factory('person')->like(
      array('email_address' => $username))->find();
      if ($person->loaded) {
        $user = ORM::factory('user')->where(
        array('person_id' => $person->id))->find();
      }
    }
    if (! is_object($user) || ! $user->loaded) {
      // user not known
      Kohana::log('debug', 'Auth_Core->site_login - user '.$username.
      ' not known to indicia core');
      return $user_id;
    }
    
    if ('f' !== $user->deleted) {
      // user has been logically deleted
      Kohana::log('debug', 'Auth_Core->site_login - user '.$username.
      ' has been logically deleted from indicia core, $user->deleted = ['.$user->deleted.']');
      return $user_id;
    }

    // check if this is a user for the requesting website
    if (! $this->is_website_user ($user->id, $website_id)) {
      return $user_id;
    }
    
    // Get the salt from the stored password
    $salt = $this->find_salt($user->password);

    // Create a hashed password using the salt from the stored password
    $hashed_password = $this->hash_password($password, $salt);

    // if the password hashes match, we authenticate the user
    if ($user->password == $hashed_password) {
      $website = ORM::factory('users_website')->where(
        array('user_id' => $user->id,
        'website_id' => $website_id))->find();
      $website->last_login_datetime = date("Ymd H:i:s");
      $website->save();
      $user_id = $user->id;
    }

    Kohana::log('debug', 'Auth_Core->site_login - returning user_id '.$user_id);
    return $user_id;
  }
      
  /**
   * Get the site_role for the supplied user_id and website_id.
   *
   * @param   integer   remote user_id
   * @param   integer   authenticated id for the requesting website
   *
   * @return  string  site_role if found, else ''
   */
  public function get_site_role($user_id, $website_id)
  {
    Kohana::log('debug', 'Entering Auth_Core->get_site_role');

    // we will return '' if no role found
    $site_role = '';

    // check if this is a user for the requesting website
    $website = ORM::factory('users_website')->where(
    array('user_id' => $user_id,
      'website_id' => $website_id))->find();
    if (! $website->loaded) {
      // user not registered for requesting website
      Kohana::log('debug', 'Auth_Core->get_site_role - user_id '.$user_id.
        ' not registered for requesting website id '.$website_id);
      return $site_role;
    }
    $role = ORM::factory('site_role')->where(
    array('id' => $website->site_role_id))->find();
    if (! $role->loaded) {
      // user has no role for requesting website
      Kohana::log('debug', 'Auth_Core->get_site_role - user_id '.$user_id.
        ' has no role for requesting website id '.$website_id);
      return $site_role;
    }
    $site_role = $role->title;

    Kohana::log('debug', 'Auth_Core->get_site_role - returning site_role '.$site_role);
    return $site_role;
  }
      
  /**
   * Gets the user and person models for the supplied username or email address.
   *
   * @param   string    username or email to identify the user and person data
   *
   * @return  array   contains items 'user' for the user model and 'person' for the person model.
   * on error, contains error message in 'error_message'.
   */
  public function user_and_person_by_username_or_email ($username_or_email) {
    Kohana::log('debug', 'Entering Auth_Core->user_and_person_by_username_or_email ['.$username_or_email.']');

    $user = ORM::factory('user')->like(array('username' => $username_or_email))->find();
    if ( ! $user->loaded )
    {
      $person = ORM::factory('person', array('email_address' => $username_or_email));
      if ( ! $person->loaded )
      {
        return array('error_message' => 'Not a valid Username or Email address');
      }
      $user = ORM::factory('user', array('person_id' => $person->id));
      if ( ! $user->loaded )
      {
        return array('error_message' => $username_or_email.' is not a registered user');
      }
    }
    else
    {
      $person = ORM::factory('person', $user->person_id);
    }
    
    $result = array('user' => $user, 'person' => $person);
    Kohana::log('debug', 'Auth_Core->user_and_person_by_username_or_email - returning user and person models');
    return $result;
  }

  /**
   * Test if the current logged in user is at least user, editor or admin of at least one website.
   * @return boolean True if the user is has access to any website at this level.
   */
  public function has_any_website_access($level) {
    switch ($level) {
      case 'admin': $role=1; break;
      case 'editor': $role=2; break;
      case 'user': $role=3; break;
    }
    return ORM::factory('users_website')->where(
        array('user_id' => $_SESSION['auth_user']->id,
        'site_role_id <=' => $role, 'site_role_id IS NOT' => NULL))->find()->loaded;
  }
      
  /**
   * Returns true if the supplied user has a role on the supplied website.
   *
   * @param   integer    user id field
   * @param   integer    website id field
   * @param   boolean    if set to true, banned users don't count as users
   *
   * @return  boolean   true if user has a role on this website.
   */
  public function is_website_user ($user_id, $website_id, $exclude_banned=true) {
    Kohana::log('debug', 'Entering Auth_Core->is_website_user ['.$user_id.']['.$website_id.']');

    // check if this is a user for the requesting website
    $website = ORM::factory('users_website')->where(
        array('user_id' => $user_id,
        'website_id' => $website_id))->find();
    if (! $website->loaded) {
      // user not registered for requesting website
      Kohana::log('debug', 'Auth_Core->site_login - user '.$user_id.
      ' not registered for requesting website id '.$website_id);
      return false;
    }
    if ('f' !== $website->banned && $exclude_banned) {
      // user has been banned from requesting website
      Kohana::log('debug', 'Auth_Core->site_login - user '.$user_id.
      ' has been banned from requesting website id '.$website_id.' $website->banned = ['.$website->banned.']');
      return false;
    }
    if ('' == $website->site_role_id) {
      // user has blank role on requesting website
      Kohana::log('debug', 'Auth_Core->site_login - user '.$user_id.
      ' has blank site_role_id on requesting website id '.$website_id.' of ['.$website->site_role_id.']');
      return false;
    }
        
    $result = true;
    Kohana::log('debug', 'Auth_Core->is_website_user - returning '.$result);
    return $result;
  }
      
  /**
   * Creates and stores a forgotten_password_key, then composes and sends an email to 
   * the user's registered address.
   *
   * @param   User_Model the user's User_Model
   * @param   Person_Model the user's Person_Model
   *
   * @return  void. Throws Kohana_User_Exception on error.
   */
  public function send_forgotten_password_mail($user, $person) {
    Kohana::log('debug', 'Entering Auth_Core->send_forgotten_password_mail');

    $email_config = Kohana::config('email');

    $link_code = $this->hash_password($user->username);
    $user->__set('forgotten_password_key', $link_code);
    $user->save();
    try
    {
      $swift = email::connect();
      $message = new Swift_Message($email_config['forgotten_passwd_title'],
      View::factory('templates/forgotten_password_email')->set(array('server' => $email_config['server_name'], 'new_password_link' => '<a href="'.url::site().'new_password/email/'.$link_code.'">'.url::site().'new_password/email/'.$link_code.'</a>')),
                                   'text/html');
      $recipients = new Swift_RecipientList();
      $recipients->addTo($person->email_address, $person->first_name.' '.$person->surname);
      $swift->send($message, $recipients, $email_config['address']);
    }
    catch (Swift_Exception $e)
    {
      kohana::log('error', "Error sending forgotten password: " . $e->getMessage());
      throw new Kohana_User_Exception('swift.general_error', $e->getMessage());
    }
    kohana::log('info', "Forgotten password sent to $person->first_name $person->surname");
    return ;
  }
  
  /**
   * Creates a hashed password from a plaintext password, inserting salt
   * based on the configured salt pattern.
   *
   * @param   string  plaintext password
   * @return  string  hashed password string
   */
  public function hash_password($password, $salt = FALSE)
  {
    if ($salt === FALSE)
    {
      // Create a salt seed, same length as the number of offsets in the pattern
      $salt = substr($this->hash(uniqid(NULL, TRUE)), 0, count($this->config['salt_pattern']));
    }

    // Password hash that the salt will be inserted into
    $hash = $this->hash($salt.$password);

    // Change salt to an array
    $salt = str_split($salt, 1);

    // Returned password
    $password = '';

    // Used to calculate the length of splits
    $last_offset = 0;

    foreach ($this->config['salt_pattern'] as $offset)
    {
      // Split a new part of the hash off
      $part = substr($hash, 0, $offset - $last_offset);

      // Cut the current part out of the hash
      $hash = substr($hash, $offset - $last_offset);

      // Add the part to the password, appending the salt character
      $password .= $part.array_shift($salt);

      // Set the last offset to the current offset
      $last_offset = $offset;
    }

    // Return the password, with the remaining hash appended
    return $password.$hash;
  }

  /**
   * Perform a hash, using the configured method.
   *
   * @param   string   string to hash
   * @return  string
   */
  protected function hash($str)
  {
    return hash($this->config['hash_method'], $str);
  }

  /**
   * Finds the salt from a password, based on the configured salt pattern.
   *
   * @param   string  hashed password
   * @return  string
   */
  protected function find_salt($password)
  {
    $salt = '';

    foreach ($this->config['salt_pattern'] as $i => $offset)
    {
      // Find salt characters... take a good long look..
      $salt .= substr($password, $offset + $i, 1);
    }

    return $salt;
  }

} // End Auth