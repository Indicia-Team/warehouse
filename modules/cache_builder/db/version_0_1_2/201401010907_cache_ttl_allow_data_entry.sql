-- ensures that this field is populated if following an upgrade path where the field was added after initial use of the table
UPDATE cache_taxa_taxon_lists cttl
SET allow_data_entry=ttl.allow_data_entry
FROM taxa_taxon_lists ttl
WHERE ttl.id=cttl.id
AND ttl.allow_data_entry=false
AND cttl.allow_data_entry<>ttl.allow_data_entry;
