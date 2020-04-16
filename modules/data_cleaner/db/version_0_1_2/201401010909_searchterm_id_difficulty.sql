-- NEEDS TO RUN AFTER CORE SCRIPTS
CREATE OR REPLACE function f_update_cts (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 

IF EXISTS (SELECT * FROM pg_class WHERE relname='verification_rule_data') THEN

-- Update the existing searchterm entries with their ID difficulty rating.
update cache_taxon_searchterms cts 
  set identification_difficulty=vrd.value::integer, id_diff_verification_rule_id=vrd.verification_rule_id 
from cache_taxa_taxon_lists cttl 
join verification_rule_data vrd on vrd.header_name='Data' and upper(vrd.key)=cttl.external_key and vrd.deleted=false
join verification_rules vr on vr.id=vrd.verification_rule_id and vr.deleted=false
    and vr.test_type='IdentificationDifficulty'
where cttl.id=cts.preferred_taxa_taxon_list_id;

END IF; 

END
$func$;

SELECT f_update_cts();

DROP FUNCTION f_update_cts();