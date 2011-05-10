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
 * Model class for the Taxa table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Taxon_Model extends ORM {
  public static $search_field='taxon';
  protected $belongs_to = array('meaning', 'language', 'created_by' => 'user', 'updated_by' => 'user');
  protected $has_many = array('taxa_taxon_lists');
  protected $has_and_belongs_to_many = array('taxon_lists');

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('taxon', 'required');
    $array->add_rules('language_id', 'required');
    $array->add_rules('taxon_group_id', 'required');

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'external_key',
      'authority',
      'deleted',
      'search_code',
      'description'
    );
    return parent::validate($array, $save);
  }

  protected function preSubmit(){

    // Call the parent preSubmit function
    parent::preSubmit();

    // Set scientific if latin
    $l = ORM::factory('language');
    $sci = 'f';
    /*if ($l->find($this->submission['fields']['language_id']['value'])->iso == "lat") {
      $sci = 't';
    }*/
    $this->submission['fields']['scientific'] = array(
      'value' =>  $sci
    );

  }
  
  /** 
   * Set default values for a new taxon entry.   
   */
  public function getDefaults() {
    return array(
      'language_id'=>2 // latin
    );  
  }
}

