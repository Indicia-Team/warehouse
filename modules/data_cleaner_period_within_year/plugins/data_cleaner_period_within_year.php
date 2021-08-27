<?php

/**
 * @file
 * Plugin functions for the data_cleaner_period_within_year rule.
 *
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
 * @link http://code.google.com/p/indicia/
 */

/**
 * Updates the cached copy of period within year rules.
 *
 * After saving a record, does an insert of the updated cache entry, helping
 * to impprove performance.
 */
function data_cleaner_period_within_year_cache_sql() {
  return <<<SQL
insert into cache_verification_rules_period_within_year
select vr.id as verification_rule_id,
  vr.reverse_rule,
  coalesce(vrmkey.value, cttltaxon.external_key, cttlmeaning.external_key) as taxa_taxon_list_external_key,
  extract(doy from cast('2012' || vrmstart.value as date)) as start_date,
  extract(doy from cast('2012' || vrmend.value as date)) as end_date,
  vrmsurvey.value::integer as survey_id,
  null::text[] as stages,
  vr.error_message
from verification_rules vr
left join verification_rule_metadata vrmkey on vrmkey.verification_rule_id=vr.id
  and vrmkey.key ilike 'Tvk' and vrmkey.deleted=false
left join verification_rule_metadata vrmtaxon on vrmtaxon.verification_rule_id=vr.id
  and vrmtaxon.key='Taxon' and vrmtaxon.deleted=false
left join cache_taxa_taxon_lists cttltaxon on cttltaxon.preferred_taxon=vrmtaxon.value and cttltaxon.preferred=true
left join verification_rule_metadata vrmmeaning on vrmmeaning.verification_rule_id=vr.id
  and vrmmeaning.key='TaxonMeaningId' and vrmmeaning.deleted=false
left join cache_taxa_taxon_lists cttlmeaning on cttlmeaning.taxon_meaning_id=vrmmeaning.value::integer and cttlmeaning.preferred=true
left join verification_rule_metadata vrmstart on vrmstart.verification_rule_id=vr.id and vrmstart.key ilike 'StartDate' and length(vrmstart.value)=4
  and vrmstart.deleted=false
left join verification_rule_metadata vrmend on vrmend.verification_rule_id=vr.id and vrmend.key ilike 'EndDate' and length(vrmend.value)=4
  and vrmend.deleted=false
left join verification_rule_metadata vrmsurvey on vrmsurvey.verification_rule_id=vr.id and vrmsurvey.key='SurveyId' and vrmsurvey.deleted=false
where vr.test_type='PeriodWithinYear'
  and vr.deleted=false
  and (vrmstart.id is not null or vrmend.id is not null)
  and vr.id=#id#
union
select vr.id as verification_rule_id,
  vr.reverse_rule,
  coalesce(vrmkey.value, cttltaxon.external_key, cttlmeaning.external_key) as taxa_taxon_list_external_key,
  extract(doy from cast('2012' || vrstart.value as date)) as start_date,
  extract(doy from cast('2012' || vrend.value as date)) as end_date,
  vrmsurvey.value::integer as survey_id,
  string_to_array(lower(vrdstage.value), ',') as stages,
  vr.error_message
from verification_rules vr
left join verification_rule_metadata vrmkey on vrmkey.verification_rule_id=vr.id
  and vrmkey.key ilike 'Tvk' and vrmkey.deleted=false
left join verification_rule_metadata vrmtaxon on vrmtaxon.verification_rule_id=vr.id
  and vrmtaxon.key='Taxon' and vrmtaxon.deleted=false
left join cache_taxa_taxon_lists cttltaxon on cttltaxon.taxon=vrmtaxon.value and cttltaxon.preferred=true
left join verification_rule_metadata vrmmeaning on vrmmeaning.verification_rule_id=vr.id
  and vrmmeaning.key='TaxonMeaningId' and vrmmeaning.deleted=false
left join cache_taxa_taxon_lists cttlmeaning on cttlmeaning.taxon_meaning_id=vrmmeaning.value::integer and cttlmeaning.preferred=true
join verification_rule_data vrdstage on vrdstage.verification_rule_id=vr.id and vrdstage.key ilike 'Stage'
left join verification_rule_data vrstart on vrstart.verification_rule_id=vr.id and vrstart.key ilike 'StartDate' and length(vrstart.value)=4
  and vrstart.deleted=false and vrstart.data_group=vrdstage.data_group
left join verification_rule_data vrend on vrend.verification_rule_id=vr.id and vrend.key ilike 'EndDate' and length(vrend.value)=4
  and vrend.deleted=false and vrend.data_group=vrdstage.data_group
left join verification_rule_data vrmsurvey on vrmsurvey.verification_rule_id=vr.id and vrmsurvey.key='SurveyId' and vrmsurvey.deleted=false
where vr.test_type='PeriodWithinYear'
  and vr.deleted=false
  and (vrstart.id is not null or vrend.id is not null)
  and vr.id=#id#;
SQL;
}

