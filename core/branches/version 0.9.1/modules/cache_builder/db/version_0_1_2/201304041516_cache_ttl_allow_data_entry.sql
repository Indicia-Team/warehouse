ALTER TABLE cache_taxa_taxon_lists ADD COLUMN allow_data_entry boolean DEFAULT true;

UPDATE cache_taxa_taxon_lists cttl
SET allow_data_entry=ttl.allow_data_entry
FROM taxa_taxon_lists ttl
WHERE ttl.id=cttl.id
AND ttl.allow_data_entry=false;
