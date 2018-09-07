ALTER TABLE cache_occurrences_functional
ADD COLUMN location_ids integer[];

ALTER TABLE cache_samples_functional
ADD COLUMN location_ids integer[];

CREATE INDEX ix_cache_occurrences_functional_location_ids ON cache_occurrences_functional USING GIN(location_ids);
CREATE INDEX ix_cache_samples_functional_location_ids ON cache_samples_functional USING GIN(location_ids);