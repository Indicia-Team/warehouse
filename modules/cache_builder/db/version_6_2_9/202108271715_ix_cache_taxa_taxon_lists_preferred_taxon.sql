CREATE INDEX ix_cache_taxa_taxon_lists_preferred_taxon
  ON cache_taxa_taxon_lists
  USING btree
  (preferred_taxon);
