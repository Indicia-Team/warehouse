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
 * Model class for the Sample_Attribute_Values table.
 */
class Sample_Attribute_Value_Model extends Attribute_Value_ORM {
  public $search_field='text_value';

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'sample', 'sample_attribute');

  public function validate(Validation $array, $save = FALSE) {
    self::attribute_validation($array, 'sample');
    return parent::validate($array, $save);
  }

  protected function get_survey_specific_rules($values) {
    return $this->db
            ->from('sample_attributes_websites as saw')
            ->join('samples as s', 's.survey_id', 'saw.restrict_to_survey_id')
            ->join('sample_attributes as sa', 'sa.id', 'saw.sample_attribute_id')
            ->select('saw.validation_rules, sa.allow_ranges')
            ->where(array(
              's.id' => $values['sample_id'],
              'saw.sample_attribute_id'=>$values['sample_attribute_id']
            ))
            ->limit(1)
            ->get();
  }
}
