CREATE OR REPLACE FUNCTION f_fixup_uksi_links_data_cleaner()
  RETURNS boolean AS
$BODY$
BEGIN
-- Function which tidies the links to UKSI after an update, e.g. to ensure that
-- verification rules still point to the accepted version of a name.

-- Fix the data cleaner module rule data.
UPDATE verification_rule_metadata vrm
SET value=t.external_key
FROM taxa t
JOIN taxa_taxon_lists ttl ON ttl.taxon_id=t.id
  AND ttl.taxon_list_id=(select uksi_taxon_list_id from uksi.uksi_settings)
  AND ttl.deleted=false AND ttl.allow_data_entry=true
WHERE (vrm.key ilike 'Tvk' OR vrm.key ilike 'DataRecordId')
AND t.search_code=vrm.value
AND t.external_key<>vrm.value
AND t.deleted=false;

UPDATE verification_rule_data vrd
SET key=t.external_key
FROM taxa t
JOIN taxa_taxon_lists ttl ON ttl.taxon_id=t.id AND ttl.taxon_list_id=15 AND ttl.deleted=false
WHERE vrd.header_name ilike 'Data'
AND t.search_code=vrd.key
AND t.external_key<>t.search_code
AND t.deleted=false;

-- Refresh verification rule cache tables.

-- cache_verification_rules_identification_difficulty
create temp table cache_verification_rules_identification_difficulty2 as
select distinct vr.id as verification_rule_id,
  coalesce(vrdtvk.key, cttltaxon.external_key) as taxa_taxon_list_external_key,
  coalesce(vrdtvk.value, vrdtaxa.value)::int as id_difficulty
from verification_rules vr
left join verification_rule_data vrdtvk on vrdtvk.verification_rule_id=vr.id
  and vrdtvk.header_name='Data' and vrdtvk.deleted=false
left join verification_rule_data vrdtaxa on vrdtaxa.verification_rule_id=vr.id
  and vrdtaxa.header_name='Taxa' and vrdtaxa.deleted=false
left join cache_taxa_taxon_lists cttltaxon on cttltaxon.preferred_taxon=vrdtaxa.value and cttltaxon.preferred=true
where vr.test_type='IdentificationDifficulty'
and vr.deleted=false;

truncate cache_verification_rules_identification_difficulty;
insert into cache_verification_rules_identification_difficulty
  select * from cache_verification_rules_identification_difficulty2;
drop table cache_verification_rules_identification_difficulty2;

-- cache_verification_rules_period_within_year
create temp table cache_verification_rules_period_within_year2 as
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

truncate cache_verification_rules_period_within_year;
insert into cache_verification_rules_period_within_year
  select * from cache_verification_rules_period_within_year2;
drop table cache_verification_rules_period_within_year2;

-- cache_verification_rules_without_polygon
create temp table cache_verification_rules_without_polygon2 as
select distinct vr.id as verification_rule_id,
  vr.reverse_rule,
  vrmkey.value as taxa_taxon_list_external_key,
  vrd.value_geom as geom,
  vr.error_message
from verification_rules vr
join verification_rule_metadata vrmkey on vrmkey.verification_rule_id=vr.id and vrmkey.key='DataRecordId' and vrmkey.deleted=false
join verification_rule_metadata isSpecies on isSpecies.verification_rule_id=vr.id and isSpecies.value='Species' and isSpecies.deleted=false
join verification_rule_data vrd on vrd.verification_rule_id=vr.id and vrd.header_name='geom' and vrd.deleted=false
where vr.test_type='WithoutPolygon'
and vr.deleted=false;

truncate cache_verification_rules_without_polygon;
insert into cache_verification_rules_without_polygon
  select * from cache_verification_rules_without_polygon2;
drop table cache_verification_rules_without_polygon2;

-- Ensure that cache_taxa_taxon_lists has updated rule information.
update cache_taxa_taxon_lists cttl
set applicable_verification_rule_types=array[]::text[];

update cache_taxa_taxon_lists cttl
set applicable_verification_rule_types=array['period']
from verification_rule_metadata vrm
join verification_rules vr
  on vr.id = vrm.verification_rule_id
  and vr.test_type = 'Period'
  and vr.deleted = false
where vrm.key = 'Tvk'
  and vrm.value = cttl.external_key
  and vrm.deleted = false;

update cache_taxa_taxon_lists cttl
set applicable_verification_rule_types=applicable_verification_rule_types || array['period_within_year']
from cache_verification_rules_period_within_year pwy
where pwy.taxa_taxon_list_external_key=cttl.external_key;

update cache_taxa_taxon_lists cttl
set applicable_verification_rule_types=applicable_verification_rule_types || array['without_polygon']
from cache_verification_rules_without_polygon wp
where wp.taxa_taxon_list_external_key=cttl.external_key;

return true;

END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;