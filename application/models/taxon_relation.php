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
 * Model class for the Taxon_Relations table.
 */
class Taxon_Relation_Model extends ORM {
  public $search_field = 'id';

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user',
    'from_taxon_meaning' => 'taxon_meaning',
    'to_taxon_meaning' => 'taxon_meaning',
    'taxon_relation_type',
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('from_taxon_meaning_id', 'required');
    $array->add_rules('to_taxon_meaning_id', 'required');
    $array->add_rules('taxon_relation_type_id', 'required');

    $this->unvalidatedFields = array('deleted');
    return parent::validate($array, $save);
  }

}
