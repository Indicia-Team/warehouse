-- Column: metadata

-- ALTER TABLE occurrences DROP COLUMN metadata;

ALTER TABLE occurrences ADD COLUMN metadata json;
COMMENT ON COLUMN occurrences.metadata IS 'Record metadata. Use this to store additional metadata that is not part of the actual record, e.g. information about a mobile device used for the record. For system use, not shown to the recorder.';
