ALTER TABLE samples
  ADD COLUMN forced_spatial_indexer_location_ids JSONB;

COMMENT ON COLUMN samples.forced_spatial_indexer_location_ids IS
'A JSON object where the keys are location type IDs and the properties are an array of location
IDs that should be used to force the spatial indexer to use these locations when indexing the
sample. The array of location IDs will normally contain a single location ID chosen by a verifier
when resolving the boundary of a record that overlaps 2 location boundaries.';

CREATE INDEX ix_samples_forced_spatial_indexer_location_ids ON samples USING gin(forced_spatial_indexer_location_ids);