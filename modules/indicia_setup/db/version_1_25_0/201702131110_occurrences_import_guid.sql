ALTER TABLE occurrences ADD COLUMN import_guid CHARACTER VARYING;
COMMENT ON COLUMN occurrences.import_guid IS 'Globally unique identifier of the import batch.';