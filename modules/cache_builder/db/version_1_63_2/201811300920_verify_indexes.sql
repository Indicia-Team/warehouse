-- #slow script#
CREATE INDEX ix_cache_occurrences_functional_verify_ext_key
  ON cache_occurrences_functional(website_id, taxa_taxon_list_external_key)
WHERE record_status='C' and record_substatus IS NULL;
CREATE INDEX ix_cache_occurrences_functional_verify_taxon_group
  ON cache_occurrences_functional(website_id, taxon_group_id)
WHERE record_status='C' and record_substatus IS NULL;
CREATE INDEX ix_cache_occurrences_functional_verify_family
  ON cache_occurrences_functional(website_id, family_taxa_taxon_list_id)
WHERE record_status='C' and record_substatus IS NULL;