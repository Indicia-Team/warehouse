-- Index to improve performance on WithoutPolygon data cleaning.
CREATE INDEX ix_cache_occurrences_functional_ttl_ext_key_map_sq_v
  ON cache_occurrences_functional(taxa_taxon_list_external_key, map_sq_10km_id)
  WHERE record_status='V';