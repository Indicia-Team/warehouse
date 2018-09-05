-- #slow script#
UPDATE cache_occurrences_functional o
SET location_ids=(
  SELECT array_agg(distinct ils.location_id)
  FROM index_locations_samples ils
  WHERE ils.sample_id=o.sample_id
);

UPDATE cache_samples_functional o
SET location_ids=(
  SELECT array_agg(distinct ils.location_id)
  FROM index_locations_samples ils
  WHERE ils.sample_id=s.id
);

CREATE INDEX ix_cache_occurrences_functional_location_ids ON cache_occurrences_functional USING GIN(location_ids);
CREATE INDEX ix_cache_samples_functional_location_ids ON cache_samples_functional USING GIN(location_ids);

DROP TABLE index_locations_samples;