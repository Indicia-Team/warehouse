-- #slow script#
CREATE INDEX ix_cache_occurrences_functional_verify_taxa
  ON cache_occurrences_functional
  USING GIN(website_id, taxon_path)
WHERE record_status='C' and record_substatus IS NULL;
CREATE INDEX ix_cache_occurrences_functional_verify_taxon_group
  ON cache_occurrences_functional(website_id, taxon_group_id)
WHERE record_status='C' and record_substatus IS NULL;
CREATE INDEX ix_cache_occurrences_functional_verify_family
  ON cache_occurrences_functional(website_id, family_taxa_taxon_list_id)
WHERE record_status='C' and record_substatus IS NULL;