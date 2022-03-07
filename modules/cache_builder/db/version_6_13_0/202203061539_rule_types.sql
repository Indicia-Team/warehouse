ALTER TABLE cache_taxa_taxon_lists ADD COLUMN applicable_verification_rule_types text[];

COMMENT ON COLUMN cache_taxa_taxon_lists.applicable_verification_rule_types IS
  'List of data_cleaner verification rule types that apply to this taxon.';

ALTER TABLE cache_occurrences_functional ADD COLUMN applied_verification_rule_types text[];

COMMENT ON COLUMN cache_occurrences_functional.applied_verification_rule_types IS
  'List of data_cleaner verification rule types that have been applied to this occurrence.';