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
 * Model class for the Taxon_ranks table.
 */
class Taxon_rank_Model extends ORM {

  public $search_field='rank';

  protected $has_many = array('taxa');

  protected $belongs_to = array(
    'created_by'=>'user',
    'updated_by'=>'user'
  );

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('rank', 'required', 'length[1,50]');
    $array->add_rules('short_name', 'required', 'length[1,20]');
    $this->unvalidatedFields = array('italicise_taxon', 'sort_order', 'deleted');
    return parent::validate($array, $save);
  }
}