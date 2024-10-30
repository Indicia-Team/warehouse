ALTER TABLE cache_samples_functional 
ADD COLUMN import_guid 
CHARACTER VARYING;
COMMENT ON COLUMN cache_samples_functional.import_guid IS 'Globally unique identifier of the import batch.';