ALTER TABLE people DROP CONSTRAINT unique_email;

CREATE UNIQUE INDEX ix_unique_email_address ON people (email_address)
    WHERE deleted='f';
