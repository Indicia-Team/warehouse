CREATE INDEX ix_cache_occurrences_functional_taxa_taxon_list_external_key
  ON cache_occurrences_functional
  USING btree
  (taxa_taxon_list_external_key);