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
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the Site_Roles table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Site_Role_Model extends ORM {

  protected $has_many = array('users_websites');

  public function unique_key($id)
  {
    if ( ! empty($id) AND is_string($id) AND ! ctype_digit($id))
    {
      return 'title';
    }

    return parent::unique_key($id);
  }

} // End site Role Model