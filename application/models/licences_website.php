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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the Licences_Websites table.
 */
class Licences_Website_Model extends ORM
{

  protected $belongs_to = array(
    'licence',
    'website',
    'created_by'=>'user',
    'updated_by'=>'user'
  );

  /**
   * Validate and save the data.
   */
  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('licence_id', 'required');
    $array->add_rules('website_id', 'required');
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'deleted'
    );

    return parent::validate($array, $save);
  }

  public function caption()
  {
    if ($this->id) {
      return 'Licence '.$this->licence->title.' for '.$this->website->title;
    } else {
      return $this->getNewItemCaption();
    }
  }

}