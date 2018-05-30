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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the sample_attributes_taxa_taxon_list_attributes table.
 *
 * @link https://github.com/indicia-team/warehouse/wiki/DataModel
 */
class Sample_attributes_taxa_taxon_list_attribute_Model extends ORM {

  protected $belongs_to = array(
    'sample_attribute',
    'taxa_taxon_list_attribute',
    'created_by' => 'user',
    'updated_by' => 'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('sample_attribute_id', 'integer');
    // Sample attribute ID not required as it will be filled in by a trigger.
    $array->add_rules('taxa_taxon_list_attribute_id', 'integer');
    $array->add_rules('taxa_taxon_list_attribute_id', 'required');
    $this->unvalidatedFields = array(
      'restrict_sample_attribute_to_single_value',
      'validate_sample_attribute_values_against_taxon_values',
    );

    return parent::validate($array, $save);
  }

  public function postSubmit($isInsert) {
    attribute_sets::updateSetLinks($this->db, $this);
    return TRUE;
  }

}
