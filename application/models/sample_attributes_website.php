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
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Model class for the Sample_Attributes_Websites table.
 */
class Sample_attributes_website_Model extends Base_Attributes_With_Taxon_Restrictions_Model {
  protected $has_one = array(
    'sample_attribute',
    'website',
  );

  protected $belongs_to = array(
    'created_by' => 'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $this->unvalidatedFields = array(
      'sample_attribute_id',
      'website_id',
      'restrict_to_survey_id',
      'default_text_value',
      'default_float_value',
      'default_int_value',
      'default_upper_value',
      'default_date_start_value',
      'default_date_end_value',
      'default_date_type_value',
      'control_type_id',
      'restrict_to_sample_method_id',
    );
    return parent::validate($array, $save);
  }

  /**
   * Return a displayable caption for the item.
   */
  public function caption() {
    if ($this->id) {
      return ($this->sample_attribute != NULL ? $this->sample_attribute->caption : '');
    }
    else {
      return 'Sample Attribute';
    }
  }

  /**
   * Map a default_value virtual field  onto the relevant default value fields.
   *
   * Mapping depends on the data type.
   */
  protected function preSubmit() {
    if (isset($this->submission['fields']['default_value']['value'])) {
      $attr = ORM::factory('sample_attribute', $this->submission['fields']['sample_attribute_id']['value']);
      $this->setSubmissionAttrValue($this->submission['fields']['default_value']['value'], $attr->data_type, 'default_');
    }
    return parent::presubmit();
  }

  /**
   * Handle saving any taxon restrictions.
   *
   * After saving, if the posting form was the warehouse attributes_in_survey
   * edit form then it may have information about restrictions for this
   * attribute's use according to the chosen taxa. Ensure this is persisted to
   * the database.
   *
   * @param bool $isInsert
   *   True if the post is an insert, false for update.
   *
   * @return bool
   *   Return TRUE allowing the transaction to commit.
   */
  protected function postSubmit($isInsert) {
    self::postSubmitSaveTaxonRestrictions($isInsert, 'sample');
    return TRUE;
  }

  /**
   * Create a virtual field called default_value from the relevant default value fields, depending on the data type.
   */
  public function __get($column) {
    if ($column === 'default_value') {
      $attr = ORM::factory('sample_attribute', $this->sample_attribute_id);
      switch ($attr->data_type) {
        case 'T':
          return parent::__get('default_text_value');

        case 'F':
          return parent::__get('default_float_value');

        case 'I':
        case 'L':
          return parent::__get('default_int_value');

        case 'D':
        case 'V':
          $vagueDate = array(
            parent::__get('default_date_start_value'),
            parent::__get('default_date_end_value'),
            parent::__get('default_date_type_value'),
          );
          return vague_date::vague_date_to_string($vagueDate);
      }
    }
    else {
      return parent::__get($column);
    }
  }

}
