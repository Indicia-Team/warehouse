
CREATE INDEX ix_cache_occurrences_functional_parent_sample_id
  ON cache_occurrences_functional
  USING btree
  (parent_sample_id);

CREATE INDEX ix_cache_samples_functional_parent_sample_id
  ON cache_samples_functional
  USING btree
  (parent_sample_id);