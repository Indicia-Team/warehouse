ALTER TABLE rest_api_sync_taxon_mappings
ADD COLUMN other_taxon_rank VARCHAR(255);

COMMENT ON COLUMN rest_api_sync_taxon_mappings.other_taxon_rank IS 'The taxonomic rank of the source name.';