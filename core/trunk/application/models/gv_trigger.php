<?php defined('SYSPATH') or die('No direct script access.');

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
 * @subpackage GridModels
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Declares a model simply to expose the gv_trigger view to ORM.
 *
 * @package	Core
 * @subpackage GridModels
 */
class gv_trigger_Model extends ORM {

  /**
   * The find_all override enforces that the grid view only shows public triggers or triggers created by this user,
   * and also filters the subscriber information to this user only. A bit too complex for base filter and auth 
   * filter techniques.
   */
  public function find_all($limit = NULL, $offset = NULL) {
    $this->in('private_for_user_id', array(null, $_SESSION['auth_user']->id)); 
    return parent::find_all($limit, $offset);    
  }
  
  

}