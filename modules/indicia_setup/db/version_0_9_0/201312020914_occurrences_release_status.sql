ALTER TABLE occurrences ADD COLUMN release_status character(1);
ALTER TABLE occurrences ALTER COLUMN release_status SET DEFAULT 'R'::bpchar;
COMMENT ON COLUMN occurrences.release_status IS 'Release states of this record. R - released, P - recorder has requested a precheck before release, U - unreleased as part of a project whcih is witholding records until completion.';

ALTER TABLE occurrences
  ADD CONSTRAINT occurrences_release_status_check CHECK (release_status = ANY (ARRAY['R'::bpchar, 'P'::bpchar, 'U'::bpchar]));
  
UPDATE occurrences co
SET release_status='R'

