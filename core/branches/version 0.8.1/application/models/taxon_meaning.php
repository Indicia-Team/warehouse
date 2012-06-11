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
 * @package  Core
 * @subpackage Models
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

/**
 * Model class for the Taxon_Meanings table.
 *
 * @package  Core
 * @subpackage Models
 * @link  http://code.google.com/p/indicia/wiki/DataModel
 */
class Taxon_meaning_Model extends ORM {
  public $search_field='id';

  protected $has_many = array(
      'taxa_taxon_lists',
      'taxon_codes'
    );

  public function insert(){
    $nextval = $this->db->query("SELECT nextval('taxon_meanings_id_seq'::regclass)")
      ->current()->nextval;
    $this->id = $nextval;
     return $this->save();
  }

  public function validate(Validation $array, $save = FALSE){
    if (!$this->id) {
      $this->insert();
    }
    return true;
  }

}
