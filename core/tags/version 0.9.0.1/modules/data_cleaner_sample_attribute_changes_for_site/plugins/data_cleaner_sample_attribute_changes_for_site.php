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
 * Hook into the data cleaner to declare checks for a sample attribute which describes something about a site that should not really 
 * change. If there are 2 samples with the tested attribute but different values, then the 2nd will fail.
 * @return type array of rules.
 */
function data_cleaner_sample_attribute_changes_for_site_data_cleaner_rules() {
  return array(
    'testType' => 'SampleAttributeChangesForSite',
    'required' => array('Metadata'=>array('SampleAttr','SurveyId')),
    'queries' => array(
      array(
        'joins' => 
              "join samples s on s.id=co.sample_id and s.deleted=false
              left join samples sp on sp.id=s.parent_id and sp.deleted=false
              join sample_attribute_values sav on sav.sample_id in (s.id, sp.id) and sav.deleted=false
              join verification_rule_metadata vrm on vrm.value = cast(sav.sample_attribute_id as character varying) and vrm.key='SampleAttr' 
                  and vrm.deleted=false
              join sample_attribute_values savprev on savprev.sample_attribute_id=sav.sample_attribute_id and savprev.deleted=false
                  and (coalesce(savprev.text_value, savprev.float_value::bpchar, savprev.int_value::bpchar) <> coalesce(sav.text_value, sav.float_value::bpchar, sav.int_value::bpchar) or
                    coalesce(savprev.date_start_value, '1000-01-01')<>coalesce(sav.date_start_value, '1000-01-01') or
                    coalesce(savprev.date_end_value, '1000-01-01')<>coalesce(sav.date_end_value, '1000-01-01') or
                    coalesce(savprev.date_type_value, '')<>coalesce(sav.date_type_value, ''))
              join samples sprev on sprev.id=savprev.sample_id and sprev.survey_id=s.survey_id and sprev.location_id in (s.location_id, sp.location_id)
              join verification_rules vr on vr.id=vrm.verification_rule_id and vr.test_type='SampleAttributeChangesForSite' and vr.deleted=false
              join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vr.id and vrsurvey.key='SurveyId' 
                  and vrsurvey.value=cast(co.survey_id as character varying) and vrsurvey.deleted=false"
      )
    )
  );
}

?>