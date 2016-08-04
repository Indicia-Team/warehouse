CREATE INDEX ix_cache_occurrences_functional_location_id
  ON cache_occurrences_functional
  USING btree
  (location_id);

CREATE INDEX ix_cache_samples_functional_location_id
  ON cache_samples_functional
  USING btree
  (location_id);