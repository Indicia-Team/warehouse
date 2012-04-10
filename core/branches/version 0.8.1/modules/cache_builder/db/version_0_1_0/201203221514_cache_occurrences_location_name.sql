ALTER TABLE cache_occurrences
   ADD COLUMN location_name character varying;

COMMENT ON COLUMN cache_occurrences.location_name IS 'Location name, either from the linked location or from the location_name field in sample.';

