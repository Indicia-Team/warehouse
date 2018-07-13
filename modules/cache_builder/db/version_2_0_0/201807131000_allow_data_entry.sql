-- Force allow_data_entry=false taxa back into cache_taxa_taxon_lists
UPDATE taxa_taxon_lists
SET updated_on=now()
WHERE allow_data_entry=false;