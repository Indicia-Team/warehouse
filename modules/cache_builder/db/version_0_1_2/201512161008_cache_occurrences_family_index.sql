-- #slow script#
CREATE INDEX ix_cache_occurrences_family_taxa_taxon_list_id
  ON cache_occurrences
  USING btree
  (family_taxa_taxon_list_id);