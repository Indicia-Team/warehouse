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
 * @package	Core.Controllers
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for the home page.
 *
 * @package Core
 * @subpackage Controllers
 */
class Home_Controller extends Indicia_Controller {

  public function index()
  {
    $this->check_for_upgrade();

    $view = new View('home');
    $this->template->title='Indicia';
    $this->template->content=$view;
  }

  /**
  * Check version of the php scripts against the database version
  */
  private function check_for_upgrade()
  {
    // system file which is distributed with every indicia version
    //
    $new_system = Kohana::config('indicia_dist.system');

    // get system info with the version number of the database
    $db_system = new System_Model;

    // compare the script version against the database version
    // if both arent equal start the upgrade process
    //
    if(0 != version_compare($db_system->getVersion(), $new_system['version'] ))
    {
      $upgrade = new Upgrade_Model;

      // upgrade to version $new_system['version']
      //
      if(true !== ($result = $upgrade->run($db_system->getVersion(), $new_system)))
      {
        // fatal error: the system stops here
        //
        if( false === Kohana::config('core.display_errors'))
      {
        die( Kohana::lang('setup.error_upgrade_for_end_user') );
      }
      else
      {
        die( 'UPGRADE ERROR: <pre>' . nl2br($result) . '</pre>' );
      }
      }

      // if successful, reload the system and display a message
      //
      $this->session->create()->set_flash(
        'flash_info', 'The Warehouse has been upgraded to version '.$new_system['version']
      );
      url::redirect();
    }
  }
}

?>
