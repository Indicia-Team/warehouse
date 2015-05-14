ALTER TABLE samples
   ADD COLUMN record_status character(1) CONSTRAINT samples_record_status_check CHECK (record_status = ANY (ARRAY['I'::bpchar, 'C'::bpchar, 'V'::bpchar, 'R'::bpchar, 'T'::bpchar, 'D'::bpchar])),
   ADD COLUMN verified_by_id integer,
   ADD COLUMN verified_on timestamp without time zone,
   ADD CONSTRAINT fk_sample_verifier FOREIGN KEY (verified_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;


COMMENT ON COLUMN samples.record_status IS 'Status of this sample. I - in progress, C - completed, V - verified, R - rejected, D - dubious/queried (deprecated), T - test.';
COMMENT ON COLUMN samples.verified_by_id IS 'Foreign key to the users table (verifier of the sample).';
COMMENT ON COLUMN samples.verified_on IS 'Date this record was verified.';

ALTER TABLE sample_comments
   ADD COLUMN query boolean,
   ADD COLUMN record_status character(1) CONSTRAINT sample_comments_record_status_check CHECK (record_status = ANY (ARRAY['I'::bpchar, 'C'::bpchar, 'V'::bpchar, 'R'::bpchar, 'T'::bpchar, 'D'::bpchar]));
UPDATE sample_comments SET query=FALSE;
ALTER TABLE sample_comments ALTER query SET default false;

COMMENT ON COLUMN sample_comments.query IS 'Set to true if this comment asks a question that needs a response.';
COMMENT ON COLUMN sample_comments.record_status IS 'If this comment relates to the changing of the status of a sample, then determines the status it was changed to. Provides and audit trail of sample verification changes.';