/**
 * Hook into the data cleaner to declare checks for the test of record time of year.
 *
 * @return array
 *   Array of rules.
 */
function data_cleaner_period_within_year_data_cleaner_rules() {
  $joinSql = <<<SQL
join cache_verification_rules_period_within_year vr on vr.taxa_taxon_list_external_key=co.taxa_taxon_list_external_key
-- join to find similar accepted records to the one we are scanning
left join (cache_occurrences_functional o2
  -- join to find the stage of the potentially similar record
  join cache_occurrences_nonfunctional onf2 on onf2.id=o2.id
) on o2.id<>co.id
  -- same species
  and o2.taxa_taxon_list_external_key=co.taxa_taxon_list_external_key
  -- accepted
  and o2.record_status='V'
  -- within 7 days of the day in year
  and abs(extract(doy from o2.date_start) - extract(doy from co.date_start)) < 7
  -- nearby
  and o2.map_sq_10km_id=co.map_sq_10km_id
  -- the compared record is only used if it matches the rule stage if one is specified
  and (vr.stages @> string_to_array(lower(coalesce(onf2.attr_stage, onf2.attr_sex_stage)),'') or vr.stages is null)
  and (co.stage is null or lower(coalesce(onf2.attr_stage, onf2.attr_sex_stage)) = co.stage)
SQL;
  $whereSql = <<<SQL
vr.reverse_rule<>(
  (
    (vr.start_date is null or vr.end_date is null or vr.start_date <= vr.end_date)
    and (
      (vr.start_date is not null and extract(doy from co.date_start) < vr.start_date)
      or
      (vr.end_date is not null and extract(doy from co.date_start) > vr.end_date)
    )
  )
  or (
    (vr.start_date > vr.end_date)
    and (
      (vr.start_date is not null and extract(doy from co.date_start) < vr.start_date)
      and
      (vr.end_date is not null and extract(doy from co.date_start) > vr.end_date)
    )
  )
)
-- limit to survey if the rule says so
and (coalesce(vr.survey_id, co.survey_id) = co.survey_id)
-- the rule is only used if it matches the compared record stage, or doesn't specify one.
and (vr.stages is null or vr.stages @> string_to_array(co.stage, ''))
SQL;
  // The groupBy allows us to count the verified records at a similar time of
  // year and only create messages if less than 6.
  $groupBy = <<<SQL
group by co.id, co.date_start, co.taxa_taxon_list_external_key, co.stage,
  co.verification_checks_enabled, co.record_status, vr.error_message, vr.stages
-- at least 6 similar records
having count(o2.id) < 6
SQL;

  return [
    'testType' => 'periodWithinYear',
    'optional' => [
      'Metadata' => [
        'Tvk',
        'TaxonMeaningId',
        'Taxon',
        'StartDate',
        'EndDate',
        'DataFieldName',
        'SurveyId',
      ],
      'Data' => [
        'Stage',
        'StartDate',
        'EndDate',
      ],
    ],
    // Slightly convoluted logic required in this test to get it to work with
    // ranges in middle of year as well as ranges that span the end of the
    // year. Also note in these queries we use 2012 as the year for expanding
    // dates that have just a month and day, as it is a leap year so all dates
    // are covered.
    // Also a warning - these queries are case-sensitive, but performance is
    // miserable if they are made insensitive since this kills the use of
    // indexes. The overall performance is also better with 3 simpler queries
    // than one complex one.

    'queries' => [
      [
        // Query 1 - TVK linked rules.
        'joins' => $joinSql,
        'where' => $whereSql,
        'groupBy' => $groupBy,
        'errorMsgSuffix' => " || case when vr.stages is null then '' else ' This test was based on the record being ' || co.stage || '.' end",
      ],
    ],
  ];
}

/**
 * Taxon version keys should really be uppercase, so enforce this.
 *
 * Otherwise the query needs to be case insensitive which makes it slow.
 */
function data_cleaner_period_within_year_data_cleaner_postprocess($id, $db) {
  $db->query("update verification_rule_metadata set value=upper(value) where key ilike 'Tvk' and value<>upper(value) and verification_rule_id=$id");
}
