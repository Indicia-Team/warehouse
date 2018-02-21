-- #slow script#

select distinct vr.id as verification_rule_id,
  vr.reverse_rule,
  vrmkey.value as taxa_taxon_list_external_key,
  vrd.value_geom as geom,
  vr.error_message
into cache_verification_rules_without_polygon
from verification_rules vr
join verification_rule_metadata vrmkey on vrmkey.verification_rule_id=vr.id and vrmkey.key='DataRecordId' and vrmkey.deleted=false
join verification_rule_metadata isSpecies on isSpecies.verification_rule_id=vr.id and isSpecies.value='Species' and isSpecies.deleted=false
join verification_rule_data vrd on vrd.verification_rule_id=vr.id and vrd.header_name='geom' and vrd.deleted=false
where vr.test_type='WithoutPolygon'
and vr.deleted=false;

create index ix_cache_wp_vr_id on cache_verification_rules_without_polygon(verification_rule_id);
create index ix_cache_wp_external_key on cache_verification_rules_without_polygon(taxa_taxon_list_external_key);
