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
            join sample_attribute_values starttime on starttime.sample_id in (sparent.id, s.id) and starttime.deleted=false
            join sample_attribute_values endtime on endtime.sample_id in (sparent.id, s.id) and endtime.deleted=false
            join verification_rule_metadata vrmstartattr on vrmstartattr.value = cast(starttime.sample_attribute_id as character varying) 
                and vrmstartattr.key='StartTimeAttr' and vrmstartattr.deleted=false
            join verification_rule_metadata vrmendattr on vrmendattr.value = cast(endtime.sample_attribute_id as character varying) and vrmendattr.key='EndTimeAttr'             
                and vrmendattr.verification_rule_id=vrmstartattr.verification_rule_id and vrmendattr.deleted=false
            join verification_rule_metadata vrmstart on vrmstart.verification_rule_id=vrmstartattr.verification_rule_id and vrmstart.key='StartTime' and vrmstart.deleted=false
            join verification_rule_metadata vrmend on vrmend.verification_rule_id=vrmstartattr.verification_rule_id and vrmend.key='EndTime' and vrmend.deleted=false
            join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vrmstartattr.verification_rule_id and vrsurvey.key='SurveyId' 
                and vrsurvey.value=cast(co.sample_id as character varying) and vrsurvey.deleted=false
            join verification_rules vr on vr.id=vrmstartattr.verification_rule_id and vr.test_type='SampleTimeAttrOutsideRange' and vr.deleted=false",
        'where' =>
          "cast(starttime.text_value as time)<cast(vrmstart.value as time) or cast(endtime.text_value as time)>cast(vrmend.value as time)"
      )
    )
  );
}

?>