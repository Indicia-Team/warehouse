ALTER TABLE index_groups_locations
	DROP CONSTRAINT IF EXISTS fk_index_group_locations_location_type;

ALTER TABLE index_groups_locations
  ADD COLUMN IF NOT EXISTS location_type_id integer,
  ADD CONSTRAINT fk_index_group_locations_location_type FOREIGN KEY (location_type_id)
    REFERENCES termlists_terms (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE NO ACTION;

COMMENT ON COLUMN index_groups_locations.location_type_id IS 'Location type of the indexed group, allowing different granularities of location indexing to be utilised.';