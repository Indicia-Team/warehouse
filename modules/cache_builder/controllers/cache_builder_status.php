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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for the login page.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Cache_builder_status_Controller extends Indicia_Controller {

  public function index() {
    $config = kohana::config('cache_builder');

    $this->template->title = 'Cache Builder Status';
    $this->template->content = new View('cache_builder_status/index');
    $values = [];
    foreach ($config as $table => $discard) {
    	$values[$table] = variable::get("populated-$table");
    }
    $this->template->content->values = $values;
  }

  public function save() {
  	$state = true;
  	$config = kohana::config('cache_builder');

	if (isset($_POST))
  		foreach($config as $table => $discard){
			// can only switch on the catchup, not switch it off.
			// Due to way catch up works, the tables after are not run if this one not complete
			// so it may mean they miss their normal processing. So have to set all subsequent
			// tables to catch up as well.
		    if((isset($_POST[$table]) && $_POST[$table]==0 ) || $state == false) {
		    	$state = false;
  				variable::set("populated-$table", false);
			}
  		}
  	$this->index();
  }

}