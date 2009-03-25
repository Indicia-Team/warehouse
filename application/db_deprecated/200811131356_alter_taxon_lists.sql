ALTER TABLE taxon_lists
ADD COLUMN deleted boolean NOT NULL DEFAULT FALSE; -- Has this list been deleted?

COMMENT ON COLUMN taxon_lists.deleted IS 'Has this list been deleted?';
