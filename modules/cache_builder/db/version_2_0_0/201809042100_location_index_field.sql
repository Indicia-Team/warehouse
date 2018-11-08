ALTER TABLE cache_occurrences_functional
ADD COLUMN location_ids integer[];

ALTER TABLE cache_samples_functional
ADD COLUMN location_ids integer[];