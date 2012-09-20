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
 * Hook into the data cleaner to declare checks for the test of record time of year. 
 * @return type array of rules.
 */
function data_cleaner_period_within_year_data_cleaner_rules() {
  return array(
    'testType' => 'periodWithinYear',
    'optional' => array(
        'Metadata'=>array('Tvk','TaxonMeaningId','Taxon','StartDate','EndDate','DataFieldName','SurveyId'), 
        'Data'=>array('Stage','StartDate','EndDate')
    ),
    'queries' => array(
      // Slightly convoluted logic required in this test to get it to work with ranges in middle of year as well as ranges that span the end of the year.
      // Also note in these queries we use 2012 as the year for expanding dates that have just a month and day, as it is a leap
      // year so all dates are covered.
      // Also a warning - these queries are case-sensitive, but performance is miserable if they are made insensitive since this kills the use of indexes.
      array(
        'joins' => 
            "join verification_rule_metadata vrm ".
            "  on (vrm.value=co.taxa_taxon_list_external_key and vrm.key='Tvk')".
            "  or (vrm.value=co.preferred_taxon and vrm.key='Taxon') ".
            "  or (vrm.value=cast(co.taxon_meaning_id as character varying) and vrm.key='TaxonMeaningId') ".
            "join verification_rules vr on vr.id=vrm.verification_rule_id and vr.test_type='PeriodWithinYear' ".
            "left join verification_rule_metadata vrmstart on vrmstart.verification_rule_id=vr.id and vrmstart.key='StartDate' and length(vrmstart.value)=4 ".
            "left join verification_rule_metadata vrmend on vrmend.verification_rule_id=vr.id and vrmend.key='EndDate' and length(vrmend.value)=4 ".
            "left join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vr.id and vrsurvey.key='SurveyId' and vrsurvey.deleted=false ",
        'where' =>
            "vr.reverse_rule<>(((vrmstart is null or vrmend.value is null or vrmstart.value <= vrmend.value) ".
            "and ((vrmstart.value is not null and extract(doy from co.date_start) < extract(doy from cast('2012' || vrmstart.value as date))) ".
            "or (vrmend.value is not null and extract(doy from co.date_start) > extract(doy from cast('2012' || vrmend.value as date))))) ".
            "or ((vrmstart.value > vrmend.value) ".
            "and ((vrmstart.value is not null and extract(doy from co.date_start) < extract(doy from cast('2012' || vrmstart.value as date))) ".
            "and (vrmend.value is not null and extract(doy from co.date_start) > extract(doy from cast('2012' || vrmend.value as date)))))) ".
            "and (vrsurvey.id is null or vrsurvey.value=cast(co.survey_id as varchar))"
      ),
      array(
        // repeat the test, this time filtered by stage
        'joins' => 
            "join verification_rule_metadata vrm ".
            "  on (vrm.value=co.taxa_taxon_list_external_key and vrm.key='Tvk')".
            "  or (vrm.value=co.preferred_taxon and vrm.key='Taxon') ".
            "  or (vrm.value=cast(co.taxon_meaning_id as character varying) and vrm.key='TaxonMeaningId') ".
            "join verification_rules vr on vr.id=vrm.verification_rule_id and vr.test_type='PeriodWithinYear' ".
            "join verification_rule_data vrdstage on vrdstage.verification_rule_id=vr.id and vrdstage.key='Stage' ".
            "left join verification_rule_data vrdstart on vrdstart.verification_rule_id=vr.id and vrdstart.key='StartDate' and vrdstart.data_group=vrdstage.data_group ".
            "left join verification_rule_data vrdend on vrdend.verification_rule_id=vr.id and vrdend.key='EndDate' and vrdend.data_group=vrdstage.data_group ".
            "left join verification_rule_metadata vrsurvey on vrsurvey.verification_rule_id=vr.id and vrsurvey.key='SurveyId' and vrsurvey.deleted=false ".
            "join occurrence_attribute_values oav on oav.occurrence_id=co.id and oav.deleted=false ".
            "left join cache_termlists_terms ctt on ctt.id=oav.int_value and string_to_array(lower(vrdstage.value),',') @> string_to_array(lower(ctt.term),'') ".
            "join occurrence_attributes oa on oa.id=oav.occurrence_attribute_id and oav.deleted=false ".
            "  and lower(oa.system_function) = 'sex_stage' ", 
        'where' =>
            // This logic allows a text value, lookup value or caption of a checked boolean attribute to count as the stage to filter on.
            "(string_to_array(lower(vrdstage.value),',') @> string_to_array(lower(oav.text_value),'') ".
            "   or ctt.id is not null ".
            "   or (oa.data_type='B' and string_to_array(lower(vrdstage.value),',') @> string_to_array(lower(oa.caption),'') ".
            "      and oav.int_value=1)) ". // last 2 lines accept a checked boolean attribute with stage for the caption
            "and (((vrdstart is null or vrdend.value is null or vrdstart.value <= vrdend.value) ".
            "and ((vrdstart.value is not null and extract(doy from co.date_start) < extract(doy from cast('2012' || vrdstart.value as date))) ".
            "or (vrdend.value is not null and extract(doy from co.date_start) > extract(doy from cast('2012' || vrdend.value as date))))) ".
            "or ((vrdstart.value > vrdend.value) ".
            "and ((vrdstart.value is not null and extract(doy from co.date_start) < extract(doy from cast('2012' || vrdstart.value as date))) ".
            "and (vrdend.value is not null and extract(doy from co.date_start) > extract(doy from cast('2012' || vrdend.value as date)))))) ".
            "and (vrsurvey.id is null or vrsurvey.value=cast(co.survey_id as varchar))",
        'errorMsgSuffix' => " || ' This test was based on the record being ' || vrdstage.value || '.'"
      )
    )
  );
}

/** 
 * Taxon version keys should really be uppercase, so enforce this. Otherwise the query needs to be case insensitive which makes it slow.
 */
function data_cleaner_period_within_year_data_cleaner_postprocess($id, $db) {
  $db->query("update verification_rule_metadata set value=upper(value) where key ilike 'Tvk' and value<>upper(value) and verification_rule_id=$id");
}

?>