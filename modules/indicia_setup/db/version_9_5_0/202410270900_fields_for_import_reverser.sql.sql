ALTER TABLE samples 
ADD COLUMN import_guid 
CHARACTER VARYING;
COMMENT ON COLUMN samples.import_guid IS 'Globally unique identifier of the import batch.';

ALTER TABLE imports
ADD COLUMN reversible
BOOLEAN default FALSE;
COMMENT ON COLUMN samples.import_guid IS 'FALSE if reverse is not permitted (such as if import has already been reversed).';