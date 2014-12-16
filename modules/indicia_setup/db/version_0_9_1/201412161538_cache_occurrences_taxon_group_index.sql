CREATE INDEX ix_cache_occurrences_taxon_group_id
  ON cache_occurrences
  USING btree
  (taxon_group_id);