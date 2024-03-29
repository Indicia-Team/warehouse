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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Model class for the Occurrence_Attribute_Values table.
 */
class Occurrence_Attribute_Value_Model extends Attribute_Value_ORM {
  public $search_field='text_value';

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'occurrence', 'occurrence_attribute');

  public function validate(Validation $array, $save = FALSE) {
    self::attribute_validation($array, 'occurrence');
    return parent::validate($array, $save);
  }

  protected function get_survey_specific_rules($values) {
    return $this->db
            ->from('occurrence_attributes_websites as oaw')
            ->join('samples as s', 's.survey_id', 'oaw.restrict_to_survey_id')
            ->join('occurrences as o', 'o.sample_id', 's.id')
            ->join('occurrence_attributes as oa', 'oa.id', 'oaw.occurrence_attribute_id')
            ->select('oaw.validation_rules, oa.allow_ranges')
            ->where(array(
              'o.id' => $values['occurrence_id'],
              'oaw.occurrence_attribute_id'=>$values['occurrence_attribute_id']
            ))
            ->limit(1)
            ->get();
  }
}
