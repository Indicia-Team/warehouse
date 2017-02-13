CREATE INDEX ix_cache_taxa_taxon_lists_preferred_taxa_taxon_list_id
  ON cache_taxa_taxon_lists
  USING btree
  (preferred);