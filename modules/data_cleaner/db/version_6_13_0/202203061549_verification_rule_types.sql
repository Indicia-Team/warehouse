-- #slow script#
UPDATE cache_taxa_taxon_lists cttl
SET applicable_verification_rule_types=ARRAY[]::text[];

UPDATE cache_taxa_taxon_lists cttl
SET applicable_verification_rule_types=ARRAY['period']
FROM verification_rule_metadata vrm
JOIN verification_rules vr
  ON vr.id = vrm.verification_rule_id
  AND vr.test_type = 'Period'
  AND vr.deleted = false
WHERE vrm.key = 'Tvk'
  AND vrm.value = cttl.external_key
  AND vrm.deleted = false;

UPDATE cache_taxa_taxon_lists cttl
SET applicable_verification_rule_types=applicable_verification_rule_types || ARRAY['period_within_year']
FROM cache_verification_rules_period_within_year pwy
WHERE pwy.taxa_taxon_list_external_key=cttl.external_key;

UPDATE cache_taxa_taxon_lists cttl
SET applicable_verification_rule_types=applicable_verification_rule_types || ARRAY['without_polygon']
FROM cache_verification_rules_without_polygon wp
WHERE wp.taxa_taxon_list_external_key=cttl.external_key;

-- Consider doing this in chunks so that tracking does not cause Logstash
-- queue to grow huge.
UPDATE cache_occurrences_functional o
SET applied_verification_rule_types=cttl.applicable_verification_rule_types
FROM cache_taxa_taxon_lists cttl
WHERE cttl.external_key=o.taxa_taxon_list_external_key
AND o.data_cleaner_result is not null;