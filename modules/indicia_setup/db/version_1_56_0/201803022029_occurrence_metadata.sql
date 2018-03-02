-- Column: metadata

-- ALTER TABLE indicia.occurrences DROP COLUMN metadata;

ALTER TABLE indicia.occurrences ADD COLUMN metadata jsonb;
COMMENT ON COLUMN indicia.occurrences.metadata IS 'Record metadata. Use this to store additional metadata that is not part of the actual record, e.g. information about a mobile device used for the record. For system use, not shown to the recorder.';
