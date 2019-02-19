-- #slow script#
CREATE INDEX ix_cache_samples_functional_group_id
  ON cache_samples_functional
  USING btree
  (group_id);
