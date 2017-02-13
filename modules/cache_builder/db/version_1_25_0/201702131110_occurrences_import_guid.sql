ALTER TABLE cache_occurrences_functional ADD COLUMN import_guid CHARACTER VARYING;
COMMENT ON COLUMN cache_occurrences_functional.import_guid IS 'Globally unique identifier of the import batch.';