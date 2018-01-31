-- #slow script#

DROP INDEX IF EXISTS ix_cache_occurrences_functional_map_sq_10km_id;

CREATE INDEX ix_cache_occurrences_functional_map_sq_10km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_10km_id);

DROP INDEX IF EXISTS ix_cache_occurrences_functional_map_sq_2km_id;

CREATE INDEX ix_cache_occurrences_functional_map_sq_2km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_2km_id);

DROP INDEX IF EXISTS ix_cache_occurrences_functional_map_sq_1km_id;

CREATE INDEX ix_cache_occurrences_functional_map_sq_1km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_1km_id);

DROP INDEX IF EXISTS ix_cache_samples_functional_map_sq_10km_id;

CREATE INDEX ix_cache_samples_functional_map_sq_10km_id
  ON cache_samples_functional
  USING btree
  (map_sq_10km_id);

DROP INDEX IF EXISTS ix_cache_samples_functional_map_sq_2km_id;

CREATE INDEX ix_cache_samples_functional_map_sq_2km_id
  ON cache_samples_functional
  USING btree
  (map_sq_2km_id);

DROP INDEX IF EXISTS ix_cache_samples_functional_map_sq_1km_id;

CREATE INDEX ix_cache_samples_functional_map_sq_1km_id
  ON cache_samples_functional
  USING btree
  (map_sq_1km_id);