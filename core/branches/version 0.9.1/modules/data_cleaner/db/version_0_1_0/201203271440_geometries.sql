ALTER TABLE verification_rule_data
   ADD COLUMN value_geom geometry;

COMMENT ON COLUMN verification_rule_data.value_geom IS 'For rules which require geometry data for testing, this can hold the geometry.';