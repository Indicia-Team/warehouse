ALTER TABLE taxa_taxon_lists
ADD COlUMN updated_on timestamp without time zone NOT NULL, -- Date this record was updated
ADD COLUMN updated_by_id integer NOT NULL, -- Foreign key to the users table (updater).
ADD COLUMN deleted BOOLEAN NOT NULL DEFAULT FALSE;

COMMENT ON COLUMN taxa_taxon_lists.updated_on IS 'Date this record was updated.';
COMMENT ON COLUMN taxa_taxon_lists.updated_by_id IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN taxa_taxon_lists.deleted IS 'Has this record been deleted?';

ALTER TABLE termlists_terms
ADD COLUMN deleted BOOLEAN NOT NULL DEFAULT FALSE;

COMMENT ON COLUMN termlists_terms.deleted IS 'Has this record been deleted?';

