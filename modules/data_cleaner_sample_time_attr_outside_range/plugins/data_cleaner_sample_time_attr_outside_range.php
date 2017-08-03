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
 * Hook into the data cleaner to declare checks for the test of a sample time being outside a range. For example you might want 
 * to set up a form which allows input of a wide range of times, but define that those early or late in the day need thorough checking.
 * Requires 
 * - verification checks enabled for the website
 * - metadata for StartTime and EndTime (hh:mm)
 * - metadata for StartTimeAttr and EndTimeAttr (pointing to attribute IDs).
 * - metadata for SurveyId
 * - custom attribute setup to capture text times (hh:mm), e.g. use a regexp to force the correct format such as /^((2[0-3])|([0,1][0-9])):[0-5][0-9]$/. 
 *   You can either have just 1 attribute, in which case set StartTimeAttr and EndTimeAttr to the same, or 2 attributes for the start and end time.
 * @return type array of rules.
 */
function data_cleaner_sample_time_attr_outside_range_data_cleaner_rules() {
  return 
    array(
    'testType' => 'SampleTimeAttrOutsideRange',
    'required' => array('Metadata'=>array('StartTime','EndTime','StartTimeAttr','EndTimeAttr','SurveyId')),
    'queries' => array(
      array(
        'joins' => 
          "join samples s  on s.id=co.sample_id and s.deleted=false
            left join samples sparent on sparent.id=s.parent_id and sparent.deleted=false
            join sample_attribute_values vtime on vtime.sample_id in (sparent.id, s.id) and vtime.deleted=false
            join verification_rule_metadata vrmattr on vrmattr.value = cast(vtime.sample_attribute_id as character varying) 
                and vrmattr.key in ('StartTimeAttr', 'EndTimeAttr') and vrmattr.deleted=false
            join verification_rule_metadata vrm on vrm.verification_rule_id=vrmattr.verification_rule_id 
                and vrm.key='StartTime' and vrm.deleted=false
            join verification_rules vr on vr.id=vrmattr.verification_rule_id and vr.test_type='SampleTimeAttrOutsideRange' and vr.deleted=false
            join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vr.id and vrsurvey.key='SurveyId' 
                and vrsurvey.value=cast(co.survey_id as character varying) and vrsurvey.deleted=false",
        'where' =>
          "vtime.text_value<>''
         and regexp_replace(vtime.text_value, '^(([0-1][0-9])|2[0-3]):[0-5][0-9]', '')=''
         and cast(vtime.text_value as time)<cast(vrm.value as time)"
      ),
      array(
        'joins' => 
          "join samples s  on s.id=co.sample_id and s.deleted=false
            left join samples sparent on sparent.id=s.parent_id and sparent.deleted=false
            join sample_attribute_values vtime on vtime.sample_id in (sparent.id, s.id) and vtime.deleted=false
            join verification_rule_metadata vrmattr on vrmattr.value = cast(vtime.sample_attribute_id as character varying) 
                and vrmattr.key in ('StartTimeAttr', 'EndTimeAttr') and vrmattr.deleted=false
            join verification_rule_metadata vrm on vrm.verification_rule_id=vrmattr.verification_rule_id 
                and vrm.key='EndTime' and vrm.deleted=false
            join verification_rules vr on vr.id=vrmattr.verification_rule_id and vr.test_type='SampleTimeAttrOutsideRange' and vr.deleted=false
            join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vr.id and vrsurvey.key='SurveyId' 
                and vrsurvey.value=cast(co.survey_id as character varying) and vrsurvey.deleted=false",
        'where' =>
          " vtime.text_value<>''
         and regexp_replace(vtime.text_value, '^(([0-1][0-9])|2[0-3]):[0-5][0-9]', '')=''
         and cast(vtime.text_value as time)>cast(vrm.value as time)"
      )
    )
  );
}