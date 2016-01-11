-- #slow script#
ALTER TABLE index_locations_samples
  ADD location_type_id integer NULL;

COMMENT ON COLUMN index_locations_samples.location_type_id IS 'Location type for the location to aid filtering. FK to termlists_terms.';

UPDATE index_locations_samples ils
SET location_type_id=l.location_type_id
FROM locations l
WHERE l.id=ils.location_id;
