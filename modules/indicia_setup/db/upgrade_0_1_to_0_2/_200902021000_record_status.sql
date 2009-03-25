ALTER TABLE occurrences
ADD COLUMN record_status char(1) DEFAULT 'I' CHECK (record_status IN ('I', 'C', 'V'));
COMMENT ON COLUMN occurrences.record_status IS 'Progress of this record. I - in progress, C - completed, V - verified.';
