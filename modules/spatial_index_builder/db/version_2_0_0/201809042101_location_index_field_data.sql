-- #slow script#
UPDATE cache_occurrences_functional o
SET location_ids=(
  SELECT array_agg(distinct ils.location_id)
  FROM index_locations_samples ils
  WHERE ils.sample_id=o.sample_id
);

UPDATE cache_samples_functional s
SET location_ids=(
  SELECT array_agg(distinct ils.location_id)
  FROM index_locations_samples ils
  WHERE ils.sample_id=s.id
);