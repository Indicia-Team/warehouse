-- Check: s_subject_observations_verified_status_check

ALTER TABLE identifiers_subject_observations DROP CONSTRAINT s_subject_observations_verified_status_check;

ALTER TABLE identifiers_subject_observations
  ADD CONSTRAINT s_subject_observations_verified_status_check CHECK (verified_status = ANY (ARRAY['V'::bpchar, 'M'::bpchar, 'U'::bpchar]));
