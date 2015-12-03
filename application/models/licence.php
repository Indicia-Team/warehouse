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
 * Model class for the Licences table.
 *
 * @package	Core
 * @subpackage Models
 */
class Licence_Model extends ORM {
  public $search_field='title';

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user');

  protected $has_many = array('users');

  protected $has_and_belongs_to_many = array('websites');

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('code', 'required');
    $this->unvalidatedFields = array('description', 'url_readable', 'url_legal', 'version');
    return parent::validate($array, $save);
  }

}
