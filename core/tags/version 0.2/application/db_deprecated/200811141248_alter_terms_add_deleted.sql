ALTER TABLE terms
ADD COLUMN deleted BOOLEAN NOT NULL DEFAULT FALSE;

COMMENT ON COLUMN terms.deleted IS 'Has this term been deleted?';