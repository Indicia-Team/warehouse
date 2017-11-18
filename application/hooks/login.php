<?php

/**
 * @file
 * Login redirection hook.
 *
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
 * @package Core
 * @subpackage Controllers
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Login hook class.
 *
 * Checks all routing events. If not logged in then redirects to the login page.
 */
class login {

  /**
   * Constructor, attaches event hook function.
   */
  public function __construct() {
    // Hook into routing, but not if running unit tests.
    if (!defined('inPhpUnit')) {
      Event::add('system.routing', array($this, 'check'));
    }
  }

  /**
   * Checks user log in status on any routing event.
   *
   * When visiting any page on the site, check if the user is already logged in,
   * or they are visiting a page that is allowed when logged out. Otherwise,
   * redirect to the login page. If visiting the login page, check the browser
   * supports cookies.
   */
  public function check() {
    $uri = new URI();
    // Skip check when accessing the data services, as it is redundant but would slow the services down.
    // Also no need to login when running the scheduled tasks.
    if ($uri->segment(1) === 'services' || $uri->segment(1) === 'scheduled_tasks') {
      return;
    }
    // Check for setup request.
    if ($uri->segment(1) == 'setup_check') {
      // Get kohana paths.
      $ipaths = Kohana::include_paths();

      // Check if indicia_setup module folder exists.
      clearstatcache();
      foreach ($ipaths as $path) {
        if ((preg_match("/indicia_setup/", $path)) && file_exists($path)) {
          return;
        }
      }
    }
    // Always logged in.
    $auth = new Auth();

    if (!$auth->logged_in()
        && !$auth->auto_login()
        && $uri->segment(1) != 'login'
        && $uri->segment(1) != 'logout'
        && $uri->segment(1) != 'new_password'
        && $uri->segment(1) != 'forgotten_password') {
      url::redirect('login');
    }
    // If we are logged in, but the password was blank, force a change of password (allow logging out only).
    elseif ($auth->logged_in()
        && is_null($_SESSION['auth_user']->password)
        && $uri->segment(1) != 'new_password'
        && $uri->segment(1) != 'logout'
        && $uri->segment(1) != 'setup_check') {
      $_SESSION['requested_page'] = $uri->string();
      url::redirect('new_password');
    }
  }

}

new login();
