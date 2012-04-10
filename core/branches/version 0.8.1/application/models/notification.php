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
 * Model class for the notifications table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Notification_Model extends ORM {

  protected $has_many = array('taxa');

  protected $belongs_to = array('user');

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('source_type', 'required');
    $array->add_rules('data', 'required');
    $array->add_rules('acknowledged', 'required');
    $array->add_rules('user_id', 'required');
    $array->add_rules('triggered_on', 'required');
    $this->unvalidatedFields = array('digest_mode','cc');
    return parent::validate($array, $save);
  }
}

?>
