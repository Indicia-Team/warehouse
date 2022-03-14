-- #slow script#

-- Non-stage linked rules
drop table if exists cache_verification_rules_period_within_year;

select vr.id as verification_rule_id,
  vr.reverse_rule,
  coalesce(vrmkey.value, cttltaxon.external_key, cttlmeaning.external_key) as taxa_taxon_list_external_key,
  extract(doy from cast('2012' || vrmstart.value as date)) as start_date,
  extract(doy from cast('2012' || vrmend.value as date)) as end_date,
  vrmsurvey.value::integer as survey_id,
  null::text[] as stages,
  vr.error_message
into cache_verification_rules_period_within_year
from verification_rules vr
left join verification_rule_metadata vrmkey on vrmkey.verification_rule_id=vr.id
  and vrmkey.key ilike 'Tvk' and vrmkey.deleted=false
left join verification_rule_metadata vrmtaxon on vrmtaxon.verification_rule_id=vr.id
  and vrmtaxon.key='Taxon' and vrmtaxon.deleted=false
left join cache_taxa_taxon_lists cttltaxon on cttltaxon.preferred_taxon=vrmtaxon.value and cttltaxon.preferred=true
left join verification_rule_metadata vrmmeaning on vrmmeaning.verification_rule_id=vr.id
  and vrmmeaning.key='TaxonMeaningId' and vrmmeaning.deleted=false
left join cache_taxa_taxon_lists cttlmeaning on cttltaxon.taxon_meaning_id=vrmmeaning.value::integer and cttlmeaning.preferred=true
left join verification_rule_metadata vrmstart on vrmstart.verification_rule_id=vr.id and vrmstart.key ilike 'StartDate' and length(vrmstart.value)=4
  and vrmstart.deleted=false
left join verification_rule_metadata vrmend on vrmend.verification_rule_id=vr.id and vrmend.key ilike 'EndDate' and length(vrmend.value)=4
  and vrmend.deleted=false
left join verification_rule_metadata vrmsurvey on vrmsurvey.verification_rule_id=vr.id and vrmsurvey.key='SurveyId' and vrmsurvey.deleted=false
where vr.test_type='PeriodWithinYear'
  and vr.deleted=false
  and (vrmstart.id is not null or vrmend.id is not null)
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
left join cache_taxa_taxon_lists cttlmeaning on cttltaxon.taxon_meaning_id=vrmmeaning.value::integer and cttlmeaning.preferred=true
join verification_rule_data vrdstage on vrdstage.verification_rule_id=vr.id and vrdstage.key ilike 'Stage'
left join verification_rule_data vrstart on vrstart.verification_rule_id=vr.id and vrstart.key ilike 'StartDate' and length(vrstart.value)=4
  and vrstart.deleted=false
left join verification_rule_data vrend on vrend.verification_rule_id=vr.id and vrend.key ilike 'EndDate' and length(vrend.value)=4
  and vrend.deleted=false
left join verification_rule_data vrmsurvey on vrmsurvey.verification_rule_id=vr.id and vrmsurvey.key='SurveyId' and vrmsurvey.deleted=false
where vr.test_type='PeriodWithinYear'
  and vr.deleted=false
  and (vrstart.id is not null or vrend.id is not null);

create index ix_cache_pwy_vr_id on cache_verification_rules_period_within_year(verification_rule_id);
create index ix_cache_pwy_external_key on cache_verification_rules_period_within_year(taxa_taxon_list_external_key);
