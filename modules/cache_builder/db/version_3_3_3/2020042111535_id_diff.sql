-- #slow script#
DROP TRIGGER trigger_cache_occurrences_functional_changed ON cache_occurrences_functional;
CREATE TRIGGER trigger_cache_occurrences_functional_changed
    BEFORE INSERT OR UPDATE
    ON indicia.cache_occurrences_functional
    FOR EACH ROW WHEN (current_setting('session_replication_role') <> 'local')
    EXECUTE PROCEDURE indicia.cache_functional_changed();

-- Disable tracking increments, so doesn't force a complete ES refresh.
SET session_replication_role = 'local';

UPDATE cache_occurrences_functional o
SET identification_difficulty=extkey.value::integer
FROM verification_rule_data extkey
JOIN verification_rules vr ON vr.id=extkey.verification_rule_id AND vr.test_type='IdentificationDifficulty' AND vr.deleted=false
WHERE extkey.key=UPPER(o.taxa_taxon_list_external_key) AND extkey.header_name='Data' AND extkey.deleted=false;