ALTER TABLE occurrences DROP CONSTRAINT occurrences_record_status_check;

ALTER TABLE occurrences
  ADD CONSTRAINT occurrences_record_status_check CHECK (record_status = ANY (ARRAY['I'::bpchar, 'C'::bpchar, 'V'::bpchar, 'R'::bpchar, 'T'::bpchar, 'S'::bpchar, 'D'::bpchar]));

COMMENT ON COLUMN occurrences.record_status IS 'Status of this record. I - in progress, C - completed, V - verified, R - rejected, D - dubious, T - test, S - Sent for verification.';
