<?php

class login {

  public function __construct()
  {
    // Hook into routing, but not if running unit tests
    if (!in_array(MODPATH.'phpUnit', kohana::config('config.modules'))) {
      Event::add('system.routing', array($this, 'check'));      
    }
  }

  /**
   * When visiting any page on the site, check if the user is already logged in,
   * or they are visiting a page that is allowed when logged out. Otherwise,
   * redirect to the login page. If visiting the login page, check the browser
   * supports cookies.
   */
  public function check()
  {
    $uri = new URI();
    // Skip check when accessing the data services, as it is redundant but would slow the services down.
    // Also no need to login when running the scheduled tasks.
    if ($uri->segment(1)=='services' || $uri->segment(1)=='scheduled_tasks') {
    	return;
    }
    // check for setup request
    //
    if($uri->segment(1) == 'setup_check')
    {
      // get kohana paths
      //
      $ipaths = Kohana::include_paths();

      // check if indicia_setup module folder exists
      //
      clearstatcache();
      foreach($ipaths as $path)
      {
        if((preg_match("/indicia_setup/",$path)) && file_exists($path))
        {
          return;
        }
      }
    }
    // Always logged in
    $auth = new Auth();

    if (! $auth->logged_in() AND
        ! $auth->auto_login() AND
        $uri->segment(1) != 'login' AND
        $uri->segment(1) != 'logout' AND
        $uri->segment(1) != 'new_password' AND
        $uri->segment(1) != 'forgotten_password')
    {
      $_SESSION['requested_page'] = $uri->string();
      url::redirect('login');
    }
    // If we are logged in, but the password was blank, force a change of password. (allow logging out only)
    else if ($auth->logged_in() AND is_null($_SESSION['auth_user']->password) AND
      $uri->segment(1) != 'new_password' AND $uri->segment(1) != 'logout' AND $uri->segment(1) != 'setup_check')
    {
      $_SESSION['requested_page'] = $uri->string();
      url::redirect('new_password');
    }
  }
  
}

new login;