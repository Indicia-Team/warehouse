-- #slow script#

UPDATE cache_taxon_searchterms cts
SET search_code=t.search_code
FROM taxa_taxon_lists ttl
JOIN taxa t on t.id=ttl.taxon_id AND t.deleted=false AND t.search_code IS NOT NULL
WHERE ttl.deleted=false
AND ttl.id=cts.taxa_taxon_list_id;