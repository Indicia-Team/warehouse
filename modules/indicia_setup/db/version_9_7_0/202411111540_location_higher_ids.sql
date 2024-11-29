ALTER TABLE locations
ADD COLUMN higher_location_ids integer[];

COMMENT ON COLUMN locations.higher_location_ids IS 'For location types that are indexed spatially against another layer, lists the higher location IDs. For example a site may be indexed against a layer of locations.';

CREATE INDEX IF NOT EXISTS ix_locations_higher_location_ids
    ON locations USING gin
    (higher_location_ids);