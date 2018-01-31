-- #slow script#

select distinct vr.id as verification_rule_id,
  coalesce(vrdtvk.key, cttltaxon.external_key) as taxa_taxon_list_external_key,
  coalesce(vrdtvk.value, vrdtaxa.value) as id_difficulty
into cache_verification_rules_identification_difficulty
from verification_rules vr
left join verification_rule_data vrdtvk on vrdtvk.verification_rule_id=vr.id
  and vrdtvk.header_name='Data' and vrdtvk.deleted=false
left join verification_rule_data vrdtaxa on vrdtaxa.verification_rule_id=vr.id
  and vrdtaxa.header_name='Taxa' and vrdtaxa.deleted=false
left join cache_taxa_taxon_lists cttltaxon on cttltaxon.preferred_taxon=vrdtaxa.value and cttltaxon.preferred=true
where vr.test_type='IdentificationDifficulty'
and vr.deleted=false;

create index ix_cache_iddiff_vr_id on cache_verification_rules_identification_difficulty(verification_rule_id);
create index ix_cache_iddiff_external_key on cache_verification_rules_identification_difficulty(taxa_taxon_list_external_key);