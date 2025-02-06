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
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the attribute_sets_taxon_restrictions table.
 *
 * @link https://github.com/indicia-team/warehouse/wiki/DataModel
 */
class Attribute_sets_taxon_restriction_Model extends ORM {

  protected $belongs_to = array(
    'attribute_sets_survey',
    'restrict_to_taxon_meaning' => 'taxon_meaning',
    'restrict_to_stage_term_meaning' => 'meaning',
    'created_by' => 'user',
    'updated_by' => 'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('attribute_sets_survey_id', 'integer');
    $array->add_rules('attribute_sets_survey_id', 'required');
    $array->add_rules('restrict_to_taxon_meaning_id', 'integer');
    $array->add_rules('restrict_to_taxon_meaning_id', 'required');
    $array->add_rules('restrict_to_stage_term_meaning_id', 'integer');
    return parent::validate($array, $save);
  }

  /**
   * After submission, ensure that any changes are reflected in the core model.
   *
   * E.g. if an attribute set is linked to a taxa_taxon_list_attribute which is
   * also linked to an occurrence attribute, then the occurrence attribute will
   * also be linked to the survey.
   */
  public function postSubmit($isInsert) {
    attribute_sets::updateSetLinks($this->db, $this);
    return TRUE;
  }

}
