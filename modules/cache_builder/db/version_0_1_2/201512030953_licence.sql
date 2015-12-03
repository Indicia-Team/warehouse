ALTER TABLE cache_occurrences ADD COLUMN licence_id integer;
ALTER TABLE cache_occurrences ADD COLUMN licence_code character varying;

COMMENT ON COLUMN cache_occurrences.licence_id IS 'ID of the licence that is associated with this record.';
COMMENT ON COLUMN cache_occurrences.licence_code IS 'Abbreviation or code of the licence that is associated with this record.';
