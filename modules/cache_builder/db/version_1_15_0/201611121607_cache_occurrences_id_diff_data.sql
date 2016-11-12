-- #slow script#

update cache_occurrences_functional o
  set identification_difficulty=vrd.value::integer
from verification_rule_data vrd
join verification_rules vr on vr.id=vrd.verification_rule_id and vr.deleted=false
    and vr.test_type='IdentificationDifficulty'
where vrd.header_name='Data' and upper(vrd.key)=o.taxa_taxon_list_external_key and vrd.deleted=false;