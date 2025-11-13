ALTER TABLE taxa_taxon_lists
ADD COLUMN IF NOT EXISTS manually_entered boolean DEFAULT false;

COMMENT ON COLUMN taxa_taxon_lists.manually_entered IS 'Set to true if a taxon was manually entered onto the system. This allows external synchronisation scripts to process manually added taxa differently to automatically added ones.';