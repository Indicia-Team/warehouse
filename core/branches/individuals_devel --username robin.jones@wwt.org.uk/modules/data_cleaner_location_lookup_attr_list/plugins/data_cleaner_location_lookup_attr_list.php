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
 * Hook into the data cleaner to declare checks for the test of a location attribute lookup being in or out of a list of provided preferred terms.
 * This assumes the location is linked to the sample by the location_id when it identifies the records to flag up.
 * Rule reversal is supported. If not reversed then values not in the list cause the record to be flagged. If reversed then items in the list
 * cause the record to be flagged.
 * Requires 
 * - verification checks enabled for the website
 * - data for Terms (list of values). Will be compared to the preferred term from the lookup.
 * - metadata for JoinMethod - either meaning_id or termlists_term_id to join to the term via the meaning_id or the termlists_term_id. Default
 *     is termlists_term_id as meaning_id is only used in some special case multi-lingual surveys to simplify localisation of terms. 
 * - metadata for Attr (pointing to attribute ID, attribute must be lookup).
 * - metadata for SurveyId
 * @return type array of rules.
 */
function data_cleaner_location_lookup_attr_list_data_cleaner_rules() {
  return 
    array(
    'testType' => 'LocationLookupAttrList',
    'required' => array('Metadata'=>array('SurveyId','Attr','JoinMethod'),
                        'Terms'=>array('*')),
    'queries' => array(
      array(
        'joins' => 
          "join samples s  on s.id=co.sample_id and s.deleted=false
            join locations l on l.id=s.location_id and l.deleted=false
            join location_attribute_values val on val.location_id = l.id and val.deleted=false
            join verification_rule_metadata vrmattr on vrmattr.value = cast(val.location_attribute_id as character varying) and vrmattr.key='Attr' and vrmattr.deleted=false
            left join verification_rule_metadata vrmjoinmethod on vrmjoinmethod.verification_rule_id=vrmattr.verification_rule_id 
              and vrmjoinmethod.key='JoinMethod' and vrmjoinmethod.value='meaning_id' and vrmjoinmethod.deleted=false
            join cache_termlists_terms tlt on ((tlt.id=val.int_value and vrmjoinmethod.id is null) or (tlt.meaning_id=val.int_value and vrmjoinmethod.id is not null)) 
            join verification_rules vr on vr.id=vrmattr.verification_rule_id and vr.test_type='LocationLookupAttrList' and vr.deleted=false
            join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vr.id and vrsurvey.key='SurveyId' 
                and vrsurvey.value=cast(co.survey_id as character varying) and vrsurvey.deleted=false
            left join verification_rule_data vrd on vrd.verification_rule_id=vr.id and vrd.deleted=false 
               and upper(vrd.key)=upper(tlt.term) and upper(vrd.header_name)='TERMS'",
        'where' =>
          "((vr.reverse_rule and vrd.id is not null) or (not vr.reverse_rule and vrd.id is null))"
      )
    )
  );
}

?>