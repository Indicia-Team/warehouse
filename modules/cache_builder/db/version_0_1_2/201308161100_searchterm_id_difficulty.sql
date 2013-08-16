ALTER TABLE cache_taxon_searchterms
   ADD COLUMN identification_difficulty integer;

COMMENT ON COLUMN cache_taxon_searchterms.identification_difficulty IS 'Identification difficulty assigned by the data_cleaner module, on a scale from 1 (easy) to 5 (difficult)';

ALTER TABLE cache_taxon_searchterms
   ADD COLUMN id_diff_verification_rule_id integer;

COMMENT ON COLUMN cache_taxon_searchterms.id_diff_verification_rule_id  IS 'Verification rule that is associated with the identification difficulty.';

-- Update the existing searchterm entries with their ID difficulty rating.
UPDATE cache_taxon_searchterms cts
SET identification_difficulty=extkey.value::integer, id_diff_verification_rule_id=vr.id
FROM cache_taxa_taxon_lists cttl  
JOIN verification_rule_data extkey ON extkey.key=LOWER(cttl.external_key) AND extkey.header_name='Data' AND extkey.deleted=false
JOIN verification_rules vr ON vr.id=extkey.verification_rule_id AND vr.test_type='IdentificationDifficulty' AND vr.deleted=false
WHERE cttl.id=cts.taxa_taxon_list_id