-- UPDATE SHOULD BE DELAYED TILL AFTER CORE SCRIPTS

-- update existing data
UPDATE cache_taxon_searchterms cts
SET parent_id=ttl.parent_id, preferred_taxa_taxon_list_id=ttl.preferred_taxa_taxon_list_id
FROM cache_taxa_taxon_lists ttl
WHERE ttl.id=cts.taxa_taxon_list_id;