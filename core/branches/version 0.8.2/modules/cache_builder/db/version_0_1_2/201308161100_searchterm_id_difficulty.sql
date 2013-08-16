ALTER TABLE cache_taxon_searchterms
   ADD COLUMN identification_difficulty integer;

COMMENT ON COLUMN cache_taxon_searchterms.identification_difficulty IS 'Identification difficulty assigned by the data_cleaner module, on a scale from 1 (easy) to 5 (difficult)';

ALTER TABLE cache_taxon_searchterms
   ADD COLUMN id_diff_verification_rule_id integer;

COMMENT ON COLUMN cache_taxon_searchterms.id_diff_verification_rule_id  IS 'Verification rule that is associated with the identification difficulty.';

-- Update the existing searchterm entries with their ID difficulty rating.
update cache_taxon_searchterms cts 
  set identification_difficulty=vrd.value::integer, id_diff_verification_rule_id=vrd.verification_rule_id 
from cache_taxa_taxon_lists cttl 
join verification_rule_data vrd on vrd.header_name='Data' and upper(vrd.key)=cttl.external_key and vrd.deleted=false
join verification_rules vr on vr.id=vrd.verification_rule_id and vr.deleted=false
    and vr.test_type='IdentificationDifficulty'
where cttl.id=cts.preferred_taxa_taxon_list_id 