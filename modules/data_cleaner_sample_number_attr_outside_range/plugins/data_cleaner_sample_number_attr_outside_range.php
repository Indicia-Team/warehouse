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
 * @package	Data Cleaner
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the data cleaner to declare checks for the test of a number being outside a range in a sample custom attribute. For example you might want 
 * to set up a form which allows input of a wide range of number, but define that particularly low or high values need thorough checking.
 * Requires 
 * - verification checks enabled for the website
 * - metadata for Low and High (optional, numbers)
 * - metadata for Attr (pointing to attribute ID, attribute must be integer or float).
 * - metadata for SurveyId
 * @return type array of rules.
 */
function data_cleaner_sample_number_attr_outside_range_data_cleaner_rules() {
  return 
    array(
    'testType' => 'SampleNumberAttrOutsideRange',
    'required' => array('Metadata'=>array('SurveyId','Attr')),
    'optional' => array('Metadata'=>array('Low','High')),
    'queries' => array(
      array(
        'joins' => 
          "join samples s  on s.id=co.sample_id and s.deleted=false
            left join samples sparent on sparent.id=s.parent_id and sparent.deleted=false
            join sample_attribute_values val on val.sample_id in (sparent.id, s.id) and val.deleted=false
            join verification_rule_metadata vrmattr on vrmattr.value = cast(val.sample_attribute_id as character varying) and vrmattr.key='Attr' and vrmattr.deleted=false
            left join verification_rule_metadata vrmlow on vrmlow.verification_rule_id=vrmattr.verification_rule_id and vrmlow.key='Low' and vrmlow.deleted=false
            left join verification_rule_metadata vrmhigh on vrmhigh.verification_rule_id=vrmattr.verification_rule_id and vrmhigh.key='High' and vrmhigh.deleted=false
            join verification_rules vr on vr.id=vrmattr.verification_rule_id and vr.test_type='SampleNumberAttrOutsideRange' and vr.deleted=false",
        'where' =>
          "coalesce(val.int_value, val.float_value)<cast(vrmlow.value as float) or coalesce(val.int_value, val.float_value)>cast(vrmhigh.value as float)"
      )
    )
  );
}

?>