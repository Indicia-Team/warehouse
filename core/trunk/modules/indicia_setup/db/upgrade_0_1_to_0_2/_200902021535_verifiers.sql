ALTER TABLE occurrences
ADD COLUMN verified_by_id integer,
ADD COLUMN verified_on timestamp without time zone,
ADD CONSTRAINT fk_occurrence_verifier FOREIGN KEY (verified_by_id)
REFERENCES users (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrences.verified_by_id IS 'Foreign key to the users table (verifier).';
COMMENT ON COLUMN occurrences.verified_on IS 'Date this record was verified.';

INSERT INTO people (first_name, surname, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('','Unknown', now(), 1, now(), 1);