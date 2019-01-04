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
 * Model class for the occurrence_attributes_taxa_taxon_list_attributes table.
 *
 * @link https://github.com/indicia-team/warehouse/wiki/DataModel
 */
class Occurrence_attributes_taxa_taxon_list_attribute_Model extends ORM {

  protected $has_one = [
    'occurrence_attribute',
    'taxa_taxon_list_attribute',
  ];

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('occurrence_attribute_id', 'integer');
    // Occurrence attribute ID not required as it will be filled in by a trigger.
    $array->add_rules('taxa_taxon_list_attribute_id', 'integer');
    $array->add_rules('taxa_taxon_list_attribute_id', 'required');
    $this->unvalidatedFields = array(
      'restrict_occurrence_attribute_to_single_value',
      'validate_occurrence_attribute_values_against_taxon_values',
    );

    return parent::validate($array, $save);
  }

  private function getValue($field) {
    return array_key_exists($field, $this->submission['fields']) ?
      $this->submission['fields'][$field]['value'] : $this->$field;
  }

  public function preSubmit() {
    // If submitting a link from a taxon attribute to an occcurrence
    // attribute, then create the missing occurrence attribute, or update the
    // existing one.
    $oa = ORM::factory('occurrence_attribute');
    $ttla = ORM::factory('taxa_taxon_list_attribute', $this->getValue('taxa_taxon_list_attribute_id'));
    if (attribute_sets::isLinkedAttributeRequired($ttla)) {
      $s = [
        'caption' => attribute_sets::removePercentiles($ttla->allow_ranges === 't', $ttla->caption),
        'data_type' => $ttla->data_type,
        'validation_rules' => $ttla->validation_rules,
        'termlist_id' => $ttla->termlist_id,
        'multi_value' => $this->getValue('restrict_occurrence_attribute_to_single_value') === 't'
          ? 'f' : $ttla->multi_value,
        'public' => $ttla->public,
        'system_function' => $ttla->system_function,
        'source_id' => $ttla->source_id,
        'caption_i18n' => attribute_sets::removePercentiles($ttla->allow_ranges === 't', $ttla->caption_i18n),
        'term_name' => $ttla->term_name,
        'term_identifier' => $ttla->term_identifier,
        'allow_ranges' => $this->getValue('restrict_occurrence_attribute_to_single_value') === 't'
          ? 'f' : $ttla->allow_ranges,
        'unit' => $ttla->unit,
        'image_path' => $ttla->image_path,
      ];
      // Force an update if it already exists.
      if (!empty($this->getValue('occurrence_attribute_id'))) {
        $s['id'] = $this->getValue('occurrence_attribute_id');
      }
      $oa->set_submission_data($s);
      $oa->submit();
      $this->submission['fields']['occurrence_attribute_id']['value'] = $oa->id;
    }
    return parent::preSubmit();
  }

  /**
   * After submission, ensure that any changes are reflected in the core model.
   *
   * E.g. if a taxa_taxon_list_attribute which is in an attribute set is also
   * linked to an occurrence attribute, then the occurrence attribute will also
   * be linked to the survey.
   */
  public function postSubmit($isInsert) {
    attribute_sets::updateSetLinks($this->db, $this);
    return TRUE;
  }

}
