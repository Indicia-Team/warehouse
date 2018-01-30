
UPDATE THIS TO INCLUDE TVK, MEANING OR TAXON NAME LINKS
THEN UPDATE THE ACTUAL QUERIES TO GRAB THE NOTIFICATIONS

-- Non-stage linked rules
select vr.id as verification_rule_id,
 vr.reverse_rule,
 vrmkey.value as taxa_taxon_list_external_key,
 extract(doy from cast('2012' || vrmstart.value as date)) as start_date,
 extract(doy from cast('2012' || vrmend.value as date)) as end_date,
 vrmsurvey.value::integer as survey_id,
 null::varchar[] as stages
into cache_verification_rules_period_within_year
from verification_rules vr
join verification_rule_metadata vrmkey on vrmkey.verification_rule_id=vr.id and vrmkey.key ilike 'Tvk' and vrmkey.deleted=false
left join verification_rule_metadata vrmstart on vrmstart.verification_rule_id=vr.id and vrmstart.key ilike 'StartDate' and length(vrmstart.value)=4
 and vrmstart.deleted=false
left join verification_rule_metadata vrmend on vrmend.verification_rule_id=vr.id and vrmend.key ilike 'EndDate' and length(vrmend.value)=4
 and vrmend.deleted=false
left join verification_rule_metadata vrmsurvey on vrmsurvey.verification_rule_id=vr.id and vrmsurvey.key='SurveyId' and vrmsurvey.deleted=false
where vr.test_type='PeriodWithinYear'
and vr.deleted=false
and (vrmstart.id is not null or vrmend.id is not null)
union
-- Stage linked rules
select vr.id,
 vr.reverse_rule,
 vrmkey.value as taxa_taxon_list_external_key,
 extract(doy from cast('2012' || vrdstart.value as date)) as start_date,
 extract(doy from cast('2012' || vrdend.value as date)) as end_date,
 vrmsurvey.value::integer as survey_id,
 string_to_array(lower(vrdstage.value), ',') as stages
from verification_rules vr
join verification_rule_metadata vrmkey on vrmkey.verification_rule_id=vr.id and vrmkey.key ilike 'Tvk' and vrmkey.deleted=false
join verification_rule_data vrdstage on vrdstage.verification_rule_id=vr.id and vrdstage.key ilike 'Stage'
left join verification_rule_data vrdstart on vrdstart.verification_rule_id=vr.id and vrdstart.key='StartDate' and vrdstart.data_group=vrdstage.data_group
left join verification_rule_data vrdend on vrdend.verification_rule_id=vr.id and vrdend.key='EndDate' and vrdend.data_group=vrdstage.data_group
left join verification_rule_metadata vrmsurvey on vrmsurvey.verification_rule_id=vr.id and vrmsurvey.key='SurveyId' and vrmsurvey.deleted=false
where vr.test_type='PeriodWithinYear'
and vr.deleted=false;

create index ix_cache_pwy_vr_id on cache_verification_rules_period_within_year(verification_rule_id);
create index ix_cache_pwy_external_key on cache_verification_rules_period_within_year(taxa_taxon_list_external_key);
