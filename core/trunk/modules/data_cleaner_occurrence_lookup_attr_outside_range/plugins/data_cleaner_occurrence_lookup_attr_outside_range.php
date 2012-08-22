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
 * Hook into the data cleaner to declare checks for the test of a lookup being outside a range in an occurrence custom attribute. For example you might want 
 * to set up a form which allows input of a wide range of values, but define that particularly low or high values need thorough checking. Relies on the 
 * termlist entries' sort order to define the sequence of the attribute values.
 * Requires 
 * - verification checks enabled for the website
 * - metadata for Low and High (optional, numbers. Will be compared with the term sort_orders to define the passing and failing terms.)
 * - metadata for JoinMethod - either meaning_id or termlists_term_id to join to the term via the meaning_id or the termlists_term_id. Default
 *     is termlists_term_id as meaning_id is only used in some special case multi-lingual surveys to simplify localisation of terms. 
 * - metadata for Attr (pointing to attribute ID, attribute must be lookup).
 * - metadata for SurveyId
 * @return type array of rules.
 */
function data_cleaner_occurrence_lookup_attr_outside_range_data_cleaner_rules() {
  return 
    array(
    'testType' => 'SampleLookupAttrOutsideRange',
    'required' => array('Metadata'=>array('SurveyId','Attr','JoinMethod')),
    'optional' => array('Metadata'=>array('Low','High')),
    'queries' => array(
      array(
        'joins' => 
          "join occurrence_attribute_values val on val.occurrence_id=co.id
            join verification_rule_metadata vrmattr on vrmattr.value = cast(val.occurrence_attribute_id as character varying) and vrmattr.key='Attr' and vrmattr.deleted=false
            left join verification_rule_metadata vrmjoinmethod on vrmjoinmethod.verification_rule_id=vrmattr.verification_rule_id 
              and vrmjoinmethod.key='JoinMethod' and vrmjoinmethod.value='meaning_id' and vrmjoinmethod.deleted=false
            left join verification_rule_metadata vrmlow on vrmlow.verification_rule_id=vrmattr.verification_rule_id and vrmlow.key='Low' and vrmlow.deleted=false
            left join verification_rule_metadata vrmhigh on vrmhigh.verification_rule_id=vrmattr.verification_rule_id and vrmhigh.key='High' and vrmhigh.deleted=false
            join termlists_terms tlt on ((tlt.id=val.int_value and vrmjoinmethod.id is null) or (tlt.meaning_id=val.int_value and vrmjoinmethod.id is not null)) and tlt.deleted=false
            join verification_rules vr on vr.id=vrmattr.verification_rule_id and vr.test_type='OccurrenceLookupAttrOutsideRange' and vr.deleted=false
            join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vr.id and vrsurvey.key='SurveyId' 
                and vrsurvey.value=cast(co.survey_id as character varying) and vrsurvey.deleted=false",
        'where' =>
          "tlt.sort_order<cast(vrmlow.value as float) or tlt.sort_order>cast(vrmhigh.value as float)"
      )
    )
  );
}

?>