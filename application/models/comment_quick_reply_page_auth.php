<?php

defined('SYSPATH') or die('No direct script access.');

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
 * @package Core
 * @subpackage Models
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

/**
 * Model class for the user_trusts table.
 *
 * @package Core
 * @subpackage Models
 * @link http://code.google.com/p/indicia/wiki/DataModel
 */
class Comment_Quick_Reply_Page_Auth_Model extends ORM {
  protected $belongs_to = array(
    'user',
    'created_by' => 'user',
    'updated_by' => 'user',
  );

  public $search_field = 'trust';

  /**
   * Inform system which fields require validation.
   */
  public function validate(Validation $array, $save = FALSE) {
    /* Uses PHP trim() to remove whitespace from beginning
    and end of all fields before validation.*/
    $array->pre_filter('trim');
    $array->add_rules('occurrence_id', 'required');
    $array->add_rules('token', 'required');
    $values = $array->as_array();

    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = array(
      'deleted',
    );
    return parent::validate($array, $save);
  }

}
