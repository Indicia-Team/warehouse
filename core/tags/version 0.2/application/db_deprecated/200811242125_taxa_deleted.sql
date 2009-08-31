ALTER TABLE taxa 
ADD COLUMN deleted boolean NOT NULL DEFAULT FALSE; --Has this record been deleted?

COMMENT ON COLUMN taxa.deleted IS 'Has this record been deleted?';