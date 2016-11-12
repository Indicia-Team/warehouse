-- #slow script#

update cache_taxa_taxon_lists cttl
  set identification_difficulty=vrd.value::integer
from verification_rule_data vrd
join verification_rules vr on vr.id=vrd.verification_rule_id and vr.deleted=false
    and vr.test_type='IdentificationDifficulty'
where vrd.header_name='Data' and upper(vrd.key)=cttl.external_key and vrd.deleted=false;