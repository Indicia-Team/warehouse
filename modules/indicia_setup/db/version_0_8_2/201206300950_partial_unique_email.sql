ALTER TABLE people DROP CONSTRAINT unique_email;

CREATE UNIQUE INDEX unique_undeleted_email_constraint
  ON people
  USING btree
  (email_address)
  WHERE deleted = false;
COMMENT ON INDEX unique_undeleted_email_constraint IS 'A partial unique index to ensure that emails are unique across people, excluding those deleted.';
